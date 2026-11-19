<?php
/** One saved quote, priced again today. "Open in cart" puts its items in the cart to order. */
ob_start();
?>
<div class="wrap sec" style="max-width:56rem">
  <p style="font-size:.85rem;margin:0 0 .6rem"><a href="/account#quotes">← My account</a></p>
  <h1 style="margin:0 0 .3rem;font-size:1.8rem"><?= e($quote['name']) ?></h1>
  <p style="margin:0 0 1.2rem;color:var(--mut)">Saved <?= e(date('M j, Y', strtotime((string)$quote['created_at']))) ?>
    <?= $quote['status'] === 'ordered' ? ' · Ordered' . ($quote['so_number'] ? ' as order #' . e($quote['so_number']) : '') : '' ?></p>

  <?php if ($problem !== ''): ?>
    <div class="note" style="border-color:#fca5a5;background:#fef2f2;color:#991b1b;margin-bottom:1rem"><?= e($problem) ?>
      Something in this quote is no longer available, so it can't be priced. Please call us, or start a new cart.</div>
  <?php else: ?>
    <table style="width:100%;border-collapse:collapse;font-size:.95rem">
      <?php foreach ($priced['lines'] as $l): ?>
        <tr style="border-bottom:1px solid var(--line)">
          <td style="padding:.6rem .6rem .6rem 0"><strong><?= e($l['name']) ?></strong>
            <div style="color:var(--mut);font-size:.85rem"><?= e($l['detail'] ?? '') ?></div></td>
          <td style="padding:.6rem;white-space:nowrap"><?= (int)$l['qty'] ?> × <?= money((float)$l['unit']) ?></td>
          <td style="padding:.6rem 0;text-align:right;white-space:nowrap;font-weight:700"><?= money((float)$l['unit'] * (int)$l['qty']) ?></td>
        </tr>
      <?php endforeach; ?>
      <tr><td colspan="2" style="padding:.8rem 0;font-weight:800">Subtotal, at today's prices</td>
        <td style="padding:.8rem 0;text-align:right;font-weight:800"><?= money((float)$priced['subtotal']) ?></td></tr>
    </table>
    <?php if (abs((float)$priced['subtotal'] - (float)$quote['total_when_saved']) > 0.009): ?>
      <p style="color:var(--mut);font-size:.9rem">It was <?= money((float)$quote['total_when_saved']) ?> when you saved it; prices have changed since.</p>
    <?php endif; ?>
    <p style="color:var(--mut);font-size:.9rem">Shipping is added at checkout.</p>

    <?php if ($quote['status'] === 'saved'): ?>
      <p style="margin-top:1.2rem">
        <button type="button" class="b b-p" data-load-quote="<?= (int)$quote['id'] ?>" data-items="<?= e(json_encode($items)) ?>">Open in cart to order</button>
      </p>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($quote['status'] === 'saved'): ?>
    <form method="POST" action="/account/quotes/<?= (int)$quote['id'] ?>/delete" style="margin-top:1.5rem"
          onsubmit="return confirm('Delete this quote?')">
      <button type="submit" class="b b-s">Delete this quote</button>
    </form>
  <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/site.php';
