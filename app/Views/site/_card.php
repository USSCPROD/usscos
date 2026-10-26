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
    <?php $img = \App\Repositories\StorefrontRepository::imageFor($p); ?>
    <?php if ($img !== null): ?>
        <img src="<?= e($img) ?>" alt="<?= e($p['name']) ?>"
             style="aspect-ratio:4/3;object-fit:contain;background:#fff;border-bottom:1px solid var(--line)" loading="lazy"
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
            <!--
                No 'from'. The card leads with 1/8", the thickest and dearest cut, so
                'from' would promise a price below the one printed next to it.
            -->
            <div class="price"><?= money($price) ?></div>
            <?php if ((int)($p['variant_count'] ?? 1) > 1): ?>
                <div style="font-size:.74rem;color:var(--mut);margin-top:.2rem">
                    <?= e($p['variant_label'] ?? '') ?>
                    &nbsp;·&nbsp; <?= (int)$p['variant_count'] ?> thicknesses
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="callfor">Call or email for best price</div>
        <?php endif; ?>
    </div>
</div>
