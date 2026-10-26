<?php

declare(strict_types=1);

/**
 * Which column should the product import join on?
 *
 *   php database/analyze_product_file.php --file=/path/to/new.xlsx
 *
 * READS ONLY. There is no --commit and no write path in this file at all.
 *
 * WHY THIS EXISTS. import_products.php matches on SKU. When a new product file
 * renumbers the catalogue, that join silently stops matching anything: every row
 * looks new, the importer inserts it, and we end up with two parallel catalogues
 * where 43,512 invoice lines point at the old one and every future order points at
 * the new one. Nothing errors. The damage is only visible weeks later when someone
 * asks why a product's sales history is empty.
 *
 * So before importing an unfamiliar file, try every candidate key against the
 * products we already have and see which one actually lands. The file answers the
 * question better than anyone's recollection of how the SKUs were derived.
 *
 * A key is only usable if it is UNIQUE on both sides. A key that matches 800 rows
 * but maps three of them to the same product is not a 97% success - it is a quiet
 * corruption of those three, so ambiguity is counted separately and loudly.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;

$argvList = $argv ?? [];
$file     = null;
$sheetArg = null;

foreach ($argvList as $a) {
    if (str_starts_with($a, '--file='))  { $file     = substr($a, 7); }
    if (str_starts_with($a, '--sheet=')) { $sheetArg = substr($a, 8); }
}

if ($file === null || !is_file($file)) {
    fwrite(STDERR, "Usage: php database/analyze_product_file.php --file=/path/to.xlsx [--sheet=NAME]\n");
    exit(1);
}

/** Same normalisation import_products.php uses, so a column it can read is a column we test. */
function aNorm(?string $h): string
{
    $h = strtolower(trim((string)$h));
    $h = str_replace(['(', ')', '#', '.', '/', '—', '–', '_'], ' ', $h);

    return preg_replace('/\s+/', ' ', $h) ?? '';
}

/**
 * Compare codes the way a human means them to compare. GTINs arrive from Excel with
 * lost leading zeros and stray spaces; SKUs arrive with inconsistent case and dashes.
 * Without this, a key that genuinely matches reads as a key that matches nothing.
 */
function aKey(string $kind, ?string $v): string
{
    $v = trim((string)$v);

    if ($v === '' || strtolower($v) === 'nan') {
        return '';
    }

    if (str_starts_with($kind, 'gtin')) {
        $digits = preg_replace('/\D/', '', $v) ?? '';
        // Compare GTINs at their widest form: a GTIN-12 is the same article as the
        // GTIN-14 that is it with zeros in front, and half our catalogue stores one
        // while a new file may quote the other.
        return $digits === '' ? '' : ltrim($digits, '0');
    }

    return strtoupper(preg_replace('/\s+/', '', $v) ?? '');
}

echo "Reading {$file}\n";

$book   = IOFactory::createReaderForFile($file);
$book->setReadDataOnly(true);
$sheets = $book->load($file);

// ---------------------------------------------------------------------------
// Pull every row out of the sheet(s), keyed by normalised header
// ---------------------------------------------------------------------------

$rows    = [];
$headers = [];

foreach ($sheets->getSheetNames() as $sheetName) {
    if ($sheetArg !== null && $sheetArg !== $sheetName) {
        continue;
    }

    $data = $sheets->getSheetByName($sheetName)->toArray(null, true, false, true);

    // The header row is the first one in the top five that has a SKU-ish column.
    $headerIdx = null;

    foreach (array_slice($data, 0, 5, true) as $i => $row) {
        foreach ($row as $cell) {
            if (in_array(aNorm(is_scalar($cell) ? (string)$cell : ''), ['sku', 'new sku', 'item'], true)) {
                $headerIdx = $i;
                break 2;
            }
        }
    }

    if ($headerIdx === null) {
        echo "  skipped sheet '{$sheetName}' — no SKU-like header in the first five rows\n";
        continue;
    }

    $cols = [];

    foreach ($data[$headerIdx] as $ci => $cell) {
        $h = aNorm(is_scalar($cell) ? (string)$cell : '');
        if ($h !== '') {
            $cols[$ci] = $h;
            $headers[$h] = true;
        }
    }

    foreach (array_slice($data, $headerIdx + 1, null, true) as $row) {
        $rec = [];
        $any = false;

        foreach ($cols as $ci => $h) {
            $v = $row[$ci] ?? null;
            $rec[$h] = is_scalar($v) ? trim((string)$v) : '';
            if ($rec[$h] !== '') {
                $any = true;
            }
        }

        if ($any) {
            $rec['_sheet'] = $sheetName;
            $rows[] = $rec;
        }
    }
}

