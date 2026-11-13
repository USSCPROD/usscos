<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\OpportunityRepository;
use App\Repositories\QuoteRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\SalesOrderRepository;

class QuoteService
{
    private QuoteRepository       $quotes;
    private CustomerRepository    $customers;
    private SalesOrderRepository  $soRepo;
    private OpportunityRepository $opps;

    public function __construct()
    {
        $this->quotes    = new QuoteRepository();
        $this->customers = new CustomerRepository();
        $this->soRepo    = new SalesOrderRepository();
        $this->opps      = new OpportunityRepository();
    }

    public function create(): array
    {
        $company = Database::selectOne('SELECT default_tax_rate_id, default_payment_term_id, default_ship_via_id FROM companies WHERE id = 1');
        return [
            'ship_via_options'  => $this->quotes->getShipViaOptions(),
            'tax_rates'         => $this->quotes->getTaxRates(),
            'reps'              => $this->quotes->getReps(),
            'next_quote_number' => $this->quotes->nextQuoteNumber(),
            'payment_terms'     => $this->customers->getAllPaymentTerms(),
            'users'             => $this->quotes->getActiveUsers(),
            'company_defaults'  => $company ?: [],
        ];
    }

    public function store(int $userId, array $post): int
    {
        $lines  = $this->parseLines($post);
        $totals = $this->calcTotals($lines, (float)($post['tax_rate_pct'] ?? 0));

        $quoteId = $this->quotes->insert([
            ':quote_number'    => $this->quotes->nextQuoteNumber(),
            ':customer_id'     => ($post['customer_id'] ?? '') !== '' ? (int)$post['customer_id'] : null,
            ':lead_id'         => ($post['lead_id'] ?? '') !== '' ? (int)$post['lead_id'] : null,
            ':opportunity_id'  => ($post['opportunity_id'] ?? '') !== '' ? (int)$post['opportunity_id'] : null,
            ':created_by'      => $userId,
            ':rep_id'          => ($post['rep_id'] ?? '') !== '' ? (int)$post['rep_id'] : null,
            ':quote_date'      => $post['quote_date'],
            ':expiry_date'     => ($post['expiry_date'] ?? '') ?: null,
            ':po_number'       => ($post['po_number'] ?? '') ?: null,
            ':payment_term_id' => ($post['payment_term_id'] ?? '') !== '' ? (int)$post['payment_term_id'] : null,
            ':ship_via_id'     => ($post['ship_via_id'] ?? '') !== '' ? (int)$post['ship_via_id'] : null,
            ':tax_rate_id'     => ($post['tax_rate_id'] ?? '') !== '' ? (int)$post['tax_rate_id'] : null,
            ':ship_name'       => ($post['ship_name'] ?? '') ?: null,
            ':ship_address_1'  => ($post['ship_address_1'] ?? '') ?: null,
            ':ship_address_2'  => ($post['ship_address_2'] ?? '') ?: null,
            ':ship_city'       => ($post['ship_city'] ?? '') ?: null,
            ':ship_state'      => ($post['ship_state'] ?? '') ?: null,
            ':ship_zip'        => ($post['ship_zip'] ?? '') ?: null,
            ':subtotal'        => $totals['subtotal'],
            ':discount_amount' => $totals['discount'],
            ':tax_amount'      => $totals['tax'],
            ':total_amount'    => $totals['total'],
            ':memo'            => ($post['memo'] ?? '') ?: null,
            ':internal_notes'  => ($post['internal_notes'] ?? '') ?: null,
        ]);

        $this->quotes->replaceLineItems($quoteId, $lines);

        // Keep opportunity value in sync with quote total
        $oppId = ($post['opportunity_id'] ?? '') !== '' ? (int)$post['opportunity_id'] : null;
        if ($oppId) {
            $this->opps->updateValue($oppId, $totals['total']);
        }

        return $quoteId;
    }

