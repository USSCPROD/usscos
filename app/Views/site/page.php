<?php ob_start(); ?>
<div class="wrap sec" style="max-width:720px">
  <h1 style="margin:0 0 .5rem;font-size:1.9rem;letter-spacing:-.025em"><?= e($heading) ?></h1>
  <?php if ($blurb !== ''): ?>
    <p style="color:var(--mut);margin:0 0 1.75rem"><?= e($blurb) ?></p>
  <?php endif; ?>
  <div class="note">
    This page is part of the shell, not written yet. It exists so the navigation is
    complete and nothing in the design review dead-ends on a 404.
    <?php if (!empty($sku)): ?>
      <br><br>Arrived here from product <strong><?= e($sku) ?></strong> — the quote form
      will carry that through.
    <?php endif; ?>
  </div>
  <a class="b b-s" href="/shop">Back to the shop</a>
</div>
<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/site.php';
