<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\EmailIntakeRepository;
use App\Services\Activity;
use App\Services\Email\EmailIntake;

/**
 * The BCC mailbox: one machine endpoint that receives mail, and the screen for whatever
 * it could not place.
 */
class EmailIntakeController extends Controller
{
    private EmailIntakeRepository $repo;

    public function __construct()
    {
        parent::__construct();
        $this->repo = new EmailIntakeRepository();
    }

    /**
     * POST /api/email/inbound — a message from the BCC mailbox.
     *
     * Behind the API key middleware, which fails closed when no key is configured. Expects
     * JSON, because whatever watches the mailbox (a Power Automate flow, a script, a
     * provider's inbound parse) can all produce JSON, and none of them agree on anything
     * else.
     *
     * Always 200 for a message it cannot place. An unmatched email is stored and queued,
     * not rejected: a non-2xx sends most senders into retry, and retrying will not make an
     * address appear on a customer record.
     */
    public function inbound(Request $request, Response $response): Response
    {
        $raw  = file_get_contents('php://input');
        $data = json_decode((string)$raw, true);

        if (!is_array($data) || (trim((string)($data['from_email'] ?? '')) === '')) {
            return $response->json(['error' => 'Invalid payload: from_email is required.'], 400);
        }

        try {
            $result = (new EmailIntake())->receive([
                'message_id' => $data['message_id'] ?? '',
                'from_email' => $data['from_email'] ?? '',
                'from_name'  => $data['from_name']  ?? '',
                'to'         => $this->addresses($data['to'] ?? []),
                'cc'         => $this->addresses($data['cc'] ?? []),
                'subject'    => $data['subject'] ?? '',
                'body'       => $data['body'] ?? ($data['html'] ?? ''),
                'sent_at'    => $data['sent_at'] ?? ($data['date'] ?? ''),
            ]);
        } catch (\Throwable $e) {
            Logger::error('Email intake failed: ' . $e->getMessage(), ['from' => $data['from_email'] ?? '']);
            return $response->json(['error' => 'Could not process the message.'], 500);
        }

        return $response->json($result, 201);
    }

    /** GET /admin/email-intake — what arrived, and what could not be placed. */
    public function index(Request $request, Response $response): Response
    {
        $show = (string)($request->query('show') ?? 'unmatched');
        $statuses = match ($show) {
            'all'     => ['matched', 'unmatched', 'ignored'],
            'matched' => ['matched'],
            'ignored' => ['ignored'],
            default   => ['unmatched'],
        };

        return $this->view('admin.email_intake', [
            'title'           => 'Email intake',
            'emails'          => $this->repo->byStatus($statuses, 100),
            'show'            => $show,
            'unmatchedCount'  => $this->repo->countByStatus('unmatched'),
        ]);
    }

    /**
     * POST /admin/email-intake/{id}/attach — place an email by hand.
     *
     * Takes a customer id or a lead id, not a free-text search: whatever is attached here
     * writes to a timeline, and a typo'd id would put somebody's email on the wrong
     * account with nothing saying so.
     */
    public function attach(Request $request, Response $response, string $id = '0'): Response
    {
        $email = $this->repo->find((int)$id);
        if (!$email) {
            return $this->view('errors.404', ['title' => 'Not Found'], 404);
        }

        $customerId = (int)($_POST['customer_id'] ?? 0) ?: null;
        $leadId     = (int)($_POST['lead_id'] ?? 0) ?: null;

        if ($customerId === null && $leadId === null) {
            Session::flash('error', 'Give a customer or a lead to attach it to.');
            return $response->redirect('/admin/email-intake');
        }

        $activityId = Activity::logInteraction(
            'email',
            'Email: ' . ($email['subject'] ?: '(no subject)')
                . ' — from ' . $email['from_email']
                . ' (attached by hand)',
            [
                'subject_type' => $customerId !== null ? 'Customer' : 'Lead',
                'subject_id'   => $customerId ?? $leadId,
                'customer_id'  => $customerId,
                'lead_id'      => $leadId,
                'user_id'      => Auth::id(),
            ]
        );

        $this->repo->attach((int)$id, $customerId, $leadId, (int)$activityId);

        Session::flash('success', 'Email attached.');
        return $response->redirect('/admin/email-intake');
    }

    /** POST /admin/email-intake/{id}/ignore — not business mail. */
    public function ignore(Request $request, Response $response, string $id = '0'): Response
    {
        $this->repo->ignore((int)$id);
        Session::flash('success', 'Email ignored.');
        return $response->redirect('/admin/email-intake');
    }

    /** Accepts either a list or a single comma-separated string. Senders differ. */
    private function addresses(mixed $value): array
    {
        if (is_array($value)) {
            return array_map('strval', $value);
        }
        return array_filter(array_map('trim', explode(',', (string)$value)));
    }
}
