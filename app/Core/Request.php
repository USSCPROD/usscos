<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    private array  $get;
    private array  $post;
    private array  $server;
    private array  $files;
    private array  $cookies;
    private array  $headers;
    private string $body;
    private array  $routeParams = [];

    public function __construct()
    {
        $this->get     = $_GET;
        $this->post    = $_POST;
        $this->server  = $_SERVER;
        $this->files   = $_FILES;
        $this->cookies = $_COOKIE;
        $this->body    = file_get_contents('php://input') ?: '';
        $this->headers = $this->parseHeaders();
    }

    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = str_replace('_', '-', ucwords(strtolower(substr($key, 5)), '_'));
                $headers[$header] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $header = str_replace('_', '-', ucwords(strtolower($key), '_'));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }

    public function method(): string
    {
        $method = $this->server['REQUEST_METHOD'] ?? 'GET';

        // Support method spoofing via _method field
        if ($method === 'POST' && isset($this->post['_method'])) {
            $spoofed = strtoupper($this->post['_method']);
            if (in_array($spoofed, ['PUT', 'PATCH', 'DELETE'])) {
                return $spoofed;
            }
        }

        return strtoupper($method);
    }

    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }

    public function isGet(): bool    { return $this->isMethod('GET'); }
    public function isPost(): bool   { return $this->isMethod('POST'); }
    public function isPut(): bool    { return $this->isMethod('PUT'); }
    public function isPatch(): bool  { return $this->isMethod('PATCH'); }
    public function isDelete(): bool { return $this->isMethod('DELETE'); }

    public function isAjax(): bool
    {
        return ($this->header('X-Requested-With') === 'XMLHttpRequest')
            || $this->wantsJson();
    }

    public function wantsJson(): bool
    {
        $accept = $this->header('Accept') ?? '';
        return str_contains($accept, 'application/json');
    }

    public function isSecure(): bool
    {
        return ($this->server['HTTPS'] ?? '') === 'on'
            || ($this->server['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    public function uri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $pos = strpos($uri, '?');
        return $pos !== false ? substr($uri, 0, $pos) : $uri;
    }

    public function fullUri(): string
    {
        return $this->server['REQUEST_URI'] ?? '/';
    }

    public function path(): string
    {
        return $this->uri();
    }

    public function host(): string
    {
        return $this->server['HTTP_HOST'] ?? 'localhost';
    }

    public function ip(): string
    {
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($this->server[$key])) {
                return explode(',', $this->server[$key])[0];
            }
        }
        return '0.0.0.0';
    }

    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    public function header(string $key, ?string $default = null): ?string
    {
        $normalized = str_replace('_', '-', ucwords(strtolower($key), '-'));
        return $this->headers[$normalized] ?? $default;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->sanitize($this->get[$key] ?? $default);
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->sanitize($this->post[$key] ?? $default);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post($key, $this->get($key, $default));
    }

    public function all(): array
    {
        return array_merge($this->get, $this->post);
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    public function has(string $key): bool
    {
        return isset($this->all()[$key]);
    }

    public function filled(string $key): bool
    {
        return $this->has($key) && $this->input($key) !== '' && $this->input($key) !== null;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->sanitize($this->get[$key] ?? $default);
    }

    public function file(string $key): array|null
    {
        return $this->files[$key] ?? null;
    }

    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    public function cookie(string $key, ?string $default = null): ?string
    {
        return $this->cookies[$key] ?? $default;
    }

    public function json(): array
    {
        $data = json_decode($this->body, true);
        return is_array($data) ? $data : [];
    }

    public function body(): string
    {
        return $this->body;
    }

    public function routeParam(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    private function sanitize(mixed $value): mixed
    {
        if (is_string($value)) {
            return htmlspecialchars(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        return $value;
    }

    public function raw(string $key, mixed $default = null): mixed
    {
        return array_merge($this->get, $this->post)[$key] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('Authorization');
        if ($auth && str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }
        return null;
    }
}
