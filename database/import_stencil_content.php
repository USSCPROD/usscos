<?php

declare(strict_types=1);

/**
 * The stencil shop's listings, names, descriptions and pictures, into USSCOS.
 *
 *   php database/import_stencil_content.php            dry run: the plan, nothing fetched or written
 *   php database/import_stencil_content.php --commit   fetch the pictures, then write
 *
 * Run database/sync_stencils_from_shop.php first: this one assumes the SKUs already match.
 *
 * LISTINGS MATCH THE SHOP. Chip, 19 Nov 2026. On the shop NO PARKING is one page where the
 * customer picks a size and a thickness; USSCOS grouped by thickness only, which made every
 * size its own card — about 300 cards in Word Stencils against the shop's 41. Each shop
 * product becomes one variant group here, axis 'Size & thickness', labels like
 * '4" letters · 1/8"', in the shop's own order. The storefront's existing single picker
 * shows them as one row of buttons; no code change.
 *
 * group_stencil_variants.php only touches groups whose axis is Thickness, so it leaves
 * these alone. Products the shop does not sell keep whatever grouping they had, except a
 * USSCOS-only thickness of a size the shop does sell, which joins that size's listing.
 *
 * WHAT IS WRITTEN, per SKU
 *   variant_group/label/order/axis/title   the listing, as above
 *   website_name                            the listing title: 'NO PARKING Stencil'
 *   website_short_description               that SKU's size: '28" wide × 4" tall · 1/8" thick'
 *   website_description                     the shop's description, as paragraphs
 *   product_images                          the shop's photos, the stencil picture of the
 *                                           SKU's size and its measurement drawing
 *   website_slug / website_size_slug        the shop's address for the listing and size,
 *                                           so /stencils/no-parking/4-inch-letters works here
 *
 * AND, OUTSIDE PRODUCTS
 *   the five quote listings (endzone, midfield, custom field, rooftop, yard): name,
 *     description, the made-to-order note and every photo
 *   categories.website_copy                 the long copy under each category's products;
 *                                           the Stencils landing gets the shop's home copy
 *   site_pages                              Shipping & Returns, and stencil materials & sizing
 *
 * ONE SITE. Chip, 19 Nov 2026: usscproducts.com will point at USSCOS and there is no
 * separate stencil site. So no copy may send a customer elsewhere: links in the shop's
 * copy are rewritten to their address here (siteLinks), and a link to the WordPress paint
 * pages becomes the paint section of this same site.
 *
 * One image per listing is marked is_listing: the shop's first photo, or else the stencil
 * picture of the size the shop shows on its own card. That is the card picture.
 *
 * PICTURES are copied, not hot-linked: the shop is going away, and a page that loads its
 * images from a retired server breaks the day it is switched off. They land in
 * public/uploads/stencils/, which deploys never touch. Re-running replaces only rows under
 * that prefix; an image somebody uploaded by hand is never removed.
 *
 *   photos/     the shop's uploaded photos, by their shop filename
 *   art/        the stencil picture of a size, as PNG (the shop renders it at 1200 px)
 *   drawings/   the measurement drawing of a size, as SVG — the shop has no PNG of it
 *
 * AN SVG IS A DOCUMENT, NOT A PICTURE. Opened directly from os.usscos.com it runs with the
 * staff application's origin, so a drawing carrying script would be a way into USSCOS.
 * These come from the shop's own generator, but every one is still refused if it contains
 * script, event handlers, foreignObject or an external link.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;

const SHOP      = 'https://stencils.usscproducts.com';
const WEB_DIR   = '/uploads/stencils';
const AXIS      = 'Size & thickness';
const PAUSE_US  = 150000;   // between fetches: this is the live shop, customers are on it

const THICKNESS = [
    '1/8'      => ['label' => '1/8"',     'order' => 1],
    '3/32'     => ['label' => '3/32"',    'order' => 2],
    '1/16'     => ['label' => '1/16"',    'order' => 3],
    '10mil'    => ['label' => '10 mil',   'order' => 4],
    'ldpe'     => ['label' => 'LDPE',     'order' => 5],
    'aluminum' => ['label' => 'Aluminum', 'order' => 6],
];

/** Shop category => the USSCOS category whose hero image its call-for-quote listing supplies. */
/** Shop call-for-pricing listing => SKU. The same list as sync_stencils_from_shop.php. */
const QUOTE_SKU = [
    'cf-endzone-lettering'           => 'STEN-CUSTOM-ENDZONE',
    'cf-midfield-logo'               => 'STEN-CUSTOM-MIDFIELD',
    'cf-custom-field'                => 'STEN-CUSTOM-FIELD',
    'ussc-custom-rooftop-stencils'   => 'STEN-CUSTOM-ROOFTOP',
    'ussc-custom-yard-lawn-stencils' => 'STEN-CUSTOM-YARD',
];

