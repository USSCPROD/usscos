<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\WarehouseScope;

/**
 * Keeps a warehouse login inside the warehouse.
 *
 * Hiding the links is not enough — a typed URL, a bookmark or a stale tab would walk
 * straight past a sidebar that simply did not draw them. The route has to refuse.
 *
 * Applied to the whole authenticated group so a section added tomorrow is covered
 * without anyone remembering to think about it.
 */
class WarehouseOnlyMiddleware
{
    public function handle(Request $request, Response $response, callable $next): Response
    {
        if (WarehouseScope::isWarehouse() && !WarehouseScope::isAllowed($request->uri())) {
            if ($request->isAjax()) {
                return $response->json(['error' => 'Not available for this account.'], 403);
            }

            Session::flash('error', 'That section isn\'t available for your account.');

            return $response->redirect('/shipping');
        }

        return $next();
    }
}
