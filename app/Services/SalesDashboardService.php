<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

/**
 * The signed-in salesperson's own numbers. Never the company's.
 *
 * Every query here is filtered to the rep tied to this login. There is no "all" branch
 * to fall into by accident: without a rep record the methods return empty, and the page
 * says so in words rather than showing zeroes that read like a quiet month.
 *
 * That distinction matters. A rep who sees "$0 pipeline" assumes they have no deals; a
 * rep who sees "your login isn't linked to a rep record yet" asks somebody to fix it.
 * Only 1 of 33 rep records currently carries a user_id, so this is the common case for
 * now, not an edge one.
 */
class SalesDashboardService
{
    /** The sales_reps.id for this login, or null when there is no link. */
    public function repId(): ?int
    {
        $userId = Auth::id();

        if ($userId === null) {
            return null;
        }

        $row = Database::selectOne(
            "SELECT id FROM sales_reps WHERE user_id = ? AND is_active = 1 LIMIT 1",
            [$userId]
        );

        return $row ? (int)$row['id'] : null;
    }

    /**
     * My sales this month and last, and how many accounts are mine.
     *
     * @return array<string,mixed>
     */
    public function kpis(?int $repId): array
    {
        if ($repId === null) {
            return ['linked' => false];
        }

        $mtd = Database::selectOne("
            SELECT COALESCE(SUM(i.total_amount),0) total, COUNT(*) n
            FROM invoices i JOIN customers c ON c.id = i.customer_id
            WHERE c.sales_rep_id = ? AND i.status <> 'void' AND i.invoice_type = 'invoice'
              AND i.invoice_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
        ", [$repId]);

        $last = Database::selectOne("
            SELECT COALESCE(SUM(i.total_amount),0) total
            FROM invoices i JOIN customers c ON c.id = i.customer_id
            WHERE c.sales_rep_id = ? AND i.status <> 'void' AND i.invoice_type = 'invoice'
              AND i.invoice_date >= DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01')
              AND i.invoice_date <  DATE_FORMAT(CURDATE(), '%Y-%m-01')
        ", [$repId]);

        $accounts = Database::selectOne(
            "SELECT COUNT(*) n FROM customers WHERE sales_rep_id = ?", [$repId]
        );

        $lastTotal = (float)($last['total'] ?? 0);
        $mtdTotal  = (float)($mtd['total'] ?? 0);

        return [
            'linked'       => true,
            'sales_mtd'    => $mtdTotal,
            'sales_count'  => (int)($mtd['n'] ?? 0),
            'sales_last'   => $lastTotal,
            'sales_trend'  => $lastTotal > 0 ? round((($mtdTotal - $lastTotal) / $lastTotal) * 100, 1) : 0.0,
            'accounts'     => (int)($accounts['n'] ?? 0),
        ];
    }

    /** My open opportunities, grouped by stage. */
    public function pipeline(?int $repId): array
    {
        if ($repId === null) {
            return [];
        }

        // Open means not yet closed either way. There is no status column - the stage
        // IS the status, and closed_won/closed_lost are stages like any other.
        return Database::select("
            SELECT stage, COUNT(*) n, COALESCE(SUM(expected_value),0) total
            FROM opportunities
            WHERE rep_id = ? AND stage NOT IN ('closed_won','closed_lost')
            GROUP BY stage
            ORDER BY FIELD(stage,'prospecting','proposal','negotiation')
        ", [$repId]);
    }

    /** My leads that nobody has touched recently, oldest neglect first. */
    public function leads(?int $repId, int $limit = 8): array
    {
        if ($repId === null) {
            return [];
        }

        return Database::select("
            SELECT id, company_name,
                   TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))) contact_name,
                   status, created_at,
                   DATEDIFF(CURDATE(), DATE(created_at)) age_days
            FROM leads
            WHERE rep_id = ? AND status NOT IN ('converted','dead')
            ORDER BY created_at
            LIMIT {$limit}
        ", [$repId]);
    }

    /** My quotes still waiting on the customer. */
    public function quotes(?int $repId, int $limit = 8): array
    {
        if ($repId === null) {
            return [];
        }

        // Matched on the quote's OWN rep_id, not the customer's. A quote can be raised
        // by one person for another's account, and the person who wrote it is the one
        // waiting on the answer.
        return Database::select("
            SELECT q.id, q.quote_number, q.total_amount, q.status, q.quote_date, q.expiry_date,
                   DATEDIFF(q.expiry_date, CURDATE()) days_left, c.company_name
            FROM quotes q
            LEFT JOIN customers c ON c.id = q.customer_id
            WHERE q.rep_id = ? AND q.status IN ('draft','sent')
            ORDER BY q.expiry_date IS NULL, q.expiry_date, q.quote_date DESC
            LIMIT {$limit}
        ", [$repId]);
    }

    /**
     * Orders on my accounts that have not finished yet.
     *
     * A rep submits an order and then cannot see a single screen it passes through —
     * not the clerk's queue, not the pull list, not the bench. Without this the order
     * leaves their hands and there is nowhere in the application that says it exists,
     * which is exactly what somebody checks the dashboard for.
     *
     * Where it is, in words rather than a status code: the words are what a rep wants
     * to read out to a customer who is asking.
     *
     * @return array<int,array<string,mixed>>
     */
    public function orders(?int $repId, int $limit = 8): array
    {
        if ($repId === null) {
            return [];
        }

        return Database::select("
            SELECT s.id, s.so_number, s.status, s.total_amount, s.order_date, s.ship_by,
                   s.approved_at, s.pick_status, s.pack_status,
                   c.company_name,
                   (SELECT COUNT(*) FROM shipment_tracking t
                     JOIN invoices i ON i.id = t.invoice_id
                     WHERE i.sales_order_id = s.id) AS labelled
            FROM sales_orders s
            JOIN customers c ON c.id = s.customer_id
            WHERE c.sales_rep_id = ?
              AND s.status NOT IN ('cancelled')
              AND (s.status <> 'invoiced' OR NOT EXISTS (
                    SELECT 1 FROM shipment_tracking t2
                    JOIN invoices i2 ON i2.id = t2.invoice_id
                    WHERE i2.sales_order_id = s.id))
            ORDER BY FIELD(s.status,'draft') DESC, s.order_date DESC, s.id DESC
            LIMIT {$limit}
        ", [$repId]);
    }

    /**
     * Where an order has got to, said plainly.
     *
     * The rep has no way to look, so the answer has to be complete enough to repeat to
     * a customer on the phone without checking anything else.
     */
    public static function orderStage(array $o): array
    {
        $status = (string)($o['status'] ?? '');

        if ($status === 'draft') {
            return ['Not sent yet', '#b45309'];
        }

        if ((int)($o['labelled'] ?? 0) > 0) {
            return ['Shipped', '#166534'];
        }

        if ($status === 'invoiced' || $status === 'partially_shipped') {
            return ['Invoiced, waiting on a label', '#0A3D91'];
        }

        if (empty($o['approved_at'])) {
            return ['With the shipping clerk', '#0A3D91'];
        }

        return match ((string)($o['pack_status'] ?? '')) {
            'verified', 'confirmed', 'mismatch' => ['Packed, ready to go', '#0A3D91'],
            default => match ((string)($o['pick_status'] ?? '')) {
                'ready'       => ['Picked, being packed', '#0A3D91'],
                'in_progress' => ['Being picked', '#0A3D91'],
                'short'       => ['Short — not all of it is in stock', '#b45309'],
                default       => ['With the warehouse', '#0A3D91'],
            },
        };
    }

    /**
     * Overdue invoices on MY accounts.
     *
     * Bounded by the A/R opening date like every other receivables figure, so it cannot
     * contradict the finance screens by counting invoices from before the cutover.
     */
    public function overdue(?int $repId, int $limit = 8): array
    {
        if ($repId === null) {
            return [];
        }

        // clause() returns its own leading AND, and an empty string when no A/R opening
        // date is set. Adding another AND in front produces 'AND  AND' and a syntax
        // error the moment the cutover is unset — which is exactly how it ships.
        $period = new ArPeriod();
        [$clause, $params] = $period->clause('i');

        return Database::select("
            SELECT i.id, i.invoice_number, i.total_amount, i.balance_due, i.due_date,
                   DATEDIFF(CURDATE(), i.due_date) days_over, c.company_name
            FROM invoices i JOIN customers c ON c.id = i.customer_id
            WHERE c.sales_rep_id = ? {$clause}
              AND i.status <> 'void' AND COALESCE(i.balance_due,0) > 0
              AND i.due_date < CURDATE()
            ORDER BY i.due_date
            LIMIT {$limit}
        ", array_merge([$repId], $params));
    }

    /**
     * My accounts that have gone quiet.
     *
     * The most actionable thing on a sales dashboard: a customer who used to order and
     * has stopped is a problem while it is still fixable, and nothing else on the screen
     * would ever surface them.
     */
    public function quiet(?int $repId, int $days = 90, int $limit = 8): array
    {
        if ($repId === null) {
            return [];
        }

        return Database::select("
            SELECT c.id, c.company_name, MAX(i.invoice_date) last_order,
                   DATEDIFF(CURDATE(), MAX(i.invoice_date)) days_quiet
            FROM customers c
            JOIN invoices i ON i.customer_id = c.id AND i.status <> 'void' AND i.invoice_type = 'invoice'
            WHERE c.sales_rep_id = ?
            GROUP BY c.id
            HAVING days_quiet > {$days}
            ORDER BY days_quiet DESC
            LIMIT {$limit}
        ", [$repId]);
    }
}
