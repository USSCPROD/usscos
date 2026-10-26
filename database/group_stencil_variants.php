<?php

declare(strict_types=1);

/**
 * Group the stencil thickness variants into one listing each.
 *
 *   php database/group_stencil_variants.php            dry run, the default
 *   php database/group_stencil_variants.php --commit
 *
 * The same stencil is cut in three thicknesses and each is its own product with its
 * own SKU, price and stock. That is correct — an order line has to name exactly one
 * of them. What is wrong is showing all three in the shop as separate listings with
 * nearly identical names, so this fills in variant_group/variant_label and the
 * storefront collapses them into one listing with a choice.
 *
 * THE SUFFIX IS NOT ALWAYS AT THE END. 'STEMP-04-8-C' is the 1/8" Employee stencil
 * for curbs: thickness in the middle, '-C' after it. A rule that strips a trailing
 * '-8' leaves that product in a group of one and silently fails to pair it with
 * '-32-C'. The thickness is matched as a SEGMENT wherever it falls.
 *
 * Writes nothing outside variant_group, variant_label and variant_order.
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
 * The thickness codes, in the order a customer should see them: thickest first,
 * because that is the heavier-duty and more expensive option and the one most
 * buyers of a parking-lot stencil actually want.
 */
const THICKNESS = [
    '8'  => ['label' => '1/8"',  'order' => 1],
    '32' => ['label' => '3/32"', 'order' => 2],
    '16' => ['label' => '1/16"', 'order' => 3],
];

$rows = Database::select("
    SELECT DISTINCT p.id, p.sku, p.name, p.website_price
    FROM products p
    LEFT JOIN product_categories pc ON pc.product_id = p.id
    LEFT JOIN categories c          ON c.id = pc.category_id
    WHERE p.is_active = 1
      AND (c.name = 'Stencils' OR p.name LIKE '%stencil%')
    ORDER BY p.sku
");

printf("%d active stencil product(s).\n", count($rows));

$groups  = [];
$ungrouped = [];

foreach ($rows as $r) {
    $parts = explode('-', $r['sku']);
    $found = null;

    // The LAST matching segment, not the first. A SKU reads {code}-{size}-{thickness},
    // and 8 is both a size and a thickness: 'STELVEHPKNG-8-32' is the EIGHT INCH
    // Electric Vehicle Parking stencil cut at 3/32". Taking the first match reads
    // that 8 as the thickness, groups it as 'STELVEHPKNG-32', and leaves it unpaired
    // with 'STELVEHPKNG-8-8' — two halves of one listing, each alone.
    foreach ($parts as $i => $seg) {
        // Position 0 is the product code itself ('ST12', 'STNP'); a thickness never
        // lives there, and matching it would group unrelated products together.
        if ($i > 0 && isset(THICKNESS[$seg])) {
            $found = $i;
        }
    }

    if ($found === null) {
        $ungrouped[] = $r;
        continue;
    }

    $code = $parts[$found];
    unset($parts[$found]);

    $groups[implode('-', $parts)][] = $r + [
        'code'  => $code,
        'label' => THICKNESS[$code]['label'],
        'order' => THICKNESS[$code]['order'],
    ];
}

$sizes = [];

foreach ($groups as $members) {
    $sizes[count($members)] = ($sizes[count($members)] ?? 0) + 1;
}

ksort($sizes);

echo "\nGrouping:\n";

foreach ($sizes as $n => $c) {
    printf("  %4d group(s) of %d\n", $c, $n);
}

$inGroups = array_sum(array_map('count', $groups));

printf("  %d product(s) with no thickness in the SKU, left alone\n", count($ungrouped));
printf("\n  %d products -> %d listings in the shop\n", $inGroups, count($groups));

// A group of one is not a variation. Leaving variant_group set on it would make the
// storefront render a picker with a single button, which looks broken.
$single = 0;

foreach ($groups as $key => $members) {
    if (count($members) < 2) {
        $single++;
        unset($groups[$key]);
    }
}

if ($single > 0) {
    printf("  %d group(s) of one — not a variation, left ungrouped\n", $single);
}

echo "\n  Sample:\n";
$i = 0;

foreach ($groups as $key => $members) {
    usort($members, fn($a, $b) => $a['order'] <=> $b['order']);
    printf("    %s\n", $key);

    foreach ($members as $m) {
        printf("        %-8s %-18s $%s\n", $m['label'], $m['sku'], number_format((float)$m['website_price'], 2));
    }

    if (++$i >= 3) {
        break;
    }
}

if ($ungrouped !== []) {
    echo "\n  No thickness in the SKU (sample):\n";

    foreach (array_slice($ungrouped, 0, 5) as $u) {
        printf("    %-18s %s\n", $u['sku'], substr($u['name'], 0, 48));
    }
}

if (!$commit) {
    echo "\nNothing written. Add --commit to apply.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    // Clear first, so re-running after a catalogue change cannot leave a product
    // pointing at a group it is no longer part of.
    $pdo->prepare("UPDATE products SET variant_group = NULL, variant_label = NULL, variant_order = 0,
                   variant_axis = NULL
                   WHERE variant_group IS NOT NULL AND COALESCE(variant_axis,'Thickness') = 'Thickness'")->execute();

    $set = $pdo->prepare("UPDATE products SET variant_group = ?, variant_label = ?,
                          variant_order = ?, variant_axis = 'Thickness' WHERE id = ?");
    $n = 0;

    foreach ($groups as $key => $members) {
        foreach ($members as $m) {
            $set->execute([$key, $m['label'], $m['order'], (int)$m['id']]);
            $n++;
        }
    }

    $pdo->commit();
    printf("\nGrouped %d product(s) into %d listing(s).\n", $n, count($groups));
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $e;
}
