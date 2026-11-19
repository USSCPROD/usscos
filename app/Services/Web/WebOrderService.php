<?php

declare(strict_types=1);

namespace App\Services\Web;

use App\Core\Config;
use App\Core\Database;
use App\Repositories\CustomerRepository;
use App\Repositories\SalesOrderRepository;
use App\Repositories\WebCheckoutRepository;
use App\Services\ShippingQuoteService;
use App\Services\Stencils\DesignerError;
use App\Services\Stencils\EndzoneDesigner;
use App\Services\Stencils\MidfieldDesigner;
use App\Services\WebsiteLeadService;

/**
 * The website checkout, from cart to paid sales order.
 *
 *   shipping()  FedEx rates for the cart: the shop's rolled-stencil packing, USSCOS's FedEx
 *   start()     price the cart, record it (web_checkouts), and hand it to Square to pay
 *   complete()  once Square says it is paid, make the sales order. Idempotent: the return
 *               page, the webhook and the reconcile script may all call it for the same
 *               payment, and only the first makes an order.
 *
 * THE ORDER (Chip, 19 Nov 2026):
 *   customer   an existing customer with the buyer's email, else a new retail customer
 *              from the checkout's name and address. A new one that looks like someone
 *              already on file is noted on the order for staff to merge.
 *   tax        none, as on the stencil shop, until the accountant says where to collect.
 *   payment    recorded as a credit-card payment with Square's payment id, so the order
 *              arrives paid and goes to the warehouse like any other.
 */
class WebOrderService
{
    private WebCheckoutRepository $checkouts;
    private SquareClient $square;

    public function __construct()
    {
        $this->checkouts = new WebCheckoutRepository();
        $this->square    = new SquareClient();
    }

    public function enabled(): bool
    {
        return $this->square->configured();
    }

    /**
     * Shipping options for a cart to a ZIP, cheapest service first in the shop's list order.
     *
     * @return array{zip:string, options: array<int,array{id:string,name:string,price:float}>}
     * @throws DesignerError
     */
    public function shipping(array $items, string $zip, bool $residential): array
    {
        $zip    = self::zip($zip);
        $priced = (new CartService())->price($items);
        $boxes  = array_map(static fn($p) => ['weight' => $p['weight'], 'length' => $p['dims'][0], 'width' => $p['dims'][1],
                                               'height' => $p['dims'][2]], StencilPacking::packages($priced['pieces']));

        $res = (new ShippingQuoteService())->rate(['zip' => $zip, 'residential' => $residential], $boxes,
            (array)(Config::get('shipping.web_services') ?? []), self::handling());

        if (empty($res['ok'])) {
            error_log('web shipping: ' . ($res['message'] ?? 'no rates') . ' for ' . $zip);
            throw new DesignerError(str_contains(strtoupper((string)($res['message'] ?? '')), 'POSTAL')
                ? "FedEx didn't recognize that ZIP code. Please check it and try again."
                : "We couldn't get FedEx rates right now. Please try again, or call us to order.");
        }

        $options = array_map(static fn($r) => ['id' => $r['service'], 'name' => $r['label'], 'price' => $r['charge']], $res['rates']);
        usort($options, static fn($a, $b) => $a['price'] <=> $b['price']);

        return ['zip' => $zip, 'options' => $options, 'subtotal' => $priced['subtotal']];
    }

