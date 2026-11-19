<?php ob_start(); $activeSlug = 'stencils'; ?>
<?= view('site._designer_parts', ['part' => 'styles']) ?>

<div class="wrap">
  <div style="font-size:.8rem;color:var(--mut);padding-top:1.5rem">
    <a href="/shop">Home</a> &nbsp;/&nbsp; <a href="/shop/stencils">Stencils</a> &nbsp;/&nbsp;
    <a href="/shop/stencils/sports">Sports &amp; Athletics</a> &nbsp;/&nbsp;
    <a href="/shop/stencils/endzone">Endzone Stencils</a> &nbsp;/&nbsp; Custom Endzone
  </div>
  <div class="dz-head">
    <h1>Custom Endzone Lettering</h1>
    <p>Type your team or school name, pick a letter height and style, and see it on a 160' × 30' end zone in the
      exact lettering we cut, with the size, number of pieces and price.</p>
  </div>

  <?php if (!$ready || $sizes === []): ?>
    <div class="note" style="margin-bottom:3rem">The endzone designer isn't available just now.
      Please <a href="/stencils/endzone-stencils">send us your endzone</a> and we'll quote it.</div>
  <?php else: ?>
  <div class="dz-grid" id="endzone-designer"
       data-sizes="<?= e(json_encode($sizes)) ?>" data-colors="<?= e(json_encode($colors)) ?>">
    <div class="dz-card">
      <label class="dz-label" for="ez-text">Your text</label>
      <input id="ez-text" class="dz-text" maxlength="30" spellcheck="false" autocomplete="off" placeholder="HOME" value="HOME">
      <div class="dz-sub" id="ez-chars" style="margin-top:.35rem"></div>

      <span class="dz-label">Lettering style</span><div class="dz-chips" id="ez-style"></div>
      <span class="dz-label">Letter height</span><div class="dz-chips" id="ez-size"></div>
      <span class="dz-label">How it's cut</span><div class="dz-chips" id="ez-format"></div>
      <span class="dz-label">Preview colors <span style="text-transform:none;letter-spacing:0;font-weight:400">(for the picture only)</span></span>
      <div class="dz-swatches" id="ez-fill"></div>
      <div class="dz-swatches" id="ez-line" style="margin-top:.4rem"></div>

      <div class="dz-price" id="ez-price">—</div>
      <div class="dz-sub" id="ez-breakdown"></div>
      <div id="ez-msg"></div>
      <?= view('site._designer_parts', ['part' => 'buy']) ?>
    </div>

    <div>
      <div class="dz-field" id="ez-field"></div>
      <div class="dz-specs" id="ez-specs"></div>
      <div class="dz-stencil" id="ez-stencil"></div>
      <div class="dz-note">Cut from 10-mil polyethylene that lays flat on grass or turf and folds up to ship. Outline
        letters cut the outline only; fill the inside by hand in a second color. Need a logo, shadow layer or different
        lettering? <a href="/stencils/endzone-stencils">Ask us for a quote</a>.</div>
    </div>
  </div>

  <?= view('site._designer_parts', ['part' => 'request', 'kind' => 'endzone', 'sent' => $sent, 'error' => $error]) ?>
  <?php endif; ?>
</div>

<script src="/assets/js/stencil-designers.js?v=3" defer></script>
<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/site.php';
