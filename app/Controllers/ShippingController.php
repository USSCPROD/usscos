<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Core\Session;
use App\Repositories\SalesOrderRepository;
use App\Services\CartonizerService;
use App\Services\FedExShippingService;

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
                'message' => 'Not recognized: ' . $code,
            ]);
        }

        $product = $match['product'];
        $line    = $this->matchLine($lines, $product);

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

    /**
     * The packing screen — the second scan, as the box is filled.
     *
     * Deliberately separate from picking rather than a mode on the same page. It is a
     * second look at work already done, and a second look that shares a screen with the
     * first one stops being independent.
     */
    public function pack(Request $request, Response $response, string $id = '0'): Response
    {
        $order = $this->repo->findWithDetails((int)$id);

        if (!$order) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        return $this->view('shipping.pack', [
            'title' => 'Pack SO #' . $order['so_number'],
            'order' => $order,
            'lines' => $this->repo->getPickLines((int)$id),
        ]);
    }

    /** The warehouse has dealt with a change — stop shouting about it. */
    public function ackChange(Request $request, Response $response, string $id = '0'): Response
    {
        $this->repo->acknowledgePickedChange((int)$id);
        Session::flash('success', 'Noted — the warning is cleared.');

        return $response->redirect('/shipping/pull');
    }

    /**
     * The shipping clerk's release queue.
     *
     * Hers, not the warehouse's. The guys pull whatever they can reach and she decides
     * what actually goes out — full, partial, or wait for the rest — which is why this
     * runs alongside the pull list rather than after it.
     */
    public function release(Request $request, Response $response): Response
    {
        return $this->view('shipping.release', [
            'title'  => 'Ready to ship',
            'orders' => $this->repo->releaseQueue(),
        ]);
    }

    /**
     * Release an order: invoice what has been picked, and leave the rest owed.
     *
     * shipAndInvoice already bills qty_picked minus qty_shipped, so a partial is not a
     * special case here — invoicing a half-picked order produces an invoice for that
     * half and leaves the remainder on the order. What this adds is the clerk's
     * intent: she is saying "send what we have", and the order has to show afterwards
     * that something is still owed rather than looking finished.
     */
    public function releaseOrder(Request $request, Response $response, string $id = '0'): Response
    {
        $soId   = (int)$id;
        $userId = (int)(\App\Core\Auth::user()['id'] ?? 0);

        $outstanding = Database::selectOne("
            SELECT SUM(li.qty_ordered - COALESCE(li.qty_picked,0)) AS remaining,
                   SUM(COALESCE(li.qty_picked,0) - COALESCE(li.qty_shipped,0)) AS to_ship
            FROM sales_order_line_items li WHERE li.sales_order_id = ?
        ", [$soId]);

        if ((float)($outstanding['to_ship'] ?? 0) <= 0) {
            Session::flash('error', 'Nothing picked that has not already shipped — there is nothing to invoice.');
            return $response->redirect('/shipping/release');
        }

        try {
            $invoiceId = (new \App\Services\InvoiceService())->shipAndInvoice($soId, $userId, $_POST);
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
            return $response->redirect('/shipping/release');
        }

        // Still owed? Say so on the order itself. An order that reads 'shipped' while
        // paint is still owed is how a backorder gets forgotten, and the customer finds
        // out before anybody here does.
        $remaining = (float)($outstanding['remaining'] ?? 0);

        if ($remaining > 0) {
            Database::statement(
                "UPDATE sales_orders SET status='partially_shipped' WHERE id=?", [$soId]
            );
        }

        $inv = Database::selectOne("SELECT invoice_number FROM invoices WHERE id=?", [$invoiceId]);

        Session::flash('success', sprintf(
            'Invoice %s created.%s Create the label next.',
            $inv['invoice_number'] ?? ('#' . $invoiceId),
            $remaining > 0
                ? sprintf(' %s still owed on this order — it stays open.',
                    rtrim(rtrim(number_format($remaining, 2), '0'), '.'))
                : ''
        ));

        return $response->redirect('/shipping/' . $soId . '/label');
    }

    /**
     * Hold an order back, with the reason attached.
     *
     * A hold with no reason is indistinguishable from an order nobody got to, and the
     * difference matters to whoever picks the queue up tomorrow.
     */
    public function holdOrder(Request $request, Response $response, string $id = '0'): Response
    {
        $reason = trim((string)($_POST['reason'] ?? ''));

        if ($reason === '') {
            Session::flash('error', 'Say why it is being held — "on hold" with no reason tells the next person nothing.');
            return $response->redirect('/shipping/release');
        }

        Database::statement(
            "UPDATE sales_orders SET pick_note = ? WHERE id = ?",
            ['HOLD: ' . $reason, (int)$id]
        );

        Session::flash('success', 'Held — the reason is on the order.');

        return $response->redirect('/shipping/release');
    }

    /**
     * The warehouse pull list — everything owed, totalled by product.
     *
     * Deliberately not a per-order screen. The warehouse and the shipping clerk work at
     * the same time rather than in sequence: the guys keep pulling against open orders
     * while she approves whichever ones look ready, so neither is ever waiting on the
     * other to finish a batch.
     */
    public function pull(Request $request, Response $response): Response
    {
        $list = $this->repo->pullList();

        return $this->view('shipping.pull', [
            'title'   => 'Pull list',
            'changes' => $this->repo->pickedChanges(),
            'list'    => $list,
            'short'  => array_values(array_filter($list, fn($p) => $p['qty_on_hand'] < $p['total_needed'])),
        ]);
    }

    /**
     * Record what came off the shelf, from the pull list.
     *
     * Adds to what is already picked rather than replacing it, because the warehouse
     * works this list in passes - a second trip for the rest of the DSW should not
     * silently undo the first.
     */
    public function pullPick(Request $request, Response $response): Response
    {
        $lineId = (int)($_POST['line_id'] ?? 0);
        $qty    = (float)($_POST['qty'] ?? 0);

        if ($lineId <= 0 || $qty <= 0) {
            Session::flash('error', 'Nothing recorded — enter how many came off the shelf.');
            return $response->redirect('/shipping/pull');
        }

        $line = Database::selectOne(
            "SELECT li.id, li.sales_order_id, li.qty_ordered, COALESCE(li.qty_picked,0) AS qty_picked
             FROM sales_order_line_items li WHERE li.id = ?",
            [$lineId]
        );

        if ($line === false) {
            Session::flash('error', 'That order line no longer exists.');
            return $response->redirect('/shipping/pull');
        }

        // Never more than was ordered. Over-picking here would quietly promise stock to
        // an order that cannot use it, while the next order on the list goes short.
        $newQty = min((float)$line['qty_ordered'], (float)$line['qty_picked'] + $qty);

        Database::statement("UPDATE sales_order_line_items SET qty_picked = ? WHERE id = ?",
            [$newQty, $lineId]);

        $this->repo->refreshPickStatus((int)$line['sales_order_id']);

        Session::flash('success', sprintf('Picked %s — SO #%s now has %s of %s.',
            rtrim(rtrim(number_format($qty, 2), '0'), '.'),
            Database::selectOne("SELECT so_number FROM sales_orders WHERE id=?", [(int)$line['sales_order_id']])['so_number'] ?? '?',
            rtrim(rtrim(number_format($newQty, 2), '0'), '.'),
            rtrim(rtrim(number_format((float)$line['qty_ordered'], 2), '0'), '.')));

        return $response->redirect('/shipping/pull');
    }

    /**
     * The label screen, after the pack has been verified.
     *
     * Shows what the cartonizer worked out - which boxes, how many, what they weigh -
     * and lets the packer correct it before anything is printed. It is a suggestion,
     * not an instruction: the person at the bench can see the actual parcel and the
     * software cannot.
     */
    public function label(Request $request, Response $response, string $id = '0'): Response
    {
        $order = $this->repo->findWithDetails((int)$id);

        if (!$order) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        if (\App\Repositories\FulfillmentRepository::orderIsManaged((int)$id)) {
            return $response->redirect('/shipping/fulfillments/order/' . (int)$id);
        }

        $fedex = new FedExShippingService();
        $lines = $this->repo->getPickLines((int)$id);

        // The cartonizer wants a plain `qty` — how many of this product are going in
        // boxes — and getPickLines returns qty_ordered, qty_picked, qty_packed and
        // qty_shipped, none of which is called that. It was silently skipping every
        // line and returning no packages at all, which read as missing product data
        // rather than as a mismatch between two function signatures.
        //
        // WHICH QUANTITY DEPENDS ON WHETHER IT HAS BEEN INVOICED, and getting this wrong
        // is why SO 11 showed an empty packing plan. Releasing an order marks its lines
        // shipped, so picked-minus-shipped drops to zero the moment the invoice exists -
        // and the label is made AFTER the invoice. Reading it that way meant the screen
        // had nothing to pack at exactly the point somebody wanted a label.
        //
        // Once invoiced, the parcel is whatever the invoice bills. Before that, it is
        // what has been picked and not yet sent. Either way the label and the invoice
        // describe the same goods.
        $invoice = Database::selectOne(
            "SELECT id, invoice_number, ship_address_1, ship_city, ship_state, ship_zip
             FROM invoices WHERE sales_order_id = ? ORDER BY id DESC LIMIT 1",
            [(int)$id]
        );

        $forPacking = $invoice === false
            ? array_map(
                fn(array $l) => $l + ['qty' => max(0.0, (float)($l['qty_picked'] ?? 0) - (float)($l['qty_shipped'] ?? 0))],
                $lines
              )
            : Database::select(
                "SELECT li.product_id, li.qty AS qty, p.sku
                 FROM invoice_line_items li
                 LEFT JOIN products p ON p.id = li.product_id
                 WHERE li.invoice_id = ? AND li.product_id IS NOT NULL",
                [(int)$invoice['id']]
              );

        $plan = (new CartonizerService())->pack($forPacking);

        return $this->view('shipping.label', [
            'title'      => 'Label SO #' . $order['so_number'],
            'order'      => $order,
            'lines'      => $lines,
            'plan'       => $plan,
            'invoice'    => $invoice === false ? null : $invoice,
            'configured' => $fedex->configured(),
            'sandbox'    => $fedex->isSandbox(),
            'services'   => FedExShippingService::SERVICES,
            'existing'   => $invoice === false ? [] : Database::select(
                "SELECT * FROM shipment_tracking WHERE invoice_id = ? ORDER BY id",
                [(int)$invoice['id']]
            ),
        ]);
    }

    /**
     * Ask FedEx for the label.
     *
     * Everything that decides what gets printed comes from the form rather than from the
     * cartonizer, because the packer has already been shown the suggestion and either
     * accepted it or corrected it. Silently using the computed figure after someone typed
     * a different one would make the correction look like it worked when it did not.
     */
    public function createLabel(Request $request, Response $response, string $id = '0'): Response
    {
        if (\App\Repositories\FulfillmentRepository::orderIsManaged((int)$id)) {
            Session::flash('error','Use the fulfillment record. The legacy label action is invoice-based.');
            return $response->redirect('/shipping/fulfillments/order/' . (int)$id);
        }
        $invoiceId = (int)($_POST['invoice_id'] ?? 0);

        if ($invoiceId <= 0) {
            Session::flash('error', 'No invoice to label — ship and invoice the order first.');
            return $response->redirect('/shipping/' . (int)$id . '/label');
        }

        // Boxes typed on the bench, one row each. Rows with no weight are dropped rather
        // than sent as zero — an empty row is somebody adding a box and changing their
        // mind, not a parcel weighing nothing.
        $boxes = [];

        foreach ((array)($_POST['box'] ?? []) as $row) {
            $w = (float)($row['weight'] ?? 0);

            if ($w <= 0) {
                continue;
            }

            $boxes[] = [
                'weight' => $w,
                'length' => (float)($row['length'] ?? 0),
                'width'  => (float)($row['width'] ?? 0),
                'height' => (float)($row['height'] ?? 0),
            ];
        }

        $result = (new FedExShippingService())->createLabel($invoiceId, [
            'weight'   => (float)($_POST['weight'] ?? 0),
            'packages' => (int)($_POST['packages'] ?? 1),
            'service'  => (string)($_POST['service'] ?? 'FEDEX_GROUND'),
            'boxes'    => $boxes,
        ]);

        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);

        return $response->redirect('/shipping/' . (int)$id . '/label');
    }

    /**
     * One scan at the packing bench.
     *
     * Checked against what was picked, not what was ordered: a deliberate short pick is
     * not a packing error, and flagging it as one would teach people to ignore the warning
     * that matters.
     */
    public function packScan(Request $request, Response $response, string $id = '0'): Response
    {
        $order = $this->repo->findWithDetails((int)$id);

        if (!$order) {
            return $response->json(['ok' => false, 'message' => 'Order not found.'], 404);
        }

        $code  = trim((string)$request->post('code', ''));
        $match = $this->repo->resolveScan($code);
        $lines = $this->repo->getPickLines((int)$id);

        if ($match === null) {
            return $response->json([
                'ok'      => false,
                'status'  => 'unknown',
                'message' => 'Not recognized: ' . $code,
            ]);
        }

        $product = $match['product'];
        $line    = $this->matchLine($lines, $product);

        if ($line === null) {
            // The error this whole screen exists to catch: something in the box that was
            // never on the order at all.
            return $response->json([
                'ok'      => false,
                'status'  => 'not_on_order',
                'message' => $product['name'] . ' is not on this order — take it out of the box.',
            ]);
        }

        $step = $match['is_case'] && (int)$product['units_per_case'] > 0
            ? (float)$product['units_per_case']
            : 1.0;

        $picked = (float)$line['qty_picked'];
        $packed = (float)$line['qty_packed'] + $step;
        $over   = $packed > $picked;

        $this->repo->setPacked((int)$line['id'], $packed, $line['pack_note']);
        $status = $this->repo->refreshPackStatus((int)$id, \App\Core\Auth::id());

        $n = fn(float $v) => rtrim(rtrim(number_format($v, 2), '0'), '.');

        return $response->json([
            'ok'          => !$over,
            'status'      => $over ? 'over' : 'ok',
            'line_id'     => (int)$line['id'],
            'product'     => $product['name'],
            'qty_packed'  => $packed,
            'qty_picked'  => $picked,
            'pack_status' => $status,
            'message'     => $over
                ? 'More in the box than was picked — ' . $n($packed) . ' packed, ' . $n($picked) . ' picked'
                : $product['name'],
        ]);
    }

    /** Correct a packed line by hand, for a label that will not scan. */
    public function packSetLine(Request $request, Response $response, string $id = '0'): Response
    {
        $this->repo->setPacked(
            (int)$request->post('line_id', 0),
            (float)$request->post('qty', 0),
            trim((string)$request->post('note', '')) ?: null
        );
        $this->repo->refreshPackStatus((int)$id, \App\Core\Auth::id());

        \App\Core\Session::flash('success', 'Line updated.');

        return $response->redirect('/shipping/' . (int)$id . '/pack');
    }

    /**
     * Confirm the whole order matches the pick, without scanning it.
     *
     * Scanning every case does not survive contact with pallet volume, and a pre-made
     * double box carries no barcode to scan in the first place. Refusing to offer this
     * would not produce more scanning — it would produce people typing quantities into
     * the per-line boxes until the screen let them past, which is the same act with
     * worse evidence and no record of what happened.
     *
     * Recorded as 'confirmed', never 'verified'. A person saying it is right is a real
     * check and worth having, but it is not a machine reading a barcode, and the two
     * must stay distinguishable afterwards.
     */
    public function packConfirmAll(Request $request, Response $response, string $id = '0'): Response
    {
        $orderId = (int)$id;
        $order   = $this->repo->findWithDetails($orderId);

        if (!$order) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        $lines  = $this->repo->getPickLines($orderId);
        $picked = 0.0;

        foreach ($lines as $l) {
            $picked += (float)$l['qty_picked'];
        }

        if ($picked <= 0) {
            \App\Core\Session::flash('error', 'Nothing has been picked on this order yet.');

            return $response->redirect('/shipping/' . $orderId . '/pack');
        }

        $this->repo->confirmPackedAsPicked($orderId, \App\Core\Auth::id());

        \App\Core\Session::flash('success', 'Confirmed as packed — recorded without a scan.');

        return $response->redirect('/shipping');
    }

    /**
     * Seal a box that does not match the pick.
     *
     * Allowed, because refusing would just move the problem off the system — but it needs
     * a reason, and it is recorded as a mismatch rather than as verified.
     */
    public function packMismatch(Request $request, Response $response, string $id = '0'): Response
    {
        $note = trim((string)$request->post('pack_note', ''));

        if ($note === '') {
            \App\Core\Session::flash('error', 'Say why the box does not match the pick.');

            return $response->redirect('/shipping/' . (int)$id . '/pack');
        }

        $this->repo->recordPackMismatch((int)$id, $note, \App\Core\Auth::id());
        \App\Core\Session::flash('success', 'Recorded as a mismatch, with your note.');

        return $response->redirect('/shipping');
    }

    /**
     * Find a product on the order.
     *
     * By product_id first, then by the item text — imported lines often carry only
     * `quickbooks_item`, and an unlinked line would otherwise be impossible to scan.
     * Shared by picking and packing so the two cannot disagree about what is on an order.
     *
     * @param list<array<string,mixed>> $lines
     */
    private function matchLine(array $lines, array $product): ?array
    {
        foreach ($lines as $l) {
            if ($l['product_id'] !== null && (int)$l['product_id'] === (int)$product['id']) {
                return $l;
            }
        }

        $sku = strtoupper(trim((string)$product['sku']));

        if ($sku === '') {
            return null;
        }

        foreach ($lines as $l) {
            if ($l['product_id'] !== null) {
                continue;
            }

            $item = strtoupper(trim((string)($l['quickbooks_item'] ?? '')));

            // QuickBooks writes PARENT:CHILD, so compare the trailing segment too.
            $tail = str_contains($item, ':') ? substr($item, strrpos($item, ':') + 1) : $item;

            if ($item === $sku || $tail === $sku) {
                return $l;
            }
        }

        return null;
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