    /**
     * @return array{url:string}
     * @throws DesignerError
     */
    public function start(array $items, string $zip, bool $residential, string $service, string $origin): array
    {
        if (!$this->square->configured()) {
            throw new DesignerError("Online checkout isn't set up yet. Please call or email us to place your order.");
        }

        $priced  = (new CartService())->price($items);
        $ship    = $this->shipping($items, $zip, $residential);
        $chosen  = null;

        foreach ($ship['options'] as $o) {
            if ($o['id'] === $service) {
                $chosen = $o;
            }
        }

        if ($chosen === null) {
            throw new DesignerError('Please choose a shipping option.');
        }

        $token = bin2hex(random_bytes(16));
        $id = $this->checkouts->create([
            'token' => $token, 'items' => $items, 'lines' => $priced['lines'], 'subtotal' => $priced['subtotal'],
            'shipping_service' => $chosen['id'], 'shipping_name' => $chosen['name'], 'shipping_amount' => $chosen['price'],
            'total' => round($priced['subtotal'] + $chosen['price'], 2), 'ship_zip' => $ship['zip'],
            'residential' => $residential, 'square_environment' => $this->square->environment(),
        ]);

        $lines = array_map(static fn($l) => [
            'name' => $l['name'], 'qty' => $l['qty'], 'unit' => $l['unit'],
            'note' => trim(($l['sku'] ? 'SKU ' . $l['sku'] . ' · ' : '') . ($l['detail'] ?? '')),
            'meta' => ['kind' => $l['kind'], 'sku' => (string)$l['sku']],
        ], $priced['lines']);

        try {
            $link = $this->square->paymentLink($lines, $chosen, $ship['zip'], $token, rtrim($origin, '/') . '/checkout/thanks?c=' . $token);
        } catch (\RuntimeException $e) {
            throw new DesignerError($e->getMessage());
        }

        $this->checkouts->setSquare($id, $link['link_id'], $link['order_id']);

        return ['url' => $link['url'], 'token' => $token];
    }

    /**
     * Turn a paid checkout into a sales order. Safe to call any number of times.
     *
     * @return array{state:string, so_id:?int, so_number:?string}  state: ordered | unpaid | failed
     */
    public function complete(array $checkout): array
    {
        if ($checkout['status'] === 'ordered') {
            return ['state' => 'ordered', 'so_id' => (int)$checkout['sales_order_id'], 'so_number' => $this->soNumber((int)$checkout['sales_order_id'])];
        }

        if (empty($checkout['square_order_id'])) {
            return ['state' => 'unpaid', 'so_id' => null, 'so_number' => null];
        }

        try {
            $order = $this->square->order((string)$checkout['square_order_id']);
        } catch (\RuntimeException) {
            return ['state' => 'unpaid', 'so_id' => null, 'so_number' => null];   // try again later
        }

        $paid = $this->square->paidState($order);

        if (!$paid['paid']) {
            return ['state' => 'unpaid', 'so_id' => null, 'so_number' => null];
        }

        // Paid what was quoted? Square adds nothing we did not send, so a difference means
        // something is wrong, and a person should look before goods go out.
        if (abs($paid['amount'] - (float)$checkout['total']) > 0.009) {
            $this->checkouts->markFailed((int)$checkout['id'],
                sprintf('Paid %.2f but the checkout was for %.2f.', $paid['amount'], (float)$checkout['total']));
            $this->alert($checkout, 'paid a different amount from the checkout');
            return ['state' => 'failed', 'so_id' => null, 'so_number' => null];
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $row = $this->checkouts->lock((int)$checkout['id']);

            if ($row && $row['status'] === 'ordered') {          // another caller got here first
                $pdo->commit();
                return ['state' => 'ordered', 'so_id' => (int)$row['sales_order_id'], 'so_number' => $this->soNumber((int)$row['sales_order_id'])];
            }

            $buyer = self::buyer($order, $paid['payment_id'] !== '' ? $this->paymentQuietly($paid['payment_id']) : []);
            [$customerId, $dupNote] = $this->customer($buyer);
            $soId = $this->salesOrder($row, $buyer, $customerId, $dupNote, $paid);

            $this->checkouts->markOrdered((int)$row['id'], $soId, $paid['payment_id']);
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('web order failed for checkout ' . $checkout['id'] . ': ' . $e->getMessage());
            $this->checkouts->markFailed((int)$checkout['id'], $e->getMessage());
            $this->alert($checkout, 'was paid, but the sales order could not be made: ' . $e->getMessage());

            return ['state' => 'failed', 'so_id' => null, 'so_number' => null];
        }

        $number = $this->soNumber($soId);
        $this->notify($row, $buyer, $number, $soId);

        return ['state' => 'ordered', 'so_id' => $soId, 'so_number' => $number];
    }

