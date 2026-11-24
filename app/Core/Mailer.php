<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Outbound mail, through the SMTP2GO API.
 *
 * There is no SMTP transport here and there never was: MAIL_HOST, MAIL_PORT, MAIL_USERNAME
 * and MAIL_PASSWORD were read into properties that nothing ever used, so the .env looked
 * like it configured an SMTP server while every message went out over HTTP. Removed along
 * with Mailgun on 24 Nov 2026 - a setting that appears to control something and does not is
 * worse than a missing one, because it gets changed in the belief that it will help.
 */
class Mailer
{
    private string $fromEmail;
    private string $fromName;
    private string $lastError = '';

    public function __construct()
    {
        $this->fromEmail = $_ENV['MAIL_FROM_ADDRESS'] ?? 'admin@usscos.com';
        $this->fromName  = $_ENV['MAIL_FROM_NAME']    ?? 'US Specialty Coatings';
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function send(string $to, string $subject, string $htmlBody, string $fromName = '', string $fromEmail = ''): bool
    {
        $fromEmail = $fromEmail ?: $this->fromEmail;
        $fromName  = $fromName  ?: $this->fromName;

        if (($_ENV['SMTP2GO_API_KEY'] ?? '') === '') {
            // Fails loudly rather than silently doing nothing. Mailgun used to be the
            // fallback here; the account is gone, so a missing key now means no mail can
            // be sent at all, and the caller needs to be able to say so.
            $this->lastError = 'No mail provider configured: SMTP2GO_API_KEY is not set.';
            Logger::error('Mailer error', ['reason' => 'SMTP2GO_API_KEY missing']);
            return false;
        }

        return $this->sendSmtp2go($to, $subject, $htmlBody, $fromName, $fromEmail);
    }

    /**
     * Through the SMTP2GO API, the provider the website and stencil shop already send with
     * (usscproducts.com is verified there and in its SPF record). The only provider - the
     * Mailgun account this used to fall back to no longer exists.
     */
    private function sendSmtp2go(string $to, string $subject, string $htmlBody, string $fromName, string $fromEmail): bool
    {
        $recipients = array_values(array_filter(array_map('trim', preg_split('/[,;]/', $to) ?: [])));

        if ($recipients === []) {
            $this->lastError = 'No recipient address.';
            return false;
        }

        $payload = [
            'sender'    => $fromName !== '' ? '"' . str_replace('"', '', $fromName) . '" <' . $fromEmail . '>' : $fromEmail,
            'to'        => $recipients,
            'subject'   => $subject,
            'html_body' => $htmlBody,
            'text_body' => trim(html_entity_decode(strip_tags($htmlBody), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
        ];

        try {
            $ch = curl_init('https://api.smtp2go.com/v3/email/send');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'X-Smtp2go-Api-Key: ' . $_ENV['SMTP2GO_API_KEY'],
                ],
                CURLOPT_POSTFIELDS     => json_encode($payload, JSON_THROW_ON_ERROR),
                CURLOPT_TIMEOUT        => 15,
            ]);

            $body   = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            $data = is_string($body) ? (json_decode($body, true)['data'] ?? []) : [];

            if ($status !== 200 || (int)($data['succeeded'] ?? 0) < 1) {
                $why = $data['error'] ?? ($data['failures'][0] ?? null);
                $this->lastError = is_string($why) && $why !== '' ? $why : ($curlErr ?: "SMTP2GO HTTP {$status}");
                Logger::error('Mailer error', ['provider' => 'smtp2go', 'status' => $status, 'body' => $body ?: $curlErr]);
                return false;
            }

            return true;

        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            Logger::error('Mailer error', ['provider' => 'smtp2go', 'error' => $e->getMessage()]);
            return false;
        }
    }

    /*
     * A hand-rolled SMTP client lived here - connect(), startTls(), smtp(), read() and
     * buildMessage(), about eighty lines of it. Nothing called any of it: send() has only
     * ever gone out over HTTP. It read $this->host and $this->port, which is why the .env
     * carried MAIL_HOST and MAIL_PORT that changed nothing.
     *
     * Removed 24 Nov 2026 with Mailgun. If SMTP is ever wanted, use a library rather than
     * reviving this - it disabled peer verification on the TLS context.
     */
}
