<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Services\CompanyAccess;

final class CompanyMiddleware
{
    public function handle(Request $request, Response $response, callable $next): Response
    {
        $path = parse_url($request->uri(), PHP_URL_PATH) ?: '/';
        $company = CompanyAccess::current();
        $previous = \App\Core\Session::get('active_company');
        if ($previous !== null && $previous !== $company) {
            \App\Core\Session::set('active_company', $company);
            \App\Core\Session::set('_csrf_token', bin2hex(random_bytes(32)));
            if (!$request->isGet()) {
                return $response->json(['error' => 'Company access changed. Reload before continuing.'], 409);
            }
        }
        if ($previous === null) {
            \App\Core\Session::set('active_company', $company);
        }
        $common = ['/companies', '/companies/switch', '/logout', '/settings/profile', '/settings/profile/password'];
        if (!in_array($path, $common, true) && $company !== 'ussc') {
            if ($request->isGet() && !$request->isAjax()) {
                return $response->redirect('/companies');
            }
            return $response->json(['error' => 'This operation is not available in the selected company.'], 403);
        }
        // User-management endpoints include generic /admin/users/{id}/delete and toggle.
        if (preg_match('#^/admin/users(?:/|$)#', $path) && !in_array(Auth::user()['role'] ?? '', ['owner', 'admin'], true)) {
            return $response->html('Only owners and admins may manage users.', 403);
        }
        if (preg_match('#^/admin/users/(\d+)(?:/|$)#', $path, $match)) {
            $target = (new \App\Repositories\AdminRepository())->findUser((int)$match[1]);
            if ($target && ($target['role'] ?? '') === 'owner' && (Auth::user()['role'] ?? '') !== 'owner') {
                return $response->html('Only an owner may manage an owner account.', 403);
            }
            if (!$request->isGet() && (int)$match[1] === Auth::id() && !str_ends_with($path, '/edit')) {
                return $response->html('Ask another owner to deactivate or delete your account.', 403);
            }
        }
        return $next()->header('Cache-Control', 'no-store');
    }
}
