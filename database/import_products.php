<?php
/**
 * Product Master Importer  —  USSC EDI-ERP DATA.xlsx
 *
 * Matches on SKU: existing products are UPDATED, new SKUs are INSERTED.
 * Nothing is ever deleted unless you explicitly ask, and even then only
 * products with zero references anywhere in the system.
 *
 * Usage (from the BusinessOS root on the server):
 *
 *   # 1. See exactly what would happen. Writes nothing.
 *   php database/import_products.php
 *
 *   # 2. Apply the inserts and updates.
 *   php database/import_products.php --commit
 *
 *   # 3. Optionally retire products that aren't in the sheet (sets is_active=0,
 *   #    fully reversible, keeps all history intact).
 *   php database/import_products.php --commit --deactivate-missing
 *
 *   # 4. Only if you really want them gone: deletes ONLY products that are not
 *   #    in the sheet AND are referenced by nothing (no quotes, orders, invoices,
 *   #    bills, POs, inventory transactions, or assemblies). Raw materials are
 *   #    always excluded.
 *   php database/import_products.php --commit --delete-unreferenced
 *
 * Options:
 *   --file=/path/to.xlsx     Defaults to database/spreadsheets/USSC EDI-ERP DATA.xlsx
 *   --sheet=NAME             Import a single sheet instead of all of them
 *
 * Safe to re-run: re-importing the same sheet simply refreshes the same rows.
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// ---------------------------------------------------------------------------
// Arguments
// ---------------------------------------------------------------------------

$argvList  = $argv ?? [];
$commit    = in_array('--commit', $argvList, true);
$deactivate= in_array('--deactivate-missing', $argvList, true);
$deleteUnref = in_array('--delete-unreferenced', $argvList, true);
$overwriteBlanks = in_array('--overwrite-blanks', $argvList, true);

$file      = BASE_PATH . '/database/spreadsheets/USSC EDI-ERP DATA.xlsx';
$onlySheet = null;
foreach ($argvList as $a) {
    if (str_starts_with($a, '--file='))  $file      = substr($a, 7);
    if (str_starts_with($a, '--sheet=')) $onlySheet = substr($a, 8);
}

if (!is_file($file)) {
    fwrite(STDERR, "ERROR: spreadsheet not found at:\n  $file\n\nPass --file=/full/path/to.xlsx\n");
    exit(1);
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
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

function say(string $msg = ''): void { echo $msg . PHP_EOL; }
function rule(): void { say(str_repeat('-', 72)); }

say();
rule();
say('  PRODUCT MASTER IMPORT' . ($commit ? '  [COMMIT]' : '  [DRY RUN — nothing will be written]'));
rule();
say('  File: ' . basename($file));
say('  Blank cells: ' . ($overwriteBlanks
    ? 'OVERWRITE existing values with NULL'
    : 'ignored — existing values are kept'));
say();

// ---------------------------------------------------------------------------
// Spreadsheet header → products column mapping
// ---------------------------------------------------------------------------

/** Normalise a header cell so both sheet layouts map with one table. */
function normHeader(?string $h): string {
    $h = strtolower(trim((string)$h));
    $h = str_replace(['(', ')', '#', '.', '/', '—', '–'], ' ', $h);
    return preg_replace('/\s+/', ' ', $h) ?? '';
}

// normalised header => products column
$MAP = [
    'sku'                      => 'sku',
    'product name'             => 'name',
    'brand'                    => '_brand',            // resolved to brand_id
    'product line'             => 'product_line',
    'color'                    => 'color',
    'purchase description'     => 'purchase_description',
    'short description'        => 'short_description',
    'active'                   => '_active',
    'item type'                => '_item_type',
    'category'                 => 'category',
    'brand category'           => 'brand_category',
    'uom'                      => 'uom_code',
    'pack level'               => 'pack_level',
    'cost'                     => 'cost',
    'retail price'             => 'price',
    'dist price qb'            => 'dist_price',
    'dist price 2026'          => 'dist_price_2026',
    'website pricing'          => 'website_price',
    'amazon'                   => 'amazon_price',
    'income account'           => 'income_account',
    'cogs account'             => 'cogs_account',
    'asset account'            => 'asset_account',
    'tax code'                 => '_tax_code',
    'tax agency'               => 'tax_agency',
    'qty on hand'              => 'qty_on_hand',
    'reorder point'            => 'reorder_point',
    'min order qty'            => 'min_order_qty',
    'lead time days'           => 'lead_time_days',
    'preferred vendor'         => 'preferred_vendor_name',
    'vendor part'              => 'vendor_part_number',
    'mpn'                      => 'mpn',
    'gtin 12 upc'              => 'gtin12',
    'gtin 14'                  => 'gtin14',
    'height'                   => 'height',
    'width'                    => 'width',
    'depth'                    => 'depth',
    'dim unit'                 => 'dim_unit',
    'gross wt'                 => 'gross_weight',
    'net wt'                   => 'net_weight',
    'wt unit'                  => 'weight_unit',
    'gs1 status'               => 'gs1_status',
    'asin'                     => 'asin',
    'amazon title'             => 'amazon_title',
    'image url'                => 'website_image_url',
    'gs1 product description'  => 'gs1_description',
    'review note'              => 'review_note',
];

