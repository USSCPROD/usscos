<?php

declare(strict_types=1);

/**
 * Delete categories that hold nothing.
 *
 *   php database/delete_categories.php --names="Football,Soccer,Wood"
 *   php database/delete_categories.php --names="Football,Soccer,Wood" --commit
 *
 * Refuses any category that still holds products or has children. Deleting one that
 * does would strand its products with no category at all, and - worse for the public
 * site - a product is kept OFF the storefront by being in a category, so removing the
 * category publishes it.
 *
 * That is not hypothetical: StorefrontRepository hides anything filed under 'Toll
 * Manufacturing', the work done for other companies. Delete that category and 73
 * products made for somebody else appear in the shop.
 *
 * A category that cannot be deleted is reported, not skipped quietly, so the refusal
 * is something a person sees and decides about.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;

$args   = array_slice($argv, 1);
$commit = in_array('--commit', $args, true);
$names  = [];

foreach ($args as $a) {
    if (str_starts_with($a, '--names=')) {
        $names = array_filter(array_map('trim', explode(',', substr($a, 8))));
    }
}

if ($names === []) {
    fwrite(STDERR, "Usage: php database/delete_categories.php --names=\"A,B,C\" [--commit]\n");
    exit(1);
}

if (!$commit) {
    echo "DRY RUN — nothing will be written. Add --commit to apply.\n\n";
}

$ok = $blocked = [];

foreach ($names as $name) {
    $rows = Database::select("SELECT id, name, nav_role FROM categories WHERE name = ?", [$name]);

    if ($rows === []) {
        $blocked[] = ['name' => $name, 'why' => 'not found'];
        continue;
    }

    foreach ($rows as $c) {
        $id = (int)$c['id'];

        $products = (int)Database::selectOne(
            "SELECT COUNT(DISTINCT product_id) c FROM product_categories WHERE category_id = ?", [$id]
        )['c'];

        $children = (int)Database::selectOne(
            "SELECT COUNT(*) c FROM categories WHERE parent_id = ?", [$id]
        )['c'];

        if ($products > 0) {
            $blocked[] = ['name' => $c['name'], 'why' => "holds {$products} product(s)"];
            continue;
        }

        if ($children > 0) {
            $blocked[] = ['name' => $c['name'], 'why' => "has {$children} child categor(ies)"];
            continue;
        }

        $ok[] = $c;
    }
}

printf("  → DELETE : %d\n", count($ok));

foreach ($ok as $c) {
    printf("      %-38s %s\n", substr($c['name'], 0, 38), $c['nav_role'] ?? '-');
}

if ($blocked !== []) {
    printf("\n  → REFUSED: %d\n", count($blocked));

    foreach ($blocked as $b) {
        printf("      %-38s %s\n", substr($b['name'], 0, 38), $b['why']);
    }
}

if (!$commit) {
    echo "\nNothing written. Add --commit to apply.\n";
    exit(0);
}

if ($ok === []) {
    echo "\nNothing to delete.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    $del = $pdo->prepare("DELETE FROM categories WHERE id = ?");

    foreach ($ok as $c) {
        $del->execute([(int)$c['id']]);
    }

    $pdo->commit();
    printf("\nDeleted %d categor(ies).\n", count($ok));
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $e;
}
