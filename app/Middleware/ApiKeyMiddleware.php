<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;

/**
 * Shared-secret auth for machine-to-machine endpoints.
 *
 * The QuickBooks bridge runs unattended on a Windows box, so it can't use the session
 * login. It presents a key in the X-API-Key header instead.
 *
 * The key lives in .env as API_KEY and is never committed. If it isn't set, every
 * request is refused rather than allowed — a missing secret must fail closed.
 */
class ApiKeyMiddleware
{
    public function handle(Request $request, Response $response, callable $next): Response
    {
        $expected = trim((string)($_ENV['API_KEY'] ?? ''));

        if ($expected === '') {
            Logger::error('API request refused: API_KEY is not configured in .env');
            return $response->json(['error' => 'API access is not configured.'], 503);
        }

        $provided = (string)($request->header('X-API-Key') ?? '');

        // hash_equals guards against timing attacks on the comparison
        if ($provided === '' || !hash_equals($expected, $provided)) {
            Logger::error('API request refused: bad or missing X-API-Key', [
                'uri' => $request->uri(),
                'ip'  => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
            return $response->json(['error' => 'Unauthorized.'], 401);
        }

        return $next();
    }
}
