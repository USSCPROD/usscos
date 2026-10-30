<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\FulfillmentRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\SalesOrderRepository;
use App\Services\FulfillmentPolicy;
use App\Services\FulfillmentService;
use App\Services\InvoiceService;
use App\Services\StockService;

class FulfillmentController extends Controller
{
    private function allowed(bool $billing = false): bool
    {
        return FulfillmentPolicy::enabled() && in_array(
            Auth::user()['role'] ?? '',
            $billing ? ['owner','bookkeeper'] : ['owner','admin','bookkeeper','manager','shipping','employee'],
            true
        );
    }

    private function service(FulfillmentRepository $repo): FulfillmentService
    {
        return new FulfillmentService(
            $repo,
            fn ($so, $term, $lines, $userId) => (new InvoiceService())->createFulfillmentInvoice($so, $term, $lines, $userId),
            fn ($lines, $meta) => (new StockService())->shipLines($lines, $meta),
            function ($so, $receipt, $userId): int {
                $payments = new PaymentRepository();
                $id = $payments->insert([
                    ':customer_id' => (int)$so['customer_id'],':sales_order_id' => (int)$so['id'],
                    ':payment_date' => substr($receipt['collected_at'], 0, 10),':payment_method' => 'check',
                    ':reference_number' => $receipt['check_number'],':amount' => $receipt['amount'],
                    ':memo' => 'Driver check handed to accounting. Receipt does not confirm bank clearance.',':created_by' => $userId,
                ]);
                $payments->insertApplication($id, (int)$receipt['invoice_id'], (float)$receipt['amount']);
                $payments->recalcInvoice((int)$receipt['invoice_id']);
                return $id;
            }
        );
    }

    public function index(Request $request, Response $response): Response
    {
        if (!$this->allowed()) {
            return $response->html('Not available for this account.', 403);
        }
        $repo = new FulfillmentRepository();
        $method = (string)$request->query('method', '');
        if (!isset(FulfillmentPolicy::METHODS[$method])) {
            $method = '';
        }
        return $this->view('shipping.fulfillments', [
            'title' => 'Shipping & Fulfillment','rows' => $repo->queue($method),'method' => $method,
            'checks' => $this->allowed(true) ? $repo->checks() : [],'order' => null,
            'billing' => $this->allowed(true),
        ]);
    }

    public function order(Request $request, Response $response, string $id): Response
    {
        if (!$this->allowed()) {
            return $response->html('Not available for this account.', 403);
        }
        if (!(new SalesOrderRepository())->findWithDetails((int)$id)) {
            return $response->html('Order not found.', 404);
        }
        $repo = new FulfillmentRepository();
        $order = $repo->order((int)$id);
        $rows = $repo->all((int)$id);
        foreach ($rows as &$row) {
            $row['items'] = $repo->items((int)$row['id']);
            $row['check'] = $repo->checkReceipt((int)$row['id']);
        }
        unset($row);
        return $this->view('shipping.fulfillments', [
            'title' => 'Fulfillment for SO '.$order['so_number'],'order' => $order,'rows' => $rows,
            'lines' => $repo->lines((int)$id),'checks' => [],'method' => '','billing' => $this->allowed(true),
        ]);
    }

    public function save(Request $request, Response $response, string $id): Response
    {
        if (!$this->allowed()) {
            return $response->html('Not available for this account.', 403);
        }
        if (!(new SalesOrderRepository())->findWithDetails((int)$id)) {
            return $response->html('Order not found.', 404);
        }
        $action = (string)$request->post('action', '');
        if (in_array($action, ['invoice','post_check'], true) && !$this->allowed(true)) {
            return $response->html('An owner or bookkeeper must perform this action.', 403);
        }
        $service = $this->service(new FulfillmentRepository());
        $fulfillmentId = (int)$request->post('fulfillment_id', 0);
        try {
            switch ($action) {
                case 'plan': $service->plan((int)$id, $_POST, (int)Auth::id());
                    break;
                case 'invoice':
                    $invoiceId = $service->invoice((int)$id, $fulfillmentId ?: null, trim((string)$request->post('exception', '')), (int)Auth::id());
                    return $response->redirect('/invoices/'.$invoiceId);
                case 'receive_check':
                    $service->receiveCheck((int)$id, $fulfillmentId, (string)$request->post('check_number', ''), $request->post('amount', ''), (int)Auth::id());
                    break;
                case 'post_check': $service->handCheckToAccounting((int)$id, $fulfillmentId, (int)Auth::id());
                    break;
                default: $service->act((int)$id, $fulfillmentId, $action, $_POST, (int)Auth::id());
            }
            Session::flash('success', 'Fulfillment updated. No customer email was sent.');
        } catch (\DomainException $e) {
            Session::flash('error',$e->getMessage());
        }
        return $response->redirect('/shipping/fulfillments/order/'.(int)$id);
    }
}
