<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AccessScope;

/**
 * Blocks reps and distributors from company-wide sections.
 *
 * They see their own sales and commissions, never the company's books — so accounting,
 * admin, purchasing and vendor pages are closed to them entirely. Hiding the nav links
 * is not enough on its own: the route has to refuse the request.
 *
 * Matching is by URI prefix and the middleware is applied to the whole authenticated
 * group, so a new route under one of these sections is covered the moment it is added.
 * Opt-in per route would mean remembering every time, which is how gaps appear.
 */
class InternalOnlyMiddleware
{
    /** Sections a rep or distributor may not open at all. */
    private const INTERNAL_PREFIXES = [
        '/accounting',
        '/admin',
        '/vendors',
        '/purchase-orders',
        '/purchasing',
        '/inventory',
        '/reports',
    ];

    public function handle(Request $request, Response $response, callable $next): Response
    {
        if (AccessScope::isRestricted() && $this->isInternalOnly($request->uri())) {
            if ($request->isAjax()) {
                return $response->json(['error' => 'Not available for this account.'], 403);
            }

            Session::flash('error', 'That section isn\'t available for your account.');

            return $response->redirect('/dashboard');
        }

        return $next();
    }

    /** True when the path sits under one of the internal-only sections. */
    private function isInternalOnly(string $uri): bool
    {
        $path = '/' . ltrim(parse_url($uri, PHP_URL_PATH) ?: '/', '/');

        foreach (self::INTERNAL_PREFIXES as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }
}
