<?php
/**
 * What both designers share: their styles, the price area and the request form.
 *
 * $part   'styles' | 'buy' | 'request'
 * $kind   'endzone' | 'midfield'
 * $sent, $error   from the request round trip
 *
 * The request form carries the current design in hidden fields that
 * public/assets/js/stencil-designers.js fills in; the server prices it again.
 */
$part = $part ?? '';
$co   = config('site.company');
?>
<?php if ($part === 'styles'): ?>
<style>
.dz-head{padding:1.5rem 0 1rem}
.dz-head h1{margin:0 0 .4rem;font-size:1.9rem;letter-spacing:-.025em}
.dz-head p{margin:0;color:var(--mut);max-width:68ch}
.dz-grid{display:grid;grid-template-columns:minmax(300px,400px) 1fr;gap:2rem;align-items:start;padding-bottom:2rem}
@media (max-width:900px){.dz-grid{grid-template-columns:1fr}}
.dz-card{border:1px solid var(--line);border-radius:10px;padding:1.25rem;background:#fff}
.dz-label{display:block;font-size:.74rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--mut);margin:1.1rem 0 .45rem}
.dz-label:first-child{margin-top:0}
.dz-mut{color:var(--mut);font-size:.8rem;margin-right:.4rem}
.dz-text{width:100%;box-sizing:border-box;padding:.7rem .8rem;border:1px solid var(--line);border-radius:7px;font:inherit;font-size:1.2rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase}
.dz-chips{display:flex;flex-wrap:wrap;gap:.4rem}
.dz-chip{appearance:none;border:2px solid var(--line);background:#fff;border-radius:7px;padding:.45rem .75rem;font:inherit;font-size:.86rem;font-weight:700;color:var(--ink);cursor:pointer;text-align:left}
.dz-chip small{display:block;font-weight:500;font-size:.72rem;color:var(--mut);margin-top:.1rem;max-width:16rem}
.dz-chip[aria-pressed="true"]{border-color:var(--blue);background:#eff4ff;color:var(--blue)}
.dz-swatches{display:flex;align-items:center;gap:.35rem;flex-wrap:wrap}
.dz-swatch{width:1.6rem;height:1.6rem;border-radius:999px;border:1px solid #00000033;cursor:pointer;padding:0}
.dz-swatch[aria-pressed="true"]{box-shadow:0 0 0 2px #fff,0 0 0 4px var(--blue)}
.dz-price{font-size:2rem;font-weight:800;color:var(--navy);margin-top:1.2rem}
.dz-sub{font-size:.82rem;color:var(--mut)}
.dz-btn{appearance:none;display:block;width:100%;margin-top:.7rem;padding:.75rem;border-radius:7px;font:inherit;font-weight:700;cursor:pointer;border:2px solid var(--navy);background:#fff;color:var(--navy)}
.dz-btn.p{background:var(--navy);color:#fff}
.dz-btn:disabled{opacity:.45;cursor:default}
.dz-field{border-radius:10px;overflow:hidden;background:#356b26;min-height:12rem}
.dz-field svg,.dz-stencil svg{display:block;width:100%;height:auto}
.dz-num{font-size:8.3px;font-weight:700;fill:#f6f6f2;text-anchor:middle;letter-spacing:1.2px}
.dz-specs{display:grid;grid-template-columns:repeat(3,1fr);border:1px solid var(--line);border-radius:8px;margin:.8rem 0;background:var(--soft)}
.dz-specs > div{padding:.6rem .8rem;border-left:1px solid var(--line)}
.dz-specs > div:first-child{border-left:0}
.dz-specs span{display:block;font-size:.74rem;color:var(--mut)}
.dz-specs strong{font-size:.92rem}
.dz-stencil{border:1px solid var(--line);border-radius:10px;padding:1rem;background:#fff}
.dz-cap{margin:.5rem 0 0;font-size:.8rem;color:var(--mut)}
.dz-note{margin-top:.8rem;padding:.8rem 1rem;background:var(--soft);border-left:3px solid #7cb82f;font-size:.86rem;color:#374151}
.dz-alert{margin-top:.8rem;padding:.7rem .9rem;border-radius:7px;background:#fff7ed;border:1px solid #fdba74;color:#9a3412;font-size:.86rem}
.dz-drop{display:block;border:2px dashed var(--line);border-radius:10px;padding:1.2rem;text-align:center;cursor:pointer;background:var(--soft)}
.dz-drop input{display:none}
.dz-drop strong{display:block;margin:.2rem 0}
.dz-drop.over,.dz-drop.done{border-color:var(--blue)}
.dz-drop.busy{opacity:.6}
.dz-range{display:flex;align-items:center;gap:.8rem}
.dz-range input{flex:1}
.dz-range output{font-weight:700;font-size:.9rem;white-space:nowrap}
</style>

<?php elseif ($part === 'buy'): ?>
  <button class="dz-btn p" type="button" data-add-design disabled>Add to cart</button>
  <?= view('site._designer_parts', ['part' => 'save', 'signedIn' => $signedIn ?? false, 'staffOnly' => $staffOnly ?? false]) ?>
  <button class="dz-btn" id="dz-proof" type="button" disabled>⤓ Proof to print or save as PDF</button>
  <p class="dz-sub" style="margin:.4rem 0 0">A proof with your design, size and price to share with your athletic
    director, school administration or board.</p>
  <a class="dz-btn" href="#request" style="text-align:center;text-decoration:none;box-sizing:border-box">Or send it to us for a quote ↓</a>

<?php elseif ($part === 'gate'): ?>
  <?php /* The designers need an account (Chip, 19 Nov 2026). $next brings them back here. */ ?>
  <div class="dz-card" style="max-width:34rem;margin:0 0 3rem">
    <h2 style="margin:0 0 .4rem;font-size:1.25rem">Sign in to use the designer</h2>
    <p style="margin:0 0 1rem;color:#374151">It's free. With an account you can design, see your price, save your designs
      to come back to, and order.</p>
    <a class="b b-p" href="/account/sign-up?next=<?= e(rawurlencode($next)) ?>">Create a free account</a>
    <a class="b b-s" href="/account/sign-in?next=<?= e(rawurlencode($next)) ?>" style="margin-left:.4rem">Sign in</a>
    <p class="dz-sub" style="margin:1rem 0 0">Rather talk to someone? <a href="/contact?about=Custom%20stencil">Send us your project</a>
      and we'll quote it.</p>
  </div>

<?php elseif ($part === 'save'): ?>
  <?php /* Saving needs an account: signed out, the button goes to sign in and comes back. */ ?>
  <?php if (empty($staffOnly)): /* staff have no customer account to save into */ ?>
  <button class="dz-btn" type="button" data-save-design data-signed-in="<?= !empty($signedIn) ? '1' : '0' ?>" disabled>Save this design</button>
  <?php endif; ?>

<?php elseif ($part === 'request'): ?>
  <div id="request" class="dz-card" style="max-width:46rem;margin:0 0 3rem">
    <?php if (($sent ?? '') === '1'): ?>
      <h2 style="margin:0 0 .5rem;font-size:1.25rem">Thank you</h2>
      <p style="margin:0;color:#374151">We have your design and will be in touch soon (<?= e($co['hours']) ?>) to confirm it,
        send a proof and arrange your order. Need us sooner? Call
        <a href="tel:<?= e(preg_replace('/\D/', '', $co['phone'])) ?>"><?= e($co['phone']) ?></a>.</p>
    <?php else: ?>
      <h2 style="margin:0 0 .35rem;font-size:1.25rem">Request this stencil</h2>
      <p style="margin:0 0 1rem;color:var(--mut);font-size:.9rem">Send us your design as it is above. We'll confirm it,
        send a proof before anything is cut, and arrange your order and shipping.
        <?= ($kind ?? '') !== 'text' ? 'Your name and school also go on the proof.' : '' ?></p>
      <?php if (($error ?? '') !== ''): ?>
        <div class="dz-alert" style="margin:0 0 1rem"><?= e($error) ?></div>
      <?php endif; ?>
      <form id="dz-request-form" method="POST" action="/custom/request">
        <input type="hidden" name="kind" value="<?= e($kind) ?>">
        <?php foreach (['text', 'size', 'style', 'format', 'fill', 'line', 'logo', 'field', 'width', 'thickness'] as $h): ?>
          <input type="hidden" name="<?= $h ?>" value="">
        <?php endforeach; ?>
        <input type="hidden" name="started" value="<?= time() ?>">
        <input type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true"
               style="position:absolute;left:-10000px;width:1px;height:1px;opacity:0">
        <table style="width:100%;border-collapse:separate;border-spacing:0 .7rem">
          <tr>
            <td style="width:50%;padding-right:.5rem;vertical-align:top">
              <label class="fl" for="dz-name">Your name</label><input class="fi" id="dz-name" name="name" autocomplete="name" required value="<?= e($me['name'] ?? '') ?>">
            </td>
            <td style="width:50%;padding-left:.5rem;vertical-align:top">
              <label class="fl" for="dz-org">School, team or company</label><input class="fi" id="dz-org" name="org" autocomplete="organization" value="<?= e($me['org'] ?? '') ?>">
            </td>
          </tr>
          <tr>
            <td style="padding-right:.5rem;vertical-align:top">
              <label class="fl" for="dz-email">Email</label><input class="fi" id="dz-email" name="email" type="email" autocomplete="email" required value="<?= e($me['email'] ?? '') ?>">
            </td>
            <td style="padding-left:.5rem;vertical-align:top">
              <label class="fl" for="dz-phone">Phone <span style="font-weight:400;color:var(--mut)">(optional)</span></label>
              <input class="fi" id="dz-phone" name="phone" type="tel" autocomplete="tel" value="<?= e($me['phone'] ?? '') ?>">
            </td>
          </tr>
          <tr>
            <td colspan="2">
              <label class="fl" for="dz-message">Anything else? <span style="font-weight:400;color:var(--mut)">(optional)</span></label>
              <textarea class="fi" id="dz-message" name="message" rows="3" maxlength="3000"
                        placeholder="When you need it, how many, colors…"></textarea>
            </td>
          </tr>
        </table>
        <button class="b b-p" id="dz-send" type="submit" disabled>Send my design</button>
      </form>
    <?php endif; ?>
  </div>
  <style>
  .fl{display:block;font-size:.8rem;font-weight:600;margin-bottom:.3rem;color:var(--ink)}
  .fi{width:100%;box-sizing:border-box;padding:.6rem .7rem;border:1px solid var(--line);border-radius:6px;font:inherit;font-size:.92rem;background:#fff}
  @media (max-width:560px){#request td{display:block;width:auto!important;padding:0!important}}
  </style>
<?php endif; ?>
