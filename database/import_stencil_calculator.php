<?php

declare(strict_types=1);

/**
 * The stencil price sheet — weight, cost, wholesale and retail for every stencil.
 *
 *   php database/import_stencil_calculator.php --file=calc.xlsx            dry run
 *   php database/import_stencil_calculator.php --file=calc.xlsx --commit   update loaded ones
 *   php database/import_stencil_calculator.php --file=calc.xlsx --commit --insert-new
 *
 * This is the authoritative stencil source: 1,222 SKUs against the listing plan's
 * 741, and unlike the plan it carries a WEIGHT. That matters more than the extra
 * SKUs — the cartonizer skips any line with no weight, so every stencil loaded so
 * far produces no packing plan and no shipping label.
 *
 * The sheet is a calculator, so its numbers come out at full floating-point
 * precision: a stencil weighs 1.6794622395833 lb and costs 6.0625709375. Those are
 * rounded on the way in. Storing the raw value would show a customer a price of
 * $16.672070078125 and tell FedEx a weight no scale can produce.
 *
 * WEIGHT IS THE PLASTIC, NOT THE PARCEL. The calculator derives weight from sheet
 * area, so it is the material alone with no packaging, tube or pallet. It goes to
 * net_weight, and to gross_weight only where gross is still empty — a measured
 * gross weight is worth more than a derived one and is never overwritten.
 *
 * Two sheet layouts, one rule: a SKU cell is followed by Weight, Cost, Wholesale,
 * Retail in that order, in both the kit blocks and the word-stencil table.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;

$args      = array_slice($argv, 1);
$commit    = in_array('--commit', $args, true);
$insertNew = in_array('--insert-new', $args, true);
$file      = null;

foreach ($args as $a) {
    if (str_starts_with($a, '--file=')) {
        $file = substr($a, 7);
    }
}

if ($file === null || !is_file($file)) {
    fwrite(STDERR, "Usage: php database/import_stencil_calculator.php --file=/path/to.xlsx [--insert-new] [--commit]\n");
    exit(1);
}

if (!$commit) {
    echo "DRY RUN — nothing will be written. Add --commit to apply.\n\n";
}

$book = IOFactory::createReaderForFile($file);
$book->setReadDataOnly(true);
$workbook = $book->load($file);

$num = function ($v): ?float {
    $v = trim((string)$v);

    if ($v === '') {
        return null;
    }

    $v = preg_replace('/[^0-9.\-]/', '', $v) ?? '';

    return ($v === '' || !is_numeric($v)) ? null : (float)$v;
};

$rows = [];

foreach ($workbook->getSheetNames() as $sheetName) {
    $data = $workbook->getSheetByName($sheetName)->toArray(null, true, false, true);

    foreach ($data as $r) {
        $vals = array_values($r);

        foreach ($vals as $i => $v) {
            $sku = trim((string)$v);

            // A stencil SKU ends in its thickness, optionally followed by -C for curbs.
            if (!preg_match('/^ST[A-Z0-9.\-]*-(8|32)(-C)?$/i', $sku)) {
                continue;
            }

            $weight    = $num($vals[$i + 1] ?? null);
            $cost      = $num($vals[$i + 2] ?? null);
            $wholesale = $num($vals[$i + 3] ?? null);
            $retail    = $num($vals[$i + 4] ?? null);

            // A SKU mentioned without its numbers is a cross-reference, not a row.
            if ($weight === null && $retail === null) {
                continue;
            }

            // Description is the first cell of the row in both layouts.
            $desc = trim((string)($vals[0] ?? ''));

            $rows[strtoupper($sku)] = [
                'sku'       => $sku,
                'desc'      => $desc,
                'weight'    => $weight === null ? null : round($weight, 2),
                'cost'      => $cost === null ? null : round($cost, 4),
                'wholesale' => $wholesale === null ? null : round($wholesale, 2),
                'retail'    => $retail === null ? null : round($retail, 2),
            ];
        }
    }
}

printf("%d stencil SKU(s) in the price sheet.\n", count($rows));

$existing = [];

foreach (Database::select("SELECT id, sku, gross_weight, net_weight, case_weight_gross, units_per_case,
                   cost, dist_price, price, website_price FROM products") as $p) {
    $existing[strtoupper($p['sku'])] = $p;
}

$updates = $inserts = [];
$stats = ['weight' => 0, 'gross' => 0, 'case' => 0, 'cost' => 0, 'wholesale' => 0, 'retail' => 0];

foreach ($rows as $key => $r) {
    if (!isset($existing[$key])) {
        $inserts[] = $r;
        continue;
    }

    $cur = $existing[$key];
    $set = [];

    if ($r['weight'] !== null) {
        $set['net_weight'] = $r['weight'];
        $stats['weight']++;

        // Only where gross is unset: a weighed parcel beats a figure derived from
        // sheet area, and overwriting one with the other loses the better number.
        if ((float)$cur['gross_weight'] <= 0) {
            $set['gross_weight'] = $r['weight'];
            $stats['gross']++;
        }

        // THE COLUMN THE CARTONIZER ACTUALLY READS. It selects case_weight_gross,
        // not gross_weight, so filling gross_weight alone leaves every stencil
        // reported as 'has no case weight' and the packing plan unweighed. A stencil
        // is sold Each, so its case is the item itself and the two are the same
        // number; this is set only where units_per_case says the product is not
        // genuinely cased, and never over a value somebody has already measured.
        if ((float)($cur['case_weight_gross'] ?? 0) <= 0 && (float)($cur['units_per_case'] ?? 0) <= 1) {
            $set['case_weight_gross'] = $r['weight'];
            $stats['case']++;
        }

        $set['weight_unit'] = 'Pounds';
    }

    /*
     * NO DIMENSIONS. A stencil is a thin sheet that gets rolled, so a 74" x 20" sheet
     * ships in a tube far smaller than its flat size — deriving case dimensions from
     * the artwork would hand FedEx a parcel nobody has ever shipped. Which box a
     * rolled stencil goes in is a packing rule against a real shipping_boxes row,
     * set by the people who roll them, not arithmetic over the cut size.
     */

    if ($r['cost'] !== null)      { $set['cost'] = $r['cost'];            $stats['cost']++; }
    if ($r['wholesale'] !== null) { $set['dist_price'] = $r['wholesale']; $stats['wholesale']++; }

    if ($r['retail'] !== null) {
        $set['price'] = $r['retail'];
        $set['website_price'] = $r['retail'];
        $stats['retail']++;
    }

    if ($set !== []) {
        $updates[] = ['id' => (int)$cur['id'], 'sku' => $r['sku'], 'set' => $set];
    }
}

