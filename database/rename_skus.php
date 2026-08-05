<?php
/**
 * SKU Rename Tool
 *
 * When the product master spreadsheet re-SKUs an existing product, the importer can't
 * tell a rename from a new product — it matches on SKU. Left alone it inserts the new
 * SKU and orphans the old row, so the same physical product ends up in the catalog twice,
 * with the order history attached to the copy nobody uses.
 *
 * This script renames in place FIRST, so quotes, sales orders, invoices, bills, POs and
 * inventory transactions all keep pointing at the right product. Then the importer runs
 * clean.
 *
 * A rename is only proposed when a database SKU absent from the workbook has exactly one
 * workbook counterpart with an identical product name AND colour. Anything less certain is
 * reported, never applied.
 *
 * Usage (from the app root on the server):
 *
 *   # 1. See the plan. Writes nothing.
 *   php database/rename_skus.php
 *
 *   # 2. Apply the confident renames.
 *   php database/rename_skus.php --commit
 *
 * Options:
 *   --file=/path/to.xlsx   Workbook to compare against
 *   --map=/path/to.csv     Apply an explicit old_sku,new_sku map instead of matching
 *                          (use this for the ambiguous ones, once decided by hand)
 *
 * Run this BEFORE database/import_products.php whenever a new workbook changes SKUs.
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// ---------------------------------------------------------------------------
// Arguments
// ---------------------------------------------------------------------------

$argvList = $argv ?? [];
$commit   = in_array('--commit', $argvList, true);

$file    = BASE_PATH . '/database/spreadsheets/USSC EDI-ERP DATA.xlsx';
$mapFile = null;
foreach ($argvList as $a) {
    if (str_starts_with($a, '--file=')) $file    = substr($a, 7);
    if (str_starts_with($a, '--map='))  $mapFile = substr($a, 6);
}

// ---------------------------------------------------------------------------
// Bootstrap
// ---------------------------------------------------------------------------

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

$pdo = new PDO(
    sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4',
        trim($_ENV['DB_HOST'] ?? 'localhost'),
        trim($_ENV['DB_DATABASE'])
    ),
    trim($_ENV['DB_USERNAME']),
    trim($_ENV['DB_PASSWORD']),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

function say(string $m = ''): void { echo $m . PHP_EOL; }
function rule(): void { say(str_repeat('-', 74)); }

/** Normalise text so trivial punctuation differences don't block a match. */
function norm(?string $s): string {
    $s = strtolower(trim((string)$s));
    $s = preg_replace('/[^a-z0-9]+/', ' ', $s) ?? '';
    return trim($s);
}

say();
rule();
say('  SKU RENAME' . ($commit ? '  [COMMIT]' : '  [DRY RUN — nothing will be written]'));
rule();

// ---------------------------------------------------------------------------
// Tables that reference products, for the impact report
// ---------------------------------------------------------------------------

$REFS = [
    'quote_line_items'          => 'product_id',
    'sales_order_line_items'    => 'product_id',
    'invoice_line_items'        => 'product_id',
    'bill_line_items'           => 'product_id',
    'purchase_order_line_items' => 'product_id',
    'inventory_transactions'    => 'product_id',
    'product_components'        => 'component_id',
    'product_categories'        => 'product_id',
    'product_images'            => 'product_id',
    'product_documents'         => 'product_id',
];

function refCounts(PDO $pdo, int $productId, array $refs): array {
    $out = [];
    foreach ($refs as $table => $col) {
        try {
            $st = $pdo->prepare("SELECT COUNT(*) c FROM `$table` WHERE `$col` = ?");
            $st->execute([$productId]);
            $n = (int)$st->fetch()['c'];
            if ($n > 0) $out[$table] = $n;
        } catch (PDOException) { /* table may not exist */ }
    }
    return $out;
}

// ---------------------------------------------------------------------------
// Build the rename list — either from an explicit map or by matching
// ---------------------------------------------------------------------------

/** @var array<int, array{old:string,new:string,why:string}> */
$renames  = [];
$ambiguous = [];
$noMatch   = [];
$newSkus   = [];

