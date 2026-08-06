<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Reporting and chart-of-accounts queries for the Accounting section.
 *
 * Note on scope: AR aging here is computed directly from `invoices`, not from the
 * general ledger. That is deliberate — aging is useful today and needs no posted
 * journal entries, so it works whether or not USSCOS becomes the ledger of record.
 */
class AccountingRepository
{
    // ------------------------------------------------------- Chart of accounts

    public function accounts(string $search = '', string $type = ''): array
    {
        $where  = ['1=1'];
        $params = [];

        if ($search !== '') {
            $where[]  = '(name LIKE ? OR account_code LIKE ? OR account_subtype LIKE ?)';
            $s = '%' . $search . '%';
            array_push($params, $s, $s, $s);
        }
        if ($type !== '') {
            $where[]  = 'account_type = ?';
            $params[] = $type;
        }

        return Database::select(
            'SELECT * FROM chart_of_accounts WHERE ' . implode(' AND ', $where) .
            ' ORDER BY account_code IS NULL, account_code, name',
            $params
        );
    }

    /** Accounts grouped by type, in balance-sheet-then-P&L order. */
    public function accountsGrouped(string $search = ''): array
    {
        $order = [
            'bank'                    => 'Assets',
            'accounts_receivable'     => 'Assets',
            'other_current_asset'     => 'Assets',
            'fixed_asset'             => 'Assets',
            'other_asset'             => 'Assets',
            'accounts_payable'        => 'Liabilities',
            'credit_card'             => 'Liabilities',
            'other_current_liability' => 'Liabilities',
            'long_term_liability'     => 'Liabilities',
            'equity'                  => 'Equity',
            'income'                  => 'Revenue',
            'other_income'            => 'Revenue',
            'cost_of_goods_sold'      => 'Cost of Goods Sold',
            'expense'                 => 'Operating Expenses',
            'other_expense'           => 'Operating Expenses',
        ];

        $grouped = [];
        foreach ($this->accounts($search) as $a) {
            $section = $order[$a['account_type']] ?? 'Other';
            $grouped[$section][] = $a;
        }

        // Preserve the intended section order rather than insertion order
        $sections = ['Assets', 'Liabilities', 'Equity', 'Revenue', 'Cost of Goods Sold', 'Operating Expenses', 'Other'];
        $out = [];
        foreach ($sections as $s) {
            if (!empty($grouped[$s])) $out[$s] = $grouped[$s];
        }
        return $out;
    }

    public function accountTypes(): array
    {
        return array_map(
            fn($r) => $r['account_type'],
            Database::select('SELECT DISTINCT account_type FROM chart_of_accounts ORDER BY account_type')
        );
    }

    // --------------------------------------------------------------- AR aging

