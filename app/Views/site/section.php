<?php ob_start(); $activeSlug = $section['slug']; ?>

<div class="wrap" style="padding-top:1.5rem">
  <div style="font-size:.8rem;color:var(--mut);margin-bottom:1rem">
    <a href="/shop">Home</a> &nbsp;/&nbsp; <?= e($section['label']) ?>
  </div>
  <h1 style="margin:0 0 .4rem;font-size:1.9rem;letter-spacing:-.025em"><?= e($section['label']) ?></h1>
  <p style="margin:0 0 1.5rem;color:var(--mut);max-width:62ch"><?= e($section['blurb'] ?? '') ?></p>
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
        <?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?>
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
