<?php
/**
 * Relink invoice line items to products.
 *
 * The original import (import_sales_history.php) matched `products.quickbooks_item`
 * exactly, so anything QuickBooks wrote as "PARENT:CHILD (Description)" missed and was
 * left with product_id NULL. The raw name survives in
 * invoice_line_items.quickbooks_item, so the link can be rebuilt in place.
 *
 * Only unambiguous matches are applied. A normalized name that hits more than one
 * product is reported and skipped — guessing which product earned $200k of history is
 * not something a script should do.
 *
 * Nothing but product_id is touched. Invoice totals, quantities and prices are untouched,
 * so this cannot change any financial figure.
 *
 * Usage (from the app root on the server):
 *
 *   php database/relink_invoice_products.php
 *   php database/relink_invoice_products.php --commit
 *
 * Options:
 *   --commit     apply; without it nothing is written
 *   --verbose    list every matched name, not just the summary
 *
 * Re-runnable and safe to run after the QuickBooks SKU renaming lands — it only ever
 * considers rows that are still unlinked.
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

use App\Core\Database;

$argvList = $argv ?? [];
$commit   = in_array('--commit', $argvList, true);
$verbose  = in_array('--verbose', $argvList, true);

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();
App\Core\Config::load(BASE_PATH . '/config');
Database::init();

echo $commit
    ? "COMMIT MODE — changes will be written.\n\n"
    : "DRY RUN — nothing will be written. Re-run with --commit to apply.\n\n";

/**
 * QuickBooks items that are charges or parent-item buckets, not products.
 *
 * These are correctly left with a NULL product_id. "<PARENT> - Other" is what QuickBooks
 * emits for a sale booked against a parent item rather than a specific child.
 */
$nonProduct = ['SH', 'CPU', 'DELIVERY', 'ADJ', 'DISCOUNT', 'FREIGHT'];

/** Strip a trailing " (description)" and surrounding whitespace. */
$base = static function (string $item): string {
    $pos = strpos($item, ' (');

    return trim($pos === false ? $item : substr($item, 0, $pos));
};

// ------------------------------------------------------------------ load products

$bySku  = [];
$byQb   = [];

foreach (Database::select("SELECT id, sku, quickbooks_item, name FROM products") as $p) {
    $id = (int)$p['id'];

    if (($p['sku'] ?? '') !== '') {
        $bySku[strtoupper(trim($p['sku']))][$id] = $p['name'];
    }
    if (($p['quickbooks_item'] ?? '') !== '') {
        $byQb[strtoupper(trim($p['quickbooks_item']))][$id] = $p['name'];
    }
}

printf("Products loaded: %d distinct SKUs, %d distinct QuickBooks item names\n\n", count($bySku), count($byQb));

// -------------------------------------------------- examine the unlinked line items

$rows = Database::select("
    SELECT quickbooks_item, COUNT(*) AS lines_count, SUM(line_total) AS revenue
    FROM invoice_line_items
    WHERE product_id IS NULL
      AND quickbooks_item IS NOT NULL
      AND quickbooks_item <> ''
    GROUP BY quickbooks_item
    ORDER BY SUM(line_total) DESC
");

$matched     = [];   // quickbooks_item => product_id
$ambiguous   = [];
$unmatched   = [];
$skipped     = [];
$stats       = ['matched_lines' => 0, 'matched_revenue' => 0.0, 'skipped_lines' => 0,
                'unmatched_lines' => 0, 'unmatched_revenue' => 0.0];

foreach ($rows as $r) {
    $item    = (string)$r['quickbooks_item'];
    $lines   = (int)$r['lines_count'];
    $revenue = (float)$r['revenue'];
    $b       = $base($item);
    $upper   = strtoupper($b);

    // Charges and parent-item buckets stay unlinked by design.
    if (in_array($upper, $nonProduct, true) || str_ends_with($upper, '- OTHER')) {
        $skipped[$item]         = $lines;
        $stats['skipped_lines'] += $lines;
        continue;
    }

    // Candidates, most specific first: the full QuickBooks name, then the SKU, then the
    // child portion of a PARENT:CHILD name.
    $child      = strpos($upper, ':') !== false ? trim(substr($upper, strrpos($upper, ':') + 1)) : null;
    $candidates = [];

    foreach ([$byQb[$upper] ?? null, $bySku[$upper] ?? null, $child !== null ? ($bySku[$child] ?? null) : null] as $hit) {
        if ($hit !== null) {
            $candidates = $hit;
            break;
        }
    }

    if ($candidates === []) {
        $unmatched[$item]            = [$lines, $revenue];
        $stats['unmatched_lines']   += $lines;
        $stats['unmatched_revenue'] += $revenue;
        continue;
    }

    if (count($candidates) > 1) {
        $ambiguous[$item] = [$lines, $revenue, $candidates];
        continue;
    }

    $matched[$item]            = array_key_first($candidates);
    $stats['matched_lines']   += $lines;
    $stats['matched_revenue'] += $revenue;
}

printf("Distinct unlinked item names: %d\n\n", count($rows));
printf("  will link:              %4d names  %6d lines  \$%s\n", count($matched), $stats['matched_lines'], number_format($stats['matched_revenue'], 2));
printf("  ambiguous (skipped):    %4d names\n", count($ambiguous));
printf("  charges / Other buckets:%4d names  %6d lines  (correctly left unlinked)\n", count($skipped), $stats['skipped_lines']);
printf("  no product found:       %4d names  %6d lines  \$%s\n\n", count($unmatched), $stats['unmatched_lines'], number_format($stats['unmatched_revenue'], 2));

if ($ambiguous !== []) {
    echo "Ambiguous — more than one product matches, so left alone:\n";
    foreach (array_slice($ambiguous, 0, 15, true) as $item => [$lines, $revenue, $cands]) {
        printf("  %-52s %4d lines -> %s\n", substr($item, 0, 50), $lines, implode(' | ', array_slice($cands, 0, 3)));
    }
    echo "\n";
}

if ($verbose && $matched !== []) {
    echo "Matches to apply:\n";
    foreach ($matched as $item => $pid) {
        printf("  %-56s -> product %d\n", substr($item, 0, 54), $pid);
    }
    echo "\n";
}

if ($unmatched !== []) {
    echo "Top unmatched by revenue — these need the QuickBooks SKU mapping:\n";
    $shown = 0;
    foreach ($unmatched as $item => [$lines, $revenue]) {
        printf("  %-52s %4d lines  \$%s\n", substr($item, 0, 50), $lines, number_format($revenue, 0));
        if (++$shown >= 15) {
            break;
        }
    }
    echo "\n";
}

// ------------------------------------------------------------------------- apply

if (!$commit) {
    echo "Dry run complete. Nothing was written.\n";
    exit(0);
}

if ($matched === []) {
    echo "Nothing to link.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    $update = $pdo->prepare("
        UPDATE invoice_line_items
        SET product_id = ?
        WHERE quickbooks_item = ? AND product_id IS NULL
    ");

    $linked = 0;
    foreach ($matched as $item => $productId) {
        $update->execute([$productId, $item]);
        $linked += $update->rowCount();
    }

    $pdo->commit();
    printf("\nDone. %d line item(s) linked to a product.\n", $linked);
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "\nFailed, rolled back: " . $e->getMessage() . "\n");
    exit(1);
}
