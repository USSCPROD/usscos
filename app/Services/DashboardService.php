<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Service;
use App\Repositories\TaskRepository;

/**
 * The front page.
 *
 * Every figure on it used to be a hardcoded zero, which is worse than an empty screen: a
 * page that confidently reports nothing teaches people not to look at it.
 *
 * Two rules now govern what appears here.
 *
 * ONLY WHAT USSCOS ACTUALLY KNOWS. Cash balance lives in QuickBooks, gross profit needs
 * product costs nobody has entered, and inventory value waits on the accountant deciding
 * who owns valuation. Showing a plausible number for any of those would be inventing one.
 * They are left out rather than shown as zero.
 *
 * THE QUEUES ARE THE POINT. Receiving variances, stock below zero, invoices waiting on the
 * bookkeeper, credits owed, shelves nobody has counted — every one of those is a signal the
 * system now produces, and a signal nobody sees is the same as no signal. This is where
 * they surface.
 */
class DashboardService extends Service
{
    public function load(): array
    {
        $userId   = (int)(Auth::user()['id'] ?? 0);
        $taskRepo = new TaskRepository();

        return [
            'kpis'            => $this->getKpis(),
            'recent_activity' => $this->getRecentActivity(),
            'focus_items'     => $this->getFocusItems(),
            'revenue_chart'   => $this->getRevenueChart(),
            'top_products'    => $this->getTopProducts(),
            'my_tasks'        => $userId ? $taskRepo->getForUser($userId) : [],
            'my_task_stats'   => $userId ? $taskRepo->getStats($userId)   : [],
        ];
    }

