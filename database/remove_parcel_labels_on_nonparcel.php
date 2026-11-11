<?php

declare(strict_types=1);

/**
 * Delete FedEx labels sitting on orders that are not going by parcel.
 *
 *   php database/remove_parcel_labels_on_nonparcel.php
 *   php database/remove_parcel_labels_on_nonparcel.php --commit
 *
 * The label screen used to offer the parcel form on a freight order — it warned that
 * the order looked like freight and then showed the form underneath the warning. SO
 * #15 collected nine FedEx labels that way, on a shipment that went on a pallet.
 *
 * They are not harmless. Tracking is what retires an order from the shipping queue, so
 * a freight order wearing parcel tracking reads as shipped when nobody has booked it,
 * and the number on it belongs to a parcel that does not exist.
 *
 * Only labels on orders whose carrier is freight, pick-up or our own truck. A parcel
 * order's labels are left alone however many it has — that is what
 * clear_duplicate_labels.php is for.
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

$rows = Database::select("
    SELECT t.id, t.tracking_number, t.label_path,
           s.so_number, c.company_name,
           COALESCE(v.name, 'none') AS carrier,
           COALESCE(v.ship_mode, 'other') AS mode
    FROM shipment_tracking t
    JOIN invoices i      ON i.id = t.invoice_id
    JOIN sales_orders s  ON s.id = i.sales_order_id
    JOIN customers c     ON c.id = s.customer_id
    LEFT JOIN ship_via v ON v.id = s.ship_via_id
    WHERE COALESCE(v.ship_mode, 'other') IN ('freight', 'pickup', 'delivery')
    ORDER BY s.so_number + 0, t.id
");

if ($rows === []) {
    echo "No parcel labels on non-parcel orders. Nothing to do.\n";
    exit(0);
}

$byOrder = [];

foreach ($rows as $r) {
    $byOrder[$r['so_number']][] = $r;
}

foreach ($byOrder as $soNumber => $labels) {
    printf(
        "SO #%-5s %-28s %s (%s) — %d label(s)\n",
        $soNumber,
        substr((string)$labels[0]['company_name'], 0, 28),
        $labels[0]['carrier'],
        $labels[0]['mode'],
        count($labels)
    );

    foreach ($labels as $l) {
        printf("    %s\n", $l['tracking_number']);
    }
}

printf("\n%d label(s) across %d order(s).\n", count($rows), count($byOrder));

if (!$commit) {
    echo "\nThose orders go back to having no tracking, which is what a freight\n";
    echo "shipment looks like until the PRO number is recorded.\n";
    echo "Nothing written. Re-run with --commit to apply.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

$files = 0;

try {
    $del = $pdo->prepare('DELETE FROM shipment_tracking WHERE id = ?');

    foreach ($rows as $r) {
        $del->execute([(int)$r['id']]);

        $path = (string)($r['label_path'] ?? '');

        if ($path === '' || !str_starts_with($path, '/uploads/labels/') || str_contains($path, '..')) {
            continue;
        }

        $full = realpath(BASE_PATH . '/public' . $path);
        $root = realpath(BASE_PATH . '/public/uploads/labels');

        if ($full !== false && $root !== false && str_starts_with($full, $root) && is_file($full)) {
            @unlink($full) and $files++;
        }
    }

    $pdo->commit();
    printf("\nDone. %d label(s) removed, %d file(s) deleted.\n", count($rows), $files);
} catch (\Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "\nRolled back: " . $e->getMessage() . "\n");
    exit(1);
}
