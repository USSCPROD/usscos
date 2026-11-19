<?php

declare(strict_types=1);

/**
 * Bring USSCOS's stencils, and the stencil category tree, into line with the live shop.
 *
 *   php database/sync_stencils_from_shop.php                  dry run, reads the live shop
 *   php database/sync_stencils_from_shop.php --file=cat.json  dry run, reads a saved catalog
 *   php database/sync_stencils_from_shop.php --commit         apply
 *
 * THE SHOP IS THE DEFAULT. Chip, 19 Nov 2026: the stencil shop's catalog (stencilsplus.com
 * and stencils.usscproducts.com, which serve identical SKUs) is the most current, and its
 * categories are "the new updated and approved listings". Where it and USSCOS disagree, the
 * shop wins. See docs/STENCIL_CATALOG_MATCH.md.
 *
 * Source is the USSC shop's public /catalog.json, the very file its own pages load. It is
 * the USSC brand of the shop, so it carries Rooftop and Yard & Lawn, which StencilsPlus
 * does not; its SKUs and prices are StencilsPlus's, read-only.
 *
 * WHAT IT DOES
 *   1. CATEGORIES. The children of Stencils become the shop's categories, in the shop's
 *      order, with the shop's blurb as the description. Endzone, Midfield and Custom Field
 *      sit under Sports as they do on the shop. Existing rows that mean the same thing are
 *      renamed rather than duplicated (REUSE). Every other child of Stencils is deactivated
 *      and hidden, not deleted, and its product links are removed.
 *   2. PRODUCTS ARE REFILED. Each stencil is linked to Stencils and to its shop category
 *      (primary), and unlinked from any other category under Stencils. Links outside the
 *      Stencils tree are not touched.
 *   3. MATCHED SKUs: retail (`price`, `website_price`) and `net_weight` take the shop's
 *      value; `dist_price` takes the shop's wholesale rule, retail x 2 / 2.75. `cost` is
 *      touched only where retail changes, as retail / 2.75.
 *   4. SHOP-ONLY SKUs are inserted under their shop category.
 *   5. RETIRE: superseded SKUs are deactivated and unpublished. Never deleted: products
 *      are referenced from seven tables (CLAUDE.md, Destructive operations).
 *
 * WHAT IT DOES NOT DO
 *   - Retire a USSCOS-only SKU that is not in RETIRE. Those are reported and left; whether
 *     the shop dropped them on purpose is a question for a person. They are still filed,
 *     beside their other thickness where the shop has one.
 *   - Copy names, descriptions or photos. The shop lists sizes and thicknesses as one
 *     product; USSCOS groups thickness only. How listings map is a storefront decision.
 *
 * GROSS AND CASE WEIGHT. Updated only where empty, or where they still equal the old net
 * weight and so were derived from it, never measured. Same principle as
 * import_stencil_calculator.php: a weighed parcel beats a figure from sheet area.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;

const SHOP_URL = 'https://stencils.usscproducts.com/catalog.json';

/** Wholesale = retail x 2 / 2.75 and cost = retail / 2.75: the price sheet's own ratios. */
const WHOLESALE_RATIO = 2 / 2.75;
const COST_RATIO      = 1 / 2.75;

/**
 * Wholesale differences smaller than this are rounding, not disagreement. USSCOS stores
 * wholesale as 2 x cost to four places; the shop derives it from the rounded retail. The
 * two land a cent apart on ~200 SKUs, and rewriting those would be churn, not a fix.
 */
const WHOLESALE_TOLERANCE = 0.015;

/**
 * Shop category => slug of the existing USSCOS row to rename into it, rather than create a
 * duplicate beside it. Ribbon Stencils keeps its name: Chip, 19 Nov 2026, "ribbons should
 * go under ribbon stencils". The shop calls the section 'Awareness Ribbons'.
 */
