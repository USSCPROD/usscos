<?php

declare(strict_types=1);

/**
 * Stencil catalogue importer — the Amazon listing plan.
 *
 *   php database/import_stencils.php --file=plan.xlsx                 dry run
 *   php database/import_stencils.php --file=plan.xlsx --launch-only   just priority 1
 *   php database/import_stencils.php --file=plan.xlsx --commit
 *
 * WHY THIS IS NOT import_products.php. That script reads one product per row. This
 * sheet puts TWO products on every row — the same stencil cut in 1/8" and in 3/32",
 * each with its own SKU and its own price — so 372 rows describe 743 products. Run
 * it through the product importer and you would get 372 products, each carrying one
 * thickness's SKU and the other thickness's price.
 *
 * WHAT THE SHEET DOES NOT CARRY. No weight, no shipping dimensions, no GTIN, no cost.
 * 'Cut size' is the size of the cut artwork, not the size of the parcel. Products
 * without a weight cannot be cartonised, so a stencil on an order will not produce a
 * packing plan until somebody weighs one. That is a real consequence of loading these,
 * not a detail — it is reported at the end of every run so it cannot be forgotten.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;

$argvList   = $argv ?? [];
$commit     = in_array('--commit', $argvList, true);
$launchOnly = in_array('--launch-only', $argvList, true);
$file       = null;

foreach ($argvList as $a) {
    if (str_starts_with($a, '--file=')) {
        $file = substr($a, 7);
    }
}

if ($file === null || !is_file($file)) {
    fwrite(STDERR, "Usage: php database/import_stencils.php --file=/path/to.xlsx [--launch-only] [--commit]\n");
    exit(1);
}

if (!$commit) {
    echo "DRY RUN — nothing will be written. Add --commit to apply.\n\n";
}

// ---------------------------------------------------------------------------
// Read the sheet, one row at a time, two products at a time
// ---------------------------------------------------------------------------

$book = IOFactory::createReaderForFile($file);
$book->setReadDataOnly(true);
$data = $book->load($file)->getSheet(0)->toArray(null, true, false, true);

$headerRow = null;

foreach (array_slice($data, 0, 5, true) as $i => $row) {
    foreach ($row as $cell) {
        if (stripos(trim((string)$cell), 'SKU') === 0) {
            $headerRow = $i;
            break 2;
        }
    }
}

if ($headerRow === null) {
    fwrite(STDERR, "No header row with a SKU column found.\n");
    exit(1);
}

$col = [];

foreach ($data[$headerRow] as $ci => $cell) {
    $h = trim((string)$cell);
    if ($h !== '') {
        $col[$h] = $ci;
    }
}

/** Read a labelled cell from a row, '' when the column is absent. */
$cell = function (array $row, string $label) use ($col): string {
    return isset($col[$label]) ? trim((string)($row[$col[$label]] ?? '')) : '';
};

$money = fn(string $v): float => (float)preg_replace('/[^0-9.]/', '', $v);

// The two thickness variants that share a row.
$VARIANTS = [
    ['sku' => 'SKU 1/8"',  'price' => 'Your retail 1/8" ($)',  'label' => '1/8"'],
    ['sku' => 'SKU 3/32"', 'price' => 'Your retail 3/32" ($)', 'label' => '3/32"'],
];

$wanted  = [];
$skipped = 0;

/* Keyed, not positional. toArray() numbers rows from 1, so the header at key H sits
   at POSITION H-1 - array_slice(offset: H+1) therefore skipped the first data row
   of every sheet, silently. Filtering on the key cannot drift that way. */
foreach (array_filter($data, fn($k) => $k > $headerRow, ARRAY_FILTER_USE_KEY) as $row) {
    $product = $cell($row, 'Product');

    if ($product === '') {
        continue;
    }

    $priority = $cell($row, 'Priority');

    if ($launchOnly && !str_starts_with($priority, '1')) {
        $skipped++;
        continue;
    }

    foreach ($VARIANTS as $v) {
        $sku = $cell($row, $v['sku']);

        if ($sku === '') {
            continue;
        }

        $wanted[$sku] = [
            'sku'      => $sku,
            'name'     => $product . ' stencil — ' . $v['label'],
            'price'    => $money($cell($row, $v['price'])),
            'cut'      => $cell($row, 'Cut size (in)'),
            'category' => $cell($row, 'Category'),
            'priority' => $priority,
        ];
    }
}

printf("%d stencil SKU(s) in the sheet%s.\n", count($wanted), $launchOnly ? " (launch only; {$skipped} later-priority rows skipped)" : '');

if ($wanted === []) {
    exit(0);
}

// ---------------------------------------------------------------------------
// Compare against what we already have
// ---------------------------------------------------------------------------

$existing = [];

foreach (Database::select("SELECT id, sku, name, price, website_price FROM products") as $p) {
    $existing[$p['sku']] = $p;
}

$toInsert = $toUpdate = [];

