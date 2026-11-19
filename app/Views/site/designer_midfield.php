<?php ob_start(); $activeSlug = 'stencils'; ?>
<?= view('site._designer_parts', ['part' => 'styles']) ?>

<div class="wrap">
  <div style="font-size:.8rem;color:var(--mut);padding-top:1.5rem">
    <a href="/shop">Home</a> &nbsp;/&nbsp; <a href="/shop/stencils">Stencils</a> &nbsp;/&nbsp;
    <a href="/shop/stencils/sports">Sports &amp; Athletics</a> &nbsp;/&nbsp;
    <a href="/shop/stencils/midfield">Midfield Logo Stencils</a> &nbsp;/&nbsp; Custom Midfield Logo
  </div>
  <div class="dz-head">
    <h1>Custom Midfield Logo</h1>
    <p>Upload your school or team logo, choose your field and size it. See it at midfield with the exact size and price.
      We cut it as one stencil with a dot pattern, so every color of your logo paints from the same stencil.</p>
  </div>

  <div class="dz-grid" id="midfield-designer" data-saved="<?= e(json_encode($saved)) ?>" data-options="<?= e(json_encode($options)) ?>">
    <div class="dz-card">
      <span class="dz-label">Your logo</span>
      <label class="dz-drop" id="mf-drop">
        <input type="file" id="mf-file" accept=".png,.jpg,.jpeg,.webp,.gif,.svg,.pdf,.ai,image/png,image/jpeg,image/webp,image/gif,image/svg+xml,application/pdf">
        <span aria-hidden="true" style="font-size:1.4rem">⇪</span>
        <strong id="mf-drop-title">Upload your logo</strong>
        <span class="dz-sub" id="mf-drop-text">JPG, PNG, SVG, PDF or Illustrator (.ai) · up to 25 MB. A logo file on a plain background works best.</span>
      </label>
      <div id="mf-upload-msg"></div>

      <span class="dz-label">Your field</span><div class="dz-chips" id="mf-field-type"></div>
      <label class="dz-label" for="mf-width">Logo size</label>
      <div class="dz-range"><input type="range" id="mf-width" min="15" max="55" step="0.5" value="28" disabled><output id="mf-size-out">28' wide</output></div>
      <div class="dz-sub" id="mf-size-note" style="margin-top:.3rem"></div>

      <div class="dz-price" id="mf-price">—</div>
      <div class="dz-sub" id="mf-breakdown">Upload your logo to see the price.</div>
      <div id="mf-msg"></div>
      <?= view('site._designer_parts', ['part' => 'buy', 'signedIn' => $signedIn]) ?>
    </div>

    <div>
      <div class="dz-field" id="mf-field"></div>
      <div class="dz-specs" id="mf-specs"></div>
      <div class="dz-note">Cut from 10-mil polyethylene that lays flat on grass or turf and folds up to ship. Our art team
        prepares the cutting file from the artwork you upload. Need a shadow layer, a different layout or help with your
        artwork? <a href="/stencils/midfield-logo-stencil">Ask us for a quote</a>.</div>
    </div>
  </div>

  <?= view('site._designer_parts', ['part' => 'request', 'kind' => 'midfield', 'sent' => $sent, 'error' => $error]) ?>
</div>

<script src="/assets/js/stencil-designers.js?v=4" defer></script>
<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/site.php';
