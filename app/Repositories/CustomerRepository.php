<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\Repository;

class CustomerRepository extends Repository
{
    protected function getTable(): string
    {
        return 'customers';
    }

    public function search(string $term, array $columns = [], int $limit = 50): array
    {
        $term = '%' . $term . '%';
        return Database::select("
            SELECT id, company_name, quickbooks_name, phone, email, qb_balance, is_active
            FROM customers
            WHERE (company_name LIKE ? OR quickbooks_name LIKE ? OR phone LIKE ? OR email LIKE ?)
            AND is_active = 1
            ORDER BY company_name ASC
            LIMIT ?
        ", [$term, $term, $term, $term, $limit]);
    }

    public function paginateWithBalance(int $page, int $perPage, string $search = '', string $filter = 'all'): array
    {
        $params     = [];
        $conditions = ['1=1'];

        if ($search !== '') {
            $conditions[] = '(company_name LIKE ? OR quickbooks_name LIKE ? OR phone LIKE ? OR email LIKE ?)';
            $s = '%' . $search . '%';
            array_push($params, $s, $s, $s, $s);
        }

        if ($filter === 'balance') {
            $conditions[] = 'qb_balance > 0';
            $conditions[] = 'parent_id IS NULL';
        } elseif ($filter === 'inactive') {
            $conditions[] = 'is_active = 0';
            $conditions[] = 'parent_id IS NULL';
        } else {
            $conditions[] = 'is_active = 1';
            $conditions[] = 'parent_id IS NULL';
        }

        $where  = 'WHERE ' . implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $total = (int)(Database::selectOne(
            "SELECT COUNT(*) as total FROM customers $where",
            $params
        )['total'] ?? 0);

        $rows = Database::select(
            "SELECT id, company_name, quickbooks_name, phone, email, qb_balance, is_active, parent_id
             FROM customers $where
             ORDER BY company_name ASC
             LIMIT $perPage OFFSET $offset",
            $params
        );

        return [
            'data'         => $rows,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int)ceil($total / $perPage),
            'from'         => $offset + 1,
            'to'           => min($offset + $perPage, $total),
        ];
    }