/** The shop's made-to-order note, where a quote listing has none of its own. */
const QUOTE_NOTE = '<strong>Made to order.</strong> Every one of these is cut for your field, team or design, so we price it with you. Send us your artwork or logo and the size you need, and we\'ll quote it and send a proof before cutting.';

const HERO = [
    'endzone'      => 'stencils-endzone',
    'midfield'     => 'stencils-midfield',
    'custom-field' => 'stencils-custom-field',
    'rooftop'      => 'stencils-rooftop',
    'yard'         => 'stencils-yard',
];

$commit = in_array('--commit', array_slice($argv, 1), true);
$root   = BASE_PATH . '/public' . WEB_DIR;

// ── Read the shop ────────────────────────────────────────────────────────────

$raw     = fetch(SHOP . '/catalog.json');
$catalog = $raw === null ? null : json_decode($raw, true);

if (!is_array($catalog) || !is_array($catalog['products'] ?? null)) {
    fwrite(STDERR, "Could not read " . SHOP . "/catalog.json\n");
    exit(1);
}

/** Shop SKU (upper) => where it sits: product, variant, thickness key. */
$shopSku = [];

/** Size key with thickness removed => [product id, variant index], to place a USSCOS-only thickness. */
$shopBase = [];

foreach ($catalog['products'] as $pi => $p) {
    foreach ($p['variants'] ?? [] as $vi => $v) {
        foreach ($v['options'] ?? [] as $thick => $o) {
            $sku = strtoupper(trim((string)($o['sku'] ?? '')));

            if ($sku !== '') {
                $shopSku[$sku] = ['p' => $pi, 'v' => $vi, 't' => (string)$thick];
                $shopBase[skuBase($sku)] = ['p' => $pi, 'v' => $vi];
            }
        }
    }
}

if (count($shopSku) < 1000) {
    fwrite(STDERR, "Refusing: " . count($shopSku) . " SKUs looks like a partial catalog.\n");
    exit(1);
}

// ── Read USSCOS ──────────────────────────────────────────────────────────────

$stencilCat = Database::selectOne("SELECT id FROM categories WHERE name = 'Stencils' AND parent_id IS NULL LIMIT 1");

if (!$stencilCat) {
    fwrite(STDERR, "No top-level 'Stencils' category.\n");
    exit(1);
}

$os = [];

