<?php ob_start(); $activeSlug = $section['slug']; $parent = $parent ?? null; $groups = $groups ?? []; ?>

<div class="wrap" style="padding-top:1.5rem">
  <div style="font-size:.8rem;color:var(--mut);margin-bottom:1rem">
    <a href="/shop">Home</a> &nbsp;/&nbsp;
    <?php if ($active !== null): ?>
      <a href="/shop/<?= e($section['slug']) ?>"><?= e($section['label']) ?></a> &nbsp;/&nbsp;
      <?php if ($parent !== null): ?>
        <a href="/shop/<?= e($section['slug']) ?>/<?= e($parent['slug']) ?>"><?= e($parent['label']) ?></a> &nbsp;/&nbsp;
      <?php endif; ?>
      <?= e($active['label']) ?>
    <?php else: ?>
      <?= e($section['label']) ?>
    <?php endif; ?>
  </div>
  <h1 style="margin:0 0 .4rem;font-size:1.9rem;letter-spacing:-.025em">
    <?= e($active !== null ? $active['label'] : $section['label']) ?>
  </h1>
  <p style="margin:0 0 1.25rem;color:var(--mut);max-width:62ch"><?= e($section['blurb'] ?? '') ?></p>

  <?php
    // Chips: the section's children on its landing page and on a child's page; on a
    // grandchild's page (a chain, Endzone) its siblings, so moving between them is one
    // click. A child that has its own children shows those beneath.
    $chipBase = '/shop/' . $section['slug'];
    $chips    = $children;
    $subChips = [];

    if ($active !== null && $parent !== null) {
        $subChips = $parent['children'] ?? [];
    } elseif ($active !== null && !empty($active['children']) && $groups === []) {
        $subChips = $active['children'];
    }
  ?>
  <?php if (!empty($chips)): ?>
    <!--
        Links, not tabs. Each subcategory has its own URL so it can be linked and
        indexed; 'All' returns to the whole section rather than being a seventh
        filter that happens to match everything.
    -->
    <div style="display:flex;gap:.45rem;flex-wrap:wrap;margin-bottom:<?= $subChips !== [] ? '.6rem' : '1.75rem' ?>">
      <a href="<?= e($chipBase) ?>"
         style="padding:.45rem .85rem;border-radius:999px;font-size:.84rem;font-weight:600;text-decoration:none;
                border:1px solid <?= $active === null ? 'var(--blue)' : 'var(--line)' ?>;
                background:<?= $active === null ? 'var(--blue)' : '#fff' ?>;
                color:<?= $active === null ? '#fff' : 'var(--ink)' ?>">All</a>
      <?php foreach ($chips as $c): ?>
        <?php $on = $active !== null && ($active['slug'] === $c['slug'] || ($parent !== null && $parent['slug'] === $c['slug'])); ?>
        <a href="<?= e($chipBase . '/' . $c['slug']) ?>"
           style="padding:.45rem .85rem;border-radius:999px;font-size:.84rem;font-weight:600;text-decoration:none;
                  border:1px solid <?= $on ? 'var(--blue)' : 'var(--line)' ?>;
                  background:<?= $on ? 'var(--blue)' : '#fff' ?>;
                  color:<?= $on ? '#fff' : 'var(--ink)' ?>">
          <?= e($c['label']) ?>
          <span style="opacity:.65;font-weight:500">&nbsp;<?= (int)$c['count'] ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($subChips !== []): ?>
    <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1.75rem;padding-left:.25rem">
      <?php foreach ($subChips as $c): ?>
        <?php $on = $active !== null && $active['slug'] === $c['slug']; ?>
        <a href="<?= e($chipBase . '/' . $c['slug']) ?>"
           style="padding:.35rem .75rem;border-radius:999px;font-size:.8rem;font-weight:600;text-decoration:none;
                  border:1px solid <?= $on ? 'var(--blue)' : 'var(--line)' ?>;
                  background:<?= $on ? '#eff4ff' : '#fff' ?>;color:<?= $on ? 'var(--blue)' : 'var(--ink)' ?>">
          <?= e($c['label']) ?> <span style="opacity:.65;font-weight:500">&nbsp;<?= (int)$c['count'] ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="wrap" style="padding-bottom:3rem">
  <?php if ($groups !== []): ?>
    <!-- One card per chain: the category's listings all sit in these. -->
    <div class="grid g4">
      <?php foreach ($groups as $g): ?>
        <div class="card">
          <?php if (($g['image'] ?? '') !== ''): ?>
            <img src="<?= e($g['image']) ?>" alt="" loading="lazy"
                 style="aspect-ratio:4/3;object-fit:cover;border-bottom:1px solid var(--line)">
          <?php else: ?>
            <div class="ph" style="font-weight:800;font-size:1.2rem;color:var(--navy)"><?= e(preg_replace('/ Stencils$/', '', $g['label'])) ?></div>
          <?php endif; ?>
          <div class="card-b">
            <h3><a href="<?= e('/shop/' . $section['slug'] . '/' . $g['slug']) ?>"><?= e($g['label']) ?></a></h3>
            <div style="font-size:.8rem;color:var(--mut)"><?= (int)$g['count'] ?> stencil<?= (int)$g['count'] === 1 ? '' : 's' ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php elseif (empty($products)): ?>
    <div class="note">
      Nothing is filed under this section yet. It draws from the internal categories
      <strong><?= e(implode(', ', $section['categories'] ?? [])) ?></strong> —
      either no products carry those, or the section needs pointing somewhere else in
      <code>config/site.php</code>.
    </div>
  <?php else: ?>
    <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:1rem">
      <div style="font-size:.85rem;color:var(--mut)">
        <?php if ($total > count($products)): ?>
          Showing <?= count($products) ?> of <?= (int)$total ?> products
        <?php else: ?>
          <?= (int)$total ?> product<?= (int)$total === 1 ? '' : 's' ?>
        <?php endif; ?>
      </div>
    </div>
    <?php
      // The designers lead these pages, as they did on the shop.
      $designers = $section['slug'] === 'stencils' && $active !== null
          ? (['sports' => ['endzone', 'midfield'], 'endzone' => ['endzone'], 'midfield' => ['midfield']][$active['slug']] ?? [])
          : [];
    ?>
    <div class="grid g4">
      <?php foreach ($designers as $dk): ?><?= view('site._designer_box', ['kind' => $dk, 'card' => true]) ?><?php endforeach; ?>
      <?php foreach ($products as $p): ?><?= view('site._card', ['p' => $p]) ?><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($copy)): ?>
    <!-- The category's long copy. Written by us (categories.website_copy), so printed as HTML. -->
    <section class="rt" style="margin-top:3rem;padding-top:2rem;border-top:1px solid var(--line);max-width:62rem;font-size:.95rem;color:#374151;line-height:1.7">
      <?= $copy ?>
    </section>
  <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/site.php';
