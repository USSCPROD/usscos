<?php
/**
 * QuickBooks Excel Importer
 *
 * Usage (from BusinessOS root on server):
 *   php database/import_quickbooks.php /path/to/spreadsheets/
 *
 * Run order is automatic. Script is idempotent — safe to re-run.
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlDate;

// ---------------------------------------------------------------------------
// Bootstrap
// ---------------------------------------------------------------------------

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

$spreadsheetDir = $argv[1] ?? BASE_PATH . '/database/spreadsheets';
$spreadsheetDir = rtrim($spreadsheetDir, '/');

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

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function log_msg(string $msg): void
{
    echo '[' . date('H:i:s') . '] ' . $msg . PHP_EOL;
}

function clean(mixed $v): ?string
{
    if ($v === null || $v === '' || (is_float($v) && is_nan($v))) {
        return null;
    }
    return trim((string)$v);
}

function money(mixed $v): ?string
{
    $s = clean($v);
    if ($s === null) return null;
    $s = preg_replace('/[^0-9.\-]/', '', $s);
    return is_numeric($s) ? $s : null;
}

function qty(mixed $v): ?string
{
    $s = clean($v);
    if ($s === null) return null;
    return is_numeric($s) ? $s : null;
}

function xlDate(mixed $v): ?string
{
    if ($v === null || $v === '') return null;
    if ($v instanceof \DateTimeInterface) return $v->format('Y-m-d');
    if (is_numeric($v)) {
        try {
            return XlDate::excelToDateTimeObject((float)$v)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
    if (is_string($v)) {
        $ts = strtotime($v);
        return $ts !== false ? date('Y-m-d', $ts) : null;
    }
    return null;
}

function loadSheet(string $path): array
{
    $spreadsheet = IOFactory::load($path);
    $sheet = $spreadsheet->getActiveSheet();
    return $sheet->toArray(null, true, true, false);
}

function findFile(string $dir, int $fileNum): string
{
    $patterns = [
        "$dir/Report_from_US_SPECIALTY_COATINGS $fileNum.xlsx",
        "$dir/Report_from_US_SPECIALTY_COATINGS$fileNum.xlsx",
        "$dir/Report_from_US_SPECIALTY_COATINGS {$fileNum}.xlsx",
    ];
    // File 1 (customers) has no number suffix
    if ($fileNum === 1) {
        $noNum = "$dir/Report_from_US_SPECIALTY_COATINGS.xlsx";
        if (file_exists($noNum)) return $noNum;
    }
    foreach ($patterns as $p) {
        if (file_exists($p)) return $p;
    }
    // Fallback: glob — but exclude the no-number file when looking for numbered ones
    $files = glob("$dir/*{$fileNum}*.xlsx");
    if ($files) return $files[0];
    throw new \RuntimeException("Cannot find spreadsheet #$fileNum in $dir");
}

// ---------------------------------------------------------------------------
// UOM cache helper
// ---------------------------------------------------------------------------

function uomId(PDO $pdo, ?string $code, array &$cache): ?int
{
    if (!$code) return null;
    $code = strtoupper(trim($code));
    // Truncate to column limit and strip anything that looks like a full description
    if (strlen($code) > 20) $code = substr($code, 0, 20);
    if (isset($cache[$code])) return $cache[$code];

    $stmt = $pdo->prepare('SELECT id FROM units_of_measure WHERE code = ?');
    $stmt->execute([$code]);
    $row = $stmt->fetch();
    if ($row) {
        $cache[$code] = (int)$row['id'];
        return $cache[$code];
    }
    // Auto-create unknown UOM codes
    $label = strlen($code) > 50 ? substr($code, 0, 50) : $code;
    $pdo->prepare('INSERT INTO units_of_measure (code, label) VALUES (?, ?)')->execute([$code, $label]);
    $cache[$code] = (int)$pdo->lastInsertId();
    return $cache[$code];
}

// ---------------------------------------------------------------------------
// 1. Vendors (file 5) — must come before brands and products
// ---------------------------------------------------------------------------

function importVendors(PDO $pdo, string $dir): array
{
    log_msg('Importing vendors (file 5)...');
    $rows = loadSheet(findFile($dir, 5));

    // Find header row
    $headerRow = null;
    foreach ($rows as $i => $row) {
        if (in_array('Vendor', $row, true) || in_array('vendor', array_map('strtolower', array_filter($row, 'is_string')))) {
            $headerRow = $i;
            break;
        }
    }
    if ($headerRow === null) {
        log_msg('  WARNING: Could not find vendor header row, skipping.');
        return [];
    }

    $headers = array_map(fn($h) => trim((string)($h ?? '')), $rows[$headerRow]);
    $col = array_flip($headers);

    $inserted = 0;
    $idMap = []; // QB name => db id

    // Cache existing
    $existing = $pdo->query('SELECT id, quickbooks_name FROM vendors')->fetchAll();
    foreach ($existing as $e) {
        $idMap[$e['quickbooks_name']] = (int)$e['id'];
    }

    $stmt = $pdo->prepare("
        INSERT INTO vendors (quickbooks_name, company_name, account_number, phone, fax, qb_balance)
        VALUES (:qb_name, :company, :acct, :phone, :fax, :balance)
        ON DUPLICATE KEY UPDATE
            company_name   = VALUES(company_name),
            account_number = VALUES(account_number),
            phone          = VALUES(phone),
            fax            = VALUES(fax),
            qb_balance     = VALUES(qb_balance),
            last_synced_at = CURRENT_TIMESTAMP
    ");

    for ($i = $headerRow + 1; $i < count($rows); $i++) {
        $row = $rows[$i];

        // Skip blank, total, or header-repeat rows
        $vendorName = clean($row[$col['Vendor'] ?? 0] ?? null);
        if (!$vendorName || str_starts_with($vendorName, 'Total')) continue;

        $balance = money($row[$col['Balance Total'] ?? $col['Balance'] ?? null] ?? null);

        $stmt->execute([
            ':qb_name'  => $vendorName,
            ':company'  => $vendorName,
            ':acct'     => clean($row[$col['Account No.'] ?? $col['Account No'] ?? null] ?? null),
            ':phone'    => clean($row[$col['Main Phone'] ?? null] ?? null),
            ':fax'      => clean($row[$col['Fax'] ?? null] ?? null),
            ':balance'  => $balance,
        ]);

        if (!isset($idMap[$vendorName])) {
            $idMap[$vendorName] = (int)$pdo->lastInsertId();
            $inserted++;
        }
    }

    log_msg("  Done: $inserted new vendors.");
    return $idMap;
}

// ---------------------------------------------------------------------------
// 2. Customers (file 1)
// ---------------------------------------------------------------------------

function importCustomers(PDO $pdo, string $dir): array
{
    log_msg('Importing customers (customers.csv)...');
    $csvFile = $dir . '/customers.csv';
    if (!file_exists($csvFile)) {
        log_msg('  ERROR: customers.csv not found in spreadsheet directory. Generate it from the Excel file on your Mac.');
        return [];
    }

    $idMap    = [];
    $inserted = 0;

    foreach ($pdo->query('SELECT id, quickbooks_name FROM customers') as $e) {
        $idMap[$e['quickbooks_name']] = (int)$e['id'];
    }

    $stmt = $pdo->prepare("
        INSERT INTO customers
            (quickbooks_name, company_name, phone, fax, qb_balance)
        VALUES
            (:qb_name, :company, :phone, :fax, :balance)
        ON DUPLICATE KEY UPDATE
            company_name   = VALUES(company_name),
            phone          = VALUES(phone),
            fax            = VALUES(fax),
            qb_balance     = VALUES(qb_balance),
            last_synced_at = CURRENT_TIMESTAMP
    ");

    $fh = fopen($csvFile, 'r');
    $header = fgetcsv($fh); // skip header row
    while (($row = fgetcsv($fh)) !== false) {
        $qbName = clean($row[0] ?? null);
        if (!$qbName) continue;

        $phone   = substr(clean($row[1] ?? null) ?? '', 0, 30) ?: null;
        $fax     = substr(clean($row[2] ?? null) ?? '', 0, 30) ?: null;
        $balance = money($row[3] ?? null);

        $stmt->execute([
            ':qb_name'  => $qbName,
            ':company'  => $qbName,
            ':phone'    => $phone,
            ':fax'      => $fax,
            ':balance'  => $balance,
        ]);

        if (!isset($idMap[$qbName])) {
            $idMap[$qbName] = (int)$pdo->lastInsertId();
            $inserted++;
        }
    }

    // Wire up parent:child relationships
    $parentStmt = $pdo->prepare('UPDATE customers SET parent_id = ? WHERE id = ?');
    foreach ($idMap as $name => $childId) {
        if (str_contains($name, ':')) {
            $parentName = explode(':', $name, 2)[0];
            if (isset($idMap[$parentName])) {
                $parentStmt->execute([$idMap[$parentName], $childId]);
            }
        }
    }

    log_msg("  Done: $inserted new customers.");
    return $idMap;
}

// ---------------------------------------------------------------------------
// 3. Products / Items (file 8)
// ---------------------------------------------------------------------------

function importProducts(PDO $pdo, string $dir, array $vendorMap): array
{
    log_msg('Importing products (file 8)...');
    $rows = loadSheet(findFile($dir, 8));

    $headerRow = null;
    foreach ($rows as $i => $row) {
        $flat = array_map(fn($v) => strtolower(trim((string)($v ?? ''))), $row);
        if (in_array('item', $flat) || in_array('type', $flat)) {
            $headerRow = $i;
            break;
        }
    }
    if ($headerRow === null) {
        log_msg('  WARNING: Could not find item header row, skipping.');
        return [];
    }

    $headers = array_map(fn($h) => trim((string)($h ?? '')), $rows[$headerRow]);
    $col = array_flip($headers);

    $uomCache  = [];
    $brandCache = [];
    $idMap     = [];
    $inserted  = 0;

    foreach ($pdo->query('SELECT id, quickbooks_item FROM products') as $e) {
        $idMap[$e['quickbooks_item']] = (int)$e['id'];
    }
    foreach ($pdo->query('SELECT id, name FROM product_brands') as $b) {
        $brandCache[strtoupper($b['name'])] = (int)$b['id'];
    }

    $typeMap = [
        'service'              => 'service',
        'inventory part'       => 'inventory_part',
        'inventory assembly'   => 'inventory_assembly',
        'non-inventory part'   => 'non_inventory_part',
        'non inventory part'   => 'non_inventory_part',
        'other charge'         => 'other_charge',
        'group'                => 'group',
        'discount'             => 'discount',
        'payment'              => 'payment',
        'sales tax item'       => 'sales_tax_item',
    ];

    $brandStmt = $pdo->prepare("
        INSERT INTO product_brands (name, slug) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE name = name
    ");

    $prodStmt = $pdo->prepare("
        INSERT INTO products
            (brand_id, sku, quickbooks_item, name, description, item_type,
             cost, price, uom_id, track_inventory,
             qty_on_hand, qty_on_sales_order, qty_on_po, reorder_point,
             sales_tax_code, is_taxable, preferred_vendor_id)
        VALUES
            (:brand_id, :sku, :qb_item, :name, :desc, :type,
             :cost, :price, :uom_id, :track,
             :qty_on_hand, :qty_on_so, :qty_on_po, :reorder,
             :tax_code, :taxable, :vendor_id)
        ON DUPLICATE KEY UPDATE
            name                = VALUES(name),
            description         = VALUES(description),
            item_type           = VALUES(item_type),
            cost                = VALUES(cost),
            price               = VALUES(price),
            uom_id              = VALUES(uom_id),
            track_inventory     = VALUES(track_inventory),
            qty_on_hand         = VALUES(qty_on_hand),
            qty_on_sales_order  = VALUES(qty_on_sales_order),
            qty_on_po           = VALUES(qty_on_po),
            reorder_point       = VALUES(reorder_point),
            sales_tax_code      = VALUES(sales_tax_code),
            is_taxable          = VALUES(is_taxable),
            preferred_vendor_id = VALUES(preferred_vendor_id),
            last_synced_at      = CURRENT_TIMESTAMP
    ");

    for ($i = $headerRow + 1; $i < count($rows); $i++) {
        $row = $rows[$i];

        $qbItem = clean($row[$col['Item'] ?? 0] ?? null);
        if (!$qbItem || str_starts_with($qbItem, 'Total')) continue;

        // Parse BRAND:SKU
        $brandName = null;
        $sku       = $qbItem;
        if (str_contains($qbItem, ':')) {
            [$brandName, $sku] = explode(':', $qbItem, 2);
        }

        // Brand
        $brandId = null;
        if ($brandName) {
            $brandKey = strtoupper($brandName);
            if (!isset($brandCache[$brandKey])) {
                $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $brandName));
                $brandStmt->execute([$brandName, $slug]);
                $brandCache[$brandKey] = (int)$pdo->lastInsertId();
            }
            $brandId = $brandCache[$brandKey];
        }

        $rawType  = strtolower(trim(clean($row[$col['Type'] ?? 1] ?? null) ?? ''));
        $itemType = $typeMap[$rawType] ?? 'non_inventory_part';
        $trackInv = in_array($itemType, ['inventory_part', 'inventory_assembly']) ? 1 : 0;

        $taxCode  = clean($row[$col['Sales Tax Code'] ?? null] ?? null);
        $taxable  = ($taxCode && strtolower($taxCode) !== 'non') ? 1 : 0;

        $vendorName = clean($row[$col['Preferred Vendor'] ?? null] ?? null);
        $vendorId   = $vendorName ? ($vendorMap[$vendorName] ?? null) : null;

        $uomCode = clean($row[$col['U/M'] ?? null] ?? null);
        $uomId   = uomId($pdo, $uomCode, $uomCache);

        $desc    = clean($row[$col['Description'] ?? null] ?? null);
        $display = $desc ?: $qbItem;
        if (strlen($display) > 255) $display = substr($display, 0, 255);

        $prodStmt->execute([
            ':brand_id'   => $brandId,
            ':sku'        => $sku,
            ':qb_item'    => $qbItem,
            ':name'       => $display,
            ':desc'       => $desc,
            ':type'       => $itemType,
            ':cost'       => money($row[$col['Cost'] ?? null] ?? null),
            ':price'      => money($row[$col['Price'] ?? null] ?? null),
            ':uom_id'     => $uomId,
            ':track'      => $trackInv,
            ':qty_on_hand'=> qty($row[$col['Qty On Hand'] ?? null] ?? null) ?? 0,
            ':qty_on_so'  => qty($row[$col['Qty On Sales Order'] ?? null] ?? null) ?? 0,
            ':qty_on_po'  => qty($row[$col['Qty On PO'] ?? null] ?? null) ?? 0,
            ':reorder'    => qty($row[$col['Reorder Pt'] ?? null] ?? null),
            ':tax_code'   => $taxCode,
            ':taxable'    => $taxable,
            ':vendor_id'  => $vendorId,
        ]);

        if (!isset($idMap[$qbItem])) {
            $idMap[$qbItem] = (int)$pdo->lastInsertId();
            $inserted++;
        }
    }

    log_msg("  Done: $inserted new products.");
    return $idMap;
}

// ---------------------------------------------------------------------------
// 4. Invoices (file 3 — open invoices) + line items (file 4 — sales detail)
// ---------------------------------------------------------------------------

function importInvoices(PDO $pdo, string $dir, array $customerMap, array $productMap): void
{
    log_msg('Importing invoices (invoices.csv)...');
    $csvFile = $dir . '/invoices.csv';
    if (!file_exists($csvFile)) {
        log_msg('  ERROR: invoices.csv not found. Generate it from the Excel file on your Mac.');
        importSalesDetail($pdo, $dir, $productMap);
        return;
    }

    // Resolve term IDs
    $termCache = [];
    foreach ($pdo->query('SELECT id, name FROM payment_terms') as $t) {
        $termCache[strtoupper(trim($t['name']))] = (int)$t['id'];
    }

    $inserted = 0;
    $existing = [];
    foreach ($pdo->query('SELECT invoice_number FROM invoices') as $r) {
        $existing[$r['invoice_number']] = true;
    }

    $invStmt = $pdo->prepare("
        INSERT INTO invoices
            (customer_id, invoice_number, po_number, invoice_type, status,
             invoice_date, due_date, payment_term_id, balance_due, total_amount, aging_days)
        VALUES
            (:cust_id, :num, :po, 'invoice', :status,
             :inv_date, :due_date, :term_id, :balance, :balance, :aging)
        ON DUPLICATE KEY UPDATE
            po_number       = VALUES(po_number),
            due_date        = VALUES(due_date),
            payment_term_id = VALUES(payment_term_id),
            balance_due     = VALUES(balance_due),
            total_amount    = VALUES(total_amount),
            aging_days      = VALUES(aging_days),
            last_synced_at  = CURRENT_TIMESTAMP
    ");

    // CSV columns: customer_name, type, date, num, po, terms, due_date, aging, balance
    $currentCustomer = null;
    $fh = fopen($csvFile, 'r');
    fgetcsv($fh); // skip header

    while (($row = fgetcsv($fh)) !== false) {
        $customerName = clean($row[0] ?? null);
        $typeCell     = clean($row[1] ?? null);

        // Customer grouping rows have a name but no type
        if ($customerName && !$typeCell) {
            if (!str_starts_with($customerName, 'Total')) {
                $currentCustomer = $customerName;
            }
            continue;
        }

        if (!in_array($typeCell, ['Invoice', 'Credit Memo'])) continue;

        $num = clean($row[3] ?? null);
        if (!$num || !$currentCustomer) continue;

        $invDate = xlDate($row[2] ?? null);
        $dueDate = xlDate($row[6] ?? null) ?? $invDate;
        $balance = money($row[8] ?? null);
        $aging   = is_numeric($row[7] ?? null) ? (int)$row[7] : null;
        $po      = clean($row[4] ?? null);
        $terms   = strtoupper(trim(clean($row[5] ?? null) ?? ''));
        $termId  = $termCache[$terms] ?? null;
        $custId  = $customerMap[$currentCustomer] ?? null;

        if (!$custId) {
            $s = $pdo->prepare('SELECT id FROM customers WHERE quickbooks_name = ?');
            $s->execute([$currentCustomer]);
            $custId = $s->fetchColumn() ?: null;
        }
        if (!$custId) continue;

        $status = ($aging > 0) ? 'overdue' : 'sent';
        if ($balance == 0) $status = 'paid';

        $invStmt->execute([
            ':cust_id'  => $custId,
            ':num'      => $num,
            ':po'       => $po,
            ':status'   => $status,
            ':inv_date' => $invDate,
            ':due_date' => $dueDate,
            ':term_id'  => $termId,
            ':balance'  => $balance ?? 0,
            ':aging'    => $aging,
        ]);

        if (!isset($existing[$num])) {
            $existing[$num] = true;
            $inserted++;
        }
    }
    fclose($fh);

    log_msg("  Done: $inserted new invoices.");
    importSalesDetail($pdo, $dir, $productMap);
}

function importSalesDetail(PDO $pdo, string $dir, array $productMap): void
{
    log_msg('Importing sales line items (file 4)...');
    $rows = loadSheet(findFile($dir, 4));

    $uomCache = [];
    $inserted = 0;

    // Build invoice number → id cache
    $invCache = [];
    foreach ($pdo->query('SELECT id, invoice_number FROM invoices') as $r) {
        $invCache[$r['invoice_number']] = (int)$r['id'];
    }

    // Existing line item invoice IDs (to avoid duplicating on re-run)
    $existingInvIds = [];
    foreach ($pdo->query('SELECT DISTINCT invoice_id FROM invoice_line_items') as $r) {
        $existingInvIds[(int)$r['invoice_id']] = true;
    }
    $processedInvIds = []; // track within this run separately

    $lineStmt = $pdo->prepare("
        INSERT INTO invoice_line_items
            (invoice_id, product_id, quickbooks_item, description, qty, uom_id, unit_price, line_total)
        VALUES
            (:inv_id, :prod_id, :qb_item, :desc, :qty, :uom_id, :price, :total)
    ");

    foreach ($rows as $row) {
        $type = clean($row[0] ?? null);
        if ($type !== 'Invoice') continue;

        $num     = clean((string)(int)($row[2] ?? 0));
        $invId   = $invCache[$num] ?? null;
        if (!$invId || isset($existingInvIds[$invId])) continue;

        $qbItem  = clean($row[5] ?? null);
        // QB item field may include description in parens — strip it
        if ($qbItem && str_contains($qbItem, ' (')) {
            $qbItem = trim(explode(' (', $qbItem)[0]);
        }

        $prodId  = $qbItem ? ($productMap[$qbItem] ?? null) : null;
        $desc    = clean($row[4] ?? null) ?: $qbItem;
        $qty     = qty($row[6] ?? null) ?? 1;
        $uomCode = clean($row[7] ?? null);
        $uomId   = uomId($pdo, $uomCode, $uomCache);
        $price   = money($row[8] ?? null) ?? 0;
        $total   = money($row[9] ?? null) ?? (float)$qty * (float)$price;

        $lineStmt->execute([
            ':inv_id'  => $invId,
            ':prod_id' => $prodId,
            ':qb_item' => $qbItem,
            ':desc'    => $desc,
            ':qty'     => $qty,
            ':uom_id'  => $uomId,
            ':price'   => $price,
            ':total'   => $total,
        ]);
        $inserted++;
        $processedInvIds[$invId] = true;
    }

    log_msg("  Done: $inserted new invoice line items.");
}

// ---------------------------------------------------------------------------
// 5. Bills / AP (file 6)
// ---------------------------------------------------------------------------

function importBills(PDO $pdo, string $dir, array $vendorMap): void
{
    log_msg('Importing bills (file 6)...');
    $rows = loadSheet(findFile($dir, 6));

    $termCache = [];
    foreach ($pdo->query('SELECT id, name FROM payment_terms') as $t) {
        $termCache[strtoupper(trim($t['name']))] = (int)$t['id'];
    }

    $existing = [];
    foreach ($pdo->query('SELECT bill_number, vendor_id FROM bills') as $r) {
        $existing[$r['vendor_id'] . '|' . $r['bill_number']] = true;
    }

    $billStmt = $pdo->prepare("
        INSERT INTO bills
            (vendor_id, bill_number, bill_type, status,
             bill_date, due_date, total_amount, balance_due, aging_days)
        VALUES
            (:vendor_id, :num, 'bill', :status,
             :bill_date, :due_date, :total, :balance, :aging)
        ON DUPLICATE KEY UPDATE
            due_date    = VALUES(due_date),
            balance_due = VALUES(balance_due),
            aging_days  = VALUES(aging_days)
    ");

    $inserted    = 0;
    $headerFound = false;
    $colType = $colDate = $colNum = $colName = $colDue = $colAging = $colBalance = null;

    foreach ($rows as $row) {
        if (!$headerFound) {
            $flat = array_map(fn($v) => strtolower(trim((string)($v ?? ''))), $row);
            if (in_array('type', $flat)) {
                $headerFound = true;
                $colType    = array_search('type', $flat);
                $colDate    = array_search('date', $flat);
                $colNum     = array_search('num', $flat);
                $colName    = array_search('name', $flat);
                $colDue     = array_search('due date', $flat);
                $colAging   = array_search('aging', $flat);
                $colBalance = array_search('open balance', $flat);
            }
            continue;
        }

        $type = clean($row[$colType] ?? null);
        if (!$type || !in_array(strtolower($type), ['bill', 'credit'])) continue;

        $vendorName = clean($row[$colName] ?? null);
        $vendorId   = $vendorName ? ($vendorMap[$vendorName] ?? null) : null;
        if (!$vendorId) continue;

        $num     = clean($row[$colNum] ?? null) ?: null;
        $key     = $vendorId . '|' . ($num ?? '');
        if (isset($existing[$key])) continue;

        $billDate = xlDate($row[$colDate] ?? null);
        $dueDate  = xlDate($row[$colDue] ?? null) ?? $billDate;
        $balance  = money($row[$colBalance] ?? null) ?? 0;
        $aging    = is_numeric($row[$colAging] ?? null) ? (int)$row[$colAging] : null;
        $status   = ($aging > 0) ? 'overdue' : 'approved';
        if ($balance == 0) $status = 'paid';

        $billStmt->execute([
            ':vendor_id' => $vendorId,
            ':num'       => $num,
            ':status'    => $status,
            ':bill_date' => $billDate,
            ':due_date'  => $dueDate,
            ':total'     => $balance,
            ':balance'   => $balance,
            ':aging'     => $aging,
        ]);
        $existing[$key] = true;
        $inserted++;
    }

    log_msg("  Done: $inserted new bills.");
}

// ---------------------------------------------------------------------------
// 6. Inventory transactions (file 7)
// ---------------------------------------------------------------------------

function importInventoryTransactions(PDO $pdo, string $dir, array $productMap, array $customerMap): void
{
    log_msg('Importing inventory transactions (file 7)...');
    $rows = loadSheet(findFile($dir, 7));

    $uomCache  = [];
    $inserted  = 0;
    $existing  = [];
    foreach ($pdo->query('SELECT quickbooks_id FROM inventory_transactions WHERE quickbooks_id IS NOT NULL') as $r) {
        $existing[$r['quickbooks_id']] = true;
    }

    $stmt = $pdo->prepare("
        INSERT INTO inventory_transactions
            (product_id, transaction_type, reference_type, reference_num,
             transaction_date, qty, unit_cost, total_cost,
             qty_on_hand_after, avg_cost_after, asset_value_after, uom_id, quickbooks_id)
        VALUES
            (:prod_id, :type, :ref_type, :ref_num,
             :date, :qty, :cost, :total_cost,
             :on_hand, :avg_cost, :asset_val, :uom_id, :qb_id)
    ");

    $currentProduct = null;
    $headerFound    = false;
    $colType = $colDate = $colName = $colNum = $colQty = $colCost = $colOnHand = $colUom = $colAvgCost = $colAsset = null;

    foreach ($rows as $row) {
        if (!$headerFound) {
            $flat = array_map(fn($v) => strtolower(trim((string)($v ?? ''))), $row);
            if (in_array('type', $flat)) {
                $headerFound = true;
                $colType     = array_search('type', $flat);
                $colDate     = array_search('date', $flat);
                $colName     = array_search('name', $flat);
                $colNum      = array_search('num', $flat);
                $colQty      = array_search('qty', $flat);
                $colCost     = array_search('cost', $flat);
                $colOnHand   = array_search('on hand', $flat);
                $colUom      = array_search('u/m', $flat);
                $colAvgCost  = array_search('avg cost', $flat);
                $colAsset    = array_search('asset value', $flat);
            }
            continue;
        }

        $type = clean($row[$colType] ?? null);
        if (!$type) {
            // May be a product heading row
            $heading = clean($row[3] ?? null);
            if ($heading && !str_starts_with($heading, 'Total')) {
                // Find product by SKU in heading
                foreach ($productMap as $qbItem => $id) {
                    $qbItem = (string)$qbItem;
                    $sku = str_contains($qbItem, ':') ? explode(':', $qbItem, 2)[1] : $qbItem;
                    if (str_starts_with($heading, $sku)) {
                        $currentProduct = $id;
                        break;
                    }
                }
            }
            continue;
        }

        if (!in_array(strtolower($type), ['invoice', 'bill', 'inventory adjustment', 'receipt'])) continue;
        if (!$currentProduct) continue;

        $num   = clean($row[$colNum] ?? null);
        $qbId  = $currentProduct . '|' . $type . '|' . $num . '|' . ($row[$colDate] ?? '');
        if (isset($existing[$qbId])) continue;

        $txnType = match (strtolower($type)) {
            'invoice'              => 'sale',
            'bill', 'receipt'      => 'receipt',
            default                => 'adjustment',
        };

        $qty   = qty($row[$colQty] ?? null) ?? 0;
        $cost  = money($row[$colCost] ?? null);

        $stmt->execute([
            ':prod_id'   => $currentProduct,
            ':type'      => $txnType,
            ':ref_type'  => ($txnType === 'sale') ? 'invoice' : 'bill',
            ':ref_num'   => $num,
            ':date'      => xlDate($row[$colDate] ?? null),
            ':qty'       => $qty,
            ':cost'      => $cost,
            ':total_cost'=> ($cost !== null && is_numeric($qty)) ? (float)$cost * abs((float)$qty) : null,
            ':on_hand'   => qty($row[$colOnHand] ?? null),
            ':avg_cost'  => money($row[$colAvgCost] ?? null),
            ':asset_val' => money($row[$colAsset] ?? null),
            ':uom_id'    => uomId($pdo, clean($row[$colUom] ?? null), $uomCache),
            ':qb_id'     => $qbId,
        ]);
        $existing[$qbId] = true;
        $inserted++;
    }

    log_msg("  Done: $inserted new inventory transactions.");
}

// ---------------------------------------------------------------------------
// Main
// ---------------------------------------------------------------------------

log_msg('=== QuickBooks Import Starting ===');
log_msg("Spreadsheet directory: $spreadsheetDir");

$pdo->beginTransaction();
try {
    $vendorMap   = importVendors($pdo, $spreadsheetDir);
    $customerMap = importCustomers($pdo, $spreadsheetDir);
    $productMap  = importProducts($pdo, $spreadsheetDir, $vendorMap);
    importInvoices($pdo, $spreadsheetDir, $customerMap, $productMap);
    importBills($pdo, $spreadsheetDir, $vendorMap);
    importInventoryTransactions($pdo, $spreadsheetDir, $productMap, $customerMap);
    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    log_msg('ERROR: ' . $e->getMessage());
    log_msg($e->getTraceAsString());
    exit(1);
}

log_msg('=== Import Complete ===');
