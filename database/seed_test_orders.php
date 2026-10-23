<?php

declare(strict_types=1);

/**
 * Test orders, for exercising the pull list and the release queue.
 *
 *   php database/seed_test_orders.php              dry run, the default
 *   php database/seed_test_orders.php --commit
 *   php database/seed_test_orders.php --commit --orders=25 --stock
 *   php database/seed_test_orders.php --remove     take them all back out again
 *
 * Every order it writes is tagged SEED in internal_notes and carries a memo saying so,
 * so there is never any doubt which orders are real. --remove finds them by that tag,
 * which is why it can be trusted to take out exactly what this put in.
 *
 * WHY IT SEEDS STOCK TOO. The interesting cases are the ones where there is not enough
 * to go round - that is the whole reason the shipping clerk exists. With every product
 * at zero on hand, every line reads SHORT and the screen proves nothing. So --stock puts
 * a deliberate spread on the shelf: plenty of some, enough of others, not enough of a
 * few. Stock goes in through StockRepository like every other movement, never by writing
 * qty_on_hand, because that column is recomputed and a direct write would be undone the
 * next time anything moved.
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;
use App\Repositories\SalesOrderRepository;
use App\Repositories\StockRepository;

const SEED_TAG = 'SEED — test order, safe to delete';

$args    = array_slice($argv, 1);
$commit  = in_array('--commit', $args, true);
$remove  = in_array('--remove', $args, true);
$doStock = in_array('--stock', $args, true);
$howMany = 25;

foreach ($args as $a) {
    if (str_starts_with($a, '--orders=')) {
        $howMany = max(1, min(200, (int)substr($a, 9)));
    }
}

$pdo = Database::connection();

if ($remove) {
    removeSeed($pdo, $commit);
    exit(0);
}

if (!$commit) {
    echo "DRY RUN — nothing will be written. Add --commit to apply.\n\n";
}

// ---------------------------------------------------------------- the pools

// Real customers, so the screens look like the real thing. Picked from those with a
// delivery address, because an order with nowhere to ship to cannot be labelled and
// would make the shipping screens look broken for a reason that is not their fault.
$customers = Database::select("
    SELECT id, company_name, bill_address_1, bill_city, bill_state, bill_zip, phone
    FROM customers
    WHERE COALESCE(bill_address_1,'') <> '' AND COALESCE(bill_city,'') <> ''
      AND COALESCE(bill_state,'') <> ''     AND COALESCE(bill_zip,'')  <> ''
    ORDER BY RAND() LIMIT 40
");

// Products that can actually be picked and packed: a SKU to scan, a price to bill, and
// a pack size so the cartonizer has something to work with on the label screen.
$products = Database::select("
    SELECT id, sku, name, COALESCE(price, retail_price, 0) AS price, units_per_case
    FROM products
    WHERE is_active = 1 AND COALESCE(sku,'') <> '' AND COALESCE(price, retail_price, 0) > 0
      AND units_per_case > 0
    ORDER BY RAND() LIMIT 30
");

if (count($customers) < 5 || count($products) < 5) {
    exit("Not enough customers or priced products to build from.\n");
}

printf("Building %d orders from %d customers and %d products.\n\n", $howMany, count($customers), count($products));

// ------------------------------------------------------------------ the plan

mt_srand(20261023);   // same orders every run, so a second run is comparable

$repo  = new SalesOrderRepository();
$plan  = [];
$today = new DateTimeImmutable('today');

for ($i = 0; $i < $howMany; $i++) {
    $customer = $customers[array_rand($customers)];
    $lineCount = mt_rand(1, 4);
    $picked = array_rand($products, min($lineCount, count($products)));
    $picked = is_array($picked) ? $picked : [$picked];

    $lines = [];
    foreach ($picked as $pi) {
        $p = $products[$pi];
        $qty = mt_rand(1, 12);
        $lines[] = [
            'product_id'      => (int)$p['id'],
            'quickbooks_item' => $p['sku'],
            'description'     => $p['name'],
            'qty_ordered'     => $qty,
            'uom_id'          => null,
            'unit_price'      => (float)$p['price'],
            'discount_pct'    => 0,
            'taxable'         => 1,
            'line_total'      => round($qty * (float)$p['price'], 2),
        ];
    }

    // A spread of states, so the release queue has something to decide about rather than
    // twenty-five identical rows. roughly: a third untouched, a third part-picked, a
    // third ready to go.
    $stage = $i % 3;

    $plan[] = [
        'customer' => $customer,
        'lines'    => $lines,
        'stage'    => $stage,
        'priority' => [0 => 'normal', 1 => 'normal', 2 => 'normal', 3 => 'high', 4 => 'urgent'][mt_rand(0, 4)],
        'ship_by'  => $today->modify('+' . mt_rand(0, 10) . ' days')->format('Y-m-d'),
        'channel'  => mt_rand(0, 4) === 0 ? 'amazon' : 'direct',
    ];
}

$totalLines = array_sum(array_map(fn($o) => count($o['lines']), $plan));
printf("  %d orders, %d lines, %s of goods\n", count($plan), $totalLines,
    '$' . number_format(array_sum(array_map(
        fn($o) => array_sum(array_column($o['lines'], 'line_total')), $plan)), 2));
printf("  %d untouched, %d part-picked, %d fully picked\n",
    count(array_filter($plan, fn($o) => $o['stage'] === 0)),
    count(array_filter($plan, fn($o) => $o['stage'] === 1)),
    count(array_filter($plan, fn($o) => $o['stage'] === 2)));

if (!$commit) {
    echo "\nNothing written.\n";
    exit(0);
}

// ------------------------------------------------------------------ write it

$userId = (int)(Database::selectOne("SELECT id FROM users WHERE is_active=1 ORDER BY id LIMIT 1")['id'] ?? 1);

$pdo->beginTransaction();

try {
    if ($doStock) {
        seedStock($products, $userId);
    }

    $made = 0;

    foreach ($plan as $o) {
        $c = $o['customer'];
        $subtotal = array_sum(array_column($o['lines'], 'line_total'));

        $soId = $repo->create([
            ':so_number'           => $repo->nextSoNumber(),
            ':customer_id'         => (int)$c['id'],
            ':created_by'          => $userId,
            ':rep_id'              => null,
            ':status'              => 'confirmed',
            ':order_date'          => date('Y-m-d'),
            ':requested_ship_date' => $o['ship_by'],
            ':po_number'           => null,
            ':ship_via_id'         => null,
            ':tax_rate_id'         => null,
            ':customer_message_id' => null,
            ':ship_name'           => $c['company_name'],
            ':ship_address_1'      => $c['bill_address_1'],
            ':ship_address_2'      => null,
            ':ship_city'           => $c['bill_city'],
            ':ship_state'          => $c['bill_state'],
            ':ship_zip'            => $c['bill_zip'],
            ':ship_phone'          => $c['phone'],
            ':subtotal'            => $subtotal,
            ':discount_amount'     => 0,
            ':tax_amount'          => 0,
            ':total_amount'        => $subtotal,
            ':memo'                => SEED_TAG,
            ':internal_notes'      => SEED_TAG,
            ':channel'             => $o['channel'],
            ':channel_reference'   => $o['channel'] === 'amazon'
                ? sprintf('%03d-%07d-%07d', mt_rand(111, 114), mt_rand(0, 9999999), mt_rand(0, 9999999))
                : null,
            ':marketplace_tax_collected' => 0,
            ':marketplace_fee'           => 0,
            ':marketplace_fee_source'    => 'none',
        ]);

        $repo->replaceLineItems($soId, $o['lines']);

        Database::statement("UPDATE sales_orders SET priority = ?, ship_by = ? WHERE id = ?",
            [$o['priority'], $o['ship_by'] . ' 17:00:00', $soId]);

        // Part-picked orders get a believable partial: some lines complete, some short,
        // which is exactly the state the clerk has to make a judgement about.
        if ($o['stage'] > 0) {
            foreach (Database::select("SELECT id, qty_ordered FROM sales_order_line_items WHERE sales_order_id=?", [$soId]) as $li) {
                $full = (float)$li['qty_ordered'];
                $qty  = $o['stage'] === 2 ? $full : (mt_rand(0, 1) ? $full : floor($full / 2));

                if ($qty > 0) {
                    Database::statement("UPDATE sales_order_line_items SET qty_picked=? WHERE id=?", [$qty, $li['id']]);
                }
            }
            $repo->refreshPickStatus($soId, $userId);
        }

        $made++;
    }

    $pdo->commit();
    printf("\nCreated %d orders. Remove them with:  php database/seed_test_orders.php --remove --commit\n", $made);
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}


/**
 * Put a deliberate spread of stock on the shelf.
 *
 * Plenty of some, enough of others, not enough of a few — because an all-short screen
 * and an all-green screen both prove nothing. The shortage is the case worth looking at.
 */
