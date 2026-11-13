<?php

declare(strict_types=1);

/**
 * Put every Amazon order at urgent, where the rule now says they belong.
 *
 *   php database/amazon_orders_urgent.php            dry run
 *   php database/amazon_orders_urgent.php --commit   writes it
 *
 * An Amazon order ships immediately, so urgent is what the channel means rather than
 * something somebody decides. New orders get it on creation and editing cannot take
 * it away; this is for the ones that arrived before the rule existed.
 *
 * Touches only `priority`, only on `channel = 'amazon'`, and only where it is not
 * already urgent.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;

$commit = in_array('--commit', array_slice($argv, 1), true);

$rows = Database::select(
    "SELECT id, so_number, priority, status, channel_reference
       FROM sales_orders
      WHERE channel = 'amazon' AND priority <> 'urgent'
      ORDER BY id"
);

if ($rows === []) {
    echo "Every Amazon order is already urgent. Nothing to do.\n";
    exit(0);
}

printf("%-8s %-10s %-12s %s\n", 'ORDER', 'NOW', 'STATUS', 'AMAZON REF');
echo str_repeat('-', 60), "\n";

foreach ($rows as $r) {
    printf("%-8s %-10s %-12s %s\n", '#' . $r['so_number'], $r['priority'], $r['status'],
        $r['channel_reference'] ?: '—');
}

echo str_repeat('-', 60), "\n";
printf("%d order(s) would become urgent.\n", count($rows));

if (!$commit) {
    echo "\nDry run. Add --commit to write it.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    Database::statement("UPDATE sales_orders SET priority = 'urgent' WHERE channel = 'amazon'");
    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Rolled back: " . $e->getMessage() . "\n");
    exit(1);
}

echo "\nAfter:\n";

foreach (Database::select("SELECT so_number, priority FROM sales_orders WHERE channel = 'amazon' ORDER BY id") as $r) {
    printf("  #%-6s %s\n", $r['so_number'], $r['priority']);
}
