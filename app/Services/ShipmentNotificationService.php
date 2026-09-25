<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Mailer;
use App\Repositories\TrackingRepository;

/**
 * Telling the customer their order has shipped, without anybody retyping a tracking number.
 *
 * Today Sharon types the number onto the invoice and emails it. The number is already known
 * at the bench, so the retyping adds nothing except a delay and a chance to get it wrong.
 *
 * **Sending is gated by the environment, deliberately.** 28,646 customers in this database
 * have real email addresses and USSCOS is still in testing. The mode lives in `.env` and
 * not in a settings screen so that turning it on takes somebody on the server who meant to,
 * rather than a mis-click:
 *
 *   SHIPMENT_EMAILS=off    nothing sent — what would have gone is recorded as suppressed
 *   SHIPMENT_EMAILS=test   everything goes to SHIPMENT_EMAIL_TEST_TO
 *   SHIPMENT_EMAILS=on     the customer is emailed
 *
 * Off is the default, including when the variable is missing entirely. A system that
 * emails thousands of real customers because a setting was absent is not one anybody
 * should trust with their outbox.
 */
class ShipmentNotificationService
{
    public function __construct(
        private TrackingRepository $tracking = new TrackingRepository(),
    ) {
    }

    public function mode(): string
    {
        $mode = strtolower(trim((string)($_ENV['SHIPMENT_EMAILS'] ?? 'off')));

        return in_array($mode, ['off', 'test', 'on'], true) ? $mode : 'off';
    }

    /**
     * Record tracking numbers against an invoice.
     *
     * Accepts several at once — a parcel shipment is often four cartons and four numbers,
     * so they can be pasted in separated by commas, spaces or new lines rather than added
     * one form submission at a time.
     *
     * @return list<string> the numbers actually stored
     */
    public function addTracking(int $invoiceId, string $raw, ?int $shipViaId, string $type = 'parcel'): array
    {
        $numbers = preg_split('/[\s,;]+/', trim($raw)) ?: [];
        $stored  = [];

        foreach ($numbers as $number) {
            $number = trim($number);

            if ($number === '' || $this->tracking->exists($invoiceId, $number)) {
                continue;
            }

            $this->tracking->add([
                'invoice_id'      => $invoiceId,
                'ship_via_id'     => $shipViaId,
                'tracking_number' => $number,
                'tracking_type'   => $type,
                'created_by'      => Auth::id(),
            ]);

            $stored[] = $number;
        }

        // Keep the first number on the invoice itself: the invoice view, the packing slip
        // and the QuickBooks export all read that column.
        if ($stored !== []) {
            Database::statement(
                "UPDATE invoices SET tracking_number = COALESCE(NULLIF(tracking_number, ''), ?) WHERE id = ?",
                [$stored[0], $invoiceId]
            );
        }

        return $stored;
    }

