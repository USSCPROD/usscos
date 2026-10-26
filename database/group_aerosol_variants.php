<?php

declare(strict_types=1);

/**
 * Group aerosol paints so a color is a choice, not a separate product.
 *
 *   php database/group_aerosol_variants.php
 *   php database/group_aerosol_variants.php --commit
 *
 * 195 aerosol products are really 14 things to buy: DuraStripe T-Tip in 35 colors,
 * EcoStripe UMA-Tip in 16, Fat Cans in 7. Listing every color separately is how a
 * shop with 14 products looks like a wall of 195, and it leaves a customer reading
 * 'T-Tip - Augusta Green' with no way to tell whether that is DuraStripe or
 * EcoStripe, because the product name never says.
 *
 * They stay separate product rows. Each color has its own SKU, price and stock, and
 * an order line must name exactly one. This groups them for display only.
 *
 * THE PACK IS PART OF THE GROUP. A 2-pack is a different thing to buy from a case of
 * twelve, at a different price, so they are different listings even in the same color.
 * uom_code is unreliable for this - the same pack is recorded as '18 oz Can' on one
 * row and 'Case (12)' on another, one describing the unit and the other the case - so
 * the pack is read from the name as well.
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

/**
 * Colors a buyer reaches for first, then everything else alphabetically, with the
 * mixed and made-to-order ones last - nobody browsing lands on 'Custom Color'.
 */
const LEAD_COLORS = ['White', 'Black', 'Red', 'Yellow', 'Blue', 'Royal Blue', 'Navy Blue',
                     'Green', 'Orange', 'Handicap Blue'];

$packOf = function (array $r): string {
    $hay = strtolower(($r['uom_code'] ?? '') . ' ' . ($r['name'] ?? ''));

    if (str_contains($hay, '2-pack') || str_contains($hay, '2 pack')) {
        return '2-Pack Case';
    }

    if (str_contains($hay, '26 oz')) {
        return 'Case (12 × 26 oz)';
    }

    return 'Case (12 × 18 oz)';
};

$rows = Database::select("
    SELECT p.id, p.sku, p.name, p.product_line, p.color, p.uom_code, b.name AS brand
    FROM product_categories pc
    JOIN products p ON p.id = pc.product_id AND p.is_active = 1
    JOIN categories c ON c.id = pc.category_id
    LEFT JOIN product_brands b ON b.id = p.brand_id
    WHERE c.external_ref = 'WC:aerosol-field-marking-paints'
    ORDER BY p.sku
");

printf("%d active aerosol product(s).\n", count($rows));

$groups = [];

foreach ($rows as $r) {
    $brand = trim((string)($r['brand'] ?? ''));
    $line  = trim((string)($r['product_line'] ?? ''));
    $pack  = $packOf($r);

    // Without a brand there is nothing to group by that a customer would recognise.
    if ($brand === '') {
        continue;
    }

    $key = $brand . '|' . $line . '|' . $pack;

    $groups[$key][] = $r + ['brand' => $brand, 'line' => $line, 'pack' => $pack];
}

// A group of one is not a variation; leaving it grouped renders a picker with a
// single button, which looks broken.
$single = 0;

foreach ($groups as $k => $v) {
    if (count($v) < 2) {
        $single++;
        unset($groups[$k]);
    }
}

printf("\n  %d listing(s), %d left ungrouped as one-offs.\n\n", count($groups), $single);

$plan = [];

foreach ($groups as $key => $members) {
    [$brand, $line, $pack] = explode('|', $key);

    $title = trim($brand . ' ' . $line) . ', ' . $pack;

    usort($members, function ($a, $b) {
        $ai = array_search($a['color'], LEAD_COLORS, true);
        $bi = array_search($b['color'], LEAD_COLORS, true);
        $am = preg_match('/mix|custom/i', (string)$a['color']) ? 1 : 0;
        $bm = preg_match('/mix|custom/i', (string)$b['color']) ? 1 : 0;

        if ($am !== $bm) {
            return $am <=> $bm;
        }

        if ($ai !== false || $bi !== false) {
            return ($ai === false ? 99 : $ai) <=> ($bi === false ? 99 : $bi);
        }

        return strcasecmp((string)$a['color'], (string)$b['color']);
    });

    $plan[$key] = ['title' => $title, 'members' => $members];
    printf("  %-46s %2d colors   leads with %s\n", substr($title, 0, 46),
        count($members), $members[0]['color']);
}

if (!$commit) {
    echo "\nNothing written. Add --commit to apply.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    $ids = array_map(fn($r) => (int)$r['id'], $rows);

    if ($ids !== []) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("UPDATE products SET variant_group = NULL, variant_label = NULL,
                       variant_order = 0, variant_title = NULL WHERE id IN ({$in})")->execute($ids);
    }

    $set = $pdo->prepare("UPDATE products SET variant_group = ?, variant_label = ?,
                          variant_order = ?, variant_title = ? WHERE id = ?");
    $n = 0;

    foreach ($plan as $key => $g) {
        foreach ($g['members'] as $i => $m) {
            $set->execute([$key, $m['color'] ?: $m['sku'], $i + 1, $g['title'], (int)$m['id']]);
            $n++;
        }
    }

    $pdo->commit();
    printf("\nGrouped %d product(s) into %d listing(s).\n", $n, count($plan));
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $e;
}
