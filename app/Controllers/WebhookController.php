<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\WebsiteLeadService;

class WebhookController extends Controller
{
    private WebsiteLeadService $intake;

    public function __construct()
    {
        parent::__construct();
        $this->intake = new WebsiteLeadService();
    }

    public function lead(Request $request, Response $response): Response
    {
        /*
         * The secret is accepted but not yet required.
         *
         * usscos.com's own forms — contact.php, paint-quote.php, stencil-quote.php —
         * post here and do not send it. Requiring it on 13 Nov 2026 took live lead
         * capture down until it was noticed, which is the kind of change that has to
         * land on both sides at once or not at all.
         *
         * It is also not the fix for the spam. The junk leads arrived *through* the
         * contact form, filled in by bots; a secret stops something posting straight at
         * this endpoint and does nothing about that. The fix for form spam is a
         * honeypot on the form — see docs/wordpress/README.md.
         *
         * So: when a secret is sent it must be right, which protects the forms once
         * they send it. When none is sent the request is allowed and logged, so the
         * switch to requiring it can be made the moment the logs stop showing any.
         *
         * THAT SWITCH IS LEAD_WEBHOOK_REQUIRE_SECRET, and until it is on this endpoint is
         * open. Rotating the secret does not change that: anyone holding a leaked value
         * can omit the header instead. Rotation protects the senders; requiring it
         * protects the endpoint. Both are needed, in that order.
         *
         * LEAD_WEBHOOK_SECRET_PREVIOUS is accepted alongside the current secret so a
         * rotation does not have to land on every sender at the same instant. Its use is
         * logged by sender, so the overlap closes on evidence.
         */
        $current  = (string)(\App\Core\Config::get('app.lead_webhook_secret') ?: '');
        $previous = (string)(\App\Core\Config::get('app.lead_webhook_secret_previous') ?: '');
        $required = (bool)\App\Core\Config::get('app.lead_webhook_require_secret');
        $given    = (string)($_SERVER['HTTP_X_USSCOS_SECRET'] ?? '');

        if ($given === '') {
            if ($required) {
                error_log('Lead webhook: rejected, no secret, from ' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
                return $response->json(['error' => 'Unauthorized'], 401);
            }

            // Logged every time. This line is the signal for when the endpoint can be
            // closed: once it stops appearing, every sender is authenticating and
            // LEAD_WEBHOOK_REQUIRE_SECRET can be turned on without taking leads down.
            if ($current !== '') {
                error_log('Lead webhook: no secret sent from ' . ($_SERVER['REMOTE_ADDR'] ?? '?')
                    . ' ua=' . ($_SERVER['HTTP_USER_AGENT'] ?? '-'));
            }
        } elseif ($current !== '' || $previous !== '') {
            // hash_equals on both, with no short-circuit between them: comparing only when
            // the first fails leaks which secret was tried through response timing.
            $matchesCurrent  = $current  !== '' && hash_equals($current, $given);
            $matchesPrevious = $previous !== '' && hash_equals($previous, $given);

            if (!$matchesCurrent && !$matchesPrevious) {
                return $response->json(['error' => 'Unauthorized'], 401);
            }

            if ($matchesPrevious && !$matchesCurrent) {
                // Names the sender, because the point of this line is to tell you which
                // one still needs updating before the old secret can be dropped.
                error_log('Lead webhook: PREVIOUS secret used by ' . ($_SERVER['REMOTE_ADDR'] ?? '?')
                    . ' ua=' . ($_SERVER['HTTP_USER_AGENT'] ?? '-')
                    . ' - that sender still has the retired secret.');
            }
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (!$data || empty($data['form_type'])) {
            return $response->json(['error' => 'Invalid payload'], 400);
        }

        /*
         * Which site sent this. Worked out once here rather than in each handler, and
         * carried on the payload so the handlers stay a straight mapping from fields to
         * lead. Null means the sender did not identify itself - logged, so the gap shows
         * up as a gap rather than being quietly filled with a guess.
         */
        $data['source_site'] = \App\Services\Web\LeadOrigin::detect($data, $_SERVER);

        if ($data['source_site'] === null) {
            error_log('Lead webhook: sender did not identify its site, from '
                . ($_SERVER['REMOTE_ADDR'] ?? '?')
                . ' ua=' . ($_SERVER['HTTP_USER_AGENT'] ?? '-'));
        }

        try {
            match ($data['form_type']) {
                'contact'       => $this->handleContact($data),
                'paint_quote'   => $this->handlePaintQuote($data),
                'stencil_quote' => $this->handleStencilQuote($data),
                default         => throw new \InvalidArgumentException('Unknown form type'),
            };
        } catch (\Exception $e) {
            error_log('Webhook error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return $response->json(['error' => 'Processing failed'], 500);
        }

        return $response->json(['status' => 'ok'], 201);
    }

    // -------------------------------------------------------------------------

    private function handleContact(array $d): void
    {
        $routing = $this->intake->routing();

        $leadId = $this->intake->createLead([
            'first_name' => $d['first_name'],
            'last_name'  => $d['last_name'],
            'email'      => $d['email'],
            'phone'      => $d['phone'] ?? null,
            'source'     => 'Website — Contact Form',
            'notes'      => $this->buildContactNotes($d),
            'assigned_to'=> $routing['general_user_id'] ?? null,
            'source_site'=> $d['source_site'] ?? null,
        ]);

        $this->intake->notify(
            $routing['general_email'],
            'New Contact Form Submission — ' . ($d['first_name'] . ' ' . $d['last_name']),
            $this->buildContactEmail($d)
        );
    }

    private function handlePaintQuote(array $d): void
    {
        $routing    = $this->intake->routing();
        $assignedTo = $this->nextPaintRep($routing);

        $leadId = $this->intake->createLead([
            'first_name'   => $d['first_name'],
            'last_name'    => $d['last_name'],
            'company'      => $d['company'] ?? null,
            'email'        => $d['email'],
            'phone'        => $d['phone'] ?? null,
            'address_line1'=> $d['address_line1'] ?? null,
            'address_line2'=> $d['address_line2'] ?? null,
            'city'         => $d['city'] ?? null,
            'state'        => $d['state'] ?? null,
            'postal_code'  => $d['postal_code'] ?? null,
            'source'       => 'Website — Paint Quote Request',
            'notes'        => $this->buildPaintNotes($d),
            'assigned_to'  => $assignedTo,
            'source_site'  => $d['source_site'] ?? null,
        ]);

        // Notify the assigned rep directly if we have their email, else fallback
        $notifyEmail = $routing['paint_email'];
        if ($assignedTo) {
            $rep = Database::selectOne('SELECT email FROM users WHERE id = ?', [$assignedTo]);
            if ($rep) $notifyEmail = $rep['email'];
        }

        $this->intake->notify(
            $notifyEmail,
            'New Paint Quote Request — ' . ($d['first_name'] . ' ' . $d['last_name']),
            $this->buildPaintEmail($d)
        );
    }

    private function nextPaintRep(array $routing): ?int
    {
        $pool = array_filter(explode(',', $routing['paint_pool'] ?? ''));
        if (empty($pool)) {
            return null;
        }

        $pool  = array_values($pool);
        $index = (int)($routing['paint_next_index'] ?? 0);
        if ($index >= count($pool)) $index = 0;

        $userId = (int)$pool[$index];

        // Advance the index
        $next = ($index + 1) % count($pool);
        Database::update(
            "UPDATE settings SET setting_value = ? WHERE setting_key = 'lead_routing_paint_next_index'",
            [(string)$next]
        );

        return $userId;
    }

    private function handleStencilQuote(array $d): void
    {
        $routing = $this->intake->routing();

        $leadId = $this->intake->createLead([
            'first_name'   => $d['first_name'],
            'last_name'    => $d['last_name'],
            'company'      => $d['company'] ?? null,
            'email'        => $d['email'],
            'phone'        => $d['phone'] ?? null,
            'address_line1'=> $d['address_line1'] ?? null,
            'address_line2'=> $d['address_line2'] ?? null,
            'city'         => $d['city'] ?? null,
            'state'        => $d['state'] ?? null,
            'postal_code'  => $d['postal_code'] ?? null,
            'source'       => 'Website — Stencil Quote Request',
            'notes'        => $this->buildStencilNotes($d),
            'assigned_to'  => $routing['stencil_user_id']  ?? null,
            'attachment'   => $d['attachment'] ?? null,
            'source_site'  => $d['source_site'] ?? null,
        ]);

        $this->intake->notify(
            $routing['stencil_email'],
            'New Stencil Quote Request — ' . ($d['first_name'] . ' ' . $d['last_name']),
            $this->buildStencilEmail($d)
        );

        // If they need paint, create a follow-up task for the paint rep
        if (!empty($d['needs_paint']) && $d['needs_paint'] === 'yes') {
            $gallons = !empty($d['paint_gallons']) ? ' (~' . (int)$d['paint_gallons'] . ' gal)' : '';
            $this->createPaintFollowUpTask($leadId, $d['first_name'] . ' ' . $d['last_name'], $gallons, $routing);
        }
    }

    // -------------------------------------------------------------------------
    // Lead creation
    // -------------------------------------------------------------------------


    private function createPaintFollowUpTask(int $leadId, string $contactName, string $gallons, array $routing): void
    {
        Database::insert(
            'INSERT INTO tasks
                (title, description, lead_id, assigned_to, priority, status, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [
                'Follow up on paint interest — ' . $contactName,
                'This stencil quote request includes a paint need' . $gallons . '. Follow up to discuss paint options.',
                $leadId,
                $this->nextPaintRep($routing),
                'high',
                'open',
                1,
            ]
        );
    }

    // -------------------------------------------------------------------------
    // Routing settings
    // -------------------------------------------------------------------------


    // -------------------------------------------------------------------------
    // Email sending
    // -------------------------------------------------------------------------



    // -------------------------------------------------------------------------
    // Notes builders
    // -------------------------------------------------------------------------

    private function buildContactNotes(array $d): string
    {
        $lines = ['Subject: ' . ($d['subject'] ?? '')];
        if (!empty($d['message'])) $lines[] = "\n" . $d['message'];
        return implode("\n", $lines);
    }

    private function buildPaintNotes(array $d): string
    {
        $lines = [];
        if (!empty($d['uses_paint']))    $lines[] = 'Currently uses marking paint: ' . $d['uses_paint'];
        if (!empty($d['current_brand'])) $lines[] = 'Current brand: ' . $d['current_brand'];
        if (!empty($d['products']))      $lines[] = 'Products needed: ' . implode(', ', (array)$d['products']);
        if (!empty($d['hear_about_us'])) $lines[] = 'Lead source: ' . $d['hear_about_us'];
        if (!empty($d['notes']))         $lines[] = "\nNotes: " . $d['notes'];
        return implode("\n", $lines);
    }

    private function buildStencilNotes(array $d): string
    {
        $lines = [];
        if (!empty($d['project_description'])) $lines[] = $d['project_description'];
        if (!empty($d['needed_by']))            $lines[] = 'Needed by: ' . $d['needed_by'];
        if (!empty($d['needs_paint']))          $lines[] = 'Needs paint: ' . $d['needs_paint'];
        if (!empty($d['paint_gallons']))        $lines[] = 'Est. paint gallons: ' . $d['paint_gallons'];
        if (!empty($d['hear_about_us']))        $lines[] = 'Lead source: ' . $d['hear_about_us'];
        return implode("\n", $lines);
    }

    // -------------------------------------------------------------------------
    // Email body builders
    // -------------------------------------------------------------------------


    private function buildContactEmail(array $d): string
    {
        return '<table style="width:100%;border-collapse:collapse">'
            . $this->intake->row('Name',    $d['first_name'] . ' ' . $d['last_name'])
            . $this->intake->row('Email',   $d['email'])
            . $this->intake->row('Phone',   $d['phone'] ?? '')
            . $this->intake->row('Subject', $d['subject'] ?? '')
            . $this->intake->row('Message', $d['message'] ?? '')
            . '</table>';
    }

    private function buildPaintEmail(array $d): string
    {
        $products = implode(', ', (array)($d['products'] ?? []));
        return '<table style="width:100%;border-collapse:collapse">'
            . $this->intake->row('Name',           $d['first_name'] . ' ' . $d['last_name'])
            . $this->intake->row('Company',        $d['company'] ?? '')
            . $this->intake->row('Email',          $d['email'])
            . $this->intake->row('Phone',          $d['phone'] ?? '')
            . $this->intake->row('Address',        trim(($d['address_line1'] ?? '') . ' ' . ($d['address_line2'] ?? '') . ' ' . ($d['city'] ?? '') . ', ' . ($d['state'] ?? '') . ' ' . ($d['postal_code'] ?? '')))
            . $this->intake->row('Uses paint?',    $d['uses_paint'] ?? '')
            . $this->intake->row('Current brand',  $d['current_brand'] ?? '')
            . $this->intake->row('Products',       $products)
            . $this->intake->row('How heard',      $d['hear_about_us'] ?? '')
            . $this->intake->row('Notes',          $d['notes'] ?? '')
            . '</table>';
    }

    private function buildStencilEmail(array $d): string
    {
        $attachment = !empty($d['attachment'])
            ? '<tr><td style="padding:6px 12px 6px 0;font-weight:600;color:#374151">Attachment</td><td style="padding:6px 0"><em>File uploaded — see USSCOS lead record</em></td></tr>'
            : '';
        return '<table style="width:100%;border-collapse:collapse">'
            . $this->intake->row('Name',        $d['first_name'] . ' ' . $d['last_name'])
            . $this->intake->row('Company',     $d['company'] ?? '')
            . $this->intake->row('Email',       $d['email'])
            . $this->intake->row('Phone',       $d['phone'] ?? '')
            . $this->intake->row('Address',     trim(($d['address_line1'] ?? '') . ' ' . ($d['address_line2'] ?? '') . ' ' . ($d['city'] ?? '') . ', ' . ($d['state'] ?? '') . ' ' . ($d['postal_code'] ?? '')))
            . $this->intake->row('Project',     $d['project_description'] ?? '')
            . $this->intake->row('Needed by',   $d['needed_by'] ?? '')
            . $this->intake->row('Needs paint?',$d['needs_paint'] ?? '')
            . $this->intake->row('Est. gallons',$d['paint_gallons'] ?? '')
            . $this->intake->row('How heard',   $d['hear_about_us'] ?? '')
            . $attachment
            . '</table>';
    }
}
