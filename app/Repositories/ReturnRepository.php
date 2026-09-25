<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/** Customer returns and their lines. */
class ReturnRepository
{
    public function nextNumber(): string
    {
        $row = Database::selectOne("SELECT return_number FROM stock_returns ORDER BY id DESC LIMIT 1");
        $n   = $row === false ? 0 : (int)preg_replace('/\D/', '', (string)$row['return_number']);

        return 'RMA-' . str_pad((string)($n + 1), 5, '0', STR_PAD_LEFT);
    }

    public function create(array $d): int
    {
        Database::statement("
            INSERT INTO stock_returns
                (return_number, customer_id, invoice_id, sales_order_id, reason, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ", [
            $this->nextNumber(),
            $d['customer_id'],
            $d['invoice_id']     ?? null,
            $d['sales_order_id'] ?? null,
            $d['reason'] ?? 'other',
            $d['notes']  ?? null,
            $d['created_by'] ?? null,
        ]);

        return (int)Database::connection()->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $row = Database::selectOne("
            SELECT r.*,
                   c.company_name,
                   i.invoice_number, i.tax_rate_applied, i.tax_source,
                   TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) AS received_by_name
            FROM stock_returns r
            JOIN customers c ON c.id = r.customer_id
            LEFT JOIN invoices i  ON i.id = r.invoice_id
            LEFT JOIN users u    ON u.id = r.received_by
            WHERE r.id = ?
        ", [$id]);

        return $row === false ? null : $row;
    }

    public function lines(int $returnId): array
    {
        return Database::select("
            SELECT rl.*, p.sku, p.name AS product_name, l.code AS location_code
            FROM stock_return_lines rl
            JOIN products p ON p.id = rl.product_id
            LEFT JOIN stock_locations l ON l.id = rl.location_id
            WHERE rl.return_id = ?
            ORDER BY rl.id
        ", [$returnId]);
    }

    public function addLine(array $d): int
    {
        Database::statement("
            INSERT INTO stock_return_lines
                (return_id, product_id, qty, item_condition, location_id, unit_price, line_credit, note)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $d['return_id'], $d['product_id'], $d['qty'], $d['item_condition'],
            $d['location_id'] ?? null, $d['unit_price'] ?? 0, $d['line_credit'] ?? 0, $d['note'] ?? null,
        ]);

        return (int)Database::connection()->lastInsertId();
    }

    public function removeLine(int $lineId): void
    {
        Database::statement("DELETE FROM stock_return_lines WHERE id = ?", [$lineId]);
    }

    public function setTotals(int $id, float $credit, float $tax): void
    {
        Database::statement(
            "UPDATE stock_returns SET credit_amount = ?, credit_tax = ? WHERE id = ?",
            [$credit, $tax, $id]
        );
    }

    public function markReceived(int $id, ?int $userId): void
    {
        Database::statement("
            UPDATE stock_returns SET status = 'received', received_by = ?, received_at = NOW() WHERE id = ?
        ", [$userId, $id]);
    }

    public function setCreditStatus(int $id, string $status): void
    {
        Database::statement("UPDATE stock_returns SET credit_status = ? WHERE id = ?", [$status, $id]);
    }

    /** Link a return to the credit memo raised for it, and mark it credited. */
    public function attachCredit(int $returnId, int $creditInvoiceId): void
    {
        Database::statement("
            UPDATE stock_returns
            SET credit_invoice_id = ?, credit_status = 'issued'
            WHERE id = ?
        ", [$creditInvoiceId, $returnId]);
    }

    public function setStatus(int $id, string $status): void
    {
        Database::statement("UPDATE stock_returns SET status = ? WHERE id = ?", [$status, $id]);
    }

    public function all(int $limit = 50): array
    {
        return Database::select("
            SELECT r.*, c.company_name, i.invoice_number,
                   (SELECT COUNT(*) FROM stock_return_lines l WHERE l.return_id = r.id) AS line_count,
                   (SELECT COALESCE(SUM(l.qty), 0) FROM stock_return_lines l WHERE l.return_id = r.id) AS qty_total
            FROM stock_returns r
            JOIN customers c ON c.id = r.customer_id
            LEFT JOIN invoices i ON i.id = r.invoice_id
            ORDER BY FIELD(r.status, 'draft', 'received', 'closed', 'cancelled'), r.id DESC
            LIMIT {$limit}
        ");
    }

    /** Returns whose credit the bookkeeper still owes the customer. */
    public function creditsOwed(): array
    {
        return Database::select("
            SELECT r.id, r.return_number, r.credit_amount, r.credit_tax, r.received_at,
                   c.company_name, i.invoice_number
            FROM stock_returns r
            JOIN customers c ON c.id = r.customer_id
            LEFT JOIN invoices i ON i.id = r.invoice_id
            WHERE r.credit_status = 'pending'
              AND r.status IN ('received', 'closed')
              AND r.credit_amount > 0
            ORDER BY r.received_at
        ");
    }

    /** What a customer bought on an invoice, so a return can be priced from it. */
    public function invoiceLines(int $invoiceId): array
    {
        return Database::select("
            SELECT li.id, li.product_id, li.qty, li.unit_price, li.discount_pct, li.is_taxable,
                   li.description, p.sku, p.name AS product_name
            FROM invoice_line_items li
            LEFT JOIN products p ON p.id = li.product_id
            WHERE li.invoice_id = ? AND li.product_id IS NOT NULL
            ORDER BY li.sort_order, li.id
        ", [$invoiceId]);
    }

    /** Invoices for a customer, newest first, for choosing what is being returned. */
    public function invoicesFor(int $customerId, int $limit = 25): array
    {
        return Database::select("
            SELECT id, invoice_number, invoice_date, total_amount
            FROM invoices
            WHERE customer_id = ? AND invoice_type = 'invoice' AND status <> 'void'
            ORDER BY invoice_date DESC, id DESC
            LIMIT {$limit}
        ", [$customerId]);
    }

    /** How much of a product has already come back on this invoice. */
    public function alreadyReturned(int $invoiceId, int $productId): float
    {
        $row = Database::selectOne("
            SELECT COALESCE(SUM(rl.qty), 0) AS q
            FROM stock_return_lines rl
            JOIN stock_returns r ON r.id = rl.return_id
            WHERE r.invoice_id = ? AND rl.product_id = ? AND r.status <> 'cancelled'
        ", [$invoiceId, $productId]);

        return (float)($row['q'] ?? 0);
    }
}
