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
 *
 * Since 24 Nov 2026 an AGREED price for a specific customer and product overrides the tier
 * entirely - see migration 131. The tier answers what a class of customer pays; an agreed
 * price is what this one was promised, and the second is not a refinement of the first.
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
        'agreed'          => 'agreed',
        'stocking_dist'   => 'stocking dist',
        'dist_price_2026' => 'distributor',
        'dist_price'      => 'distributor (QB)',
        'price'           => 'retail',
    ];

    /**
     * @param array<string,mixed> $product
     * @param int|null $customerId When given, an agreed price for this customer beats the
     *                             tier ladder entirely. Optional so existing callers keep
     *                             working unchanged and simply get tier pricing.
     * @param float $qty           Which volume break applies. Defaults to 1, so a caller
     *                             that does not know the quantity gets the any-quantity
     *                             price rather than the best one.
     * @return array{price:float, column:string, label:string, fell_back:bool, agreed:bool,
     *               min_qty:float|null, note:string|null}
     */
    public static function priceFor(
        array $product,
        ?string $customerType,
        ?int $customerId = null,
        float $qty = 1.0
    ): array {
        /*
         * An agreed price wins outright - it is not another rung on the ladder.
         *
         * The ladder answers "what does a distributor pay". Once somebody has negotiated a
         * figure for this account, the tier is no longer the question: a deal struck at
         * $38.50 does not become $41 because the customer type changed, and a deal below
         * the distributor price must not be overridden by the distributor price.
         */
        $productId = (int)($product['id'] ?? 0);

        if ($customerId !== null && $customerId > 0 && $productId > 0) {
            // A caller working through a list will have fetched these in one query and put
            // the row on the product as _agreed. Only a lone call hits the database here.
            $agreed = $product['_agreed'] ?? null;

            if ($agreed === null) {
                $agreed = (new \App\Repositories\CustomerPriceRepository())
                    ->priceFor($customerId, $productId, $qty);
            }

            if ($agreed !== null && (float)$agreed['unit_price'] > 0) {
                return [
                    'price'     => round((float)$agreed['unit_price'], 2),
                    'column'    => 'agreed',
                    'label'     => (float)$agreed['min_qty'] > 1
                        ? 'agreed @ ' . self::qty((float)$agreed['min_qty']) . '+'
                        : 'agreed',
                    // Never a fallback: this is the most specific answer there is, and
                    // colouring it as a fallback would read as a fault.
                    'fell_back' => false,
                    'agreed'    => true,
                    'min_qty'   => (float)$agreed['min_qty'],
                    'note'      => $agreed['note'],
                ];
            }
        }

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
                    'agreed'    => false,
                    'min_qty'   => null,
                    'note'      => null,
                ];
            }
        }

        return [
            'price'     => 0.0,
            'column'    => 'price',
            'label'     => 'no price',
            'fell_back' => true,
            'agreed'    => false,
            'min_qty'   => null,
            'note'      => null,
        ];
    }

    /**
     * A quantity as a person writes it: 50, not 50.00 and not 5.
     *
     * rtrim($n, '0') is the obvious way to drop trailing zeros and it is wrong: on "50" it
     * strips the zero from fifty. It only looks right while the value still carries its
     * decimals, so the bug appears the moment a caller casts to float first - which the
     * batched lookup does. Labelled a 50+ break as "5+" until it was caught on 24 Nov 2026.
     */
    public static function qty(float $qty): string
    {
        return $qty == (int)$qty
            ? (string)(int)$qty
            : rtrim(number_format($qty, 2, '.', ''), '0');
    }

    /** The columns any tier might read, for a SELECT. */
    public static function columns(): array
    {
        return ['price', 'dist_price', 'dist_price_2026', 'stocking_dist'];
    }
}
