<?php

declare(strict_types=1);

/**
 * Put the walkthrough lead back to the beginning.
 *
 *   php database/reset_demo_lead.php            dry run
 *   php database/reset_demo_lead.php --commit   does it
 *
 * Walking lead → quote → customer → sales order leaves the lead converted and a
 * customer behind it, so the next person to walk it starts halfway through. This
 * winds it back: the lead is new again, the quote is a draft on the lead, and the
 * customer the conversion created is removed.
 *
 * It refuses to delete the customer if anything real hangs off it — an invoice, a
 * payment, a sales order it does not know about. Rewinding a demo must never be a
 * way to lose a record that matters.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;

const LEAD_ID = 123;

$commit = in_array('--commit', array_slice($argv, 1), true);

$lead = Database::selectOne('SELECT * FROM leads WHERE id = ?', [LEAD_ID]);

if ($lead === false) {
    fwrite(STDERR, "Lead " . LEAD_ID . " is gone. Run seed_demo_lead.php first.\n");
    exit(1);
}

$customerId = $lead['customer_id'] !== null ? (int)$lead['customer_id'] : null;
$quotes     = Database::select('SELECT id, quote_number, status, sales_order_id FROM quotes WHERE lead_id = ?', [LEAD_ID]);

echo "Lead " . LEAD_ID . " — {$lead['company_name']}\n";
echo "  status            {$lead['status']}\n";
echo "  customer_id       " . var_export($customerId, true) . "\n";

foreach ($quotes as $q) {
    echo "  quote {$q['quote_number']}  status {$q['status']}  sales_order_id "
       . var_export($q['sales_order_id'], true) . "\n";
}

$blockers = [];
$orders   = [];

if ($customerId !== null) {
    foreach ([
        'invoices'     => 'SELECT COUNT(*) n FROM invoices WHERE customer_id = ?',
        'payments'     => 'SELECT COUNT(*) n FROM payments WHERE customer_id = ?',
    ] as $what => $sql) {
        $n = (int)Database::selectOne($sql, [$customerId])['n'];
        if ($n > 0) {
            $blockers[] = "{$n} {$what}";
        }
    }

    $orders = Database::select(
        'SELECT id, so_number, status FROM sales_orders WHERE customer_id = ?',
        [$customerId]
    );

    foreach ($orders as $o) {
        if (!in_array($o['status'], ['draft', 'confirmed'], true)) {
            $blockers[] = "SO #{$o['so_number']} is {$o['status']}";
        }
    }

    echo "\nCustomer {$customerId} has " . count($orders) . " sales order(s)\n";
}

if ($blockers !== []) {
    echo "\nRefusing: " . implode('; ', $blockers) . ".\n";
    echo "Something real is hanging off this customer. Deal with it by hand, or seed a\n";
    echo "second lead instead with seed_demo_lead.php.\n";
    exit(1);
}

echo "\nWould:\n";
echo "  - put the lead back to 'new' and clear its customer_id\n";
foreach ($quotes as $q) {
    echo "  - put quote {$q['quote_number']} back to draft, off the customer, on the lead\n";
}
foreach ($orders as $o) {
    echo "  - delete SO #{$o['so_number']} ({$o['status']}) and its lines\n";
}
if ($customerId !== null) {
    echo "  - delete customer {$customerId}\n";
}

if (!$commit) {
    echo "\nDry run. Add --commit to do it.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    foreach ($orders as $o) {
        Database::statement('DELETE FROM sales_order_line_items WHERE sales_order_id = ?', [(int)$o['id']]);
        Database::statement('DELETE FROM sales_orders WHERE id = ?', [(int)$o['id']]);
    }

    Database::statement(
        "UPDATE quotes SET status = 'draft', customer_id = NULL, sales_order_id = NULL
          WHERE lead_id = ?",
        [LEAD_ID]
    );

    Database::statement(
        "UPDATE opportunities SET customer_id = NULL WHERE lead_id = ?",
        [LEAD_ID]
    );

    Database::statement(
        "UPDATE leads SET status = 'new', customer_id = NULL WHERE id = ?",
        [LEAD_ID]
    );

    if ($customerId !== null) {
        Database::statement('DELETE FROM customer_notes WHERE customer_id = ?', [$customerId]);
        Database::statement('DELETE FROM customers WHERE id = ?', [$customerId]);
    }

    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "\nRolled back: " . $e->getMessage() . "\n");
    exit(1);
}

$after = Database::selectOne('SELECT status, customer_id FROM leads WHERE id = ?', [LEAD_ID]);
echo "\nLead " . LEAD_ID . " is {$after['status']} again, customer_id "
   . var_export($after['customer_id'], true) . ".\n";

foreach (Database::select('SELECT quote_number, status, customer_id FROM quotes WHERE lead_id = ?', [LEAD_ID]) as $q) {
    echo "Quote {$q['quote_number']} is {$q['status']}, customer "
       . var_export($q['customer_id'], true) . ".\n";
}
