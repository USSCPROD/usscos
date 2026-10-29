<?php

declare(strict_types=1);

/**
 * Promote invoices that were issued but are still marked draft.
 *
 *   php database/issue_draft_invoices.php
 *   php database/issue_draft_invoices.php --commit
 *
 * Every invoice used to be written with status 'draft', and the only thing that moved
 * one to 'pending' was emailing it. An order that shipped but was never emailed left
 * an invoice nobody could pay: Receive Payment offers pending, partial and overdue
 * invoices only, so the page reported "No open invoices" on an unpaid bill.
 *
 * An invoice is treated as issued here when goods are behind it - it has line items
 * and a balance owing. A draft with no lines is someone's unfinished work and is left
 * where it is.
 *
 * Nothing is deleted and no amount changes; only the status moves, and only in the
 * one direction.
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
    SELECT i.id, i.invoice_number, i.invoice_date, i.due_date, i.total_amount, i.balance_due,
           i.sales_order_id, c.company_name,
           (SELECT COUNT(*) FROM invoice_line_items li WHERE li.invoice_id = i.id) AS line_count
    FROM invoices i
    LEFT JOIN customers c ON c.id = i.customer_id
    WHERE i.status = 'draft'
      AND i.invoice_type = 'invoice'
      AND i.balance_due > 0
    HAVING line_count > 0
    ORDER BY i.invoice_date, i.id
");

if ($rows === []) {
    echo "No stranded draft invoices. Nothing to do.\n";
    exit(0);
}

// All of them become 'pending': issued and unpaid. Not 'overdue', even where the due
// date has passed - nothing else in the application ever promotes an invoice to
// overdue, so doing it here would brand these few and leave every later one alone,
// and the aging report works off due_date rather than this column anyway.
$today   = date('Y-m-d');
$late    = 0;

printf("%-10s %-32s %-12s %-12s %10s  %s\n", 'INVOICE', 'CUSTOMER', 'DATE', 'DUE', 'BALANCE', 'PAST DUE');

foreach ($rows as $r) {
    $isLate = $r['due_date'] !== null && $r['due_date'] < $today;
    $isLate and $late++;

    printf(
        "%-10s %-32s %-12s %-12s %10s  %s\n",
        $r['invoice_number'],
        substr((string)($r['company_name'] ?? '—'), 0, 32),
        (string)$r['invoice_date'],
        (string)($r['due_date'] ?? '—'),
        number_format((float)$r['balance_due'], 2),
        $isLate ? 'yes' : ''
    );
}

printf("\n%d invoice(s) to mark pending; %d already past due.\n", count($rows), $late);

if (!$commit) {
    echo "\nNothing written. Re-run with --commit to apply.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("UPDATE invoices SET status = 'pending' WHERE id = ? AND status = 'draft'");

    foreach ($rows as $r) {
        $stmt->execute([(int)$r['id']]);
    }

    $pdo->commit();
    printf("\nDone. %d invoice(s) issued.\n", count($rows));
} catch (\Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "\nRolled back: " . $e->getMessage() . "\n");
    exit(1);
}
