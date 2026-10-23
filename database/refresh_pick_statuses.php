<?php

declare(strict_types=1);

/**
 * Make every order's pick_status agree with its own line quantities.
 *
 *   php database/refresh_pick_statuses.php            dry run, the default
 *   php database/refresh_pick_statuses.php --commit
 *
 * WHY THIS EXISTS. replaceLineItems used to delete and re-insert lines without carrying
 * qty_picked across, so editing a sales order zeroed the pick while pick_status went on
 * saying 'ready'. That is how SO 22 came to wear a Picked badge over an empty pack
 * screen. The cause is fixed; this repairs the orders it already happened to.
 *
 * Worth keeping afterwards. pick_status is derived from the lines, and anything derived
 * can drift - a direct database edit, an import, a future bug. A command that recomputes
 * it from the source of truth is cheap to run and answers "is the badge lying?" in one
 * step instead of a session of guessing.
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;
use App\Repositories\SalesOrderRepository;

$commit = in_array('--commit', array_slice($argv, 1), true);

if (!$commit) {
    echo "DRY RUN — nothing will be written. Add --commit to apply.\n\n";
}

$repo = new SalesOrderRepository();

// Only orders still in play. A shipped or cancelled order's pick status is history, and
// rewriting history to match a rule we hold today is how an audit trail stops being one.
$orders = Database::select("
    SELECT so.id, so.so_number, so.pick_status,
           COALESCE(SUM(li.qty_ordered), 0)              AS ordered,
           COALESCE(SUM(li.qty_picked), 0)               AS picked,
           SUM(CASE WHEN COALESCE(li.pick_note,'') <> '' THEN 1 ELSE 0 END) AS noted
    FROM sales_orders so
    LEFT JOIN sales_order_line_items li ON li.sales_order_id = so.id
    WHERE so.status IN ('draft','confirmed','processing','partially_shipped')
    GROUP BY so.id
    ORDER BY so.id
");

$wrong = [];

foreach ($orders as $o) {
    $ordered = (float)$o['ordered'];
    $picked  = (float)$o['picked'];

    // The same rule refreshPickStatus applies, worked out here so a dry run can show
    // what would change without writing anything.
    $should = match (true) {
        (int)$o['noted'] > 0           => 'short',
        $picked > 0 && $picked >= $ordered => 'ready',
        $picked > 0                    => 'in_progress',
        default                        => 'not_started',
    };

    if ($should !== $o['pick_status']) {
        $wrong[] = $o + ['should' => $should];
    }
}

printf("%d open order(s) checked, %d disagree with their own lines.\n", count($orders), count($wrong));

if ($wrong === []) {
    echo "Nothing to repair.\n";
    exit(0);
}

echo "\n";
printf("  %-6s %-14s %-14s %-9s %s\n", 'SO', 'SAYS', 'SHOULD SAY', 'ORDERED', 'PICKED');

foreach ($wrong as $w) {
    printf("  %-6s %-14s %-14s %-9s %s\n",
        $w['so_number'], $w['pick_status'], $w['should'],
        rtrim(rtrim(number_format((float)$w['ordered'], 2), '0'), '.'),
        rtrim(rtrim(number_format((float)$w['picked'], 2), '0'), '.'));
}

if (!$commit) {
    echo "\nNothing written. Add --commit to repair them.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    foreach ($wrong as $w) {
        $repo->refreshPickStatus((int)$w['id']);
    }
    $pdo->commit();
    printf("\nRepaired %d.\n", count($wrong));
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}
