<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;

/**
 * Which dashboard a role gets, and which panels appear on it.
 *
 * The dashboard was the same for everyone, which meant a salesperson opened the app
 * to company revenue, total accounts receivable, stock value and open purchase
 * orders. None of that is theirs to see, and none of it helps them sell anything.
 *
 * Panels are an ALLOW-LIST per audience, not a set of exclusions. A panel added later
 * appears for nobody until it is named here — so the failure mode of forgetting is a
 * missing panel somebody asks about, not company financials quietly appearing on a
 * rep's screen.
 *
 * This decides what is DRAWN. It is not a security boundary on its own: the figures
 * themselves are scoped in DashboardService, and access to the underlying pages is
 * enforced by middleware. A hidden panel whose data was still fetched would leak
 * through any other route that returns it.
 */
class DashboardAudience
{
    public const EXECUTIVE = 'executive';
    public const FINANCE   = 'finance';
    public const SALES     = 'sales';
    public const SHIPPING  = 'shipping';

    /** role => audience. Anything unlisted falls back to SALES, the narrowest useful view. */
    private const BY_ROLE = [
        'owner'       => self::EXECUTIVE,
        'admin'       => self::EXECUTIVE,
        'manager'     => self::EXECUTIVE,
        'bookkeeper'  => self::FINANCE,
        'shipping'    => self::SHIPPING,
        'rep'         => self::SALES,
        'distributor' => self::SALES,
        'employee'    => self::SALES,
        'readonly'    => self::SALES,
    ];

    /**
     * Panels per audience, in the order they appear.
     *
     * 'kpis_company'  company money: revenue, A/R, stock, purchase orders
     * 'kpis_finance'  the receivables half of that, without the sales targets
     * 'kpis_mine'     the signed-in person's own numbers only
     * 'chart'         company revenue over time
     * 'focus'         system warnings - stale tax rates, stalled sync, reboot due
     * 'tasks'         my tasks
     * 'activity'      recent activity across the company
     * 'top_products'  what sells most, company-wide
     * 'my_pipeline'   my opportunities by stage
     * 'my_leads'      my leads needing a follow-up
     * 'my_quotes'     my quotes still awaiting an answer
     * 'my_overdue'    overdue invoices on MY accounts, for chasing
     * 'my_quiet'      my accounts that have gone quiet
     * 'shipping_work' orders waiting to be released, picked, labelled
     * 'ai'            the assistant
     */
    private const PANELS = [
        self::EXECUTIVE => ['kpis_company', 'chart', 'focus', 'tasks', 'activity', 'top_products', 'ai'],
        self::FINANCE   => ['kpis_finance', 'chart', 'focus', 'tasks', 'activity', 'ai'],
        self::SALES     => ['kpis_mine', 'my_pipeline', 'my_leads', 'my_quotes', 'my_overdue', 'my_quiet', 'tasks'],
        self::SHIPPING  => ['shipping_work', 'tasks', 'activity'],
    ];

    public static function forCurrentUser(): string
    {
        $role = Auth::user()['role'] ?? '';

        return self::BY_ROLE[$role] ?? self::SALES;
    }

    /** @return string[] */
    public static function panels(?string $audience = null): array
    {
        $audience ??= self::forCurrentUser();

        return self::PANELS[$audience] ?? self::PANELS[self::SALES];
    }

    public static function shows(string $panel, ?string $audience = null): bool
    {
        return in_array($panel, self::panels($audience), true);
    }

    /** What the heading says, so the page states plainly whose numbers these are. */
    public static function heading(?string $audience = null): string
    {
        return match ($audience ?? self::forCurrentUser()) {
            self::EXECUTIVE => 'Dashboard',
            self::FINANCE   => 'Finance',
            self::SHIPPING  => 'Shipping',
            default         => 'My Dashboard',
        };
    }
}
