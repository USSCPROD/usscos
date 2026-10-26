<?php

declare(strict_types=1);

/**
 * Attach image files on disk to a product, the way an upload through the web form
 * would. For product photography that arrives as files rather than through a browser.
 *
 *   php database/attach_product_images.php --sku=STCO-12-8 --dir=/tmp/stco12
 *   php database/attach_product_images.php --sku=STCO-12-8 --dir=/tmp/stco12 --variants --commit
 *
 * --variants attaches to every thickness of the same stencil. The 1/8" and 3/32"
 * cuts are the same artwork on the same sheet, so one photo shoot serves both, and
 * a listing that shows a photo on one thickness and a grey placeholder on the other
 * looks broken rather than thorough.
 *
 * Files are copied into each product's own upload directory rather than shared by
 * path. Sharing would mean deleting one product silently blanks the other's gallery.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;

const ALLOWED = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
const MAX_BYTES = 10485760;

$args     = array_slice($argv, 1);
$commit   = in_array('--commit', $args, true);
$variants = in_array('--variants', $args, true);
$sku = $dir = null;

foreach ($args as $a) {
    if (str_starts_with($a, '--sku=')) { $sku = substr($a, 6); }
    if (str_starts_with($a, '--dir=')) { $dir = rtrim(substr($a, 6), '/'); }
}

if ($sku === null || $dir === null || !is_dir($dir)) {
    fwrite(STDERR, "Usage: php database/attach_product_images.php --sku=SKU --dir=/path [--variants] [--commit]\n");
    exit(1);
}

if (!$commit) {
    echo "DRY RUN — nothing will be written. Add --commit to apply.\n\n";
}

$product = Database::selectOne("SELECT id, sku, name, variant_group FROM products WHERE sku = ?", [$sku]);

if (!$product) {
    fwrite(STDERR, "No product with SKU {$sku}.\n");
    exit(1);
}

$targets = [$product];

if ($variants && !empty($product['variant_group'])) {
    $targets = Database::select(
        "SELECT id, sku, name, variant_group FROM products WHERE variant_group = ? ORDER BY variant_order, sku",
        [$product['variant_group']]
    );
}

// Sorted, so the gallery order is the directory order and not whatever the
// filesystem feels like returning.
$files = [];

foreach (scandir($dir) ?: [] as $f) {
    if ($f === '.' || $f === '..') {
        continue;
    }

    $path = $dir . '/' . $f;
    $ext  = strtolower(pathinfo($f, PATHINFO_EXTENSION));

    if (!is_file($path) || !in_array($ext, ALLOWED, true)) {
        printf("  skipping %s (not an allowed image)\n", $f);
        continue;
    }

    if (filesize($path) > MAX_BYTES) {
        printf("  skipping %s (over 10 MB)\n", $f);
        continue;
    }

    // Confirm it really is an image, rather than trusting the extension. These files
    // reach the public site; a mislabelled one would render as a broken image at best.
    if (@getimagesize($path) === false && $ext !== 'webp') {
        printf("  skipping %s (not a readable image)\n", $f);
        continue;
    }

    $files[$f] = $path;
}

ksort($files);

printf("%d image(s) in %s\n", count($files), $dir);

foreach ($files as $f => $p) {
    printf("    %-34s %s KB\n", $f, number_format(filesize($p) / 1024, 0));
}

printf("\nAttaching to %d product(s):\n", count($targets));

foreach ($targets as $t) {
    $have = (int)Database::selectOne("SELECT COUNT(*) c FROM product_images WHERE product_id = ?", [$t['id']])['c'];
    printf("    %-14s %-34s (%d image(s) already)\n", $t['sku'], substr($t['name'], 0, 34), $have);
}

if (!$commit) {
    echo "\nNothing written. Add --commit to apply.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();
$written = [];

try {
    $ins = $pdo->prepare(
        "INSERT INTO product_images (product_id, file_path, file_name, caption, is_primary, sort_order)
         VALUES (?,?,?,?,?,?)"
    );

    foreach ($targets as $t) {
        $pid    = (int)$t['id'];
        $destDir = BASE_PATH . '/public/uploads/products/' . $pid;

        if (!is_dir($destDir) && !mkdir($destDir, 0755, true) && !is_dir($destDir)) {
            throw new \RuntimeException("Could not create {$destDir}");
        }

        $existing = (int)Database::selectOne("SELECT COUNT(*) c FROM product_images WHERE product_id = ?", [$pid])['c'];
        $sort = (int)(Database::selectOne("SELECT COALESCE(MAX(sort_order),-1)+1 n FROM product_images WHERE product_id = ?", [$pid])['n']);

        foreach ($files as $name => $src) {
            $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $slug = trim(strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', pathinfo($name, PATHINFO_FILENAME)) ?? ''), '-');
            $slug = substr($slug ?: 'image', 0, 60);
            $file = $slug . '-' . bin2hex(random_bytes(4)) . '.' . $ext;

            if (!copy($src, $destDir . '/' . $file)) {
                throw new \RuntimeException("Could not copy {$name} to {$destDir}");
            }

            @chmod($destDir . '/' . $file, 0644);
            $written[] = $destDir . '/' . $file;

            $ins->execute([
                $pid,
                '/uploads/products/' . $pid . '/' . $file,
                $name,
                null,
                ($existing === 0 && $sort === 0) ? 1 : 0,
                $sort,
            ]);

            $sort++;
        }
    }

    $pdo->commit();
    printf("\nAttached %d image(s) across %d product(s).\n", count($files) * count($targets), count($targets));
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // The database rolled back; the files it copied did not, so remove them rather
    // than leaving orphans in the web root that nothing references.
    foreach ($written as $w) {
        @unlink($w);
    }

    throw $e;
}