const REUSE = [
    'words'   => 'stencils-words',
    'kits'    => 'stencils-number-kits',
    'retail'  => 'stencils-retail-stores',
    'sports'  => 'stencils-sports-fields',
    'ribbons' => 'ribbon-stencils',
    'rooftop' => 'roof-stencils',
    'yard'    => 'residential-yard-stencils',
];

const KEEP_NAME = ['ribbons' => 'Ribbon Stencils'];

/**
 * USSCOS-only SKUs with no other thickness on the shop to file beside: SKU prefix => shop
 * category. The basketball courts are not on the shop at all.
 */
const PREFIX_CATEGORY = ['ST-BBALL-' => 'sports'];

/**
 * Superseded SKUs to deactivate. The shop sells these as STF-04-8-C and STNP-04-8-C,
 * which USSCOS also has. These old ones carry no price, and FIRE's weight is 0.07 lb.
 */
const RETIRE = ['STF-C04-8', 'STNP-C04-8'];

$args   = array_slice($argv, 1);
$commit = in_array('--commit', $args, true);
$file   = null;

foreach ($args as $a) {
    if (str_starts_with($a, '--file=')) {
        $file = substr($a, 7);
    }
}

// ── Read the shop ────────────────────────────────────────────────────────────

if ($file !== null) {
    $raw  = @file_get_contents($file);
    $from = $file;
} else {
    $ctx  = stream_context_create(['http' => ['timeout' => 30, 'user_agent' => 'USSCOS stencil sync']]);
    $raw  = @file_get_contents(SHOP_URL, false, $ctx);
    $from = SHOP_URL;
}

if ($raw === false || $raw === '') {
    fwrite(STDERR, "Could not read the shop catalog from {$from}\n");
    exit(1);
}

$catalog = json_decode($raw, true);

if (!is_array($catalog) || !is_array($catalog['products'] ?? null) || !is_array($catalog['categories'] ?? null)) {
    fwrite(STDERR, "{$from} is not a shop catalog (no products or categories)\n");
    exit(1);
}

/** One row per sellable SKU: product, size, thickness. */
$shop = [];

/** SKU with its thickness segment removed => shop category, to file a USSCOS-only thickness. */
$shopBase = [];

foreach ($catalog['products'] as $p) {
    foreach ($p['variants'] ?? [] as $v) {
        foreach ($v['options'] ?? [] as $thick => $o) {
            $sku = trim((string)($o['sku'] ?? ''));

            if ($sku === '') {
                continue;
            }

            $shop[strtoupper($sku)] = [
                'sku'     => $sku,
                'product' => (string)$p['id'],
                'section' => (string)($p['category'] ?? ''),
                'group'   => (string)($p['group'] ?? ''),
                'name'    => (string)$p['name'],
                'size'    => (string)($v['label'] ?? ''),
                'thick'   => (string)$thick,
                'price'   => isset($o['price'])  && $o['price']  !== null ? round((float)$o['price'], 2)  : null,
                'weight'  => isset($o['weight']) && $o['weight'] !== null ? round((float)$o['weight'], 2) : null,
            ];

            $shopBase[skuBase($sku)] = (string)($p['category'] ?? '');
        }
    }
}

printf("Shop: %d SKUs in %d products, %d categories, from %s\n",
    count($shop), count($catalog['products']), count($catalog['categories']), $from);

if (count($shop) < 1000) {
    // The live catalog has ~1,250. A short read is a broken fetch, not a smaller catalog,
    // and acting on it would report hundreds of SKUs as "USSCOS only".
    fwrite(STDERR, "Refusing: fewer than 1,000 SKUs looks like a partial catalog.\n");
    exit(1);
}

// ── Read USSCOS ──────────────────────────────────────────────────────────────

$stencilCat = Database::selectOne("SELECT id FROM categories WHERE name = 'Stencils' AND parent_id IS NULL LIMIT 1");