if ($mapFile !== null) {
    if (!is_file($mapFile)) {
        fwrite(STDERR, "ERROR: map file not found: $mapFile\n");
        exit(1);
    }
    say('  Source: explicit map — ' . basename($mapFile));
    say();

    $fh = fopen($mapFile, 'r');
    $header = fgetcsv($fh);   // old_sku,new_sku[,confidence]
    while (($row = fgetcsv($fh)) !== false) {
        $old = trim((string)($row[0] ?? ''));
        $new = trim((string)($row[1] ?? ''));
        if ($old === '' || $new === '' || str_contains($new, '|')) continue;
        $renames[] = ['old' => $old, 'new' => $new, 'why' => 'from map'];
    }
    fclose($fh);

} else {
    if (!is_file($file)) {
        fwrite(STDERR, "ERROR: spreadsheet not found:\n  $file\n\nPass --file=/full/path.xlsx\n");
        exit(1);
    }
    say('  Comparing against: ' . basename($file));
    say();

    // ---- read the workbook: SKU => [name, colour]
    $reader = IOFactory::createReader(pathinfo($file, PATHINFO_EXTENSION) === 'xlsx' ? 'Xlsx' : 'Xls');
    $reader->setReadDataOnly(true);
    $book = $reader->load($file);

    $wb = [];
    foreach ($book->getSheetNames() as $sheetName) {
        $data = $book->getSheetByName($sheetName)->toArray(null, true, false, false);
        if (empty($data)) continue;

        // header row = first of the top 6 containing a cell that reads "SKU"
        $hdr = null;
        foreach (array_slice($data, 0, 6, true) as $i => $r) {
            foreach ($r as $cell) {
                if (norm(is_scalar($cell) ? (string)$cell : '') === 'sku') { $hdr = $i; break 2; }
            }
        }
        if ($hdr === null) continue;

        $cols = [];
        foreach ($data[$hdr] as $ci => $cell) {
            $key = norm(is_scalar($cell) ? (string)$cell : '');
            if (in_array($key, ['sku', 'product name', 'color'], true)) $cols[$key] = $ci;
        }
        if (!isset($cols['sku'])) continue;

        foreach (array_slice($data, $hdr + 1, null, true) as $r) {
            $sku = trim((string)($r[$cols['sku']] ?? ''));
            if ($sku === '' || strtolower($sku) === 'nan') continue;
            $key = strtoupper($sku);
            if (isset($wb[$key])) continue;
            $wb[$key] = [
                'sku'   => $sku,
                'name'  => norm((string)($r[$cols['product name'] ?? -1] ?? '')),
                'color' => norm((string)($r[$cols['color']        ?? -1] ?? '')),
            ];
        }
    }
    say(sprintf('  workbook SKUs : %d', count($wb)));

    // ---- read the database
    $db = [];
    foreach ($pdo->query("SELECT id, sku, name, color FROM products WHERE item_type != 'raw_material'") as $r) {
        $db[strtoupper(trim((string)$r['sku']))] = [
            'id'    => (int)$r['id'],
            'sku'   => $r['sku'],
            'name'  => norm($r['name']),
            'color' => norm($r['color']),
        ];
    }
    say(sprintf('  database SKUs : %d', count($db)));
    say();

    $onlyWb = array_diff_key($wb, $db);
    $onlyDb = array_diff_key($db, $wb);

    // index workbook-only rows by name+colour
    $byKey = [];
    foreach ($onlyWb as $up => $w) {
        $byKey[$w['name'] . '|' . $w['color']][] = $up;
    }

    foreach ($onlyDb as $up => $d) {
        $cands = $byKey[$d['name'] . '|' . $d['color']] ?? [];
        if (count($cands) === 1) {
            $renames[] = [
                'old' => $d['sku'],
                'new' => $wb[$cands[0]]['sku'],
                'why' => 'name + colour match',
                'id'  => $d['id'],
            ];
        } elseif (count($cands) > 1) {
            $ambiguous[] = ['old' => $d['sku'], 'cands' => array_map(fn($c) => $wb[$c]['sku'], $cands), 'id' => $d['id']];
        } else {
            $noMatch[] = ['old' => $d['sku'], 'id' => $d['id']];
        }
    }

    $claimed = array_map(fn($r) => strtoupper($r['new']), $renames);
    foreach ($onlyWb as $up => $w) {
        if (!in_array($up, $claimed, true)) $newSkus[] = $w['sku'];
    }
}

// ---------------------------------------------------------------------------
// Safety checks — never rename onto an existing SKU
// ---------------------------------------------------------------------------