$DECIMALS = ['cost','price','dist_price','dist_price_2026','website_price','amazon_price',
             'qty_on_hand','reorder_point','min_order_qty',
             'height','width','depth','gross_weight','net_weight'];
$INTS     = ['lead_time_days'];

$ITEM_TYPES = [
    'inventory part'      => 'inventory_part',
    'inventory assembly'  => 'inventory_assembly',
    'non-inventory part'  => 'non_inventory_part',
    'non inventory part'  => 'non_inventory_part',
    'service'             => 'service',
    'other charge'        => 'other_charge',
    'group'               => 'group',
    'discount'            => 'discount',
    'payment'             => 'payment',
    'sales tax item'      => 'sales_tax_item',
];

// ---------------------------------------------------------------------------
// Read every sheet into rows keyed by SKU (later sheets fill gaps only)
// ---------------------------------------------------------------------------

$book   = IOFactory::createReader(pathinfo($file, PATHINFO_EXTENSION) === 'xlsx' ? 'Xlsx' : 'Xls');
$book->setReadDataOnly(true);
$ss     = $book->load($file);

$rows        = [];   // sku => [col => value]
$sheetCounts = [];

foreach ($ss->getSheetNames() as $sheetName) {
    if ($onlySheet !== null && $sheetName !== $onlySheet) continue;

    $sheet = $ss->getSheetByName($sheetName);
    $data  = $sheet->toArray(null, true, false, false);
    if (empty($data)) continue;

    // Find the header row: the first row within the top 5 that contains "SKU"
    $headerIdx = null;
    foreach (array_slice($data, 0, 5, true) as $i => $r) {
        foreach ($r as $cell) {
            if (normHeader(is_scalar($cell) ? (string)$cell : '') === 'sku') { $headerIdx = $i; break 2; }
        }
    }
    if ($headerIdx === null) { say("  skipped '$sheetName' (no SKU header found)"); continue; }

    // Column index => products column
    $cols = [];
    foreach ($data[$headerIdx] as $ci => $cell) {
        $key = normHeader(is_scalar($cell) ? (string)$cell : '');
        if ($key !== '' && isset($MAP[$key])) $cols[$ci] = $MAP[$key];
    }

    $count = 0;
    foreach (array_slice($data, $headerIdx + 1, null, true) as $r) {
        $rec = [];
        foreach ($cols as $ci => $col) {
            $v = $r[$ci] ?? null;
            if (is_string($v)) $v = trim($v);
            if ($v === '' ) $v = null;
            $rec[$col] = $v;
        }
        $sku = isset($rec['sku']) ? trim((string)$rec['sku']) : '';
        if ($sku === '' || strtolower($sku) === 'nan') continue;
        if (empty($rec['name'])) continue;              // skip structural / blank rows

        $rec['sku'] = $sku;
        if (!isset($rows[$sku])) {
            $rows[$sku] = $rec;
        } else {
            // Later sheets only fill in blanks — EDI Master stays authoritative
            foreach ($rec as $k => $v) {
                if ($v !== null && ($rows[$sku][$k] ?? null) === null) $rows[$sku][$k] = $v;
            }
        }
        $count++;
    }
    $sheetCounts[$sheetName] = $count;
}

foreach ($sheetCounts as $n => $c) {
    say(sprintf('  %-28s %5d rows with a SKU', $n, $c));
}
say(sprintf('  %-28s %5d unique SKUs', 'TOTAL', count($rows)));
say();

if (empty($rows)) { say('Nothing to import.'); exit(0); }

// ---------------------------------------------------------------------------
// Existing products + brands
// ---------------------------------------------------------------------------

say('  reading existing products...');
$existing = [];   // upper(sku) => id
foreach ($pdo->query("SELECT id, sku, item_type FROM products") as $r) {
    if (($r['item_type'] ?? '') === 'raw_material') continue;
    $existing[strtoupper(trim((string)$r['sku']))] = (int)$r['id'];
}
say(sprintf('    %d products loaded', count($existing)));

