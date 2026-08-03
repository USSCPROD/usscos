<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class WebhookController extends Controller
{
    public function lead(Request $request, Response $response): Response
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (!$data || empty($data['form_type'])) {
            return $response->json(['error' => 'Invalid payload'], 400);
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
        $routing = $this->getRouting();

        $leadId = $this->createLead([
            'first_name' => $d['first_name'],
            'last_name'  => $d['last_name'],
            'email'      => $d['email'],
            'phone'      => $d['phone'] ?? null,
            'source'     => 'Website — Contact Form',
            'notes'      => $this->buildContactNotes($d),
            'assigned_to'=> $routing['general_user_id'] ?? null,
        ]);

        $this->sendNotification(
            $routing['general_email'],
            'New Contact Form Submission — ' . ($d['first_name'] . ' ' . $d['last_name']),
            $this->buildContactEmail($d)
        );
    }

    private function handlePaintQuote(array $d): void
    {
        $routing    = $this->getRouting();
        $assignedTo = $this->nextPaintRep($routing);

        $leadId = $this->createLead([
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
        ]);

        // Notify the assigned rep directly if we have their email, else fallback
        $notifyEmail = $routing['paint_email'];
        if ($assignedTo) {
            $rep = Database::selectOne('SELECT email FROM users WHERE id = ?', [$assignedTo]);
            if ($rep) $notifyEmail = $rep['email'];
        }

        $this->sendNotification(
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
        $routing = $this->getRouting();

        $leadId = $this->createLead([
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
        ]);

        $this->sendNotification(
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

    private function createLead(array $d): int
    {
        // Build notes — prepend address block if present, then content notes
        $notes = '';
        $addressParts = array_filter([
            $d['address_line1'] ?? '',
            $d['address_line2'] ?? '',
            trim(($d['city'] ?? '') . ', ' . ($d['state'] ?? '') . ' ' . ($d['postal_code'] ?? '')),
        ]);
        if ($addressParts) {
            $notes .= implode("\n", $addressParts) . "\n\n";
        }
        $notes .= $d['notes'] ?? '';
        if (!empty($d['attachment'])) {
            $notes .= "\n\nAttachment: " . $d['attachment'];
        }

        Database::insert(
            'INSERT INTO leads
                (first_name, last_name, company_name, email, phone,
                 source, notes, status, rep_id, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [
                $d['first_name'],
                $d['last_name'],
                ($d['company'] ?? '') ?: ($d['first_name'] . ' ' . $d['last_name']),
                $d['email'],
                $d['phone']       ?? null,
                'web',
                trim($notes)      ?: null,
                'new',
                $d['assigned_to'] ?? null,
            ]
        );

        return (int) Database::lastInsertId();
    }

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

    private function getRouting(): array
    {
        $settings = Database::select("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'lead_routing_%'");

        $map = [];
        foreach ($settings as $row) {
            $map[$row['setting_key']] = $row['setting_value'];
        }

        return [
            'stencil_email'    => $map['lead_routing_stencil_email']       ?? 'chip@usscproducts.com',
            'stencil_user_id'  => !empty($map['lead_routing_stencil_user_id'])  ? (int)$map['lead_routing_stencil_user_id']  : null,
            'paint_email'      => $map['lead_routing_paint_email']         ?? 'sales@usscproducts.com',
            'paint_pool'       => $map['lead_routing_paint_pool']          ?? '',
            'paint_next_index' => $map['lead_routing_paint_next_index']    ?? '0',
            'general_email'    => $map['lead_routing_general_email']       ?? 'sales@usscproducts.com',
            'general_user_id'  => !empty($map['lead_routing_general_user_id']) ? (int)$map['lead_routing_general_user_id'] : null,
        ];
    }

    // -------------------------------------------------------------------------
    // Email sending
    // -------------------------------------------------------------------------

    private function sendNotification(string $to, string $subject, string $body): void
    {
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: BusinessOS <noreply@usscproducts.com>\r\n";

        mail($to, $subject, $this->wrapEmail($subject, $body), $headers);
    }

    private function wrapEmail(string $title, string $body): string
    {
        return '<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;color:#111827;max-width:600px;margin:0 auto;padding:20px">
            <div style="background:#222b59;padding:16px 20px;border-radius:8px 8px 0 0">
                <h2 style="color:#fff;margin:0;font-size:1.1rem">' . htmlspecialchars($title) . '</h2>
            </div>
            <div style="border:1px solid #d1d5db;border-top:none;padding:20px;border-radius:0 0 8px 8px">
                ' . $body . '
                <p style="margin-top:24px;font-size:.8rem;color:#9ca3af">This lead was created automatically from a website form submission. Log in to BusinessOS to view and manage it.</p>
            </div>
        </body></html>';
    }

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

    private function row(string $label, string $value): string
    {
        if ($value === '') return '';
        return '<tr><td style="padding:6px 12px 6px 0;font-weight:600;color:#374151;white-space:nowrap;vertical-align:top">' . htmlspecialchars($label) . '</td>'
             . '<td style="padding:6px 0;color:#111827">' . nl2br(htmlspecialchars($value)) . '</td></tr>';
    }

    private function buildContactEmail(array $d): string
    {
        return '<table style="width:100%;border-collapse:collapse">'
            . $this->row('Name',    $d['first_name'] . ' ' . $d['last_name'])
            . $this->row('Email',   $d['email'])
            . $this->row('Phone',   $d['phone'] ?? '')
            . $this->row('Subject', $d['subject'] ?? '')
            . $this->row('Message', $d['message'] ?? '')
            . '</table>';
    }

    private function buildPaintEmail(array $d): string
    {
        $products = implode(', ', (array)($d['products'] ?? []));
        return '<table style="width:100%;border-collapse:collapse">'
            . $this->row('Name',           $d['first_name'] . ' ' . $d['last_name'])
            . $this->row('Company',        $d['company'] ?? '')
            . $this->row('Email',          $d['email'])
            . $this->row('Phone',          $d['phone'] ?? '')
            . $this->row('Address',        trim(($d['address_line1'] ?? '') . ' ' . ($d['address_line2'] ?? '') . ' ' . ($d['city'] ?? '') . ', ' . ($d['state'] ?? '') . ' ' . ($d['postal_code'] ?? '')))
            . $this->row('Uses paint?',    $d['uses_paint'] ?? '')
            . $this->row('Current brand',  $d['current_brand'] ?? '')
            . $this->row('Products',       $products)
            . $this->row('How heard',      $d['hear_about_us'] ?? '')
            . $this->row('Notes',          $d['notes'] ?? '')
            . '</table>';
    }

    private function buildStencilEmail(array $d): string
    {
        $attachment = !empty($d['attachment'])
            ? '<tr><td style="padding:6px 12px 6px 0;font-weight:600;color:#374151">Attachment</td><td style="padding:6px 0"><em>File uploaded — see BusinessOS lead record</em></td></tr>'
            : '';
        return '<table style="width:100%;border-collapse:collapse">'
            . $this->row('Name',        $d['first_name'] . ' ' . $d['last_name'])
            . $this->row('Company',     $d['company'] ?? '')
            . $this->row('Email',       $d['email'])
            . $this->row('Phone',       $d['phone'] ?? '')
            . $this->row('Address',     trim(($d['address_line1'] ?? '') . ' ' . ($d['address_line2'] ?? '') . ' ' . ($d['city'] ?? '') . ', ' . ($d['state'] ?? '') . ' ' . ($d['postal_code'] ?? '')))
            . $this->row('Project',     $d['project_description'] ?? '')
            . $this->row('Needed by',   $d['needed_by'] ?? '')
            . $this->row('Needs paint?',$d['needs_paint'] ?? '')
            . $this->row('Est. gallons',$d['paint_gallons'] ?? '')
            . $this->row('How heard',   $d['hear_about_us'] ?? '')
            . $attachment
            . '</table>';
    }
}