printf("\n%d data row(s) across %d column(s)\n", count($rows), count($headers));

if ($rows === []) {
    echo "Nothing to analyse.\n";
    exit(1);
}

echo "\nColumns found:\n  " . implode("\n  ", array_keys($headers)) . "\n";

// ---------------------------------------------------------------------------
// Candidate keys: a sheet column paired with the products column it would join to
// ---------------------------------------------------------------------------

$candidates = [
    ['sheet' => ['sku', 'new sku', 'item'],                     'db' => 'sku',                 'kind' => 'sku'],
    ['sheet' => ['old sku', 'legacy sku', 'previous sku',
                 'current sku', 'existing sku', 'qb sku',
                 'quickbooks sku', 'old item'],                 'db' => 'sku',                 'kind' => 'sku'],
    ['sheet' => ['gtin 12 upc', 'gtin 12', 'upc'],              'db' => 'gtin12',              'kind' => 'gtin12'],
    ['sheet' => ['gtin 14', 'case gtin', 'gtin14'],             'db' => 'gtin14',              'kind' => 'gtin14'],
    ['sheet' => ['mpn'],                                        'db' => 'mpn',                 'kind' => 'mpn'],
    ['sheet' => ['vendor part', 'vendor part number'],          'db' => 'vendor_part_number',  'kind' => 'vpn'],
    ['sheet' => ['asin'],                                       'db' => 'asin',                'kind' => 'asin'],
    ['sheet' => ['product name', 'name', 'description'],        'db' => 'name',                'kind' => 'name'],
];

// Cross-match GTIN too: a sheet's GTIN-12 may be stored here as a GTIN-14 and the
// other way round, and aKey() strips the padding that distinguishes them.
$candidates[] = ['sheet' => ['gtin 12 upc', 'gtin 12', 'upc'], 'db' => 'gtin14', 'kind' => 'gtin12'];
$candidates[] = ['sheet' => ['gtin 14', 'gtin14'],             'db' => 'gtin12', 'kind' => 'gtin14'];

$products = Database::select(
    "SELECT id, sku, name, gtin12, gtin14, mpn, vendor_part_number, asin, is_active FROM products"
);

printf("\n%d product(s) in the database to match against.\n", count($products));

$results = [];