    /**
     * The headline figures.
     *
     * Sales and A/R come from invoices and are true. The warehouse figures are counts of
     * work rather than money, because money in the warehouse means valuation and that is
     * still an open question with the accountant.
     */
    private function getKpis(): array
    {
        $mtd = Database::selectOne("
            SELECT COALESCE(SUM(total_amount), 0) AS total, COUNT(*) AS n
            FROM invoices
            WHERE status <> 'void' AND invoice_type = 'invoice'
              AND invoice_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
        ");

        // Same span of days last month, so a comparison on the 3rd is not against a full
        // month and instantly alarming.
        $lastMonth = Database::selectOne("
            SELECT COALESCE(SUM(total_amount), 0) AS total
            FROM invoices
            WHERE status <> 'void' AND invoice_type = 'invoice'
              AND invoice_date >= DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01')
              AND invoice_date <= CURDATE() - INTERVAL 1 MONTH
        ");

        $ytd = Database::selectOne("
            SELECT COALESCE(SUM(total_amount), 0) AS total
            FROM invoices
            WHERE status <> 'void' AND invoice_type = 'invoice'
              AND invoice_date >= DATE_FORMAT(CURDATE(), '%Y-01-01')
        ");

        $ar = Database::selectOne("
            SELECT COALESCE(SUM(balance_due), 0) AS total, COUNT(*) AS n
            FROM invoices
            WHERE status NOT IN ('void', 'paid') AND balance_due > 0
        ");

        $overdue = Database::selectOne("
            SELECT COALESCE(SUM(balance_due), 0) AS total, COUNT(*) AS n
            FROM invoices
            WHERE status NOT IN ('void', 'paid') AND balance_due > 0 AND due_date < CURDATE()
        ");

        $orders = Database::selectOne("
            SELECT COUNT(*) AS n
            FROM sales_orders
            WHERE status IN ('confirmed', 'processing', 'partially_shipped', 'paid')
        ");

        $pos = Database::selectOne("
            SELECT COALESCE(SUM(total_amount), 0) AS total, COUNT(*) AS n
            FROM purchase_orders WHERE status IN ('sent', 'partial')
        ");

        $stock = Database::selectOne("
            SELECT COUNT(DISTINCT product_id) AS products, COALESCE(SUM(qty_on_hand), 0) AS units
            FROM product_stock WHERE qty_on_hand <> 0
        ");

        $mtdTotal  = (float)($mtd['total'] ?? 0);
        $lastTotal = (float)($lastMonth['total'] ?? 0);

        return [
            'sales_mtd'           => $mtdTotal,
            'sales_mtd_count'     => (int)($mtd['n'] ?? 0),
            'sales_trend'         => $lastTotal > 0 ? round((($mtdTotal - $lastTotal) / $lastTotal) * 100, 1) : 0.0,
            'sales_ytd'           => (float)($ytd['total'] ?? 0),
            'open_invoices'       => (float)($ar['total'] ?? 0),
            'open_invoices_count' => (int)($ar['n'] ?? 0),
            'overdue'             => (float)($overdue['total'] ?? 0),
            'overdue_count'       => (int)($overdue['n'] ?? 0),
            'open_orders'         => (int)($orders['n'] ?? 0),
            'open_pos'            => (float)($pos['total'] ?? 0),
            'open_pos_count'      => (int)($pos['n'] ?? 0),
            'stock_products'      => (int)($stock['products'] ?? 0),
            'stock_units'         => (float)($stock['units'] ?? 0),
        ];
    }

    /**
     * The work that is actually waiting on somebody.
     *
     * Ordered by how much it costs to ignore, not by how recent it is. A negative stock
     * figure and an unreviewed invoice are both quiet — neither shouts — which is exactly
     * why they belong on the front page.
     */
    private function getFocusItems(): array
    {
        $items = [];
        $n     = fn($v) => rtrim(rtrim(number_format((float)$v, 2), '0'), '.');

        $add = function (array &$items, int $count, string $title, string $subtitle, string $url, string $colour) {
            if ($count > 0) {
                $items[] = ['title' => $title, 'subtitle' => $subtitle, 'url' => $url, 'color' => $colour];
            }
        };

        // Shipped but not yet billed correctly — the customer is waiting to be told anything.
        $review = Database::selectOne(
            "SELECT COUNT(*) AS n FROM invoices WHERE review_status = 'pending' AND status <> 'void'"
        );
        $add($items, (int)$review['n'],
            (int)$review['n'] . ' invoice' . ((int)$review['n'] === 1 ? '' : 's') . ' awaiting review',
            'Shipped. The customer has not been told anything yet.',
            '/invoices/review', 'amber');

        // Deliveries that disagreed with their PO.
        $variance = Database::selectOne("
            SELECT COUNT(*) AS n FROM purchase_order_lines pol
            JOIN purchase_orders po ON po.id = pol.po_id
            WHERE pol.qty_received > 0 AND ABS(pol.qty_received - pol.qty_ordered) > 0.0001
              AND pol.variance_ack_at IS NULL AND po.status <> 'cancelled'
        ");
        $add($items, (int)$variance['n'],
            (int)$variance['n'] . ' receipt' . ((int)$variance['n'] === 1 ? '' : 's') . ' did not match the PO',
            'Either the PO follows the delivery, or somebody explains the difference.',
            '/purchasing/variances', 'red');

        // Stock below zero — allowed on purpose, but each one is asking to be counted.
        $negative = Database::selectOne(
            "SELECT COUNT(*) AS n, COALESCE(SUM(qty_on_hand), 0) AS units FROM product_stock WHERE qty_on_hand < 0"
        );
        $add($items, (int)$negative['n'],
            (int)$negative['n'] . ' item' . ((int)$negative['n'] === 1 ? '' : 's') . ' showing below zero',
            'More went out than the count knew about. Each one needs counting.',
            '/inventory/adjustments', 'red');

        // Orders picked and waiting to be checked before they are sealed.
        $toPack = Database::selectOne("
            SELECT COUNT(*) AS n FROM sales_orders
            WHERE pick_status = 'ready' AND pack_status IN ('not_started', 'in_progress')
              AND status IN ('confirmed', 'processing', 'paid')
        ");
        $add($items, (int)$toPack['n'],
            (int)$toPack['n'] . ' order' . ((int)$toPack['n'] === 1 ? '' : 's') . ' picked, not yet verified',
            'Packed and checked against the pick before the box is sealed.',
            '/shipping', 'blue');

        // Orders the shipping floor has handed back.
        $short = Database::selectOne(
            "SELECT COUNT(*) AS n FROM sales_orders WHERE pick_status = 'short' AND status NOT IN ('invoiced', 'cancelled')"
        );
        $add($items, (int)$short['n'],
            (int)$short['n'] . ' order' . ((int)$short['n'] === 1 ? '' : 's') . ' short on stock',
            'Shipping could not complete these. They are waiting on whoever owns them.',
            '/shipping', 'amber');

        // Money owed back to customers.
        $credits = Database::selectOne("
            SELECT COUNT(*) AS n, COALESCE(SUM(credit_amount), 0) AS total
            FROM stock_returns
            WHERE credit_status = 'pending' AND status IN ('received', 'closed') AND credit_amount > 0
        ");
        $add($items, (int)$credits['n'],
            '$' . number_format((float)$credits['total'], 2) . ' of credits owed to customers',
            (int)$credits['n'] . ' return' . ((int)$credits['n'] === 1 ? '' : 's') . ' booked in but not yet credited.',
            '/inventory/returns', 'amber');

        // Transfers that left one building and never arrived at the other.
        $transit = Database::selectOne(
            "SELECT COUNT(*) AS n FROM stock_transfers WHERE status = 'in_transit' AND sent_at < NOW() - INTERVAL 2 DAY"
        );
        $add($items, (int)$transit['n'],
            (int)$transit['n'] . ' transfer' . ((int)$transit['n'] === 1 ? '' : 's') . ' still on a truck',
            'Sent more than two days ago and not received at the other end.',
            '/inventory/transfers', 'red');

        // Counts left part-finished.
        $counts = Database::selectOne("SELECT COUNT(*) AS n FROM stock_counts WHERE status IN ('counting', 'review')");
        $add($items, (int)$counts['n'],
            (int)$counts['n'] . ' stock count' . ((int)$counts['n'] === 1 ? '' : 's') . ' unfinished',
            'Counted but not yet applied, so nothing has been corrected.',
            '/inventory/counts', 'blue');

        // Tax rates nobody has checked.
        $rates = Database::selectOne("SELECT COUNT(*) AS n FROM tax_rates WHERE needs_review = 1 AND is_active = 1");
        $add($items, (int)$rates['n'],
            (int)$rates['n'] . ' tax rate' . ((int)$rates['n'] === 1 ? '' : 's') . ' unverified',
            'Being charged to customers without anybody having confirmed them.',
            '/accounting/tax', 'amber');

        $overdue = Database::selectOne("
            SELECT COUNT(*) AS n, COALESCE(SUM(balance_due), 0) AS total
            FROM invoices WHERE status NOT IN ('void', 'paid') AND balance_due > 0 AND due_date < CURDATE()
        ");
        $add($items, (int)$overdue['n'],
            '$' . number_format((float)$overdue['total'], 2) . ' overdue',
            (int)$overdue['n'] . ' invoice' . ((int)$overdue['n'] === 1 ? '' : 's') . ' past their due date.',
            '/accounting/ar-aging', 'red');

        return $items;
    }

    /**
     * What has happened lately, across the whole system.
     *
     * Deliberately not just invoices. A stock movement, a return and a transfer are all
     * things somebody would want to notice, and separate lists nobody opens is how they go
     * unnoticed.
     */
    private function getRecentActivity(): array
    {
        $rows = Database::select("
            (SELECT 'invoice' AS type,
                    CONCAT('Invoice ', i.invoice_number, ' — ', c.company_name) AS description,
                    i.invoice_number AS reference, i.created_at AS at,
                    TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) AS user
             FROM invoices i
             JOIN customers c ON c.id = i.customer_id
             LEFT JOIN users u ON u.id = i.created_by
             WHERE i.status <> 'void'
             ORDER BY i.id DESC LIMIT 8)
            UNION ALL
            (SELECT 'order', CONCAT('Order ', so.so_number, ' — ', c.company_name),
                    so.so_number, so.created_at,
                    TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')))
             FROM sales_orders so
             JOIN customers c ON c.id = so.customer_id
             LEFT JOIN users u ON u.id = so.created_by
             ORDER BY so.id DESC LIMIT 8)
            UNION ALL
            (SELECT 'inventory',
                    CONCAT(
                        CASE t.transaction_type
                            WHEN 'receipt'   THEN 'Received '
                            WHEN 'sale'      THEN 'Shipped '
                            WHEN 'transfer'  THEN 'Transferred '
                            WHEN 'return_in' THEN 'Returned '
                            ELSE 'Adjusted '
                        END,
                        TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM ABS(t.qty))), ' ', p.name),
                    COALESCE(t.reference_num, ''), t.created_at,
                    TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')))
             FROM inventory_transactions t
             JOIN products p ON p.id = t.product_id
             LEFT JOIN users u ON u.id = t.created_by
             ORDER BY t.id DESC LIMIT 8)
            ORDER BY at DESC
            LIMIT 12
        ");

        foreach ($rows as &$r) {
            $r['time'] = $this->ago((string)$r['at']);
            $r['user'] = trim((string)$r['user']) ?: 'System';
        }

        return $rows;
    }

    /** Invoiced totals for this month and last, by day, for the overview chart. */
    private function getRevenueChart(): array
    {
        $days = (int)date('t');

        $fetch = static function (string $monthExpr): array {
            $rows = Database::select("
                SELECT DAY(invoice_date) AS d, COALESCE(SUM(total_amount), 0) AS total
                FROM invoices
                WHERE status <> 'void' AND invoice_type = 'invoice'
                  AND DATE_FORMAT(invoice_date, '%Y-%m') = {$monthExpr}
                GROUP BY DAY(invoice_date)
            ");

            $byDay = [];
            foreach ($rows as $r) {
                $byDay[(int)$r['d']] = (float)$r['total'];
            }

            return $byDay;
        };

        $thisMonth = $fetch("DATE_FORMAT(CURDATE(), '%Y-%m')");
        $lastMonth = $fetch("DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m')");

        $labels = $a = $b = [];

        for ($d = 1; $d <= $days; $d++) {
            $labels[] = (string)$d;
            $a[]      = round($thisMonth[$d] ?? 0, 2);
            $b[]      = round($lastMonth[$d] ?? 0, 2);
        }

        return ['labels' => $labels, 'this_month' => $a, 'last_month' => $b];
    }

    /** What has actually sold this year, by revenue. */
    private function getTopProducts(): array
    {
        return Database::select("
            SELECT COALESCE(p.name, li.description) AS name,
                   SUM(li.qty)        AS sold,
                   SUM(li.line_total) AS revenue
            FROM invoice_line_items li
            JOIN invoices i ON i.id = li.invoice_id
            LEFT JOIN products p ON p.id = li.product_id
            WHERE i.status <> 'void' AND i.invoice_type = 'invoice'
              AND i.invoice_date >= DATE_FORMAT(CURDATE(), '%Y-01-01')
            GROUP BY COALESCE(p.name, li.description)
            ORDER BY revenue DESC
            LIMIT 6
        ");
    }

    private function ago(string $timestamp): string
    {
        $seconds = time() - strtotime($timestamp);

        return match (true) {
            $seconds < 60      => 'just now',
            $seconds < 3600    => floor($seconds / 60) . 'm ago',
            $seconds < 86400   => floor($seconds / 3600) . 'h ago',
            $seconds < 604800  => floor($seconds / 86400) . 'd ago',
            default            => date('j M', strtotime($timestamp)),
        };
    }
}
