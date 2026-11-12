<?php

declare(strict_types=1);

/**
 * Put a working amount of stock on the DuraStripe cases, for walking the flow.
 *
 *   php database/set_demo_stock.php            dry run
 *   php database/set_demo_stock.php --commit   writes it
 *
 * Three of the four are negative — DSW at −34 — which is fallout from the count bug
 * (a product imported with a count and no location row loses it on first shipment)
 * plus a day of test shipments. Left alone the pick bench flags them short, which is
 * a distraction in a walkthrough and nothing to do with what is being demonstrated.
 *
 * Inventory does not matter in testing and all of this data is deleted at cutover,
 * so these are simply set high enough to run the flow many times over.
 *
 * It goes through StockRepository rather than writing products.qty_on_hand directly,
 * which matters: qty_on_hand is recomputed from product_stock rows on every movement,
 * so a number written straight onto the product is replaced by the sum of the
 * locations the moment anything ships. Going through the proper path also leaves an
 * inventory transaction behind, so the adjustment is visible rather than magic.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;
use App\Repositories\StockRepository;

const TARGET = 5000;

$commit = in_array('--commit', array_slice($argv, 1), true);
$skus   = ['DSW', 'DSY', 'DSR', 'DSGRN'];

$stock    = new StockRepository();
$location = $stock->defaultWarehouseId();

if ($location === null) {
    fwrite(STDERR, "No default warehouse — add one under Admin > Locations.\n");
    exit(1);
}

$in   = implode(',', array_fill(0, count($skus), '?'));
$rows = Database::select(
    "SELECT id, sku, name, qty_on_hand FROM products WHERE sku IN ({$in})",
    $skus
);

printf("%-8s %-40s %12s %12s %10s\n", 'SKU', 'PRODUCT', 'NOW', 'TARGET', 'MOVE');
echo str_repeat('-', 86), "\n";

$plan = [];

foreach ($rows as $r) {
    $now   = (float)$r['qty_on_hand'];
    $delta = TARGET - $now;
    $plan[] = ['id' => (int)$r['id'], 'sku' => $r['sku'], 'delta' => $delta];

    printf("%-8s %-40s %12s %12s %10s\n",
        $r['sku'], substr((string)$r['name'], 0, 38),
        rtrim(rtrim(number_format($now, 2), '0'), '.'),
        number_format(TARGET),
        ($delta >= 0 ? '+' : '') . rtrim(rtrim(number_format($delta, 2), '0'), '.'));
}

echo str_repeat('-', 86), "\n";

if (count($rows) !== count($skus)) {
    fwrite(STDERR, "\nExpected " . count($skus) . " products, found " . count($rows) . ". Stopping.\n");
    exit(1);
}

if (!$commit) {
    echo "\nDry run. Add --commit to write it.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    foreach ($plan as $p) {
        if (abs($p['delta']) < 0.0001) {
            continue;
        }

        // A rise is stock in at the default warehouse; a fall is stock out of it.
        $stock->applyMovement([
            'product_id'       => $p['id'],
            'qty'              => abs($p['delta']),
            'type'             => 'adjustment',
            'reason_code'      => 'count_correction',
            'to_location_id'   => $p['delta'] > 0 ? $location : null,
            'from_location_id' => $p['delta'] < 0 ? $location : null,
            'notes'            => 'Set to ' . number_format(TARGET) . ' for walking the flow. '
                                . 'Test data, not a counted figure.',
            'user_id'          => 1,
        ]);
    }

    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "\nRolled back: " . $e->getMessage() . "\n");
    exit(1);
}

echo "\nAfter:\n";

foreach (Database::select("SELECT sku, qty_on_hand FROM products WHERE sku IN ({$in})", $skus) as $r) {
    printf("  %-8s %s\n", $r['sku'], rtrim(rtrim(number_format((float)$r['qty_on_hand'], 2), '0'), '.'));
}
