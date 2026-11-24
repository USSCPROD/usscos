<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\LeadRepository;

/**
 * Turning a website enquiry into a lead, for every door one comes in by.
 *
 * Two doors today: the webhook WordPress and the old usscos.com forms post to, and the
 * storefront's own contact and quote form. They used to be one private method on
 * WebhookController, which meant the storefront could only reach it by posting to
 * itself over HTTP. One service, so a lead from either looks the same in the list.
 */
class WebsiteLeadService
{
    /** Artwork a customer may attach. Vector formats included: that is what a logo arrives as. */
    public const FILE_TYPES = ['pdf', 'ai', 'eps', 'svg', 'png', 'jpg', 'jpeg', 'gif', 'webp'];
    public const FILE_MAX   = 25 * 1024 * 1024;

    private LeadRepository $leads;

    public function __construct()
    {
        $this->leads = new LeadRepository();
    }

    /**
     * The storefront form. Returns the lead id.
     *
     * $d: name, email, phone, org, topic, message, about (the product or page it was sent
     * from). $file: one entry of $_FILES, or null.
     *
     * Artwork goes to storage/, OUTSIDE the web root, and is reached only through the
     * signed-in /leads/artwork?f={name} route. A customer's SVG or PDF is not ours: served
     * from os.usscos.com an SVG would run with the staff application's origin.
     */
    public function siteEnquiry(array $d, ?array $file): int
    {
        $name  = trim((string)($d['name'] ?? ''));
        $parts = preg_split('/\s+/', $name, 2) ?: [''];
        $about = trim((string)($d['about'] ?? ''));
        $topic = trim((string)($d['topic'] ?? ''));
        $isStencil = stripos($about . ' ' . $topic, 'stencil') !== false;

        $attachment = $file !== null ? $this->storeArtwork($file) : null;

        $lines = [];

        if ($about !== '') {
            $lines[] = 'About: ' . $about;
        }

        if ($topic !== '') {
            $lines[] = 'Topic: ' . $topic;
        }

        $lines[] = '';
        $lines[] = trim((string)($d['message'] ?? ''));

        $routing = $this->routing();

        $id = $this->createLead([
            'first_name'  => $parts[0] ?? '',
            'last_name'   => $parts[1] ?? '',
            'company'     => trim((string)($d['org'] ?? '')) ?: null,
            'email'       => trim((string)($d['email'] ?? '')),
            'phone'       => trim((string)($d['phone'] ?? '')) ?: null,
            'source'      => $isStencil ? 'Website — Stencil Quote Request' : 'Website — Contact Form',
            'notes'       => implode("\n", $lines),
            'assigned_to' => $isStencil ? ($routing['stencil_user_id'] ?? null) : ($routing['general_user_id'] ?? null),
            'attachment'  => $attachment,
            // Not detected: this form IS on this host, so the host is the answer.
            'source_site' => self::thisHost(),
        ]);

        $this->notify(
            $isStencil ? $routing['stencil_email'] : $routing['general_email'],
            ($isStencil ? 'New Stencil Quote Request — ' : 'New Website Enquiry — ') . $name,
            '<table style="width:100%;border-collapse:collapse">'
            . $this->row('Name', $name)
            . $this->row('Organization', (string)($d['org'] ?? ''))
            . $this->row('Email', (string)($d['email'] ?? ''))
            . $this->row('Phone', (string)($d['phone'] ?? ''))
            . $this->row('About', $about)
            . $this->row('Topic', $topic)
            . $this->row('Message', (string)($d['message'] ?? ''))
            . $this->row('Artwork', $attachment !== null ? 'Attached — open the lead in USSCOS' : '')
            . '</table>'
        );

        return $id;
    }

