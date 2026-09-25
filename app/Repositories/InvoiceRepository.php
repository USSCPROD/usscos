<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class InvoiceRepository
{
    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function paginate(int $page, int $perPage, string $search, string $status, string $sort): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where  = ['1=1'];

        // Reps and distributors see only documents belonging to their own customers.
        [$scopeSql, $scopeParams] = \App\Services\AccessScope::customerOwnedConditionNamed('i');
        if ($scopeSql !== '') {
            $where[] = $scopeSql;
            $params  = array_merge($params, $scopeParams);
        }

        if ($search !== '') {
            $where[]          = '(i.invoice_number LIKE :s OR c.company_name LIKE :s2 OR i.po_number LIKE :s3)';
            $params[':s']     = "%{$search}%";
            $params[':s2']    = "%{$search}%";
            $params[':s3']    = "%{$search}%";
        }

        if ($status !== 'all') {
            $where[]           = 'i.status = :status';
            $params[':status'] = $status;
        }

        $orderMap = [
            'date_desc'    => 'i.invoice_date DESC',
            'date_asc'     => 'i.invoice_date ASC',
            'due_asc'      => 'i.due_date ASC',
            'due_desc'     => 'i.due_date DESC',
            'balance_desc' => 'i.balance_due DESC',
            'number_desc'  => 'i.invoice_number DESC',
        ];
        $orderBy = $orderMap[$sort] ?? 'i.invoice_date DESC';

        $whereStr = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) FROM invoices i JOIN customers c ON c.id = i.customer_id WHERE {$whereStr}";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "
            SELECT i.id, i.invoice_number, i.invoice_date, i.due_date, i.status, i.invoice_type,
                   i.total_amount, i.balance_due, i.aging_days, i.po_number,
                   c.id AS customer_id, c.company_name
            FROM invoices i
            JOIN customers c ON c.id = i.customer_id
            WHERE {$whereStr}
            ORDER BY {$orderBy}
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  \PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $lastPage = max(1, (int)ceil($total / $perPage));

        return [
            'data'         => $data,
            'total'        => $total,
            'current_page' => $page,
            'last_page'    => $lastPage,
            'per_page'     => $perPage,
            'from'         => $total > 0 ? $offset + 1 : 0,
            'to'           => min($offset + $perPage, $total),
        ];
    }

    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT i.*,
                   c.company_name, c.email, c.phone,
                   c.bill_address_1, c.bill_address_2, c.bill_city, c.bill_state, c.bill_zip,
                   pt.name AS payment_term_name,
                   u.first_name AS rep_first, u.last_name AS rep_last,
                   so.so_number
            FROM invoices i
            JOIN customers c ON c.id = i.customer_id
            LEFT JOIN payment_terms pt ON pt.id = i.payment_term_id
            LEFT JOIN users u ON u.id = i.rep_id
            LEFT JOIN sales_orders so ON so.id = i.sales_order_id
            WHERE i.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        // A guessed id must not open another rep's invoice — treated as not found.
        if (!\App\Services\AccessScope::canSeeCustomer((int)$row['customer_id'])) {
            return null;
        }

        return $row;
    }

    public function getLineItems(int $invoiceId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT li.*,
                   p.sku, p.name AS product_name,
                   u.code AS uom_code
            FROM invoice_line_items li
            LEFT JOIN products p ON p.id = li.product_id
            LEFT JOIN units_of_measure u ON u.id = li.uom_id
            WHERE li.invoice_id = :id
            ORDER BY li.sort_order, li.id
        ");
        $stmt->execute([':id' => $invoiceId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE invoices SET
                po_number        = :po_number,
                invoice_date     = :invoice_date,
                due_date         = :due_date,
                payment_term_id  = :payment_term_id,
                rep_id           = :rep_id,
                ship_date        = :ship_date,
                tracking_number  = :tracking_number,
                ship_via         = :ship_via,
                ship_address_1   = :ship_address_1,
                ship_address_2   = :ship_address_2,
                ship_city        = :ship_city,
                ship_state       = :ship_state,
                ship_zip         = :ship_zip,
                memo             = :memo,
                internal_notes   = :internal_notes
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id'             => $id,
            ':po_number'      => $data['po_number']      ?: null,
            ':invoice_date'   => $data['invoice_date'],
            ':due_date'       => $data['due_date'],
            ':payment_term_id'=> $data['payment_term_id'] ?: null,
            ':rep_id'         => $data['rep_id']          ?: null,
            ':ship_date'      => ($data['ship_date'] ?? '') ?: null,
            ':tracking_number'=> ($data['tracking_number'] ?? '') ?: null,
            ':ship_via'       => $data['ship_via']        ?: null,
            ':ship_address_1' => $data['ship_address_1']  ?: null,
            ':ship_address_2' => $data['ship_address_2']  ?: null,
            ':ship_city'      => $data['ship_city']       ?: null,
            ':ship_state'     => $data['ship_state']      ?: null,
            ':ship_zip'       => $data['ship_zip']        ?: null,
            ':memo'           => $data['memo']            ?: null,
            ':internal_notes' => $data['internal_notes']  ?: null,
        ]);
    }

    public function updateTotals(int $id, array $totals): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE invoices SET
                subtotal        = :subtotal,
                discount_amount = :discount,
                tax_amount      = :tax,
                total_amount    = :total,
                -- A credit memo's balance is negative on purpose: it is owed to the
                -- customer and nets against what they owe us. Clamping it at zero, as an
                -- invoice's balance is clamped, would erase it from A/R.
                balance_due     = CASE
                                      WHEN :total2 < 0 THEN :total3 - amount_paid
                                      ELSE GREATEST(0, :total4 - amount_paid)
                                  END
            WHERE id = :id
        ");
        $stmt->execute([
            ':subtotal' => $totals['subtotal'],
            ':discount' => $totals['discount'],
            ':tax'      => $totals['tax'],
            // Named parameters may appear only once per query — see CLAUDE.md.
            ':total'    => $totals['total'],
            ':total2'   => $totals['total'],
            ':total3'   => $totals['total'],
            ':total4'   => $totals['total'],
            ':id'       => $id,
        ]);
    }

    /**
     * Invoices that shipped and are waiting on the bookkeeper.
     *
     * Ordered oldest first, because the customer has been waiting for their tracking since
     * the box went on the truck.
     */
    public function awaitingReview(): array
    {
        return Database::select("
            SELECT i.id, i.invoice_number, i.invoice_date, i.ship_date, i.po_number,
                   i.subtotal, i.total_amount, i.tracking_number, i.shipment_email_status,
                   c.company_name, c.email AS customer_email,
                   so.so_number,
                   (SELECT COUNT(*) FROM shipment_tracking t WHERE t.invoice_id = i.id) AS tracking_count
            FROM invoices i
            JOIN customers c ON c.id = i.customer_id
            LEFT JOIN sales_orders so ON so.id = i.sales_order_id
            WHERE i.review_status = 'pending' AND i.status <> 'void'
            ORDER BY i.ship_date, i.id
        ");
    }

    public function countAwaitingReview(): int
    {
        $row = Database::selectOne(
            "SELECT COUNT(*) AS n FROM invoices WHERE review_status = 'pending' AND status <> 'void'"
        );

        return (int)($row['n'] ?? 0);
    }

    /** A shipped invoice is not finished — it waits for the bookkeeper. */
    public function markAwaitingReview(int $id): void
    {
        Database::statement(
            "UPDATE invoices SET review_status = 'pending' WHERE id = ? AND review_status <> 'approved'",
            [$id]
        );
    }

    public function markReviewed(int $id, ?int $userId): void
    {
        Database::statement("
            UPDATE invoices SET review_status = 'approved', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?
        ", [$userId, $id]);
    }

    public function nextInvoiceNumber(): string
    {
        $stmt = $this->pdo->query("SELECT MAX(CAST(invoice_number AS UNSIGNED)) FROM invoices");
        $max  = (int)$stmt->fetchColumn();
        return (string)($max + 1);
    }

    public function insert(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO invoices
                (customer_id, sales_order_id, invoice_number, po_number, invoice_type, credits_invoice_id, status,
                 invoice_date, due_date, payment_term_id,
                 subtotal, discount_amount, tax_amount, total_amount, balance_due,
                 ship_date, ship_via, tracking_number, ship_address_1, ship_address_2,
                 ship_city, ship_state, ship_zip,
                 memo, internal_notes, created_by, rep_id)
            VALUES
                (:customer_id, :sales_order_id, :invoice_number, :po_number, :invoice_type, :credits_invoice_id, 'draft',
                 :invoice_date, :due_date, :payment_term_id,
                 :subtotal, :discount_amount, :tax_amount, :total_amount, :balance_due,
                 :ship_date, :ship_via, :tracking_number, :ship_address_1, :ship_address_2,
                 :ship_city, :ship_state, :ship_zip,
                 :memo, :internal_notes, :created_by, :rep_id)
        ");
        // Defaulted rather than required, so the existing callers are untouched.
        $data += [':invoice_type' => 'invoice', ':credits_invoice_id' => null];

        $stmt->execute($data);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Record how this invoice was taxed, and why.
     *
     * Separate from insert() because the rate can only be worked out once the lines exist,
     * and because what is stored here must never be recomputed afterwards: the Georgia
     * return is filed by jurisdiction, and a rate change next quarter must not rewrite
     * what last quarter's invoices said.
     */
    public function setTaxDetail(int $invoiceId, array $tax): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE invoices SET
                tax_rate_id               = :rate_id,
                tax_rate_applied          = :rate,
                taxable_subtotal          = :base,
                tax_source                = :source,
                tax_reason                = :reason,
                marketplace_tax_collected = :market
            WHERE id = :id
        ");
        $stmt->execute([
            ':rate_id' => $tax['tax_rate_id'] ?? null,
            ':rate'    => $tax['rate']        ?? 0,
            ':base'    => $tax['base']        ?? 0,
            ':source'  => $tax['source']      ?? 'none',
            ':reason'  => $tax['reason']      ?? null,
            ':market'  => $tax['marketplace_tax'] ?? 0,
            ':id'      => $invoiceId,
        ]);
    }

    public function replaceLineItems(int $invoiceId, array $lines): void
    {
        $del = $this->pdo->prepare("DELETE FROM invoice_line_items WHERE invoice_id = :id");
        $del->execute([':id' => $invoiceId]);

        if (empty($lines)) return;

        $ins = $this->pdo->prepare("
            INSERT INTO invoice_line_items
                (invoice_id, product_id, quickbooks_item, description,
                 qty, uom_id, unit_price, discount_pct, is_taxable, line_total, sort_order)
            VALUES
                (:invoice_id, :product_id, :quickbooks_item, :description,
                 :qty, :uom_id, :unit_price, :discount_pct, :is_taxable, :line_total, :sort_order)
        ");

        foreach ($lines as $i => $line) {
            $ins->execute([
                ':invoice_id'     => $invoiceId,
                ':product_id'     => $line['product_id']      ?: null,
                ':quickbooks_item'=> $line['quickbooks_item']  ?: null,
                ':description'    => $line['description']      ?: null,
                ':qty'            => $line['qty']              ?? 1,
                ':uom_id'         => $line['uom_id']           ?: null,
                ':unit_price'     => $line['unit_price']       ?? 0,
                ':discount_pct'   => $line['discount_pct']     ?? 0,
                ':is_taxable'     => (int)($line['is_taxable'] ?? 0),
                ':line_total'     => $line['line_total']       ?? 0,
                ':sort_order'     => $i + 1,
            ]);
        }
    }

    public function getShipViaOptions(): array
    {
        return $this->pdo->query("SELECT id, name FROM ship_via WHERE is_active = 1 ORDER BY sort_order, name")
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getTaxRates(): array
    {
        return $this->pdo->query("SELECT id, name, rate FROM tax_rates WHERE is_active = 1 ORDER BY name")
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getCustomerMessages(): array
    {
        return $this->pdo->query("SELECT id, message FROM customer_messages WHERE is_active = 1 ORDER BY sort_order")
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getReps(): array
    {
        return $this->pdo->query("SELECT id, first_name, last_name, rep_code FROM users WHERE role = 'rep' AND is_active = 1 ORDER BY last_name")
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getActiveUsers(): array
    {
        return $this->pdo->query("SELECT id, first_name, last_name FROM users WHERE is_active = 1 ORDER BY last_name, first_name")
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getSummaryStats(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                COUNT(*) AS total_invoices,
                SUM(CASE WHEN status IN ('pending','partial','overdue') THEN 1 ELSE 0 END) AS open_count,
                SUM(CASE WHEN status IN ('pending','partial','overdue') THEN balance_due ELSE 0 END) AS total_ar,
                SUM(CASE WHEN status = 'overdue' THEN balance_due ELSE 0 END) AS overdue_ar,
                SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) AS overdue_count
            FROM invoices
        ");
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getAgingBuckets(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                SUM(CASE WHEN aging_days BETWEEN 1 AND 30  THEN balance_due ELSE 0 END) AS bucket_1_30,
                SUM(CASE WHEN aging_days BETWEEN 31 AND 60 THEN balance_due ELSE 0 END) AS bucket_31_60,
                SUM(CASE WHEN aging_days BETWEEN 61 AND 90 THEN balance_due ELSE 0 END) AS bucket_61_90,
                SUM(CASE WHEN aging_days > 90              THEN balance_due ELSE 0 END) AS bucket_90_plus
            FROM invoices
            WHERE status IN ('pending','partial','overdue')
        ");
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function setStatus(int $id, string $status): void
    {
        $stmt = Database::connection()->prepare("UPDATE invoices SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $id]);
    }
}
