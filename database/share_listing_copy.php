<?php

declare(strict_types=1);

/**
 * Push listing copy out to every colour in a variant group.
 *
 *   php database/share_listing_copy.php
 *   php database/share_listing_copy.php --commit
 *   php database/share_listing_copy.php --group=DURASTRIPE-TTIP --commit
 *
 * Saving a product has shared its listing copy with its siblings since the sharing
 * code went in, but anything typed BEFORE that only ever landed on the one colour it
 * was typed against. The website then shows a full Description tab on White and an
 * empty one on every other colour of the same listing - the copy exists, it just
 * never travelled.
 *
 * This backfills it. For each group it picks the richest source - the variant with
 * the most website copy on it - and copies that copy to the siblings that have none.
 *
 * A sibling that already has its own text in a field is LEFT ALONE. Two people may
 * have written two descriptions, and this script is not able to tell which one was
 * meant; overwriting would destroy the one nobody is looking at. Re-saving the
 * listing from the product form is the deliberate way to overwrite.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;

$args   = array_slice($argv, 1);
$commit = in_array('--commit', $args, true);
$only   = null;

foreach ($args as $a) {
    if (str_starts_with($a, '--group=')) {
        $only = substr($a, 8);
    }
}

if (!$commit) {
    echo "DRY RUN — nothing will be written. Add --commit to apply.\n\n";
}

/** The copy that belongs to the listing rather than to one colour. */
const SHARED = ['website_name', 'website_short_description', 'website_description', 'video_urls'];

$where  = "p.variant_group IS NOT NULL AND p.variant_group <> ''";
$params = [];

if ($only !== null) {
    $where   .= " AND p.variant_group = ?";
    $params[] = $only;
}

$rows = Database::select("
    SELECT p.id, p.sku, p.variant_group, p.variant_label, p.variant_order,
           p.website_name, p.website_short_description, p.website_description, p.video_urls
    FROM products p
    WHERE {$where}
    ORDER BY p.variant_group, p.variant_order, p.sku
", $params);

$groups = [];

foreach ($rows as $r) {
    $groups[$r['variant_group']][] = $r;
}

$plan  = [];
$fills = 0;

foreach ($groups as $group => $members) {
    // The richest member wins: most characters of copy across the shared fields. A
    // group where somebody filled in two colours differently still has one obvious
    // source, and it is the one with the most written on it.
    $source = null;
    $best   = 0;

    foreach ($members as $m) {
        $len = 0;

        foreach (SHARED as $col) {
            $len += strlen(trim((string)($m[$col] ?? '')));
        }

        if ($len > $best) {
            $best   = $len;
            $source = $m;
        }
    }

    if ($source === null || $best === 0) {
        continue;   // nothing written anywhere in this group
    }

    foreach ($members as $m) {
        if ((int)$m['id'] === (int)$source['id']) {
            continue;
        }

        $set = [];

        foreach (SHARED as $col) {
            $from = trim((string)($source[$col] ?? ''));
            $to   = trim((string)($m[$col] ?? ''));

            if ($from !== '' && $to === '') {
                $set[$col] = $source[$col];
            }
        }

        if ($set !== []) {
            $plan[] = [$m, $source, $set];
            $fills += count($set);
        }
    }
}

if ($plan === []) {
    echo "Every listing already shares its copy. Nothing to do.\n";
    exit(0);
}

$byGroup = [];

foreach ($plan as [$m, $source, $set]) {
    $byGroup[$m['variant_group']][] = [$m, $source, $set];
}

foreach ($byGroup as $group => $items) {
    [$firstMember, $firstSource] = $items[0];

    printf(
        "%s — from %s (%s) to %d sibling(s)\n",
        $group,
        $firstSource['sku'],
        $firstSource['variant_label'] ?: '—',
        count($items)
    );

    foreach ($items as [$m, , $set]) {
        printf("    %-16s %s\n", $m['sku'], implode(', ', array_keys($set)));
    }
}

printf("\n%d product(s), %d field(s) to fill.\n", count($plan), $fills);

if (!$commit) {
    echo "\nNothing written. Re-run with --commit to apply.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    foreach ($plan as [$m, , $set]) {
        $sets   = [];
        $params = [];

        foreach ($set as $col => $val) {
            $sets[]   = "{$col} = ?";
            $params[] = $val;
        }

        $params[] = (int)$m['id'];

        $pdo->prepare("UPDATE products SET " . implode(', ', $sets) . " WHERE id = ?")
            ->execute($params);
    }

    $pdo->commit();
    printf("\nDone. %d product(s) updated.\n", count($plan));
} catch (\Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "\nRolled back: " . $e->getMessage() . "\n");
    exit(1);
}
