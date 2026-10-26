<?php ob_start(); ?>
<div class="wrap sec" style="text-align:center;padding:5rem 20px">
  <h1 style="margin:0 0 .6rem;font-size:2rem;letter-spacing:-.025em">Page not found</h1>
  <p style="color:var(--mut);margin:0 0 1.75rem">
    That page has moved or never existed. Try a category above, or search.
  </p>
  <a class="b b-p" href="/shop">Back to the shop</a>
</div>
<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/site.php';
