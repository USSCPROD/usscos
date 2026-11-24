<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * What a rep earns: the margin between what they sold at and wholesale.
 *
 * Chip, 24 Nov 2026: "Reps get wholesale pricing and whatever they sell it above is their
 * commission." So this is not a percentage of revenue. It is computed per LINE —
 *
 *     (unit price sold − wholesale) × quantity
 *
 * — and a line sold at wholesale earns nothing while a line sold below it earns a negative
 * figure, which is shown rather than floored at zero. A rep who discounted below their own
 * buy price needs to see that, and hiding it would make a loss look like a quiet month.
 *
 * WHOLESALE COMES FROM THE LINE where it was snapshotted at invoicing
 * (invoice_line_items.wholesale_price, migration 133), and only falls back to the current
 * price list for lines written before that column existed. The difference matters: a
 * figure from the snapshot is what was true at the time, and a figure from the price list
 * is what would be true if the sale happened today. Every total below reports how much of
 * it came from each, because a commission statement that mixes the two without saying so
 * is a statement that quietly changes after it has been read.
 *
 * Credit memos carry negative totals and negative line totals, so returns net off without
 * a special case — a returned case removes the commission that was earned on it.
 */
final class RepCommission
{
    /**
     * The wholesale ladder, best entitlement first.
     *
     * Mirrors the distributor rungs in CustomerPricing: dist_price_2026 is the current
     * distributor price, dist_price is what came across from QuickBooks and is a fallback
     * while the 2026 list is filled in. If a rep's buy price turns out NOT to be the
     * distributor price, this is the one place to change it.
     */
    private const WHOLESALE = ['dist_price_2026', 'dist_price'];

    /** SQL for wholesale: the snapshot if present, else today's price list. */
    private static function wholesaleExpr(): string
    {
        return 'COALESCE(ili.wholesale_price, ' . implode(', ', array_map(
            static fn (string $c) => "NULLIF(p.{$c}, 0)",
            self::WHOLESALE
        )) . ')';
    }

