<?php ob_start(); $activeSlug = 'stencils'; ?>
<?= view('site._designer_parts', ['part' => 'styles']) ?>
<div class="wrap sec">
  <h1 style="margin:0 0 1.25rem;font-size:1.9rem;letter-spacing:-.025em">Your cart</h1>
  <?php if (!empty($sandbox) && !empty($enabled)): ?>
    <div class="note" style="margin-bottom:1rem">Checkout is in <strong>test mode</strong> (Square sandbox): no card is really charged.</div>
  <?php endif; ?>
  <!-- Drawn by public/assets/js/cart.js from this browser's cart, priced by the server. -->
  <?php if (!empty($login) && empty($trade) && empty($login['customer_id'])): ?>
    <div class="note" style="margin-bottom:1rem">Signed in as <?= e($login['email']) ?>. We're setting up your account with
      your company's pricing; until then you'll see list prices.</div>
  <?php endif; ?>
  <div id="cart-page" data-enabled="<?= !empty($enabled) ? '1' : '0' ?>" data-trade="<?= !empty($trade) ? '1' : '0' ?>"
       data-signed-in="<?= !empty($login) ? '1' : '0' ?>"><p class="dz-sub">Loading your cart…</p></div>
</div>
<style>@media (max-width:860px){#cart-page > table > tbody > tr > td{display:block;width:auto!important;padding:0 0 1.5rem!important}}</style>
<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/site.php';
