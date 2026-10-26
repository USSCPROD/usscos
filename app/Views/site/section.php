<?php ob_start(); $activeSlug = $section['slug']; ?>

<div class="wrap" style="padding-top:1.5rem">
  <div style="font-size:.8rem;color:var(--mut);margin-bottom:1rem">
    <a href="/shop">Home</a> &nbsp;/&nbsp;
    <?php if ($active !== null): ?>
      <a href="/shop/<?= e($section['slug']) ?>"><?= e($section['label']) ?></a> &nbsp;/&nbsp; <?= e($active['label']) ?>
    <?php else: ?>
      <?= e($section['label']) ?>
    <?php endif; ?>
  </div>
  <h1 style="margin:0 0 .4rem;font-size:1.9rem;letter-spacing:-.025em">
    <?= e($active !== null ? $active['label'] : $section['label']) ?>
  </h1>
  <p style="margin:0 0 1.25rem;color:var(--mut);max-width:62ch"><?= e($section['blurb'] ?? '') ?></p>

  <?php if (!empty($children)): ?>
    <!--
        Links, not tabs. Each subcategory has its own URL so it can be linked and
        indexed; 'All' returns to the whole section rather than being a seventh
        filter that happens to match everything.
    -->
    <div style="display:flex;gap:.45rem;flex-wrap:wrap;margin-bottom:1.75rem">
      <a href="/shop/<?= e($section['slug']) ?>"
         style="padding:.45rem .85rem;border-radius:999px;font-size:.84rem;font-weight:600;text-decoration:none;
                border:1px solid <?= $active === null ? 'var(--blue)' : 'var(--line)' ?>;
                background:<?= $active === null ? 'var(--blue)' : '#fff' ?>;
                color:<?= $active === null ? '#fff' : 'var(--ink)' ?>">All</a>
      <?php foreach ($children as $c): ?>
        <?php $on = $active !== null && $active['slug'] === $c['slug']; ?>
        <a href="/shop/<?= e($section['slug']) ?>/<?= e($c['slug']) ?>"
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
</div>

<div class="wrap" style="padding-bottom:3rem">
  <?php if (empty($products)): ?>
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
    <div class="grid g4">
      <?php foreach ($products as $p): ?><?= view('site._card', ['p' => $p]) ?><?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/site.php';