foreach (Database::select("
    SELECT DISTINCT p.id, p.sku, p.website_name, p.website_description, p.variant_group, p.variant_axis
    FROM products p
    JOIN product_categories pc ON pc.product_id = p.id
    JOIN categories c          ON c.id = pc.category_id
    WHERE p.is_active = 1 AND (c.id = ? OR c.parent_id = ? OR c.parent_id IN (SELECT id FROM categories WHERE parent_id = ?))
", [(int)$stencilCat['id'], (int)$stencilCat['id'], (int)$stencilCat['id']]) as $r) {
    $os[strtoupper($r['sku'])] = $r;
}

// ── Plan: listings ───────────────────────────────────────────────────────────

/** Shop product index => list of members: USSCOS product id, variant index, thickness key. */
$listings = [];
$joined   = [];
$unplaced = [];

foreach ($os as $key => $r) {
    if (isset($shopSku[$key])) {
        $at = $shopSku[$key];
        $listings[$at['p']][] = ['id' => (int)$r['id'], 'sku' => $r['sku'], 'v' => $at['v'], 't' => $at['t']];
        continue;
    }

    $base = $shopBase[skuBase($key)] ?? null;
    $t    = thicknessOf($key);

    if ($base !== null && $t !== null) {
        $listings[$base['p']][] = ['id' => (int)$r['id'], 'sku' => $r['sku'], 'v' => $base['v'], 't' => $t];
        $joined[] = $r['sku'];
        continue;
    }

    $unplaced[] = $r['sku'];
}

$rows       = [];   // product id => columns to set
$images     = [];   // product id => [[web path, caption, is_listing]]
$need       = [];   // web path => source URL
$heroes     = [];   // category slug => web path
$overwrites = 0;
$longest    = 0;

foreach ($listings as $pi => $members) {
    $p        = $catalog['products'][$pi];
    $variants = $p['variants'];
    $title    = listingTitle($p);
    $desc     = paragraphs((string)($p['description'] ?? ''));

    $thicks = [];

    foreach ($members as $m) {
        $thicks[$m['t']] = true;
    }

    $sizes = count(array_unique(array_column($members, 'v')));

    usort($members, static fn($a, $b) => [$a['v'], THICKNESS[$a['t']]['order'] ?? 99] <=> [$b['v'], THICKNESS[$b['t']]['order'] ?? 99]);

    // The size the shop puts on its own card (app.js stencilVariant): a 28"-tall piece of
    // art, else the middle one with art; else a 12" plain size, else the first.
    $cardV = cardVariant($p);

    $photos     = array_values(array_filter((array)($p['images'] ?? []), 'is_string'));
    $listingSet = false;

    foreach ($members as $order => $m) {
        $v     = $variants[$m['v']];
        $tl    = THICKNESS[$m['t']]['label'] ?? $m['t'];
        $label = $sizes > 1 && count($thicks) > 1 ? $v['label'] . ' · ' . $tl
              : ($sizes > 1 ? (string)$v['label'] : $tl);

        $longest = max($longest, mb_strlen($label));

        $cur = $os[strtoupper($m['sku'])];

        if (trim((string)$cur['website_description']) !== '' && trim((string)$cur['website_description']) !== $desc) {
            $overwrites++;
        }

        $rows[$m['id']] = [
            'variant_group'             => 'stencil:' . $p['id'],
            'variant_label'             => $label,
            'variant_order'             => $order + 1,
            // Set even on a listing of one: an empty axis reads as Thickness to
            // group_stencil_variants.php, which would then regroup it.
            'variant_axis'              => AXIS,
            'variant_title'             => $title,
            'website_name'              => $title,
            'website_short_description' => sizeLine($v, $tl),
            'website_description'       => $desc,
            'website_slug'              => (string)$p['slug'],
            'website_size_slug'         => (string)($v['slug'] ?? ''),
        ];

        // Pictures, in gallery order.
        $pics = [];

        foreach ((array)($v['images'] ?? []) as $u) {
            $pics[] = [photoPath($u), $title . ' — ' . $v['label'], 0];
            $need[photoPath($u)] = SHOP . $u;
        }

        $hasArt = ($p['category'] ?? '') === 'words' || !empty($v['art']);

        if ($hasArt) {
            $path   = WEB_DIR . '/art/' . fileKey($v['key']) . '.png';
            $isCard = !$listingSet && $photos === [] && $m['v'] === $cardV;
            $pics[] = [$path, $title . ' — ' . $v['label'], $isCard ? 1 : 0];
            $need[$path] = SHOP . '/api/stencil/' . rawurlencode($p['id']) . '/' . rawurlencode($v['key']) . '.png';
            $listingSet = $listingSet || $isCard;
        }

        if (!empty($v['width']) && !empty($v['height'])) {
            $path   = WEB_DIR . '/drawings/' . fileKey($v['key']) . '.svg';
            $pics[] = [$path, $title . ' — ' . $v['label'] . ', measurements', 0];
            $need[$path] = SHOP . '/api/drawing/' . rawurlencode($p['id']) . '/' . rawurlencode($v['key']) . '.svg';
        }

        foreach ($photos as $i => $u) {
            $isCard = !$listingSet && $i === 0;
            $pics[] = [photoPath($u), $title, $isCard ? 1 : 0];
            $need[photoPath($u)] = SHOP . $u;
            $listingSet = $listingSet || $isCard;
        }

        $images[$m['id']] = $pics;
    }
}

// The quote listings. One product each, no sizes.
$quoteRows = 0;

foreach ($catalog['products'] as $p) {
    $qsku = QUOTE_SKU[$p['id']] ?? null;

    if ($qsku === null || !isset($os[$qsku])) {
        continue;
    }

    $id    = (int)$os[$qsku]['id'];
    $title = trim((string)$p['name']);
    $quoteRows++;

    $rows[$id] = [
        'variant_group'             => null,
        'variant_label'             => null,
        'variant_order'             => 0,
        'variant_axis'              => null,
        'variant_title'             => null,
        'website_name'              => $title,
        'website_short_description' => '<p>' . siteLinks((string)($p['quoteNote'] ?? '') ?: QUOTE_NOTE) . '</p>',
        'website_description'       => paragraphs((string)($p['description'] ?? '')),
        'website_slug'              => (string)$p['slug'],
        'website_size_slug'         => null,
    ];

    $images[$id] = [];

    foreach (array_values(array_filter((array)($p['images'] ?? []), 'is_string')) as $u) {
        $images[$id][] = [photoPath($u), $title, 0];
        $need[photoPath($u)] = SHOP . $u;
    }
}

// Category copy, keyed by USSCOS category slug.
$catCopy = [];

foreach ((array)($catalog['categoryCopy'] ?? []) as $key => $html) {
    $catCopy['stencils-' . $key] = siteLinks((string)$html);
}

$catCopy['stencils'] = stencilsLanding((array)($catalog['homeCopy'] ?? []));

// Content pages.
$pages = [];

foreach ((array)($catalog['pages'] ?? []) as $slug => $pg) {
    $pages[$slug] = [(string)$pg['title'], (string)($pg['description'] ?? ''), siteLinks((string)$pg['html'])];
}

$pages['stencil-materials'] = ['Stencil Materials & Sizing',
    'Which stencil plastic to choose, how stencil sizes are measured, curb stencils and multi-piece stencils.',
    MATERIALS_PAGE];

// Call-for-quote listings (endzone, rooftop …) have no SKU, but their photos make the
// category pages they stand for.
foreach ($catalog['products'] as $p) {
    $slug = HERO[$p['category'] ?? ''] ?? null;

    if ($slug !== null && !isset($heroes[$slug]) && !empty($p['images'][0])) {
        $heroes[$slug] = photoPath($p['images'][0]);
        $need[photoPath($p['images'][0])] = SHOP . $p['images'][0];
    }
}

if ($longest > 50) {
    fwrite(STDERR, "Refusing: a variant label is {$longest} characters; the column holds 50.\n");
    exit(1);
}

$existing = Database::selectOne(
    "SELECT COUNT(*) n FROM product_images WHERE file_path LIKE ?", [WEB_DIR . '/%']
)['n'];

$handMade = Database::select(
    "SELECT p.sku, COUNT(*) n FROM product_images pi JOIN products p ON p.id = pi.product_id
     WHERE pi.file_path NOT LIKE ? AND pi.product_id IN (" . implode(',', array_keys($rows) ?: [0]) . ")
     GROUP BY p.id",
    [WEB_DIR . '/%']
);

$missing = array_filter(array_keys($need), static fn($w) => !is_file(BASE_PATH . '/public' . $w));

// ── Report ───────────────────────────────────────────────────────────────────

printf("Shop: %d products, %d SKUs.  USSCOS: %d active stencils.\n\n", count($catalog['products']), count($shopSku), count($os));
printf("Listings           %d  (from %d SKUs; longest picker label %d chars)\n", count($listings), count($rows), $longest);
printf("Joined a listing   %d  USSCOS-only thickness of a size the shop sells: %s\n", count($joined), implode(', ', $joined));
printf("Left as they are   %d  not on the shop: %s\n", count($unplaced), implode(', ', $unplaced));
printf("Descriptions       %d  replace a different description already in USSCOS\n", $overwrites);
printf("Image rows         %d  (%d now under %s, replaced)\n", array_sum(array_map('count', $images)), $existing, WEB_DIR);
printf("Card pictures      %d  of %d listings\n", count(array_filter($images, static fn($p) => in_array(1, array_column($p, 2), true))), count($listings));
printf("Category heroes    %d  %s\n", count($heroes), implode(', ', array_keys($heroes)));
printf("Quote listings     %d\n", $quoteRows);
printf("Category copy      %d  %s\n", count($catCopy), implode(', ', array_keys($catCopy)));
printf("Pages              %d  %s\n", count($pages), implode(', ', array_keys($pages)));

$outside = [];

foreach ([...array_values($catCopy), ...array_column($pages, 2), ...array_column($rows, 'website_short_description')] as $html) {
    // sales@usscproducts.com is the company's address, not another site: the @ excuses it.
    if (preg_match_all('#(?:href|src)="(https?:)?//[^"]*"|(?<!@)usscproducts\.com|stencilsplus#i', (string)$html, $m)) {
        array_push($outside, ...$m[0]);
    }
}

if ($outside !== []) {
    fwrite(STDERR, "Refusing: copy still points at another site: " . implode(', ', array_unique($outside)) . "\n");
    exit(1);
}
printf("Files              %d  (%d to fetch: %d photos, %d stencil pictures, %d drawings)\n",
    count($need), count($missing),
    count(array_filter($missing, static fn($w) => str_contains($w, '/photos/'))),
    count(array_filter($missing, static fn($w) => str_contains($w, '/art/'))),
    count(array_filter($missing, static fn($w) => str_contains($w, '/drawings/'))));

if ($handMade !== []) {
    printf("Kept, uploaded by hand: %s\n", implode(', ', array_map(static fn($r) => "{$r['sku']} ({$r['n']})", $handMade)));
}

echo "\nSample:\n";

foreach (array_slice($rows, 0, 3, true) as $id => $r) {
    printf("  #%d  %s  [%s]  %s\n", $id, $r['website_name'], $r['variant_label'], strip_tags($r['website_short_description']));

    foreach ($images[$id] as $im) {
        printf("        %s%s\n", $im[0], $im[2] ? '   ← card' : '');
    }
}

if (!$commit) {
    echo "\nNothing fetched or written. Add --commit to apply.\n";
    exit(0);
}

// ── Fetch pictures (outside the transaction: files are idempotent) ───────────

$failed = [];
$n = 0;

foreach ($missing as $web) {
    $n++;
    $body = fetch($need[$web], 90);
    usleep(PAUSE_US);

    $problem = $body === null ? 'fetch failed' : checkFile($web, $body);

    if ($problem !== null) {
        $failed[$web] = $problem;
        continue;
    }

    $file = BASE_PATH . '/public' . $web;

    if (!is_dir(dirname($file)) && !mkdir(dirname($file), 0775, true)) {
        fwrite(STDERR, "Cannot create " . dirname($file) . "\n");
        exit(1);
    }

    file_put_contents($file . '.tmp', $body);
    rename($file . '.tmp', $file);

    if ($n % 100 === 0) {
        printf("  fetched %d of %d\n", $n, count($missing));
    }
}

printf("\nFetched %d file(s), %d failed.\n", count($missing) - count($failed), count($failed));

foreach ($failed as $web => $why) {
    printf("  %-60s %s\n", $web, $why);
}

// ── Write ────────────────────────────────────────────────────────────────────

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    $upd = $pdo->prepare(
        "UPDATE products SET variant_group = ?, variant_label = ?, variant_order = ?, variant_axis = ?,
                variant_title = ?, website_name = ?, website_short_description = ?, website_description = ?,
                website_slug = ?, website_size_slug = ?
         WHERE id = ?"
    );

    $clear = $pdo->prepare("DELETE FROM product_images WHERE product_id = ? AND file_path LIKE ?");
    $img   = $pdo->prepare(
        "INSERT INTO product_images (product_id, file_path, file_name, caption, is_primary, is_listing, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    $imageRows = 0;

    // A photo somebody uploaded by hand stays first: it was chosen, these were generated.
    $handIds = array_flip(array_map('intval', array_column(Database::select(
        "SELECT DISTINCT product_id FROM product_images WHERE file_path NOT LIKE ?", [WEB_DIR . '/%']
    ), 'product_id')));

    foreach ($rows as $id => $r) {
        $upd->execute([...array_values($r), $id]);
        $clear->execute([$id, WEB_DIR . '/%']);

        $sort = isset($handIds[$id]) ? 100 : 0;

        foreach ($images[$id] as [$web, $caption, $isListing]) {
            if (isset($failed[$web]) || !is_file(BASE_PATH . '/public' . $web)) {
                continue;
            }

            $img->execute([$id, $web, basename($web), mb_substr($caption, 0, 255), $sort === 0 ? 1 : 0, $isListing, $sort]);
            $sort++;
            $imageRows++;
        }
    }

    $hero = $pdo->prepare("UPDATE categories SET image_path = ? WHERE slug = ? AND (image_path IS NULL OR image_path = '' OR image_path LIKE ?)");

    foreach ($heroes as $slug => $web) {
        if (!isset($failed[$web])) {
            $hero->execute([$web, $slug, WEB_DIR . '/%']);
        }
    }

    $copy = $pdo->prepare("UPDATE categories SET website_copy = ? WHERE slug = ?");

    foreach ($catCopy as $slug => $html) {
        $copy->execute([$html, $slug]);
    }

    $page = $pdo->prepare(
        "INSERT INTO site_pages (slug, title, description, html) VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description), html = VALUES(html)"
    );

    foreach ($pages as $slug => [$t, $d, $h]) {
        $page->execute([$slug, $t, $d ?: null, $h]);
    }

    $pdo->commit();
    printf("\nWrote %d listing row(s) and %d image row(s); %d category hero(es), %d category copy, %d page(s).\n",
        count($rows), $imageRows, count($heroes), count($catCopy), count($pages));
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fwrite(STDERR, "\nRolled back, nothing written to the database: " . $e->getMessage() . "\n");
    exit(1);
}

// ── Helpers ──────────────────────────────────────────────────────────────────

function fetch(string $url, int $timeout = 30): ?string
{
    $ctx  = stream_context_create(['http' => ['timeout' => $timeout, 'user_agent' => 'USSCOS stencil import', 'ignore_errors' => true]]);
    $body = @file_get_contents($url, false, $ctx);

    $status = 0;

    foreach ($http_response_header ?? [] as $h) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) {
            $status = (int)$m[1];
        }
    }

    return ($body === false || $status !== 200 || $body === '') ? null : $body;
}

