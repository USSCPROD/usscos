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
use App\Services\QuoteService;

class QuoteController extends Controller
{
    private QuoteService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new QuoteService();
    }

    public function index(Request $request, Response $response): Response
    {
        $page    = max(1, (int)($request->query('page') ?? 1));
        $search  = trim($request->query('q') ?? '');
        $status  = $request->query('status') ?? 'all';
        $sort    = $request->query('sort') ?? 'date_desc';

        $data = $this->service->list($page, 50, $search, $status, $sort);

        return $this->view('quotes.index', ['title' => 'Quotes', ...$data]);
    }

    public function create(Request $request, Response $response): Response
    {
        $data        = $this->service->create();
        $customerId  = (int)($request->query('customer_id')  ?? 0);
        $leadId      = (int)($request->query('lead_id')      ?? 0);
        $presetOppId = (int)($request->query('opportunity_id') ?? 0);

        $presetCustomer = null;
        $presetLead     = null;

        if ($customerId) {
            $presetCustomer = Database::selectOne(
                'SELECT id, company_name, email AS customer_email, phone,
                        bill_address_1, bill_address_2, bill_city, bill_state, bill_zip,
                        payment_term_id
                 FROM customers WHERE id = ?',
                [$customerId]
            );
        } elseif ($leadId) {
            $presetLead = Database::selectOne(
                'SELECT id, company_name, email, phone, first_name, last_name
                 FROM leads WHERE id = ?',
                [$leadId]
            );
        }

        return $this->view('quotes.create', [
            'title'                => 'New Quote',
            'preset_customer'      => $presetCustomer,
            'preset_lead'          => $presetLead,
            'preset_opportunity_id'=> $presetOppId,
            ...$data,
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $user    = Auth::user();
        $quoteId = $this->service->store((int)$user['id'], $_POST);

        if (($_POST['_action'] ?? '') === 'save_new') {
            return $response->redirect('/quotes/create');
        }

        return $response->redirect('/quotes/' . $quoteId);
    }

    public function show(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $data = $this->service->show((int)$id);
        } catch (\RuntimeException) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        return $this->view('quotes.show', [
            'title' => 'Quote ' . $data['quote']['quote_number'],
            ...$data,
        ]);
    }

    public function edit(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $data = $this->service->edit((int)$id);
        } catch (\RuntimeException) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        $quote = $data['quote'];
        if (in_array($quote['status'], ['accepted', 'converted'])) {
            Session::flash('error', 'Accepted or converted quotes cannot be edited.');
            return $response->redirect('/quotes/' . (int)$id);
        }

        return $this->view('quotes.edit', [
            'title' => 'Edit Quote ' . $quote['quote_number'],
            ...$data,
        ]);
    }

    public function update(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $data  = $this->service->edit((int)$id);
            $quote = $data['quote'];

            if (in_array($quote['status'], ['accepted', 'converted'])) {
                Session::flash('error', 'Accepted or converted quotes cannot be edited.');
                return $response->redirect('/quotes/' . (int)$id);
            }

            $this->service->update((int)$id, $_POST);
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
            return $response->redirect('/quotes/' . (int)$id);
        }

        Session::flash('success', 'Quote updated.');
        return $response->redirect('/quotes/' . (int)$id);
    }

    public function linkOpportunity(Request $request, Response $response, string $id = '0'): Response
    {
        $oppId = ($_POST['opportunity_id'] ?? '') !== '' ? (int)$_POST['opportunity_id'] : null;
        Database::statement(
            'UPDATE quotes SET opportunity_id = ? WHERE id = ?',
            [$oppId, (int)$id]
        );
        if ($oppId) {
            $opp = Database::selectOne('SELECT expected_value FROM opportunities WHERE id = ?', [$oppId]);
            $quote = Database::selectOne('SELECT total_amount FROM quotes WHERE id = ?', [(int)$id]);
            if ($opp && $quote) {
                Database::statement('UPDATE opportunities SET expected_value = ? WHERE id = ?', [$quote['total_amount'], $oppId]);
            }
        }
        Session::flash('success', 'Opportunity linked.');
        return $response->redirect('/quotes/' . (int)$id);
    }

    public function updateStatus(Request $request, Response $response, string $id = '0'): Response
    {
        $status  = $_POST['status'] ?? '';
        $allowed = ['draft', 'sent', 'accepted', 'declined', 'expired'];

        if (!in_array($status, $allowed)) {
            Session::flash('error', 'Invalid status.');
            return $response->redirect('/quotes/' . (int)$id);
        }

        $repo  = new \App\Repositories\QuoteRepository();
        $quote = $repo->findWithDetails((int)$id);
        $repo->setStatus((int)$id, $status, $status === 'accepted' ? date('Y-m-d') : null);

        if ($quote) {
            $this->advanceLeadStatus($quote, $status);
        }

        Session::flash('success', 'Quote status updated.');
        return $response->redirect('/quotes/' . (int)$id);
    }

    public function convertToSO(Request $request, Response $response, string $id = '0'): Response
    {
        $user = Auth::user();

        try {
            $soId = $this->service->convertToSO((int)$id, (int)$user['id']);
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
            return $response->redirect('/quotes/' . (int)$id);
        }

        Session::flash('success', 'Quote converted to Sales Order.');
        return $response->redirect('/sales-orders/' . $soId);
    }

    public function email(Request $request, Response $response, string $id = '0'): Response
    {
        try {
            $data = $this->service->show((int)$id);
        } catch (\RuntimeException) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        $quote     = $data['quote'];
        $to        = trim($_POST['email_to']   ?? '');
        $fromEmail = trim($_POST['email_from'] ?? '');
        $note      = trim($_POST['email_note'] ?? '');

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Invalid recipient email address.');
            return $response->redirect('/quotes/' . (int)$id);
        }
        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            Session::flash('error', 'Invalid from email address.');
            return $response->redirect('/quotes/' . (int)$id);
        }

        $user     = Auth::user();
        $company  = Database::selectOne('SELECT * FROM companies WHERE id = 1');
        $fromName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($company['name'] ?? 'US Specialty Coatings');
        $subject  = 'Quote ' . $quote['quote_number'] . ' from ' . $fromName;

        $body   = $this->buildEmailBody($quote, $data['line_items'], $company, $note);
        $mailer = new Mailer();
        $sent   = $mailer->send($to, $subject, $body, $fromName, $fromEmail);

        if ($sent) {
            $repo = new \App\Repositories\QuoteRepository();
            if ($quote['status'] === 'draft') {
                $repo->setStatus((int)$id, 'sent');
                $this->advanceLeadStatus($quote, 'sent');
            }
            Session::flash('success', 'Quote emailed to ' . $to . '.');
        } else {
            Session::flash('error', 'Email failed: ' . $mailer->getLastError());
        }

        return $response->redirect('/quotes/' . (int)$id);
    }

    private function advanceLeadStatus(array $quote, string $quoteStatus): void
    {
        $leadId = !empty($quote['lead_id']) ? (int)$quote['lead_id'] : null;
        if (!$leadId) return;

        $lead = Database::selectOne('SELECT id, status FROM leads WHERE id = ?', [$leadId]);
        if (!$lead) return;

        $current = $lead['status'];

        $newStatus = match($quoteStatus) {
            'sent'     => $current === 'new' ? 'contacted' : null,
            'accepted' => in_array($current, ['new', 'contacted']) ? 'qualified' : null,
            default    => null,
        };

        if ($newStatus) {
            Database::statement('UPDATE leads SET status = ? WHERE id = ?', [$newStatus, $leadId]);
        }

        $this->advanceOpportunityStage($quote, $quoteStatus);
    }

    private function advanceOpportunityStage(array $quote, string $quoteStatus): void
    {
        $oppId = !empty($quote['opportunity_id']) ? (int)$quote['opportunity_id'] : null;
        if (!$oppId) return;

        $opp = Database::selectOne('SELECT id, stage FROM opportunities WHERE id = ?', [$oppId]);
        if (!$opp) return;

        $current = $opp['stage'];
        $stageOrder = ['prospecting' => 1, 'proposal' => 2, 'negotiation' => 3, 'closed_won' => 4, 'closed_lost' => 4];

        $newStage = match($quoteStatus) {
            'sent'     => 'proposal',
            'accepted' => 'closed_won',
            'declined' => 'closed_lost',
            default    => null,
        };

        if (!$newStage) return;

        // Only move forward (or to closed states)
        $isClosed = in_array($newStage, ['closed_won', 'closed_lost']);
        if ($isClosed || ($stageOrder[$newStage] ?? 0) > ($stageOrder[$current] ?? 0)) {
            Database::statement('UPDATE opportunities SET stage = ? WHERE id = ?', [$newStage, $oppId]);
        }
    }

    private function buildEmailBody(array $quote, array $lineItems, array|false $company, string $note): string
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

        $noteHtml   = $note ? '<p style="background:#fffbeb;border-left:4px solid #d97706;padding:12px 16px;margin:20px 0;font-size:14px">' . nl2br(htmlspecialchars($note)) . '</p>' : '';
        $expiryDate = !empty($quote['expiry_date']) ? date('M j, Y', strtotime($quote['expiry_date'])) : '';

        return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;color:#1a1a1a;max-width:700px;margin:0 auto;padding:20px">
        <div style="background:#222b59;padding:20px 24px;border-radius:6px 6px 0 0">
            <h1 style="color:#fff;margin:0;font-size:20px">Quote</h1>
            <div style="color:#a5b4fc;font-size:14px;margin-top:4px">' . htmlspecialchars($quote['quote_number']) . '</div>
        </div>
        <div style="border:1px solid #e5e7eb;border-top:none;padding:20px 24px;border-radius:0 0 6px 6px">
            ' . $noteHtml . '
            <table style="width:100%;border-collapse:collapse;margin-bottom:16px">
                <tr>
                    <td style="width:50%;vertical-align:top;padding-right:16px">
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#888;margin-bottom:6px">Prepared For</div>
                        <div style="font-size:14px"><strong>' . htmlspecialchars($quote['company_name']) . '</strong></div>
                    </td>
                    <td style="width:50%;vertical-align:top;text-align:right">
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;color:#888;margin-bottom:4px">Quote Date</div>
                        <div style="font-size:14px">' . date('M j, Y', strtotime($quote['quote_date'])) . '</div>
                        ' . ($expiryDate ? '<div style="font-size:12px;color:#dc2626;margin-top:6px;font-weight:700">Expires: ' . $expiryDate . '</div>' : '') . '
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
                <div style="font-size:13px;color:#555">Subtotal: $' . number_format((float)$quote['subtotal'], 2) . '</div>
                ' . ((float)($quote['tax_amount'] ?? 0) > 0 ? '<div style="font-size:13px;color:#555">Tax: $' . number_format((float)$quote['tax_amount'], 2) . '</div>' : '') . '
                <div style="font-size:18px;font-weight:700;color:#222b59;margin-top:6px">Total: $' . number_format((float)$quote['total_amount'], 2) . '</div>
            </div>
            <p style="margin-top:24px;font-size:12px;color:#999;border-top:1px solid #e5e7eb;padding-top:12px">
                ' . htmlspecialchars($fromName) . ' &bull; ' . htmlspecialchars($company['email'] ?? '') . '
            </p>
        </div>
        </body></html>';
    }
}