$blocked = [];
foreach ($renames as $i => $r) {
    $st = $pdo->prepare("SELECT id, sku FROM products WHERE UPPER(sku) = ? LIMIT 1");
    $st->execute([strtoupper($r['new'])]);
    if ($hit = $st->fetch()) {
        $blocked[] = $r + ['collides_with_id' => (int)$hit['id']];
        unset($renames[$i]);
    }
}
$renames = array_values($renames);

// ---------------------------------------------------------------------------
// Plan
// ---------------------------------------------------------------------------

rule();
say('  PLAN');
rule();
say(sprintf('  → RENAME (safe, history preserved) : %d', count($renames)));
say(sprintf('  → Ambiguous, needs a decision      : %d', count($ambiguous)));
say(sprintf('  → No workbook match (discontinued?): %d', count($noMatch)));
say(sprintf('  → New SKUs the importer will add   : %d', count($newSkus)));
if ($blocked) {
    say(sprintf('  → BLOCKED — target SKU already used: %d', count($blocked)));
}
say();

if ($renames) {
    say('  RENAMES');
    foreach ($renames as $r) {
        $refs = isset($r['id']) ? refCounts($pdo, (int)$r['id'], $REFS) : [];
        $note = $refs
            ? '  [' . implode(', ', array_map(fn($t, $n) => "$t:$n", array_keys($refs), $refs)) . ']'
            : '';
        say(sprintf('    %-24s →  %-20s%s', $r['old'], $r['new'], $note));
    }
    say();
    say('    Bracketed counts are existing references that this rename preserves.');
    say();
}

if ($ambiguous) {
    say('  AMBIGUOUS — several workbook SKUs share this name and colour.');
    say('  Nothing will be changed for these. Resolve by hand, then re-run with --map=');
    foreach ($ambiguous as $a) {
        say(sprintf('    %-24s →  %s', $a['old'], implode('  or  ', $a['cands'])));
    }
    say();
}

if ($noMatch) {
    say('  NO MATCH IN WORKBOOK — left untouched. Discontinued, or renamed in a way');
    say('  name + colour cannot detect. Deactivate rather than delete anything with history.');
    foreach ($noMatch as $n) {
        $refs = refCounts($pdo, (int)$n['id'], $REFS);
        $used = $refs ? 'IN USE [' . implode(', ', array_map(fn($t,$c)=>"$t:$c", array_keys($refs), $refs)) . ']' : 'unused';
        say(sprintf('    %-24s %s', $n['old'], $used));
    }
    say();
}

if ($blocked) {
    say('  BLOCKED — the new SKU already exists on another product, so renaming would');
    say('  create a duplicate. These need resolving by hand (likely the old row is a');
    say('  typo duplicate that should be merged or deactivated).');
    foreach ($blocked as $b) {
        say(sprintf('    %-24s →  %-20s (already product #%d)', $b['old'], $b['new'], $b['collides_with_id']));
    }
    say();
}

if (!$commit) {
    say('  DRY RUN — nothing was written. Re-run with --commit to apply the renames.');
    say('  Then run:  php database/import_products.php');
    say();
    exit(0);
}

if (empty($renames)) {
    say('  Nothing to rename.');
    say();
    exit(0);
}

// ---------------------------------------------------------------------------
// Apply
// ---------------------------------------------------------------------------

$pdo->beginTransaction();
try {
    $upd = $pdo->prepare("UPDATE products SET sku = :new WHERE id = :id");
    $byS = $pdo->prepare("UPDATE products SET sku = :new WHERE UPPER(sku) = :old");

    $n = 0;
    foreach ($renames as $r) {
        if (isset($r['id'])) {
            $upd->execute([':new' => $r['new'], ':id' => (int)$r['id']]);
        } else {
            $byS->execute([':new' => $r['new'], ':old' => strtoupper($r['old'])]);
        }
        $n += 1;
    }

    $pdo->commit();

    say();
    rule();
    say('  DONE');
    rule();
    say(sprintf('  Renamed : %d', $n));
    say();
    say('  Product ids are unchanged, so every quote, order, invoice, bill, PO,');
    say('  inventory transaction, category, image and document link is intact.');
    say();
    say('  Next:  php database/import_products.php          (dry run)');
    say('         php database/import_products.php --commit');
    say();

} catch (Throwable $e) {
    $pdo->rollBack();
    say();
    say('  FAILED — rolled back, database unchanged.');
    say('  ' . $e->getMessage());
    say();
    exit(1);
}
