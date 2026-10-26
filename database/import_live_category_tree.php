<?php

declare(strict_types=1);

/**
 * Import the live usscproducts.com category tree.
 *
 *   php database/import_live_category_tree.php
 *   php database/import_live_category_tree.php --commit
 *
 * This is the authoritative structure: read from the site's own mega-menus, where
 * the URL path carries the hierarchy. The earlier spreadsheet tree was a different,
 * flatter taxonomy with different names, and where the two disagree the live site
 * wins - it is what customers actually see.
 *
 * Keyed on the WooCommerce slug, which is the site's own permanent identifier and
 * survives a label being edited. Names are NOT unique in this tree: 'Asphalt Repair
 * Products' appears under both Traffic Paints and Specialty Coatings, as separate
 * categories with separate slugs, so matching on name would collapse two real
 * categories into one.
 *
 * A category we already hold under the same name is adopted rather than duplicated,
 * so the ones already carrying products keep them.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;

$args   = array_slice($argv, 1);
$commit = in_array('--commit', $args, true);
$file   = BASE_PATH . '/database/reference/usscproducts-category-tree.csv';

foreach ($args as $a) {
    if (str_starts_with($a, '--file=')) {
        $file = substr($a, 7);
    }
}

if (!is_file($file)) {
    fwrite(STDERR, "Not found: {$file}\n");
    exit(1);
}

if (!$commit) {
    echo "DRY RUN — nothing will be written. Add --commit to apply.\n\n";
}

$rows = [];
$fh   = fopen($file, 'r');
$head = fgetcsv($fh);

while (($r = fgetcsv($fh)) !== false) {
    if (($r[0] ?? '') === '') {
        continue;
    }

    $rows[$r[0]] = [
        'slug'   => $r[0],
        'parent' => $r[1] ?: null,
        'name'   => $r[2],
        'depth'  => (int)$r[3],
    ];
}

fclose($fh);

printf("%d categor(ies) in the live tree.\n", count($rows));

$byDepth = [];

foreach ($rows as $r) {
    $byDepth[$r['depth']] = ($byDepth[$r['depth']] ?? 0) + 1;
}

ksort($byDepth);

foreach ($byDepth as $d => $n) {
    printf("    level %d : %3d\n", $d, $n);
}

// What we already hold, by live ref and by name.
$byRef = $byName = [];

foreach (Database::select("SELECT id, name, external_ref FROM categories") as $c) {
    if (str_starts_with((string)$c['external_ref'], 'WC:')) {
        $byRef[substr($c['external_ref'], 3)] = $c;
    }

    $key = mb_strtolower(trim($c['name']));

    // First one wins: a name held twice is already ambiguous, and picking either
    // consistently beats picking a different one on each run.
    if (!isset($byName[$key])) {
        $byName[$key] = $c;
    }
}

$insert = $adopt = $update = [];

foreach ($rows as $slug => $r) {
    if (isset($byRef[$slug])) {
        $update[$slug] = $r;
    } elseif (isset($byName[mb_strtolower($r['name'])])) {
        $adopt[$slug] = $r + ['id' => (int)$byName[mb_strtolower($r['name'])]['id']];
    } else {
        $insert[$slug] = $r;
    }
}

printf("\n  → INSERT new            : %d\n", count($insert));
printf("  → ADOPT existing by name: %d\n", count($adopt));
printf("  → UPDATE already linked : %d\n", count($update));

if ($adopt !== []) {
    echo "\n  Adopting (these keep their products):\n";

    foreach (array_slice($adopt, 0, 14) as $a) {
        printf("      %s\n", $a['name']);
    }

    if (count($adopt) > 14) {
        printf("      ... and %d more\n", count($adopt) - 14);
    }
}

if (!$commit) {
    echo "\nNothing written. Add --commit to apply.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    $taken = [];

    foreach (Database::select("SELECT id, slug FROM categories") as $c) {
        $taken[strtolower($c['slug'])] = (int)$c['id'];
    }

    $ins = $pdo->prepare(
        "INSERT INTO categories (name, slug, external_ref, nav_role, canonical_url, is_active, show_on_website)
         VALUES (?,?,?,?,?,1,1)"
    );

    $upd = $pdo->prepare("UPDATE categories SET name = ?, external_ref = ?, nav_role = ?, canonical_url = ? WHERE id = ?");

    $idOf = [];

    foreach ($byRef as $slug => $c) {
        $idOf[$slug] = (int)$c['id'];
    }

    foreach ($rows as $slug => $r) {
        $role = $r['depth'] === 1 ? 'top_level' : 'category';
        $url  = 'https://usscproducts.com/product-category/' . $slug . '/';

        if (isset($adopt[$slug])) {
            $upd->execute([$r['name'], 'WC:' . $slug, $role, $url, $adopt[$slug]['id']]);
            $idOf[$slug] = $adopt[$slug]['id'];
            continue;
        }

        if (isset($idOf[$slug])) {
            $upd->execute([$r['name'], 'WC:' . $slug, $role, $url, $idOf[$slug]]);
            continue;
        }

        // The live slug is the natural one, but ours is unique and may be in use by a
        // category this tree does not cover.
        $use = isset($taken[$slug]) ? $slug . '-wc' : $slug;
        $ins->execute([$r['name'], $use, 'WC:' . $slug, $role, $url]);
        $idOf[$slug] = (int)$pdo->lastInsertId();
        $taken[$use] = $idOf[$slug];
    }

    // Parents last: a child can be listed before its parent.
    $setParent = $pdo->prepare("UPDATE categories SET parent_id = ? WHERE id = ?");
    $linked = 0;

    foreach ($rows as $slug => $r) {
        $pid = ($r['parent'] !== null && isset($idOf[$r['parent']])) ? $idOf[$r['parent']] : null;

        if ($pid !== null && $pid === $idOf[$slug]) {
            $pid = null;
        }

        $setParent->execute([$pid, $idOf[$slug]]);

        if ($pid !== null) {
            $linked++;
        }
    }

    $pdo->commit();
    printf("\nInserted %d, adopted %d, updated %d. %d parent link(s) set.\n",
        count($insert), count($adopt), count($update), $linked);
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $e;
}
