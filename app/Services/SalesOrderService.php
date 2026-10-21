<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\SalesOrderRepository;
use App\Repositories\CustomerRepository;

class SalesOrderService
{
    private SalesOrderRepository $repo;
    private CustomerRepository   $customers;

    public function __construct()
    {
        $this->repo      = new SalesOrderRepository();
        $this->customers = new CustomerRepository();
    }

    public function list(int $page, int $perPage, string $search, string $status, string $sort): array
    {
        $pagination = $this->repo->paginate($page, $perPage, $search, $status, $sort);
        $stats      = $this->repo->getSummaryStats();

        return [
            'pagination' => $pagination,
            'stats'      => $stats,
            'search'     => $search,
            'status'     => $status,
            'sort'       => $sort,
        ];
    }

    public function show(int $id): array
    {
        $so = $this->repo->findWithDetails($id);
        if (!$so) throw new \RuntimeException("Sales order not found");

        return [
            'so'                  => $so,
            'line_items'          => $this->repo->getLineItems($id),
            'ship_via_options'    => $this->repo->getShipViaOptions(),
            'tax_rates'           => $this->repo->getTaxRates(),
            'customer_messages'   => $this->repo->getCustomerMessages(),
            'reps'                => $this->repo->getReps(),
            'payment_terms'       => $this->customers->getAllPaymentTerms(),
            'users'               => $this->repo->getActiveUsers(),
        ];
    }

    public function create(): array
    {
        $company = Database::selectOne('SELECT default_tax_rate_id, default_payment_term_id, default_ship_via_id FROM companies WHERE id = 1');
        return [
            'ship_via_options'  => $this->repo->getShipViaOptions(),
            'tax_rates'         => $this->repo->getTaxRates(),
            'customer_messages' => $this->repo->getCustomerMessages(),
            'reps'              => $this->repo->getReps(),
            'next_so_number'    => $this->repo->nextSoNumber(),
            'payment_terms'     => $this->customers->getAllPaymentTerms(),
            'users'             => $this->repo->getActiveUsers(),
            'company_defaults'  => $company ?: [],
        ];
    }


    /**
     * Where the order came from, and what the marketplace already did about tax.
     *
     * The channel is not cosmetic. TaxService refuses to charge tax on a marketplace
     * order because Amazon has already collected and remitted it - so an Amazon order
     * saved as 'direct' is charged Georgia tax a second time, overcharging a customer
     * and booking a liability that was never ours. It also drives which records the
     * QuickBooks bridge is allowed to take.
     *
     * Unknown values fall back to 'direct' rather than being written through, because
     * channel is an enum and MySQL would otherwise coerce a bad value to an empty
     * string under some modes.
     */
    private function channelFields(array $post): array
    {
        $allowed = ['direct', 'amazon', 'website', 'pickup', 'edi', 'other'];
        $channel = strtolower(trim((string)($post['channel'] ?? 'direct')));

        if (!in_array($channel, $allowed, true)) {
            $channel = 'direct';
        }

        // Only a marketplace order can carry marketplace tax. Keeping the figure on a
        // direct order would quietly understate what USSC owes.
        $collected = $channel === 'amazon'
            ? round((float)($post['marketplace_tax_collected'] ?? 0), 2)
            : 0.0;

        return [
            ':channel'                   => $channel,
            ':channel_reference'         => trim((string)($post['channel_reference'] ?? '')) ?: null,
            ':marketplace_tax_collected' => $collected,
        ];
    }

    public function store(int $userId, array $post): int
    {
        $lines    = $this->parseLines($post);
        $totals   = $this->calcTotals($lines, (float)($post['tax_rate_pct'] ?? 0));

        $soId = $this->repo->create([
            ':so_number'          => $this->repo->nextSoNumber(),
            ':customer_id'        => (int)$post['customer_id'],
            ':created_by'         => $userId,
            ':rep_id'             => ($post['rep_id'] ?? '') !== '' ? (int)$post['rep_id'] : null,
            ':status'             => 'draft',
            ':order_date'         => $post['order_date'],
            ':requested_ship_date'=> ($post['requested_ship_date'] ?? '') ?: null,
            ':po_number'          => ($post['po_number'] ?? '') ?: null,
            ':ship_via_id'        => ($post['ship_via_id'] ?? '') !== '' ? (int)$post['ship_via_id'] : null,
            ':tax_rate_id'        => ($post['tax_rate_id'] ?? '') !== '' ? (int)$post['tax_rate_id'] : null,
            ':customer_message_id'=> ($post['customer_message_id'] ?? '') !== '' ? (int)$post['customer_message_id'] : null,
            ':ship_name'          => ($post['ship_name'] ?? '') ?: null,
            ':ship_address_1'     => ($post['ship_address_1'] ?? '') ?: null,
            ':ship_address_2'     => ($post['ship_address_2'] ?? '') ?: null,
            ':ship_city'          => ($post['ship_city'] ?? '') ?: null,
            ':ship_state'         => ($post['ship_state'] ?? '') ?: null,
            ':ship_zip'           => ($post['ship_zip'] ?? '') ?: null,
            ':ship_phone'         => ($post['ship_phone'] ?? '') ?: null,
            ':subtotal'           => $totals['subtotal'],
            ':discount_amount'    => $totals['discount'],
            ':tax_amount'         => $totals['tax'],
            ':total_amount'       => $totals['total'],
            ':memo'               => ($post['memo'] ?? '') ?: null,
            ':internal_notes'     => ($post['internal_notes'] ?? '') ?: null,
            ...$this->channelFields($post),
        ]);

        $this->repo->replaceLineItems($soId, $lines);

        return $soId;
    }

