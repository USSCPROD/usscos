<?php

declare(strict_types=1);

/**
 * Take people out of the rep list who are not reps.
 *
 *   php database/retire_sales_reps.php
 *   php database/retire_sales_reps.php --commit
 *
 * The rep list comes across from QuickBooks, where "sales rep" is recorded against
 * accounts for reasons that do not all survive the move. Larry Fitzpatrick is sales
 * staff and not a rep; QuickBooks has him on 36 accounts anyway.
 *
 * Written as a re-runnable list rather than a one-off fix, because the real import will
 * bring every one of them back. Run it again afterwards.
 *
 * Two things happen to each name:
 *   - the sales_reps row is DEACTIVATED, not deleted, so historical orders and invoices
 *     that reference it still read correctly;
 *   - their customers are UNASSIGNED, so those accounts are free to be given to a real
 *     rep rather than pointing at somebody who cannot act on them.
 *
 * The accounts are listed before anything is written. 36 customer relationships is not
 * a number to change without reading it first.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;

/**
 * Names that must never be in the rep list, however they arrive.
 *
 * Matched on the sales_reps.name as QuickBooks spells it.
 */
const NOT_REPS = [
    'Larry Fitzpatrick',   // sales staff, not a rep — Chip, 10 November 2026
];

$commit = in_array('--commit', array_slice($argv, 1), true);

if (!$commit) {
    echo "DRY RUN — nothing will be written. Add --commit to apply.\n\n";
}

$placeholders = implode(',', array_fill(0, count(NOT_REPS), '?'));

$reps = Database::select("
    SELECT r.id, r.name, r.is_active,
           (SELECT COUNT(*) FROM customers c WHERE c.sales_rep_id = r.id) AS customers
    FROM sales_reps r
    WHERE r.name IN ({$placeholders})
    ORDER BY r.name
", NOT_REPS);

if ($reps === []) {
    echo "Nobody on the list is in sales_reps. Nothing to do.\n";
    exit(0);
}

$work = false;

foreach ($reps as $rep) {
    printf(
        "%s — rep #%d, currently %s, %d customer(s)\n",
        $rep['name'],
        (int)$rep['id'],
        $rep['is_active'] ? 'ACTIVE' : 'already retired',
        (int)$rep['customers']
    );

    if (!$rep['is_active'] && (int)$rep['customers'] === 0) {
        continue;
    }

    $work = true;

    foreach (Database::select(
        'SELECT id, company_name FROM customers WHERE sales_rep_id = ? ORDER BY company_name',
        [(int)$rep['id']]
    ) as $c) {
        printf("    %-8s %s\n", $c['id'], $c['company_name']);
    }
}

if (!$work) {
    echo "\nAlready done. Nothing to change.\n";
    exit(0);
}

if (!$commit) {
    echo "\nThose customers would be left with no rep, and the rep record deactivated.\n";
    echo "Nothing written. Re-run with --commit to apply.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    $clear   = $pdo->prepare('UPDATE customers SET sales_rep_id = NULL WHERE sales_rep_id = ?');
    $retire  = $pdo->prepare('UPDATE sales_reps SET is_active = 0 WHERE id = ?');
    $cleared = 0;

    foreach ($reps as $rep) {
        $clear->execute([(int)$rep['id']]);
        $cleared += $clear->rowCount();
        $retire->execute([(int)$rep['id']]);
    }

    $pdo->commit();
    printf("\nDone. %d rep(s) retired, %d customer(s) left unassigned.\n", count($reps), $cleared);
} catch (\Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "\nRolled back: " . $e->getMessage() . "\n");
    exit(1);
}
