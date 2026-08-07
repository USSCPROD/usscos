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

    /** The picking screen for one order — built for a shared tablet at the bench. */
    public function pick(Request $request, Response $response, string $id = '0'): Response
    {
        $order = $this->repo->findWithDetails((int)$id);

        if (!$order) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        return $this->view('shipping.pick', [
            'title' => 'Pick SO #' . $order['so_number'],
            'order' => $order,
            'lines' => $this->repo->getPickLines((int)$id),
        ]);
    }

    /**
     * Handle one scan and answer immediately.
     *
     * Returns JSON so the tablet can show the result without a page reload — a picker
     * scanning through an order needs the wrong-item warning at the moment of the scan,
     * not after a round trip they might not read.
     */
    public function scan(Request $request, Response $response, string $id = '0'): Response
    {
        $order = $this->repo->findWithDetails((int)$id);

        if (!$order) {
            return $response->json(['ok' => false, 'message' => 'Order not found.'], 404);
        }

        $code    = trim((string)$request->post('code', ''));
        $match   = $this->repo->resolveScan($code);
        $lines   = $this->repo->getPickLines((int)$id);

        if ($match === null) {
            return $response->json([
                'ok'      => false,
                'status'  => 'unknown',
                'message' => 'Not recognised: ' . $code,
            ]);
        }

        $product = $match['product'];

        // Find this product on the order.
        $line = null;
        foreach ($lines as $l) {
            if ((int)$l['product_id'] === (int)$product['id']) {
                $line = $l;
                break;
            }
        }

        if ($line === null) {
            return $response->json([
                'ok'      => false,
                'status'  => 'not_on_order',
                'message' => $product['name'] . ' is not on this order.',
            ]);
        }

        // A case barcode means a whole case of units, not one unit.
        $step = $match['is_case'] && (int)$product['units_per_case'] > 0
            ? (float)$product['units_per_case']
            : 1.0;

        $ordered = (float)$line['qty_ordered'];
        $picked  = (float)$line['qty_picked'] + $step;
        $over    = $picked > $ordered;

        $this->repo->setPicked((int)$line['id'], $picked, $line['pick_note']);
        $status = $this->repo->refreshPickStatus((int)$id, \App\Core\Auth::id());

        return $response->json([
            'ok'          => !$over,
            'status'      => $over ? 'over' : 'ok',
            'line_id'     => (int)$line['id'],
            'product'     => $product['name'],
            'step'        => $step,
            'qty_picked'  => $picked,
            'qty_ordered' => $ordered,
            'pick_status' => $status,
            'message'     => $over
                ? 'Too many — ' . rtrim(rtrim(number_format($picked, 2), '0'), '.') . ' picked of ' . rtrim(rtrim(number_format($ordered, 2), '0'), '.')
                : $product['name'],
        ]);
    }

    /** Set a line by hand — damaged label, or correcting an over-scan. */
    public function setLine(Request $request, Response $response, string $id = '0'): Response
    {
        $lineId = (int)$request->post('line_id', 0);
        $qty    = (float)$request->post('qty', 0);
        $note   = trim((string)$request->post('note', '')) ?: null;

        $this->repo->setPicked($lineId, $qty, $note);
        $status = $this->repo->refreshPickStatus((int)$id, \App\Core\Auth::id());

        \App\Core\Session::flash('success', 'Line updated.');

        return $response->redirect('/shipping/' . (int)$id . '/pick');
    }

    /** "We don't have everything" — hand the order back to whoever owns it. */
    public function short(Request $request, Response $response, string $id = '0'): Response
    {
        $note = trim((string)$request->post('pick_note', ''));

        if ($note === '') {
            \App\Core\Session::flash('error', 'Say what is missing so someone can act on it.');
            return $response->redirect('/shipping/' . (int)$id . '/pick');
        }

        $this->repo->reportShort((int)$id, $note, \App\Core\Auth::id());
        \App\Core\Session::flash('success', 'Reported as short. The order has been flagged.');

        return $response->redirect('/shipping');
    }
}