if (!$stencilCat) {
    fwrite(STDERR, "No top-level 'Stencils' category in USSCOS.\n");
    exit(1);
}

$stencilCatId = (int)$stencilCat['id'];

/** The whole Stencils tree below the top: children and grandchildren. */
$tree = [];

foreach (Database::select("
    SELECT c.id, c.parent_id, c.name, c.slug, c.is_active, c.show_on_website
    FROM categories c
    WHERE c.parent_id = ? OR c.parent_id IN (SELECT id FROM categories WHERE parent_id = ?)
", [$stencilCatId, $stencilCatId]) as $r) {
    $tree[(int)$r['id']] = $r;
}

$treeBySlug = [];

foreach ($tree as $id => $r) {
    $treeBySlug[$r['slug']] = $id;
}

$os = [];

foreach (Database::select("
    SELECT DISTINCT p.id, p.sku, p.name, p.is_active, p.publish_to_website, p.price, p.website_price,
           p.dist_price, p.cost, p.net_weight, p.gross_weight, p.case_weight_gross, p.units_per_case
    FROM products p
    LEFT JOIN product_categories pc ON pc.product_id = p.id
    LEFT JOIN categories c          ON c.id = pc.category_id
    WHERE c.id = ? OR c.parent_id = ? OR p.name LIKE '%stencil%'
", [$stencilCatId, $stencilCatId]) as $r) {
    $os[strtoupper($r['sku'])] = $r;
}

/** Every SKU in USSCOS, stencil or not, so an insert never collides with another product. */
$allSkus = [];

foreach (Database::select("SELECT id, sku FROM products") as $r) {
    $allSkus[strtoupper($r['sku'])] = (int)$r['id'];
}

/** Current links into the Stencils tree (the top itself included), per product. */
$linksOf = [];

foreach (Database::select("SELECT product_id, category_id, is_primary FROM product_categories") as $r) {
    $cid = (int)$r['category_id'];

    if ($cid === $stencilCatId || isset($tree[$cid])) {
        $linksOf[(int)$r['product_id']][$cid] = (int)$r['is_primary'];
    }
}

printf("USSCOS: %d stencil SKUs, %d categories under Stencils\n", count($os), count($tree));

// ── Plan: categories ─────────────────────────────────────────────────────────

/** Shop category key => ['id' => existing id or null, 'name', 'slug', 'parent' key|null, ...]. */
$catPlan = [];
$order   = 0;

foreach ($catalog['categories'] as $c) {
    $key = (string)$c['id'];
    $order += 10;

    $reuseId = isset(REUSE[$key]) ? ($treeBySlug[REUSE[$key]] ?? null) : null;
    $slug    = 'stencils-' . $key;

    $catPlan[$key] = [
        'id'          => $reuseId,
        'was'         => $reuseId !== null ? $tree[$reuseId]['name'] : null,
        'name'        => KEEP_NAME[$key] ?? (string)$c['name'],
        'slug'        => $slug,
        'description' => isset($c['blurb']) ? (string)$c['blurb'] : null,
        'parent'      => isset($c['parent']) ? (string)$c['parent'] : null,
        'sort_order'  => $order,
    ];
}

foreach ($catPlan as $key => $c) {
    if ($c['parent'] !== null && !isset($catPlan[$c['parent']])) {
        fwrite(STDERR, "Shop category '{$key}' names a parent '{$c['parent']}' that is not in the catalog.\n");
        exit(1);
    }

    // Slugs are unique across every category, not just stencils.
    $clash = Database::selectOne("SELECT id, name FROM categories WHERE slug = ? LIMIT 1", [$c['slug']]);

    if ($clash && (int)$clash['id'] !== $c['id']) {
        fwrite(STDERR, "Slug '{$c['slug']}' already belongs to category {$clash['id']} ({$clash['name']}).\n");
        exit(1);
    }
}

$keptIds   = array_filter(array_column($catPlan, 'id'));
$retireCat = [];

foreach ($tree as $id => $r) {
    if (!in_array($id, $keptIds, true)) {
        $retireCat[$id] = $r;
    }
}

// ── Plan: products ───────────────────────────────────────────────────────────

$money = static fn($v): ?float => $v === null || $v === '' ? null : round((float)$v, 2);
$same  = static fn(?float $a, ?float $b): bool => $a === $b || ($a !== null && $b !== null && abs($a - $b) < 0.005);

$updates = [];
$inserts = [];
$retire  = [];
$osOnly  = [];
$noPrice = [];
$clashes = [];

foreach ($shop as $key => $s) {
    if (!isset($os[$key])) {
        if (isset($allSkus[$key])) {
            // In USSCOS but not filed as a stencil. Updating it blind could rewrite a
            // product that only shares a SKU, so it is reported for a person to look at.
            $clashes[] = $s['sku'];
            continue;
        }

        if ($s['price'] === null) {
            $noPrice[] = $s['sku'];
            continue;
        }

        $inserts[] = $s;
        continue;
    }

    $cur = $os[$key];
    $set = [];
    $was = [];

    if ($s['price'] !== null) {
        if (!$same($s['price'], $money($cur['website_price'])) || !$same($s['price'], $money($cur['price']))) {
            $set['price']         = $s['price'];
            $set['website_price'] = $s['price'];
            $set['cost']          = round($s['price'] * COST_RATIO, 4);
            $was['price']         = $cur['website_price'];
        }

        $dist = round($s['price'] * WHOLESALE_RATIO, 2);

        if ($money($cur['dist_price']) === null || abs($dist - (float)$cur['dist_price']) > WHOLESALE_TOLERANCE) {
            $set['dist_price'] = $dist;
            $was['dist_price'] = $cur['dist_price'];
        }
    }

    if ($s['weight'] !== null && !$same($s['weight'], $money($cur['net_weight']))) {
        $oldNet = $money($cur['net_weight']);
        $set['net_weight']  = $s['weight'];
        $set['weight_unit'] = 'Pounds';
        $was['net_weight']  = $cur['net_weight'];

        $derived = static fn($v): bool => $money($v) === null || $money($v) <= 0 || $same($money($v), $oldNet);

        if ($derived($cur['gross_weight'])) {
            $set['gross_weight'] = $s['weight'];
        }

        if ($derived($cur['case_weight_gross']) && (float)($cur['units_per_case'] ?? 0) <= 1) {
            $set['case_weight_gross'] = $s['weight'];
        }
    }

    if (!(int)$cur['is_active']) {
        // The shop sells it, so it is not retired. Bring it back.
        $set['is_active'] = 1;
        $was['is_active'] = 0;
    }

    if ($set !== []) {
        $updates[] = ['id' => (int)$cur['id'], 'sku' => $cur['sku'], 'set' => $set, 'was' => $was];
    }
}

/** Existing product id => shop category key it should be filed under (null = Stencils only). */
$fileAs = [];

foreach ($os as $key => $cur) {
    $id = (int)$cur['id'];

    if (isset($shop[$key])) {
        $fileAs[$id] = $shop[$key]['section'];
        continue;
    }

    if (in_array($key, RETIRE, true)) {
        if ((int)$cur['is_active'] || (int)$cur['publish_to_website']) {
            $retire[] = ['id' => $id, 'sku' => $cur['sku'], 'name' => $cur['name']];
        }
        $fileAs[$id] = null;
        continue;
    }

    $section = $shopBase[skuBase($cur['sku'])] ?? null;

    foreach (PREFIX_CATEGORY as $prefix => $cat) {
        if ($section === null && str_starts_with($key, $prefix)) {
            $section = $cat;
        }
    }

    $fileAs[$id] = $section;

    if ((int)$cur['is_active']) {
        $osOnly[] = ['sku' => $cur['sku'], 'name' => $cur['name'], 'section' => $section];
    }
}

// Link changes for existing products, counted against category keys so the dry run can
// report them before the new categories have ids.
$linkAdds = 0;
$linkDrops = 0;
$perCat = [];

foreach ($fileAs as $pid => $section) {
    $want = $section !== null && isset($catPlan[$section]) ? $catPlan[$section]['id'] : null;
    $have = $linksOf[$pid] ?? [];

    $perCat[$section ?? '(Stencils only)'] = ($perCat[$section ?? '(Stencils only)'] ?? 0) + 1;

    foreach ($have as $cid => $_) {
        if ($cid !== $stencilCatId && $cid !== $want) {
            $linkDrops++;
        }
    }

    if ($section !== null && ($want === null || !isset($have[$want]))) {
        $linkAdds++;
    }
}

foreach ($inserts as $s) {
    $perCat[$s['section']] = ($perCat[$s['section']] ?? 0) + 1;
}

// Links from products that are not stencils at all, into categories about to be retired.
$strayLinks = 0;

foreach ($linksOf as $pid => $have) {
    if (isset($fileAs[$pid])  || array_key_exists($pid, $fileAs)) {
        continue;
    }

    foreach ($have as $cid => $_) {
        if (isset($retireCat[$cid])) {
            $strayLinks++;
        }
    }
}

// ── Report ───────────────────────────────────────────────────────────────────

echo "\n── Stencil categories become the shop's\n";

foreach ($catPlan as $key => $c) {
    $how = $c['id'] === null ? 'create' : ($c['was'] === $c['name'] ? "keep #{$c['id']}" : "rename #{$c['id']} '{$c['was']}'");
    printf("   %-24s %-26s %-28s %4d SKU(s)   %s\n",
        ($c['parent'] !== null ? '  └ ' : '') . $c['name'],
        $c['slug'],
        $how,
        $perCat[$key] ?? 0,
        $c['parent'] !== null ? '' : '');
}

printf("\n── Deactivate and hide %d old categor%s under Stencils\n", count($retireCat), count($retireCat) === 1 ? 'y' : 'ies');

foreach ($retireCat as $id => $r) {
    printf("   #%-4d %s\n", $id, $r['name']);
}

printf("\n── Refile existing stencils: %d link(s) added, %d removed", $linkAdds, $linkDrops);
echo $strayLinks ? "; {$strayLinks} non-stencil link(s) into retired categories also removed\n" : "\n";

if (isset($perCat['(Stencils only)'])) {
    printf("   %d left under Stencils with no child category (retired SKUs)\n", $perCat['(Stencils only)']);
}

$fmt = static function (array $set, array $was): string {
    $out = [];

    foreach ($set as $c => $v) {
        if (in_array($c, ['website_price', 'weight_unit', 'cost', 'gross_weight', 'case_weight_gross'], true)) {
            continue;
        }

        $out[] = array_key_exists($c, $was) ? "{$c} {$was[$c]} → {$v}" : "{$c} → {$v}";
    }

    foreach (['gross_weight', 'case_weight_gross', 'cost'] as $c) {
        if (array_key_exists($c, $set)) {
            $out[] = "{$c} → {$set[$c]}";
        }
    }

    return implode('; ', $out);
};

printf("\n── Update %d matched SKU(s)\n", count($updates));

foreach ($updates as $u) {
    printf("   %-20s %s\n", $u['sku'], $fmt($u['set'], $u['was']));
}

printf("\n── Insert %d shop-only SKU(s)\n", count($inserts));

foreach ($inserts as $s) {
    printf(
        "   %-20s %8.2f  %6s lb  %-55s → %s\n",
        $s['sku'],
        $s['price'],
        $s['weight'] === null ? '—' : number_format($s['weight'], 2),
        insertName($s),
        $catPlan[$s['section']]['name'] ?? 'Stencils only'
    );
}

if ($noPrice !== []) {
    printf("\n── Not inserted, no price on the shop (%d): %s\n", count($noPrice), implode(', ', $noPrice));
}

if ($clashes !== []) {
    printf("\n── Not touched, SKU exists in USSCOS outside Stencils (%d): %s\n", count($clashes), implode(', ', $clashes));
}

printf("\n── Deactivate %d superseded SKU(s)\n", count($retire));

foreach ($retire as $r) {
    printf("   %-20s %s\n", $r['sku'], $r['name']);
}

printf("\n── Kept active, in USSCOS but not on the shop (%d)\n", count($osOnly));

foreach ($osOnly as $r) {
    printf("   %-20s %-50s → %s\n", $r['sku'], $r['name'], $catPlan[$r['section']]['name'] ?? 'Stencils only');
}

if (!$commit) {
    echo "\nNothing written. Add --commit to apply.\n";
    exit(0);
}

// ── Apply ────────────────────────────────────────────────────────────────────

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    // Categories: parents before children, so a child's parent id exists.
    uasort($catPlan, static fn($a, $b) => ($a['parent'] !== null) <=> ($b['parent'] !== null));

    $updCat = $pdo->prepare(
        "UPDATE categories SET name = ?, slug = ?, description = ?, parent_id = ?, sort_order = ?,
                               is_active = 1, show_on_website = 1
         WHERE id = ?"
    );
    $insCat = $pdo->prepare(
        "INSERT INTO categories (name, slug, description, parent_id, sort_order, is_active, show_on_website)
         VALUES (?, ?, ?, ?, ?, 1, 1)"
    );

    foreach ($catPlan as $key => $c) {
        $parentId = $c['parent'] === null ? $stencilCatId : $catPlan[$c['parent']]['id'];
        $vals = [$c['name'], $c['slug'], $c['description'], $parentId, $c['sort_order']];

        if ($c['id'] !== null) {
            $updCat->execute([...$vals, $c['id']]);
        } else {
            $insCat->execute($vals);
            $catPlan[$key]['id'] = (int)$pdo->lastInsertId();
        }
    }

    $offCat  = $pdo->prepare("UPDATE categories SET is_active = 0, show_on_website = 0 WHERE id = ?");
    $unlinkC = $pdo->prepare("DELETE FROM product_categories WHERE category_id = ?");

    foreach ($retireCat as $id => $_) {
        $unlinkC->execute([$id]);
        $offCat->execute([$id]);
    }

    // Products.
    foreach ($updates as $u) {
        $cols = [];
        $vals = [];

        foreach ($u['set'] as $c => $v) {
            $cols[] = "`{$c}` = ?";
            $vals[] = $v;
        }

        $vals[] = $u['id'];
        $pdo->prepare("UPDATE products SET " . implode(', ', $cols) . " WHERE id = ?")->execute($vals);
    }

    $brand = Database::selectOne("SELECT id FROM product_brands WHERE name = 'US Specialty Coatings' LIMIT 1");

    $ins = $pdo->prepare(
        "INSERT INTO products
            (sku, name, quickbooks_item, item_type, pack_level, brand_id,
             cost, dist_price, price, website_price, net_weight, gross_weight,
             case_weight_gross, weight_unit, publish_to_website, is_active)
         VALUES (?,?,?,'inventory_part','Each',?,?,?,?,?,?,?,?,?,1,1)"
    );

    foreach ($inserts as $s) {
        // Aluminum is not cut from sheet, so the plastic price sheet's cost ratio says
        // nothing about it. Chip, 19 Nov 2026: no weight either. Both stay empty.
        $isPlastic = $s['thick'] !== 'aluminum';

        $ins->execute([
            $s['sku'],
            insertName($s),
            'US SPECIALTY COATINGS:' . $s['sku'],
            $brand ? (int)$brand['id'] : null,
            $isPlastic ? round($s['price'] * COST_RATIO, 4) : null,
            round($s['price'] * WHOLESALE_RATIO, 2),
            $s['price'],
            $s['price'],
            $s['weight'],
            $s['weight'],
            $s['weight'],
            $s['weight'] === null ? null : 'Pounds',
        ]);

        $fileAs[(int)$pdo->lastInsertId()] = $s['section'];
    }

    $off = $pdo->prepare("UPDATE products SET is_active = 0, publish_to_website = 0 WHERE id = ?");

    foreach ($retire as $r) {
        $off->execute([$r['id']]);
    }

    // Filing: Stencils plus one shop category, primary. Other links inside the Stencils
    // tree go; links elsewhere (a paint category, say) are left alone.
    $treeIds = array_merge(array_keys($tree), array_column($catPlan, 'id'));
    $link    = $pdo->prepare(
        "INSERT INTO product_categories (product_id, category_id, is_primary) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE is_primary = VALUES(is_primary)"
    );
    $unlink  = $pdo->prepare("DELETE FROM product_categories WHERE product_id = ? AND category_id = ?");

    foreach ($fileAs as $pid => $section) {
        $want = $section !== null && isset($catPlan[$section]) ? (int)$catPlan[$section]['id'] : null;

        foreach (array_keys($linksOf[$pid] ?? []) as $cid) {
            if ($cid !== $stencilCatId && $cid !== $want && in_array($cid, $treeIds, true)) {
                $unlink->execute([$pid, $cid]);
            }
        }

        $link->execute([$pid, $stencilCatId, $want === null ? 1 : 0]);

        if ($want !== null) {
            $link->execute([$pid, $want, 1]);
        }
    }

    $pdo->commit();
    printf("\nCategories: %d set, %d retired. Products: updated %d, inserted %d, deactivated %d, %d filed.\n",
        count($catPlan), count($retireCat), count($updates), count($inserts), count($retire), count($fileAs));
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, "\nRolled back, nothing written: " . $e->getMessage() . "\n");
    exit(1);
}

