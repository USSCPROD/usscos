<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

/**
 * What the signed-in user is allowed to see.
 *
 * Reps and distributors are limited to their own customers, and to the invoices and
 * sales orders belonging to those customers. Everyone else sees everything.
 *
 * The link is `customers.sales_rep_id` -> `sales_reps.id`, and the rep's login is joined
 * through `sales_reps.user_id`. `customers.rep_id` is the older column from migration 027
 * and is deliberately not used here — the QuickBooks import populates `sales_rep_id`,
 * which is what "the account is flagged as that rep's customer" actually means.
 *
 * Scoping is expressed as SQL fragments applied inside repositories rather than hidden in
 * views, so a guessed URL or an unfiltered list can't leak another rep's customers.
 *
 * A restricted user whose login isn't linked to any rep record sees NOTHING rather than
 * everything. Failing closed matters: an unlinked account is a misconfiguration, and the
 * safe reading of "no rep record" is "no customers", not "all customers".
 */
class AccessScope
{
    private const RESTRICTED_ROLES = ['rep', 'distributor'];

    private static ?array $cache = null;

    /** True when the current user only sees their own customers. */
    public static function isRestricted(): bool
    {
        $user = Auth::user();

        return $user !== null && in_array($user['role'] ?? '', self::RESTRICTED_ROLES, true);
    }

    /**
     * The sales_reps.id tied to the current login, or null if there isn't one.
     *
     * Cached per request — this is consulted by nearly every list query.
     */
    public static function repId(): ?int
    {
        if (self::$cache !== null) {
            return self::$cache['rep_id'];
        }

        $userId = Auth::id();
        $repId  = null;

        if ($userId !== null) {
            $row = Database::selectOne(
                'SELECT id FROM sales_reps WHERE user_id = ? AND is_active = 1 LIMIT 1',
                [$userId]
            );

            $repId = $row === false ? null : (int)$row['id'];
        }

        self::$cache = ['rep_id' => $repId];

        return $repId;
    }

    /**
     * A condition restricting a customers query, as [sql, params].
     *
     * Returns ['', []] for unrestricted users so callers can append unconditionally.
     *
     * @param string $alias table alias for `customers`
     */
    public static function customerCondition(string $alias = 'c'): array
    {
        if (!self::isRestricted()) {
            return ['', []];
        }

        $repId = self::repId();

        if ($repId === null) {
            return ['1=0', []];      // fail closed
        }

        return ["{$alias}.sales_rep_id = ?", [$repId]];
    }

    /**
     * A condition restricting a query on any table with a `customer_id`.
     *
     * Used for invoices and sales orders — a rep sees a document because they own the
     * customer, not because their name is on the document.
     *
     * @param string $alias table alias for the document table
     */
    public static function customerOwnedCondition(string $alias): array
    {
        if (!self::isRestricted()) {
            return ['', []];
        }

        $repId = self::repId();

        if ($repId === null) {
            return ['1=0', []];
        }

        return [
            "{$alias}.customer_id IN (SELECT id FROM customers WHERE sales_rep_id = ?)",
            [$repId],
        ];
    }

    /**
     * As customerOwnedCondition(), but with a named placeholder.
     *
     * Some repositories bind by name and PDO won't mix the two styles in one statement.
     *
     * @return array{0:string,1:array<string,int>}
     */
    public static function customerOwnedConditionNamed(string $alias, string $key = ':scope_rep'): array
    {
        if (!self::isRestricted()) {
            return ['', []];
        }

        $repId = self::repId();

        if ($repId === null) {
            return ['1=0', []];
        }

        return [
            "{$alias}.customer_id IN (SELECT id FROM customers WHERE sales_rep_id = {$key})",
            [$key => $repId],
        ];
    }

    /** True if the current user may see this customer. */
    public static function canSeeCustomer(?int $customerId): bool
    {
        if (!self::isRestricted()) {
            return true;
        }

        if ($customerId === null) {
            return false;
        }

        $repId = self::repId();

        if ($repId === null) {
            return false;
        }

        $row = Database::selectOne(
            'SELECT 1 AS ok FROM customers WHERE id = ? AND sales_rep_id = ?',
            [$customerId, $repId]
        );

        return $row !== false;
    }

    /** Whether the company-wide financial reporting is available to this user. */
    public static function canSeeCompanyFinancials(): bool
    {
        return !self::isRestricted();
    }

    /** Test seam — forget the cached rep lookup. */
    public static function reset(): void
    {
        self::$cache = null;
    }
}
