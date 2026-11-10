<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Which price a customer pays.
 *
 * The price list has had four columns for a long time and nothing ever chose between
 * them: every order, to every customer, came in at retail. A distributor being billed
 * retail is not a visible fault — the figure looks like a price, the order saves, and
 * the only person who finds out is the customer.
 *
 * So the tier is decided here, once, and the line says which one it used.
 */
class CustomerPricing
{
    public const RETAIL     = 'Retail';
    public const DIST       = 'Distributor';
    public const STOCKING   = 'Stocking Distributor';
    public const REP        = 'Rep';

    /**
     * Columns to try, best entitlement first, retail last.
     *
     * dist_price_2026 is the distributor price — Chip, 10 November 2026. dist_price is
     * what came across from QuickBooks and is kept only as a fallback while the 2026
     * list is still being filled in: it is on 1,843 products against 150.
     *
     * stocking_dist is in the list but empty on every product today. A stocking
     * distributor therefore falls through to the distributor price, which is the right
     * answer until those numbers exist.
     */
    private const LADDER = [
        self::STOCKING => ['stocking_dist', 'dist_price_2026', 'dist_price', 'price'],
        self::DIST     => ['dist_price_2026', 'dist_price', 'price'],
        self::RETAIL   => ['price'],

        // 'Rep' is retired as a customer type — a rep is an employee, and USSC invoices
        // the customer rather than the rep. The rung stays because pricing reads the
        // string stored on the row: if an import ever brings one back, it prices as it
        // always did instead of silently dropping to retail.
        self::REP      => ['dist_price_2026', 'dist_price', 'price'],
    ];

    /** Human labels for the column actually used, so a fallback is visible. */
    private const LABELS = [
        'stocking_dist'   => 'stocking dist',
        'dist_price_2026' => 'distributor',
        'dist_price'      => 'distributor (QB)',
        'price'           => 'retail',
    ];

    /**
     * @param array<string,mixed> $product
     * @return array{price:float, column:string, label:string, fell_back:bool}
     */
    public static function priceFor(array $product, ?string $customerType): array
    {
        $type   = trim((string)$customerType);
        $ladder = self::LADDER[$type] ?? self::LADDER[self::RETAIL];

        foreach ($ladder as $i => $column) {
            $value = $product[$column] ?? null;

            if ($value !== null && (float)$value > 0) {
                return [
                    'price'     => round((float)$value, 2),
                    'column'    => $column,
                    'label'     => self::LABELS[$column] ?? $column,
                    // True when the entitled price was missing and something further
                    // down the list answered instead. Worth showing: a distributor
                    // quietly billed at retail is the fault this class exists to stop.
                    'fell_back' => $i > 0,
                ];
            }
        }

        return ['price' => 0.0, 'column' => 'price', 'label' => 'no price', 'fell_back' => true];
    }

    /** The columns any tier might read, for a SELECT. */
    public static function columns(): array
    {
        return ['price', 'dist_price', 'dist_price_2026', 'stocking_dist'];
    }
}
