<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Mailer;
use App\Core\Session;
use App\Core\Request;
use App\Core\Response;
use App\Services\InvoiceService;

class InvoiceController extends Controller
{
    private InvoiceService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new InvoiceService();
    }

    public function create(Request $request, Response $response): Response
    {
        $fromSoId = (int)($request->query('from_so') ?? 0);
        $data     = $this->service->create($fromSoId > 0 ? $fromSoId : null);

        return $this->view('invoices.create', [
            'title' => 'New Invoice',
            ...$data,
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $user      = Auth::user();
        $invoiceId = $this->service->store((int)$user['id'], $_POST);

        if (($_POST['_action'] ?? '') === 'save_new') {
            return $response->redirect('/invoices/create');
        }

        return $response->redirect('/invoices/' . $invoiceId);
    }

    public function edit(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $data = $this->service->edit((int)$id);
        } catch (\RuntimeException) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        return $this->view('invoices.edit', [
            'title' => 'Edit Invoice #' . $data['invoice']['invoice_number'],
            ...$data,
        ]);
    }

    public function update(Request $request, Response $response, string $id = '0'): Response
    {
        $userRole = Auth::user()['role'] ?? 'user';
        try {
            $this->service->update((int)$id, $_POST, $userRole);
        } catch (\RuntimeException) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        Session::flash('success', 'Invoice saved.');
        return $response->redirect('/invoices/' . (int)$id);
    }

    public function index(Request $request, Response $response): Response
    {
        $page    = max(1, (int)($request->query('page') ?? 1));
        $perPage = 50;
        $search  = trim($request->query('q') ?? '');
        $status  = $request->query('status') ?? 'all';
        $sort    = $request->query('sort') ?? 'date_desc';

        $data = $this->service->list($page, $perPage, $search, $status, $sort);

        return $this->view('invoices.index', [
            'title' => 'Invoices',
            ...$data,
        ]);
    }

    public function show(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $data = $this->service->show((int)$id);
        } catch (\PDOException $e) {
            throw $e;                 // PDOException extends RuntimeException — let real DB errors surface
        } catch (\RuntimeException) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        return $this->view('invoices.show', [
            'title' => 'Invoice #' . $data['invoice']['invoice_number'],
            ...$data,
        ]);
    }

    /** Add a tracking number to an invoice after the fact — a second carton, or a correction. */
    public function addTracking(Request $request, Response $response, string $id = '0'): Response
    {
        $notify = new \App\Services\ShipmentNotificationService();
        $raw    = trim((string)$request->post('tracking_number', ''));

        if ($raw === '') {
            Session::flash('error', 'Enter a tracking or PRO number.');

            return $response->redirect('/invoices/' . (int)$id);
        }

        $viaId = ($request->post('ship_via_id', '') !== '') ? (int)$request->post('ship_via_id') : null;
        $added = $notify->addTracking((int)$id, $raw, $viaId, (string)$request->post('tracking_type', 'parcel'));

        Session::flash($added === [] ? 'error' : 'success', $added === []
            ? 'Nothing added — those numbers are already on this invoice.'
            : count($added) . ' tracking number' . (count($added) === 1 ? '' : 's') . ' added.');

        return $response->redirect('/invoices/' . (int)$id);
    }

    /** Send, or resend, the shipment email. */
    public function sendTracking(Request $request, Response $response, string $id = '0'): Response
    {
        $result = (new \App\Services\ShipmentNotificationService())->notify((int)$id, true);

        Session::flash($result['status'] === 'sent' ? 'success' : 'error', $result['message']);

        return $response->redirect('/invoices/' . (int)$id);
    }

    public function removeTracking(Request $request, Response $response, string $id = '0', string $trackingId = '0'): Response
    {
        (new \App\Services\ShipmentNotificationService())->remove((int)$trackingId);
        Session::flash('success', 'Tracking number removed.');

        return $response->redirect('/invoices/' . (int)$id);
    }

    public function packingSlip(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $data = $this->service->show((int)$id);
        } catch (\RuntimeException) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        $company  = Database::selectOne('SELECT * FROM companies WHERE id = 1');
        $invoice  = $data['invoice'];

        // Map invoice fields to the structure packing_slip.php expects
        $so = [
            'id'                => $invoice['id'],
            'so_number'         => 'INV-' . $invoice['invoice_number'],
            'company_name'      => $invoice['company_name'],
            'ship_to_name'      => $invoice['ship_to_name']      ?? $invoice['company_name'],
            'ship_to_address_1' => $invoice['ship_to_address_1'] ?? $invoice['bill_address_1'] ?? '',
            'ship_to_city'      => $invoice['ship_to_city']      ?? $invoice['bill_city']      ?? '',
            'ship_to_state'     => $invoice['ship_to_state']     ?? $invoice['bill_state']     ?? '',
            'ship_to_zip'       => $invoice['ship_to_zip']       ?? $invoice['bill_zip']       ?? '',
            'bill_address_1'    => $invoice['bill_address_1']    ?? '',
            'bill_city'         => $invoice['bill_city']         ?? '',
            'bill_state'        => $invoice['bill_state']        ?? '',
            'bill_zip'          => $invoice['bill_zip']          ?? '',
            'po_number'         => $invoice['po_number']         ?? '',
            'requested_ship_date' => $invoice['invoice_date']    ?? '',
            'ship_via_name'     => $invoice['ship_via_name']     ?? '',
            'ship_to_phone'     => $invoice['phone']             ?? '',
            'notes'             => $invoice['notes']             ?? '',
        ];

        // Normalise qty field name for the packing slip template
        $line_items = array_map(function ($li) {
            $li['qty_ordered'] = $li['qty_ordered'] ?? $li['qty'] ?? 0;
            return $li;
        }, $data['line_items']);

        ob_start();
        require BASE_PATH . '/app/Views/sales_orders/packing_slip.php';
        $html = ob_get_clean();

        return $response->html($html);
    }

    public function printView(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $data = $this->service->show((int)$id);
        } catch (\RuntimeException) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        $company    = Database::selectOne('SELECT * FROM companies WHERE id = 1');
        $invoice    = $data['invoice'];
        $line_items = $data['line_items'];
        $hasDiscount = !empty(array_filter($line_items, fn($li) => (float)($li['discount_pct'] ?? 0) > 0));

        ob_start();
        require BASE_PATH . '/app/Views/invoices/print.php';
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

        $invoice   = $data['invoice'];
        $to        = trim($_POST['email_to']   ?? '');
        $fromEmail = trim($_POST['email_from'] ?? '');
        $note      = trim($_POST['email_note'] ?? '');

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Invalid recipient email address.');
            return $response->redirect('/invoices/' . (int)$id);
        }
        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Invalid from email address.');
            return $response->redirect('/invoices/' . (int)$id);
        }

        $user      = Auth::user();
        $company   = Database::selectOne('SELECT * FROM companies WHERE id = 1');
        $fromName  = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($company['name'] ?? 'US Specialty Coatings');
        $isReceipt = (float)($invoice['balance_due'] ?? $invoice['total_amount']) <= 0;
        $subject   = ($isReceipt ? 'Receipt' : 'Invoice') . ' #' . $invoice['invoice_number'] . ' from ' . $fromName;

        $body   = $this->buildEmailBody($invoice, $data['line_items'], $company, $note);
        $mailer = new Mailer();
        $sent   = $mailer->send($to, $subject, $body, $fromName, $fromEmail);

        if ($sent) {
            // Auto-set status to pending when emailed from draft
            if ($invoice['status'] === 'draft') {
                $this->service->setStatus((int)$id, 'pending');
            }
            Session::flash('success', 'Invoice emailed to ' . $to . '.');
        } else {
            Session::flash('error', 'Email failed: ' . $mailer->getLastError());
        }

        return $response->redirect('/invoices/' . (int)$id);
    }

    private function buildEmailBody(array $invoice, array $lineItems, array|false $company, string $note): string
    {
        $fromName = $company['name'] ?? 'US Specialty Coatings';
        $lineRows = '';
        foreach ($lineItems as $li) {
            $lineRows .= '<tr>
                <td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;font-family:monospace;font-size:11px">' . htmlspecialchars($li['sku'] ?? '') . '</td>
                <td style="padding:8px 10px;border-bottom:1px solid #e5e7eb">' . htmlspecialchars($li['product_name'] ?? $li['description'] ?? '') . '</td>
                <td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;text-align:right">' . number_format((float)$li['qty'], 2) . '</td>
                <td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;text-align:right">$' . number_format((float)$li['unit_price'], 2) . '</td>
                <td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;text-align:right">$' . number_format((float)$li['line_total'], 2) . '</td>
            </tr>';
        }

        $noteHtml = $note
            ? '<p style="background:#fffbeb;border-left:4px solid #d97706;padding:12px 16px;margin:20px 0;font-size:14px">' . nl2br(htmlspecialchars($note)) . '</p>'
            : '';

        $dueDate    = !empty($invoice['due_date']) ? date('M j, Y', strtotime($invoice['due_date'])) : '';
        $amountPaid = (float)($invoice['amount_paid'] ?? 0);
        $balanceDue = (float)($invoice['balance_due'] ?? $invoice['total_amount']);
        $isReceipt  = $balanceDue <= 0;

        if ($isReceipt) {
            $balanceRow = '<div style="font-size:13px;color:#555">Amount Paid: $' . number_format($amountPaid > 0 ? $amountPaid : (float)$invoice['total_amount'], 2) . '</div>
               <div style="font-size:18px;font-weight:700;color:#16a34a;margin-top:6px">Paid in Full &mdash; Thank You</div>';
        } elseif ($amountPaid > 0) {
            $balanceRow = '<div style="font-size:13px;color:#555">Amount Paid: ($' . number_format($amountPaid, 2) . ')</div>
               <div style="font-size:18px;font-weight:700;color:#dc2626;margin-top:6px">Balance Due: $' . number_format($balanceDue, 2) . '</div>';
        } else {
            $balanceRow = '<div style="font-size:18px;font-weight:700;color:#222b59;margin-top:6px">Total Due: $' . number_format((float)$invoice['total_amount'], 2) . '</div>';
        }

        $trackingHtml = '';
        if (!empty($invoice['tracking_number'])) {
            $trackingHtml = '<div style="background:#f0f6ff;border:1px solid #bfdbfe;border-radius:6px;padding:10px 14px;margin:16px 0;font-size:13px">
                <strong>Shipped' . (!empty($invoice['ship_via']) ? ' via ' . htmlspecialchars($invoice['ship_via']) : '') . '</strong>'
                . (!empty($invoice['ship_date']) ? ' on ' . date('M j, Y', strtotime($invoice['ship_date'])) : '')
                . ' &mdash; Tracking #: <span style="font-family:monospace;font-weight:700">' . htmlspecialchars($invoice['tracking_number']) . '</span>
            </div>';
        }

        return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;color:#1a1a1a;max-width:700px;margin:0 auto;padding:20px">
        <div style="background:#222b59;padding:20px 24px;border-radius:6px 6px 0 0">
            <h1 style="color:#fff;margin:0;font-size:20px">' . ($isReceipt ? 'Receipt' : 'Invoice') . '</h1>
            <div style="color:#a5b4fc;font-size:14px;margin-top:4px">#' . htmlspecialchars($invoice['invoice_number']) . '</div>
        </div>
        <div style="border:1px solid #e5e7eb;border-top:none;padding:20px 24px;border-radius:0 0 6px 6px">
            ' . $noteHtml . $trackingHtml . '
            <table style="width:100%;border-collapse:collapse;margin-bottom:16px">
                <tr>
                    <td style="width:50%;vertical-align:top;padding-right:16px">
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#888;margin-bottom:6px">Bill To</div>
                        <div style="font-size:14px"><strong>' . htmlspecialchars($invoice['company_name']) . '</strong></div>
                    </td>
                    <td style="width:50%;vertical-align:top;text-align:right">
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#888;margin-bottom:4px">Invoice Date</div>
                        <div style="font-size:14px">' . date('M j, Y', strtotime($invoice['invoice_date'])) . '</div>
                        ' . (($dueDate && !$isReceipt) ? '<div style="font-size:12px;color:#dc2626;margin-top:6px;font-weight:700">Due: ' . $dueDate . '</div>' : '') . '
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
                <div style="font-size:13px;color:#555">Subtotal: $' . number_format((float)$invoice['subtotal'], 2) . '</div>
                ' . ((float)($invoice['tax_amount'] ?? 0) > 0 ? '<div style="font-size:13px;color:#555">Tax: $' . number_format((float)$invoice['tax_amount'], 2) . '</div>' : '') . '
                ' . ((float)($invoice['shipping_amount'] ?? 0) > 0 ? '<div style="font-size:13px;color:#555">Shipping: $' . number_format((float)$invoice['shipping_amount'], 2) . '</div>' : '') . '
                ' . $balanceRow . '
            </div>
            <p style="margin-top:24px;font-size:12px;color:#999;border-top:1px solid #e5e7eb;padding-top:12px">
                ' . htmlspecialchars($fromName) . ' &bull; ' . htmlspecialchars($company['email'] ?? '') . '
            </p>
        </div>
        </body></html>';
    }
}
