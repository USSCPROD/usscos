<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Auth;
use App\Core\Session;
use App\Services\InventoryService;

class InventoryController extends Controller
{
    private InventoryService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new InventoryService();
    }

    public function index(Request $request, Response $response): Response
    {
        $user   = Auth::user();
        $search = trim($_GET['search'] ?? '');
        $brand  = trim($_GET['brand'] ?? '');
        $filter = $_GET['filter'] ?? 'all';
        $page   = max(1, (int)($_GET['page'] ?? 1));

        $paginator = $this->service->list($page, $search, $brand, $filter);
        $brands    = $this->service->getAllBrands();
        $stats     = $this->service->getStats();

        return $this->view('inventory.index', [
            'title'      => 'Inventory',
            'paginator'  => $paginator,
            'brands'     => $brands,
            'stats'      => $stats,
            'search'     => $search,
            'brand'      => $brand,
            'filter'     => $filter,
            'canAdjust'  => $this->service->canAdjust($user['role'] ?? ''),
        ]);
    }

    public function show(Request $request, Response $response, string $id = '0'): Response
    {
        $user   = Auth::user();
        $result = $this->service->show((int)$id);

        if (!$result) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        return $this->view('inventory.show', [
            'title'      => 'Inventory — ' . $result['product']['sku'],
            'product'    => $result['product'],
            'transactions' => $result['transactions'],
            'canAdjust'  => $this->service->canAdjust($user['role'] ?? ''),
        ]);
    }

    public function adjust(Request $request, Response $response, string $id = '0'): Response
    {
        $user = Auth::user();

        if (!$user || !$this->service->canAdjust($user['role'] ?? '')) {
            Session::flash('error', 'You do not have permission to adjust inventory.');
            return $response->redirect('/inventory/' . (int)$id);
        }

        $result = $this->service->adjust((int)$id, $_POST, (int)$user['id']);

        if ($result !== true) {
            Session::flash('error', $result);
        } else {
            Session::flash('success', 'Inventory updated.');
        }

        return $response->redirect('/inventory/' . (int)$id);
    }
}
