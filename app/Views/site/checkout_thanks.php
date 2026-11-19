<?php ob_start(); $co = config('site.company'); ?>
<div class="wrap sec" style="max-width:720px">
  <?php if ($state === 'ordered'): ?>
    <!-- data-cart-bought: cart.js empties this browser's cart. -->
    <div data-cart-bought></div>
    <h1 style="margin:0 0 .6rem;font-size:1.9rem">Thank you for your order</h1>
    <?php if (!empty($terms)): ?>
      <p style="font-size:1.05rem">Your order of <strong><?= money($total) ?></strong> is placed on your account. Your order
        number is <strong><?= e((string)$number) ?></strong>, and we'll invoice it on your usual terms.</p>
    <?php else: ?>
      <p style="font-size:1.05rem">Your payment of <strong><?= money($total) ?></strong> went through and your order number is
        <strong><?= e((string)$number) ?></strong>. Square has emailed your receipt.</p>
    <?php endif; ?>
    <p>We'll be in touch if we have any questions, and you'll hear from us when it ships.
      Custom stencils are checked by our art team before they're cut.</p>
  <?php elseif ($state === 'unpaid'): ?>
    <h1 style="margin:0 0 .6rem;font-size:1.9rem">Confirming your payment…</h1>
    <p>Square hasn't confirmed your payment yet. This page checks again in a few seconds. If you closed the
      payment page without paying, your cart is still here: <a href="/cart">return to your cart</a>.</p>
    <script>setTimeout(function () { location.reload(); }, 5000);</script>
  <?php elseif ($state === 'failed'): ?>
    <h1 style="margin:0 0 .6rem;font-size:1.9rem">We've got your payment</h1>
    <p>Your payment went through, but something on our side needs a person to finish your order. We've been told and
      will be in touch shortly. You don't need to pay again. Questions? Call
      <a href="tel:<?= e(preg_replace('/\D/', '', $co['phone'])) ?>"><?= e($co['phone']) ?></a>.</p>
  <?php else: ?>
    <h1 style="margin:0 0 .6rem;font-size:1.9rem">We couldn't find that checkout</h1>
    <p>If you were charged, please call us at <a href="tel:<?= e(preg_replace('/\D/', '', $co['phone'])) ?>"><?= e($co['phone']) ?></a>.</p>
  <?php endif; ?>
  <p style="margin-top:1.5rem"><a class="b b-s" href="/shop/stencils">Back to stencils</a></p>
</div>
<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/site.php';
