<?php

declare(strict_types=1);

/**
 * Merge one category into another.
 *
 *   php database/merge_categories.php --from="Aerosol" --into="Aerosol Field Marking Paints"
 *   php database/merge_categories.php --from="Aerosol" --into="Aerosol Field Marking Paints" --commit
 *
 * For folding our own categories into the imported website tree: the products move,
 * any child categories are re-hung on the target, and the source is deactivated and
 * hidden rather than deleted.
 *
 * NOT DELETED, because a category id can be referenced from places this script cannot
 * see - a saved filter, a report, a bookmarked URL, the storefront config. An empty
 * inactive category answers those harmlessly; a missing row makes them fail.
 *
 * Product links are moved with INSERT IGNORE. A product already in both categories is
 * common when somebody has been tidying by hand, and the merge should absorb that
 * rather than fall over on a duplicate key.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;

$args   = array_slice($argv, 1);
$commit = in_array('--commit', $args, true);
$from = $into = null;

foreach ($args as $a) {
    if (str_starts_with($a, '--from=')) { $from = substr($a, 7); }
    if (str_starts_with($a, '--into=')) { $into = substr($a, 7); }
}

if ($from === null || $into === null) {
    fwrite(STDERR, "Usage: php database/merge_categories.php --from=\"Old\" --into=\"New\" [--commit]\n");
    exit(1);
}

/** Resolve by external ref first, then by exact name. */
$find = function (string $needle): array|false {
    $row = Database::selectOne("SELECT * FROM categories WHERE external_ref = ? LIMIT 1", [$needle]);

    if ($row) {
        return $row;
    }

    $rows = Database::select("SELECT * FROM categories WHERE name = ?", [$needle]);

    if (count($rows) > 1) {
        fwrite(STDERR, "'{$needle}' matches " . count($rows) . " categories — use the external ref instead.\n");
        exit(1);
    }

    return $rows[0] ?? false;
};

$src = $find($from);
$dst = $find($into);

if (!$src) { fwrite(STDERR, "No category called '{$from}'.\n"); exit(1); }
if (!$dst) { fwrite(STDERR, "No category called '{$into}'.\n"); exit(1); }

if ((int)$src['id'] === (int)$dst['id']) {
    fwrite(STDERR, "Source and target are the same category.\n");
    exit(1);
}

$srcId = (int)$src['id'];
$dstId = (int)$dst['id'];

// Merging a category into its own descendant would orphan the branch.
$walk = $dstId;
$seen = 0;

while ($walk !== 0 && $seen++ < 50) {
    if ($walk === $srcId) {
        fwrite(STDERR, "'{$into}' sits beneath '{$from}' — merging would orphan the branch.\n");
        exit(1);
    }

    $row  = Database::selectOne("SELECT parent_id FROM categories WHERE id = ?", [$walk]);
    $walk = (int)($row['parent_id'] ?? 0);
}

$products = Database::select("
    SELECT p.id, p.sku FROM product_categories pc
    JOIN products p ON p.id = pc.product_id
    WHERE pc.category_id = ?
", [$srcId]);

$already = (int)Database::selectOne("
    SELECT COUNT(*) c FROM product_categories a
    JOIN product_categories b ON b.product_id = a.product_id AND b.category_id = ?
    WHERE a.category_id = ?
", [$dstId, $srcId])['c'];

$children = Database::select("SELECT id, name FROM categories WHERE parent_id = ?", [$srcId]);

if (!$commit) {
    echo "DRY RUN — nothing will be written. Add --commit to apply.\n\n";
}

printf("  FROM  %-36s (id %d%s)\n", $src['name'], $srcId, $src['external_ref'] ? ', ' . $src['external_ref'] : '');
printf("  INTO  %-36s (id %d%s)\n", $dst['name'], $dstId, $dst['external_ref'] ? ', ' . $dst['external_ref'] : '');
printf("\n  products to move   : %d%s\n", count($products),
    $already > 0 ? sprintf('  (%d already in both)', $already) : '');
printf("  child categories   : %d%s\n", count($children),
    $children === [] ? '' : '  (' . implode(', ', array_map(fn($c) => $c['name'], $children)) . ')');
printf("  source afterwards  : deactivated and hidden, not deleted\n");

if (!$commit) {
    echo "\nNothing written. Add --commit to apply.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    $pdo->prepare(
        "INSERT IGNORE INTO product_categories (product_id, category_id, is_primary, sort_order)
         SELECT product_id, ?, is_primary, sort_order FROM product_categories WHERE category_id = ?"
    )->execute([$dstId, $srcId]);

    $pdo->prepare("DELETE FROM product_categories WHERE category_id = ?")->execute([$srcId]);
    $pdo->prepare("UPDATE categories SET parent_id = ? WHERE parent_id = ?")->execute([$dstId, $srcId]);
    $pdo->prepare("UPDATE categories SET is_active = 0, show_on_website = 0 WHERE id = ?")->execute([$srcId]);

    $pdo->commit();

    printf("\nMoved %d product link(s) and %d child categor(ies). '%s' is now inactive.\n",
        count($products), count($children), $src['name']);
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $e;
}
