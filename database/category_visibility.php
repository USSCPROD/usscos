<?php

declare(strict_types=1);

/**
 * Hide categories that hold no products, show the ones that do.
 *
 *   php database/category_visibility.php            report only
 *   php database/category_visibility.php --commit
 *
 * A category counts as stocked when it, or anything beneath it, holds an active
 * product. Counting only the category itself would hide every parent in the tree:
 * products hang off the leaves, so "Field Marking Paints" owns nothing directly while
 * its children own everything.
 *
 * Depth is walked explicitly rather than with a recursive CTE. The tree is three
 * levels and under a hundred rows, and an explicit walk reads the same on any MySQL
 * version this might run against.
 *
 * show_on_website is a MANUAL override as well as a computed one, so this only ever
 * turns a category ON when it has products and OFF when it has none. A category
 * somebody deliberately hid while it still had stock would be unhidden by a blunter
 * rule, which is how a manual switch stops being trusted.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;

$commit = in_array('--commit', array_slice($argv, 1), true);

if (!$commit) {
    echo "REPORT ONLY — nothing will be written. Add --commit to apply.\n\n";
}

$cats = [];

foreach (Database::select("SELECT id, name, parent_id, nav_role, show_on_website FROM categories") as $c) {
    $cats[(int)$c['id']] = $c + ['own' => 0, 'total' => 0];
}

foreach (Database::select("
    SELECT pc.category_id, COUNT(DISTINCT p.id) n
    FROM product_categories pc
    JOIN products p ON p.id = pc.product_id AND p.is_active = 1
    GROUP BY pc.category_id
") as $row) {
    $id = (int)$row['category_id'];

    if (isset($cats[$id])) {
        $cats[$id]['own'] = (int)$row['n'];
    }
}

/** Products in this category and everything beneath it. */
$reach = function (int $id) use (&$reach, $cats): int {
    $n = $cats[$id]['own'];

    foreach ($cats as $cid => $c) {
        if ((int)($c['parent_id'] ?? 0) === $id) {
            $n += $reach($cid);
        }
    }

    return $n;
};

foreach ($cats as $id => $c) {
    $cats[$id]['total'] = $reach($id);
}

$turnOn = $turnOff = [];

foreach ($cats as $id => $c) {
    $shouldShow = $c['total'] > 0;
    $doesShow   = (int)$c['show_on_website'] === 1;

    if ($shouldShow && !$doesShow) {
        $turnOn[] = $c;
    } elseif (!$shouldShow && $doesShow) {
        $turnOff[] = $c;
    }
}

printf("%d categor(ies). %d hold products (counting descendants), %d are empty.\n",
    count($cats),
    count(array_filter($cats, fn($c) => $c['total'] > 0)),
    count(array_filter($cats, fn($c) => $c['total'] === 0)));

printf("\n  → HIDE (empty, currently shown) : %d\n", count($turnOff));
printf("  → SHOW (has products, hidden)   : %d\n", count($turnOn));

if ($turnOff !== []) {
    echo "\n  Would be hidden:\n";

    foreach (array_slice($turnOff, 0, 12) as $c) {
        printf("    %-40s %s\n", substr($c['name'], 0, 40), $c['nav_role'] ?? '-');
    }

    if (count($turnOff) > 12) {
        printf("    ... and %d more\n", count($turnOff) - 12);
    }
}

echo "\n  Staying visible:\n";

foreach ($cats as $c) {
    if ($c['total'] > 0) {
        printf("    %-40s %5d product(s)%s\n", substr($c['name'], 0, 40), $c['total'],
            $c['own'] === 0 ? '  (all from children)' : '');
    }
}

if (!$commit) {
    echo "\nNothing written. Add --commit to apply.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    $set = $pdo->prepare("UPDATE categories SET show_on_website = ? WHERE id = ?");

    foreach ($turnOff as $c) {
        $set->execute([0, (int)$c['id']]);
    }

    foreach ($turnOn as $c) {
        $set->execute([1, (int)$c['id']]);
    }

    $pdo->commit();
    printf("\nHid %d, showed %d.\n", count($turnOff), count($turnOn));
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $e;
}
