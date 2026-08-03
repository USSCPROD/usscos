<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class QuoteRepository
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

        if ($search !== '') {
            $where[]       = '(q.quote_number LIKE :s OR c.company_name LIKE :s2 OR q.po_number LIKE :s3)';
            $params[':s']  = "%{$search}%";
            $params[':s2'] = "%{$search}%";
            $params[':s3'] = "%{$search}%";
        }

        if ($status !== 'all') {
            $where[]           = 'q.status = :status';
            $params[':status'] = $status;
        }

        $orderMap = [
            'date_desc'   => 'q.quote_date DESC',
            'date_asc'    => 'q.quote_date ASC',
            'expiry_asc'  => 'q.expiry_date ASC',
            'total_desc'  => 'q.total_amount DESC',
            'number_desc' => 'q.quote_number DESC',
        ];
        $orderBy  = $orderMap[$sort] ?? 'q.quote_date DESC';
        $whereStr = implode(' AND ', $where);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM quotes q LEFT JOIN customers c ON c.id = q.customer_id WHERE {$whereStr}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "
            SELECT q.id, q.quote_number, q.quote_date, q.expiry_date, q.status,
                   q.total_amount, q.po_number, q.customer_id,
                   COALESCE(c.company_name, l.company_name) AS company_name,
                   u.first_name AS rep_first, u.last_name AS rep_last
            FROM quotes q
            LEFT JOIN customers c ON c.id = q.customer_id
            LEFT JOIN leads l     ON l.id = q.lead_id
            LEFT JOIN users u ON u.id = q.rep_id
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

        $lastPage = max(1, (int)ceil($total / $perPage));

        return [
            'data'         => $stmt->fetchAll(\PDO::FETCH_ASSOC),
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
            SELECT q.*,
                   COALESCE(c.company_name, l.company_name) AS company_name,
                   COALESCE(c.email, l.email)               AS customer_email,
                   COALESCE(c.phone, l.phone)               AS customer_phone,
                   c.bill_address_1, c.bill_address_2, c.bill_city, c.bill_state, c.bill_zip,
                   pt.name AS payment_term_name, pt.days_due,
                   sv.name AS ship_via_name,
                   tr.name AS tax_rate_name, tr.rate AS tax_rate_pct,
                   u.first_name AS rep_first, u.last_name AS rep_last,
                   cb.first_name AS created_first, cb.last_name AS created_last,
                   so.so_number,
                   opp.name AS opportunity_name, opp.stage AS opportunity_stage
            FROM quotes q
            LEFT JOIN customers c  ON c.id = q.customer_id
            LEFT JOIN leads l      ON l.id = q.lead_id
            LEFT JOIN opportunities opp ON opp.id = q.opportunity_id
            LEFT JOIN payment_terms pt ON pt.id = q.payment_term_id
            LEFT JOIN ship_via sv ON sv.id = q.ship_via_id
            LEFT JOIN tax_rates tr ON tr.id = q.tax_rate_id
            LEFT JOIN users u ON u.id = q.rep_id
            LEFT JOIN users cb ON cb.id = q.created_by
            LEFT JOIN sales_orders so ON so.id = q.sales_order_id
            WHERE q.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getLineItems(int $quoteId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT li.*, p.sku, p.name AS product_name, u.code AS uom_code
            FROM quote_line_items li
            LEFT JOIN products p ON p.id = li.product_id
            LEFT JOIN units_of_measure u ON u.id = li.uom_id
            WHERE li.quote_id = :id
            ORDER BY li.sort_order, li.id
        ");
        $stmt->execute([':id' => $quoteId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function insert(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO quotes
                (quote_number, customer_id, lead_id, opportunity_id, created_by, rep_id,
                 status, quote_date, expiry_date, po_number, payment_term_id, ship_via_id, tax_rate_id,
                 ship_name, ship_address_1, ship_address_2, ship_city, ship_state, ship_zip,
                 subtotal, discount_amount, tax_amount, total_amount, memo, internal_notes)
            VALUES
                (:quote_number, :customer_id, :lead_id, :opportunity_id, :created_by, :rep_id,
                 'draft', :quote_date, :expiry_date, :po_number, :payment_term_id, :ship_via_id, :tax_rate_id,
                 :ship_name, :ship_address_1, :ship_address_2, :ship_city, :ship_state, :ship_zip,
                 :subtotal, :discount_amount, :tax_amount, :total_amount, :memo, :internal_notes)
        ");
        $stmt->execute($data);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE quotes SET
                po_number       = :po_number,
                payment_term_id = :payment_term_id,
                ship_via_id     = :ship_via_id,
                tax_rate_id     = :tax_rate_id,
                quote_date      = :quote_date,
                expiry_date     = :expiry_date,
                rep_id          = :rep_id,
                ship_name       = :ship_name,
                ship_address_1  = :ship_address_1,
                ship_address_2  = :ship_address_2,
                ship_city       = :ship_city,
                ship_state      = :ship_state,
                ship_zip        = :ship_zip,
                subtotal        = :subtotal,
                discount_amount = :discount_amount,
                tax_amount      = :tax_amount,
                total_amount    = :total_amount,
                memo            = :memo,
                internal_notes  = :internal_notes
            WHERE id = :id
        ");
        $stmt->execute(array_merge($data, [':id' => $id]));
    }

    public function setStatus(int $id, string $status, ?string $acceptedDate = null): void
    {
        if ($status === 'accepted' && $acceptedDate) {
            $stmt = $this->pdo->prepare("UPDATE quotes SET status = :status, accepted_date = :date WHERE id = :id");
            $stmt->execute([':status' => $status, ':date' => $acceptedDate, ':id' => $id]);
        } else {
            $stmt = $this->pdo->prepare("UPDATE quotes SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $status, ':id' => $id]);
        }
    }

    public function setSalesOrder(int $id, int $soId): void
    {
        $stmt = $this->pdo->prepare("UPDATE quotes SET sales_order_id = :so_id, status = 'accepted' WHERE id = :id");
        $stmt->execute([':so_id' => $soId, ':id' => $id]);
    }

    public function replaceLineItems(int $quoteId, array $lines): void
    {
        $this->pdo->prepare("DELETE FROM quote_line_items WHERE quote_id = :id")->execute([':id' => $quoteId]);

        if (empty($lines)) return;

        $ins = $this->pdo->prepare("
            INSERT INTO quote_line_items
                (quote_id, product_id, quickbooks_item, description,
                 qty, uom_id, unit_price, discount_pct, is_taxable, line_total, sort_order)
            VALUES
                (:quote_id, :product_id, :quickbooks_item, :description,
                 :qty, :uom_id, :unit_price, :discount_pct, :is_taxable, :line_total, :sort_order)
        ");

        foreach ($lines as $i => $line) {
            $ins->execute([
                ':quote_id'       => $quoteId,
                ':product_id'     => $line['product_id']     ?: null,
                ':quickbooks_item'=> $line['quickbooks_item'] ?: null,
                ':description'    => $line['description']     ?: null,
                ':qty'            => $line['qty']             ?? 1,
                ':uom_id'         => $line['uom_id']          ?: null,
                ':unit_price'     => $line['unit_price']      ?? 0,
                ':discount_pct'   => $line['discount_pct']    ?? 0,
                ':is_taxable'     => (int)($line['is_taxable'] ?? 0),
                ':line_total'     => $line['line_total']      ?? 0,
                ':sort_order'     => $i + 1,
            ]);
        }
    }

    public function nextQuoteNumber(): string
    {
        $stmt = $this->pdo->query("SELECT MAX(CAST(SUBSTRING(quote_number, 3) AS UNSIGNED)) FROM quotes");
        $max  = (int)$stmt->fetchColumn();
        return 'Q-' . str_pad((string)($max + 1), 5, '0', STR_PAD_LEFT);
    }

    public function getSummaryStats(): array
    {
        $stmt = $this->pdo->query("
            SELECT
                COUNT(*) AS total_quotes,
                SUM(CASE WHEN status = 'sent'     THEN 1 ELSE 0 END) AS sent_count,
                SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) AS accepted_count,
                SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) AS declined_count,
                SUM(CASE WHEN status = 'expired'  THEN 1 ELSE 0 END) AS expired_count,
                SUM(CASE WHEN status IN ('draft','sent') THEN total_amount ELSE 0 END) AS pipeline_value,
                ROUND(
                    100.0 * SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END)
                    / NULLIF(SUM(CASE WHEN status IN ('accepted','declined') THEN 1 ELSE 0 END), 0)
                , 1) AS win_rate
            FROM quotes
        ");
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getByCustomer(int $customerId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT q.id, q.quote_number, q.quote_date, q.expiry_date, q.status, q.total_amount
            FROM quotes q
            WHERE q.customer_id = :cid
            ORDER BY q.quote_date DESC
            LIMIT 20
        ");
        $stmt->execute([':cid' => $customerId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
}
