<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\SalesOrderRepository;

class ShippingController extends Controller
{
    private SalesOrderRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new SalesOrderRepository();
    }

    public function index(Request $request, Response $response): Response
    {
        $orders    = $this->repo->getShippingQueue();
        $shipVias  = $this->repo->getShipViaOptions();

        // Group orders by ship_via_id (null = unassigned)
        $grouped = [];
        foreach ($orders as $o) {
            $key = $o['ship_via_id'] ?? 'none';
            $grouped[$key][] = $o;
        }

        return $this->view('shipping.index', [
            'title'     => 'Shipping Queue',
            'orders'    => $orders,
            'grouped'   => $grouped,
            'shipVias'  => $shipVias,
        ]);
    }
}
