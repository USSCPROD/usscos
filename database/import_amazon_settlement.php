<?php

declare(strict_types=1);

/**
 * Load an Amazon settlement report.
 *
 *   php database/import_amazon_settlement.php                 dry run, the default
 *   php database/import_amazon_settlement.php --commit
 *   php database/import_amazon_settlement.php --commit --file=path/to/report.txt
 *
 * Reads whatever settlement reports are in database/amazon/. Get one from Seller Central:
 * Payments, All Statements, pick a completed period, Download Flat File V2. It is
 * tab-separated despite the extension.
 *
 * WHAT THIS IS FOR. Amazon deposits the net and the accountant books that figure, so
 * nothing in USSCOS has ever known what Amazon kept. With Amazon at 28 percent of all
 * invoices, that means margin on the biggest selling products has been reported as if
 * USSC received the gross. This file is where the real number comes from.
 *
 * THE RECONCILIATION IS THE POINT. The lines must add up to the deposit Amazon says it
 * made. If they do not, something was dropped or misread, and a fee figure nobody can
 * tie back to the bank is not worth having. The importer refuses to report success
 * without that check passing.
 *
 * Dry run by default, per the rule for anything that changes data in bulk.
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;

$args   = array_slice($argv, 1);
$commit = in_array('--commit', $args, true);
$files  = [];

foreach ($args as $arg) {
    if (str_starts_with($arg, '--file=')) {
        $files[] = substr($arg, 7);
    }
}

if ($files === []) {
    $dir   = BASE_PATH . '/database/amazon';
    $files = array_merge(glob($dir . '/*settlement*.txt') ?: [], glob($dir . '/*settlement*.csv') ?: []);
}

if ($files === []) {
    exit("No settlement reports found.\n\n"
       . "  Put them in database/amazon/ with 'settlement' in the filename, or pass --file=\n"
       . "  Seller Central: Payments, All Statements, Download Flat File V2\n");
}

if (!$commit) {
    echo "DRY RUN — nothing will be written. Add --commit to apply.\n\n";
}

$pdo = Database::connection();

foreach ($files as $path) {
    importSettlement($path, $commit, $pdo);
}


function importSettlement(string $path, bool $commit, PDO $pdo): void
{
    if (!is_readable($path)) {
        echo "  cannot read {$path}\n";
        return;
    }

    $fh     = fopen($path, 'r');
    $header = fgetcsv($fh, 0, "\t");

    if (!$header || !in_array('settlement-id', $header, true)) {
        echo "  " . basename($path) . " does not look like a settlement report — skipped\n";
        fclose($fh);
        return;
    }

    $col = array_flip($header);
    $get = fn(array $r, string $name): string => trim((string)($r[$col[$name] ?? -1] ?? ''));

    $summary = null;
    $lines   = [];

    while (($r = fgetcsv($fh, 0, "\t")) !== false) {
        if ($get($r, 'settlement-id') === '') {
            continue;
        }

        // The first row carries the period and the deposit and nothing else. Every other
        // row repeats the settlement id and leaves those columns empty.
        if ($summary === null && $get($r, 'deposit-date') !== '') {
            $summary = [
                'settlement_id'  => $get($r, 'settlement-id'),
                'period_start'   => parseDate($get($r, 'settlement-start-date')),
                'period_end'     => parseDate($get($r, 'settlement-end-date')),
                'deposit_date'   => parseDate($get($r, 'deposit-date')),
                'deposit_amount' => (float)$get($r, 'total-amount'),
                'currency'       => $get($r, 'currency') ?: 'USD',
            ];
            continue;
        }

        $amount = $get($r, 'amount');
        if ($amount === '') {
            continue;
        }

        $lines[] = [
            'transaction_type'   => $get($r, 'transaction-type'),
            'order_id'           => $get($r, 'order-id') ?: null,
            'order_item_code'    => $get($r, 'order-item-code') ?: null,
            'adjustment_id'      => $get($r, 'adjustment-id') ?: null,
            'shipment_id'        => $get($r, 'shipment-id') ?: null,
            'sku'                => $get($r, 'sku') ?: null,
            'quantity'           => $get($r, 'quantity-purchased') !== '' ? (int)$get($r, 'quantity-purchased') : null,
            'amount_type'        => $get($r, 'amount-type') ?: null,
            'amount_description' => $get($r, 'amount-description') ?: null,
            'amount'             => round((float)$amount, 2),
            'posted_date'        => parseDate($get($r, 'posted-date-time') ?: $get($r, 'posted-date')),
        ];
    }

    fclose($fh);

    if ($summary === null) {
        echo "  " . basename($path) . " has no settlement summary row — skipped\n";
        return;
    }

    $t = totals($lines);

    printf("\n%s\n", basename($path));
    printf("  settlement %s   %s to %s   deposited %s\n",
        $summary['settlement_id'],
        substr((string)$summary['period_start'], 0, 10),
        substr((string)$summary['period_end'], 0, 10),
        substr((string)$summary['deposit_date'], 0, 10));
    printf("  %-26s %14s\n", 'gross sales',        money($t['gross']));
    printf("  %-26s %14s\n", 'tax Amazon charged', money($t['tax']));
    printf("  %-26s %14s\n", 'tax Amazon withheld', money($t['withheld']));
    printf("  %-26s %14s   %s of gross\n", 'fees Amazon kept', money($t['fees']),
        $t['gross'] > 0 ? number_format(abs($t['fees']) / $t['gross'] * 100, 1) . '%' : '-');
    printf("  %-26s %14s\n", 'refunds',            money($t['refunds']));
    printf("  %-26s %14s\n", 'advertising and other', money($t['other']));
    printf("  %-26s %14s\n", 'computed net',       money($t['net']));
    printf("  %-26s %14s\n", 'Amazon says deposited', money($summary['deposit_amount']));

    $drift = round($t['net'] - $summary['deposit_amount'], 2);

    if (abs($drift) > 0.01) {
        printf("\n  *** OUT BY %s — NOT IMPORTED ***\n", money($drift));
        echo   "  The lines do not add up to the deposit, so something was dropped or\n"
             . "  misread. A fee figure that cannot be tied back to the bank is not worth\n"
             . "  having. Fix the parse before loading this file.\n";
        return;
    }

    printf("  reconciles exactly%s\n", $commit ? '' : ' (dry run)');
    printf("  %d lines, %d of them order items\n", count($lines),
        count(array_filter($lines, fn($l) => $l['order_id'] !== null)));

    // Which Amazon skus we cannot resolve. Reported every run, because an unmapped sku is
    // a product whose true margin is still unknown.
    $skus = array_values(array_unique(array_filter(array_column($lines, 'sku'))));
    $unmapped = unmappedSkus($skus);

    if ($unmapped !== []) {
        printf("\n  %d of %d Amazon SKUs are not mapped to a USSCOS product:\n",
            count($unmapped), count($skus));
        foreach (array_slice($unmapped, 0, 15) as $s) {
            echo "    {$s}\n";
        }
        if (count($unmapped) > 15) {
            printf("    … and %d more\n", count($unmapped) - 15);
        }
        echo "  Fees still load. Per-product margin stays unknown until these are mapped.\n";
    }

    if (!$commit) {
        return;
    }

    $pdo->beginTransaction();
    try {
        Database::statement("
            INSERT INTO marketplace_settlements
                (marketplace, settlement_id, period_start, period_end, deposit_date,
                 deposit_amount, currency, gross_sales, fees_total, tax_collected,
                 tax_withheld, refunds_total, other_total, computed_net, line_count, source_file)
            VALUES ('amazon', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                period_start=VALUES(period_start), period_end=VALUES(period_end),
                deposit_date=VALUES(deposit_date), deposit_amount=VALUES(deposit_amount),
                gross_sales=VALUES(gross_sales), fees_total=VALUES(fees_total),
                tax_collected=VALUES(tax_collected), tax_withheld=VALUES(tax_withheld),
                refunds_total=VALUES(refunds_total), other_total=VALUES(other_total),
                computed_net=VALUES(computed_net), line_count=VALUES(line_count),
                source_file=VALUES(source_file), imported_at=NOW()
        ", [
            $summary['settlement_id'], $summary['period_start'], $summary['period_end'],
            $summary['deposit_date'], $summary['deposit_amount'], $summary['currency'],
            $t['gross'], $t['fees'], $t['tax'], $t['withheld'], $t['refunds'],
            $t['other'], $t['net'], count($lines), basename($path),
        ]);

        $id = (int)(Database::selectOne(
            "SELECT id FROM marketplace_settlements WHERE marketplace='amazon' AND settlement_id=?",
            [$summary['settlement_id']]
        )['id'] ?? 0);

        // Re-importing the same settlement replaces its lines rather than doubling them.
        Database::statement("DELETE FROM marketplace_settlement_lines WHERE settlement_id = ?", [$id]);

        $stmt = $pdo->prepare("
            INSERT INTO marketplace_settlement_lines
                (settlement_id, transaction_type, order_id, order_item_code, adjustment_id,
                 shipment_id, sku, quantity, amount_type, amount_description, amount,
                 posted_date, product_id)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");

        foreach ($lines as $l) {
            $stmt->execute([
                $id, $l['transaction_type'], $l['order_id'], $l['order_item_code'],
                $l['adjustment_id'], $l['shipment_id'], $l['sku'], $l['quantity'],
                $l['amount_type'], $l['amount_description'], $l['amount'],
                $l['posted_date'], resolveProduct($l['sku']),
            ]);
        }

        $pdo->commit();
        printf("  imported.\n");
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}


/**
 * Split the lines the way the money actually behaves.
 *
 * Classified by what Amazon called them rather than by sign, so a positive Commission on
 * a refund stays a fee rather than being mistaken for revenue.
 */
