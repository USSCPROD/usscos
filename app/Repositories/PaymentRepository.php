<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class PaymentRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function insert(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO payments
                (customer_id, sales_order_id, payment_date, payment_method, reference_number, amount, memo, created_by)
            VALUES
                (:customer_id, :sales_order_id, :payment_date, :payment_method, :reference_number, :amount, :memo, :created_by)
        ");
        $data[':sales_order_id'] = $data[':sales_order_id'] ?? null;
        $stmt->execute($data);
        return (int)$this->pdo->lastInsertId();
    }

    public function insertApplication(int $paymentId, int $invoiceId, float $amount): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO payment_applications (payment_id, invoice_id, amount_applied)
            VALUES (:payment_id, :invoice_id, :amount)
            ON DUPLICATE KEY UPDATE amount_applied = :amount2
        ");
        $stmt->execute([
            ':payment_id' => $paymentId,
            ':invoice_id' => $invoiceId,
            ':amount'     => $amount,
            ':amount2'    => $amount,
        ]);
    }

    public function recalcInvoice(int $invoiceId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE invoices i
            SET
                amount_paid = (
                    SELECT COALESCE(SUM(pa.amount_applied), 0)
                    FROM payment_applications pa
                    WHERE pa.invoice_id = i.id
                ),
                balance_due = i.total_amount - (
                    SELECT COALESCE(SUM(pa.amount_applied), 0)
                    FROM payment_applications pa
                    WHERE pa.invoice_id = i.id
                ),
                status = CASE
                    WHEN i.total_amount <= (
                        SELECT COALESCE(SUM(pa.amount_applied), 0)
                        FROM payment_applications pa
                        WHERE pa.invoice_id = i.id
                    ) THEN 'paid'
                    WHEN (
                        SELECT COALESCE(SUM(pa.amount_applied), 0)
                        FROM payment_applications pa
                        WHERE pa.invoice_id = i.id
                    ) > 0 THEN 'partial'
                    ELSE 'pending'
                END
            WHERE i.id = :id
        ");
        $stmt->execute([':id' => $invoiceId]);
    }

    public function getOpenInvoicesForCustomer(int $customerId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT i.id, i.invoice_number, i.invoice_date, i.due_date,
                   i.total_amount, i.amount_paid, i.balance_due, i.status
            FROM invoices i
            WHERE i.customer_id = :cid
              AND i.status IN ('pending','partial','overdue')
              AND i.balance_due > 0
            ORDER BY i.invoice_date ASC, i.id ASC
        ");
        $stmt->execute([':cid' => $customerId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT p.*,
                   c.company_name,
                   u.first_name AS created_first, u.last_name AS created_last
            FROM payments p
            JOIN customers c ON c.id = p.customer_id
            LEFT JOIN users u ON u.id = p.created_by
            WHERE p.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getApplications(int $paymentId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT pa.invoice_id, pa.amount_applied,
                   i.invoice_number, i.invoice_date, i.total_amount, i.balance_due, i.status
            FROM payment_applications pa
            JOIN invoices i ON i.id = pa.invoice_id
            WHERE pa.payment_id = :id
            ORDER BY i.invoice_date ASC
        ");
        $stmt->execute([':id' => $paymentId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function deleteApplications(int $paymentId): array
    {
        // Returns affected invoice IDs before deleting so we can recalc them
        $stmt = $this->pdo->prepare("SELECT invoice_id FROM payment_applications WHERE payment_id = :id");
        $stmt->execute([':id' => $paymentId]);
        $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $del = $this->pdo->prepare("DELETE FROM payment_applications WHERE payment_id = :id");
        $del->execute([':id' => $paymentId]);

        return $ids;
    }

    public function updatePayment(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE payments SET
                payment_date     = :payment_date,
                payment_method   = :payment_method,
                reference_number = :reference_number,
                amount           = :amount,
                memo             = :memo
            WHERE id = :id
        ");
        $data[':id'] = $id;
        $stmt->execute($data);
    }

    public function findBySalesOrder(int $soId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM payments WHERE sales_order_id = :so_id ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([':so_id' => $soId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getByInvoice(int $invoiceId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT p.id, p.payment_date, p.payment_method, p.reference_number,
                   p.amount, pa.amount_applied, p.memo,
                   u.first_name AS created_first, u.last_name AS created_last
            FROM payment_applications pa
            JOIN payments p ON p.id = pa.payment_id
            LEFT JOIN users u ON u.id = p.created_by
            WHERE pa.invoice_id = :id
            ORDER BY p.payment_date DESC, p.id DESC
        ");
        $stmt->execute([':id' => $invoiceId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
