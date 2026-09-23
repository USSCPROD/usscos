<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Service;
use App\Repositories\InvoiceRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\SalesOrderRepository;

class InvoiceService extends Service
{
    private InvoiceRepository $invoices;
    private CustomerRepository $customers;
    private SalesOrderRepository $soRepo;
    private PaymentRepository $paymentRepo;
    private StockService $stock;

    public function __construct()
    {
        $this->invoices     = new InvoiceRepository();
        $this->customers    = new CustomerRepository();
        $this->soRepo       = new SalesOrderRepository();
        $this->paymentRepo  = new PaymentRepository();
        $this->stock        = new StockService();
    }

    public function create(?int $fromSoId = null): array
    {
        $result = [
            'ship_via_options'    => $this->invoices->getShipViaOptions(),
            'tax_rates'           => $this->invoices->getTaxRates(),
            'customer_messages'   => $this->invoices->getCustomerMessages(),
            'reps'                => $this->invoices->getReps(),
            'next_invoice_number' => $this->invoices->nextInvoiceNumber(),
            'payment_terms'       => $this->customers->getAllPaymentTerms(),
            'users'               => $this->invoices->getActiveUsers(),
            'prefill_so'          => null,
            'prefill_lines'       => [],
        ];

        $company = Database::selectOne('SELECT default_tax_rate_id, default_payment_term_id, default_ship_via_id FROM companies WHERE id = 1');
        $result['company_defaults'] = $company ?: [];

        if ($fromSoId) {
            $so = $this->soRepo->findWithDetails($fromSoId);
            if ($so) {
                $result['prefill_so']    = $so;
                $result['prefill_lines'] = $this->soRepo->getLineItems($fromSoId);
            }
        }

        return $result;
    }

    public function store(int $userId, array $post): int
    {
        $lines  = $this->parseLines($post);
        $totals = $this->calcTotals($lines, (float)($post['tax_rate_pct'] ?? 0));
        $soId   = ($post['sales_order_id'] ?? '') !== '' ? (int)$post['sales_order_id'] : null;

        $invoiceId = $this->invoices->insert([
            ':customer_id'      => (int)$post['customer_id'],
            ':sales_order_id'   => $soId,
            ':invoice_number'   => $this->invoices->nextInvoiceNumber(),
            ':po_number'        => ($post['po_number'] ?? '') ?: null,
            ':invoice_date'     => $post['invoice_date'],
            ':due_date'         => $post['due_date'],
            ':payment_term_id'  => ($post['payment_term_id'] ?? '') !== '' ? (int)$post['payment_term_id'] : null,
            ':ship_date'        => ($post['ship_date'] ?? '') ?: null,
            ':tracking_number'  => ($post['tracking_number'] ?? '') ?: null,
            ':ship_via'         => ($post['ship_via'] ?? '') ?: null,
            ':ship_address_1'   => ($post['ship_address_1'] ?? '') ?: null,
            ':ship_address_2'   => ($post['ship_address_2'] ?? '') ?: null,
            ':ship_city'        => ($post['ship_city'] ?? '') ?: null,
            ':ship_state'       => ($post['ship_state'] ?? '') ?: null,
            ':ship_zip'         => ($post['ship_zip'] ?? '') ?: null,
            ':subtotal'         => $totals['subtotal'],
            ':discount_amount'  => $totals['discount'],
            ':tax_amount'       => $totals['tax'],
            ':total_amount'     => $totals['total'],
            ':balance_due'      => $totals['total'],
            ':memo'             => ($post['memo'] ?? '') ?: null,
            ':internal_notes'   => ($post['internal_notes'] ?? '') ?: null,
            ':created_by'       => $userId,
            ':rep_id'           => ($post['rep_id'] ?? '') !== '' ? (int)$post['rep_id'] : null,
        ]);

        $this->invoices->replaceLineItems($invoiceId, $lines);

        if ($soId) {
            // Check if pre-paid before flipping status
            $so = $this->soRepo->findWithDetails($soId);
            $isPrepaid = ($so['status'] ?? '') === 'paid' && !empty($so['payment_amount']);

            $this->soRepo->setStatus($soId, 'invoiced');

            // Auto-apply the existing payment to this invoice
            if ($isPrepaid) {
                $payment = $this->paymentRepo->findBySalesOrder($soId);
                if ($payment) {
                    $this->paymentRepo->insertApplication(
                        (int)$payment['id'],
                        $invoiceId,
                        (float)$so['payment_amount']
                    );
                    $this->paymentRepo->recalcInvoice($invoiceId);
                }
            }
        }

        return $invoiceId;
    }