if ($inserts !== []) {
    echo "\nRun database/group_stencil_variants.php (dry run, then --commit) to group the new thicknesses.\n";
}

/** A SKU with its thickness segment (-8, -32, -16) removed: ST-STOPBAR-18x8-32 → ST-STOPBAR-18x8. */
function skuBase(string $sku): string
{
    return (string)preg_replace('/-(8|32|16)(?=$|-C$)/', '', strtoupper($sku));
}

/**
 * Operational name for a new SKU, in the form existing stencils use:
 * 'Target Number 5 63" stencil — 1/8"'. Shop names alone ('Number 5', 'Stop Bar') say
 * nothing on an invoice without the chain, so a retail listing is prefixed with its group.
 */
function insertName(array $s): string
{
    // 'Hashmark Stencil - Aluminum' already says everything; prefixing the listing name
    // would give 'Hashmark Stencils Hashmark Stencil - Aluminum stencil'.
    if (stripos($s['size'], 'stencil') !== false) {
        return $s['size'];
    }

    $name = $s['name'];

    if ($s['thick'] === '10mil') {
        // 'Football Block Letters (10-mil)' — the thickness is appended below.
        $name = trim((string)preg_replace('/\s*(\(10[\s-]?mil\)|-\s*10[\s-]?mil)\s*/i', ' ', $name));
    }

    if ($s['group'] !== '' && $s['section'] === 'retail' && stripos($name, $s['group']) === false) {
        $name = $s['group'] . ' ' . $name;
    }

    if ($s['size'] !== '' && stripos($name, $s['size']) === false) {
        $name .= ' ' . $s['size'];
    }

    $thick = match ($s['thick']) {
        '1/8'      => '1/8"',
        '3/32'     => '3/32"',
        '1/16'     => '1/16"',
        '10mil'    => '10 mil',
        'aluminum' => 'aluminum',
        default    => $s['thick'],
    };

    return $name . ' stencil — ' . $thick;
}
