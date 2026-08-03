<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Service;
use App\Repositories\TaskRepository;

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

    private function getKpis(): array
    {
        // These queries will be live once full modules exist.
        // Returning zeros for now so the shell renders without data.
        return [
            'cash_balance'    => 0,
            'sales_mtd'       => 0,
            'gross_profit'    => 0,
            'gross_margin'    => 0,
            'open_invoices'   => 0,
            'open_invoices_count' => 0,
            'inventory_value' => 0,
            'inventory_items' => 0,
            'open_pos'        => 0,
            'open_pos_count'  => 0,
        ];
    }

    private function getRecentActivity(): array
    {
        return [];
    }

    private function getFocusItems(): array
    {
        return [];
    }

    private function getRevenueChart(): array
    {
        return [
            'labels'    => [],
            'this_month' => [],
            'last_month' => [],
        ];
    }

    private function getTopProducts(): array
    {
        return [];
    }
}
