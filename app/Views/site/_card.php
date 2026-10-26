<?php
/**
 * One product card. A partial because the homepage, every section page and search
 * all show the same thing, and three copies of it would drift apart within a month.
 *
 * $p is one row from StorefrontRepository, which never selects cost.
 */
$price = (float)($p['website_price'] ?? 0);
?>
<div class="card">
    <?php if (\App\Repositories\StorefrontRepository::isImageUrl($p['website_image_url'] ?? null)): ?>
        <img src="<?= e($p['website_image_url']) ?>" alt="<?= e($p['name']) ?>"
             style="aspect-ratio:4/3;object-fit:cover;border-bottom:1px solid var(--line)" loading="lazy"
             onerror="this.outerHTML='&lt;div class=\'ph\'&gt;No photo yet&lt;/div&gt;'">
    <?php else: ?>
        <div class="ph">No photo yet</div>
    <?php endif; ?>
    <div class="card-b">
        <h3><a href="/product/<?= e(rawurlencode((string)$p['sku'])) ?>"><?= e(\App\Repositories\StorefrontRepository::listingName($p)) ?></a></h3>
        <div class="sku"><?= e($p['sku']) ?><?php if (!empty($p['brand_name'])): ?> · <?= e($p['brand_name']) ?><?php endif; ?></div>
        <?php if (!empty($p['short_description'])): ?>
            <p class="desc"><?= e($p['short_description']) ?></p>
        <?php endif; ?>
        <?php if ($price > 0): ?>
            <div class="price">
                <?php if ((int)($p['variant_count'] ?? 1) > 1): ?>
                    <span style="font-size:.72rem;font-weight:600;color:var(--mut);text-transform:uppercase;letter-spacing:.05em">from</span>
                <?php endif; ?>
                <?= money($price) ?>
            </div>
            <?php if ((int)($p['variant_count'] ?? 1) > 1): ?>
                <div style="font-size:.74rem;color:var(--mut);margin-top:.2rem">
                    <?= (int)$p['variant_count'] ?> thicknesses
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="callfor">Call or email for best price</div>
        <?php endif; ?>
    </div>
</div>
