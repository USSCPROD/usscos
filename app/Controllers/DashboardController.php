<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    private DashboardService $dashboardService;

    public function __construct()
    {
        parent::__construct();
        $this->dashboardService = new DashboardService();
    }

    public function index(Request $request, Response $response): Response
    {
        $data = $this->dashboardService->load();

        return $this->view('dashboard.index', [
            'title'     => 'Dashboard',
            'dashboard' => $data,
        ]);
    }
}