    public function edit(int $id): array
    {
        $so = $this->repo->findWithDetails($id);
        if (!$so) throw new \RuntimeException("Sales order not found");

        return [
            'so'                  => $so,
            'line_items'          => $this->repo->getLineItems($id),
            'ship_via_options'    => $this->repo->getShipViaOptions(),
            'tax_rates'           => $this->repo->getTaxRates(),
            'customer_messages'   => $this->repo->getCustomerMessages(),
            'reps'                => $this->repo->getReps(),
            'payment_terms'       => $this->customers->getAllPaymentTerms(),
            'users'               => $this->repo->getActiveUsers(),
        ];
    }

    public function update(int $id, array $post): void
    {
        $so = $this->repo->findWithDetails($id);
        if (!$so) throw new \RuntimeException("Sales order not found");

        $lines  = $this->parseLines($post);
        $totals = $this->calcTotals($lines, (float)($post['tax_rate_pct'] ?? 0));

        $this->repo->update($id, [
            ':status'             => $post['status'],
            ':created_by'         => ($post['processed_by_id'] ?? '') !== '' ? (int)$post['processed_by_id'] : ($so['created_by'] ?? null),
            ':order_date'         => $post['order_date'],
            ':requested_ship_date'=> ($post['requested_ship_date'] ?? '') ?: null,
            ':po_number'          => ($post['po_number'] ?? '') ?: null,
            ':rep_id'             => ($post['rep_id'] ?? '') !== '' ? (int)$post['rep_id'] : null,
            ':ship_via_id'        => ($post['ship_via_id'] ?? '') !== '' ? (int)$post['ship_via_id'] : null,
            ':tax_rate_id'        => ($post['tax_rate_id'] ?? '') !== '' ? (int)$post['tax_rate_id'] : null,
            ':customer_message_id'=> ($post['customer_message_id'] ?? '') !== '' ? (int)$post['customer_message_id'] : null,
            ':ship_name'          => ($post['ship_name'] ?? '') ?: null,
            ':ship_address_1'     => ($post['ship_address_1'] ?? '') ?: null,
            ':ship_address_2'     => ($post['ship_address_2'] ?? '') ?: null,
            ':ship_city'          => ($post['ship_city'] ?? '') ?: null,
            ':ship_state'         => ($post['ship_state'] ?? '') ?: null,
            ':ship_zip'           => ($post['ship_zip'] ?? '') ?: null,
            ':ship_phone'         => ($post['ship_phone'] ?? '') ?: null,
            ':subtotal'           => $totals['subtotal'],
            ':discount_amount'    => $totals['discount'],
            ':tax_amount'         => $totals['tax'],
            ':total_amount'       => $totals['total'],
            ':memo'               => ($post['memo'] ?? '') ?: null,
            ':internal_notes'     => ($post['internal_notes'] ?? '') ?: null,
            ...$this->channelFields($post),
        ]);

        $this->repo->replaceLineItems($id, $lines);
    }

    public function delete(int $id): void
    {
        $so = $this->repo->findWithDetails($id);
        if (!$so) throw new \RuntimeException("Sales order not found");
        $this->repo->delete($id);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function parseLines(array $post): array
    {
        $items      = $post['line_item']     ?? [];
        $descs      = $post['line_desc']     ?? [];
        $qtys       = $post['line_qty']      ?? [];
        $prices     = $post['line_price']    ?? [];
        $discounts  = $post['line_discount'] ?? [];
        $taxables   = $post['line_taxable']  ?? [];
        $productIds = $post['line_product_id'] ?? [];

        $lines = [];
        foreach ($qtys as $i => $qty) {
            $qty   = (float)str_replace(',', '', $qty);
            $price = (float)str_replace(',', '', $prices[$i] ?? '0');
            $disc  = (float)($discounts[$i] ?? 0);
            if ($qty == 0 && $price == 0) continue;

            $lineTotal = round($qty * $price * (1 - $disc / 100), 2);

            $lines[] = [
                'product_id'     => ($productIds[$i] ?? '') !== '' ? (int)$productIds[$i] : null,
                'quickbooks_item'=> ($items[$i] ?? '') ?: null,
                'description'    => ($descs[$i] ?? '') ?: null,
                'qty_ordered'    => $qty,
                'uom_id'         => null,
                'unit_price'     => $price,
                'discount_pct'   => $disc,
                'taxable'        => isset($taxables[$i]) ? 1 : 0,
                'line_total'     => $lineTotal,
            ];
        }
        return $lines;
    }

    private function calcTotals(array $lines, float $taxRatePct): array
    {
        $subtotal = 0.0;
        $discount = 0.0;
        $taxable  = 0.0;

        foreach ($lines as $line) {
            $gross     = (float)$line['qty_ordered'] * (float)$line['unit_price'];
            $discAmt   = $gross * ((float)$line['discount_pct'] / 100);
            $net       = $gross - $discAmt;
            $subtotal += $gross;
            $discount += $discAmt;
            if ($line['taxable']) $taxable += $net;
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
