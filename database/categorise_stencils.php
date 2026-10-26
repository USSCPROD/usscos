<?php

declare(strict_types=1);

/**
 * File stencils under child categories, from the listing plan's own Category column.
 *
 *   php database/categorise_stencils.php --file=plan.xlsx
 *   php database/categorise_stencils.php --file=plan.xlsx --commit
 *
 * import_stencils.php puts everything under one 'Stencils' category, which is fine
 * for 21 products and useless for 200. The plan sheet already sorts them — Handicap,
 * Arrows, Number kits — so that column becomes real child categories rather than
 * being thrown away and re-derived later from product names.
 *
 * Categories are data, not configuration: filing them here means the internal app,
 * reports and the storefront all see the same arrangement, and somebody can move a
 * product between them in USSCOS without a deploy.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;

$args   = array_slice($argv, 1);
$commit = in_array('--commit', $args, true);
$file   = null;

foreach ($args as $a) {
    if (str_starts_with($a, '--file=')) {
        $file = substr($a, 7);
    }
}

if ($file === null || !is_file($file)) {
    fwrite(STDERR, "Usage: php database/categorise_stencils.php --file=/path/to.xlsx [--commit]\n");
    exit(1);
}

if (!$commit) {
    echo "DRY RUN — nothing will be written. Add --commit to apply.\n\n";
}

$book = IOFactory::createReaderForFile($file);
$book->setReadDataOnly(true);
$data = $book->load($file)->getSheet(0)->toArray(null, true, false, true);

$headerRow = null;

foreach (array_slice($data, 0, 5, true) as $i => $row) {
    foreach ($row as $c) {
        if (stripos(trim((string)$c), 'SKU') === 0) {
            $headerRow = $i;
            break 2;
        }
    }
}

if ($headerRow === null) {
    fwrite(STDERR, "No header row found.\n");
    exit(1);
}

$col = [];

foreach ($data[$headerRow] as $ci => $c) {
    $h = trim((string)$c);
    if ($h !== '') {
        $col[$h] = $ci;
    }
}

$get = fn(array $r, string $h): string => isset($col[$h]) ? trim((string)($r[$col[$h]] ?? '')) : '';

// SKU -> category name from the sheet
$wanted = [];

foreach (array_slice($data, $headerRow + 1, null, true) as $r) {
    if ($get($r, 'Product') === '') {
        continue;
    }

    $cat = $get($r, 'Category');

    if ($cat === '') {
        continue;
    }

    foreach (['SKU 1/8"', 'SKU 3/32"'] as $k) {
        $sku = $get($r, $k);

        if ($sku !== '') {
            $wanted[$sku] = $cat;
        }
    }
}

$have = [];

foreach (Database::select("SELECT id, sku FROM products WHERE is_active = 1") as $p) {
    $have[$p['sku']] = (int)$p['id'];
}

$plan = [];
$missing = 0;

foreach ($wanted as $sku => $cat) {
    if (!isset($have[$sku])) {
        $missing++;
        continue;
    }

    $plan[$cat][] = $have[$sku];
}

ksort($plan);

printf("%d SKU(s) in the sheet carry a category; %d are not loaded yet.\n\n", count($wanted), $missing);
printf("  %-26s %s\n", 'CHILD CATEGORY', 'PRODUCTS');

foreach ($plan as $cat => $ids) {
    printf("  %-26s %6d\n", $cat, count($ids));
}

if ($plan === []) {
    exit(0);
}

if (!$commit) {
    echo "\nNothing written. Add --commit to apply.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    $parent = Database::selectOne("SELECT id FROM categories WHERE name = 'Stencils' AND parent_id IS NULL LIMIT 1");

    if (!$parent) {
        throw new \RuntimeException("No top-level 'Stencils' category — run import_stencils.php first.");
    }

    $parentId = (int)$parent['id'];

    $findCat = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND parent_id = ? LIMIT 1");
    $makeCat = $pdo->prepare("INSERT INTO categories (parent_id, name, slug, is_active, show_on_website)
                              VALUES (?,?,?,1,1)");
    $map     = $pdo->prepare("INSERT IGNORE INTO product_categories (product_id, category_id, is_primary)
                              VALUES (?,?,0)");

    $made = 0;
    $filed = 0;

    foreach ($plan as $cat => $ids) {
        $findCat->execute([$cat, $parentId]);
        $row = $findCat->fetch(\PDO::FETCH_ASSOC);

        if ($row) {
            $catId = (int)$row['id'];
        } else {
            $slug = trim(strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $cat) ?? ''), '-');
            $makeCat->execute([$parentId, $cat, 'stencils-' . $slug]);
            $catId = (int)$pdo->lastInsertId();
            $made++;
        }

        foreach ($ids as $pid) {
            $map->execute([$pid, $catId]);
            $filed++;
        }
    }

    $pdo->commit();
    printf("\nCreated %d child categor(ies), filed %d product(s).\n", $made, $filed);
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $e;
}