foreach ($candidates as $c) {
    // Which of this candidate's acceptable header spellings is actually in the sheet?
    $col = null;

    foreach ($c['sheet'] as $h) {
        if (isset($headers[$h])) {
            $col = $h;
            break;
        }
    }

    if ($col === null) {
        continue;
    }

    // Index the database side, noting any value that is not unique.
    $index = [];
    $dupDb = [];

    foreach ($products as $p) {
        $k = aKey($c['kind'], (string)($p[$c['db']] ?? ''));

        if ($k === '') {
            continue;
        }

        if (isset($index[$k])) {
            $dupDb[$k] = true;
        }

        $index[$k][] = $p;
    }

    $matched = $ambiguous = $blank = $unmatched = 0;
    $seen = [];
    $dupSheet = 0;
    $samplesUnmatched = [];
    $samplesMatched   = [];

    foreach ($rows as $r) {
        $k = aKey($c['kind'], (string)($r[$col] ?? ''));

        if ($k === '') {
            $blank++;
            continue;
        }

        if (isset($seen[$k])) {
            $dupSheet++;
        }
        $seen[$k] = true;

        if (!isset($index[$k])) {
            $unmatched++;
            if (count($samplesUnmatched) < 4) {
                $samplesUnmatched[] = (string)$r[$col];
            }
            continue;
        }

        if (count($index[$k]) > 1) {
            $ambiguous++;
            continue;
        }

        $matched++;
        if (count($samplesMatched) < 3) {
            $samplesMatched[] = $r[$col] . ' → ' . $index[$k][0]['sku'];
        }
    }

    $results[] = [
        'label'     => "sheet '{$col}'  →  products.{$c['db']}",
        'matched'   => $matched,
        'ambiguous' => $ambiguous,
        'unmatched' => $unmatched,
        'blank'     => $blank,
        'dupSheet'  => $dupSheet,
        'dupDb'     => count($dupDb),
        'exUnmatched' => $samplesUnmatched,
        'exMatched'   => $samplesMatched,
    ];
}

if ($results === []) {
    echo "\nNo candidate key columns recognised in this sheet.\n";
    echo "Tell me which column should tie a row to an existing product and I'll add it.\n";
    exit(1);
}

usort($results, fn($a, $b) => $b['matched'] <=> $a['matched']);

echo "\n" . str_repeat('=', 78) . "\n";
echo "CANDIDATE JOIN KEYS, best first\n";
echo str_repeat('=', 78) . "\n";

$total = count($rows);

foreach ($results as $r) {
    printf("\n%s\n", $r['label']);
    printf("  matched      %5d  (%.1f%% of %d rows)\n", $r['matched'], $total ? $r['matched'] / $total * 100 : 0, $total);

    if ($r['ambiguous']) {
        printf("  AMBIGUOUS    %5d  <-- these hit more than one product; unusable as-is\n", $r['ambiguous']);
    }

    printf("  unmatched    %5d\n", $r['unmatched']);

    if ($r['blank']) {
        printf("  blank        %5d  (column empty on these rows)\n", $r['blank']);
    }

    if ($r['dupSheet']) {
        printf("  repeated in sheet %d\n", $r['dupSheet']);
    }

    if ($r['dupDb']) {
        printf("  not unique in database on %d value(s)\n", $r['dupDb']);
    }

    foreach ($r['exMatched'] as $s) {
        echo "    e.g. {$s}\n";
    }

    foreach ($r['exUnmatched'] as $s) {
        echo "    no match: {$s}\n";
    }
}

$best = $results[0];

echo "\n" . str_repeat('=', 78) . "\n";

if ($best['matched'] === 0) {
    echo "VERDICT: nothing in this sheet ties back to the existing catalogue.\n\n";
    echo "These are new products as far as the database can tell. Importing them means\n";
    echo "two catalogues side by side — which is the right answer only if the old ones\n";
    echo "are genuinely being retired. Deactivate them, never delete: 43,512 invoice\n";
    echo "lines reference the current rows and must keep doing so.\n";
} elseif ($best['ambiguous'] > 0) {
    printf("VERDICT: %s is the closest, but %d row(s) are ambiguous.\n\n",
        $best['label'], $best['ambiguous']);
    echo "Do not import on this key until those are resolved — each one would write a\n";
    echo "new product's data over whichever row it happened to pick.\n";
} elseif ($best['matched'] === $total - $best['blank']) {
    printf("VERDICT: %s matches every non-blank row cleanly.\n\n", $best['label']);
    echo "Safe to import on this key.\n";
} else {
    printf("VERDICT: %s matches %d of %d; %d would insert as new products.\n\n",
        $best['label'], $best['matched'], $total, $best['unmatched']);
    echo "Check the unmatched samples above. If they are genuinely new items, that is\n";
    echo "correct. If they should have matched, the key is wrong or the values differ.\n";
}

echo "\nNothing was written. This script has no write path.\n";
