<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Builds the payloads the QuickBooks bridge writes through QODBC, and records what
 * happened to each one.
 *
 * Field names in the output deliberately mirror QODBC's own column names
 * (CustomerRefFullName, RefNumber, TxnDate…) so the Windows-side script is a thin
 * mapping rather than a translation layer. See docs/QUICKBOOKS_SYNC.md.
 */
class QbExportRepository
{
    /**
     * The pilot gate - see config/quickbooks.php.
     *
     * Returns a WHERE fragment and its parameters restricting records to the channels
     * being piloted, or an empty fragment when the gate is off. Built with placeholders
     * rather than interpolated, because the value comes from the environment and SQL is
     * never assembled from a string we did not write ourselves.
     */
    private function channelGate(string $soAlias): array
    {
        $channels = (array)(\App\Core\Config::get('quickbooks.channels') ?? []);

        if ($channels === []) {
            return ['', []];
        }

        $holes = implode(',', array_fill(0, count($channels), '?'));

        return [" AND {$soAlias}.channel IN ({$holes}) ", $channels];
    }

    /** Is the pilot gate on, and for what? Used by the status endpoint. */
    public function gatedChannels(): array
    {
        return (array)(\App\Core\Config::get('quickbooks.channels') ?? []);
    }

    /** Invoices not yet confirmed as written to QuickBooks. */
    public function pendingInvoices(int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        [$gate, $gateParams] = $this->channelGate('so');

        $invoices = Database::select("
            SELECT i.id,
                   i.invoice_number      AS RefNumber,
                   i.invoice_date        AS TxnDate,
                   i.due_date            AS DueDate,
                   i.po_number           AS PONumber,
                   i.memo                AS Memo,
                   i.subtotal, i.tax_amount, i.total_amount, i.balance_due,
                   c.quickbooks_name     AS CustomerRefFullName,
                   c.company_name,
                   pt.name               AS TermsRefFullName,
                   i.ship_date           AS ShipDate,
                   i.ship_via            AS ShipMethodRefFullName,
                   i.tracking_number,
                   so.so_number          AS source_sales_order,
                   i.qb_export_error
            FROM invoices i
            JOIN customers c            ON c.id  = i.customer_id
            LEFT JOIN payment_terms pt  ON pt.id = i.payment_term_id
            LEFT JOIN sales_orders so   ON so.id = i.sales_order_id
            WHERE i.qb_exported_at IS NULL
              AND i.status != 'void'
              {$gate}
            ORDER BY i.invoice_date ASC, i.id ASC
            LIMIT {$limit}
        ", $gateParams);

        foreach ($invoices as &$inv) {
            $inv['lines'] = $this->invoiceLines((int)$inv['id']);
        }
        return $invoices;
    }

    /**
     * Invoice lines. ItemRefFullName is products.quickbooks_item — the item name
     * QuickBooks itself uses — falling back to the line's own stored value for lines
     * that were never linked to a product.
     */
    private function invoiceLines(int $invoiceId): array
    {
        return Database::select("
            SELECT COALESCE(p.quickbooks_item, ili.quickbooks_item) AS ItemRefFullName,
                   ili.description  AS `Desc`,
                   ili.qty          AS Quantity,
                   ili.unit_price   AS Rate,
                   ili.line_total   AS Amount,
                   ili.is_taxable,
                   u.code           AS UOM
            FROM invoice_line_items ili
            LEFT JOIN products p          ON p.id = ili.product_id
            LEFT JOIN units_of_measure u  ON u.id = ili.uom_id
            WHERE ili.invoice_id = ?
            ORDER BY ili.sort_order, ili.id
        ", [$invoiceId]);
    }

    public function pendingSalesOrders(int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        [$gate, $gateParams] = $this->channelGate('so');

        $orders = Database::select("
            SELECT so.id,
                   so.so_number     AS RefNumber,
                   so.order_date    AS TxnDate,
                   so.po_number     AS PONumber,
                   so.memo          AS Memo,
                   so.subtotal, so.tax_amount, so.total_amount,
                   so.status,
                   c.quickbooks_name AS CustomerRefFullName,
                   c.company_name,
                   sv.name          AS ShipMethodRefFullName,
                   so.qb_export_error
            FROM sales_orders so
            JOIN customers c        ON c.id = so.customer_id
            LEFT JOIN ship_via sv   ON sv.id = so.ship_via_id
            WHERE so.qb_exported_at IS NULL
              AND so.status NOT IN ('cancelled','draft')
              {$gate}
            ORDER BY so.order_date ASC, so.id ASC
            LIMIT {$limit}
        ", $gateParams);

        foreach ($orders as &$o) {
            $o['lines'] = Database::select("
                SELECT COALESCE(p.quickbooks_item, li.quickbooks_item) AS ItemRefFullName,
                       li.description   AS `Desc`,
                       li.qty_ordered   AS Quantity,
                       li.unit_price    AS Rate,
                       li.line_total    AS Amount,
                       li.taxable       AS is_taxable
                FROM sales_order_line_items li
                LEFT JOIN products p ON p.id = li.product_id
                WHERE li.sales_order_id = ?
                ORDER BY li.sort_order, li.id
            ", [(int)$o['id']]);
        }
        return $orders;
    }

    /**
     * Payments, with the invoices each one is applied to. QuickBooks needs the
     * applications so a receipt lands against the right invoice rather than sitting
     * unapplied on the customer's account.
     */
    public function pendingPayments(int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));

        // A payment is only safe to hand over once every invoice it pays already exists in
        // QuickBooks, because QODBC matches the application by RefNumber. Offering it
        // earlier produces a receipt that lands unapplied on the customer's account -
        // which looks like money received against nothing, and is tedious to unpick.
        $channels = $this->gatedChannels();
        $gateFails = $channels === []
            ? ''
            : ' OR so2.channel IS NULL OR so2.channel NOT IN (' . implode(',', array_fill(0, count($channels), '?')) . ')';

        $payments = Database::select("
            SELECT p.id,
                   p.payment_date       AS TxnDate,
                   p.amount             AS TotalAmount,
                   p.payment_method     AS PaymentMethodRefFullName,
                   p.reference_number   AS RefNumber,
                   p.memo               AS Memo,
                   c.quickbooks_name    AS CustomerRefFullName,
                   c.company_name,
                   p.qb_export_error
            FROM payments p
            JOIN customers c ON c.id = p.customer_id
            WHERE p.qb_exported_at IS NULL
              AND EXISTS (
                  SELECT 1 FROM payment_applications pa WHERE pa.payment_id = p.id
              )
              AND NOT EXISTS (
                  SELECT 1
                  FROM payment_applications pa
                  JOIN invoices i2            ON i2.id  = pa.invoice_id
                  LEFT JOIN sales_orders so2  ON so2.id = i2.sales_order_id
                  WHERE pa.payment_id = p.id
                    AND (i2.qb_txn_id IS NULL {$gateFails})
              )
            ORDER BY p.payment_date ASC, p.id ASC
            LIMIT {$limit}
        ", $channels);

        foreach ($payments as &$pay) {
            $pay['applied_to'] = Database::select("
                SELECT i.invoice_number AS RefNumber, i.qb_txn_id, pa.amount_applied AS Amount
                FROM payment_applications pa
                JOIN invoices i ON i.id = pa.invoice_id
                WHERE pa.payment_id = ?
            ", [(int)$pay['id']]);
        }
        return $payments;
    }

    /**
     * Counts must match what /pending would actually hand over, gate included. A status
     * endpoint reporting work that the next call will not offer is worse than no status
     * endpoint - it reads as a stuck queue.
     *
     * 'held' is the honest companion figure: real records, deliberately not being sent
     * because the pilot gate excludes them. Without it the gate looks like data loss.
     */
    public function pendingCounts(): array
    {
        [$gate, $p] = $this->channelGate('so');

        $invoices = (int)(Database::selectOne("
            SELECT COUNT(*) c FROM invoices i
            LEFT JOIN sales_orders so ON so.id = i.sales_order_id
            WHERE i.qb_exported_at IS NULL AND i.status != 'void' {$gate}
        ", $p)['c'] ?? 0);

        $orders = (int)(Database::selectOne("
            SELECT COUNT(*) c FROM sales_orders so
            WHERE so.qb_exported_at IS NULL
              AND so.status NOT IN ('cancelled','draft') {$gate}
        ", $p)['c'] ?? 0);

        $failed = (int)(Database::selectOne("
            SELECT COUNT(*) c FROM invoices i
            LEFT JOIN sales_orders so ON so.id = i.sales_order_id
            WHERE i.qb_exported_at IS NULL AND i.qb_export_error IS NOT NULL {$gate}
        ", $p)['c'] ?? 0);

        $counts = [
            'invoices'     => $invoices,
            'sales_orders' => $orders,
            'payments'     => count($this->pendingPayments(500)),
            'failed'       => $failed,
        ];

        if ($this->gatedChannels() !== []) {
            $counts['gate'] = $this->gatedChannels();
            $counts['held'] = [
                'invoices' => (int)(Database::selectOne(
                    "SELECT COUNT(*) c FROM invoices WHERE qb_exported_at IS NULL AND status != 'void'"
                )['c'] ?? 0) - $invoices,
                'sales_orders' => (int)(Database::selectOne(
                    "SELECT COUNT(*) c FROM sales_orders WHERE qb_exported_at IS NULL AND status NOT IN ('cancelled','draft')"
                )['c'] ?? 0) - $orders,
            ];
        }

        return $counts;
    }

    // ------------------------------------------------------------ acknowledge

    private const TABLES = [
        'invoice'      => 'invoices',
        'sales_order'  => 'sales_orders',
        'payment'      => 'payments',
    ];

    /**
     * Record the outcome of one record. Success stamps qb_exported_at so it is never
     * offered again; failure leaves it pending but stores the reason, so a broken record
     * is visible rather than silently retried forever.
     */
    public function acknowledge(string $type, int $id, bool $ok, ?string $txnId, ?string $message, ?string $batchId): bool
    {
        if (!isset(self::TABLES[$type])) return false;
        $table = self::TABLES[$type];

        if ($ok) {
            Database::statement(
                "UPDATE `$table` SET qb_exported_at = NOW(), qb_txn_id = ?, qb_export_error = NULL WHERE id = ?",
                [$txnId, $id]
            );
        } else {
            Database::statement(
                "UPDATE `$table` SET qb_export_error = ? WHERE id = ?",
                [$message !== null ? substr($message, 0, 2000) : 'Unknown error', $id]
            );
        }

        $ref = Database::selectOne(
            "SELECT " . ($table === 'payments' ? 'reference_number' : ($table === 'invoices' ? 'invoice_number' : 'so_number'))
            . " AS r FROM `$table` WHERE id = ?", [$id]
        )['r'] ?? null;

        Database::statement("
            INSERT INTO qb_export_log (record_type, record_id, reference, action, qb_txn_id, message, batch_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ", [$type, $id, $ref, $ok ? 'acknowledged' : 'failed', $txnId, $message, $batchId]);

        return true;
    }

    /** Note that a batch was handed over, before the bridge has written anything. */
    public function logSent(string $type, array $ids, string $batchId): void
    {
        if (empty($ids)) return;
        $stmt = Database::connection()->prepare("
            INSERT INTO qb_export_log (record_type, record_id, action, batch_id)
            VALUES (?, ?, 'sent', ?)
        ");
        foreach ($ids as $id) {
            $stmt->execute([$type, (int)$id, $batchId]);
        }
    }

    public function recentLog(int $limit = 50): array
    {
        $limit = max(1, min(500, $limit));
        return Database::select("
            SELECT * FROM qb_export_log ORDER BY id DESC LIMIT {$limit}
        ");
    }
}
