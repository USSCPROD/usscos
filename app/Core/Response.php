<?php

declare(strict_types=1);

namespace App\Core;

class Response
{
    private int    $statusCode = 200;
    private array  $headers    = [];
    private string $body       = '';

    private static array $statusTexts = [
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        503 => 'Service Unavailable',
    ];

    public function status(int $code): static
    {
        $this->statusCode = $code;
        return $this;
    }

    public function header(string $key, string $value): static
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function body(string $body): static
    {
        $this->body = $body;
        return $this;
    }

    public function html(string $html, int $status = 200): static
    {
        return $this->status($status)
                    ->header('Content-Type', 'text/html; charset=UTF-8')
                    ->body($html);
    }

    public function json(array $data, int $status = 200): static
    {
        return $this->status($status)
                    ->header('Content-Type', 'application/json')
                    ->body(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function redirect(string $url, int $status = 302): static
    {
        return $this->status($status)->header('Location', $url);
    }

    public function redirectBack(string $fallback = '/'): static
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? $fallback;
        return $this->redirect($referer);
    }

    public function download(string $filePath, string $filename = ''): static
    {
        if (!file_exists($filePath)) {
            return $this->status(404)->body('File not found');
        }

        $filename = $filename ?: basename($filePath);
        return $this->header('Content-Type', mime_content_type($filePath) ?: 'application/octet-stream')
                    ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
                    ->header('Content-Length', (string) filesize($filePath))
                    ->body(file_get_contents($filePath));
    }

    public function send(): void
    {
        if (headers_sent()) {
            echo $this->body;
            return;
        }

        $statusText = self::$statusTexts[$this->statusCode] ?? 'Unknown';
        header("HTTP/1.1 {$this->statusCode} {$statusText}");

        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }

        echo $this->body;
    }

    public function getStatusCode(): int   { return $this->statusCode; }
    public function getHeaders(): array    { return $this->headers; }
    public function getBody(): string      { return $this->body; }

    public static function make(): static
    {
        return new static();
    }
}
