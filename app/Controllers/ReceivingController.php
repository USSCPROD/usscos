<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\ReceivingService;
use App\Repositories\PurchaseOrderRepository;
use App\Repositories\StockRepository;
use App\Services\PurchaseReceivingService;

/**
 * Booking goods in — the screen where stock first enters USSCOS.
 *
 * Built for the same handheld as picking: the scan box holds focus, the answer comes back
 * without a page reload, and the running list shows what has been booked in so far so the
 * receiver can see their own work.
 */
class ReceivingController extends Controller
{
    private ReceivingService $receiving;
    private PurchaseOrderRepository $orders;
    private PurchaseReceivingService $poReceiving;

    public function __construct()
    {
        parent::__construct();
        $this->receiving   = new ReceivingService();
        $this->orders      = new PurchaseOrderRepository();
        $this->poReceiving = new PurchaseReceivingService();
    }

    /**
     * Booking a delivery in against its purchase order.
     *
     * List-first, not scan-first. Whoever is unloading is holding the PO, so the fastest
     * thing is the PO's own lines with a quantity box against each — the paperwork has
     * already identified the products. Scanning is an accelerator here, not the way in,
     * which also means it works with no scanner and with the 744 products that have no
     * barcode.
     */
    public function purchaseOrder(Request $request, Response $response, string $id = '0'): Response
    {
        $po = $this->orders->findById((int)$id);

        if ($po === false) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        return $this->view('receiving.purchase_order', [
            'title'     => 'Receive ' . $po['po_number'],
            'po'        => $po,
            'lines'     => $this->orders->getLines((int)$id),
            'locations' => $this->receiving->locations(),
        ]);
    }

    public function storePurchaseOrder(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $result = $this->poReceiving->receive(
                (int)$id,
                $_POST['receive_qty'] ?? [],
                (int)($_POST['location_id'] ?? 0),
                (int)(\App\Core\Auth::id() ?? 0),
                trim((string)($_POST['receipt_notes'] ?? ''))
            );
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());

            return $response->redirect('/receiving/po/' . (int)$id);
        }

        if ($result['received'] === 0) {
            Session::flash('error', 'Nothing entered — put a quantity against at least one line.');

            return $response->redirect('/receiving/po/' . (int)$id);
        }

        if ($result['variances'] > 0) {
            Session::flash('error', sprintf(
                'Booked in. %d line%s came in at a different quantity than the PO says — review it under Purchasing > Variances.',
                $result['variances'],
                $result['variances'] === 1 ? '' : 's'
            ));
        } else {
            Session::flash('success', 'Booked in and put into stock.');
        }

        return $response->redirect('/receiving');
    }

    /**
     * Find which line of this PO a scanned or typed code belongs to.
     *
     * The accelerator: it jumps the cursor to that line rather than booking anything in,
     * so a scan still ends with a person confirming the quantity.
     */
    public function purchaseOrderLookup(Request $request, Response $response, string $id = '0'): Response
    {
        $match = (new StockRepository())->resolveScan((string)$request->post('code', ''));

        if ($match === null) {
            return $response->json([
                'found'   => false,
                'message' => 'Not recognized: ' . $request->post('code', ''),
            ]);
        }

        $product = $match['product'];

        foreach ($this->orders->getLines((int)$id) as $line) {
            if ((int)$line['product_id'] === (int)$product['id']) {
                $outstanding = (float)$line['qty_ordered'] - (float)$line['qty_received'];

                return $response->json([
                    'found'       => true,
                    'line_id'     => (int)$line['id'],
                    'product'     => $product['name'],
                    'outstanding' => $outstanding,
                    // A case barcode means a case, so offer that rather than one unit.
                    'suggested'   => $match['is_case'] && (int)$product['units_per_case'] > 0
                        ? (float)$product['units_per_case']
                        : max(0.0, $outstanding),
                ]);
            }
        }

        return $response->json([
            'found'   => false,
            'message' => $product['name'] . ' is not on this purchase order.',
        ]);
    }

    public function index(Request $request, Response $response): Response
    {
        return $this->view('receiving.index', [
            'title'     => 'Receiving',
            'locations'  => $this->receiving->locations(),
            'recent'     => $this->receiving->recentReceipts(),
            'openOrders' => $this->orders->awaitingDelivery(),
        ]);
    }

    /**
     * Resolve a scan and suggest a quantity, without recording anything.
     *
     * Separate from the receive step on purpose: the receiver sees what the system thinks
     * arrived and confirms or corrects it before any stock moves.
     */
    public function lookup(Request $request, Response $response): Response
    {
        $code = (string)$request->post('code', '');
        $unit = (string)$request->post('unit', 'unit');

        return $response->json($this->receiving->lookup($code, $unit));
    }

    public function store(Request $request, Response $response): Response
    {
        try {
            $result = $this->receiving->receive($_POST);
            Session::flash('success', sprintf(
                'Booked in %s.',
                rtrim(rtrim(number_format($result['qty'], 2), '0'), '.')
            ));
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return $response->redirect('/receiving');
    }
}
