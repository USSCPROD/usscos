<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;

class AuthMiddleware
{
    public function handle(Request $request, Response $response, callable $next): Response
    {
        if (Auth::guest()) {
            if ($request->isAjax()) {
                return $response->json(['error' => 'Unauthenticated.'], 401);
            }

            \App\Core\Session::flash('intended', $request->uri());
            return $response->redirect('/login');
        }

        return $next();
    }
}
