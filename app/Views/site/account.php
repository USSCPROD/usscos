<?php
/** My Account: who you are, which company and prices, saved designs and quotes, password. */
ob_start();
$co = config('site.company');
$designerUrl = ['text' => '/custom', 'endzone' => '/custom/endzone', 'midfield' => '/custom/midfield'];
$kindLabel   = ['text' => 'Custom stencil', 'endzone' => 'Endzone', 'midfield' => 'Midfield logo'];
?>
<div class="wrap sec">
  <table style="width:100%;border-collapse:collapse;margin-bottom:1.2rem"><tr>
    <td><h1 style="margin:0;font-size:1.9rem;letter-spacing:-.02em">My account</h1></td>
    <td style="text-align:right">
      <form method="POST" action="/account/sign-out" style="margin:0"><button class="b b-s" type="submit">Sign out</button></form>
    </td>
  </tr></table>

  <?php if ($notice !== ''): ?><div class="note" style="margin-bottom:1rem"><?= e($notice) ?></div><?php endif; ?>

  <div style="border:1px solid var(--line);border-radius:10px;padding:1.25rem;background:var(--soft);margin-bottom:1.5rem">
    <div style="font-weight:700;font-size:1.05rem"><?= e($login['name']) ?></div>
    <div style="color:var(--mut);font-size:.92rem"><?= e($login['email']) ?></div>
    <?php if ($company !== ''): ?>
      <p style="margin:.7rem 0 0">Ordering for <strong><?= e($company) ?></strong>.
        <?= $trade ? 'You see <strong>' . e($tier) . '</strong> prices, can save quotes and can order on account.' : 'You see list prices.' ?></p>
    <?php else: ?>
      <p style="margin:.7rem 0 0">We're connecting your account to <strong><?= e($login['organization']) ?></strong>
        in our system. Until then you'll see list prices. Distributor? Call us at
        <a href="tel:<?= e(preg_replace('/\D/', '', $co['phone'])) ?>"><?= e($co['phone']) ?></a> to speed it up.</p>
    <?php endif; ?>
  </div>

  <?php if ($trade || $quotes): ?>
  <h2 id="quotes" style="font-size:1.25rem;margin:0 0 .6rem">Saved quotes</h2>
  <?php if (!$quotes): ?>
    <p style="color:var(--mut)">None yet. Fill your cart and choose <em>Save this cart as a quote</em>.</p>
  <?php else: ?>
    <table style="width:100%;border-collapse:collapse;margin-bottom:1.8rem;font-size:.95rem">
      <?php foreach ($quotes as $q): ?>
        <tr style="border-bottom:1px solid var(--line)">
          <td style="padding:.6rem .6rem .6rem 0"><a href="/account/quotes/<?= (int)$q['id'] ?>" style="font-weight:700"><?= e($q['name']) ?></a></td>
          <td style="padding:.6rem;color:var(--mut)"><?= e(date('M j, Y', strtotime((string)$q['updated_at']))) ?></td>
          <td style="padding:.6rem"><?= $q['status'] === 'ordered' ? 'Ordered' . ($q['so_number'] ? ' · #' . e($q['so_number']) : '') : 'Saved' ?></td>
          <td style="padding:.6rem 0;text-align:right"><?= money((float)$q['total_when_saved']) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
  <?php endif; ?>

  <h2 id="designs" style="font-size:1.25rem;margin:0 0 .6rem">Saved designs</h2>
  <?php if (!$designs): ?>
    <p style="color:var(--mut)">None yet. Design a <a href="/custom">custom stencil</a>, <a href="/custom/endzone">endzone</a>
      or <a href="/custom/midfield">midfield logo</a> and choose <em>Save this design</em>.</p>
  <?php else: ?>
    <table style="width:100%;border-collapse:collapse;margin-bottom:1.8rem;font-size:.95rem">
      <?php foreach ($designs as $d): ?>
        <tr style="border-bottom:1px solid var(--line)">
          <td style="padding:.6rem .6rem .6rem 0">
            <a href="<?= e($designerUrl[$d['kind']]) ?>?design=<?= (int)$d['id'] ?>" style="font-weight:700"><?= e($d['name']) ?></a>
            <div class="text-muted" style="font-size:.85rem;color:var(--mut)"><?= e($kindLabel[$d['kind']]) ?> · <?= e($d['summary']) ?></div>
          </td>
          <td style="padding:.6rem;color:var(--mut);white-space:nowrap"><?= e(date('M j, Y', strtotime((string)$d['updated_at']))) ?></td>
          <td style="padding:.6rem 0;text-align:right;white-space:nowrap">
            <form method="POST" action="/account/designs/<?= (int)$d['id'] ?>/delete" style="margin:0"
                  onsubmit="return confirm('Delete this design?')">
              <button type="submit" style="appearance:none;border:0;background:none;color:var(--blue);cursor:pointer;font:inherit;font-size:.85rem">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>

  <h2 id="password" style="font-size:1.25rem;margin:1.2rem 0 .6rem">Change password</h2>
  <?php if ($error !== ''): ?>
    <div class="note" style="border-color:#fca5a5;background:#fef2f2;color:#991b1b;margin-bottom:1rem;max-width:28rem"><?= e($error) ?></div>
  <?php endif; ?>
  <form method="POST" action="/account/password" style="max-width:28rem">
    <label class="fl" for="p-cur">Current password</label>
    <input class="fi" id="p-cur" name="current" type="password" autocomplete="current-password" required>
    <label class="fl" for="p-new" style="margin-top:.8rem">New password <span style="font-weight:400;color:var(--mut)">(at least 8 characters)</span></label>
    <input class="fi" id="p-new" name="password" type="password" autocomplete="new-password" required minlength="8">
    <p style="margin:1rem 0 0"><button class="b b-s" type="submit">Change password</button></p>
  </form>
</div>
<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/site.php';
