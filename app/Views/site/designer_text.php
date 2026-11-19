<?php ob_start(); $activeSlug = 'stencils'; ?>
<?= view('site._designer_parts', ['part' => 'styles']) ?>

<div class="wrap">
  <div style="font-size:.8rem;color:var(--mut);padding-top:1.5rem">
    <a href="/shop">Home</a> &nbsp;/&nbsp; <a href="/shop/stencils">Stencils</a> &nbsp;/&nbsp; Custom Stencil
  </div>
  <div class="dz-head">
    <h1>Custom Stencil</h1>
    <p>Type your text, pick a letter size and material, and see your stencil drawn in the exact lettering we cut.
      Priced by size, the same way as our stock stencils.</p>
    <p style="margin:.6rem 0 0"><a href="/custom/endzone"><strong>Football end zone?</strong> Design custom endzone lettering →</a></p>
  </div>

  <?php if (!$ready): ?>
    <div class="note" style="margin-bottom:3rem">The custom stencil builder isn't available just now.
      Please <a href="/contact?about=Custom%20stencil">tell us what you need</a> and we'll quote it.</div>
  <?php else: ?>
  <div class="dz-grid" id="custom-designer" data-sizes="<?= e(json_encode($sizes)) ?>"
       data-materials="<?= e(json_encode($materials)) ?>" data-default="<?= e($default) ?>">
    <div class="dz-card">
      <label class="dz-label" for="c-text">Your text <span style="text-transform:none;letter-spacing:0;font-weight:400">(up to 3 lines)</span></label>
      <textarea id="c-text" class="dz-text" rows="3" maxlength="100" spellcheck="false" placeholder="YOUR TEXT"
                style="font-size:1.05rem;resize:vertical">YOUR TEXT</textarea>
      <div class="dz-sub" id="c-chars" style="margin-top:.35rem"></div>

      <table style="width:100%;border-collapse:collapse;margin-top:.4rem">
        <tr>
          <td style="width:50%;padding-right:.4rem;vertical-align:top">
            <label class="dz-label" for="c-size">Letter size</label>
            <select id="c-size" class="fi" style="width:100%;padding:.55rem;border:1px solid var(--line);border-radius:6px;font:inherit"></select>
          </td>
          <td style="width:50%;padding-left:.4rem;vertical-align:top">
            <label class="dz-label" for="c-mat">Material</label>
            <select id="c-mat" class="fi" style="width:100%;padding:.55rem;border:1px solid var(--line);border-radius:6px;font:inherit"></select>
          </td>
        </tr>
      </table>

      <div class="dz-price" id="c-price">—</div>
      <div class="dz-sub" id="c-breakdown"></div>
      <div id="c-msg"></div>
      <a class="dz-btn p" href="#request" style="text-align:center;text-decoration:none;box-sizing:border-box">Request this stencil ↓</a>
    </div>

    <div>
      <div class="dz-stencil" id="c-preview"></div>
      <div class="dz-specs" id="c-specs"></div>
      <div class="dz-note">Need a logo, symbol or special layout? <a href="/contact?about=Custom%20stencil">Contact us</a>
        for a quote. We check every custom stencil before cutting.</div>
    </div>
  </div>

  <?= view('site._designer_parts', ['part' => 'request', 'kind' => 'text', 'sent' => $sent, 'error' => $error]) ?>
  <?php endif; ?>
</div>

<script src="/assets/js/stencil-designers.js?v=2" defer></script>
<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/site.php';