printf("\n  in USSCOS already : %d  (would be updated)\n", count($updates));
printf("  not yet loaded    : %d%s\n", count($inserts), $insertNew ? '  (would be inserted)' : '  (ignored without --insert-new)');

echo "\n  Fields filled or refreshed on the loaded ones:\n";
printf("    net weight      %4d\n", $stats['weight']);
printf("    gross weight    %4d   (only where it was empty)\n", $stats['gross']);
printf("    CASE weight     %4d   (what the cartonizer reads)\n", $stats['case']);
printf("    cost            %4d\n", $stats['cost']);
printf("    wholesale       %4d\n", $stats['wholesale']);
printf("    retail price    %4d\n", $stats['retail']);

echo "\n  Sample:\n";

foreach (array_slice($updates, 0, 5) as $u) {
    printf("    %-18s %s\n", $u['sku'], json_encode($u['set']));
}

if (!$commit) {
    echo "\nNothing written. Add --commit to apply.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    $n = 0;

    foreach ($updates as $u) {
        $cols = [];
        $vals = [];

        foreach ($u['set'] as $c => $v) {
            $cols[] = "`{$c}` = ?";
            $vals[] = $v;
        }

        $vals[] = $u['id'];
        $pdo->prepare("UPDATE products SET " . implode(', ', $cols) . " WHERE id = ?")->execute($vals);
        $n++;
    }

    $made = 0;

    if ($insertNew && $inserts !== []) {
        $brand = Database::selectOne("SELECT id FROM product_brands WHERE name = 'US Specialty Coatings' LIMIT 1");
        $cat   = Database::selectOne("SELECT id FROM categories WHERE name = 'Stencils' AND parent_id IS NULL LIMIT 1");

        $ins = $pdo->prepare(
            "INSERT INTO products
                (sku, name, short_description, quickbooks_item, item_type, pack_level, brand_id,
                 cost, dist_price, price, website_price, net_weight, gross_weight,
                 case_weight_gross, weight_unit, is_active)
             VALUES (?,?,?,?,'inventory_part','Each',?,?,?,?,?,?,?,?,'Pounds',1)"
        );

        $map = $pdo->prepare("INSERT IGNORE INTO product_categories (product_id, category_id, is_primary) VALUES (?,?,1)");

        foreach ($inserts as $r) {
            $thick = str_ends_with(strtoupper($r['sku']), '-32') || str_contains(strtoupper($r['sku']), '-32-')
                ? '3/32"' : '1/8"';
            $name = ($r['desc'] !== '' ? $r['desc'] : $r['sku']) . ' stencil — ' . $thick;

            $ins->execute([
                $r['sku'], $name, null,
                'US SPECIALTY COATINGS:' . $r['sku'],
                $brand ? (int)$brand['id'] : null,
                $r['cost'], $r['wholesale'], $r['retail'], $r['retail'],
                $r['weight'], $r['weight'], $r['weight'],
            ]);

            if ($cat) {
                $map->execute([(int)$pdo->lastInsertId(), (int)$cat['id']]);
            }

            $made++;
        }
    }

    $pdo->commit();
    printf("\nUpdated %d, inserted %d.\n", $n, $made);
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $e;
}

echo "\nRe-run database/group_stencil_variants.php --commit if any were inserted.\n";
