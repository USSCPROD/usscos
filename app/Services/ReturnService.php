<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\ReturnRepository;
use App\Repositories\StockRepository;

/**
 * Customer returns.
 *
 * Named as a main cause of the count drifting, and until now there was nowhere to record
 * one. Paint comes back, somebody puts it on a shelf, and the system never hears — so the
 * count is short by exactly the amount physically present.
 *
 * The judgment in a return is whether the goods can be sold again, and it belongs to the
 * person holding the pail. Only resellable stock goes back on. Goods left inventory when
 * they were sold, so returning something that gets scrapped needs no stock movement at all
 * — the position is already right. Putting scrap back and writing it off again would be
 * two wrongs that cancel, while overstating both returns and write-offs.
 */
class ReturnService
{
    public const CONDITIONS = [
        'resellable' => 'Resellable — goes back on the shelf',
        'opened'     => 'Opened — cannot be sold as new',
        'damaged'    => 'Damaged',
        'expired'    => 'Expired',
        'scrap'      => 'Scrap',
    ];

    public const REASONS = [
        'damaged_in_transit' => 'Damaged in transit',
        'wrong_item_sent'    => 'We sent the wrong item',
        'wrong_item_ordered' => 'Customer ordered the wrong item',
        'over_ordered'       => 'Customer ordered too many',
        'not_needed'         => 'No longer needed',
        'quality'            => 'Quality complaint',
        'other'              => 'Other',
    ];

    /** Conditions where the goods can be sold again, so stock goes back on. */
    private const RESTOCKABLE = ['resellable'];

    public function __construct(
        private ReturnRepository $repo = new ReturnRepository(),
        private StockRepository $stock = new StockRepository(),
    ) {
    }

    public function start(array $input): int
    {
        $customerId = (int)($input['customer_id'] ?? 0);

        if ($customerId <= 0) {
            throw new \RuntimeException('Choose which customer is returning something.');
        }

        $reason = (string)($input['reason'] ?? 'other');

        if (!isset(self::REASONS[$reason])) {
            throw new \RuntimeException('Choose a reason for the return.');
        }

        return $this->repo->create([
            'customer_id' => $customerId,
            'invoice_id'  => ($input['invoice_id'] ?? '') !== '' ? (int)$input['invoice_id'] : null,
            'reason'      => $reason,
            'notes'       => trim((string)($input['notes'] ?? '')) ?: null,
            'created_by'  => Auth::id(),
        ]);
    }

    /**
     * Put a line on a return that has not been booked in yet.
     *
     * Priced from the original invoice when there is one, because what the customer gets
     * back is what they paid — not today's price, and not the list price.
     */
    public function addLine(int $returnId, array $input): void
    {
        $return = $this->require($returnId, 'draft', 'This return has already been booked in.');

        $qty       = (float)($input['qty'] ?? 0);
        $condition = (string)($input['item_condition'] ?? '');
        $code      = trim((string)($input['code'] ?? ''));

        if ($qty <= 0) {
            throw new \RuntimeException('Enter how many came back.');
        }
        if (!isset(self::CONDITIONS[$condition])) {
            throw new \RuntimeException('Say what condition it came back in — that decides whether it can be sold again.');
        }

        $match = $this->stock->resolveScan($code);

        if ($match === null) {
            throw new \RuntimeException('Not recognized: ' . $code);
        }

        $product   = $match['product'];
        $productId = (int)$product['id'];

        $locationId = ($input['location_id'] ?? '') !== '' ? (int)$input['location_id'] : null;

        if (in_array($condition, self::RESTOCKABLE, true) && $locationId === null) {
            throw new \RuntimeException('Resellable stock has to go somewhere — choose a location.');
        }

        // Priced from the invoice it came off, and only up to what was actually bought.
        $unitPrice = 0.0;

        if (!empty($return['invoice_id'])) {
            foreach ($this->repo->invoiceLines((int)$return['invoice_id']) as $il) {
                if ((int)$il['product_id'] !== $productId) {
                    continue;
                }

                $unitPrice = (float)$il['unit_price'] * (1 - (float)($il['discount_pct'] ?? 0) / 100);

                $bought   = (float)$il['qty'];
                $already  = $this->repo->alreadyReturned((int)$return['invoice_id'], $productId);

                if ($already + $qty > $bought + 0.0001) {
                    throw new \RuntimeException(sprintf(
                        'They bought %s on that invoice and %s has already come back — %s is more than is owed.',
                        $this->fmt($bought),
                        $this->fmt($already),
                        $this->fmt($qty)
                    ));
                }
                break;
            }
        }

        $this->repo->addLine([
            'return_id'      => $returnId,
            'product_id'     => $productId,
            'qty'            => $qty,
            'item_condition' => $condition,
            'location_id'    => $locationId,
            'unit_price'     => $unitPrice,
            'line_credit'    => round($qty * $unitPrice, 2),
            'note'           => trim((string)($input['note'] ?? '')) ?: null,
        ]);

        $this->recalcCredit($returnId);
    }