$rawCount = (int)$pdo->query(
    "SELECT COUNT(*) c FROM products WHERE item_type = 'raw_material'"
)->fetch()['c'];

say('  reading brands...');
$brands     = [];   // upper(name) => id
$brandSlugs = [];   // slug => true   (slug is NOT NULL UNIQUE)
foreach ($pdo->query("SELECT id, name, slug FROM product_brands") as $r) {
    $brands[strtoupper(trim((string)$r['name']))] = (int)$r['id'];
    if (!empty($r['slug'])) $brandSlugs[strtolower((string)$r['slug'])] = true;
}
say(sprintf('    %d brands loaded', count($brands)));

say('  comparing against the sheet...');
// NOTE: PHP turns numeric-string array keys into ints, so every SKU read back
// out of a key must be cast before it touches a string function.
$toInsert = $toUpdate = [];
foreach ($rows as $sku => $rec) {
    $sku = (string)$sku;
    if (isset($existing[strtoupper($sku)])) $toUpdate[$sku] = $rec;
    else                                    $toInsert[$sku] = $rec;
}

// Hash lookup rather than in_array over every SKU — the product table is large
$sheetSkuSet = [];
foreach (array_keys($rows) as $s) { $sheetSkuSet[strtoupper((string)$s)] = true; }

$missingIds = [];
foreach ($existing as $sku => $id) {
    if (!isset($sheetSkuSet[$sku])) $missingIds[$sku] = $id;
}
say(sprintf('    %d in DB but not in the sheet', count($missingIds)));

// ---------------------------------------------------------------------------
// Reference check — what is actually safe to remove
// ---------------------------------------------------------------------------

$REF_TABLES = [
    'quote_line_items'       => 'quotes',
    'sales_order_line_items' => 'sales orders',
    'invoice_line_items'     => 'invoices',
    'bill_line_items'        => 'bills',
    'inventory_transactions' => 'inventory transactions',
    'product_components'     => 'assemblies',
];

/** Which of these product ids are referenced anywhere? */
function referencedIds(PDO $pdo, array $ids, array $refTables): array {
    if (empty($ids)) return [];

    $targets = [];
    foreach ($refTables as $table => $_label) {
        $targets[$table] = $table === 'product_components' ? 'component_id' : 'product_id';
    }
    // purchase order lines live under a couple of possible names
    $targets['purchase_order_line_items'] = 'product_id';
    $targets['po_line_items']             = 'product_id';

    $hits = [];
    foreach ($targets as $table => $col) {
        foreach (array_chunk($ids, 500) as $chunk) {
            $in = implode(',', array_map('intval', $chunk));
            try {
                $q = $pdo->query("SELECT DISTINCT $col AS pid FROM $table WHERE $col IN ($in)");
                foreach ($q as $r) { if ($r['pid'] !== null) $hits[(int)$r['pid']] = true; }
            } catch (PDOException) {
                continue 2;   // table doesn't exist — skip it entirely
            }
        }
    }
    return array_keys($hits);
}

say('  checking which of those are referenced by history...');
$missingReferenced   = referencedIds($pdo, array_values($missingIds), $REF_TABLES);
$missingUnreferenced = array_values(array_diff(array_values($missingIds), $missingReferenced));

// ---------------------------------------------------------------------------
// Plan
// ---------------------------------------------------------------------------

rule();
say('  PLAN');
rule();
say(sprintf('  Products in DB (excl. raw materials) : %d', count($existing)));
say(sprintf('  Raw materials (never touched)        : %d', $rawCount));
say();
say(sprintf('  → UPDATE existing SKUs               : %d', count($toUpdate)));
say(sprintf('  → INSERT new SKUs                    : %d', count($toInsert)));
say(sprintf('  → In DB but NOT in the sheet         : %d', count($missingIds)));
say(sprintf('        of those, referenced by history: %d  (cannot be deleted)', count($missingReferenced)));
say(sprintf('        of those, unreferenced         : %d  (safe to delete)', count($missingUnreferenced)));
say();

if (count($missingIds) && !$deactivate && !$deleteUnref) {
    say('  Those ' . count($missingIds) . ' products will be LEFT ALONE.');
    say('  Add --deactivate-missing to retire them (is_active=0, reversible),');
    say('  or --delete-unreferenced to remove only the unreferenced ones.');
    say();
}

