<?php

declare(strict_types=1);

/**
 * Decide which products appear on the website.
 *
 *   php database/set_website_publishing.php
 *   php database/set_website_publishing.php --commit
 *
 * publish_to_website already existed, is editable on the product form and comes
 * through the importer — it was simply never populated, sitting at 0 on all 2,091
 * products while the storefront ignored it and showed everything.
 *
 * Published is the default for an active product. The exclusions are deliberate:
 *
 *   2-Pack cases      sold on Amazon only, not through the website
 *   Toll manufacturing  made for other companies; not ours to sell
 *   Raw materials     ingredients, not products
 *
 * Re-runnable. A product somebody has deliberately unpublished by hand will be
 * published again by this, so it sets the baseline rather than policing it — use the
 * product form for one-offs after this has run.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;

$commit = in_array('--commit', array_slice($argv, 1), true);

if (!$commit) {
    echo "DRY RUN — nothing will be written. Add --commit to apply.\n\n";
}

// A 2-pack is recorded inconsistently: in uom_code on some rows, only in the name on
// others. Both are checked, for the same reason the cartonizer reads both.
const TWO_PACK = "(COALESCE(p.uom_code,'') LIKE '%2-Pack%' OR COALESCE(p.uom_code,'') LIKE '%2 Pack%'
                  OR p.name LIKE '%2-Pack%' OR p.name LIKE '%2 Pack%')";

const TOLL = "EXISTS (SELECT 1 FROM product_categories pc JOIN categories c ON c.id = pc.category_id
              WHERE pc.product_id = p.id AND c.name = 'Toll Manufacturing')";

/**
 * Individual products kept off the website.
 *
 * Listed here rather than only unpublished by hand, because this script sets the
 * baseline from rules and would otherwise publish them again on its next run - the
 * manual edit would quietly come undone weeks later with nobody watching.
 */
const NEVER_PUBLISH = [
    'DS-20/20-W',       // 20/20 White, 20 oz by weight
    'UMAECOMIX',        // UMA Ecostripe Mixed Case
    'DURASTRIPE 20/20', // placeholder row: no category, no price
];

/**
 * Product-line placeholder rows - 'AquaStripe Product Line (Parent)' and friends.
 *
 * They exist to hang a product line off in the catalogue and are not things anybody
 * can buy, but they were published, priced at $0.00, and came back from a storefront
 * search for 'aquastripe' looking like a product.
 */
const PLACEHOLDER = "p.name NOT LIKE '%(Parent)%'";

$counts = [
    'active'        => "p.is_active = 1",
    'two-pack'      => "p.is_active = 1 AND " . TWO_PACK,
    'toll mfg'      => "p.is_active = 1 AND " . TOLL,
    'raw material'  => "p.is_active = 1 AND COALESCE(p.item_type,'') = 'raw_material'",
    'never-publish' => "p.is_active = 1 AND p.sku IN ('" . implode("','", NEVER_PUBLISH) . "')",
    'placeholder'   => "p.is_active = 1 AND NOT " . PLACEHOLDER,
];

foreach ($counts as $label => $where) {
    printf("  %-14s %5d\n", $label,
        (int)Database::selectOne("SELECT COUNT(*) c FROM products p WHERE {$where}")['c']);
}

$never = NEVER_PUBLISH === []
    ? '1 = 1'
    : "p.sku NOT IN ('" . implode("','", array_map(
        fn($s) => str_replace("'", "''", $s), NEVER_PUBLISH)) . "')";

$publishWhere = "p.is_active = 1 AND NOT " . TWO_PACK . " AND NOT " . TOLL
              . " AND COALESCE(p.item_type,'') <> 'raw_material'"
              . " AND {$never} AND " . PLACEHOLDER;

$toPublish = (int)Database::selectOne("SELECT COUNT(*) c FROM products p WHERE {$publishWhere}")['c'];
$total     = (int)Database::selectOne("SELECT COUNT(*) c FROM products")['c'];

printf("\n  → publish    : %d\n", $toPublish);
printf("  → unpublish  : %d\n", $total - $toPublish);

echo "\n  Sample of what will NOT be published:\n";

foreach (Database::select("SELECT p.sku, p.name FROM products p
    WHERE p.is_active = 1 AND " . TWO_PACK . " ORDER BY p.sku LIMIT 6") as $r) {
    printf("      %-18s %s\n", $r['sku'], substr($r['name'], 0, 46));
}

if (!$commit) {
    echo "\nNothing written. Add --commit to apply.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    $pdo->prepare("UPDATE products p SET p.publish_to_website = 0")->execute();
    $pdo->prepare("UPDATE products p SET p.publish_to_website = 1 WHERE {$publishWhere}")->execute();
    $pdo->commit();

    printf("\nPublished %d, unpublished %d.\n", $toPublish, $total - $toPublish);
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $e;
}
