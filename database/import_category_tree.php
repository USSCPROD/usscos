<?php

declare(strict_types=1);

/**
 * Import the website's category tree.
 *
 *   php database/import_category_tree.php --file=tree.xlsx
 *   php database/import_category_tree.php --file=tree.xlsx --commit
 *
 * Matched on the tree's own Category ID, not on the name. Names differ between the
 * website and USSCOS already - "Field Marking Paints" against "Field Marking Paint" -
 * and matching on them would create a duplicate the first time somebody edits a label.
 *
 * Parents are resolved in a second pass. The sheet is not in dependency order, so a
 * child can appear before its parent, and inserting in one pass would leave those
 * children at the root and look like a flat tree.
 *
 * Nothing is deleted. A category missing from a later version of the sheet keeps its
 * products and its history - taking it away is a decision, not a side effect of an
 * import.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;

$args   = array_slice($argv, 1);
$commit = in_array('--commit', $args, true);
$file   = null;

foreach ($args as $a) {
    if (str_starts_with($a, '--file=')) {
        $file = substr($a, 7);
    }
}

if ($file === null || !is_file($file)) {
    fwrite(STDERR, "Usage: php database/import_category_tree.php --file=/path/to.xlsx [--commit]\n");
    exit(1);
}

if (!$commit) {
    echo "DRY RUN — nothing will be written. Add --commit to apply.\n\n";
}

$book = IOFactory::createReaderForFile($file);
$book->setReadDataOnly(true);
$data = $book->load($file)->getSheet(0)->toArray(null, true, false, true);

// The header is the first row carrying a 'Category ID' cell.
$headerRow = null;

foreach (array_slice($data, 0, 6, true) as $i => $row) {
    foreach ($row as $c) {
        if (strcasecmp(trim((string)$c), 'Category ID') === 0) {
            $headerRow = $i;
            break 2;
        }
    }
}

if ($headerRow === null) {
    fwrite(STDERR, "No header row with a 'Category ID' column.\n");
    exit(1);
}

$col = [];

foreach ($data[$headerRow] as $ci => $c) {
    $h = trim((string)$c);
    if ($h !== '') {
        $col[$h] = $ci;
    }
}

$get = fn(array $r, string $h): string => isset($col[$h]) ? trim((string)($r[$col[$h]] ?? '')) : '';

$rows = [];

/* Keyed, not positional. toArray() numbers rows from 1, so the header at key H sits
   at POSITION H-1 - array_slice(offset: H+1) therefore skipped the first data row
   of every sheet, silently. Filtering on the key cannot drift that way. */
foreach (array_filter($data, fn($k) => $k > $headerRow, ARRAY_FILTER_USE_KEY) as $r) {
    $ref  = $get($r, 'Category ID');
    $name = $get($r, 'Category Name');

    if ($ref === '' || $name === '') {
        continue;
    }

    $rows[$ref] = [
        'ref'    => $ref,
        'name'   => $name,
        'parent' => $get($r, 'Parent Category ID') ?: null,
        'role'   => $get($r, 'Navigation Role') ?: null,
        'url'    => $get($r, 'Canonical URL') ?: null,
        'level'  => (int)$get($r, 'Level'),
    ];
}

printf("%d categor(ies) in the sheet.\n", count($rows));

$byRole = [];

foreach ($rows as $r) {
    $byRole[$r['role'] ?? '(none)'] = ($byRole[$r['role'] ?? '(none)'] ?? 0) + 1;
}

arsort($byRole);

echo "\n  By navigation role:\n";

foreach ($byRole as $role => $n) {
    printf("    %-14s %3d\n", $role, $n);
}

// What is already here, by external_ref and by name.
$existingRef  = [];
$existingName = [];

foreach (Database::select("SELECT id, name, external_ref, parent_id FROM categories") as $c) {
    if (($c['external_ref'] ?? '') !== '') {
        $existingRef[$c['external_ref']] = $c;
    }

    $existingName[mb_strtolower(trim($c['name']))] = $c;
}

$insert = $update = $adopt = [];

