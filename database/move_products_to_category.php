<?php

declare(strict_types=1);

/**
 * Move specific products into a category, by SKU prefix.
 *
 *   php database/move_products_to_category.php --from="Safety Products" --prefix=CURB --into="Curb Protection"
 *   ... --commit
 *
 * For a category that holds two unrelated things and cannot be merged wholesale.
 * 'Safety Products' is CurbHuggers wheel protection and Powershot field marker tufts,
 * which belong under Curb Protection and Field Markers respectively — moving the lot
 * either way would file half of them somewhere wrong.
 *
 * The source link is removed only for the products that moved, so what is left behind
 * stays visible and the category empties only when everything in it has a home.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;

$args   = array_slice($argv, 1);
$commit = in_array('--commit', $args, true);
$from = $into = $prefix = null;

foreach ($args as $a) {
    if (str_starts_with($a, '--from='))   { $from   = substr($a, 7); }
    if (str_starts_with($a, '--into='))   { $into   = substr($a, 7); }
    if (str_starts_with($a, '--prefix=')) { $prefix = substr($a, 9); }
}

if ($from === null || $into === null || $prefix === null) {
    fwrite(STDERR, "Usage: --from=\"Old\" --prefix=SKU --into=\"New\" [--commit]\n");
    exit(1);
}

$src = Database::selectOne("SELECT id, name FROM categories WHERE name = ? LIMIT 1", [$from]);
$dst = Database::selectOne("SELECT id, name FROM categories WHERE name = ? LIMIT 1", [$into]);

if (!$src) { fwrite(STDERR, "No category '{$from}'.\n"); exit(1); }
if (!$dst) { fwrite(STDERR, "No category '{$into}'.\n"); exit(1); }

$moving = Database::select("
    SELECT p.id, p.sku, p.name
    FROM product_categories pc
    JOIN products p ON p.id = pc.product_id
    WHERE pc.category_id = ? AND p.sku LIKE ?
    ORDER BY p.sku
", [(int)$src['id'], $prefix . '%']);

if (!$commit) {
    echo "DRY RUN — nothing will be written. Add --commit to apply.\n\n";
}

printf("  %s  --[SKU %s*]-->  %s\n\n", $src['name'], $prefix, $dst['name']);
printf("  %d product(s):\n", count($moving));

foreach ($moving as $p) {
    printf("      %-22s %s\n", $p['sku'], substr($p['name'], 0, 46));
}

$left = (int)Database::selectOne(
    "SELECT COUNT(*) c FROM product_categories WHERE category_id = ?", [(int)$src['id']]
)['c'] - count($moving);

printf("\n  left in '%s' afterwards: %d\n", $src['name'], $left);

if (!$commit) {
    echo "\nNothing written. Add --commit to apply.\n";
    exit(0);
}

if ($moving === []) {
    echo "\nNothing matched.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    $add = $pdo->prepare("INSERT IGNORE INTO product_categories (product_id, category_id, is_primary) VALUES (?,?,1)");
    $rm  = $pdo->prepare("DELETE FROM product_categories WHERE product_id = ? AND category_id = ?");

    foreach ($moving as $p) {
        $add->execute([(int)$p['id'], (int)$dst['id']]);
        $rm->execute([(int)$p['id'], (int)$src['id']]);
    }

    $pdo->commit();
    printf("\nMoved %d product(s).\n", count($moving));
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $e;
}
