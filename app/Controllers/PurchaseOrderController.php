<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Database;
use App\Repositories\PurchaseOrderRepository;
use App\Repositories\VendorRepository;
use App\Services\PurchaseReceivingService;

class PurchaseOrderController extends Controller
{
    private PurchaseOrderRepository $repo;
    private VendorRepository $vendors;
    private PurchaseReceivingService $receiving;

    public function __construct()
    {
        parent::__construct();
        $this->repo      = new PurchaseOrderRepository();
        $this->vendors   = new VendorRepository();
        $this->receiving = new PurchaseReceivingService();
    }

    public function index(Request $request, Response $response): Response
    {
        $search = trim($_GET['search'] ?? '');
        $status = $_GET['status'] ?? '';
        $page   = max(1, (int)($_GET['page'] ?? 1));

        return $this->view('purchasing.index', [
            'title'     => 'Purchase Orders',
            'paginator' => $this->repo->paginate($page, 50, $search, $status),
            'search'    => $search,
            'status'    => $status,
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        $company = Database::selectOne('SELECT * FROM companies WHERE id = 1');

        $po = [
            'ship_to_name'      => $company['name'] ?? '',
            'ship_to_address_1' => $company['address_line1'] ?? '',
            'ship_to_address_2' => $company['address_line2'] ?? '',
            'ship_to_city'      => $company['city'] ?? '',
            'ship_to_state'     => $company['state'] ?? '',
            'ship_to_zip'       => $company['postal_code'] ?? '',
        ];

        return $this->view('purchasing.form', [
            'title'    => 'New Purchase Order',
            'po'       => $po,
            'lines'    => [],
            'vendors'  => $this->vendors->all(),
            'poNumber' => $this->repo->nextPoNumber(),
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $user  = Auth::user();
        $lines = $this->parseLines($_POST);

        if (empty($lines)) {
            Session::flash('error', 'Please add at least one line item.');
            return $response->redirect('/purchasing/create');
        }

        $data = array_merge($_POST, ['po_number' => $this->repo->nextPoNumber()]);
        $id   = $this->repo->insert($data, $lines, (int)$user['id']);

        Session::flash('success', 'Purchase order created.');
        return $response->redirect('/purchasing/' . $id);
    }

    public function show(Request $request, Response $response, string $id = '0'): Response
    {
        $po = $this->repo->findById((int)$id);
        if (!$po) return $this->view('errors.404', ['title' => 'Not Found'], 404);

        return $this->view('purchasing.show', [
            'title'     => 'PO ' . $po['po_number'],
            'po'        => $po,
            'lines'     => $this->repo->getLines((int)$id),
            'locations' => (new \App\Repositories\StockRepository())->storableLocations(),
        ]);
    }

    public function edit(Request $request, Response $response, string $id = '0'): Response
    {
        $po = $this->repo->findById((int)$id);
        if (!$po) return $this->view('errors.404', ['title' => 'Not Found'], 404);

        if (in_array($po['status'], ['received', 'closed', 'cancelled'], true)) {
            Session::flash('error', 'This purchase order cannot be edited in its current status.');
            return $response->redirect('/purchasing/' . (int)$id);
        }

        return $this->view('purchasing.form', [
            'title'   => 'Edit PO ' . $po['po_number'],
            'po'      => $po,
            'lines'   => $this->repo->getLines((int)$id),
            'vendors' => $this->vendors->all(),
        ]);
    }

    public function update(Request $request, Response $response, string $id = '0'): Response
    {
        $po = $this->repo->findById((int)$id);
        if (!$po) return $this->view('errors.404', ['title' => 'Not Found'], 404);

        $lines = $this->parseLines($_POST);

        if (empty($lines)) {
            Session::flash('error', 'Please add at least one line item.');
            return $response->redirect('/purchasing/' . (int)$id . '/edit');
        }

        $this->repo->update((int)$id, $_POST, $lines);
        Session::flash('success', 'Purchase order updated.');
        return $response->redirect('/purchasing/' . (int)$id);
    }

    public function receive(Request $request, Response $response, string $id = '0'): Response
    {
        $user = Auth::user();
        $po   = $this->repo->findById((int)$id);

        if (!$po) return $this->view('errors.404', ['title' => 'Not Found'], 404);

        try {
            $result = $this->receiving->receive(
                (int)$id,
                $_POST['receive_qty'] ?? [],
                (int)($_POST['location_id'] ?? 0),
                (int)$user['id'],
                trim((string)($_POST['receipt_notes'] ?? ''))
            );
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());

            return $response->redirect('/purchasing/' . (int)$id);
        }

        if ($result['variances'] > 0) {
            // Said plainly rather than buried: the PO and the delivery disagree, and
            // somebody has to decide which one is right.
            Session::flash('error', sprintf(
                '%d line%s came in at a different quantity than the PO says. %s',
                $result['variances'],
                $result['variances'] === 1 ? '' : 's',
                'Review it under Purchasing > Variances.'
            ));
        } else {
            Session::flash('success', 'Received and put into stock.');
        }

        return $response->redirect('/purchasing/' . (int)$id);
    }

    /** Receipts that disagree with their purchase order, waiting on somebody. */
    public function variances(Request $request, Response $response): Response
    {
        return $this->view('purchasing.variances', [
            'title' => 'Receipt Variances',
            'items' => $this->receiving->openVariances(),
        ]);
    }

    /** Resolve one variance — either the PO follows the receipt, or the difference is explained. */
    public function resolveVariance(Request $request, Response $response, string $lineId = '0'): Response
    {
        $user = Auth::user();
        $note = trim((string)($_POST['variance_note'] ?? ''));

        try {
            if (($_POST['action'] ?? '') === 'accept_received') {
                $this->receiving->acceptReceived((int)$lineId, (int)$user['id'], $note);
                Session::flash('success', 'PO updated to the quantity actually received.');
            } else {
                $this->receiving->acknowledge((int)$lineId, (int)$user['id'], $note);
                Session::flash('success', 'Variance closed with your note.');
            }
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        return $response->redirect('/purchasing/variances');
    }

    public function updateStatus(Request $request, Response $response, string $id = '0'): Response
    {
        $po     = $this->repo->findById((int)$id);
        $status = $_POST['status'] ?? '';

        if (!$po) return $this->view('errors.404', ['title' => 'Not Found'], 404);

        $allowed = ['draft', 'sent', 'partial', 'received', 'closed', 'cancelled'];
        if (in_array($status, $allowed, true)) {
            $this->repo->updateStatus((int)$id, $status);
            Session::flash('success', 'Status updated.');
        }

        return $response->redirect('/purchasing/' . (int)$id);
    }

    public function printView(Request $request, Response $response, string $id = '0'): Response
    {
        $po = $this->repo->findById((int)$id);
        if (!$po) return $this->view('errors.404', ['title' => 'Not Found'], 404);

        $company = \App\Core\Database::selectOne('SELECT * FROM companies WHERE id = 1');
        $lines   = $this->repo->getLines((int)$id);

        // Render without layout
        $data = compact('po', 'lines', 'company');
        extract($data);
        ob_start();
        require BASE_PATH . '/app/Views/purchasing/print.php';
        $html = ob_get_clean();

        return $response->html($html);
    }

    public function email(Request $request, Response $response, string $id = '0'): Response
    {
        $po      = $this->repo->findById((int)$id);
        if (!$po) return $this->view('errors.404', ['title' => 'Not Found'], 404);

        $company = \App\Core\Database::selectOne('SELECT * FROM companies WHERE id = 1');
        $lines   = $this->repo->getLines((int)$id);
        $to      = trim($_POST['email_to'] ?? '');
        $note    = trim($_POST['email_note'] ?? '');

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Invalid email address.');
            return $response->redirect('/purchasing/' . (int)$id);
        }

        $fromName  = $company['name'] ?? 'US Specialty Coatings';
        $fromEmail = $company['email'] ?? 'admin@usscos.com';
        $subject   = 'Purchase Order ' . $po['po_number'] . ' from ' . $fromName;

        $body   = $this->buildEmailBody($po, $lines, $company, $note);
        $mailer = new Mailer();
        $sent   = $mailer->send($to, $subject, $body, $fromName, $fromEmail);

        if ($sent) {
            Session::flash('success', 'Purchase order emailed to ' . $to . '.');
        } else {
            Session::flash('error', 'Email failed: ' . $mailer->getLastError());
        }

        return $response->redirect('/purchasing/' . (int)$id);
    }

    private function buildEmailBody(array $po, array $lines, array|false $company, string $note): string
    {
        $fromName = $company['name'] ?? 'US Specialty Coatings';
        $lineRows = '';
        foreach ($lines as $line) {
            $lineRows .= '<tr>
                <td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;font-family:monospace;font-size:11px">' . htmlspecialchars($line['sku'] ?? '') . '</td>
                <td style="padding:8px 10px;border-bottom:1px solid #e5e7eb">' . htmlspecialchars($line['product_name'] ?? $line['description'] ?? '') . '</td>
                <td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;text-align:right">' . number_format((float)$line['qty_ordered'], 2) . '</td>
                <td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;text-align:right">$' . number_format((float)$line['unit_cost'], 4) . '</td>
                <td style="padding:8px 10px;border-bottom:1px solid #e5e7eb;text-align:right">$' . number_format((float)$line['line_total'], 2) . '</td>
            </tr>';
        }

        $noteHtml = $note ? '<p style="background:#fffbeb;border-left:4px solid #d97706;padding:12px 16px;margin:20px 0;font-size:14px">' . nl2br(htmlspecialchars($note)) . '</p>' : '';
        $memoHtml = ($po['memo'] ?? '') ? '<p style="margin-top:16px;font-size:13px;color:#555"><strong>Notes:</strong> ' . nl2br(htmlspecialchars($po['memo'])) . '</p>' : '';

        return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;color:#1a1a1a;max-width:700px;margin:0 auto;padding:20px">
        <div style="background:#222b59;padding:20px 24px;border-radius:6px 6px 0 0">
            <h1 style="color:#fff;margin:0;font-size:20px">Purchase Order</h1>
            <div style="color:#a5b4fc;font-size:14px;margin-top:4px">' . htmlspecialchars($po['po_number']) . '</div>
        </div>
        <div style="border:1px solid #e5e7eb;border-top:none;padding:20px 24px;border-radius:0 0 6px 6px">
            ' . $noteHtml . '
            <table style="width:100%;border-collapse:collapse;margin-bottom:16px">
                <tr>
                    <td style="width:50%;vertical-align:top;padding-right:16px">
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#888;margin-bottom:6px">Vendor</div>
                        <div style="font-size:14px"><strong>' . htmlspecialchars($po['vendor_name']) . '</strong></div>
                    </td>
                    <td style="width:50%;vertical-align:top;text-align:right">
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#888;margin-bottom:6px">Order Date</div>
                        <div style="font-size:14px">' . date('M j, Y', strtotime($po['order_date'])) . '</div>
                        ' . ($po['expected_date'] ? '<div style="font-size:12px;color:#555;margin-top:4px">Expected: ' . date('M j, Y', strtotime($po['expected_date'])) . '</div>' : '') . '
                    </td>
                </tr>
            </table>
            <table style="width:100%;border-collapse:collapse;margin:20px 0">
                <thead>
                    <tr style="background:#222b59">
                        <th style="color:#fff;padding:8px 10px;text-align:left;font-size:12px">SKU</th>
                        <th style="color:#fff;padding:8px 10px;text-align:left;font-size:12px">Description</th>
                        <th style="color:#fff;padding:8px 10px;text-align:right;font-size:12px">Qty</th>
                        <th style="color:#fff;padding:8px 10px;text-align:right;font-size:12px">Unit Cost</th>
                        <th style="color:#fff;padding:8px 10px;text-align:right;font-size:12px">Total</th>
                    </tr>
                </thead>
                <tbody>' . $lineRows . '</tbody>
            </table>
            <div style="text-align:right;border-top:2px solid #222b59;padding-top:12px">
                <div style="font-size:13px;color:#555">Subtotal: $' . number_format((float)$po['subtotal'], 2) . '</div>
                ' . ((float)$po['tax_amount'] > 0 ? '<div style="font-size:13px;color:#555">Tax: $' . number_format((float)$po['tax_amount'], 2) . '</div>' : '') . '
                ' . ((float)$po['shipping_cost'] > 0 ? '<div style="font-size:13px;color:#555">Shipping: $' . number_format((float)$po['shipping_cost'], 2) . '</div>' : '') . '
                <div style="font-size:18px;font-weight:700;color:#222b59;margin-top:6px">Total: $' . number_format((float)$po['total_amount'], 2) . '</div>
            </div>
            ' . $memoHtml . '
            <p style="margin-top:24px;font-size:12px;color:#999;border-top:1px solid #e5e7eb;padding-top:12px">
                ' . htmlspecialchars($fromName) . ' &bull; ' . htmlspecialchars($company['email'] ?? '') . '
            </p>
        </div>
        </body></html>';
    }

    // -------------------------------------------------------------------------

    private function parseLines(array $post): array
    {
        $lines  = [];
        $ids    = $post['line_product_id'] ?? [];
        $descs  = $post['line_description'] ?? [];
        $qtys   = $post['line_qty'] ?? [];
        $costs  = $post['line_cost'] ?? [];

        foreach ($qtys as $i => $qty) {
            $qty  = (float)$qty;
            $cost = (float)($costs[$i] ?? 0);
            $desc = trim($descs[$i] ?? '');
            $pid  = ($ids[$i] ?? '') !== '' ? (int)$ids[$i] : null;

            if ($qty <= 0 && $desc === '') continue;

            $lines[] = [
                'product_id'  => $pid,
                'description' => $desc,
                'qty_ordered' => $qty,
                'unit_cost'   => $cost,
            ];
        }

        return $lines;
    }
}
