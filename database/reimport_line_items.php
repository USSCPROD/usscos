<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

define('BASE_PATH', dirname(__DIR__));

$env = [];
foreach (file(BASE_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v);
}
$dsn = "mysql:host={$env['DB_HOST']};dbname={$env['DB_DATABASE']};charset=utf8mb4";
$pdo = new PDO($dsn, trim($env['DB_USERNAME']), trim($env['DB_PASSWORD']), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// Load sales detail CSV (file 4)
$csvFile = BASE_PATH . '/database/spreadsheets/invoices_detail.csv';
if (!file_exists($csvFile)) {
    die("File not found: $csvFile\n  Expected the QB Sales by Item Detail export converted to CSV.\n");
}

// Build caches
$invCache = [];
foreach ($pdo->query('SELECT id, invoice_number FROM invoices') as $r) {
    $invCache[$r['invoice_number']] = (int)$r['id'];
}
echo "Loaded " . count($invCache) . " invoices.\n";

$productMap = [];
foreach ($pdo->query('SELECT id, quickbooks_item FROM products') as $r) {
    $productMap[$r['quickbooks_item']] = (int)$r['id'];
}
echo "Loaded " . count($productMap) . " products.\n";

$uomCache = [];
foreach ($pdo->query('SELECT id, code FROM units_of_measure') as $r) {
    $uomCache[strtoupper(trim($r['code']))] = (int)$r['id'];
}

function uomId(PDO $pdo, ?string $code, array &$cache): ?int {
    if (!$code) return null;
    $key = strtoupper(trim(substr($code, 0, 20)));
    if (!$key) return null;
    if (!isset($cache[$key])) {
        $s = $pdo->prepare('INSERT IGNORE INTO units_of_measure (code, name) VALUES (:c,:n)');
        $s->execute([':c' => $key, ':n' => $code]);
        $cache[$key] = (int)$pdo->lastInsertId() ?: null;
        if (!$cache[$key]) {
            $r = $pdo->query("SELECT id FROM units_of_measure WHERE code = " . $pdo->quote($key))->fetch();
            $cache[$key] = $r ? (int)$r['id'] : null;
        }
    }
    return $cache[$key];
}

function clean(?string $v): ?string {
    if ($v === null) return null;
    $v = trim($v);
    return ($v === '' || $v === 'N/A') ? null : $v;
}
function qty(?string $v): ?float {
    if ($v === null || trim($v) === '') return null;
    return (float)str_replace([',', ' '], '', $v);
}
function money(?string $v): ?float {
    if ($v === null || trim($v) === '') return null;
    return (float)str_replace([',', '$', ' '], '', $v);
}

// Clear existing line items
$pdo->exec('DELETE FROM invoice_line_items');
echo "Cleared existing line items.\n";

$lineStmt = $pdo->prepare("
    INSERT INTO invoice_line_items
        (invoice_id, product_id, quickbooks_item, description, qty, uom_id, unit_price, line_total, sort_order)
    VALUES
        (:inv_id, :prod_id, :qb_item, :desc, :qty, :uom_id, :price, :total, :sort)
");

$handle = fopen($csvFile, 'r');
fgetcsv($handle); // skip header

$inserted = 0;
$skipped  = 0;
$sortByInv = [];

while (($row = fgetcsv($handle)) !== false) {
    $type = clean($row[0] ?? null);
    if ($type !== 'Invoice') { $skipped++; continue; }

    $num   = clean((string)(int)($row[2] ?? 0));
    $invId = $invCache[$num] ?? null;
    if (!$invId) { $skipped++; continue; }

    $qbItem = clean($row[5] ?? null);
    if ($qbItem && str_contains($qbItem, ' (')) {
        $qbItem = trim(explode(' (', $qbItem)[0]);
    }

    $prodId  = $qbItem ? ($productMap[$qbItem] ?? null) : null;
    $desc    = clean($row[3] ?? null) ?: $qbItem;
    $qty     = qty($row[6] ?? null) ?? 1;
    $uomCode = clean($row[7] ?? null);
    $uomId   = uomId($pdo, $uomCode, $uomCache);
    $price   = money($row[8] ?? null) ?? 0;
    $total   = money($row[9] ?? null) ?? (float)$qty * (float)$price;

    $sortByInv[$invId] = ($sortByInv[$invId] ?? 0) + 1;

    $lineStmt->execute([
        ':inv_id'  => $invId,
        ':prod_id' => $prodId,
        ':qb_item' => $qbItem,
        ':desc'    => $desc,
        ':qty'     => $qty,
        ':uom_id'  => $uomId,
        ':price'   => $price,
        ':total'   => $total,
        ':sort'    => $sortByInv[$invId],
    ]);
    $inserted++;

    if ($inserted % 500 === 0) {
        echo "  Inserted $inserted line items...\n";
    }
}

fclose($handle);
echo "Done. Inserted: $inserted line items | Skipped: $skipped rows\n";
