<?php
/**
 * Sales Rep Backfill
 *
 * Reads a QuickBooks "Sales by Rep Detail" style report and assigns reps to invoices,
 * then derives each customer's rep from their most recent invoice.
 *
 * The report is a grouped QuickBooks export: the rep name sits alone in column B as a
 * group header, its invoice rows follow, then a "Total <REP>" row closes the group. So the
 * rep for any row is whichever group header last appeared above it.
 *
 * Usage (from the app root on the server):
 *
 *   php database/import_reps.php --file="/path/Report_from_US_SPECIALTY_COATINGS.xlsx"
 *   php database/import_reps.php --file="…" --commit
 *
 * Options:
 *   --commit              apply; without it nothing is written
 *   --skip-customers      only set invoices.sales_rep_id, don't derive customers
 *   --csv=/path/map.csv   read a pre-extracted `invoice_number,qb_rep` CSV instead of the
 *                         workbook — useful for re-running without the original export
 *
 * Re-runnable. Reps must already exist in `sales_reps` (migration 048) — an unrecognised
 * QuickBooks name is reported, never silently created, so a typo in the report can't
 * quietly invent a rep.
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$argvList      = $argv ?? [];
$commit        = in_array('--commit', $argvList, true);
$skipCustomers = in_array('--skip-customers', $argvList, true);

$file = null;
$csv  = null;
foreach ($argvList as $a) {
    if (str_starts_with($a, '--file=')) $file = trim(substr($a, 7), '"\'');
    if (str_starts_with($a, '--csv='))  $csv  = trim(substr($a, 6), '"\'');
}
if ($csv !== null) {
    if (!is_file($csv)) { fwrite(STDERR, "ERROR: csv not found: $csv\n"); exit(1); }
} elseif ($file === null || !is_file($file)) {
    fwrite(STDERR, "ERROR: pass --file=/path/to/report.xlsx or --csv=/path/map.csv\n");
    exit(1);
}

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

$pdo = new PDO(
    sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4',
        trim($_ENV['DB_HOST'] ?? 'localhost'), trim($_ENV['DB_DATABASE'])),
    trim($_ENV['DB_USERNAME']), trim($_ENV['DB_PASSWORD']),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

function say(string $m = ''): void { echo $m . PHP_EOL; }
function rule(): void { say(str_repeat('-', 72)); }

say();
rule();
say('  SALES REP BACKFILL' . ($commit ? '  [COMMIT]' : '  [DRY RUN — nothing will be written]'));
rule();
say('  Source: ' . basename($csv ?? $file));
say();

// ---------------------------------------------------------------- known reps
$repByQb = [];
foreach ($pdo->query("SELECT id, quickbooks_name, rep_type FROM sales_reps") as $r) {
    $repByQb[strtoupper(trim($r['quickbooks_name']))] = ['id' => (int)$r['id'], 'type' => $r['rep_type']];
}
say(sprintf('  reps known in sales_reps : %d', count($repByQb)));

// ---------------------------------------------------------------- read source
$invoiceRep = [];   // invoice number => qb rep name
$unknown    = [];
$lineCount  = 0;

if ($csv !== null) {
    $fh = fopen($csv, 'r');
    fgetcsv($fh);   // header
    while (($row = fgetcsv($fh)) !== false) {
        $num  = trim((string)($row[0] ?? ''));
        $name = trim((string)($row[1] ?? ''));
        if ($num === '' || $name === '') continue;
        $invoiceRep[$num] = $name;
        $lineCount++;
        if (!isset($repByQb[strtoupper($name)])) {
            $unknown[$name] = ($unknown[$name] ?? 0) + 1;
        }
    }
    fclose($fh);

} else {
    $reader = IOFactory::createReader(pathinfo($file, PATHINFO_EXTENSION) === 'xlsx' ? 'Xlsx' : 'Xls');
    $reader->setReadDataOnly(true);
    $rows = $reader->load($file)->getSheet(0)->toArray(null, true, false, false);

    // Column positions in this report layout: B group header, E type, I invoice number
    $COL_GROUP = 1; $COL_TYPE = 4; $COL_NUM = 8;
    $current = null;

    foreach ($rows as $r) {
        $group = trim((string)($r[$COL_GROUP] ?? ''));
        if ($group !== '') {
            // "Total BILL KENNEDY" closes a group rather than opening one
            $current = stripos($group, 'total') === 0 ? null : $group;
            if ($current !== null && !isset($repByQb[strtoupper($current)])) {
                $unknown[$current] = ($unknown[$current] ?? 0) + 1;
            }
            continue;
        }
        if ($current === null) continue;
        if (strcasecmp(trim((string)($r[$COL_TYPE] ?? '')), 'invoice') !== 0) continue;

        $num = trim((string)($r[$COL_NUM] ?? ''));
        if ($num === '') continue;
        $num = preg_replace('/\.0$/', '', $num);

        $invoiceRep[$num] = $current;   // an invoice never spans two rep groups
        $lineCount++;
    }
}

say(sprintf('  invoice lines read       : %d', $lineCount));
say(sprintf('  unique invoice numbers   : %d', count($invoiceRep)));
if ($unknown) {
    say();
    say('  ⚠ QuickBooks rep names not in sales_reps — these rows will be SKIPPED.');
    say('    Add them to sales_reps first if they are real.');
    foreach ($unknown as $n => $c) say(sprintf('      %s', $n));
}
say();

// ---------------------------------------------------------------- match to DB
$find = $pdo->prepare("SELECT id, customer_id FROM invoices WHERE invoice_number = ? LIMIT 1");

$toSet   = [];   // invoice id => rep id
$noMatch = 0;
$skipped = 0;
$byRep   = [];

foreach ($invoiceRep as $num => $qbName) {
    $rep = $repByQb[strtoupper($qbName)] ?? null;
    if ($rep === null) { $skipped++; continue; }

    $find->execute([$num]);
    $inv = $find->fetch();
    if (!$inv) { $noMatch++; continue; }

    $toSet[(int)$inv['id']] = $rep['id'];
    $byRep[$qbName] = ($byRep[$qbName] ?? 0) + 1;
}

rule();
say('  PLAN');
rule();
say(sprintf('  → invoices to tag        : %d', count($toSet)));
say(sprintf('  → in report, not in DB   : %d  (older than the imported history)', $noMatch));
if ($skipped) say(sprintf('  → skipped, unknown rep   : %d', $skipped));
say();

arsort($byRep);
$shown = 0;
foreach ($byRep as $name => $n) {
    if ($shown++ >= 12) { say(sprintf('    … and %d more reps', count($byRep) - 12)); break; }
    say(sprintf('    %-24s %5d invoices', $name, $n));
}
say();

if (!$commit) {
    say('  DRY RUN — nothing written. Re-run with --commit to apply.');
    say();
    exit(0);
}

// ---------------------------------------------------------------- apply
$pdo->beginTransaction();
try {
    $upd = $pdo->prepare("UPDATE invoices SET sales_rep_id = :rep WHERE id = :id");
    foreach ($toSet as $invId => $repId) {
        $upd->execute([':rep' => $repId, ':id' => $invId]);
    }
    say(sprintf('  tagged %d invoices', count($toSet)));

    $custCount = 0;
    if (!$skipCustomers) {
        // Each customer takes the rep from their most recent tagged invoice. Placeholder
        // reps (No sales rep / house / website / partner) are excluded so a single web
        // order can't overwrite a real rep relationship.
        $custCount = $pdo->exec("
            UPDATE customers c
            SET c.sales_rep_id = (
                SELECT i.sales_rep_id
                FROM invoices i
                JOIN sales_reps sr ON sr.id = i.sales_rep_id
                WHERE i.customer_id = c.id
                  AND i.sales_rep_id IS NOT NULL
                  AND sr.rep_type = 'person'
                ORDER BY i.invoice_date DESC, i.id DESC
                LIMIT 1
            )
            WHERE EXISTS (
                SELECT 1 FROM invoices i2
                JOIN sales_reps sr2 ON sr2.id = i2.sales_rep_id
                WHERE i2.customer_id = c.id AND sr2.rep_type = 'person'
            )
        ");
        say(sprintf('  assigned a rep to %d customers', (int)$custCount));
    }

    $pdo->commit();

    say();
    rule();
    say('  DONE');
    rule();
    say('  Only invoices whose rep is a real person now feed customer assignment —');
    say('  house, website and "No sales rep" entries are recorded on the invoice but');
    say('  never become a customer\'s rep.');
    say();

} catch (Throwable $e) {
    $pdo->rollBack();
    say();
    say('  FAILED — rolled back, database unchanged.');
    say('  ' . $e->getMessage());
    say();
    exit(1);
}