    public function show(int $id): array
    {
        $quote = $this->quotes->findWithDetails($id);
        if (!$quote) throw new \RuntimeException('Quote not found.');

        return [
            'quote'            => $quote,
            'line_items'       => $this->quotes->getLineItems($id),
            'ship_via_options' => $this->quotes->getShipViaOptions(),
            'tax_rates'        => $this->quotes->getTaxRates(),
            'reps'             => $this->quotes->getReps(),
            'payment_terms'    => $this->customers->getAllPaymentTerms(),
        ];
    }

    public function edit(int $id): array
    {
        $quote = $this->quotes->findWithDetails($id);
        if (!$quote) throw new \RuntimeException('Quote not found.');

        $company = Database::selectOne('SELECT default_tax_rate_id, default_payment_term_id, default_ship_via_id FROM companies WHERE id = 1');

        return [
            'quote'            => $quote,
            'line_items'       => $this->quotes->getLineItems($id),
            'ship_via_options' => $this->quotes->getShipViaOptions(),
            'tax_rates'        => $this->quotes->getTaxRates(),
            'reps'             => $this->quotes->getReps(),
            'payment_terms'    => $this->customers->getAllPaymentTerms(),
            'users'            => $this->quotes->getActiveUsers(),
            'company_defaults' => $company ?: [],
        ];
    }

    public function update(int $id, array $post): void
    {
        $lines  = $this->parseLines($post);
        $totals = $this->calcTotals($lines, (float)($post['tax_rate_pct'] ?? 0));

        $this->quotes->update($id, [
            ':po_number'       => ($post['po_number'] ?? '') ?: null,
            ':payment_term_id' => ($post['payment_term_id'] ?? '') !== '' ? (int)$post['payment_term_id'] : null,
            ':ship_via_id'     => ($post['ship_via_id'] ?? '') !== '' ? (int)$post['ship_via_id'] : null,
            ':tax_rate_id'     => ($post['tax_rate_id'] ?? '') !== '' ? (int)$post['tax_rate_id'] : null,
            ':quote_date'      => $post['quote_date'],
            ':expiry_date'     => ($post['expiry_date'] ?? '') ?: null,
            ':rep_id'          => ($post['rep_id'] ?? '') !== '' ? (int)$post['rep_id'] : null,
            ':ship_name'       => ($post['ship_name'] ?? '') ?: null,
            ':ship_address_1'  => ($post['ship_address_1'] ?? '') ?: null,
            ':ship_address_2'  => ($post['ship_address_2'] ?? '') ?: null,
            ':ship_city'       => ($post['ship_city'] ?? '') ?: null,
            ':ship_state'      => ($post['ship_state'] ?? '') ?: null,
            ':ship_zip'        => ($post['ship_zip'] ?? '') ?: null,
            ':subtotal'        => $totals['subtotal'],
            ':discount_amount' => $totals['discount'],
            ':tax_amount'      => $totals['tax'],
            ':total_amount'    => $totals['total'],
            ':memo'            => ($post['memo'] ?? '') ?: null,
            ':internal_notes'  => ($post['internal_notes'] ?? '') ?: null,
        ]);

        $this->quotes->replaceLineItems($id, $lines);

        // Keep opportunity value in sync
        $quote = $this->quotes->findWithDetails($id);
        if ($quote && !empty($quote['opportunity_id'])) {
            $this->opps->updateValue((int)$quote['opportunity_id'], $totals['total']);
        }
    }

    public function list(int $page, int $perPage, string $search, string $status, string $sort): array
    {
        return [
            'paginated' => $this->quotes->paginate($page, $perPage, $search, $status, $sort),
            'stats'     => $this->quotes->getSummaryStats(),
            'search'    => $search,
            'status'    => $status,
            'sort'      => $sort,
        ];
    }

