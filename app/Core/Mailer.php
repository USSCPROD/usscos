<?php

declare(strict_types=1);

namespace App\Core;

class Mailer
{
    private string $host;
    private int    $port;
    private string $username;
    private string $password;
    private string $fromEmail;
    private string $fromName;
    private string $lastError = '';

    public function __construct()
    {
        $this->host      = $_ENV['MAIL_HOST']         ?? 'localhost';
        $this->port      = (int)($_ENV['MAIL_PORT']   ?? 587);
        $this->username  = $_ENV['MAIL_USERNAME']     ?? '';
        $this->password  = $_ENV['MAIL_PASSWORD']     ?? '';
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

        $apiKey = $_ENV['MAILGUN_API_KEY'] ?? '';
        $domain = $_ENV['MAILGUN_DOMAIN']  ?? '';

        if ($apiKey === '' || $domain === '') {
            $this->lastError = 'Mailgun API key or domain not configured.';
            return false;
        }

        try {
            $from = $fromName ? "{$fromName} <{$fromEmail}>" : $fromEmail;

            $ch = curl_init("https://api.mailgun.net/v3/{$domain}/messages");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERPWD        => "api:{$apiKey}",
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => [
                    'from'    => $from,
                    'to'      => $to,
                    'subject' => $subject,
                    'text'    => strip_tags($htmlBody),
                    'html'    => $htmlBody,
                ],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT        => 15,
            ]);

            $body   = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($status !== 200) {
                $decoded = json_decode($body, true);
                $this->lastError = $decoded['message'] ?? "Mailgun HTTP {$status}";
                Logger::error('Mailer error', ['status' => $status, 'body' => $body]);
                return false;
            }

            return true;

        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            Logger::error('Mailer error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function connect(): mixed
    {
        $ctx = stream_context_create(['ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
            'crypto_method'     => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
        ]]);
        $prefix = $this->port === 465 ? 'ssl' : 'tcp';
        $socket = stream_socket_client(
            "{$prefix}://{$this->host}:{$this->port}",
            $errno, $errstr, 30,
            STREAM_CLIENT_CONNECT, $ctx
        );

        if (!$socket) {
            $this->lastError = "Could not connect to {$this->host}:{$this->port} — {$errstr}";
            return false;
        }

        stream_set_timeout($socket, 15);
        $this->read($socket); // greeting

        return $socket;
    }

    private function startTls(mixed $socket): void
    {
        $response = $this->smtp($socket, "STARTTLS");
        if (str_starts_with($response, '220')) {
            $method = STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                $method |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            }
            stream_socket_enable_crypto($socket, true, $method);
            $this->smtp($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        }
    }

    private function smtp(mixed $socket, string $cmd): string
    {
        fwrite($socket, $cmd . "\r\n");
        return $this->read($socket);
    }

    private function read(mixed $socket): string
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $response;
    }

    private function buildMessage(string $to, string $subject, string $html, string $fromEmail, string $fromName, string $boundary): string
    {
        $encodedSubject  = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
        $date            = date('r');

        return implode("\r\n", [
            "Date: {$date}",
            "To: {$to}",
            "From: {$encodedFromName} <{$fromEmail}>",
            "Reply-To: {$fromEmail}",
            "Subject: {$encodedSubject}",
            "MIME-Version: 1.0",
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
            "X-Mailer: USSCOS",
            "",
            "--{$boundary}",
            "Content-Type: text/plain; charset=UTF-8",
            "Content-Transfer-Encoding: base64",
            "",
            chunk_split(base64_encode(strip_tags($html))),
            "--{$boundary}",
            "Content-Type: text/html; charset=UTF-8",
            "Content-Transfer-Encoding: base64",
            "",
            chunk_split(base64_encode($html)),
            "--{$boundary}--",
        ]);
    }
}
