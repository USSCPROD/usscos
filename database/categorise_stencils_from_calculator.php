<?php

declare(strict_types=1);

/**
 * File stencils under child categories using the price sheet's own block headings.
 *
 *   php database/categorise_stencils_from_calculator.php --file=calc.xlsx
 *   php database/categorise_stencils_from_calculator.php --file=calc.xlsx --commit
 *
 * The listing plan had a Category column; the calculator does not. What it has
 * instead is structure: rows sit under headings — ALPHABET KITS, HANDICAP, ARROWS,
 * RETAIL STORES — and that heading is the category. Walking the sheet and carrying
 * the last heading forward recovers it exactly, with no guessing from product names.
 *
 * The headings are listed explicitly rather than detected. '7Brew' sits directly
 * under 'RETAIL STORES' as a brand within it, and any rule clever enough to spot
 * headings automatically would treat it as a new section and file every 7Brew
 * stencil under a category of its own.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;
use PhpOffice\PhpSpreadsheet\IOFactory;

/** Heading in the sheet => child category under 'Stencils'. */
const BLOCKS = [
    'NUMBER KITS'           => 'Number kits',
    '2-DIGIT NUMBER KITS'   => 'Number kits',
    'ALPHABET KITS'         => 'Alphabet kits',
    'REFERENCE TABLE - SINGLE LETTERS / NUMBERS' => 'Single characters',
    'WORD STENCILS'         => 'Words',
    'HANDICAP'              => 'Handicap',
    'BIKE RIDER'            => 'Bike lane',
    'ARROWS'                => 'Arrows',
    'LINES AND STOP BARS'   => 'Lines & stop bars',
    'RETAIL STORES'         => 'Retail stores',
];

$args   = array_slice($argv, 1);
$commit = in_array('--commit', $args, true);
$file   = null;

foreach ($args as $a) {
    if (str_starts_with($a, '--file=')) {
        $file = substr($a, 7);
    }
}

if ($file === null || !is_file($file)) {
    fwrite(STDERR, "Usage: php database/categorise_stencils_from_calculator.php --file=/path/to.xlsx [--commit]\n");
    exit(1);
}

if (!$commit) {
    echo "DRY RUN — nothing will be written. Add --commit to apply.\n\n";
}

$book = IOFactory::createReaderForFile($file);
$book->setReadDataOnly(true);
$workbook = $book->load($file);

$assign = [];

foreach ($workbook->getSheetNames() as $sheetName) {
    $data  = $workbook->getSheetByName($sheetName)->toArray(null, true, false, true);
    $block = null;

    foreach ($data as $row) {
        $vals = array_values($row);
        $head = strtoupper(trim((string)($vals[0] ?? '')));

        // A heading starts a section and carries forward until the next one. Compared
        // without its parenthetical, since the sheet writes
        // 'NUMBER KITS (0-9 plus 2 spacers = 12 pieces)'.
        $bare = trim(preg_replace('/\s*\(.*$/', '', $head) ?? '');

        if ($bare !== '' && isset(BLOCKS[$bare])) {
            $block = BLOCKS[$bare];
            continue;
        }

        if ($block === null) {
            continue;
        }

        foreach ($vals as $v) {
            $sku = trim((string)$v);

            if (preg_match('/^ST[A-Z0-9.\-]*-(8|32)(-C)?$/i', $sku)) {
                $assign[strtoupper($sku)] = $block;
            }
        }
    }
}

printf("%d SKU(s) matched to a block.\n\n", count($assign));

$have = [];

foreach (Database::select("SELECT id, sku FROM products WHERE is_active = 1") as $p) {
    $have[strtoupper($p['sku'])] = (int)$p['id'];
}

$plan = [];
$missing = 0;

foreach ($assign as $sku => $cat) {
    if (!isset($have[$sku])) {
        $missing++;
        continue;
    }

    $plan[$cat][] = $have[$sku];
}

ksort($plan);

printf("  %-24s %s\n", 'CHILD CATEGORY', 'PRODUCTS');

foreach ($plan as $c => $ids) {
    printf("  %-24s %6d\n", $c, count($ids));
}

if ($missing > 0) {
    printf("\n  %d SKU(s) in the sheet are not loaded — ignored.\n", $missing);
}

if (!$commit) {
    echo "\nNothing written. Add --commit to apply.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    $parent = Database::selectOne("SELECT id FROM categories WHERE name = 'Stencils' AND parent_id IS NULL LIMIT 1");

    if (!$parent) {
        throw new \RuntimeException("No top-level 'Stencils' category.");
    }

    $parentId = (int)$parent['id'];
    $find = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND parent_id = ? LIMIT 1");
    $make = $pdo->prepare("INSERT INTO categories (parent_id, name, slug, is_active, show_on_website) VALUES (?,?,?,1,1)");
    $map  = $pdo->prepare("INSERT IGNORE INTO product_categories (product_id, category_id, is_primary) VALUES (?,?,0)");

    $made = $filed = 0;

    foreach ($plan as $cat => $ids) {
        $find->execute([$cat, $parentId]);
        $row = $find->fetch(\PDO::FETCH_ASSOC);

        if ($row) {
            $catId = (int)$row['id'];
        } else {
            $slug = trim(strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $cat) ?? ''), '-');
            $make->execute([$parentId, $cat, 'stencils-' . $slug]);
            $catId = (int)$pdo->lastInsertId();
            $made++;
        }

        foreach ($ids as $pid) {
            $map->execute([$pid, $catId]);
            $filed++;
        }
    }

    $pdo->commit();
    printf("\nCreated %d child categor(ies), filed %d product(s).\n", $made, $filed);
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $e;
}