    public function findWithDetails(int $id): array|false
    {
        return Database::selectOne("
            SELECT c.*,
                   p.company_name AS parent_name,
                   pt.name        AS payment_term_name,
                   u.first_name   AS rep_first_name,
                   u.last_name    AS rep_last_name
            FROM customers c
            LEFT JOIN customers p    ON p.id = c.parent_id
            LEFT JOIN payment_terms pt ON pt.id = c.payment_term_id
            LEFT JOIN users u        ON u.id = c.rep_id
            WHERE c.id = ?
            LIMIT 1
        ", [$id]);
    }

    public function getInvoices(int $customerId, int $limit = 50): array
    {
        return Database::select("
            SELECT id, invoice_number, po_number, invoice_date, due_date,
                   total_amount, balance_due, status, aging_days
            FROM invoices
            WHERE customer_id = ?
            ORDER BY invoice_date DESC
            LIMIT ?
        ", [$customerId, $limit]);
    }

    public function getSalesOrders(int $customerId, int $limit = 50): array
    {
        return Database::select("
            SELECT so.id, so.so_number, so.order_date, so.requested_ship_date,
                   so.po_number, so.total_amount, so.status,
                   u.first_name AS rep_first, u.last_name AS rep_last
            FROM sales_orders so
            LEFT JOIN users u ON u.id = so.rep_id
            WHERE so.customer_id = ?
            ORDER BY so.order_date DESC, so.id DESC
            LIMIT ?
        ", [$customerId, $limit]);
    }

    public function getPayments(int $customerId, int $limit = 100): array
    {
        return Database::select("
            SELECT p.id, p.payment_date, p.payment_method, p.reference_number,
                   p.amount, p.memo, p.sales_order_id,
                   GROUP_CONCAT(i.invoice_number ORDER BY i.invoice_number SEPARATOR ', ') AS applied_to,
                   SUM(pa.amount_applied) AS total_applied
            FROM payments p
            LEFT JOIN payment_applications pa ON pa.payment_id = p.id
            LEFT JOIN invoices i ON i.id = pa.invoice_id
            WHERE p.customer_id = ?
            GROUP BY p.id
            ORDER BY p.payment_date DESC, p.id DESC
            LIMIT ?
        ", [$customerId, $limit]);
    }

    public function getSubCustomers(int $parentId): array
    {
        return Database::select("
            SELECT id, company_name, phone, email, qb_balance
            FROM customers
            WHERE parent_id = ?
            ORDER BY company_name ASC
        ", [$parentId]);
    }

    public function getTotalAR(): float
    {
        $row = Database::selectOne("SELECT SUM(qb_balance) as total FROM customers WHERE qb_balance > 0");
        return (float)($row['total'] ?? 0);
    }

    public function getStats(int $customerId): array
    {
        $pdo = Database::connection();

        $inv = $pdo->prepare("
            SELECT COUNT(*) AS invoice_count,
                   COALESCE(SUM(total_amount), 0) AS lifetime_revenue,
                   COALESCE(AVG(total_amount), 0) AS avg_invoice,
                   MAX(invoice_date) AS last_invoice_date
            FROM invoices WHERE customer_id = :id AND status != 'void'
        ");
        $inv->execute([':id' => $customerId]);
        $invStats = $inv->fetch(\PDO::FETCH_ASSOC);

        $so = $pdo->prepare("
            SELECT COUNT(*) AS so_count, MAX(order_date) AS last_order_date
            FROM sales_orders WHERE customer_id = :id AND status != 'cancelled'
        ");
        $so->execute([':id' => $customerId]);
        $soStats = $so->fetch(\PDO::FETCH_ASSOC);

        $lastActivity = max(
            $invStats['last_invoice_date'] ?? '',
            $soStats['last_order_date']    ?? ''
        ) ?: null;

        $daysSince = null;
        if ($lastActivity) {
            $daysSince = (int)floor((time() - strtotime($lastActivity)) / 86400);
        }

        return [
            'lifetime_revenue' => (float)$invStats['lifetime_revenue'],
            'invoice_count'    => (int)$invStats['invoice_count'],
            'so_count'         => (int)$soStats['so_count'],
            'avg_invoice'      => (float)$invStats['avg_invoice'],
            'last_activity'    => $lastActivity,
            'days_since'       => $daysSince,
        ];
    }

    public function getNotes(int $customerId): array
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare("
            SELECT cn.*, u.first_name, u.last_name
            FROM customer_notes cn
            LEFT JOIN users u ON u.id = cn.user_id
            WHERE cn.customer_id = :id
            ORDER BY cn.created_at DESC
            LIMIT 100
        ");
        $stmt->execute([':id' => $customerId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function addNote(int $customerId, int $userId, string $type, string $body): void
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare("
            INSERT INTO customer_notes (customer_id, user_id, note_type, body, created_at)
            VALUES (:customer_id, :user_id, :note_type, :body, NOW())
        ");
        $stmt->execute([
            ':customer_id' => $customerId,
            ':user_id'     => $userId,
            ':note_type'   => $type,
            ':body'        => $body,
        ]);
    }

    public function getAllPaymentTerms(): array
    {
        return Database::select("SELECT id, name, days_due FROM payment_terms WHERE is_active = 1 ORDER BY name ASC");
    }

    public function update(int|string $id, array $data): int
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare("
            UPDATE customers SET
                company_name              = :company_name,
                first_name                = :first_name,
                last_name                 = :last_name,
                email                     = :email,
                cc_email                  = :cc_email,
                phone                     = :phone,
                work_phone                = :work_phone,
                mobile                    = :mobile,
                fax                       = :fax,
                account_number            = :account_number,
                payment_term_id           = :payment_term_id,
                credit_limit              = :credit_limit,
                preferred_delivery_method = :preferred_delivery_method,
                preferred_payment_method  = :preferred_payment_method,
                cc_number                 = :cc_number,
                cc_exp_date               = :cc_exp_date,
                cc_name                   = :cc_name,
                cc_billing_address        = :cc_billing_address,
                cc_billing_zip            = :cc_billing_zip,
                tax_exempt                = :tax_exempt,
                sales_tax_code            = :sales_tax_code,
                tax_rate_id               = :tax_rate_id,
                resale_number             = :resale_number,
                bill_address_1            = :bill_address_1,
                bill_address_2            = :bill_address_2,
                bill_city                 = :bill_city,
                bill_state                = :bill_state,
                bill_zip                  = :bill_zip,
                ship_company              = :ship_company,
                ship_contact              = :ship_contact,
                ship_phone                = :ship_phone,
                ship_address_1            = :ship_address_1,
                ship_address_2            = :ship_address_2,
                ship_city                 = :ship_city,
                ship_state                = :ship_state,
                ship_zip                  = :ship_zip,
                notes                     = :notes,
                is_active                 = :is_active,
                customer_type             = :customer_type,
                rep_id                    = :rep_id
            WHERE id = :id
        ");
        $stmt->execute([
            ':id'                        => $id,
            ':company_name'              => $data['company_name'],
            ':first_name'                => $data['first_name']               ?: null,
            ':last_name'                 => $data['last_name']                ?: null,
            ':email'                     => $data['email']                    ?: null,
            ':cc_email'                  => $data['cc_email']                 ?: null,
            ':phone'                     => $data['phone']                    ?: null,
            ':work_phone'                => $data['work_phone']               ?: null,
            ':mobile'                    => $data['mobile']                   ?: null,
            ':fax'                       => $data['fax']                      ?: null,
            ':account_number'            => $data['account_number']           ?: null,
            ':payment_term_id'           => $data['payment_term_id']          ?: null,
            ':credit_limit'              => $data['credit_limit']             ?: null,
            ':preferred_delivery_method' => $data['preferred_delivery_method'] ?: null,
            ':preferred_payment_method'  => $data['preferred_payment_method']  ?: null,
            ':cc_number'                 => $data['cc_number']                ?: null,
            ':cc_exp_date'               => $data['cc_exp_date']              ?: null,
            ':cc_name'                   => $data['cc_name']                  ?: null,
            ':cc_billing_address'        => $data['cc_billing_address']       ?: null,
            ':cc_billing_zip'            => $data['cc_billing_zip']           ?: null,
            ':tax_exempt'                => (int)($data['tax_exempt'] ?? 0),
            ':sales_tax_code'            => $data['sales_tax_code']           ?: null,
            ':tax_rate_id'               => $data['tax_rate_id']              ?: null,
            ':resale_number'             => $data['resale_number']            ?: null,
            ':bill_address_1'            => $data['bill_address_1']           ?: null,
            ':bill_address_2'            => $data['bill_address_2']           ?: null,
            ':bill_city'                 => $data['bill_city']                ?: null,
            ':bill_state'                => $data['bill_state']               ?: null,
            ':bill_zip'                  => $data['bill_zip']                 ?: null,
            ':ship_company'              => $data['ship_company']             ?: null,
            ':ship_contact'              => $data['ship_contact']             ?: null,
            ':ship_phone'                => $data['ship_phone']               ?: null,
            ':ship_address_1'            => $data['ship_address_1']           ?: null,
            ':ship_address_2'            => $data['ship_address_2']           ?: null,
            ':ship_city'                 => $data['ship_city']                ?: null,
            ':ship_state'                => $data['ship_state']               ?: null,
            ':ship_zip'                  => $data['ship_zip']                 ?: null,
            ':notes'                     => $data['notes']                    ?: null,
            ':is_active'                 => (int)($data['is_active'] ?? 1),
            ':customer_type'             => $data['customer_type']            ?: null,
            ':rep_id'                    => $data['rep_id']                   ?: null,
        ]);
        return $stmt->rowCount();
    }

    public function insertFromLead(array $data): int
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare("
            INSERT INTO customers
                (company_name, quickbooks_name, first_name, last_name, email, phone, rep_id, is_active)
            VALUES
                (:company_name, :quickbooks_name, :first_name, :last_name, :email, :phone, :rep_id, 1)
        ");
        $stmt->execute([
            ':company_name'    => $data['company_name'],
            ':quickbooks_name' => $data['company_name'],
            ':first_name'      => $data['first_name'] ?? null,
            ':last_name'       => $data['last_name']  ?? null,
            ':email'           => $data['email']      ?? null,
            ':phone'           => $data['phone']      ?? null,
            ':rep_id'          => $data['rep_id']     ?? null,
        ]);
        return (int)$pdo->lastInsertId();
    }
}
