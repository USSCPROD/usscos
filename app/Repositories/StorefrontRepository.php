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
        p.id, p.sku, p.name, p.website_name, p.website_short_description,
        p.short_description, p.website_price, p.website_image_url,
        p.gross_weight, p.weight_unit, p.uom_code, p.units_per_case, p.qty_on_hand,
        p.variant_group, p.variant_label, p.variant_order, p.variant_title, p.variant_axis,
        p.website_slug, p.website_size_slug,
        b.name AS brand_name,
        (SELECT pi.file_path FROM product_images pi
          WHERE pi.product_id = p.id
          ORDER BY pi.is_primary DESC, pi.sort_order, pi.id
          LIMIT 1) AS primary_image,
        -- The group shot, if one has been marked anywhere in this variant group. A
        -- listing is sold by the picture of the whole range, not by whichever colour
        -- the card happens to lead with.
        (SELECT pi.file_path FROM product_images pi
          JOIN products vp ON vp.id = pi.product_id
          WHERE pi.is_listing = 1
            AND p.variant_group IS NOT NULL AND p.variant_group <> ''
            AND vp.variant_group = p.variant_group
          LIMIT 1) AS listing_image";

    /** Category trees that are ours, not mirrored from WooCommerce. See treeChildren(). */
    private const OWN_TREES = ['stencils'];

    /** Products never shown publicly, whatever category they are filed under. */
    private const PUBLIC_WHERE = "
        p.is_active = 1
        AND p.publish_to_website = 1
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
     * The members of a group are the same stencil in several thicknesses; showing
     * them all is how a catalogue of 107 things looks like 208.
     *
     * The card leads with the DEFAULT variant — lowest variant_order, which is the
     * thickest cut — not the cheapest. Leading with the cheapest meant every card
     * advertised 3/32" while the thing people order is 1/8", and the thickness shown
     * is the one the product page opens on. A variant with no price never leads: a
     * listing that has a price should show it rather than 'call for best price'.
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
                $out[$key] = $r + ['variant_count' => 1, 'min_price' => $price > 0 ? $price : null];
                continue;
            }

            $out[$key]['variant_count']++;

            // The lowest price in the listing, for a 'From' on cards whose variants are
            // sizes: a NO PARKING card leading with 4" letters at $16.67 would otherwise
            // hide that 4" letters in 3/32" is $14.89.
            if ($price > 0 && ($out[$key]['min_price'] === null || $price < $out[$key]['min_price'])) {
                $out[$key]['min_price'] = $price;
            }

            $curPrice = (float)($out[$key]['website_price'] ?? 0);
            $curOrder = (int)($out[$key]['variant_order'] ?? PHP_INT_MAX);
            $order    = (int)($r['variant_order'] ?? PHP_INT_MAX);

            // A priced variant always beats an unpriced one. Between two priced
            // variants the lower variant_order wins, which is 1/8".
            $better = $curPrice <= 0
                ? $price > 0
                : ($price > 0 && $order < $curOrder);

            if ($better) {
                $out[$key] = $r + ['variant_count' => $out[$key]['variant_count'], 'min_price' => $out[$key]['min_price']];
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
                   p.height, p.width, p.depth, p.dim_unit, p.net_weight, p.video_urls,
                   p.long_description, p.website_description, p.spec_notes
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
        /*
         * The stencil shop's rules, because they are the ones customers learned there.
         *
         *   Every word must match: 'no parking 8' finds the 8" NO PARKING, not every
         *   stencil with a NO in it.
         *   96 inch, 96 in, 96" and 96” all mean 96"; 8 ft and 8' both mean 8'.
         *   A number matches whole: 6" does not find 96".
         *   A word matches the name, the listing title, the category, the size or
         *   the SKU.
         *   Products whose own name holds every word come first.
         *
         * Filtered in PHP rather than SQL: those rules are not LIKE, and the published
         * catalogue is a couple of thousand rows, read once per search.
         */
        $words = self::searchWords($term);

        if ($words === []) {
            return [];
        }

        $rows = Database::select("
            SELECT " . self::PUBLIC_COLUMNS . ", p.short_description AS sd,
                   (SELECT GROUP_CONCAT(c.name SEPARATOR ' ') FROM product_categories pc
                      JOIN categories c ON c.id = pc.category_id WHERE pc.product_id = p.id) AS cats
            FROM products p
            LEFT JOIN product_brands b ON b.id = p.brand_id
            WHERE " . self::PUBLIC_WHERE . "
            ORDER BY (COALESCE(p.website_price,0) > 0) DESC, p.name
        ");

        $hits = [];

        foreach ($rows as $r) {
            $text = mb_strtolower(implode(' ', [
                $r['name'], $r['website_name'], $r['variant_title'], $r['variant_label'],
                $r['sku'], $r['cats'], $r['brand_name'], $r['sd'],
            ]));

            foreach ($words as $w) {
                if (!self::wordIn($text, $w)) {
                    continue 2;
                }
            }

            $title = mb_strtolower((string)($r['website_name'] ?: $r['name']));
            $named = true;

            foreach ($words as $w) {
                if (!preg_match('/^\d/', $w) && !self::wordIn($title, $w)) {
                    $named = false;
                    break;
                }
            }

            $r['_score'] = $named ? 0 : 1;
            $hits[] = $r;
        }

        $listings = $this->collapseVariants($hits);

        usort($listings, static fn($a, $b) =>
            [$a['_score'], mb_strlen((string)($a['website_name'] ?: $a['name']))]
            <=> [$b['_score'], mb_strlen((string)($b['website_name'] ?: $b['name']))]);

        return array_slice($listings, 0, $limit);
    }

    /** '96 inch no parking' → ['96"', 'no', 'parking']. */
    public static function searchWords(string $q): array
    {
        $q = mb_strtolower($q);
        $q = (string)preg_replace('/(\d)\s*(?:inches|inch|in\b|["“”″])/u', '$1"', $q);
        $q = (string)preg_replace("/(\d)\s*(?:feet|foot|ft\b|['‘’′])/u", "$1'", $q);

        return array_values(array_filter(preg_split('/[\s,]+/u', $q) ?: [], static fn($w) => $w !== ''));
    }

    private static function wordIn(string $text, string $w): bool
    {
        if (preg_match('/^\d/', $w)) {
            return (bool)preg_match('/(^|[^\d.\/])' . preg_quote($w, '/') . '/u', $text);
        }

        return str_contains($text, $w)
            || (mb_strlen($w) > 3 && str_ends_with($w, 's') && str_contains($text, mb_substr($w, 0, -1)));
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
            SELECT p.sku, p.name, p.website_price, p.variant_label, p.variant_order, p.variant_title, p.variant_axis,
                   p.website_slug, p.website_size_slug, p.website_short_description
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

        // A name typed for the website beats everything: it is the most deliberate
        // thing anyone can say about how this product should be called.
        $web = trim((string)($row['website_name'] ?? ''));

        if ($web !== '') {
            return $web;
        }

        // Then the grouping's own title. It is only ever set deliberately, and a
        // product line with one member today still deserves its own name: DuraGlow is
        // not a shade of DuraStripe T-Tip.
        $title = trim((string)($row['variant_title'] ?? ''));

        if ($title !== '') {
            return $title;
        }

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
    /**
     * Every image for a product, primary first, for the gallery on its page.
     *
     * @return array<int,array<string,mixed>>
     */
    public function imagesOf(int $productId): array
    {
        // This color's own photographs first, then the group shot - which comes last
        // even when it was uploaded against this very product, because on a product
        // page the customer has already chosen a color and wants to see that can.
        // A color with no photo of its own still shows the range rather than an
        // empty frame.
        return Database::select("
            SELECT pi.file_path, pi.file_name, pi.caption,
                   (pi.product_id = :pid) AS own
            FROM product_images pi
            JOIN products p ON p.id = pi.product_id
            WHERE pi.product_id = :pid2
               OR (pi.is_listing = 1
                   AND p.variant_group IS NOT NULL AND p.variant_group <> ''
                   AND p.variant_group = (SELECT variant_group FROM products WHERE id = :pid3))
            ORDER BY pi.is_listing ASC, own DESC, pi.is_primary DESC, pi.sort_order, pi.id
        ", ['pid' => $productId, 'pid2' => $productId, 'pid3' => $productId]);
    }

    /**
     * The image to show for a row, or null when there is honestly none.
     *
     * An uploaded file wins over website_image_url: the uploaded one is ours and we
     * know it is an image, while that column holds links to PAGES on the old
     * WordPress sites for every product that has it set.
     */
    public static function imageFor(array $row): ?string
    {
        // The group shot wins on a card: the card stands for the whole listing.
        $listing = trim((string)($row['listing_image'] ?? ''));

        if ($listing !== '') {
            return $listing;
        }

        $uploaded = trim((string)($row['primary_image'] ?? ''));

        if ($uploaded !== '') {
            return $uploaded;
        }

        return self::isImageUrl($row['website_image_url'] ?? null)
            ? (string)$row['website_image_url']
            : null;
    }
    /**
     * The child categories of a live-tree section that actually hold something.
     *
     * Read from the category tree rather than listed in config, so the menu cannot
     * drift from the categories it points at: file products into a category and it
     * appears, empty one and it goes.
     *
     * The count follows descendants, because a child with its own children owns
     * nothing directly and would otherwise look empty and be hidden.
     *
     * @return array<int,array<string,mixed>>
     */
    public function treeChildren(string $parentSlug): array
    {
        // A tree mirrored from the old WooCommerce site carries its slug in external_ref;
        // a tree of our own (Stencils) is found by its slug.
        // Our own trees, two levels deep with short addresses: Endzone under Sports,
        // each chain under Retail, /shop/stencils/retail-target. Named rather than
        // detected - Stencils still carries the WC: marker from the old site's mirror.
        if (in_array($parentSlug, self::OWN_TREES, true)) {
            $own = Database::selectOne(
                "SELECT id, slug FROM categories WHERE slug = ? AND parent_id IS NULL AND is_active = 1 LIMIT 1",
                [$parentSlug]
            );

            return $own ? $this->childrenOf((int)$own['id'], (string)$own['slug'], 2) : [];
        }

        // The paint trees mirrored from WooCommerce: one level, addresses as they were.
        $wc = Database::selectOne(
            "SELECT id FROM categories WHERE external_ref = ? AND is_active = 1 LIMIT 1",
            ['WC:' . $parentSlug]
        );

        return $wc ? $this->childrenOf((int)$wc['id'], '', 1) : [];
    }

    /**
     * The children of a category that hold something, with their own children.
     *
     * Sorted by sort_order, then name: the stencil categories are in the order the
     * stencil shop showed them, and the WooCommerce trees (all sort_order 0) stay A-Z.
     *
     * URL slugs drop the root's slug from the front, so the stencil category
     * stencils-words is /shop/stencils/words and the chain stencils-retail-target is
     * /shop/stencils/retail-target.
     */
    private function childrenOf(int $parentId, string $rootSlug, int $depth): array
    {
        $out = [];

        foreach (Database::select(
            "SELECT id, name, slug, description, website_copy, image_path FROM categories
             WHERE parent_id = ? AND is_active = 1 ORDER BY sort_order, name",
            [$parentId]
        ) as $child) {
            $seen = [];
            $n = $this->categoryReach((int)$child['id'], $seen);

            if ($n <= 0) {
                continue;
            }

            $slug = (string)$child['slug'];

            // Only for our own tree; the paint trees keep the addresses they have.
            if ($rootSlug !== '' && str_starts_with($slug, $rootSlug . '-')) {
                $slug = substr($slug, strlen($rootSlug) + 1);
            }

            $out[] = [
                'id'         => (int)$child['id'],
                'slug'       => $slug,
                'label'      => $child['name'],
                'blurb'      => (string)($child['description'] ?? ''),
                'copy'       => (string)($child['website_copy'] ?? ''),
                'image'      => (string)($child['image_path'] ?? ''),
                'categories' => $this->categoryNamesBeneath((int)$child['id']),
                'own'        => $this->ownCount((int)$child['id']),
                'count'      => $n,
                'children'   => $depth > 1 ? $this->childrenOf((int)$child['id'], $rootSlug, $depth - 1) : [],
            ];
        }

        return $out;
    }

    /** Listings filed directly in a category, not beneath it. */
    private function ownCount(int $id): int
    {
        $row = Database::selectOne("
            SELECT COUNT(DISTINCT COALESCE(NULLIF(p.variant_group, ''), CONCAT('#', p.id))) n
            FROM product_categories pc JOIN products p ON p.id = pc.product_id
            WHERE pc.category_id = ? AND " . self::PUBLIC_WHERE, [$id]);

        return (int)($row['n'] ?? 0);
    }

    /** A root category's long copy and blurb, for a section's landing page. */
    public function categoryCopy(string $slug): array
    {
        return Database::selectOne(
            "SELECT name, description, website_copy, image_path FROM categories WHERE slug = ? AND is_active = 1 LIMIT 1",
            [$slug]
        ) ?: [];
    }

    /**
     * The SKU a /stencils/{slug}[/{size}] address shows.
     *
     * Without a size, the listing's first variant (its smallest size, 1/8"). With a
     * material — the thickness picker's ?material=3-32 — the variant of that size cut in
     * that material, falling back to the size's first.
     */
    public function skuForListing(string $slug, string $size = '', string $material = ''): ?string
    {
        $rows = Database::select("
            SELECT p.sku, p.website_size_slug, p.variant_label
            FROM products p
            WHERE " . self::PUBLIC_WHERE . " AND p.website_slug = ?
            ORDER BY p.variant_order, p.sku
        ", [$slug]);

        if ($size !== '') {
            $rows = array_values(array_filter($rows, static fn($r) => $r['website_size_slug'] === $size));
        }

        if ($material !== '') {
            foreach ($rows as $r) {
                if (self::materialSlug((string)$r['variant_label']) === $material) {
                    return $r['sku'];
                }
            }
        }

        return $rows[0]['sku'] ?? null;
    }

    /** '4" letters · 3/32"' → '3-32'; '10 mil' → '10-mil'. The thickness part of a label. */
    public static function materialSlug(string $label): string
    {
        $parts = explode(' · ', $label);

        return trim((string)preg_replace('/[^a-z0-9]+/', '-', strtolower(str_replace(['"', '”', '″'], '', end($parts)))), '-');
    }

    /** Where a row's page is: the listing address for a stencil, else /product/{sku}. */
    public static function urlFor(array $row, bool $withSize = false): string
    {
        $slug = trim((string)($row['website_slug'] ?? ''));

        if ($slug === '') {
            return '/product/' . rawurlencode((string)$row['sku']);
        }

        $size = trim((string)($row['website_size_slug'] ?? ''));

        return '/stencils/' . rawurlencode($slug) . ($withSize && $size !== '' ? '/' . rawurlencode($size) : '');
    }

    /** The innermost stencil category a product sits in, for its breadcrumb. */
    public function crumbsFor(int $productId, string $rootSlug): array
    {
        $root = Database::selectOne("SELECT id, name FROM categories WHERE slug = ? AND parent_id IS NULL", [$rootSlug]);

        if (!$root) {
            return [];
        }

        $row = Database::selectOne("
            SELECT c.id, c.name, c.slug, c.parent_id, pa.name AS parent_name, pa.slug AS parent_slug, pa.parent_id AS grand
            FROM product_categories pc
            JOIN categories c   ON c.id = pc.category_id AND c.is_active = 1
            LEFT JOIN categories pa ON pa.id = c.parent_id
            WHERE pc.product_id = ? AND c.id <> ?
            ORDER BY pc.is_primary DESC LIMIT 1
        ", [$productId, (int)$root['id']]);

        if (!$row) {
            return [];
        }

        $short = static fn(string $s) => str_starts_with($s, $rootSlug . '-') ? substr($s, strlen($rootSlug) + 1) : $s;
        $out   = [];

        if ((int)$row['grand'] === (int)$root['id']) {
            $out[] = ['label' => $row['parent_name'], 'href' => '/shop/' . $rootSlug . '/' . $short((string)$row['parent_slug'])];
        }

        $out[] = ['label' => $row['name'], 'href' => '/shop/' . $rootSlug . '/' . $short((string)$row['slug'])];

        return $out;
    }

    /** The Football Block Letters (STBL-15 … STBL-25): the endzone designer's per-letter prices. */
    public function footballLetterSizes(): array
    {
        return Database::select("
            SELECT p.sku, p.website_price FROM products p
            WHERE " . self::PUBLIC_WHERE . " AND p.sku LIKE 'STBL-%'
        ");
    }

    /** The Single Letter or Number listing's SKUs: the custom stencil builder's letter sizes. */
    public function singleCharacterSizes(): array
    {
        return Database::select("
            SELECT p.sku, p.variant_label, p.website_price, p.variant_order FROM products p
            WHERE " . self::PUBLIC_WHERE . " AND p.website_slug = 'single-letter-or-number'
            ORDER BY p.variant_order
        ");
    }

    /**
     * A stencil SKU the website cart may sell: published, active, and filed under Stencils
     * (or a category beneath it). Anything else is not for sale online yet.
     */
    public function cartProduct(string $sku): array|false
    {
        if ($sku === '') {
            return false;
        }

        return Database::selectOne("
            SELECT " . self::PUBLIC_COLUMNS . ", p.net_weight, p.case_weight_gross, p.item_type
            FROM products p
            LEFT JOIN product_brands b ON b.id = p.brand_id
            WHERE " . self::PUBLIC_WHERE . " AND p.sku = ?
              AND EXISTS (
                  SELECT 1 FROM product_categories pc
                  JOIN categories c ON c.id = pc.category_id
                  LEFT JOIN categories c1 ON c1.id = c.parent_id
                  LEFT JOIN categories c2 ON c2.id = c1.parent_id
                  WHERE pc.product_id = p.id AND 'stencils' IN (c.slug, c1.slug, c2.slug)
              )
            LIMIT 1
        ", [$sku]);
    }

    /**
     * A published, priced product that is not a stencil and that USSCOS knows how to send
     * by FedEx: it has a packing rule (aerosol cases), or a parcel limit and a case weight
     * (pails, one to a box). Paint without either is quote-only until its packing is set
     * up, because nothing else can say what its boxes weigh. Freight is never sold online.
     * The tier price columns come along for CustomerPricing.
     */
    public function parcelProduct(string $sku): array|false
    {
        if ($sku === '') {
            return false;
        }

        return Database::selectOne("
            SELECT " . self::PUBLIC_COLUMNS . ", p.case_weight_gross, p.max_parcel_qty,
                   p.price, p.dist_price, p.dist_price_2026, p.stocking_dist
            FROM products p
            LEFT JOIN product_brands b ON b.id = p.brand_id
            WHERE " . self::PUBLIC_WHERE . " AND p.sku = ? AND p.website_price > 0
              AND NOT EXISTS (
                  SELECT 1 FROM product_categories pc
                  JOIN categories c ON c.id = pc.category_id
                  LEFT JOIN categories c1 ON c1.id = c.parent_id
                  LEFT JOIN categories c2 ON c2.id = c1.parent_id
                  WHERE pc.product_id = p.id AND 'stencils' IN (c.slug, c1.slug, c2.slug)
              )
              AND (EXISTS (SELECT 1 FROM product_packing_rules r JOIN shipping_boxes sb ON sb.id = r.shipping_box_id
                           WHERE r.product_id = p.id AND r.is_active = 1 AND sb.is_active = 1)
                   OR (p.max_parcel_qty >= 1 AND p.case_weight_gross > 0))
            LIMIT 1
        ", [$sku]);
    }

    /** A content page by slug: title, description, html. */
    public function sitePage(string $slug): array|false
    {
        return Database::selectOne("SELECT slug, title, description, html FROM site_pages WHERE slug = ?", [$slug]);
    }

    /**
     * LISTINGS in a category and everything beneath it, not products.
     *
     * Counting products made a menu chip read 195 above a page showing 14 cards: the
     * page collapses a product's colors into one listing and the chip did not. A
     * number that contradicts what is beneath it is worse than no number.
     *
     * Variant groups are gathered across the whole subtree before counting, because a
     * group split over a parent and its child would otherwise be counted twice.
     *
     * @param array<string,bool> $seen carries group keys up through the recursion
     */
    private function categoryReach(int $id, array &$seen = []): int
    {
        $n = 0;

        // Same visibility rule as the pages themselves. Counting products the shop
        // will not show is how a chip came to read 14 over a page of 9.
        foreach (Database::select("
            SELECT p.id, p.variant_group FROM product_categories pc
            JOIN products p ON p.id = pc.product_id
            WHERE pc.category_id = ? AND " . self::PUBLIC_WHERE . "
        ", [$id]) as $row) {
            $key = ($row['variant_group'] ?? '') !== '' ? 'g:' . $row['variant_group'] : 'p:' . $row['id'];

            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $n++;
            }
        }

        foreach (Database::select("SELECT id FROM categories WHERE parent_id = ?", [$id]) as $k) {
            $n += $this->categoryReach((int)$k['id'], $seen);
        }

        return $n;
    }

    /**
     * A category's name plus every descendant's, for matching products.
     *
     * @return string[]
     */
    private function categoryNamesBeneath(int $id): array
    {
        $row   = Database::selectOne("SELECT name FROM categories WHERE id = ?", [$id]);
        $names = $row ? [$row['name']] : [];

        foreach (Database::select("SELECT id FROM categories WHERE parent_id = ?", [$id]) as $k) {
            $names = array_merge($names, $this->categoryNamesBeneath((int)$k['id']));
        }

        return $names;
    }
    /**
     * Public documents for a product, grouped into the tabs they belong in.
     *
     * Only is_public rows: a product's file store also holds internal things like
     * certificates of analysis for a particular batch, which are not a customer's to
     * read and would otherwise appear the moment somebody uploaded one.
     *
     * @return array<string,array<int,array<string,mixed>>>
     */
    public function documentsFor(int $productId): array
    {
        /*
         * Across the whole variant group, not just this product.
         *
         * A TDS and an SDS describe the paint, not the color: uploading one to White
         * and nothing to Red is the normal way this gets filled in, and Red should
         * still show the datasheet. Deduplicated by file path, so the same document
         * uploaded to several colors appears once.
         */
        $rows = Database::select("
            SELECT DISTINCT d.doc_type, d.title, d.file_path, d.file_name, d.mime_type,
                            d.file_size, d.sort_order
            FROM product_documents d
            JOIN products p ON p.id = d.product_id
            WHERE d.is_public = 1
              AND (p.id = ?
                   OR (p.variant_group IS NOT NULL
                       AND p.variant_group = (SELECT variant_group FROM products WHERE id = ?)))
            ORDER BY d.sort_order, d.file_path
        ", [$productId, $productId]);

        $tabs = ['color-deck' => [], 'specs' => [], 'literature' => []];

        foreach ($rows as $r) {
            $tab = match ($r['doc_type']) {
                'color_chart'               => 'color-deck',
                'tds', 'sds', 'spec_sheet',
                'certificate'               => 'specs',
                'sales_flyer', 'catalog'    => 'literature',
                default                     => 'literature',
            };

            $tabs[$tab][] = $r;
        }

        return $tabs;
    }

    /**
     * Video URLs for a product, one per line in the field.
     *
     * YouTube links are rewritten to their embed form here rather than in the view,
     * because a watch URL in an iframe renders as a refusal page and the person
     * pasting the link has no reason to know that.
     *
     * @return array<int,array{url:string,embed:?string}>
     */
    public function videosFor(array $product): array
    {
        $raw = trim((string)($product['video_urls'] ?? ''));

        if ($raw === '') {
            return [];
        }

        $out = [];

        foreach (preg_split('/[\r\n]+/', $raw) ?: [] as $line) {
            $url = trim($line);

            if ($url === '' || !preg_match('~^https?://~i', $url)) {
                continue;
            }

            $embed = null;

            if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{6,})~i', $url, $m)) {
                $embed = 'https://www.youtube-nocookie.com/embed/' . $m[1];
            }

            $out[] = ['url' => $url, 'embed' => $embed];
        }

        return $out;
    }
}
