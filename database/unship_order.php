<?php

declare(strict_types=1);

/**
 * Put a shipped order back the way it was, for testing.
 *
 *   php database/unship_order.php --so=2
 *   php database/unship_order.php --so=2 --commit
 *   php database/unship_order.php --all-queued --commit
 *
 * TESTING ONLY. This exists because walking the flow needs orders to walk, and the
 * seeded ones arrived already invoiced — so the interesting half of the journey had
 * nothing to travel through it. It is not a business operation and there is no screen
 * for it: an invoice raised in anger is a document somebody was sent, and unpicking
 * one is a credit memo, not a delete.
 *
 * What it undoes, in the order it has to happen:
 *
 *   1. stock is put back where it came from, as a return_in movement rather than by
 *      deleting the sale — the count stays explainable, and the history says a reversal
 *      happened rather than quietly losing a line;
 *   2. labels and tracking are removed, with their PDFs;
 *   3. invoice lines and the invoices themselves are deleted;
 *   4. qty_shipped goes back to zero on the order;
 *   5. the order returns to 'confirmed'.
 *
 * Picking and packing are LEFT ALONE. They still happened, the goods are still on the
 * bench, and re-walking from "ready to invoice" is the point.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;

$args   = array_slice($argv, 1);
$commit = in_array('--commit', $args, true);
$all    = in_array('--all-queued', $args, true);
$soArg  = null;

foreach ($args as $a) {
    if (str_starts_with($a, '--so=')) {
        $soArg = substr($a, 5);
    }
}

if ($soArg === null && !$all) {
    fwrite(STDERR, "Give --so=<number> or --all-queued.\n");
    exit(1);
}

if (!$commit) {
    echo "DRY RUN — nothing will be written. Add --commit to apply.\n\n";
}

$orders = $all
    ? Database::select("
        SELECT s.id, s.so_number, c.company_name
        FROM sales_orders s JOIN customers c ON c.id = s.customer_id
        WHERE s.status = 'invoiced'
          AND NOT EXISTS (SELECT 1 FROM shipment_tracking t
                          JOIN invoices i ON i.id = t.invoice_id
                          WHERE i.sales_order_id = s.id)
        ORDER BY s.id
      ")
    : Database::select("
        SELECT s.id, s.so_number, c.company_name
        FROM sales_orders s JOIN customers c ON c.id = s.customer_id
        WHERE s.so_number = ?
      ", [$soArg]);

if ($orders === []) {
    echo "Nothing matches. (--all-queued skips orders that already have a label.)\n";
    exit(0);
}

$stock = new App\Repositories\StockRepository();
$plan  = [];

foreach ($orders as $o) {
    $soId = (int)$o['id'];

    $invoices = Database::select('SELECT id, invoice_number, total_amount FROM invoices WHERE sales_order_id = ?', [$soId]);
    $moves    = Database::select("
        SELECT it.id, it.product_id, it.qty, it.from_location_id, p.sku
        FROM inventory_transactions it
        JOIN invoices i ON i.id = it.reference_id AND it.reference_type = 'invoice'
        LEFT JOIN products p ON p.id = it.product_id
        WHERE i.sales_order_id = ? AND it.qty < 0
    ", [$soId]);
    $tracking = Database::select("
        SELECT t.id, t.tracking_number, t.label_path
        FROM shipment_tracking t JOIN invoices i ON i.id = t.invoice_id
        WHERE i.sales_order_id = ?
    ", [$soId]);

    printf(
        "SO #%-5s %-28s %d invoice(s), %d stock move(s), %d label(s)\n",
        $o['so_number'],
        substr((string)$o['company_name'], 0, 28),
        count($invoices),
        count($moves),
        count($tracking)
    );

    foreach ($moves as $m) {
        printf("      put back %-14s %s\n", $m['sku'] ?? ('product ' . $m['product_id']), rtrim(rtrim(number_format(abs((float)$m['qty']), 2), '0'), '.'));
    }

    $plan[] = ['so' => $o, 'invoices' => $invoices, 'moves' => $moves, 'tracking' => $tracking];
}

if (!$commit) {
    echo "\nNothing written. Re-run with --commit to apply.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    foreach ($plan as $p) {
        $soId = (int)$p['so']['id'];

        // 1. Stock back where it came from.
        foreach ($p['moves'] as $m) {
            if (empty($m['from_location_id'])) {
                continue;
            }

            $stock->applyMovement([
                'product_id'     => (int)$m['product_id'],
                'qty'            => abs((float)$m['qty']),
                'type'           => 'return_in',
                'to_location_id' => (int)$m['from_location_id'],
                'reference_type' => 'sales_order',
                'reference_id'   => $soId,
                'reference_num'  => (string)$p['so']['so_number'],
                'notes'          => 'Order un-shipped for testing',
            ]);
        }

        // 2. Labels, and the files with them.
        foreach ($p['tracking'] as $t) {
            $path = (string)($t['label_path'] ?? '');

            if ($path !== '' && str_starts_with($path, '/uploads/labels/') && !str_contains($path, '..')) {
                $full = realpath(BASE_PATH . '/public' . $path);
                $root = realpath(BASE_PATH . '/public/uploads/labels');

                if ($full !== false && $root !== false && str_starts_with($full, $root)) {
                    @unlink($full);
                }
            }

            $pdo->prepare('DELETE FROM shipment_tracking WHERE id = ?')->execute([(int)$t['id']]);
        }

        // 3. Invoices.
        foreach ($p['invoices'] as $inv) {
            $pdo->prepare('DELETE FROM invoice_line_items WHERE invoice_id = ?')->execute([(int)$inv['id']]);
            $pdo->prepare('DELETE FROM invoices WHERE id = ?')->execute([(int)$inv['id']]);
        }

        // 4. Nothing has shipped any more.
        $pdo->prepare('UPDATE sales_order_line_items SET qty_shipped = 0 WHERE sales_order_id = ?')->execute([$soId]);

        // 5. Back in front of the clerk, still picked and still packed.
        $pdo->prepare("UPDATE sales_orders SET status = 'confirmed' WHERE id = ?")->execute([$soId]);
    }

    $pdo->commit();
    printf("\nDone. %d order(s) put back.\n", count($plan));
} catch (\Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "\nRolled back: " . $e->getMessage() . "\n");
    exit(1);
}
