<?php
/**
 * "Processed by" Backfill
 *
 * Reads the QuickBooks invoice export that carries a "Processed By" column and records,
 * per invoice, who keyed the order in.
 *
 * This is NOT sales attribution. Sales credit belongs to `invoices.sales_rep_id`; this
 * only fills `invoices.processed_by`. The two differ constantly today because reps and
 * distributors cannot enter their own orders, so a salesperson does it for them. Nothing
 * here touches sales_rep_id.
 *
 * The export is line-item level and grouped by customer, so the same invoice number
 * appears on consecutive rows. Columns are located by header text rather than position,
 * because QuickBooks reports shift columns between runs.
 *
 * Usage (from the app root on the server):
 *
 *   php database/import_processed_by.php --file="/path/2026 Invoices with Processed By.xlsx"
 *   php database/import_processed_by.php --file="…" --commit
 *
 * Options:
 *   --commit            apply; without it nothing is written
 *   --sheet=NAME        worksheet to read (default: the first one containing the header)
 *   --overwrite         replace a processed_by that is already set and differs
 *   --limit=N           only consider the first N transaction rows (for a quick look)
 *
 * Re-runnable. Only `Invoice` rows are used — credit memos are counted and skipped, since
 * they are separate documents and not rows in `invoices`. Values are stored as the raw
 * QuickBooks string; `processed_by_rep_id` is deliberately left alone, because these are
 * QuickBooks logins ("Samantha", "Hpi") rather than rep names, and guessing the mapping
 * would invent links. Unmatched names are reported so they can be mapped deliberately.
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

use App\Core\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;

$argvList  = $argv ?? [];
$commit    = in_array('--commit', $argvList, true);
$overwrite = in_array('--overwrite', $argvList, true);

$file  = null;
$sheet = null;
$limit = 0;

foreach ($argvList as $arg) {
    if (str_starts_with($arg, '--file=')) {
        $file = substr($arg, 7);
    } elseif (str_starts_with($arg, '--sheet=')) {
        $sheet = substr($arg, 8);
    } elseif (str_starts_with($arg, '--limit=')) {
        $limit = (int)substr($arg, 8);
    }
}

if ($file === null || !is_file($file)) {
    fwrite(STDERR, "Usage: php database/import_processed_by.php --file=\"/path/export.xlsx\" [--commit]\n");
    exit(1);
}

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();
App\Core\Config::load(BASE_PATH . '/config');
Database::init();

echo $commit
    ? "COMMIT MODE — changes will be written.\n\n"
    : "DRY RUN — nothing will be written. Re-run with --commit to apply.\n\n";

// ---------------------------------------------------------------- read the workbook

$reader = IOFactory::createReaderForFile($file);
$reader->setReadDataOnly(true);
$book = $reader->load($file);

$worksheet = $sheet !== null ? $book->getSheetByName($sheet) : null;

// Find the sheet and row holding the header, identified by the "Processed By" label.
$headerRow = null;
$columns   = [];

foreach ($worksheet !== null ? [$worksheet] : $book->getAllSheets() as $ws) {
    foreach ($ws->toArray(null, true, false, false) as $rowIndex => $row) {
        $labels = array_map(
            fn($c) => is_string($c) ? strtolower(trim($c)) : '',
            $row
        );

        if (in_array('processed by', $labels, true)) {
            $headerRow = $rowIndex;
            $worksheet = $ws;
            foreach ($labels as $colIndex => $label) {
                if ($label !== '') {
                    $columns[$label] = $colIndex;
                }
            }
            break 2;
        }
    }
}

if ($headerRow === null) {
    fwrite(STDERR, "Could not find a 'Processed By' column in the workbook.\n");
    exit(1);
}

foreach (['type', 'num', 'processed by'] as $required) {
    if (!isset($columns[$required])) {
        fwrite(STDERR, "Header row is missing the '{$required}' column.\n");
        exit(1);
    }
}

printf(
    "Sheet '%s', header on row %d. Using columns: Type=%d, Num=%d, Processed By=%d\n\n",
    $worksheet->getTitle(),
    $headerRow + 1,
    $columns['type'],
    $columns['num'],
    $columns['processed by']
);

// ------------------------------------------------------------------- collect the map

$map          = [];   // invoice_number => processed_by
$conflicts    = [];   // invoice_number => [values]
$nameCounts   = [];
$creditMemos  = 0;
$blank        = 0;
$rows         = 0;

foreach ($worksheet->toArray(null, true, false, false) as $rowIndex => $row) {
    if ($rowIndex <= $headerRow) {
        continue;
    }

    $type = trim((string)($row[$columns['type']] ?? ''));
    if ($type === '') {
        continue;   // customer group header or subtotal row
    }

    $rows++;
    if ($limit > 0 && $rows > $limit) {
        break;
    }

    if (strcasecmp($type, 'Credit Memo') === 0) {
        $creditMemos++;
        continue;
    }
    if (strcasecmp($type, 'Invoice') !== 0) {
        continue;
    }

    $number = trim((string)($row[$columns['num']] ?? ''));
    $who    = trim((string)($row[$columns['processed by']] ?? ''));

    if ($number === '') {
        continue;
    }
    if ($who === '') {
        $blank++;
        continue;
    }

    $nameCounts[$who] = ($nameCounts[$who] ?? 0) + 1;

    if (isset($map[$number]) && $map[$number] !== $who) {
        $conflicts[$number][] = $who;
        continue;   // first value wins, conflict reported below
    }

    $map[$number] = $who;
}

printf("Transaction rows read: %d\n", $rows);
printf("  invoices with a Processed By: %d\n", count($map));
printf("  credit memo rows skipped:     %d\n", $creditMemos);
printf("  rows with a blank value:      %d\n", $blank);
printf("  invoices with conflicting values: %d%s\n\n", count($conflicts),
    $conflicts !== [] ? ' (first value kept — listed below)' : '');

foreach (array_slice($conflicts, 0, 10, true) as $number => $others) {
    printf("    invoice %s also saw: %s\n", $number, implode(', ', array_unique($others)));
}
if ($conflicts !== []) {
    echo "\n";
}

// ------------------------------------------------- who these names are, for mapping

arsort($nameCounts);
echo "Processed By values found (invoice rows each):\n";

$repNames = [];
foreach (Database::select("SELECT id, name, quickbooks_name, rep_type FROM sales_reps") as $r) {
    $repNames[strtolower($r['quickbooks_name'])] = $r;
    $repNames[strtolower($r['name'])]            = $r;
}

foreach ($nameCounts as $who => $n) {
    $hit = $repNames[strtolower($who)] ?? null;
    printf(
        "  %-14s %6d   %s\n",
        $who,
        $n,
        $hit !== null
            ? 'matches sales_reps: ' . $hit['name'] . ' (' . $hit['rep_type'] . ')'
            : 'no sales_reps match — stored as text'
    );
}
echo "\n";

// ------------------------------------------------------------- compare against the DB

$existing = [];
foreach (Database::select("SELECT invoice_number, processed_by FROM invoices") as $r) {
    $existing[$r['invoice_number']] = $r['processed_by'];
}

$toSet     = [];
$unchanged = 0;
$differing = [];
$missing   = [];

foreach ($map as $number => $who) {
    if (!array_key_exists($number, $existing)) {
        $missing[] = $number;
        continue;
    }

    $current = $existing[$number];

    if ($current === $who) {
        $unchanged++;
    } elseif ($current === null || $current === '') {
        $toSet[$number] = $who;
    } else {
        $differing[$number] = [$current, $who];
        if ($overwrite) {
            $toSet[$number] = $who;
        }
    }
}

printf("Against %d invoices in the database:\n", count($existing));
printf("  would set processed_by:        %d\n", count($toSet));
printf("  already correct:               %d\n", $unchanged);
printf("  already set but different:     %d%s\n", count($differing),
    $overwrite ? ' (included above, --overwrite)' : ' (skipped — pass --overwrite to replace)');
printf("  no matching invoice number:    %d\n\n", count($missing));

if ($missing !== []) {
    printf("  first few unmatched invoice numbers: %s\n\n", implode(', ', array_slice($missing, 0, 12)));
}
foreach (array_slice($differing, 0, 10, true) as $number => [$was, $now]) {
    printf("    invoice %s: '%s' -> '%s'\n", $number, $was, $now);
}
if ($differing !== []) {
    echo "\n";
}

// ------------------------------------------------------------------------- apply

if (!$commit) {
    echo "Dry run complete. Nothing was written.\n";
    exit(0);
}

if ($toSet === []) {
    echo "Nothing to do.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    $update = $pdo->prepare("UPDATE invoices SET processed_by = ? WHERE invoice_number = ?");
    $done   = 0;

    foreach ($toSet as $number => $who) {
        $update->execute([$who, $number]);
        $done++;

        if ($done % 2000 === 0) {
            printf("  %d/%d…\n", $done, count($toSet));
        }
    }

    $pdo->commit();
    printf("\nDone. processed_by set on %d invoice(s).\n", $done);
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "\nFailed, rolled back: " . $e->getMessage() . "\n");
    exit(1);
}