/** Null when the file is what its name says it is; otherwise why not. */
function checkFile(string $web, string $body): ?string
{
    $ext = strtolower(pathinfo($web, PATHINFO_EXTENSION));

    if ($ext === 'svg') {
        if (!preg_match('/^\s*(<\?xml[^>]*>\s*)?(<!--.*?-->\s*)*<svg[\s>]/s', $body)) {
            return 'not an SVG';
        }

        if (preg_match('/<script|<foreignObject|\son[a-z]+\s*=|javascript:|(?:xlink:)?href\s*=\s*["\'](?!#)/i', $body)) {
            return 'SVG carries script or an external link — refused';
        }

        return null;
    }

    $info = @getimagesizefromstring($body);

    if ($info === false) {
        return 'not an image';
    }

    $want = ['png' => IMAGETYPE_PNG, 'jpg' => IMAGETYPE_JPEG, 'jpeg' => IMAGETYPE_JPEG, 'webp' => IMAGETYPE_WEBP];

    return isset($want[$ext]) && $info[2] === $want[$ext] ? null : 'type does not match .' . $ext;
}

/** '/uploads/9fcf….jpg' → '/uploads/stencils/photos/9fcf….jpg'. The shop's names are random hex. */
function photoPath(string $shopUrl): string
{
    $name = basename(parse_url($shopUrl, PHP_URL_PATH) ?: $shopUrl);
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        $ext = 'jpg';
    }

    return WEB_DIR . '/photos/' . preg_replace('/[^a-z0-9]/i', '', pathinfo($name, PATHINFO_FILENAME)) . '.' . $ext;
}

