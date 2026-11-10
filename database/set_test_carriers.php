<?php

declare(strict_types=1);

/**
 * Give the open test orders a carrier, so every one of them lands on a bench.
 *
 *   php database/set_test_carriers.php
 *   php database/set_test_carriers.php --commit
 *
 * Orders that were already being picked when the approval gate went in were approved
 * by the migration without passing through gate one, which is where the carrier now
 * gets settled. They sit on the pull list belonging to nobody's bench.
 *
 * Chip's instruction (10 November 2026) was that it does not matter which carrier -
 * these are seeded test orders, not live ones. So rather than assigning at random this
 * splits them the way a real order would split, which costs nothing and makes the
 * parcel and freight benches look like themselves when the flow is demonstrated:
 *
 *   anything in pails, jugs or drums  ->  Freight
 *   everything else (aerosol cases)   ->  FedEx
 *
 * Only orders that are still WORK are touched. "Open" is not the right test on its own:
 * an invoiced order with no tracking number yet is still sitting in the shipping queue
 * waiting for somebody to put a label on it, and it needs a bench exactly as much as a
 * confirmed one does. An order that has already been labelled is history, and history
 * is not ours to tidy.
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

$carriers = [];

foreach (Database::select("SELECT id, name, ship_mode FROM ship_via WHERE is_active = 1") as $c) {
    $carriers[$c['name']] = (int)$c['id'];
}

foreach (['FedEx', 'Freight'] as $needed) {
    if (!isset($carriers[$needed])) {
        fwrite(STDERR, "No '{$needed}' carrier on this server — nothing done.\n");
        exit(1);
    }
}

// Bulk liquid travels on a pallet. Read off the unit of measure and the product name,
// because the catalogue records the pack size in both and neither alone is reliable.
$rows = Database::select("
    SELECT so.id, so.so_number, so.status, c.company_name,
           SUM(CASE
                 WHEN COALESCE(p.uom_code,'') REGEXP 'Gal|Pail|Drum|Jug'
                   OR COALESCE(p.name, li.description) REGEXP 'Gal|Pail|Drum|Jug'
                 THEN 1 ELSE 0 END) AS bulk_lines,
           COUNT(li.id) AS line_count
    FROM sales_orders so
    JOIN customers c ON c.id = so.customer_id
    LEFT JOIN sales_order_line_items li ON li.sales_order_id = so.id
    LEFT JOIN products p ON p.id = li.product_id
    WHERE so.ship_via_id IS NULL
      AND (
            so.status IN ('draft','confirmed','processing','partially_shipped','paid')
         OR (
              so.status = 'invoiced'
              AND NOT EXISTS (
                  SELECT 1 FROM shipment_tracking t
                  JOIN invoices i ON i.id = t.invoice_id
                  WHERE i.sales_order_id = so.id
              )
            )
      )
    GROUP BY so.id
    ORDER BY so.id
");

if ($rows === []) {
    echo "Every open order already has a carrier. Nothing to do.\n";
    exit(0);
}

$plan = [];

printf("%-8s %-34s %-18s %-7s %s\n", 'ORDER', 'CUSTOMER', 'STATUS', 'BULK', 'CARRIER');

foreach ($rows as $r) {
    $name = (int)$r['bulk_lines'] > 0 ? 'Freight' : 'FedEx';
    $plan[] = [(int)$r['id'], $carriers[$name]];

    printf(
        "%-8s %-34s %-18s %-7s %s\n",
        '#' . $r['so_number'],
        substr((string)$r['company_name'], 0, 34),
        (string)$r['status'],
        (int)$r['bulk_lines'] . '/' . (int)$r['line_count'],
        $name
    );
}

$freight = count(array_filter($plan, fn ($p) => $p[1] === $carriers['Freight']));

printf("\n%d order(s): %d freight, %d parcel.\n", count($plan), $freight, count($plan) - $freight);

if (!$commit) {
    echo "\nNothing written. Re-run with --commit to apply.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    // The null check is repeated here on purpose: between the read above and this write
    // somebody may have set one by hand, and theirs is the better answer.
    $stmt = $pdo->prepare("UPDATE sales_orders SET ship_via_id = ? WHERE id = ? AND ship_via_id IS NULL");

    foreach ($plan as [$soId, $carrierId]) {
        $stmt->execute([$carrierId, $soId]);
    }

    $pdo->commit();
    printf("\nDone. %d order(s) updated.\n", count($plan));
} catch (\Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "\nRolled back: " . $e->getMessage() . "\n");
    exit(1);
}
