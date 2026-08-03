<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class CsrfMiddleware
{
    private array $except = [
        '/api/*',
        '/webhook/*',
    ];

    public function handle(Request $request, Response $response, callable $next): Response
    {
        if ($this->isExcepted($request->uri())) {
            return $next();
        }

        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next();
        }

        $token = $request->input('_token')
            ?? $request->header('X-CSRF-TOKEN')
            ?? $request->header('X-XSRF-TOKEN');

        if (!$token || !Session::validateCsrf($token)) {
            if ($request->isAjax()) {
                return $response->json(['error' => 'CSRF token mismatch.'], 419);
            }
            return $response->html('CSRF token mismatch. Please go back and try again.', 419);
        }

        return $next();
    }

    private function isExcepted(string $uri): bool
    {
        foreach ($this->except as $pattern) {
            if (fnmatch($pattern, $uri)) {
                return true;
            }
        }
        return false;
    }
}