/** A variant key as a safe filename: 'STNUM-18x12-8' stays, anything odd becomes '_'. */
function fileKey(string $key): string
{
    return preg_replace('/[^A-Za-z0-9._-]/', '_', $key);
}

/** 'ST-STOPBAR-18x8-32' → 'ST-STOPBAR-18X8'. Matches the sync script's rule. */
function skuBase(string $sku): string
{
    return (string)preg_replace('/-(8|32|16)(?=$|-C$)/', '', strtoupper($sku));
}

function thicknessOf(string $sku): ?string
{
    return match (true) {
        (bool)preg_match('/-32(-C)?$/i', $sku) => '3/32',
        (bool)preg_match('/-8(-C)?$/i', $sku)  => '1/8',
        (bool)preg_match('/-16(-C)?$/i', $sku) => '1/16',
        default                                => null,
    };
}

/** The shop's own title rule (seo.stencil_name), with the chain in front of a retail stencil. */
function listingTitle(array $p): string
{
    $name  = trim((string)$p['name']);
    $brand = trim((string)($p['brand'] ?? ''));

    if ($brand !== '' && ($p['category'] ?? '') === 'retail' && stripos($name, $brand) === false) {
        $name = $brand . ' ' . $name;
    }

    return preg_match('/stencils?$/i', $name) ? $name : $name . ' Stencil';
}

