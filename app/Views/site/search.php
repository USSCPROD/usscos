<?php ob_start(); ?>
<div class="wrap sec">
  <h1 style="margin:0 0 .4rem;font-size:1.7rem;letter-spacing:-.025em">
    <?= $q === '' ? 'Search' : 'Results for &ldquo;' . e($q) . '&rdquo;' ?>
  </h1>
  <p style="margin:0 0 1.75rem;color:var(--mut)">
    <?php if ($q !== ''): ?>
      <?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?> found
    <?php else: ?>
      Search by product name, SKU or description.
    <?php endif; ?>
  </p>

  <?php if ($q !== '' && empty($products)): ?>
    <div class="note">
      Nothing matched. Try a shorter term, or
      <a href="/quote">ask us</a> — we make a great deal that is not listed.
    </div>
  <?php elseif (!empty($products)): ?>
    <div class="grid g4">
      <?php foreach ($products as $p): ?><?= view('site._card', ['p' => $p]) ?><?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/site.php';
