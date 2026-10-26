<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Core\Repository;

/**
 * Everything the public site reads. Separate from ProductRepository on purpose.
 *
 * A storefront query is not an internal one wearing a different hat. It must never
 * return an inactive product, a raw material, or anything we make for somebody else
 * under toll manufacturing, and it must never expose cost. Keeping those rules in
 * one class means they are applied by construction rather than remembered at each
 * call site — and a missed `is_active = 1` on a public page is not a bug that waits
 * politely to be found.
 */
class StorefrontRepository extends Repository
{
    protected function getTable(): string
    {
        return 'products';
    }

    /**
     * Columns the public is allowed to see. Written out rather than SELECT *, because
     * `cost`, `dist_price` and the vendor columns live on the same row and a widening
     * query is how they end up in a page's HTML.
     */
    private const PUBLIC_COLUMNS = "
        p.id, p.sku, p.name, p.short_description, p.website_price, p.website_image_url,
        p.gross_weight, p.weight_unit, p.uom_code, p.units_per_case, p.qty_on_hand,
        p.variant_group, p.variant_label, p.variant_order,
        b.name AS brand_name";

    /** Products never shown publicly, whatever category they are filed under. */
    private const PUBLIC_WHERE = "
        p.is_active = 1
        AND COALESCE(p.item_type,'') <> 'raw_material'
        AND NOT EXISTS (
            SELECT 1 FROM product_categories pc
            JOIN categories c ON c.id = pc.category_id
            WHERE pc.product_id = p.id AND c.name = 'Toll Manufacturing'
        )";

    /**
     * The products under one storefront section.
     *
     * @param string[] $categoryNames
     * @param string[] $brandNames
     * @return array<int,array<string,mixed>>
     */
    public function section(array $categoryNames, array $brandNames = [], int $limit = 60, int $offset = 0, array $namePatterns = []): array
    {
        [$sql, $params] = $this->sectionWhere($categoryNames, $brandNames, $namePatterns);

        if ($sql === null) {
            return [];
        }

        // Priced items first: a page that opens on a wall of "call for pricing" reads
        // as a catalogue nobody maintains, even when the prices exist further down.
        $rows = Database::select("
            SELECT " . self::PUBLIC_COLUMNS . "
            FROM products p
            LEFT JOIN product_brands b ON b.id = p.brand_id
            WHERE " . self::PUBLIC_WHERE . " AND {$sql}
            ORDER BY (COALESCE(p.website_price,0) > 0) DESC, p.name
        ", $params);

        return array_slice($this->collapseVariants($rows), $offset, $limit);
    }

    /**
     * One card per listing, not one per variant.
     *
     * The members of a group are the same stencil in three thicknesses; showing all
     * three is how a catalogue of 107 things looks like 208. The cheapest priced
     * member leads the card, and 'from' tells the customer the others cost more.
     *
     * Collapsing happens here rather than in SQL because the paging has to count
     * listings: a GROUP BY would still let LIMIT 60 return 60 rows that are really
     * 30 products, and the page would say one thing while showing another.
     *
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function collapseVariants(array $rows): array
    {
        $out = [];

        foreach ($rows as $r) {
            $key = $r['variant_group'] ?? null;

            if ($key === null || $key === '') {
                $out[] = $r;
                continue;
            }

            $key = 'g:' . $key;
            $price = (float)($r['website_price'] ?? 0);

            if (!isset($out[$key])) {
                $out[$key] = $r + ['variant_count' => 1, 'variant_from' => $price > 0];
                continue;
            }

            $out[$key]['variant_count']++;

            // Lead with the cheapest priced member. An unpriced variant must never
            // displace a priced one, or the card reads "call for price" while the
            // listing actually has one.
            $cur = (float)($out[$key]['website_price'] ?? 0);

            if ($price > 0 && ($cur <= 0 || $price < $cur)) {
                $out[$key] = $r + [
                    'variant_count' => $out[$key]['variant_count'],
                    'variant_from'  => true,
                ];
            }
        }

        return array_values($out);
    }

    /** How many products a section holds, for the nav counts and paging. */
    public function sectionCount(array $categoryNames, array $brandNames = [], array $namePatterns = []): int
    {
        [$sql, $params] = $this->sectionWhere($categoryNames, $brandNames, $namePatterns);

        if ($sql === null) {
            return 0;
        }

        // Counts listings, matching what the page shows: COUNT(*) would report 208
        // stencils under a heading above 107 cards.
        return (int)Database::selectOne("
            SELECT COUNT(DISTINCT COALESCE(p.variant_group, CONCAT('#', p.id))) c
            FROM products p
            WHERE " . self::PUBLIC_WHERE . " AND {$sql}
        ", $params)['c'];
    }

    /**
     * Build the category/brand half of a section's WHERE clause.
     *
     * @return array{0:?string,1:array}
     */
    private function sectionWhere(array $categoryNames, array $brandNames, array $namePatterns = []): array
    {
        $or     = [];
        $params = [];

        if ($categoryNames !== []) {
            $in = implode(',', array_fill(0, count($categoryNames), '?'));
            $or[] = "EXISTS (SELECT 1 FROM product_categories pc
                             JOIN categories c ON c.id = pc.category_id
                             WHERE pc.product_id = p.id AND c.name IN ({$in}))";
            $params = array_merge($params, array_values($categoryNames));
        }

        if ($brandNames !== []) {
            $in = implode(',', array_fill(0, count($brandNames), '?'));
            $or[] = "EXISTS (SELECT 1 FROM product_brands bb
                             WHERE bb.id = p.brand_id AND bb.name IN ({$in}))";
            $params = array_merge($params, array_values($brandNames));
        }

        // Some storefront sections have no internal category and no brand of their own -
        // stencils are filed under nothing in particular - so a name match is the only
        // thing that gathers them. Deliberately a prefix/substring LIKE and not a
        // regex over everything: 'machine' as a name pattern for Striping Machines
        // would sweep in every jug of iGo MACHINE PAINT, which is paint, not a machine.
        if ($namePatterns !== []) {
            $likes = [];

            foreach ($namePatterns as $pat) {
                $likes[]  = 'p.name LIKE ?';
                $params[] = '%' . $pat . '%';
            }

            $or[] = '(' . implode(' OR ', $likes) . ')';
        }

        if ($or === []) {
            return [null, []];
        }

        return ['(' . implode(' OR ', $or) . ')', $params];
    }

