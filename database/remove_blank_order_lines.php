<?php

declare(strict_types=1);

/**
 * Delete line items that were never really there.
 *
 *   php database/remove_blank_order_lines.php
 *   php database/remove_blank_order_lines.php --commit
 *
 * The order form draws three empty rows, each carrying a default quantity of 1. The
 * parser skipped a row only when its quantity AND price were both zero, so every empty
 * row it drew was saved as a line: no product, no code, no description, nothing owed.
 *
 * They are not harmless. They appear on the pick screen as items somebody has to go and
 * find, they inflate the ordered quantity on the Ready to ship screen, and they print
 * on a packing slip as blank rows.
 *
 * Only lines with nothing on them at all are removed, and only where nothing has been
 * picked or shipped against them — a line somebody has touched is not blank, whatever
 * it looks like.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;

$commit = in_array('--commit', array_slice($argv, 1), true);

if (!$commit) {
    echo "DRY RUN — nothing will be written. Add --commit to apply.\n\n";
}

const BLANK = "
    li.product_id IS NULL
    AND COALESCE(TRIM(li.quickbooks_item), '') = ''
    AND COALESCE(TRIM(li.description), '')     = ''
    AND COALESCE(li.unit_price, 0) = 0
    AND COALESCE(li.line_total, 0) = 0
    AND COALESCE(li.qty_picked, 0)  = 0
    AND COALESCE(li.qty_shipped, 0) = 0
";

$rows = Database::select("
    SELECT li.id, li.sales_order_id, li.qty_ordered,
           so.so_number, so.status, c.company_name
    FROM sales_order_line_items li
    JOIN sales_orders so ON so.id = li.sales_order_id
    JOIN customers c     ON c.id  = so.customer_id
    WHERE " . BLANK . "
    ORDER BY so.id, li.id
");

if ($rows === []) {
    echo "No blank lines. Nothing to do.\n";
    exit(0);
}

$byOrder = [];

foreach ($rows as $r) {
    $byOrder[$r['so_number']][] = $r;
}

foreach ($byOrder as $soNumber => $lines) {
    printf(
        "#%-6s %-34s %-18s %d blank line(s)\n",
        $soNumber,
        substr((string)$lines[0]['company_name'], 0, 34),
        (string)$lines[0]['status'],
        count($lines)
    );
}

printf("\n%d blank line(s) across %d order(s).\n", count($rows), count($byOrder));

if (!$commit) {
    echo "\nNothing written. Re-run with --commit to apply.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    // Aliased delete, because the condition is written with the li. prefix it is
    // checked under in the SELECT above. One definition, checked twice.
    $stmt = $pdo->prepare("DELETE li FROM sales_order_line_items li WHERE li.id = ? AND " . BLANK);

    foreach ($rows as $r) {
        $stmt->execute([(int)$r['id']]);
    }

    $pdo->commit();
    printf("\nDone. %d line(s) removed.\n", count($rows));
} catch (\Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "\nRolled back: " . $e->getMessage() . "\n");
    exit(1);
}
