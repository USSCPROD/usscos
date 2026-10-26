<?php

declare(strict_types=1);

/**
 * Remove a stencil thickness we no longer cut.
 *
 *   php database/retire_stencil_thickness.php --thickness=16              dry run
 *   php database/retire_stencil_thickness.php --thickness=16 --commit     deactivate
 *   php database/retire_stencil_thickness.php --thickness=16 --commit --delete
 *
 * DEACTIVATE IS THE DEFAULT, AND USUALLY THE RIGHT ANSWER. A product is referenced
 * from a dozen tables. Quote and sales-order lines and inventory transactions are
 * ON DELETE RESTRICT, but invoice, bill and PO lines are ON DELETE SET NULL — so a
 * delete SUCCEEDS while quietly cutting the product off historical invoices, which
 * then say a quantity and a price for nothing in particular.
 *
 * --delete is therefore allowed only for products nothing references at all, and
 * that is re-checked inside the transaction rather than trusted from the dry run.
 * Anything with a single reference is deactivated instead and reported.
 *
 * The thickness is matched as a SKU SEGMENT, so 'STNP-C04-16' is found. Matching a
 * bare '16' anywhere in the string would also take 'ST-16TH-ST-8', a 16th Street
 * stencil in 1/8".
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;

$args      = array_slice($argv, 1);
$commit    = in_array('--commit', $args, true);
$hardDelete = in_array('--delete', $args, true);
$thickness = null;

foreach ($args as $a) {
    if (str_starts_with($a, '--thickness=')) {
        $thickness = preg_replace('/\D/', '', substr($a, 12));
    }
}

if ($thickness === null || $thickness === '') {
    fwrite(STDERR, "Usage: php database/retire_stencil_thickness.php --thickness=16 [--commit] [--delete]\n");
    exit(1);
}

if (!$commit) {
    echo "DRY RUN — nothing will be written. Add --commit to apply.\n\n";
}

$products = Database::select("
    SELECT id, sku, name, is_active, variant_group
    FROM products
    WHERE sku REGEXP ?
    ORDER BY sku
", ['(^|-)' . $thickness . '($|-)']);

printf("%d product(s) with a '-%s-' segment in the SKU.\n\n", count($products), $thickness);

if ($products === []) {
    echo "Nothing to do.\n";
    exit(0);
}

/** Every table that points at a product, discovered rather than listed from memory. */
$tables = [];

foreach (Database::select("SHOW TABLES") as $t) {
    $name = array_values($t)[0];

    foreach (Database::select("SHOW COLUMNS FROM `{$name}`") as $c) {
        if ($c['Field'] === 'product_id') {
            $tables[] = $name;
            break;
        }
    }
}

/**
 * Where a product is referenced. product_categories and the storefront's own
 * bookkeeping do not count as history — they are ours to clean up, not a record of
 * something that happened.
 *
 * @return string[]
 */
$referencesOf = function (int $id) use ($tables): array {
    $out = [];

    foreach ($tables as $t) {
        if (in_array($t, ['product_categories', 'product_images', 'product_documents',
                          'product_packing_rules', 'product_stock', 'marketplace_sku_map'], true)) {
            continue;
        }

        $c = (int)Database::selectOne("SELECT COUNT(*) c FROM `{$t}` WHERE product_id = ?", [$id])['c'];

        if ($c > 0) {
            $out[] = "{$c} in {$t}";
        }
    }

    return $out;
};

$free = $held = [];

foreach ($products as $p) {
    $refs = $referencesOf((int)$p['id']);

    printf("  %-18s %-44s %s\n", $p['sku'], substr($p['name'], 0, 44),
        $refs === [] ? 'unreferenced' : implode(', ', $refs));

    if ($refs === []) {
        $free[] = $p;
    } else {
        $held[] = $p;
    }
}

printf("\n  unreferenced : %d\n  referenced   : %d\n", count($free), count($held));

$action = $hardDelete ? 'DELETE' : 'deactivate';

printf("\n  Plan: %s %d unreferenced, deactivate %d referenced.\n",
    $action, count($free), count($held));

if ($hardDelete && $held !== []) {
    echo "  (the referenced ones are never deleted, whatever the flags say)\n";
}

if (!$commit) {
    echo "\nNothing written. Add --commit to apply";
    echo $hardDelete ? ".\n" : ", and --delete to remove the unreferenced ones outright.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    $deleted = $deactivated = 0;

    $off = $pdo->prepare("UPDATE products SET is_active = 0 WHERE id = ?");
    $del = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $unmap = $pdo->prepare("DELETE FROM product_categories WHERE product_id = ?");

    foreach ($free as $p) {
        if (!$hardDelete) {
            $off->execute([(int)$p['id']]);
            $deactivated++;
            continue;
        }

        // Re-check inside the transaction. The dry run may have been minutes ago and
        // somebody may have quoted one of these since.
        if ($referencesOf((int)$p['id']) !== []) {
            $off->execute([(int)$p['id']]);
            $deactivated++;
            printf("  ! %s picked up a reference since the dry run — deactivated instead\n", $p['sku']);
            continue;
        }

        $unmap->execute([(int)$p['id']]);
        $del->execute([(int)$p['id']]);
        $deleted++;
    }

    foreach ($held as $p) {
        $off->execute([(int)$p['id']]);
        $deactivated++;
    }

    $pdo->commit();
    printf("\nDeleted %d, deactivated %d.\n", $deleted, $deactivated);
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $e;
}

echo "\nRe-run database/group_stencil_variants.php --commit so the listings that had\n";
echo "three thicknesses stop offering one that no longer exists.\n";