    /**
     * Email the customer their tracking.
     *
     * Never throws. A shipment that went out is a fact; failing to announce it must not
     * roll anything back or stop the order closing, so every outcome is recorded on the
     * invoice instead and can be retried by hand.
     *
     * @return array{status:string, to:?string, message:string}
     */
    public function notify(int $invoiceId, bool $force = false): array
    {
        $invoice = Database::selectOne("
            SELECT i.*, c.company_name, c.email AS customer_email
            FROM invoices i
            JOIN customers c ON c.id = i.customer_id
            WHERE i.id = ?
        ", [$invoiceId]);

        if ($invoice === false) {
            return $this->result($invoiceId, 'not_found', null, 'Invoice not found.');
        }

        if (!$force && !empty($invoice['shipment_email_sent_at'])) {
            return $this->result($invoiceId, 'already_sent', $invoice['shipment_email_to'],
                'Already emailed on ' . date('j M Y', strtotime($invoice['shipment_email_sent_at'])) . '.');
        }

        $tracking = $this->tracking->forInvoice($invoiceId);

        if ($tracking === []) {
            return $this->result($invoiceId, 'no_tracking', null, 'No tracking number on this invoice yet.');
        }

        $customerEmail = trim((string)($invoice['customer_email'] ?? ''));

        if ($customerEmail === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            return $this->result($invoiceId, 'no_address', null,
                'No usable email address for ' . $invoice['company_name'] . '.');
        }

        $mode = $this->mode();

        if ($mode === 'off') {
            // Recorded rather than silently skipped, so it is obvious in testing that a
            // notification would have gone and did not.
            return $this->result($invoiceId, 'suppressed', $customerEmail,
                'Shipment emails are off — nothing sent. Would have gone to ' . $customerEmail . '.');
        }

        $to = $mode === 'test'
            ? trim((string)($_ENV['SHIPMENT_EMAIL_TEST_TO'] ?? ''))
            : $customerEmail;

        if ($to === '') {
            return $this->result($invoiceId, 'no_test_address', null,
                'SHIPMENT_EMAILS=test but SHIPMENT_EMAIL_TEST_TO is not set.');
        }

        $company = Database::selectOne('SELECT * FROM companies WHERE id = 1') ?: [];
        $subject = 'Your order has shipped — invoice ' . $invoice['invoice_number']
                 . ($invoice['po_number'] ? ' / PO ' . $invoice['po_number'] : '');

        $mailer = new Mailer();
        $sent   = $mailer->send(
            $to,
            ($mode === 'test' ? '[TEST] ' : '') . $subject,
            $this->body($invoice, $tracking, $company, $mode, $customerEmail),
            (string)($company['name'] ?? 'US Specialty Coatings'),
            (string)($_ENV['MAIL_FROM_ADDRESS'] ?? '')
        );

        if (!$sent) {
            Logger::error('Shipment email failed for invoice ' . $invoiceId . ': ' . $mailer->getLastError());

            return $this->result($invoiceId, 'failed', $to, 'Could not send: ' . $mailer->getLastError());
        }

        return $this->result($invoiceId, 'sent', $to,
            $mode === 'test' ? 'Test email sent to ' . $to . '.' : 'Emailed to ' . $to . '.');
    }

    public function forInvoice(int $invoiceId): array
    {
        return $this->tracking->forInvoice($invoiceId);
    }

    public function remove(int $id): void
    {
        $this->tracking->remove($id);
    }

    public function search(string $number): array
    {
        return $this->tracking->search($number);
    }

    public function shipViaByName(?string $name): ?array
    {
        return $this->tracking->shipViaByName($name);
    }

    private function result(int $invoiceId, string $status, ?string $to, string $message): array
    {
        $this->tracking->markEmail($invoiceId, $status, $to);

        return ['status' => $status, 'to' => $to, 'message' => $message];
    }

    /** Plain, short, and mostly the tracking link — which is the only thing being asked for. */
    private function body(array $invoice, array $tracking, array $company, string $mode, string $realRecipient): string
    {
        $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

        $rows = '';
        foreach ($tracking as $t) {
            $label  = $t['tracking_type'] === 'pro' ? 'PRO number'
                    : ($t['tracking_type'] === 'bol' ? 'BOL' : 'Tracking');
            $number = $t['url']
                ? '<a href="' . $e($t['url']) . '" style="color:#0A3D91;font-weight:600">' . $e($t['tracking_number']) . '</a>'
                : '<strong>' . $e($t['tracking_number']) . '</strong>';

            $rows .= '<tr>'
                   . '<td style="padding:6px 14px 6px 0;color:#6b7280;font-size:14px">'
                   . $e($label) . ($t['carrier'] ? ' · ' . $e($t['carrier']) : '') . '</td>'
                   . '<td style="padding:6px 0;font-size:15px">' . $number . '</td>'
                   . '</tr>';
        }

        $banner = $mode === 'test'
            ? '<div style="background:#fffbeb;border:1px solid #fcd34d;color:#92400e;padding:10px 14px;'
              . 'border-radius:6px;margin-bottom:18px;font-size:13px">'
              . 'Test message. In live running this would have gone to ' . $e($realRecipient) . '.</div>'
            : '';

        $shipped = $invoice['ship_date'] ? date('j F Y', strtotime((string)$invoice['ship_date'])) : '';

        return '<div style="font-family:Arial,Helvetica,sans-serif;max-width:560px;color:#111;line-height:1.5">'
            . $banner
            . '<p style="font-size:16px;margin:0 0 14px">Your order has shipped'
            . ($shipped ? ' on ' . $e($shipped) : '') . '.</p>'
            . '<table style="border-collapse:collapse;margin:0 0 18px">'
            . '<tr><td style="padding:6px 14px 6px 0;color:#6b7280;font-size:14px">Invoice</td>'
            . '<td style="padding:6px 0;font-size:15px"><strong>' . $e($invoice['invoice_number']) . '</strong></td></tr>'
            . ($invoice['po_number']
                ? '<tr><td style="padding:6px 14px 6px 0;color:#6b7280;font-size:14px">Your PO</td>'
                  . '<td style="padding:6px 0;font-size:15px">' . $e($invoice['po_number']) . '</td></tr>'
                : '')
            . $rows
            . '</table>'
            . '<p style="font-size:14px;color:#6b7280;margin:0 0 6px">'
            . 'Reply to this email if anything is not right.</p>'
            . '<p style="font-size:14px;margin:18px 0 0"><strong>'
            . $e($company['name'] ?? 'US Specialty Coatings') . '</strong></p>'
            . '</div>';
    }
}
