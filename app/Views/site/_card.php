<?php
/**
 * One product card. A partial because the homepage, every section page and search
 * all show the same thing, and three copies of it would drift apart within a month.
 *
 * $p is one row from StorefrontRepository, which never selects cost.
 */
$price = (float)($p['website_price'] ?? 0);
$url   = \App\Repositories\StorefrontRepository::urlFor($p);
// A listing of sizes (a stencil) is priced 'From' its cheapest. A listing of thicknesses
// or colors is not: there the card's own variant is the one people buy.
$sized = strcasecmp((string)($p['variant_axis'] ?? ''), 'Size & thickness') === 0 && (int)($p['variant_count'] ?? 1) > 1;
$from  = $sized && !empty($p['min_price']) ? (float)$p['min_price'] : null;
?>
<div class="card">
    <?php $img = \App\Repositories\StorefrontRepository::imageFor($p); ?>
    <a href="<?= e($url) ?>" style="display:block;text-decoration:none" tabindex="-1" aria-hidden="true">
    <?php if ($img !== null): ?>
        <img src="<?= e($img) ?>" alt="<?= e($p['name']) ?>"
             style="aspect-ratio:4/3;object-fit:contain;background:#fff;border-bottom:1px solid var(--line);width:100%;display:block" loading="lazy"
             onerror="this.outerHTML='&lt;div class=\'ph\'&gt;No photo yet&lt;/div&gt;'">
    <?php else: ?>
        <div class="ph">No photo yet</div>
    <?php endif; ?>
    </a>
    <div class="card-b">
        <h3><a href="<?= e($url) ?>"><?= e(\App\Repositories\StorefrontRepository::listingName($p)) ?></a></h3>
        <div class="sku"><?= e($p['sku']) ?><?php if (!empty($p['brand_name'])): ?> · <?= e($p['brand_name']) ?><?php endif; ?></div>
        <?php if (!empty($p['short_description'])): ?>
            <p class="desc"><?= e($p['short_description']) ?></p>
        <?php endif; ?>
        <?php if ($price > 0): ?>
            <!--
                No 'from'. The card leads with 1/8", the thickest and dearest cut, so
                'from' would promise a price below the one printed next to it.
            -->
            <?php if ($from !== null): ?>
                <div class="price"><small style="font-size:.72rem;font-weight:600;color:var(--mut)">From</small> <?= money($from) ?></div>
                <div style="font-size:.74rem;color:var(--mut);margin-top:.2rem"><?= (int)$p['variant_count'] ?> sizes &amp; thicknesses</div>
            <?php else: ?>
            <div class="price"><?= money($price) ?></div>
            <?php endif; ?>
            <?php if (!$sized && (int)($p['variant_count'] ?? 1) > 1): ?>
                <?php
                    // 'thicknesses' was hardcoded when stencils were the only grouped
                    // thing. A card for paint read '7 thicknesses' over a row of colors.
                    $axis  = strtolower(trim((string)($p['variant_axis'] ?? '')));
                    $plural = match ($axis) {
                        'color'     => 'colors',
                        'thickness' => 'thicknesses',
                        ''          => 'options',
                        default     => $axis . 's',
                    };
                ?>
                <div style="font-size:.74rem;color:var(--mut);margin-top:.2rem">
                    <?= e($p['variant_label'] ?? '') ?>
                    &nbsp;·&nbsp; <?= (int)$p['variant_count'] ?> <?= e($plural) ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="callfor">Call or email for best price</div>
        <?php endif; ?>
    </div>
</div>