    /**
     * Receivables bucketed by how overdue they are, per customer.
     *
     * Buckets are measured from the due date, not the invoice date — an invoice on
     * Net 30 isn't "30 days old", it's current until day 31.
     */
    public function arAging(): array
    {
        return Database::select("
            SELECT c.id            AS customer_id,
                   c.company_name,
                   c.rep_id,
                   COUNT(*)        AS invoice_count,
                   SUM(i.balance_due) AS total_due,
                   SUM(CASE WHEN i.due_date >= CURDATE() THEN i.balance_due ELSE 0 END)                                    AS current_due,
                   SUM(CASE WHEN i.due_date <  CURDATE() AND DATEDIFF(CURDATE(), i.due_date) BETWEEN 1  AND 30  THEN i.balance_due ELSE 0 END) AS d1_30,
                   SUM(CASE WHEN DATEDIFF(CURDATE(), i.due_date) BETWEEN 31 AND 60  THEN i.balance_due ELSE 0 END) AS d31_60,
                   SUM(CASE WHEN DATEDIFF(CURDATE(), i.due_date) BETWEEN 61 AND 90  THEN i.balance_due ELSE 0 END) AS d61_90,
                   SUM(CASE WHEN DATEDIFF(CURDATE(), i.due_date) > 90 THEN i.balance_due ELSE 0 END)               AS d90_plus,
                   MAX(DATEDIFF(CURDATE(), i.due_date)) AS worst_days
            FROM invoices i
            JOIN customers c ON c.id = i.customer_id
            WHERE i.balance_due > 0
              AND i.status NOT IN ('void','paid')
            GROUP BY c.id, c.company_name, c.rep_id
            ORDER BY d90_plus DESC, d61_90 DESC, total_due DESC
        ");
    }

    /** Column totals for the aging report. */
    public function arAgingTotals(): array
    {
        return Database::selectOne("
            SELECT COUNT(*) AS invoice_count,
                   COUNT(DISTINCT i.customer_id) AS customer_count,
                   COALESCE(SUM(i.balance_due),0) AS total_due,
                   COALESCE(SUM(CASE WHEN i.due_date >= CURDATE() THEN i.balance_due ELSE 0 END),0) AS current_due,
                   COALESCE(SUM(CASE WHEN i.due_date < CURDATE() AND DATEDIFF(CURDATE(), i.due_date) BETWEEN 1 AND 30 THEN i.balance_due ELSE 0 END),0) AS d1_30,
                   COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), i.due_date) BETWEEN 31 AND 60 THEN i.balance_due ELSE 0 END),0) AS d31_60,
                   COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), i.due_date) BETWEEN 61 AND 90 THEN i.balance_due ELSE 0 END),0) AS d61_90,
                   COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), i.due_date) > 90 THEN i.balance_due ELSE 0 END),0) AS d90_plus
            FROM invoices i
            WHERE i.balance_due > 0 AND i.status NOT IN ('void','paid')
        ") ?: [];
    }

    /** Individual open invoices for one customer, oldest first. */
    public function openInvoicesFor(int $customerId): array
    {
        return Database::select("
            SELECT id, invoice_number, invoice_date, due_date, total_amount, amount_paid, balance_due,
                   DATEDIFF(CURDATE(), due_date) AS days_overdue
            FROM invoices
            WHERE customer_id = ? AND balance_due > 0 AND status NOT IN ('void','paid')
            ORDER BY due_date ASC
        ", [$customerId]);
    }

    // ------------------------------------------------------ Section dashboard

    public function summary(): array
    {
        $ar = $this->arAgingTotals();

        $ap = Database::selectOne("
            SELECT COUNT(*) AS bill_count,
                   COALESCE(SUM(balance_due),0) AS total_due,
                   COALESCE(SUM(CASE WHEN due_date < CURDATE() THEN balance_due ELSE 0 END),0) AS overdue
            FROM bills WHERE balance_due > 0 AND status != 'paid'
        ") ?: [];

        $revenue = Database::selectOne("
            SELECT COALESCE(SUM(total_amount),0) AS mtd
            FROM invoices
            WHERE status != 'void'
              AND invoice_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
        ") ?: [];

        $ytd = Database::selectOne("
            SELECT COALESCE(SUM(total_amount),0) AS ytd
            FROM invoices
            WHERE status != 'void'
              AND invoice_date >= DATE_FORMAT(CURDATE(), '%Y-01-01')
        ") ?: [];

        return [
            'ar_total'     => (float)($ar['total_due']  ?? 0),
            'ar_overdue'   => (float)(($ar['d1_30'] ?? 0) + ($ar['d31_60'] ?? 0) + ($ar['d61_90'] ?? 0) + ($ar['d90_plus'] ?? 0)),
            'ar_customers' => (int)($ar['customer_count'] ?? 0),
            'ap_total'     => (float)($ap['total_due'] ?? 0),
            'ap_overdue'   => (float)($ap['overdue']   ?? 0),
            'ap_count'     => (int)($ap['bill_count']  ?? 0),
            'revenue_mtd'  => (float)($revenue['mtd'] ?? 0),
            'revenue_ytd'  => (float)($ytd['ytd']     ?? 0),
            'coa_count'    => (int)(Database::selectOne('SELECT COUNT(*) c FROM chart_of_accounts WHERE is_active = 1')['c'] ?? 0),
            'je_count'     => (int)(Database::selectOne('SELECT COUNT(*) c FROM journal_entries')['c'] ?? 0),
            'rep_count'    => (int)(Database::selectOne("SELECT COUNT(*) c FROM sales_reps WHERE rep_type = 'person' AND is_active = 1")['c'] ?? 0),
        ];
    }

    /**
     * Revenue and customer count per sales rep.
     *
     * Only `rep_type = 'person'` counts as a rep — the list also holds the owner,
     * employees, and placeholders like "No sales rep" which carries the majority of
     * invoices and would otherwise swamp every figure.
     *
     * Revenue is summed from invoices attributed to the rep, NOT from the lifetime
     * revenue of their assigned customers — those differ substantially, because a
     * customer's invoices may be credited to several reps or to none.
     */
    /**
     * Revenue per rep over an inclusive date range.
     *
     * The range is applied in the JOIN, not the WHERE, so a rep with assigned customers
     * but no invoices in the period still appears (with zero revenue) rather than
     * dropping off the report.
     */
    public function repPerformance(?string $from = null, ?string $to = null): array
    {
        $params    = [];
        $yearWhere = '';

        if ($from !== null) {
            $yearWhere .= ' AND i.invoice_date >= ?';
            $params[]   = $from;
        }
        if ($to !== null) {
            $yearWhere .= ' AND i.invoice_date <= ?';
            $params[]   = $to;
        }

        return Database::select("
            SELECT sr.id, sr.name, sr.rep_type,
                   COUNT(DISTINCT i.id)                    AS invoice_count,
                   COALESCE(SUM(i.total_amount), 0)        AS revenue,
                   COUNT(DISTINCT i.customer_id)           AS customers_invoiced,
                   MAX(i.invoice_date)                     AS last_sale,
                   (SELECT COUNT(*) FROM customers c WHERE c.sales_rep_id = sr.id) AS assigned_customers
            FROM sales_reps sr
            LEFT JOIN invoices i
                   ON i.sales_rep_id = sr.id
                  AND i.status != 'void'
                  {$yearWhere}
            WHERE sr.rep_type = 'person'
            GROUP BY sr.id, sr.name, sr.rep_type
            HAVING invoice_count > 0 OR assigned_customers > 0
            ORDER BY revenue DESC, assigned_customers DESC
        ", $params);
    }

    /** Revenue that isn't credited to a real rep, so the picture stays honest. */
    /** Revenue not credited to a rep, over the same inclusive date range. */
    public function unattributedRevenue(?string $from = null, ?string $to = null): array
    {
        $params    = [];
        $yearWhere = '';

        if ($from !== null) {
            $yearWhere .= ' AND i.invoice_date >= ?';
            $params[]   = $from;
        }
        if ($to !== null) {
            $yearWhere .= ' AND i.invoice_date <= ?';
            $params[]   = $to;
        }

        return Database::select("
            SELECT COALESCE(sr.name, 'Not attributed') AS label,
                   COALESCE(sr.rep_type, 'unset')      AS rep_type,
                   COUNT(*)                            AS invoice_count,
                   COALESCE(SUM(i.total_amount), 0)    AS revenue
            FROM invoices i
            LEFT JOIN sales_reps sr ON sr.id = i.sales_rep_id
            WHERE i.status != 'void'
              AND (sr.id IS NULL OR sr.rep_type != 'person')
              {$yearWhere}
            GROUP BY label, rep_type
            ORDER BY revenue DESC
        ", $params);
    }

    /** Years that actually have invoices, for the year selector. */
    public function invoiceYears(): array
    {
        return array_map(
            fn($r) => (int)$r['y'],
            Database::select("
                SELECT DISTINCT YEAR(invoice_date) AS y
                FROM invoices WHERE status != 'void'
                ORDER BY y DESC
            ")
        );
    }

    /** All reps, for filter dropdowns. */
    public function repOptions(): array
    {
        return Database::select("
            SELECT id, name, rep_type,
                   (SELECT COUNT(*) FROM customers c WHERE c.sales_rep_id = sales_reps.id) AS customer_count
            FROM sales_reps
            WHERE rep_type = 'person' AND is_active = 1
            ORDER BY name
        ");
    }

    /** Revenue by month for the section dashboard. */
    public function revenueByMonth(int $months = 12): array
    {
        $months = max(1, min(36, $months));
        return Database::select("
            SELECT DATE_FORMAT(invoice_date, '%Y-%m') AS period,
                   SUM(total_amount) AS revenue,
                   COUNT(*)          AS invoices
            FROM invoices
            WHERE status != 'void'
              AND invoice_date >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL {$months} MONTH)
            GROUP BY period ORDER BY period ASC
        ");
    }
}