function totals(array $lines): array
{
    $t = ['gross'=>0.0,'tax'=>0.0,'withheld'=>0.0,'fees'=>0.0,'refunds'=>0.0,'other'=>0.0,'net'=>0.0];

    foreach ($lines as $l) {
        $amount = $l['amount'];
        $type   = (string)$l['amount_type'];
        $desc   = (string)$l['amount_description'];
        $isRefund = $l['transaction_type'] === 'Refund';

        $t['net'] += $amount;

        if ($isRefund) {
            $t['refunds'] += $amount;
            continue;
        }

        if ($type === 'ItemPrice' && in_array($desc, ['Principal', 'Shipping'], true)) {
            $t['gross'] += $amount;
        } elseif ($type === 'ItemPrice' && in_array($desc, ['Tax', 'ShippingTax'], true)) {
            $t['tax'] += $amount;
        } elseif ($type === 'ItemWithheldTax') {
            $t['withheld'] += $amount;
        } elseif ($type === 'ItemFees') {
            $t['fees'] += $amount;
        } else {
            $t['other'] += $amount;
        }
    }

    return array_map(fn($v) => round($v, 2), $t);
}

function unmappedSkus(array $skus): array
{
    if ($skus === []) {
        return [];
    }

    $holes = implode(',', array_fill(0, count($skus), '?'));
    $known = array_column(Database::select("
        SELECT marketplace_sku FROM marketplace_sku_map
        WHERE marketplace = 'amazon' AND product_id IS NOT NULL
          AND marketplace_sku IN ({$holes})
    ", $skus), 'marketplace_sku');

    return array_values(array_diff($skus, $known));
}

function resolveProduct(?string $sku): ?int
{
    static $cache = [];

    if ($sku === null) {
        return null;
    }
    if (array_key_exists($sku, $cache)) {
        return $cache[$sku];
    }

    $row = Database::selectOne(
        "SELECT product_id FROM marketplace_sku_map WHERE marketplace='amazon' AND marketplace_sku=?",
        [$sku]
    );

    return $cache[$sku] = $row === false ? null : ($row['product_id'] !== null ? (int)$row['product_id'] : null);
}

function parseDate(string $raw): ?string
{
    if ($raw === '') {
        return null;
    }
    $ts = strtotime($raw);

    return $ts === false ? null : date('Y-m-d H:i:s', $ts);
}

function money(float $v): string
{
    return ($v < 0 ? '-' : '') . '$' . number_format(abs($v), 2);
}
