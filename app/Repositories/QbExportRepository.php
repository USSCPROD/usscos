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
    /** Invoices not yet confirmed as written to QuickBooks. */
    public function pendingInvoices(int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));

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
            ORDER BY i.invoice_date ASC, i.id ASC
            LIMIT {$limit}
        ");

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
            ORDER BY so.order_date ASC, so.id ASC
            LIMIT {$limit}
        ");

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
            ORDER BY p.payment_date ASC, p.id ASC
            LIMIT {$limit}
        ");

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

    public function pendingCounts(): array
    {
        return [
            'invoices'     => (int)(Database::selectOne("SELECT COUNT(*) c FROM invoices     WHERE qb_exported_at IS NULL AND status != 'void'")['c'] ?? 0),
            'sales_orders' => (int)(Database::selectOne("SELECT COUNT(*) c FROM sales_orders WHERE qb_exported_at IS NULL AND status NOT IN ('cancelled','draft')")['c'] ?? 0),
            'payments'     => (int)(Database::selectOne("SELECT COUNT(*) c FROM payments     WHERE qb_exported_at IS NULL")['c'] ?? 0),
            'failed'       => (int)(Database::selectOne("SELECT COUNT(*) c FROM invoices     WHERE qb_exported_at IS NULL AND qb_export_error IS NOT NULL")['c'] ?? 0),
        ];
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
