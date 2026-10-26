<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;

/**
 * What a warehouse login may open.
 *
 * Shipping staff work from a shared tablet at the bench. They need the pull list, the
 * shipping queue and receiving, and nothing else — not customers, not pricing, not
 * accounting, not the cost of what they are holding.
 *
 * This is an ALLOW-LIST, the opposite of InternalOnlyMiddleware's deny-list, and the
 * difference is deliberate. A deny-list has to be updated every time a section is
 * added, and the failure mode of forgetting is that warehouse staff silently gain
 * access to the new section. An allow-list fails the other way: a new section is
 * closed to them until somebody decides otherwise, which is the safe direction for a
 * login that lives on a tablet in a shared space.
 */
class WarehouseScope
{
    /**
     * Roles that see only the warehouse.
     *
     * NOT 'shipping'. That role is the shipping department - it carries inventory
     * adjustment rights and belongs to the manager, who also enters orders and needs
     * most of the application. Putting it here locked her out of her own job.
     */
    private const ROLES = ['warehouse'];

    /**
     * Everything a warehouse login may reach.
     *
     * Logout is on the list because an account that cannot sign out of a shared tablet
     * is worse than one with a slightly wider reach.
     */
    private const ALLOWED = [
        '/shipping',
        '/receiving',
        '/logout',
        '/profile',
    ];

    public static function isWarehouse(): bool
    {
        $user = Auth::user();

        return $user !== null && in_array($user['role'] ?? '', self::ROLES, true);
    }

    /** True when this path is one a warehouse login may open. */
    public static function isAllowed(string $uri): bool
    {
        $path = '/' . ltrim(parse_url($uri, PHP_URL_PATH) ?: '/', '/');

        foreach (self::ALLOWED as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }
}