    /**
     * A design from the online designers, sent as a quote request.
     *
     * $notes is the design, priced, with the proof's address. $logo, for a midfield logo:
     * ['tmp' => the stored original, 'name' => its file name]. It is copied into the lead
     * files, so the lead keeps it whatever happens to the designer's own upload folder.
     */
    public function designerRequest(array $d, string $about, string $notes, ?array $logo): int
    {
        $name  = trim((string)($d['name'] ?? ''));
        $parts = preg_split('/\s+/', $name, 2) ?: [''];
        $attachment = null;

        if ($logo !== null && is_file($logo['tmp'])) {
            $dir = BASE_PATH . '/storage/uploads/lead-files';
            $ext = strtolower(pathinfo((string)$logo['name'], PATHINFO_EXTENSION));

            if ((is_dir($dir) || mkdir($dir, 0775, true)) && in_array($ext, self::FILE_TYPES, true)) {
                $stored = bin2hex(random_bytes(16)) . '.' . $ext;

                if (copy($logo['tmp'], $dir . '/' . $stored)) {
                    $attachment = '/leads/files/' . $stored . '  (their logo, as uploaded)';
                }
            }
        }

        $routing = $this->routing();

        $id = $this->createLead([
            'first_name'  => $parts[0] ?? '',
            'last_name'   => $parts[1] ?? '',
            'company'     => trim((string)($d['org'] ?? '')) ?: null,
            'email'       => trim((string)($d['email'] ?? '')),
            'phone'       => trim((string)($d['phone'] ?? '')) ?: null,
            'source'      => 'Website — Stencil Designer',
            'notes'       => "About: {$about}\n\n{$notes}",
            'assigned_to' => $routing['stencil_user_id'] ?? null,
            'attachment'  => $attachment,
            'source_site' => self::thisHost(),
        ]);

        $this->notify(
            $routing['stencil_email'],
            'New Stencil Design — ' . $about . ' — ' . $name,
            '<table style="width:100%;border-collapse:collapse">'
            . $this->row('Name', $name)
            . $this->row('Organization', (string)($d['org'] ?? ''))
            . $this->row('Email', (string)($d['email'] ?? ''))
            . $this->row('Phone', (string)($d['phone'] ?? ''))
            . $this->row('Design', (string)preg_replace('#\n*Proof: /custom/proof\?\S+#', '', $notes))
            . $this->linkRow('Proof', preg_match('#Proof: (/custom/proof\?\S+)#', $notes, $m) ? $m[1] : '', 'View proof')
            . $this->linkRow('Lead', '/leads/' . $id, 'Open the lead in USSCOS')
            . $this->row('Logo', $attachment !== null ? 'Attached — open the lead in USSCOS' : '')
            . '</table>'
        );

        return $id;
    }