// In a dry run, show exactly which products the sheet doesn't cover so the
// decision about them can be made on evidence rather than a count.
if (!$commit && !empty($missingIds)) {
    rule();
    say('  IN THE DATABASE BUT NOT IN THE SHEET');
    rule();
    $refSet = array_flip($missingReferenced);
    $in     = implode(',', array_map('intval', array_values($missingIds)));
    $q      = $pdo->query(
        "SELECT id, sku, name, is_active FROM products WHERE id IN ($in) ORDER BY sku"
    );
    foreach ($q as $r) {
        say(sprintf(
            '  %-22s %-42s %s%s',
            substr((string)$r['sku'], 0, 22),
            substr((string)$r['name'], 0, 42),
            isset($refSet[(int)$r['id']]) ? 'IN USE' : 'unused',
            ((int)$r['is_active'] === 0 ? ' (already inactive)' : '')
        ));
    }
    say();
}

if (!$commit) {
    say('  DRY RUN — nothing was written. Re-run with --commit to apply.');
    say();
    exit(0);
}

// ---------------------------------------------------------------------------
// Apply
// ---------------------------------------------------------------------------

/** Turn a spreadsheet value into a DB-ready value. */
function coerce(string $col, $v, array $decimals, array $ints) {
    if ($v === null) return null;
    if (in_array($col, $decimals, true)) {
        if (is_string($v)) $v = preg_replace('/[^0-9.\-]/', '', $v);
        return ($v === '' || $v === null) ? null : (float)$v;
    }
    if (in_array($col, $ints, true)) {
        if (is_string($v)) $v = preg_replace('/[^0-9\-]/', '', $v);
        return ($v === '' || $v === null) ? null : (int)$v;
    }
    // Barcodes and similar arrive from Excel as floats — never let them go scientific
    if (is_float($v) || (is_numeric($v) && !is_string($v))) {
        return rtrim(rtrim(sprintf('%.4F', (float)$v), '0'), '.');
    }
    return is_string($v) ? $v : (string)$v;
}

$DB_COLS = [
    'sku','name','product_line','color','purchase_description','short_description',
    'category','brand_category','uom_code','pack_level',
    'cost','price','retail_price','dist_price','dist_price_2026','website_price','amazon_price',
    'income_account','cogs_account','asset_account','tax_agency','sales_tax_code',
    'qty_on_hand','reorder_point','min_order_qty','lead_time_days',
    'preferred_vendor_name','vendor_part_number','mpn','gtin12','gtin14',
    'height','width','depth','dim_unit','gross_weight','net_weight','weight_unit','gs1_status',
    'asin','amazon_title','website_image_url','gs1_description','review_note',
    'brand_id','item_type','is_active','is_taxable',
];

$inserted = $updated = $brandsMade = 0;
$errors   = [];

$pdo->beginTransaction();