    public function convertToSO(int $quoteId, int $userId): int
    {
        $quote     = $this->quotes->findWithDetails($quoteId);
        if (!$quote) throw new \RuntimeException('Quote not found.');

        /*
         * A quote can be raised against a lead. A sales order cannot.
         *
         * Quoting a lead is the normal way round — somebody enquires, you price it,
         * and they become a customer when they say yes. But a sales order needs a
         * customer, so the lead has to be converted first, and nothing on the quote
         * said so. Pressing Convert to SO on a lead's quote failed on the customer
         * foreign key and showed the raw SQL to whoever pressed it.
         *
         * Found 12 Nov 2026 walking lead to sales order for the first time.
         */
        if ((int)($quote['customer_id'] ?? 0) === 0) {
            throw new \RuntimeException(
                $quote['lead_id']
                    ? 'This quote is for a lead, and a sales order needs a customer. '
                      . 'Open the lead and press "Convert to Customer" first — the quote '
                      . 'stays attached and can be converted straight afterwards.'
                    : 'This quote has no customer on it, so there is nothing to raise a '
                      . 'sales order against.'
            );
        }

        $lineItems = $this->quotes->getLineItems($quoteId);

        $soId = $this->soRepo->create([
            ':so_number'          => $this->soRepo->nextSoNumber(),
            ':customer_id'        => (int)$quote['customer_id'],
            ':created_by'         => $userId,
            ':rep_id'             => $quote['rep_id'] ? (int)$quote['rep_id'] : null,
            ':status'             => 'confirmed',
            ':order_date'         => date('Y-m-d'),
            ':requested_ship_date'=> null,
            ':po_number'          => $quote['po_number'],
            ':customer_message_id'=> null,
            ':ship_via_id'        => $quote['ship_via_id'] ? (int)$quote['ship_via_id'] : null,
            ':tax_rate_id'        => $quote['tax_rate_id'] ? (int)$quote['tax_rate_id'] : null,
            ':ship_name'          => $quote['ship_name'],
            ':ship_address_1'     => $quote['ship_address_1'],
            ':ship_address_2'     => $quote['ship_address_2'],
            ':ship_city'          => $quote['ship_city'],
            ':ship_state'         => $quote['ship_state'],
            ':ship_zip'           => $quote['ship_zip'],
            ':ship_phone'         => null,
            ':subtotal'           => $quote['subtotal'],
            ':discount_amount'    => $quote['discount_amount'],
            ':tax_amount'         => $quote['tax_amount'],
            ':total_amount'       => $quote['total_amount'],
            ':memo'               => $quote['memo'],
            ':internal_notes'     => $quote['internal_notes'],
        ]);

        /*
         * Copy the lines, under the names the sales order uses.
         *
         * Quote lines call it `qty` and `is_taxable`; sales order lines call it
         * `qty_ordered` and `taxable`. This map passed the quote's names straight
         * through, and `replaceLineItems()` falls back to `?? 1` and `?? 0` when a key
         * is missing — so **every converted line became quantity 1 and not taxable**,
         * while keeping the quote's line_total.
         *
         * A quote for 20 cases became a sales order for one, worth the price of twenty:
         * the warehouse picks one and the customer is billed for the lot, with nothing
         * anywhere disagreeing out loud. Found 12 Nov 2026 on SO #37, where $1,730
         * arrived as $116.
         *
         * Blank rows are dropped here too. The quote form keeps a spare line at the
         * bottom, and an empty one was being carried onto the order as a zero-priced
         * line nobody can pick.
         */
        $soLines = [];

        foreach ($lineItems as $li) {
            $qty  = (float)($li['qty'] ?? 0);
            $desc = trim((string)($li['description'] ?? ''));

            if ($qty <= 0 || ($desc === '' && empty($li['product_id']))) {
                continue;
            }

            $soLines[] = [
                'product_id'      => $li['product_id'],
                'quickbooks_item' => $li['quickbooks_item'],
                'description'     => $li['description'],
                'qty_ordered'     => $qty,
                'uom_id'          => $li['uom_id'],
                'unit_price'      => $li['unit_price'],
                'discount_pct'    => $li['discount_pct'],
                'taxable'         => $li['is_taxable'],
                'line_total'      => $li['line_total'],
            ];
        }

        $this->soRepo->replaceLineItems($soId, $soLines);
        $this->quotes->setSalesOrder($quoteId, $soId);

        return $soId;
    }