    /**
     * One rep's commission for a period.
     *
     * @return array{
     *   total:float, revenue:float, wholesale:float,
     *   lines:int, lines_snapshotted:int, lines_estimated:int,
     *   lines_no_wholesale:int, invoices:int
     * }
     */
    public static function summary(int $repId, string $from, string $to): array
    {
        $attributed = RepAttribution::EXPR;
        $wholesale  = self::wholesaleExpr();

        $row = Database::selectOne("
            SELECT
                -- line_count, not `lines`: LINES is a reserved word in MySQL and an
                -- unquoted alias is a syntax error. summary() therefore never ran at
                -- all until this was found on 24 Nov 2026 - the page only rendered
                -- while no rep was selected, which skips this query entirely.
                COUNT(*)                                   AS line_count,
                COUNT(DISTINCT i.id)                       AS invoices,
                COALESCE(SUM(ili.line_total), 0)           AS revenue,
                COALESCE(SUM({$wholesale} * ili.qty), 0)   AS wholesale,
                COALESCE(SUM(ili.line_total - ({$wholesale} * ili.qty)), 0) AS total,
                SUM(ili.wholesale_price IS NOT NULL)       AS lines_snapshotted,
                SUM(ili.wholesale_price IS NULL AND {$wholesale} IS NOT NULL) AS lines_estimated,
                SUM({$wholesale} IS NULL)                  AS lines_no_wholesale,
                -- Split, because the two have different fixes: a line with no product link
                -- is an import that never matched the QuickBooks item, while a linked
                -- product with no wholesale price is a gap in the price list.
                SUM(ili.product_id IS NULL)                AS lines_no_product,
                COALESCE(SUM(CASE WHEN {$wholesale} IS NULL THEN ili.line_total END), 0) AS revenue_uncosted
            FROM invoice_line_items ili
            JOIN invoices i   ON i.id = ili.invoice_id
            JOIN customers c  ON c.id = i.customer_id
            LEFT JOIN products p ON p.id = ili.product_id
            WHERE {$attributed} = :rep
              AND i.status <> 'void'
              AND i.invoice_date BETWEEN :from AND :to
        ", [':rep' => $repId, ':from' => $from, ':to' => $to]);

        return [
            'total'              => (float)($row['total'] ?? 0),
            'revenue'            => (float)($row['revenue'] ?? 0),
            'wholesale'          => (float)($row['wholesale'] ?? 0),
            'lines'              => (int)($row['line_count'] ?? 0),
            'invoices'           => (int)($row['invoices'] ?? 0),
            'lines_snapshotted'  => (int)($row['lines_snapshotted'] ?? 0),
            'lines_estimated'    => (int)($row['lines_estimated'] ?? 0),
            /*
             * A line with no wholesale price is EXCLUDED from the total, not inflated into
             * it: the margin expression is NULL for that line and SUM skips NULLs. So the
             * commission figure is UNDERSTATED by whatever those lines would have earned,
             * and revenue_uncosted says how much was sold through them.
             *
             * Understating is the safer direction - it pays nobody for a line nobody can
             * cost - but it has to be visible, because a rep whose figure silently omits
             * 40% of their lines will believe it and then dispute the pay run.
             */
            'lines_no_wholesale' => (int)($row['lines_no_wholesale'] ?? 0),
            'lines_no_product'   => (int)($row['lines_no_product'] ?? 0),
            'revenue_uncosted'   => (float)($row['revenue_uncosted'] ?? 0),
        ];
    }

    /** The individual lines behind the figure, so it can be checked against reality. */
    public static function lines(int $repId, string $from, string $to, int $limit = 500): array
    {
        $attributed = RepAttribution::EXPR;
        $wholesale  = self::wholesaleExpr();

        $stmt = Database::connection()->prepare("
            SELECT i.id AS invoice_id, i.invoice_number, i.invoice_date,
                   c.company_name,
                   COALESCE(p.sku, ili.quickbooks_item) AS sku,
                   ili.description, ili.qty, ili.unit_price, ili.line_total,
                   {$wholesale}                         AS wholesale,
                   ili.wholesale_price IS NOT NULL      AS is_snapshot,
                   (ili.line_total - ({$wholesale} * ili.qty)) AS margin
            FROM invoice_line_items ili
            JOIN invoices i   ON i.id = ili.invoice_id
            JOIN customers c  ON c.id = i.customer_id
            LEFT JOIN products p ON p.id = ili.product_id
            WHERE {$attributed} = :rep
              AND i.status <> 'void'
              AND i.invoice_date BETWEEN :from AND :to
            ORDER BY i.invoice_date DESC, i.id DESC, ili.sort_order
            LIMIT :limit
        ");
        $stmt->bindValue(':rep',  $repId, \PDO::PARAM_INT);
        $stmt->bindValue(':from', $from);
        $stmt->bindValue(':to',   $to);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** Every selling rep's commission for a period, for the overview. */
    public static function byRep(string $from, string $to): array
    {
        $attributed = RepAttribution::EXPR;
        $wholesale  = self::wholesaleExpr();

        return Database::select("
            SELECT {$attributed} AS sales_rep_id,
                   sr.name       AS rep_name,
                   COUNT(DISTINCT i.id)                     AS invoices,
                   COALESCE(SUM(ili.line_total), 0)         AS revenue,
                   COALESCE(SUM(ili.line_total - ({$wholesale} * ili.qty)), 0) AS total,
                   SUM({$wholesale} IS NULL)                AS lines_no_wholesale
            FROM invoice_line_items ili
            JOIN invoices i      ON i.id = ili.invoice_id
            JOIN customers c     ON c.id = i.customer_id
            LEFT JOIN products p ON p.id = ili.product_id
            JOIN sales_reps sr   ON sr.id = {$attributed}
            WHERE i.status <> 'void'
              AND i.invoice_date BETWEEN :from AND :to
              AND sr.rep_type IN ('person','employee','owner')
              -- is_active too, matching AdminRepository::activeSellingReps(). Without it
              -- this listed retired reps the goals screen does not, so the two screens
              -- disagreed about who the team is.
              AND sr.is_active = 1
            GROUP BY {$attributed}, sr.name
            ORDER BY total DESC
        ", [':from' => $from, ':to' => $to]);
    }

    /** The wholesale price for a product right now, for snapshotting at invoice time. */
    public static function wholesaleFor(array $product): ?float
    {
        foreach (self::WHOLESALE as $column) {
            $value = $product[$column] ?? null;
            if ($value !== null && (float)$value > 0) {
                return round((float)$value, 4);
            }
        }
        return null;
    }
}