    /** Open checkouts Square may have been paid for while nobody was watching. */
    public function reconcile(): array
    {
        $out = [];

        foreach ($this->checkouts->openOlderThan(10) as $c) {
            $out[] = ['token' => $c['token'], 'created' => $c['created_at']] + $this->complete($c);
        }

        return $out;
    }

    // ── The order ────────────────────────────────────────────────────────────

    /** The person, address and email Square collected. */
    private static function buyer(array $order, array $payment): array
    {
        $r = [];
        foreach ($order['fulfillments'] ?? [] as $f) {
            $r = $f['shipment_details']['recipient'] ?? [];
            if ($r !== []) {
                break;
            }
        }

        $a = $r['address'] ?? ($payment['shipping_address'] ?? []);

        return [
            'name'    => trim((string)($r['display_name'] ?? trim(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? '')))),
            'email'   => strtolower(trim((string)($r['email_address'] ?? $payment['buyer_email_address'] ?? ''))),
            'phone'   => trim((string)($r['phone_number'] ?? '')),
            'org'     => trim((string)($a['organization'] ?? '')),
            'line1'   => trim((string)($a['address_line_1'] ?? '')),
            'line2'   => trim((string)($a['address_line_2'] ?? '')),
            'city'    => trim((string)($a['locality'] ?? '')),
            'state'   => trim((string)($a['administrative_district_level_1'] ?? '')),
            'zip'     => trim((string)($a['postal_code'] ?? '')),
        ];
    }

    private function paymentQuietly(string $id): array
    {
        try {
            return $this->square->payment($id);
        } catch (\RuntimeException) {
            return [];
        }
    }

    /** @return array{0:int,1:string} customer id, and a note when it may be a duplicate */
    private function customer(array $b): array
    {
        $repo = new CustomerRepository();

        if ($b['email'] !== '') {
            $found = $repo->findActiveByEmail($b['email']);

            if ($found !== false) {
                return [(int)$found['id'], ''];
            }
        }

        $name = $b['org'] !== '' ? $b['org'] : ($b['name'] !== '' ? $b['name'] : 'Website customer ' . date('Y-m-d'));
        $dups = $repo->findPossibleDuplicates($name, $b['email'], $b['phone']);
        $id = $repo->insertCustomer([
            'company_name' => $name, 'quickbooks_name' => '', 'email' => $b['email'], 'phone' => $b['phone'],
            'customer_type' => \App\Services\CustomerPricing::RETAIL,
            'bill_address_1' => $b['line1'], 'bill_city' => $b['city'], 'bill_state' => $b['state'], 'bill_zip' => $b['zip'],
            'sales_rep_id' => null,
        ]);

        $note = $dups === [] ? '' : 'New website customer that may already be on file: '
            . implode(', ', array_map(static fn($d) => $d['company_name'] . ' (#' . $d['id'] . ')', array_slice($dups, 0, 5)))
            . '. Merge if it is the same.';

        return [$id, $note];
    }

    private function salesOrder(array $c, array $b, int $customerId, string $dupNote, array $paid): int
    {
        $repo   = new SalesOrderRepository();
        $lines  = json_decode((string)$c['priced_lines'], true) ?: [];
        $userId = (int)(((new WebsiteLeadService())->routing()['stencil_user_id'] ?? null) ?: 1);
        $soLines = [];
        $notes = [];

        foreach ($lines as $l) {
            $product = $l['kind'] === 'stock' ? $this->productBySku((string)$l['sku'])
                                              : $this->customProduct((string)$l['sku'], $l['kind']);
            $soLines[] = [
                'product_id' => $product['id'] ?? null, 'quickbooks_item' => $product['quickbooks_item'] ?? null,
                'description' => mb_substr($l['name'], 0, 500), 'qty_ordered' => (float)$l['qty'], 'uom_id' => null,
                'unit_price' => (float)$l['unit'], 'discount_pct' => 0, 'taxable' => 0,
                'line_total' => round($l['unit'] * $l['qty'], 2),
            ];

            if ($l['kind'] !== 'stock') {
                $notes[] = $this->customNote($l);
            }
        }

        $soLines[] = ['product_id' => null, 'quickbooks_item' => 'SH',
                      'description' => 'Shipping and Handling — ' . $c['shipping_name'], 'qty_ordered' => 1.0, 'uom_id' => null,
                      'unit_price' => (float)$c['shipping_amount'], 'discount_pct' => 0, 'taxable' => 0,
                      'line_total' => (float)$c['shipping_amount']];

        $internal = array_filter([
            self::zipWarning($c, $b),
            'Website order, paid by card through Square (' . $c['square_environment'] . '). Square order ' . $c['square_order_id']
                . ', payment ' . $paid['payment_id'] . '.',
            $b['email'] !== '' ? 'Buyer: ' . $b['name'] . ' <' . $b['email'] . '>' . ($b['phone'] !== '' ? ', ' . $b['phone'] : '') : '',
            $dupNote,
            ...$notes,
        ]);

        $soId = $repo->create([
            ':so_number' => $repo->nextSoNumber(), ':customer_id' => $customerId, ':created_by' => $userId,
            ':status' => 'confirmed', ':order_date' => date('Y-m-d'),
            ':ship_name' => $b['org'] !== '' ? $b['org'] . ($b['name'] !== '' ? ' — ' . $b['name'] : '') : ($b['name'] ?: null),
            ':ship_address_1' => $b['line1'] ?: null, ':ship_address_2' => $b['line2'] ?: null,
            ':ship_city' => $b['city'] ?: null, ':ship_state' => $b['state'] ?: null, ':ship_zip' => $b['zip'] ?: null,
            ':ship_phone' => $b['phone'] ?: null,
            ':subtotal' => round((float)$c['subtotal'] + (float)$c['shipping_amount'], 2), ':total_amount' => (float)$c['total'],
            ':memo' => null, ':internal_notes' => implode("\n\n", $internal),
            ':channel' => 'website', ':channel_reference' => (string)$c['square_order_id'],
        ]);

        $repo->replaceLineItems($soId, $soLines);
        $repo->collectPaymentInTransaction($soId, 'credit_card', 'Square ' . $paid['payment_id'], (float)$c['total'], $userId);

        return $soId;
    }

    /** What the art team needs to make a custom line: the design, and the logo for a midfield. */
    private function customNote(array $l): string
    {
        $d = $l['design'] ?? [];

        return match ($l['kind']) {
            'text' => 'Custom stencil: "' . str_replace("\n", ' / ', (string)($d['text'] ?? '')) . '", size ' . ($d['size'] ?? '') . ', ' . ($d['thickness'] ?? '') . '.',
            'endzone' => 'Endzone: ' . $l['name'] . '. Proof: /custom/proof?' . http_build_query(['kind' => 'endzone'] + array_intersect_key($d, array_flip(['text', 'size', 'style', 'format', 'fill', 'line']))),
            'midfield' => 'Midfield: ' . $l['name'] . '. Proof: /custom/proof?' . http_build_query(['kind' => 'midfield'] + array_intersect_key($d, array_flip(['logo', 'field', 'width'])))
                . $this->logoCopy((string)($d['logo'] ?? '')),
            default => '',
        };
    }

    /** The customer's original logo, copied into the lead files so it outlives the upload folder. */
    private function logoCopy(string $logo): string
    {
        $file = MidfieldDesigner::path($logo, 'original');

        if ($file === null) {
            return '';
        }

        $dir = BASE_PATH . '/storage/uploads/lead-files';
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $stored = bin2hex(random_bytes(16)) . '.' . $ext;

        if ((is_dir($dir) || @mkdir($dir, 0775, true)) && @copy($file, "{$dir}/{$stored}")) {
            return ' Their logo: /leads/artwork?f=' . $stored;
        }

        return '';
    }

    private function productBySku(string $sku): array
    {
        return $this->checkouts->productForLine($sku);
    }

    /** STEN-CUSTOM-*: the product a custom line is sold as. Made once if it is missing. */
    private function customProduct(string $sku, string $kind): array
    {
        return $this->checkouts->ensureCustomProduct($sku,
            ['text' => 'Custom Stencil', 'endzone' => 'Endzone Stencils', 'midfield' => 'Midfield Logo Stencil'][$kind] ?? 'Custom Stencil');
    }

    private function soNumber(int $id): ?string
    {
        return $id > 0 ? (new SalesOrderRepository())->soNumber($id) : null;
    }

    // ── Telling people ───────────────────────────────────────────────────────

    private function notify(array $c, array $b, ?string $number, int $soId): void
    {
        $intake  = new WebsiteLeadService();
        $routing = $intake->routing();
        $rows = '';

        foreach (json_decode((string)$c['priced_lines'], true) ?: [] as $l) {
            $rows .= $intake->row($l['qty'] . ' ×', $l['name'] . ' — $' . number_format($l['unit'] * $l['qty'], 2));
        }

        $warning = self::zipWarning($c, $b);

        $intake->notify($routing['stencil_email'], 'New website order ' . $number . ' — $' . number_format((float)$c['total'], 2)
                . ($warning !== '' ? ' — CHECK SHIPPING' : ''),
            ($warning !== '' ? '<p style="color:#b91c1c;font-weight:bold">' . htmlspecialchars($warning) . '</p>' : '')
            . '<table style="width:100%;border-collapse:collapse">'
            . $intake->row('Sales order', (string)$number) . $intake->row('Buyer', trim($b['name'] . ' ' . $b['email']))
            . $intake->row('Ship to', trim(implode(', ', array_filter([$b['line1'], $b['line2'], $b['city'], $b['state'] . ' ' . $b['zip']]))))
            . $rows . $intake->row('Shipping', $c['shipping_name'] . ' — $' . number_format((float)$c['shipping_amount'], 2))
            . $intake->row('Paid', '$' . number_format((float)$c['total'], 2) . ' by card (Square)')
            . '</table>');
    }

    /**
     * Shipping is quoted for the ZIP typed into the cart, but the buyer enters the real
     * address on Square's page and can type a different one there. Empty when they match,
     * or when Square sent no ZIP to compare.
     */
    private static function zipWarning(array $c, array $b): string
    {
        $paidZip = substr(preg_replace('/\D/', '', $b['zip']), 0, 5);

        if ($paidZip === '' || $paidZip === (string)$c['ship_zip']) {
            return '';
        }

        return 'CHECK SHIPPING: ' . $c['shipping_name'] . ' ($' . number_format((float)$c['shipping_amount'], 2)
            . ') was quoted to ZIP ' . $c['ship_zip'] . ', but the customer gave Square a ' . $paidZip
            . ' address. The shipping charge may be wrong.';
    }

    private function alert(array $c, string $what): void
    {
        $intake = new WebsiteLeadService();
        $intake->notify($intake->routing()['stencil_email'], 'Website checkout needs a look',
            '<p>Website checkout ' . htmlspecialchars((string)$c['token']) . ' (Square order ' . htmlspecialchars((string)$c['square_order_id'])
            . ') ' . htmlspecialchars($what) . '.</p><p>Total $' . number_format((float)$c['total'], 2) . '.</p>');
    }

    /** Handling added to the FedEx rate: shipping.web_markup, else the order desk's parcel markup. */
    private static function handling(): float
    {
        $web = Config::get('shipping.web_markup');

        return $web !== null ? (float)$web : (new ShippingQuoteService())->parcelMarkup();
    }

    /** @throws DesignerError */
    private static function zip(string $zip): string
    {
        if (!preg_match('/^\s*(\d{5})(-\d{4})?\s*$/', $zip, $m)) {
            throw new DesignerError('Please enter a valid 5-digit US ZIP code.');
        }

        return $m[1];
    }
}