foreach ($wanted as $sku => $w) {
    if (isset($existing[$sku])) {
        // Only the price is taken for a product we already hold. The name in our
        // catalogue was written by someone who knows the product; the sheet's is
        // derived from a listing plan and is not an improvement on it.
        $cur = $existing[$sku];

        if ($w['price'] > 0 && (abs((float)$cur['price'] - $w['price']) > 0.005
                             || abs((float)$cur['website_price'] - $w['price']) > 0.005)) {
            $toUpdate[$sku] = $w + ['id' => $cur['id'], 'was' => (float)$cur['price']];
        }
    } else {
        $toInsert[$sku] = $w;
    }
}

printf("\n  → INSERT new stencils : %d\n", count($toInsert));
printf("  → UPDATE price only   : %d\n", count($toUpdate));
printf("  → already correct     : %d\n", count($wanted) - count($toInsert) - count($toUpdate));

$byCat = [];

foreach ($toInsert as $w) {
    $byCat[$w['category'] ?: '(none)'] = ($byCat[$w['category'] ?: '(none)'] ?? 0) + 1;
}

arsort($byCat);

echo "\n  New stencils by category:\n";

foreach ($byCat as $c => $n) {
    printf("    %-26s %4d\n", $c, $n);
}

echo "\n  Sample of what would be created:\n";

foreach (array_slice($toInsert, 0, 6) as $w) {
    printf("    %-18s \$%-9s %s\n", $w['sku'], number_format($w['price'], 2), $w['name']);
}

foreach (array_slice($toUpdate, 0, 6) as $w) {
    printf("    price %-14s \$%s → \$%s\n", $w['sku'], number_format($w['was'], 2), number_format($w['price'], 2));
}

if (!$commit) {
    echo "\nNothing written. Add --commit to apply.\n";
    exit(0);
}

// ---------------------------------------------------------------------------
// Write, in one transaction
// ---------------------------------------------------------------------------

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    // One category for the storefront section to hang off, so it stops depending on
    // a name match. Created here rather than in a migration because it only matters
    // once there are stencils to put in it.
    $cat = Database::selectOne("SELECT id FROM categories WHERE name = 'Stencils' LIMIT 1");

    if (!$cat) {
        $pdo->prepare("INSERT INTO categories (name, slug, is_active, show_on_website)
                       VALUES ('Stencils', 'stencils', 1, 1)")->execute();
        $catId = (int)$pdo->lastInsertId();
        echo "  + created category 'Stencils'\n";
    } else {
        $catId = (int)$cat['id'];
    }

    $brand = Database::selectOne("SELECT id FROM product_brands WHERE name = 'US Specialty Coatings' LIMIT 1");
    $brandId = $brand ? (int)$brand['id'] : null;

    // qty_on_hand, is_active and friends are NOT NULL DEFAULT — omitted, never sent
    // as null. Stencils are cut to order, so an on-hand quantity would be a fiction.
    $ins = $pdo->prepare(
        "INSERT INTO products
            (sku, name, short_description, quickbooks_item, item_type, pack_level,
             brand_id, price, website_price, is_active)
         VALUES (?,?,?,?,'inventory_part','Each',?,?,?,1)"
    );

    $map = $pdo->prepare("INSERT IGNORE INTO product_categories (product_id, category_id, is_primary)
                          VALUES (?,?,1)");

    foreach ($toInsert as $w) {
        $desc = $w['cut'] !== '' ? 'Cut size ' . $w['cut'] . ' inches.' : null;

        $ins->execute([
            $w['sku'], $w['name'], $desc,
            'US SPECIALTY COATINGS:' . $w['sku'],
            $brandId, $w['price'], $w['price'],
        ]);

        $map->execute([(int)$pdo->lastInsertId(), $catId]);
    }

    $upd = $pdo->prepare("UPDATE products SET price = ?, website_price = ? WHERE id = ?");

    foreach ($toUpdate as $w) {
        $upd->execute([$w['price'], $w['price'], $w['id']]);
        $map->execute([(int)$w['id'], $catId]);
    }

    // Existing stencils that were already correctly priced still belong in the
    // category, or the storefront section shows a partial catalogue.
    foreach ($wanted as $sku => $w) {
        if (isset($existing[$sku])) {
            $map->execute([(int)$existing[$sku]['id'], $catId]);
        }
    }

    $pdo->commit();

    printf("\nInserted %d, repriced %d.\n", count($toInsert), count($toUpdate));
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $e;
}

// ---------------------------------------------------------------------------
// The consequence, stated every time
// ---------------------------------------------------------------------------

$noWeight = (int)Database::selectOne("
    SELECT COUNT(*) c FROM products p
    JOIN product_categories pc ON pc.product_id = p.id
    JOIN categories c ON c.id = pc.category_id AND c.name = 'Stencils'
    WHERE p.is_active = 1 AND COALESCE(p.gross_weight,0) = 0
")['c'];

if ($noWeight > 0) {
    echo "\n";
    echo "  ----------------------------------------------------------------\n";
    printf("  %d stencil(s) have no shipping weight.\n", $noWeight);
    echo "  The cartonizer skips a line with no weight, so an order containing\n";
    echo "  only stencils will produce no packing plan and no label. Weigh a\n";
    echo "  representative few and set gross_weight before shipping any.\n";
    echo "  ----------------------------------------------------------------\n";
}
