<?php

declare(strict_types=1);

/**
 * Website checkouts Square may have been paid for without USSCOS hearing about it.
 *
 *   php database/reconcile_web_checkouts.php            dry run: what Square says about each
 *   php database/reconcile_web_checkouts.php --commit   make the sales orders for the paid ones
 *
 * A paid order normally arrives twice over: the customer lands back on /checkout/thanks,
 * and Square's webhook says so. If both are missed - the customer closed the tab, the
 * webhook was down - this finds it. Safe to run any number of times: making the order is
 * idempotent (WebOrderService::complete), so an order already made is left alone.
 * Looks at open checkouts between 10 minutes and 7 days old. Meant for cron, every 15 min.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Repositories\WebCheckoutRepository;
use App\Services\Web\SquareClient;
use App\Services\Web\WebOrderService;

$commit = in_array('--commit', array_slice($argv, 1), true);
$square = new SquareClient();

if (!$square->configured()) {
    echo "Square is not configured in .env; nothing to reconcile.\n";
    exit(0);
}

$open = (new WebCheckoutRepository())->openOlderThan(10);
printf("%d open checkout(s) to look at (%s).\n", count($open), $square->environment());

foreach ($open as $c) {
    if (!$commit) {
        try {
            $state = $square->paidState($square->order((string)$c['square_order_id']));
            printf("  %s  $%8.2f  %s\n", $c['token'], $c['total'], $state['paid'] ? 'PAID: would make the order' : 'not paid (' . $state['reason'] . ')');
        } catch (\RuntimeException $e) {
            printf("  %s  could not ask Square\n", $c['token']);
        }
        continue;
    }

    $r = (new WebOrderService())->complete($c);
    printf("  %s  $%8.2f  %s\n", $c['token'], $c['total'], $r['state'] . ($r['so_number'] ? ' → ' . $r['so_number'] : ''));
}

if (!$commit) {
    echo "\nNothing made. Add --commit to make orders for the paid ones.\n";
}
