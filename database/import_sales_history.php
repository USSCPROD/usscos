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

$csvFile = BASE_PATH . '/database/spreadsheets/customer_detail.csv';
if (!file_exists($csvFile)) die("Not found: $csvFile\n");

// ── Caches ───────────────────────────────────────────────────────────────────
$customerMap = []; // quickbooks_name → id
foreach ($pdo->query('SELECT id, quickbooks_name FROM customers') as $r) {
    $customerMap[$r['quickbooks_name']] = (int)$r['id'];
}
echo "Customers: " . count($customerMap) . "\n";

$productMap = []; // quickbooks_item → id
foreach ($pdo->query('SELECT id, quickbooks_item FROM products') as $r) {
    $productMap[$r['quickbooks_item']] = (int)$r['id'];
}
echo "Products: " . count($productMap) . "\n";

$uomCache = [];
foreach ($pdo->query('SELECT id, code FROM units_of_measure') as $r) {
    $uomCache[strtoupper(trim($r['code']))] = (int)$r['id'];
}

$existingInvoices = []; // invoice_number → id
foreach ($pdo->query('SELECT id, invoice_number FROM invoices') as $r) {
    $existingInvoices[$r['invoice_number']] = (int)$r['id'];
}
echo "Existing invoices: " . count($existingInvoices) . "\n";

// ── Helpers ──────────────────────────────────────────────────────────────────
function clean(?string $v): ?string {
    if ($v === null) return null;
    $v = trim($v);
    return ($v === '' || $v === 'N/A') ? null : $v;
}
function money(?string $v): float {
    return (float)str_replace([',','$',' '], '', (string)$v);
}
function uomId(PDO $pdo, ?string $code, array &$cache): ?int {
    if (!$code) return null;
    $key = strtoupper(trim(substr($code, 0, 20)));
    if (!$key) return null;
    if (!isset($cache[$key])) {
        $s = $pdo->prepare('INSERT IGNORE INTO units_of_measure (code, label) VALUES (:c,:n)');
        $s->execute([':c' => $key, ':n' => substr($code, 0, 50)]);
        $id = (int)$pdo->lastInsertId();
        if (!$id) {
            $r = $pdo->query("SELECT id FROM units_of_measure WHERE code=" . $pdo->quote($key))->fetch();
            $id = $r ? (int)$r['id'] : null;
        }
        $cache[$key] = $id;
    }
    return $cache[$key];
}

// ── Pass 1: Read CSV, build invoice totals & line item rows ──────────────────
echo "Reading CSV...\n";
$handle = fopen($csvFile, 'r');
fgetcsv($handle); // header

$invoiceMeta  = []; // num → [customer_name, date, total]
$lineItemRows  = []; // array of line data

while (($row = fgetcsv($handle)) !== false) {
    if (count($row) < 10) continue;
    [$type, $date, $num, $memo, $name, $item, $qty, $uom, $price, $amount] = $row;

    $type = trim($type);
    $num  = trim((string)(int)$num);
    if (!$num || $type !== 'Invoice') continue;

    $amount_f = money($amount);

    if (!isset($invoiceMeta[$num])) {
        $invoiceMeta[$num] = ['name' => trim($name), 'date' => trim($date), 'total' => 0.0];
    }
    $invoiceMeta[$num]['total'] += $amount_f;

    $lineItemRows[] = [$num, trim($item), trim($memo), trim($qty), trim($uom), trim($price), $amount];
}
fclose($handle);
echo "Invoice numbers: " . count($invoiceMeta) . " | Line rows: " . count($lineItemRows) . "\n";

// ── Pass 2: Insert missing invoices ──────────────────────────────────────────
echo "Inserting new invoices...\n";
$invStmt = $pdo->prepare("
    INSERT IGNORE INTO invoices
        (invoice_number, customer_id, invoice_date, due_date, status, total_amount, balance_due)
    VALUES
        (:num, :cust_id, :date, :due, 'paid', :total, 0)
");

$invInserted = 0;
$invSkipped  = 0;
foreach ($invoiceMeta as $num => $meta) {
    if (isset($existingInvoices[$num])) { $invSkipped++; continue; }

    $custId = $customerMap[$meta['name']] ?? null;
    if (!$custId) {
        // Try parent name (QB sub-customer format: PARENT:CHILD)
        if (str_contains($meta['name'], ':')) {
            $parent = explode(':', $meta['name'], 2)[0];
            $custId = $customerMap[$parent] ?? null;
        }
    }
    if (!$custId) { $invSkipped++; continue; }

    $date = substr($meta['date'], 0, 10);
    $due  = date('Y-m-d', strtotime($date . ' +30 days'));

    $invStmt->execute([
        ':num'     => $num,
        ':cust_id' => $custId,
        ':date'    => $date,
        ':due'     => $due,
        ':total'   => $meta['total'],
    ]);

    $newId = (int)$pdo->lastInsertId();
    if ($newId) {
        $existingInvoices[$num] = $newId;
        $invInserted++;
    }

    if ($invInserted % 1000 === 0 && $invInserted > 0) {
        echo "  Invoices inserted: $invInserted\n";
    }
}
echo "Invoices inserted: $invInserted | Skipped (existing/no customer): $invSkipped\n";

// ── Pass 3: Insert line items ─────────────────────────────────────────────────
echo "Clearing existing line items...\n";
$pdo->exec('DELETE FROM invoice_line_items');

echo "Inserting line items...\n";
$liStmt = $pdo->prepare("
    INSERT INTO invoice_line_items
        (invoice_id, product_id, quickbooks_item, description, qty, uom_id, unit_price, line_total, sort_order)
    VALUES
        (:inv_id, :prod_id, :item, :desc, :qty, :uom_id, :price, :total, :sort)
");

$sortTracker = [];
$liInserted  = 0;
$liSkipped   = 0;

foreach ($lineItemRows as $r) {
    [$num, $item, $memo, $qty, $uom, $price, $amount] = $r;

    $invId = $existingInvoices[$num] ?? null;
    if (!$invId) { $liSkipped++; continue; }

    // Detect service lines by description and fix item code
    $memoLower = strtolower($memo);
    if (str_contains($memoLower, 'shipping') || str_contains($memoLower, 'handling') || str_contains($memoLower, 'freight')) {
        $item = 'SH';
    } elseif (str_contains($memoLower, 'pick-up') || str_contains($memoLower, 'pick up') || str_contains($memoLower, 'customer pick')) {
        $item = 'CPU';
    } elseif (str_contains($memoLower, 'delivery') || str_contains($memoLower, 'delivered price')) {
        $item = 'DELIVERY';
    }

    $prodId = $item ? ($productMap[$item] ?? null) : null;
    $uomId  = uomId($pdo, $uom ?: null, $uomCache);
    $sortTracker[$invId] = ($sortTracker[$invId] ?? 0) + 1;

    $liStmt->execute([
        ':inv_id'  => $invId,
        ':prod_id' => $prodId,
        ':item'    => $item    ?: null,
        ':desc'    => $memo    ?: null,
        ':qty'     => (float)str_replace(',', '', $qty) ?: 1,
        ':uom_id'  => $uomId,
        ':price'   => money($price),
        ':total'   => money($amount),
        ':sort'    => $sortTracker[$invId],
    ]);
    $liInserted++;

    if ($liInserted % 5000 === 0) {
        echo "  Line items: $liInserted\n";
    }
}

echo "Done.\n";
echo "  Line items inserted: $liInserted | Skipped (no invoice): $liSkipped\n";