    /** Null when the upload is acceptable; otherwise what to tell the customer. */
    public static function fileProblem(?array $file): ?string
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
            return 'The file did not upload. Please try again, or email it to us.';
        }

        if ((int)$file['size'] > self::FILE_MAX) {
            return 'Please attach a file under 25 MB.';
        }

        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));

        return in_array($ext, self::FILE_TYPES, true) ? null
            : 'That kind of file can’t be attached. Please send a PDF, AI, EPS, SVG, PNG or JPG.';
    }

    /** Where staff fetch a stored artwork file from. */
    public static function filePath(string $name): ?string
    {
        if (!preg_match('/^[0-9a-f]{32}\.[a-z0-9]{2,4}$/', $name)) {
            return null;
        }

        $path = BASE_PATH . '/storage/uploads/lead-files/' . $name;

        return is_file($path) ? $path : null;
    }

    private function storeArtwork(array $file): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || self::fileProblem($file) !== null) {
            return null;
        }

        $dir = BASE_PATH . '/storage/uploads/lead-files';

        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            error_log('Lead artwork: cannot create ' . $dir);
            return null;
        }

        // Named here, never by the customer: the name they sent is kept only as text.
        $ext    = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        $stored = bin2hex(random_bytes(16)) . '.' . $ext;

        if (!move_uploaded_file((string)$file['tmp_name'], $dir . '/' . $stored)) {
            error_log('Lead artwork: move failed for ' . $stored);
            return null;
        }

        // Noted as /leads/files/{name}; the lead page turns that into its download link.
        return '/leads/files/' . $stored . '  (sent as "' . basename((string)$file['name']) . '")';
    }

    // ── Shared with the webhook ─────────────────────────────────────────────

    public function createLead(array $d): int
    {
        // Build notes — prepend address block if present, then content notes
        $notes = '';

        /*
         * The city line is assembled before it is tested, so an empty address produced
         * a bare "," — truthy, kept by array_filter, and printed at the top of every
         * contact-form lead. Build the line, then keep it only if there is something in it.
         */
        $cityLine = trim(trim(
            ($d['city'] ?? '') . ', ' . ($d['state'] ?? '') . ' ' . ($d['postal_code'] ?? '')
        ), " ,");

        $addressParts = array_filter([
            trim((string)($d['address_line1'] ?? '')),
            trim((string)($d['address_line2'] ?? '')),
            $cityLine,
        ], static fn ($part) => $part !== '');

        if ($addressParts) {
            $notes .= implode("\n", $addressParts) . "\n\n";
        }

        $notes .= $d['notes'] ?? '';

        if (!empty($d['attachment'])) {
            $notes .= "\n\nAttachment: " . $d['attachment'];
        }

        // source stays the enum the channel filters are built on; source_detail
        // carries which form it was, so a contact enquiry and a quote request differ.
        $company = ($d['company'] ?? '') ?: trim($d['first_name'] . ' ' . $d['last_name']);

        $id = $this->leads->insertWebsiteLead([
            'first_name'    => $d['first_name'],
            'last_name'     => $d['last_name'],
            // leads.company_name is NOT NULL.
            'company_name'  => $company,
            'email'         => $d['email'],
            'phone'         => $d['phone'] ?? null,
            'source_detail' => ($d['source'] ?? '') ?: null,
            'source_site'   => $d['source_site'] ?? null,
            'notes'         => trim($notes) ?: null,
            'rep_id'        => $d['assigned_to'] ?? null,
        ]);

        // No signed-in user: this runs for a visitor on the public site, so user_id stays
        // null and the timeline shows the event with no name against it. That is the
        // honest answer - nobody here did this, the website did.
        Activity::log(
            'lead.created',
            'Enquiry received from ' . ($d['source_site'] ?? 'the website')
                . (($d['source'] ?? '') !== '' ? ' (' . $d['source'] . ')' : '')
                . ' for ' . $company . '.'
                . (!empty($d['attachment']) ? ' Artwork attached.' : ''),
            ['subject_type' => 'Lead', 'subject_id' => $id, 'lead_id' => $id]
        );

        return $id;
    }

    /**
     * The host this request arrived on, for a form served by this application rather
     * than posted in from elsewhere. Run through the same allowlist as a declared host,
     * so a spoofed Host header cannot put arbitrary text on a staff page.
     */
    private static function thisHost(): ?string
    {
        return \App\Services\Web\LeadOrigin::detect(
            ['site' => (string)($_SERVER['HTTP_HOST'] ?? '')],
            []
        );
    }

    public function routing(): array
    {
        $map = $this->leads->routingSettings();

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

    /**
     * An email to staff. Through App\Core\Mailer (SMTP2GO): the server has no local mail
     * program, so PHP's mail() - which this used to call - accepted every message and
     * delivered none of them.
     */
    public function notify(string $to, string $subject, string $body): void
    {
        $mailer = new \App\Core\Mailer();

        if (!$mailer->send($to, $subject, $this->wrapEmail($subject, $body), 'USSCOS')) {
            error_log('staff email "' . $subject . '" to ' . $to . ' failed: ' . $mailer->getLastError());
        }
    }

    public function row(string $label, string $value): string
    {
        if ($value === '') return '';
        return '<tr><td style="padding:6px 12px 6px 0;font-weight:600;color:#374151;white-space:nowrap;vertical-align:top">' . htmlspecialchars($label) . '</td>'
             . '<td style="padding:6px 0;color:#111827">' . nl2br(htmlspecialchars($value)) . '</td></tr>';
    }

    /** A row whose value is a link to a page on this site ($path starts with /). */
    private function linkRow(string $label, string $path, string $text): string
    {
        if ($path === '') return '';
        $url = rtrim((string)config('app.url'), '/') . $path;
        return '<tr><td style="padding:6px 12px 6px 0;font-weight:600;color:#374151;white-space:nowrap;vertical-align:top">' . htmlspecialchars($label) . '</td>'
             . '<td style="padding:6px 0"><a href="' . htmlspecialchars($url) . '" style="color:#0A3D91">' . htmlspecialchars($text) . '</a></td></tr>';
    }

    private function wrapEmail(string $title, string $body): string
    {
        return '<!DOCTYPE html><html><body style="font-family:Arial,sans-serif;color:#111827;max-width:600px;margin:0 auto;padding:20px">
            <div style="background:#222b59;padding:16px 20px;border-radius:8px 8px 0 0">
                <h2 style="color:#fff;margin:0;font-size:1.1rem">' . htmlspecialchars($title) . '</h2>
            </div>
            <div style="border:1px solid #d1d5db;border-top:none;padding:20px;border-radius:0 0 8px 8px">
                ' . $body . '
                <p style="margin-top:24px;font-size:.8rem;color:#9ca3af">Sent automatically by USSCOS from the website. Log in to USSCOS to act on it.</p>
            </div>
        </body></html>';
    }
}