    private function parseLines(array $post): array
    {
        $items      = $post['line_item']       ?? [];
        $descs      = $post['line_desc']       ?? [];
        $qtys       = $post['line_qty']        ?? [];
        $prices     = $post['line_price']      ?? [];
        $discounts  = $post['line_discount']   ?? [];
        $taxables   = $post['line_taxable']    ?? [];
        $productIds = $post['line_product_id'] ?? [];

        $lines = [];
        foreach ($qtys as $i => $qty) {
            $qty   = (float)str_replace(',', '', $qty);
            $price = (float)str_replace(',', '', $prices[$i] ?? '0');
            $disc  = (float)($discounts[$i] ?? 0);

            /*
             * Skip the blank rows the form keeps at the bottom.
             *
             * This only skipped a row with no quantity AND no price, but an empty row
             * submits quantity 1, so every quote was saving a spare line at $0.00 and
             * carrying it onto the sales order. The same fault was fixed on sales
             * orders on 10 Nov; quotes still had it.
             *
             * A row is real if somebody put something in it: a product, some words, or
             * a price. A free-text line at no charge is legitimate, so price alone is
             * not the test.
             */
            $hasProduct = ($productIds[$i] ?? '') !== '';
            $hasWords   = trim((string)($descs[$i] ?? '')) !== ''
                       || trim((string)($items[$i] ?? '')) !== '';

            if (!$hasProduct && !$hasWords && $price == 0.0) {
                continue;
            }

            $lines[] = [
                'product_id'      => ($productIds[$i] ?? '') !== '' ? (int)$productIds[$i] : null,
                'quickbooks_item' => ($items[$i] ?? '') ?: null,
                'description'     => ($descs[$i] ?? '') ?: null,
                'qty'             => $qty,
                'uom_id'          => null,
                'unit_price'      => $price,
                'discount_pct'    => $disc,
                'is_taxable'      => isset($taxables[$i]) ? 1 : 0,
                'line_total'      => round($qty * $price * (1 - $disc / 100), 2),
            ];
        }

        $shipping = $this->shippingLine($post);

        if ($shipping !== null) {
            $lines[] = $shipping;
        }

        return $lines;
    }

    /**
     * The shipping charge on a quote, as a line of its own.
     *
     * The same shape a sales order uses, so it survives the conversion untouched and
     * reads correctly on the invoice without anything having to translate it.
     *
     * It belongs on the quote at all because institutional buyers raise a purchase
     * order against the quoted figure. Quote $1,730 and invoice $1,950 and the invoice
     * exceeds the PO, which their purchasing department will reject — so the shipping
     * has to be in the number they approve.
     *
     * Not a product, and marked not taxable: the pick and pack screens drop charge
     * lines by their wording so nobody is sent to find a box of handling.
     */
    private function shippingLine(array $post): ?array
    {
        $raw = $post['shipping_amount'] ?? '';

        if ($raw === '' || $raw === null) {
            return null;
        }

        $amount = round((float)str_replace([',', '$'], '', (string)$raw), 2);

        if ($amount <= 0) {
            return null;
        }

        return [
            'product_id'      => null,
            'quickbooks_item' => 'SH',
            'description'     => 'Shipping and Handling',
            'qty'             => 1.0,
            'uom_id'          => null,
            'unit_price'      => $amount,
            'discount_pct'    => 0.0,
            'is_taxable'      => 0,
            'line_total'      => $amount,
        ];
    }

    private function calcTotals(array $lines, float $taxRatePct): array
    {
        $subtotal = 0.0;
        $discount = 0.0;
        $taxable  = 0.0;

        foreach ($lines as $line) {
            $gross     = (float)$line['qty'] * (float)$line['unit_price'];
            $discAmt   = $gross * ((float)$line['discount_pct'] / 100);
            $net       = $gross - $discAmt;
            $subtotal += $gross;
            $discount += $discAmt;
            if ($line['is_taxable']) $taxable += $net;
        }

        $tax   = round($taxable * ($taxRatePct / 100), 2);
        $total = round($subtotal - $discount + $tax, 2);

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'tax'      => $tax,
            'total'    => $total,
        ];
    }
}