    /** One product, by SKU. SKU rather than id so the URL survives a reimport. */
    public function productBySku(string $sku): array|false
    {
        return Database::selectOne("
            SELECT " . self::PUBLIC_COLUMNS . ", p.description, p.gs1_description,
                   p.height, p.width, p.depth, p.dim_unit, p.net_weight
            FROM products p
            LEFT JOIN product_brands b ON b.id = p.brand_id
            WHERE " . self::PUBLIC_WHERE . " AND p.sku = ?
            LIMIT 1
        ", [$sku]);
    }

    /** Best sellers for the homepage. Priced and described, because this is the shop window. */
    public function featured(int $limit = 12): array
    {
        return Database::select("
            SELECT " . self::PUBLIC_COLUMNS . "
            FROM products p
            LEFT JOIN product_brands b ON b.id = p.brand_id
            WHERE " . self::PUBLIC_WHERE . "
              AND COALESCE(p.website_price,0) > 0
              AND COALESCE(p.short_description,'') <> ''
            ORDER BY p.name
            LIMIT {$limit}
        ");
    }

    /**
     * Public search over name, SKU and description.
     *
     * Not named search(): Repository::search() takes a column list, and overriding it
     * with a different signature is a fatal the moment the class loads.
     */
    public function searchProducts(string $term, int $limit = 60): array
    {
        $like = '%' . $term . '%';

        return Database::select("
            SELECT " . self::PUBLIC_COLUMNS . "
            FROM products p
            LEFT JOIN product_brands b ON b.id = p.brand_id
            WHERE " . self::PUBLIC_WHERE . "
              AND (p.name LIKE ? OR p.sku LIKE ? OR p.short_description LIKE ?)
            ORDER BY (COALESCE(p.website_price,0) > 0) DESC, p.name
            LIMIT {$limit}
        ", [$like, $like, $like]);
    }
    /**
     * Is this value actually usable as an <img src>?
     *
     * products.website_image_url is populated for 36 products and every one of them
     * holds a link to a PAGE on the old WordPress sites, not to an image file. Feeding
     * those to an <img> renders a broken-image icon and the product's name as alt text,
     * which looks far worse than an honest "no photo yet" tile. So the column is
     * believed only when it actually names an image.
     */
    public static function isImageUrl(?string $url): bool
    {
        $url = trim((string)$url);

        if ($url === '' || !preg_match('~^https?://~i', $url)) {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH) ?? '';

        return (bool)preg_match('/\.(jpe?g|png|gif|webp|avif|svg)$/i', $path);
    }
    /**
     * The other thicknesses of the same stencil, for the picker on a product page.
     *
     * @return array<int,array<string,mixed>>
     */
    public function variantsOf(array $product): array
    {
        $group = trim((string)($product['variant_group'] ?? ''));

        if ($group === '') {
            return [];
        }

        return Database::select("
            SELECT p.sku, p.name, p.website_price, p.variant_label, p.variant_order
            FROM products p
            WHERE " . self::PUBLIC_WHERE . " AND p.variant_group = ?
            ORDER BY p.variant_order, p.sku
        ", [$group]);
    }
    /**
     * The name to show for a listing card.
     *
     * A card leads with the cheapest variant, so its name carries that variant's
     * thickness — '10" alphabet kit stencil — 3/32"' above the words '2 thicknesses'.
     * The listing is not the 3/32" one, so the label comes off the end.
     *
     * The trailing quote is optional in the match because the catalogue holds both
     * spellings: the imported names end '— 3/32"' and the older hand-entered ones
     * end '- 1/8' with no closing mark.
     */
    public static function listingName(array $row): string
    {
        $name  = (string)($row['name'] ?? '');
        $label = trim((string)($row['variant_label'] ?? ''));

        if ($label === '' || (int)($row['variant_count'] ?? 1) < 2) {
            return $name;
        }

        // str_replace, not rtrim. rtrim's second argument is a SET OF CHARACTERS, and
        // '\u{201D}' in single quotes is not a curly quote - it is the literal
        // characters \, u, {, 2, 0, 1, D, }. Trimming '3/32"' against that set strips
        // the quote and then the 2, leaving '3/3', which matches nothing.
        $bare = str_replace(['"', '”', '″'], '', $label);

        $stripped = preg_replace(
            '/[\s]*[—–-]?[\s]*' . preg_quote($bare, '/') . '["\x{201D}]?\s*$/u',
            '',
            $name
        );

        // Never return an empty title because the whole name happened to match.
        return ($stripped === null || trim($stripped) === '') ? $name : rtrim($stripped, " \t—–-");
    }
}