    /**
     * Ship a sales order: create the invoice directly from the SO
     * (line items, totals, addresses), record tracking + ship date,
     * apply any prepayment, and close the SO.
     */
    public function shipAndInvoice(int $soId, int $userId, array $post): int
    {
        // One transaction for the whole shipment. Creating the invoice, taking the stock
        // out and recording what has gone are three writes that only make sense together:
        // a failure between them would bill a customer for paint the system still thinks
        // is on the shelf, or take stock out for an invoice that does not exist.
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $invoiceId = $this->shipAndInvoiceWithin($soId, $userId, $post);
            $pdo->commit();

            return $invoiceId;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /** The body of shipAndInvoice(), which always runs inside its transaction. */
    private function shipAndInvoiceWithin(int $soId, int $userId, array $post): int
    {
        $so = $this->soRepo->findWithDetails($soId);
        if (!$so) throw new \RuntimeException('Sales order not found.');
        if (in_array($so['status'], ['invoiced', 'cancelled'])) {
            throw new \RuntimeException('This sales order is already closed.');
        }

        $shipDate    = ($post['ship_date'] ?? '') ?: date('Y-m-d');
        $invoiceDate = $shipDate;

        // Due date from the customer's payment terms (0 days = due on receipt)
        $term = Database::selectOne(
            'SELECT pt.id, pt.days_due FROM customers c
             LEFT JOIN payment_terms pt ON pt.id = c.payment_term_id
             WHERE c.id = ?',
            [(int)$so['customer_id']]
        );
        $daysDue = (int)($term['days_due'] ?? 0);
        $dueDate = date('Y-m-d', strtotime($invoiceDate . ' +' . $daysDue . ' days'));

        $invoiceId = $this->invoices->insert([
            ':customer_id'      => (int)$so['customer_id'],
            ':sales_order_id'   => $soId,
            ':invoice_number'   => $this->invoices->nextInvoiceNumber(),
            ':po_number'        => $so['po_number'] ?: null,
            ':invoice_date'     => $invoiceDate,
            ':due_date'         => $dueDate,
            ':payment_term_id'  => !empty($term['id']) ? (int)$term['id'] : null,
            ':ship_date'        => $shipDate,
            ':tracking_number'  => ($post['tracking_number'] ?? '') ?: null,
            ':ship_via'         => ($post['ship_via'] ?? '') ?: ($so['ship_via_name'] ?? null),
            ':ship_address_1'   => $so['ship_address_1'] ?: null,
            ':ship_address_2'   => $so['ship_address_2'] ?: null,
            ':ship_city'        => $so['ship_city'] ?: null,
            ':ship_state'       => $so['ship_state'] ?: null,
            ':ship_zip'         => $so['ship_zip'] ?: null,
            ':subtotal'         => $so['subtotal'],
            ':discount_amount'  => $so['discount_amount'],
            ':tax_amount'       => $so['tax_amount'],
            ':total_amount'     => $so['total_amount'],
            ':balance_due'      => $so['total_amount'],
            ':memo'             => $so['memo'] ?: null,
            ':internal_notes'   => $so['internal_notes'] ?: null,
            ':created_by'       => $userId,
            ':rep_id'           => !empty($so['rep_id']) ? (int)$so['rep_id'] : null,
        ]);

        // Copy SO line items onto the invoice.
        //
        // Invoice what was PICKED, not what was ordered — otherwise a picker who finds
        // only 4 of 10 cases still bills the customer for 10, and the scan-to-verify step
        // catches the error while the invoice repeats it.
        //
        // Orders that never went through picking keep the old behaviour and invoice the
        // ordered quantity, so nothing that bypasses the shipping station changes.
        $soLines  = $this->soRepo->getLineItems($soId);
        $wasPicked = in_array($so['pick_status'] ?? 'not_started', ['in_progress', 'ready', 'short'], true);

        $lines     = [];
        $shortfall = false;
        $isPartial = false;   // something on this order already went out earlier
        $shipped   = [];

        foreach ($soLines as $li) {
            $ordered     = (float)($li['qty_ordered'] ?? $li['qty'] ?? 1);
            $picked      = (float)($li['qty_picked'] ?? 0);
            $alreadyGone = (float)($li['qty_shipped'] ?? 0);

            // Only what has not gone yet. Shipping the remainder of a partially shipped
            // order used to re-invoice the whole picked quantity, because qty_shipped was
            // never written — so the customer was billed twice for the first shipment,
            // and stock would now be deducted twice as well.
            $qty = $wasPicked ? max(0.0, $picked - $alreadyGone) : max(0.0, $ordered - $alreadyGone);

            if ($alreadyGone > 0) {
                $isPartial = true;
            }
            if ($alreadyGone + $qty < $ordered) {
                $shortfall = true;
            }

            // Nothing going out on this line means it does not belong on the invoice at
            // all — whether it was never picked, or already shipped in full on an earlier
            // partial shipment. The line stays open on the sales order.
            if ($qty <= 0) {
                continue;
            }

            $shipped[] = ['line_id' => (int)$li['id'], 'product_id' => $li['product_id'], 'qty' => $qty];

            $unitPrice = (float)$li['unit_price'];
            $discount  = (float)($li['discount_pct'] ?? 0);

            $lines[] = [
                'product_id'      => $li['product_id'],
                'quickbooks_item' => $li['quickbooks_item'] ?? null,
                'description'     => $li['description'],
                'qty'             => $qty,
                'uom_id'          => $li['uom_id'],
                'unit_price'      => $unitPrice,
                'discount_pct'    => $discount,
                'is_taxable'      => $li['taxable'] ?? $li['is_taxable'] ?? 0,
                // Recalculated from the shipped quantity — the stored line_total is for
                // the ordered quantity and would be wrong on a short ship.
                'line_total'      => round($qty * $unitPrice * (1 - $discount / 100), 2),
            ];
        }

        $this->invoices->replaceLineItems($invoiceId, $lines);

        // Take the stock out, and record what has now gone.
        //
        // Deducting the same quantities that were just invoiced is the point: the invoice
        // and the stock movement cannot disagree, because they are the same numbers. A
        // product recorded at no location, or at less than went out, is still deducted and
        // goes negative — the paint left the building either way, and a visible negative
        // is the signal that something needs counting.
        $this->stock->shipLines($shipped, [
            'type'           => 'sale',
            'reference_type' => 'invoice',
            'reference_id'   => $invoiceId,
            'reference_num'  => $so['so_number'] ?? null,
            'user_id'        => $userId,
            'date'           => $shipDate,
        ]);

        foreach ($shipped as $s) {
            $this->soRepo->addShippedQty($s['line_id'], $s['qty']);
        }

        // Totals follow the lines actually invoiced. Uses the same tax rate the order was
        // priced at — `tax_rate_pct` comes from the joined tax_rates row and is a fraction,
        // so 0.07 means 7%.
        // Recalculate whenever the invoice does not represent the whole order — a short
        // pick, or the remainder of an order that was already part shipped. The stored SO
        // totals are for everything ordered and would overstate either one.
        if ($shortfall || $isPartial) {
            $taxRatePct = (float)($so['tax_rate_pct'] ?? 0) * 100;
            $this->invoices->updateTotals($invoiceId, $this->calcTotals($lines, $taxRatePct));
        }

        // Apply prepayment if the SO was already paid
        $isPrepaid = ($so['status'] ?? '') === 'paid' && !empty($so['payment_amount']);
        if ($isPrepaid) {
            $payment = $this->paymentRepo->findBySalesOrder($soId);
            if ($payment) {
                $this->paymentRepo->insertApplication(
                    (int)$payment['id'],
                    $invoiceId,
                    (float)$so['payment_amount']
                );
                $this->paymentRepo->recalcInvoice($invoiceId);
            }
        }

        // Close the sales order — but only if everything went out. A short ship leaves it
        // `partially_shipped` so the remainder can be shipped later, rather than
        // disappearing from the open list with product still owed.
        $this->soRepo->setStatus($soId, $shortfall ? 'partially_shipped' : 'invoiced');

        return $invoiceId;
    }

    public function edit(int|string $id): array
    {
        // Redirect to show — the show page IS the edit page now
        return $this->show((int)$id);
    }

    public function update(int|string $id, array $post, string $userRole = 'user'): void
    {
        $invoice = $this->invoices->findWithDetails((int)$id);
        if (!$invoice) {
            throw new \RuntimeException('Invoice not found.');
        }

        $isPaid      = in_array($invoice['status'], ['paid', 'void']);
        $isPrivileged = in_array($userRole, ['owner', 'bookkeeper']);

        // Header fields — editable for unpaid, or paid+privileged
        if (!$isPaid || $isPrivileged) {
            $sameAsBilling = !empty($post['ship_same_as_billing']);
            $this->invoices->update((int)$id, [
                'po_number'       => trim($post['po_number']       ?? ''),
                'invoice_date'    => $post['invoice_date']         ?? $invoice['invoice_date'],
                'due_date'        => $post['due_date']             ?? $invoice['due_date'],
                'payment_term_id' => ($post['payment_term_id'] ?? '') !== '' ? (int)$post['payment_term_id'] : null,
                'rep_id'          => ($post['rep_id'] ?? '') !== '' ? (int)$post['rep_id'] : null,
                'ship_date'       => ($post['ship_date'] ?? '') ?: ($invoice['ship_date'] ?? null),
                'tracking_number' => trim($post['tracking_number'] ?? '') ?: ($invoice['tracking_number'] ?? null),
                'ship_via'        => trim($post['ship_via']        ?? ''),
                'ship_address_1'  => $sameAsBilling ? null : trim($post['ship_address_1'] ?? ''),
                'ship_address_2'  => $sameAsBilling ? null : trim($post['ship_address_2'] ?? ''),
                'ship_city'       => $sameAsBilling ? null : trim($post['ship_city']      ?? ''),
                'ship_state'      => $sameAsBilling ? null : strtoupper(trim($post['ship_state'] ?? '')),
                'ship_zip'        => $sameAsBilling ? null : trim($post['ship_zip']       ?? ''),
                'memo'            => trim($post['memo']            ?? ''),
                'internal_notes'  => trim($post['internal_notes']  ?? ''),
            ]);
        }

        // Line items — only for unpaid invoices
        if (!$isPaid) {
            $lines  = $this->parseLines($post);
            $totals = $this->calcTotals($lines, (float)($post['tax_rate_pct'] ?? 0));
            $this->invoices->replaceLineItems((int)$id, $lines);
            $this->invoices->updateTotals((int)$id, $totals);
        }
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
            if ($qty == 0 && $price == 0) continue;

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
        return $lines;
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

    public function list(int $page, int $perPage, string $search, string $status, string $sort): array
    {
        $paginated = $this->invoices->paginate($page, $perPage, $search, $status, $sort);
        $stats     = $this->invoices->getSummaryStats();
        $aging     = $this->invoices->getAgingBuckets();

        return [
            'paginated' => $paginated,
            'stats'     => $stats,
            'aging'     => $aging,
            'search'    => $search,
            'status'    => $status,
            'sort'      => $sort,
        ];
    }

    public function show(int|string $id): array
    {
        $invoice = $this->invoices->findWithDetails((int)$id);
        if (!$invoice) {
            throw new \RuntimeException('Invoice not found.');
        }

        $lineItems = $this->invoices->getLineItems((int)$id);

        return [
            'invoice'          => $invoice,
            'line_items'       => $lineItems,
            'payments'         => $this->paymentRepo->getByInvoice((int)$id),
            'ship_via_options' => $this->invoices->getShipViaOptions(),
            'tax_rates'        => $this->invoices->getTaxRates(),
            'reps'             => $this->invoices->getReps(),
            'payment_terms'    => $this->customers->getAllPaymentTerms(),
        ];
    }

    public function setStatus(int $id, string $status): void
    {
        $this->invoices->setStatus($id, $status);
    }
}