/** Plain shop text → sanitised-looking HTML: escaped, one <p> per blank-line paragraph. */
function paragraphs(string $text): string
{
    $parts = preg_split('/\R\s*\R/', trim($text)) ?: [];

    return implode("\n", array_map(
        static fn($p) => '<p>' . nl2br(htmlspecialchars(trim($p), ENT_QUOTES | ENT_HTML5, 'UTF-8'), false) . '</p>',
        array_filter($parts, static fn($p) => trim($p) !== '')
    ));
}

/** '28" wide × 4" tall · 1 piece · 1/8" thick'. */
function sizeLine(array $v, string $thick): string
{
    $bits = [];

    if (!empty($v['width']) && !empty($v['height'])) {
        $bits[] = $v['width'] . ' wide × ' . $v['height'] . ' tall';
    } else {
        $bits[] = (string)$v['label'];
    }

    if ((int)($v['pieces'] ?? 1) > 1) {
        $bits[] = (int)$v['pieces'] . ' pieces';
    }

    if (!empty($v['curb'])) {
        $bits[] = 'sized for a 6" curb face';
    }

    $bits[] = in_array($thick, ['1/8"', '3/32"', '1/16"'], true) ? $thick . ' thick' : $thick;

    return '<p>' . htmlspecialchars(implode(' · ', $bits), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</p>';
}

/** Index of the variant the shop shows on its card (public/app.js stencilVariant). */
function cardVariant(array $p): int
{
    $vs  = $p['variants'];
    $art = array_keys(array_filter($vs, static fn($v) => !empty($v['art'])));

    if ($art !== []) {
        foreach ($art as $i) {
            if ((float)$vs[$i]['height'] === 28.0) {
                return $i;
            }
        }

        return $art[intdiv(count($art) - 1, 2)];
    }

    $plain = array_keys(array_filter($vs, static fn($v) => empty($v['curb']) && !empty($v['width']) && !empty($v['height'])));

    foreach ($plain as $i) {
        if ((float)$vs[$i]['height'] === 12.0) {
            return $i;
        }
    }

    return $plain[0] ?? 0;
}

/**
 * The shop's links, as addresses on this site. Anything left pointing elsewhere is
 * refused before writing (see the check after the plan).
 *
 *   /shop, /shop/words           → /shop/stencils, /shop/stencils/words
 *   /brand/mcdonalds             → /shop/stencils/retail-mcdonalds
 *   /custom/endzone, /midfield   → the endzone and midfield quote listings, until the
 *                                  designers are built here (docs/STENCIL_SHOP_PORT.md)
 *   /custom                      → the contact form, until the custom stencil builder is
 *   /about, /returns             → /stencil-materials, /returns
 *   usscproducts.com paint pages → this site's paint sections
 */
function siteLinks(string $html): string
{
    // Order matters. The paint links are rewritten LAST: done first, the /shop/x rule
    // would then turn /shop/field-marking-paint into a stencil category.
    $map = [
        '#href="/shop"#'                         => 'href="/shop/stencils"',
        '#href="/shop/([a-z0-9-]+)"#'            => 'href="/shop/stencils/$1"',
        '#href="/brand/([a-z0-9-]+)"#'           => 'href="/shop/stencils/retail-$1"',
        '#href="/custom/endzone"#'               => 'href="/stencils/endzone-stencils"',
        '#href="/custom/midfield"#'              => 'href="/stencils/midfield-logo-stencil"',
        '#href="/custom"#'                       => 'href="/contact?about=Custom%20stencil"',
        '#href="/about"#'                        => 'href="/stencil-materials"',
        '#href="https?://(?:www\.)?usscproducts\.com/product-category/field-marking-paints/?"#' => 'href="/shop/field-marking-paint"',
        '#href="https?://(?:www\.)?usscproducts\.com/product-category/traffic-paints/?"#'       => 'href="/shop/traffic-paint"',
        '#href="https?://(?:www\.)?usscproducts\.com/?"#'                                       => 'href="/shop/stencils"',
    ];

    $html = (string)preg_replace(array_keys($map), array_values($map), $html);

    // A sentence that names the old address as text rather than linking it.
    return str_replace('Shop them at usscproducts.com', 'Shop them <a href="/shop">here</a>', $html);
}

/** The Stencils landing copy, from the shop's home page: the about block and the FAQ. */
function stencilsLanding(array $hc): string
{
    $phone = (string)(config('site.company.phone') ?? '');
    $about = (array)($hc['about'] ?? []);
    $out   = '';

    if (!empty($about['title'])) {
        $out .= '<h2>' . htmlspecialchars((string)$about['title']) . '</h2>';
    }

    if (!empty($about['lead'])) {
        $out .= '<p class="lead">' . htmlspecialchars((string)$about['lead']) . '</p>';
    }

    foreach ((array)($about['sections'] ?? []) as $sec) {
        $href = siteLinks('href="' . ($sec['href'] ?? '') . '"');
        $out .= '<h3><a ' . $href . '>' . htmlspecialchars((string)$sec['title']) . '</a></h3>'
              . '<p>' . siteLinks((string)$sec['html']) . '</p>';
    }

    if (!empty($hc['faq'])) {
        $out .= '<h2>Frequently asked questions</h2>';

        foreach ((array)$hc['faq'] as [$q, $a]) {
            $a = str_replace('{phone}', $phone, htmlspecialchars((string)$a));
            $out .= '<h3>' . htmlspecialchars((string)$q) . '</h3><p>' . siteLinks($a) . '</p>';
        }
    }

    return $out;
}

/**
 * The shop's Materials & Sizing page. On the shop it is written into the page code, not
 * the catalog, so it is carried here as text, with the shop's name taken out.
 */
const MATERIALS_PAGE = <<<'HTML'
<h1>Stencil Materials &amp; Sizing</h1>
<p>Every US Specialty Coatings stencil is CNC-cut from durable, reusable plastic sheet so you get clean, consistent lines job after job.</p>
<h2>Choosing a material</h2>
<table>
  <thead><tr><th>Material</th><th>Best for</th></tr></thead>
  <tbody>
    <tr><td><strong>1/8" Heavy-Duty</strong></td><td>Contractors and frequent reuse. Stays flat in wind and stands up to rough handling.</td></tr>
    <tr><td><strong>3/32" Standard</strong></td><td>Occasional use and lower cost. Lighter to ship and easier to roll for storage.</td></tr>
    <tr><td><strong>10-mil Polyethylene</strong></td><td>Very large layouts such as football field letters and awareness ribbons. Foldable and reusable, so it folds up small to ship and store.</td></tr>
  </tbody>
</table>
<h2>Reading our sizes</h2>
<p><strong>Word stencils</strong> are listed by letter height. The overall stencil width is shown on each product page. <strong>Kits</strong> list letter height and character width (e.g. 18" × 9"). <strong>Symbols and arrows</strong> are listed by overall length.</p>
<h2>Curb stencils</h2>
<p>Items marked <em>curb</em> use 4" letters placed 1" above the bottom edge of the stencil. Line the bottom of the stencil up with the bottom of the curb and the letters sit centered on a standard 6" curb face.</p>
<h2>Multi-piece stencils</h2>
<p>Very large stencils are cut in multiple pieces so they can ship and be handled on site. The piece count is listed on each product page.</p>
HTML;
