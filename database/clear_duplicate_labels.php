<?php

declare(strict_types=1);

/**
 * Remove repeat label batches left by the old Create button.
 *
 *   php database/clear_duplicate_labels.php
 *   php database/clear_duplicate_labels.php --commit
 *
 * The label form used to stay open and identical after a successful run, so a retry or
 * a double click produced a second complete set of labels for the same boxes. One test
 * order ended with eighteen tracking numbers for nine parcels.
 *
 * Labels made in one call share a created_at second, which is what makes a batch
 * identifiable. The FIRST batch on an invoice is kept — that is the shipment; the ones
 * after it are the accident. Their PDFs are deleted with them, because a label file
 * nothing points at is just something to trip over later.
 *
 * Nothing is touched on an invoice with a single batch, however many parcels are in it.
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
    SELECT t.id, t.invoice_id, t.tracking_number, t.label_path, t.created_at,
           i.invoice_number, c.company_name
    FROM shipment_tracking t
    JOIN invoices i  ON i.id = t.invoice_id
    JOIN customers c ON c.id = i.customer_id
    ORDER BY t.invoice_id, t.created_at, t.id
");

$batches = [];

foreach ($rows as $r) {
    $batches[(int)$r['invoice_id']][(string)$r['created_at']][] = $r;
}

$doomed = [];

foreach ($batches as $invoiceId => $byTime) {
    if (count($byTime) < 2) {
        continue;   // one batch is one shipment, whatever its size
    }

    $first = true;

    foreach ($byTime as $when => $rowsAt) {
        if ($first) {
            $first = false;   // the shipment
            continue;
        }

        foreach ($rowsAt as $r) {
            $doomed[] = $r;
        }
    }
}

if ($doomed === []) {
    echo "No repeat batches. Nothing to do.\n";
    exit(0);
}

$byInvoice = [];

foreach ($doomed as $r) {
    $byInvoice[$r['invoice_number']][] = $r;
}

foreach ($byInvoice as $invoiceNumber => $rowsFor) {
    $kept = count($batches[(int)$rowsFor[0]['invoice_id']][array_key_first($batches[(int)$rowsFor[0]['invoice_id']])]);

    printf(
        "Invoice %-8s %-28s keeping %d, removing %d\n",
        $invoiceNumber,
        substr((string)$rowsFor[0]['company_name'], 0, 28),
        $kept,
        count($rowsFor)
    );

    foreach ($rowsFor as $r) {
        printf("    %-16s %s\n", $r['tracking_number'], $r['created_at']);
    }
}

printf("\n%d tracking number(s) across %d invoice(s).\n", count($doomed), count($byInvoice));

if (!$commit) {
    echo "\nNothing written. Re-run with --commit to apply.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

$files = 0;

try {
    $del = $pdo->prepare("DELETE FROM shipment_tracking WHERE id = ?");

    foreach ($doomed as $r) {
        $del->execute([(int)$r['id']]);

        // Only under the labels directory, and never through a '..'. These paths come
        // out of the database, which is not the same as being safe to unlink.
        $path = (string)($r['label_path'] ?? '');

        if ($path === '' || !str_starts_with($path, '/uploads/labels/') || str_contains($path, '..')) {
            continue;
        }

        $full = realpath(BASE_PATH . '/public' . $path);
        $root = realpath(BASE_PATH . '/public/uploads/labels');

        if ($full !== false && $root !== false && str_starts_with($full, $root) && is_file($full)) {
            @unlink($full);
            $files++;
        }
    }

    $pdo->commit();
    printf("\nDone. %d tracking number(s) removed, %d label file(s) deleted.\n", count($doomed), $files);
} catch (\Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "\nRolled back: " . $e->getMessage() . "\n");
    exit(1);
}
