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

    public function paginateWithBalance(int $page, int $perPage, string $search = '', string $filter = 'all', string $rep = ''): array
    {
        $params     = [];
        $conditions = ['1=1'];

        if ($search !== '') {
            $conditions[] = '(c.company_name LIKE ? OR c.quickbooks_name LIKE ? OR c.phone LIKE ? OR c.email LIKE ?)';
            $s = '%' . $search . '%';
            array_push($params, $s, $s, $s, $s);
        }

        if ($rep === 'none') {
            $conditions[] = 'c.sales_rep_id IS NULL';
        } elseif ($rep !== '') {
            $conditions[] = 'c.sales_rep_id = ?';
            $params[]     = (int)$rep;
        }

        if ($filter === 'balance') {
            $conditions[] = 'c.qb_balance > 0';
            $conditions[] = 'c.parent_id IS NULL';
        } elseif ($filter === 'inactive') {
            $conditions[] = 'c.is_active = 0';
            $conditions[] = 'c.parent_id IS NULL';
        } else {
            $conditions[] = 'c.is_active = 1';
            $conditions[] = 'c.parent_id IS NULL';
        }

        $where  = 'WHERE ' . implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $total = (int)(Database::selectOne(
            "SELECT COUNT(*) as total FROM customers c $where",
            $params
        )['total'] ?? 0);

        $rows = Database::select(
            "SELECT c.id, c.company_name, c.quickbooks_name, c.phone, c.email,
                    c.qb_balance, c.is_active, c.parent_id,
                    sr.name AS sales_rep_name, sr.rep_type AS sales_rep_type
             FROM customers c
             LEFT JOIN sales_reps sr ON sr.id = c.sales_rep_id
             $where
             ORDER BY c.company_name ASC
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
                   u.last_name    AS rep_last_name,
                   sr.id          AS sales_rep_id_val,
                   sr.name        AS sales_rep_name,
                   sr.rep_type    AS sales_rep_type
            FROM customers c
            LEFT JOIN customers p      ON p.id  = c.parent_id
            LEFT JOIN payment_terms pt ON pt.id = c.payment_term_id
            LEFT JOIN users u          ON u.id  = c.rep_id
            LEFT JOIN sales_reps sr    ON sr.id = c.sales_rep_id
            WHERE c.id = ?
            LIMIT 1
        ", [$id]);
    }

    /**
     * Purchase rhythm and receivable figures for the Customer Intelligence block.
     *
     * Voided invoices are excluded throughout — they are not sales. Credit memos are
     * counted, since a negative invoice legitimately reduces lifetime revenue.
     *
     * NOTE: this overlaps getStats() below, which predates it and feeds the KPI row.
     * The genuinely new fields here are order_frequency_days, orders_per_year,
     * outstanding_balance and overdue_balance. Worth consolidating the two into one
     * method once nothing else depends on getStats()'s exact keys.
     */
    public function getProfileStats(int $customerId): array
    {
        $row = Database::selectOne("
            SELECT
                COUNT(*)                              AS order_count,
                COALESCE(SUM(i.total_amount), 0)      AS lifetime_revenue,
                COALESCE(AVG(i.total_amount), 0)      AS avg_order_value,
                MIN(i.invoice_date)                   AS first_order_date,
                MAX(i.invoice_date)                   AS last_order_date,
                COALESCE(SUM(i.balance_due), 0)       AS outstanding_balance,
                SUM(CASE WHEN i.balance_due > 0 AND i.due_date < CURDATE()
                         THEN i.balance_due ELSE 0 END) AS overdue_balance
            FROM invoices i
            WHERE i.customer_id = ?
              AND i.status != 'void'
        ", [$customerId]) ?: [];

        $stats = [
            'order_count'         => (int)($row['order_count'] ?? 0),
            'lifetime_revenue'    => (float)($row['lifetime_revenue'] ?? 0),
            'avg_order_value'     => (float)($row['avg_order_value'] ?? 0),
            'first_order_date'    => $row['first_order_date'] ?? null,
            'last_order_date'     => $row['last_order_date'] ?? null,
            'outstanding_balance' => (float)($row['outstanding_balance'] ?? 0),
            'overdue_balance'     => (float)($row['overdue_balance'] ?? 0),
            'days_since_order'    => null,
            'order_frequency_days'=> null,
            'orders_per_year'     => null,
        ];

        if (!empty($stats['last_order_date'])) {
            $stats['days_since_order'] =
                (int)floor((time() - strtotime($stats['last_order_date'])) / 86400);
        }

        // Average gap between orders — only meaningful with at least two.
        if ($stats['order_count'] > 1 && $stats['first_order_date'] && $stats['last_order_date']) {
            $span = strtotime($stats['last_order_date']) - strtotime($stats['first_order_date']);
            $days = max(1, (int)floor($span / 86400));
            $stats['order_frequency_days'] = (int)round($days / ($stats['order_count'] - 1));
            $stats['orders_per_year']      = round($stats['order_count'] / max(1, $days / 365), 1);
        }

        return $stats;
    }

    /** What this customer buys most, by revenue. */
    public function getTopProducts(int $customerId, int $limit = 5): array
    {
        return Database::select("
            SELECT p.id, p.sku, p.name, p.color,
                   SUM(ili.qty)        AS total_qty,
                   SUM(ili.line_total) AS total_revenue,
                   COUNT(DISTINCT i.id) AS times_ordered,
                   MAX(i.invoice_date)  AS last_ordered
            FROM invoice_line_items ili
            JOIN invoices i ON i.id = ili.invoice_id
            JOIN products p ON p.id = ili.product_id
            WHERE i.customer_id = ?
              AND i.status != 'void'
              AND ili.product_id IS NOT NULL
            GROUP BY p.id, p.sku, p.name, p.color
            ORDER BY total_revenue DESC
            LIMIT " . max(1, min(50, $limit)) . "
        ", [$customerId]);
    }

    /** Revenue by month for the last N months, for a sparkline / trend read. */
    public function getRevenueByMonth(int $customerId, int $months = 12): array
    {
        $months = max(1, min(60, $months));
        return Database::select("
            SELECT DATE_FORMAT(i.invoice_date, '%Y-%m') AS period,
                   SUM(i.total_amount)                  AS revenue,
                   COUNT(*)                             AS orders
            FROM invoices i
            WHERE i.customer_id = ?
              AND i.status != 'void'
              AND i.invoice_date >= DATE_SUB(CURDATE(), INTERVAL {$months} MONTH)
            GROUP BY period
            ORDER BY period ASC
        ", [$customerId]);
    }

    /**
     * Inactivity and receivable alerts. Thresholds are deliberately simple and
     * derived from this customer's own ordering rhythm where one exists — a customer
     * who orders monthly going quiet for 90 days matters more than one who orders yearly.
     */
    public function getAlerts(int $customerId, array $stats): array
    {
        $alerts = [];

        $days = $stats['days_since_order'];
        if ($days !== null && $stats['order_count'] > 0) {
            $freq = $stats['order_frequency_days'];

            if ($days >= 180) {
                $alerts[] = ['level' => 'danger',
                    'text' => "No order in {$days} days"];
            } elseif ($days >= 90) {
                $alerts[] = ['level' => 'warning',
                    'text' => "No order in {$days} days"];
            } elseif ($freq !== null && $freq > 0 && $days > $freq * 2 && $days >= 45) {
                $alerts[] = ['level' => 'warning',
                    'text' => "Ordering has slowed — usually every ~{$freq} days, last was {$days} days ago"];
            }
        }

        if ($stats['overdue_balance'] > 0) {
            $alerts[] = ['level' => 'danger',
                'text' => 'Overdue balance of ' . money($stats['overdue_balance'])];
        }

        // Quotes that will lapse in the next fortnight
        $expiring = Database::select("
            SELECT quote_number, expiry_date
            FROM quotes
            WHERE customer_id = ?
              AND status IN ('draft','sent')
              AND expiry_date IS NOT NULL
              AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)
            ORDER BY expiry_date ASC
        ", [$customerId]);
        foreach ($expiring as $q) {
            $alerts[] = ['level' => 'warning',
                'text' => 'Quote ' . $q['quote_number'] . ' expires ' . date('M j', strtotime($q['expiry_date']))];
        }

        // Quotes already lapsed but never closed out
        $lapsed = (int)(Database::selectOne("
            SELECT COUNT(*) c FROM quotes
            WHERE customer_id = ? AND status IN ('draft','sent')
              AND expiry_date IS NOT NULL AND expiry_date < CURDATE()
        ", [$customerId])['c'] ?? 0);
        if ($lapsed > 0) {
            $alerts[] = ['level' => 'neutral',
                'text' => $lapsed . ' quote' . ($lapsed === 1 ? '' : 's') . ' expired without a decision'];
        }

        return $alerts;
    }

    /**
     * One chronological feed of everything that happened with a customer.
     *
     * Built as a UNION rather than six separate queries the view interleaves, so it can be
     * paged and reused — the Rep Portal and Marketing both need the same feed.
     *
     * Each branch produces the same shape: when it happened, what kind of event, a title,
     * a detail line, an optional amount, and where to click through to. `sort_at` carries
     * a datetime for ordering even where the source only stores a date.
     */
    public function getActivityTimeline(int $customerId, int $limit = 60): array
    {
        $limit = max(1, min(300, $limit));

        return Database::select("
            (
                SELECT 'note' AS event_type,
                       n.created_at              AS sort_at,
                       DATE(n.created_at)        AS event_date,
                       COALESCE(NULLIF(n.note_type,''), 'Note') AS title,
                       n.body                    AS detail,
                       NULL                      AS amount,
                       NULL                      AS ref,
                       NULL                      AS link,
                       TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) AS actor
                FROM customer_notes n
                LEFT JOIN users u ON u.id = n.user_id
                WHERE n.customer_id = ?
            )
            UNION ALL
            (
                SELECT 'invoice',
                       COALESCE(i.created_at, i.invoice_date),
                       i.invoice_date,
                       CASE WHEN i.status = 'paid'  THEN 'Invoice paid'
                            WHEN i.status = 'void'  THEN 'Invoice voided'
                            ELSE 'Invoice issued' END,
                       CONCAT('Invoice ', i.invoice_number,
                              CASE WHEN i.balance_due > 0
                                   THEN CONCAT(' — ', FORMAT(i.balance_due, 2), ' outstanding')
                                   ELSE '' END),
                       i.total_amount,
                       i.invoice_number,
                       CONCAT('/invoices/', i.id),
                       NULL
                FROM invoices i
                WHERE i.customer_id = ?
            )
            UNION ALL
            (
                SELECT 'order',
                       COALESCE(so.created_at, so.order_date),
                       so.order_date,
                       CONCAT('Sales order ', REPLACE(so.status, '_', ' ')),
                       CONCAT('SO ', so.so_number),
                       so.total_amount,
                       so.so_number,
                       CONCAT('/sales-orders/', so.id),
                       NULL
                FROM sales_orders so
                WHERE so.customer_id = ?
            )
            UNION ALL
            (
                SELECT 'payment',
                       COALESCE(p.created_at, p.payment_date),
                       p.payment_date,
                       CONCAT('Payment received — ', REPLACE(p.payment_method, '_', ' ')),
                       COALESCE(NULLIF(CONCAT('Ref ', p.reference_number), 'Ref '), 'Payment'),
                       p.amount,
                       p.reference_number,
                       NULL,
                       NULL
                FROM payments p
                WHERE p.customer_id = ?
            )
            UNION ALL
            (
                SELECT 'quote',
                       COALESCE(q.created_at, q.quote_date),
                       q.quote_date,
                       CONCAT('Quote ', q.status),
                       CONCAT('Quote ', q.quote_number),
                       q.total_amount,
                       q.quote_number,
                       CONCAT('/quotes/', q.id),
                       NULL
                FROM quotes q
                WHERE q.customer_id = ?
            )
            UNION ALL
            (
                SELECT 'task',
                       COALESCE(t.completed_at, t.created_at),
                       DATE(COALESCE(t.completed_at, t.created_at)),
                       CASE WHEN t.status = 'completed' THEN 'Task completed' ELSE 'Task created' END,
                       t.title,
                       NULL,
                       NULL,
                       CONCAT('/tasks/', t.id, '/edit'),
                       TRIM(CONCAT(COALESCE(u2.first_name,''), ' ', COALESCE(u2.last_name,'')))
                FROM tasks t
                LEFT JOIN users u2 ON u2.id = t.assigned_to
                WHERE t.customer_id = ?
            )
            ORDER BY sort_at DESC, event_date DESC
            LIMIT {$limit}
        ", array_fill(0, 6, $customerId));
    }

    /** How many events exist in total, so the view can say what it's truncating. */
    public function countActivity(int $customerId): int
    {
        $row = Database::selectOne("
            SELECT
              (SELECT COUNT(*) FROM customer_notes WHERE customer_id = ?) +
              (SELECT COUNT(*) FROM invoices       WHERE customer_id = ?) +
              (SELECT COUNT(*) FROM sales_orders   WHERE customer_id = ?) +
              (SELECT COUNT(*) FROM payments       WHERE customer_id = ?) +
              (SELECT COUNT(*) FROM quotes         WHERE customer_id = ?) +
              (SELECT COUNT(*) FROM tasks          WHERE customer_id = ?) AS c
        ", array_fill(0, 6, $customerId));
        return (int)($row['c'] ?? 0);
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