foreach ($rows as $ref => $r) {
    if (isset($existingRef[$ref])) {
        $update[$ref] = $r;
        continue;
    }

    // A category we already have under the same name takes the tree's ref rather than
    // gaining a twin beside it.
    $key = mb_strtolower($r['name']);

    if (isset($existingName[$key])) {
        $adopt[$ref] = $r + ['id' => (int)$existingName[$key]['id']];
        continue;
    }

    $insert[$ref] = $r;
}

printf("\n  → INSERT new            : %d\n", count($insert));
printf("  → ADOPT existing by name: %d%s\n", count($adopt),
    $adopt === [] ? '' : '  (' . implode(', ', array_map(fn($a) => $a['name'], array_slice($adopt, 0, 4))) . ')');
printf("  → UPDATE already linked : %d\n", count($update));

if (!$commit) {
    echo "\nNothing written. Add --commit to apply.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    /*
     * categories.slug is unique, and the tree holds the same label in two places -
     * 'Synthetic Turf Cleaners and Deodorizers' appears both as a product category and
     * as a discovery entry. A plain slug collides and aborts the whole import.
     *
     * The second one takes the external ref as a suffix rather than a counter: '-2'
     * tells nobody which of the two it is, while the ref says exactly where it came
     * from in the tree.
     */
    $taken = [];

    foreach (Database::select("SELECT slug FROM categories") as $c) {
        $taken[strtolower($c['slug'])] = true;
    }

    $slugOf = function (string $name, string $ref) use (&$taken): string {
        $slug = trim(strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $name) ?? ''), '-');

        if ($slug === '') {
            $slug = strtolower($ref);
        }

        if (isset($taken[$slug])) {
            $suffix = trim(strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $ref) ?? ''), '-');
            $slug   = substr($slug . '-' . $suffix, 0, 150);
        }

        // Still taken means two refs produced one slug; fall back to the ref alone.
        if (isset($taken[$slug])) {
            $slug = substr(strtolower($ref), 0, 150);
        }

        $taken[$slug] = true;

        return $slug;
    };

    $ins = $pdo->prepare(
        "INSERT INTO categories (name, slug, external_ref, nav_role, canonical_url, is_active, show_on_website)
         VALUES (?,?,?,?,?,1,1)"
    );

    $upd = $pdo->prepare(
        "UPDATE categories SET name = ?, external_ref = ?, nav_role = ?, canonical_url = ? WHERE id = ?"
    );

    $idOf = [];

    foreach ($existingRef as $ref => $c) {
        $idOf[$ref] = (int)$c['id'];
    }

    // Pass one: every row exists, parents not yet set.
    foreach ($rows as $ref => $r) {
        if (isset($adopt[$ref])) {
            $upd->execute([$r['name'], $ref, $r['role'], $r['url'], $adopt[$ref]['id']]);
            $idOf[$ref] = $adopt[$ref]['id'];
            continue;
        }

        if (isset($idOf[$ref])) {
            $upd->execute([$r['name'], $ref, $r['role'], $r['url'], $idOf[$ref]]);
            continue;
        }

        $ins->execute([$r['name'], $slugOf($r['name'], $ref), $ref, $r['role'], $r['url']]);
        $idOf[$ref] = (int)$pdo->lastInsertId();
    }

    // Pass two: now that every row has an id, hang them off their parents. A child can
    // appear above its parent in the sheet, so this cannot be done in one pass.
    $setParent = $pdo->prepare("UPDATE categories SET parent_id = ? WHERE id = ?");
    $linked = 0;

    foreach ($rows as $ref => $r) {
        $parentRef = $r['parent'];
        $parentId  = ($parentRef !== null && isset($idOf[$parentRef])) ? $idOf[$parentRef] : null;

        // Never let a category be its own parent, whatever the sheet says.
        if ($parentId !== null && $parentId === $idOf[$ref]) {
            $parentId = null;
        }

        $setParent->execute([$parentId, $idOf[$ref]]);

        if ($parentId !== null) {
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

$empty = (int)Database::selectOne("
    SELECT COUNT(*) c FROM categories cat
    WHERE NOT EXISTS (
        SELECT 1 FROM product_categories pc
        JOIN products p ON p.id = pc.product_id AND p.is_active = 1
        WHERE pc.category_id = cat.id
    )
")['c'];

printf("\n%d categor(ies) currently hold no active product.\n", $empty);
echo "Those are hidden on the storefront automatically — nothing to switch off.\n";