try {
    foreach ($rows as $sku => $rec) {
        $sku  = (string)$sku;   // array keys may have been cast to int
        $vals = [];

        foreach ($rec as $col => $v) {
            if (str_starts_with($col, '_')) continue;
            if (!in_array($col, $DB_COLS, true)) continue;
            $val = coerce($col, $v, $DECIMALS, $INTS);
            // A blank cell means "the sheet has nothing to say about this field",
            // not "erase what's already there". Use --overwrite-blanks to make
            // the spreadsheet fully authoritative instead.
            if ($val === null && !$GLOBALS['overwriteBlanks']) continue;
            $vals[$col] = $val;
        }

        // GTIN-14 keeps its leading zeros
        if (!empty($vals['gtin14'])) {
            $vals['gtin14'] = str_pad(preg_replace('/\D/', '', (string)$vals['gtin14']), 14, '0', STR_PAD_LEFT);
        }
        if (!empty($vals['gtin12'])) {
            $vals['gtin12'] = preg_replace('/\D/', '', (string)$vals['gtin12']);
        }

        // retail_price mirrors price
        if (array_key_exists('price', $vals)) $vals['retail_price'] = $vals['price'];

        // Brand → brand_id (created if new)
        $brandName = isset($rec['_brand']) ? trim((string)$rec['_brand']) : '';
        if ($brandName !== '') {
            $key = strtoupper($brandName);
            if (!isset($brands[$key])) {
                // slug is NOT NULL UNIQUE — derive one and de-duplicate it
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $brandName) ?? '', '-'));
                if ($slug === '') $slug = 'brand';
                $slug = substr($slug, 0, 90);
                $baseSlug = $slug;
                $n = 2;
                while (isset($brandSlugs[$slug])) { $slug = $baseSlug . '-' . $n++; }
                $brandSlugs[$slug] = true;

                $ins = $pdo->prepare("INSERT INTO product_brands (name, slug) VALUES (?, ?)");
                $ins->execute([$brandName, $slug]);
                $brands[$key] = (int)$pdo->lastInsertId();
                $brandsMade++;
                say("  + new brand: $brandName  ($slug)");
            }
            $vals['brand_id'] = $brands[$key];
        }

        // Active / Item type / Tax
        if (isset($rec['_active'])) {
            $vals['is_active'] = strtolower(trim((string)$rec['_active'])) === 'no' ? 0 : 1;
        }
        if (isset($rec['_item_type'])) {
            $t = strtolower(trim((string)$rec['_item_type']));
            if (isset($ITEM_TYPES[$t])) $vals['item_type'] = $ITEM_TYPES[$t];
        }
        if (isset($rec['_tax_code'])) {
            $tc = strtoupper(trim((string)$rec['_tax_code']));
            $vals['sales_tax_code'] = $tc ?: null;
            $vals['is_taxable']     = str_starts_with($tc, 'NON') ? 0 : 1;
        }

        // NOT NULL columns: a blank cell means "no opinion", not "set it to zero".
        // Dropping the key leaves the existing value untouched on an UPDATE and
        // lets the column default apply on an INSERT — a blank Qty On Hand in the
        // sheet must never wipe real inventory.
        foreach (['qty_on_hand', 'qty_on_sales_order', 'qty_on_po', 'track_inventory',
                  'is_taxable', 'is_active', 'is_hazmat', 'publish_to_website',
                  'item_type', 'name', 'sku'] as $nn) {
            if (array_key_exists($nn, $vals) && $vals[$nn] === null) {
                unset($vals[$nn]);
            }
        }

        $id = $existing[strtoupper($sku)] ?? null;

        if ($id) {
            unset($vals['sku']);                       // never rewrite the match key
            if (empty($vals)) continue;
            $set = implode(', ', array_map(fn($c) => "$c = :$c", array_keys($vals)));
            $st  = $pdo->prepare("UPDATE products SET $set WHERE id = :__id");
            $vals['__id'] = $id;
            $st->execute($vals);
            $updated++;
        } else {
            // quickbooks_item is NOT NULL UNIQUE — build BRAND:SKU like the QB export
            $qbItem = ($brandName !== '' ? strtoupper($brandName) . ':' : '') . $sku;
            $probe  = $pdo->prepare("SELECT id FROM products WHERE quickbooks_item = ? LIMIT 1");
            $probe->execute([$qbItem]);
            if ($probe->fetch()) $qbItem .= '-' . substr(md5($sku), 0, 6);

            $vals['quickbooks_item'] = $qbItem;
            $vals['sku']             = $sku;
            $vals['item_type']       = $vals['item_type'] ?? 'inventory_part';
            $vals['is_active']       = $vals['is_active'] ?? 1;
            $vals['track_inventory'] = in_array($vals['item_type'], ['inventory_part','inventory_assembly'], true) ? 1 : 0;

            $cols = array_keys($vals);
            $st   = $pdo->prepare(
                'INSERT INTO products (' . implode(', ', $cols) . ') VALUES (:' . implode(', :', $cols) . ')'
            );
            $st->execute($vals);
            $existing[strtoupper($sku)] = (int)$pdo->lastInsertId();
            $inserted++;
        }
    }

    // Retire products absent from the sheet
    $deactivated = 0;
    if ($deactivate && !empty($missingIds)) {
        $in = implode(',', array_map('intval', array_values($missingIds)));
        $deactivated = $pdo->exec("UPDATE products SET is_active = 0 WHERE id IN ($in)");
    }

    // Delete only what nothing points at
    $deleted = 0;
    if ($deleteUnref && !empty($missingUnreferenced)) {
        $in = implode(',', array_map('intval', $missingUnreferenced));
        $pdo->exec("DELETE FROM product_images    WHERE product_id IN ($in)");
        $pdo->exec("DELETE FROM product_documents WHERE product_id IN ($in)");
        $deleted = $pdo->exec("DELETE FROM products WHERE id IN ($in)");
    }

    $pdo->commit();

    say();
    rule();
    say('  DONE');
    rule();
    say(sprintf('  Inserted     : %d', $inserted));
    say(sprintf('  Updated      : %d', $updated));
    say(sprintf('  Brands added : %d', $brandsMade));
    if ($deactivate) say(sprintf('  Deactivated  : %d', $deactivated));
    if ($deleteUnref) say(sprintf('  Deleted      : %d', $deleted));
    say();

} catch (Throwable $e) {
    $pdo->rollBack();
    say();
    say('  IMPORT FAILED — everything was rolled back, the database is unchanged.');
    say('  ' . $e->getMessage());
    say();
    exit(1);
}
