<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\SalesOrderRepository;
use App\Services\SalesOrderService;

class SalesOrderController extends Controller
{
    private SalesOrderService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new SalesOrderService();
    }

    public function index(Request $request, Response $response): Response
    {
        $page    = max(1, (int)($request->query('page') ?? 1));
        $search  = trim($request->query('q') ?? '');
        $status  = $request->query('status') ?? 'open';
        $sort    = $request->query('sort') ?? 'date_desc';

        $data = $this->service->list($page, 50, $search, $status, $sort);

        return $this->view('sales_orders.index', [
            'title' => 'Sales Orders',
            ...$data,
        ]);
    }

    public function show(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $data = $this->service->show((int)$id);
        } catch (\RuntimeException) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        return $this->view('sales_orders.show', [
            'title' => 'Sales Order #' . $data['so']['so_number'],
            ...$data,
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        $data = $this->service->create();

        return $this->view('sales_orders.create', [
            'title' => 'New Sales Order',
            ...$data,
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $user = Auth::user();
        $soId = $this->service->store((int)$user['id'], $_POST);

        if (($_POST['_action'] ?? '') === 'save_new') {
            return $response->redirect('/sales-orders/create');
        }

        return $response->redirect('/sales-orders');
    }

    public function edit(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $data = $this->service->edit((int)$id);
        } catch (\RuntimeException) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        return $this->view('sales_orders.edit', [
            'title' => 'Edit SO #' . $data['so']['so_number'],
            ...$data,
        ]);
    }

    public function update(Request $request, Response $response, string $id = '0'): Response
    {
        $this->service->update((int)$id, $_POST);
        return $response->redirect('/sales-orders');
    }

    public function destroy(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $this->service->delete((int)$id);
        } catch (\RuntimeException) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        return $response->redirect('/sales-orders');
    }

    public function printView(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $data = $this->service->show((int)$id);
        } catch (\RuntimeException) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        $company    = Database::selectOne('SELECT * FROM companies WHERE id = 1');
        $so         = $data['so'];
        $line_items = $data['line_items'];

        ob_start();
        require BASE_PATH . '/app/Views/sales_orders/print.php';
        $html = ob_get_clean();

        return $response->html($html);
    }

    public function packingSlip(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $data = $this->service->show((int)$id);
        } catch (\RuntimeException) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        $company    = Database::selectOne('SELECT * FROM companies WHERE id = 1');
        $so         = $data['so'];
        $line_items = $data['line_items'];

        ob_start();
        require BASE_PATH . '/app/Views/sales_orders/packing_slip.php';
        $html = ob_get_clean();

        return $response->html($html);
    }

    public function email(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $data = $this->service->show((int)$id);
        } catch (\RuntimeException) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        $so        = $data['so'];
        $to        = trim($_POST['email_to']   ?? '');
        $fromEmail = trim($_POST['email_from'] ?? '');
        $note      = trim($_POST['email_note'] ?? '');

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Invalid recipient email address.');
            return $response->redirect('/sales-orders/' . (int)$id);
        }
        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Invalid from email address.');
            return $response->redirect('/sales-orders/' . (int)$id);
        }

        $user      = Auth::user();
        $company   = Database::selectOne('SELECT * FROM companies WHERE id = 1');
        $fromName  = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($company['name'] ?? 'US Specialty Coatings');
        $subject   = 'Sales Order #' . $so['so_number'] . ' from ' . $fromName;

        $body   = $this->buildEmailBody($so, $data['line_items'], $company, $note);
        $mailer = new Mailer();
        $sent   = $mailer->send($to, $subject, $body, $fromName, $fromEmail);

        if ($sent) {
            Session::flash('success', 'Sales order emailed to ' . $to . '.');
        } else {
            Session::flash('error', 'Email failed: ' . $mailer->getLastError());
        }

        return $response->redirect('/sales-orders/' . (int)$id);
    }

    public function ship(Request $request, Response $response, string $id = '0'): Response
    {
        $user = Auth::user();

        try {
            $invoiceService = new \App\Services\InvoiceService();
            $invoiceId = $invoiceService->shipAndInvoice((int)$id, (int)$user['id'], $_POST);
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
            return $response->redirect('/sales-orders/' . (int)$id);
        }

        Session::flash('success', 'Order shipped — invoice created.');
        return $response->redirect('/invoices/' . $invoiceId);
    }

    public function collectPayment(Request $request, Response $response, string $id = '0'): Response
    {
        $method    = trim($_POST['payment_method']    ?? '');
        $reference = trim($_POST['payment_reference'] ?? '');
        $amount    = (float)($_POST['payment_amount'] ?? 0);

        if ($method === '' || $amount <= 0) {
            Session::flash('error', 'Payment method and amount are required.');
            return $response->redirect('/sales-orders/' . (int)$id);
        }

        $repo = new SalesOrderRepository();
        $repo->collectPayment((int)$id, $method, $reference, $amount, (int)Auth::user()['id']);

        Session::flash('success', 'Payment collected. Order marked as paid.');
        return $response->redirect('/sales-orders/' . (int)$id);
    }

    private function buildEmailBody(array $so, array $lineItems, array|false $company, string $note): string
    {
        $fromName = $company['name'] ?? 'US Specialty Coatings';
        $lineRows = '';
        foreach ($lineItems as $li) {
            $lineRows .= '<tr>
                <td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;font-family:monospace;font-size:11px">' . htmlspecialchars($li['sku'] ?? '') . '</td>
                <td style="padding:8px 10px;border-bottom:1px solid #e5e7eb">' . htmlspecialchars($li['product_name'] ?? $li['description'] ?? '') . '</td>
                <td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;text-align:right">' . number_format((float)($li['qty_ordered'] ?? $li['qty'] ?? 0), 2) . '</td>
                <td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;text-align:right">$' . number_format((float)$li['unit_price'], 2) . '</td>
                <td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;text-align:right">$' . number_format((float)$li['line_total'], 2) . '</td>
            </tr>';
        }

        $noteHtml = $note
            ? '<p style="background:#fffbeb;border-left:4px solid #d97706;padding:12px 16px;margin:20px 0;font-size:14px">' . nl2br(htmlspecialchars($note)) . '</p>'
            : '';

        $shipDate = !empty($so['requested_ship_date']) ? date('M j, Y', strtotime($so['requested_ship_date'])) : '';

        return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;color:#1a1a1a;max-width:700px;margin:0 auto;padding:20px">
        <div style="background:#222b59;padding:20px 24px;border-radius:6px 6px 0 0">
            <h1 style="color:#fff;margin:0;font-size:20px">Sales Order</h1>
            <div style="color:#a5b4fc;font-size:14px;margin-top:4px">#' . htmlspecialchars($so['so_number']) . '</div>
        </div>
        <div style="border:1px solid #e5e7eb;border-top:none;padding:20px 24px;border-radius:0 0 6px 6px">
            ' . $noteHtml . '
            <table style="width:100%;border-collapse:collapse;margin-bottom:16px">
                <tr>
                    <td style="width:50%;vertical-align:top;padding-right:16px">
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#888;margin-bottom:6px">Customer</div>
                        <div style="font-size:14px"><strong>' . htmlspecialchars($so['company_name']) . '</strong></div>
                    </td>
                    <td style="width:50%;vertical-align:top;text-align:right">
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#888;margin-bottom:4px">Order Date</div>
                        <div style="font-size:14px">' . date('M j, Y', strtotime($so['order_date'])) . '</div>
                        ' . ($shipDate ? '<div style="font-size:12px;color:#555;margin-top:4px">Ship by: ' . $shipDate . '</div>' : '') . '
                        ' . (!empty($so['po_number']) ? '<div style="font-size:12px;color:#555;margin-top:4px">Customer PO: ' . htmlspecialchars($so['po_number']) . '</div>' : '') . '
                    </td>
                </tr>
            </table>
            <table style="width:100%;border-collapse:collapse;margin:20px 0">
                <thead>
                    <tr style="background:#222b59">
                        <th style="color:#fff;padding:8px 10px;text-align:left;font-size:12px">SKU</th>
                        <th style="color:#fff;padding:8px 10px;text-align:left;font-size:12px">Description</th>
                        <th style="color:#fff;padding:8px 10px;text-align:right;font-size:12px">Qty</th>
                        <th style="color:#fff;padding:8px 10px;text-align:right;font-size:12px">Unit Price</th>
                        <th style="color:#fff;padding:8px 10px;text-align:right;font-size:12px">Total</th>
                    </tr>
                </thead>
                <tbody>' . $lineRows . '</tbody>
            </table>
            <div style="text-align:right;border-top:2px solid #222b59;padding-top:12px">
                <div style="font-size:13px;color:#555">Subtotal: $' . number_format((float)$so['subtotal'], 2) . '</div>
                ' . ((float)($so['tax_amount'] ?? 0) > 0 ? '<div style="font-size:13px;color:#555">Tax: $' . number_format((float)$so['tax_amount'], 2) . '</div>' : '') . '
                ' . ((float)($so['shipping_amount'] ?? 0) > 0 ? '<div style="font-size:13px;color:#555">Shipping: $' . number_format((float)$so['shipping_amount'], 2) . '</div>' : '') . '
                <div style="font-size:18px;font-weight:700;color:#222b59;margin-top:6px">Order Total: $' . number_format((float)$so['total_amount'], 2) . '</div>
            </div>
            <p style="margin-top:24px;font-size:12px;color:#999;border-top:1px solid #e5e7eb;padding-top:12px">
                ' . htmlspecialchars($fromName) . ' &bull; ' . htmlspecialchars($company['email'] ?? '') . '
            </p>
        </div>
        </body></html>';
    }
}