    public function removeLine(int $returnId, int $lineId): void
    {
        $this->require($returnId, 'draft', 'This return has already been booked in.');
        $this->repo->removeLine($lineId);
        $this->recalcCredit($returnId);
    }

    /**
     * Book the return in. Resellable stock goes back on the shelf; nothing else moves.
     *
     * One transaction, because a half-booked return is worse than an unbooked one.
     *
     * @return array{restocked:float, not_restocked:float}
     */
    public function receive(int $returnId): array
    {
        $return = $this->require($returnId, 'draft', 'This return has already been booked in.');
        $lines  = $this->repo->lines($returnId);

        if (!$lines) {
            throw new \RuntimeException('Nothing on this return yet.');
        }

        $restocked = 0.0;
        $scrapped  = 0.0;

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            foreach ($lines as $l) {
                $qty = (float)$l['qty'];

                if (!in_array($l['item_condition'], self::RESTOCKABLE, true) || empty($l['location_id'])) {
                    // Deliberately no movement. It left inventory when it was sold, and it
                    // is not going back on sale, so the position is already correct.
                    $scrapped += $qty;
                    continue;
                }

                $this->stock->applyMovement([
                    'product_id'     => (int)$l['product_id'],
                    'qty'            => $qty,
                    'type'           => 'return_in',
                    'to_location_id' => (int)$l['location_id'],
                    'reference_type' => 'return',
                    'reference_id'   => $returnId,
                    'reference_num'  => $return['return_number'],
                    'notes'          => 'Returned by ' . ($return['company_name'] ?? 'customer'),
                    'user_id'        => Auth::id(),
                ]);

                $restocked += $qty;
            }

            $this->repo->markReceived($returnId, Auth::id());
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        return ['restocked' => $restocked, 'not_restocked' => $scrapped];
    }

    /** Mark that the bookkeeper has issued the credit in QuickBooks. */
    public function markCreditIssued(int $returnId): void
    {
        $this->repo->setCreditStatus($returnId, 'issued');
    }

    public function cancel(int $returnId): void
    {
        $this->require($returnId, 'draft', 'A booked-in return cannot be canceled — adjust the stock instead.');
        $this->repo->setStatus($returnId, 'cancelled');
    }

    /**
     * What we owe the customer back.
     *
     * Tax is credited at the rate frozen on the original invoice, not today's rate — the
     * customer is owed what they were actually charged, and rates move. Storing that rate
     * on the invoice is exactly what makes this answerable.
     */
    private function recalcCredit(int $returnId): void
    {
        $return = $this->repo->find($returnId);
        $goods  = 0.0;

        foreach ($this->repo->lines($returnId) as $l) {
            $goods += (float)$l['line_credit'];
        }

        $rate = ($return && ($return['tax_source'] ?? '') === 'usscos')
            ? (float)($return['tax_rate_applied'] ?? 0)
            : 0.0;

        $this->repo->setTotals($returnId, round($goods + round($goods * $rate, 2), 2), round($goods * $rate, 2));
    }

    private function require(int $id, string $status, string $message): array
    {
        $return = $this->repo->find($id);

        if ($return === null) {
            throw new \RuntimeException('That return no longer exists.');
        }
        if ($return['status'] !== $status) {
            throw new \RuntimeException($message);
        }

        return $return;
    }

    private function fmt(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2), '0'), '.');
    }

    public function get(int $id): ?array          { return $this->repo->find($id); }
    public function lines(int $id): array         { return $this->repo->lines($id); }
    public function all(): array                  { return $this->repo->all(); }
    public function creditsOwed(): array          { return $this->repo->creditsOwed(); }
    public function locations(): array            { return $this->stock->storableLocations(); }
    public function invoicesFor(int $c): array    { return $this->repo->invoicesFor($c); }
    public function invoiceLines(int $i): array   { return $this->repo->invoiceLines($i); }
}