function seedStock(array $products, int $userId): void
{
    $stock     = new StockRepository();
    $locations = $stock->storableLocations();

    if ($locations === []) {
        echo "  no storable locations — skipping stock\n";
        return;
    }

    $loc = (int)$locations[0]['id'];
    $n   = 0;

    foreach ($products as $i => $p) {
        // every third product deliberately left thin, so some orders cannot be filled
        $qty = match ($i % 3) {
            0 => mt_rand(80, 200),
            1 => mt_rand(20, 60),
            default => mt_rand(0, 6),
        };

        if ($qty > 0) {
            $stock->adjustAt((int)$p['id'], $loc, (float)$qty, [
                'reason_code' => 'found',
                'notes'       => SEED_TAG,
                'user_id'     => $userId,
            ]);
            $n++;
        }
    }

    printf("  stocked %d products at %s\n", $n, $locations[0]['code'] ?? $loc);
}

function removeSeed(PDO $pdo, bool $commit): void
{
    $orders = Database::select(
        "SELECT id, so_number FROM sales_orders WHERE internal_notes = ?", [SEED_TAG]
    );

    printf("%d seeded order(s) found.\n", count($orders));

    if ($orders === []) {
        return;
    }

    // Anything that has been invoiced is left alone. Once an invoice exists the order is
    // not purely test scaffolding any more - it has stock movements and an A/R figure
    // behind it, and tearing it out would leave both inconsistent.
    $ids  = array_column($orders, 'id');
    $hole = implode(',', array_fill(0, count($ids), '?'));
    $invoiced = array_column(Database::select(
        "SELECT DISTINCT sales_order_id FROM invoices WHERE sales_order_id IN ({$hole})", $ids
    ), 'sales_order_id');

    $safe = array_values(array_diff($ids, $invoiced));

    if ($invoiced !== []) {
        printf("  %d have been invoiced and will be LEFT ALONE — they have stock and A/R behind them.\n",
            count($invoiced));
    }

    printf("  %d will be removed.\n", count($safe));

    if (!$commit || $safe === []) {
        echo $commit ? "" : "\nDRY RUN — add --commit to remove them.\n";
        return;
    }

    $hole2 = implode(',', array_fill(0, count($safe), '?'));
    $pdo->beginTransaction();
    try {
        Database::statement("DELETE FROM sales_order_line_items WHERE sales_order_id IN ({$hole2})", $safe);
        Database::statement("DELETE FROM sales_orders WHERE id IN ({$hole2})", $safe);
        $pdo->commit();
        printf("Removed %d.\n", count($safe));
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
