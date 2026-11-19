<?php
/**
 * Sign in, create an account, and the forgotten-password pages: one view, by $mode
 * (signin | signup | forgot | forgot_sent | reset | reset_expired).
 */
ob_start();
$co      = config('site.company');
$nextQs  = $next !== '' ? '?next=' . rawurlencode($next) : '';
$errBox  = static fn(string $m) => $m === '' ? '' :
    '<div class="note" style="border-color:#fca5a5;background:#fef2f2;color:#991b1b;margin-bottom:1rem">' . e($m) . '</div>';
?>
<div class="wrap sec" style="max-width:30rem">
  <h1 style="margin:0 0 .4rem;font-size:1.8rem;letter-spacing:-.02em"><?= e($title) ?></h1>

  <?php if ($mode === 'signin'): ?>
    <p style="margin:0 0 1.2rem;color:var(--mut)">Save your designs, and see your company's pricing once your account is set up.</p>
    <?php if ($out): ?><div class="note" style="margin-bottom:1rem">You're signed out.</div><?php endif; ?>
    <?= $errBox($error) ?>
    <form method="POST" action="/account/sign-in<?= e($nextQs) ?>">
      <label class="fl" for="a-email">Email</label>
      <input class="fi" id="a-email" name="email" type="email" autocomplete="email" required value="<?= e($old['email']) ?>">
      <label class="fl" for="a-pw" style="margin-top:.8rem">Password</label>
      <input class="fi" id="a-pw" name="password" type="password" autocomplete="current-password" required>
      <p style="margin:1.1rem 0 0"><button class="b b-p" type="submit" style="width:100%">Sign in</button></p>
    </form>
    <p style="margin-top:1rem;font-size:.92rem"><a href="/account/forgot">Forgot your password?</a></p>
    <p style="font-size:.92rem">New here? <a href="/account/sign-up<?= e($nextQs) ?>">Create a free account</a></p>

  <?php elseif ($mode === 'signup'): ?>
    <p style="margin:0 0 1.2rem;color:var(--mut)">Free. Save your stencil designs and reorder easily. Distributors: tell us
      below, and we'll set up your account with your pricing.</p>
    <?= $errBox($error) ?>
    <form method="POST" action="/account/sign-up<?= e($nextQs) ?>">
      <input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true"
             style="position:absolute;left:-10000px;width:1px;height:1px;opacity:0">
      <label class="fl" for="a-name">Your name</label>
      <input class="fi" id="a-name" name="name" autocomplete="name" required maxlength="100" value="<?= e($old['name']) ?>">
      <label class="fl" for="a-org" style="margin-top:.8rem">School, team or company</label>
      <input class="fi" id="a-org" name="organization" autocomplete="organization" required maxlength="150" value="<?= e($old['organization']) ?>">
      <label class="fl" for="a-type" style="margin-top:.8rem">Which best describes you?</label>
      <select class="fi" id="a-type" name="buyer_type" required>
        <option value="">Choose one</option>
        <?php foreach ($types as $typeKey => $typeLabel): ?>
          <option value="<?= e($typeKey) ?>"<?= $old['buyer_type'] === $typeKey ? ' selected' : '' ?>><?= e($typeLabel) ?></option>
        <?php endforeach; ?>
      </select>
      <label class="fl" for="a-email" style="margin-top:.8rem">Email</label>
      <input class="fi" id="a-email" name="email" type="email" autocomplete="email" required maxlength="200" value="<?= e($old['email']) ?>">
      <label class="fl" for="a-phone" style="margin-top:.8rem">Phone</label>
      <input class="fi" id="a-phone" name="phone" type="tel" autocomplete="tel" required maxlength="40" value="<?= e($old['phone']) ?>">
      <label class="fl" for="a-pw" style="margin-top:.8rem">Password <span style="font-weight:400;color:var(--mut)">(at least 8 characters)</span></label>
      <input class="fi" id="a-pw" name="password" type="password" autocomplete="new-password" required minlength="8">
      <p style="margin:1.1rem 0 0"><button class="b b-p" type="submit" style="width:100%">Create account</button></p>
    </form>
    <p style="margin-top:1rem;font-size:.92rem">Already have an account? <a href="/account/sign-in<?= e($nextQs) ?>">Sign in</a></p>

  <?php elseif ($mode === 'forgot'): ?>
    <p style="margin:0 0 1.2rem;color:var(--mut)">Enter the email you signed up with and we'll send a link to choose a new password.</p>
    <?= $errBox($error) ?>
    <form method="POST" action="/account/forgot">
      <label class="fl" for="a-email">Email</label>
      <input class="fi" id="a-email" name="email" type="email" autocomplete="email" required value="<?= e($old['email']) ?>">
      <p style="margin:1.1rem 0 0"><button class="b b-p" type="submit" style="width:100%">Send the link</button></p>
    </form>
    <p style="margin-top:1rem;font-size:.92rem"><a href="/account/sign-in">Back to sign in</a></p>

  <?php elseif ($mode === 'forgot_sent'): ?>
    <p>If there's an account for that email, a link to reset its password is on its way. It works once, for an hour.
      Check your spam folder if it doesn't arrive in a few minutes.</p>
    <p><a href="/account/sign-in">Back to sign in</a></p>

  <?php elseif ($mode === 'reset'): ?>
    <?= $errBox($error) ?>
    <form method="POST" action="/account/reset">
      <input type="hidden" name="t" value="<?= e($token) ?>">
      <label class="fl" for="a-pw">New password <span style="font-weight:400;color:var(--mut)">(at least 8 characters)</span></label>
      <input class="fi" id="a-pw" name="password" type="password" autocomplete="new-password" required minlength="8">
      <p style="margin:1.1rem 0 0"><button class="b b-p" type="submit" style="width:100%">Save new password</button></p>
    </form>

  <?php else: ?>
    <p>This reset link has expired or was already used. <a href="/account/forgot">Ask for a new one</a>.</p>
  <?php endif; ?>

  <p style="margin-top:2rem;font-size:.85rem;color:var(--mut)">Trouble signing in? Call
    <a href="tel:<?= e(preg_replace('/\D/', '', $co['phone'])) ?>"><?= e($co['phone']) ?></a>.</p>
</div>
<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/site.php';
