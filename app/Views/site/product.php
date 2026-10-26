<?php ob_start(); ?>
<?php
$pr    = $product;
$price = (float)($pr['website_price'] ?? 0);
// A variable, not a view-local function — see CLAUDE.md on duplicate declarations.
$spec = function (string $label, $value, string $suffix = '') {
    if ($value === null || $value === '' || (is_numeric($value) && (float)$value == 0.0)) {
        return;
    }
    $v = is_numeric($value) ? rtrim(rtrim(number_format((float)$value, 2), '0'), '.') : (string)$value;
    printf('<tr><th style="text-align:left;padding:.5rem .9rem .5rem 0;font-weight:600;'
         . 'color:#6b7280;font-size:.84rem;white-space:nowrap">%s</th>'
         . '<td style="padding:.5rem 0;font-size:.9rem">%s%s</td></tr>',
        e($label), e($v), e($suffix));
};
?>

<div class="wrap" style="padding-top:1.5rem">
  <div style="font-size:.8rem;color:var(--mut);margin-bottom:1.5rem">
    <a href="/shop">Home</a> &nbsp;/&nbsp; <?= e($pr['name']) ?>
  </div>
</div>

<div class="wrap" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:2.5rem;padding-bottom:3rem">

  <div>
    <?php $main = \App\Repositories\StorefrontRepository::imageFor($pr); ?>
    <?php if ($main !== null): ?>
      <img id="pimg" src="<?= e($main) ?>" alt="<?= e($pr['name']) ?>"
           style="border:1px solid var(--line);border-radius:10px;aspect-ratio:1;object-fit:contain;background:#fff;width:100%"
           onerror="this.outerHTML='&lt;div class=\'ph\' style=\'aspect-ratio:1;border:1px solid var(--line);border-radius:10px\'&gt;No photo yet&lt;/div&gt;'">

      <?php if (count($images) > 1): ?>
        <!--
            Thumbnails swap the main image in place. Every image is in the markup, so
            the page works with JavaScript off: the others are simply not reachable,
            rather than the page being broken.
        -->
        <div style="display:flex;gap:.5rem;margin-top:.7rem;flex-wrap:wrap">
          <?php foreach ($images as $ix => $im): ?>
            <button type="button"
                    onclick="document.getElementById('pimg').src='<?= e($im['file_path']) ?>';
                             [].forEach.call(this.parentNode.children, function(b){ b.style.borderColor='var(--line)'; });
                             this.style.borderColor='var(--blue)';"
                    style="padding:0;width:62px;height:62px;border:2px solid <?= $ix === 0 ? 'var(--blue)' : 'var(--line)' ?>;
                           border-radius:7px;overflow:hidden;background:#fff;cursor:pointer">
              <img src="<?= e($im['file_path']) ?>" alt=""
                   style="width:100%;height:100%;object-fit:contain" loading="lazy">
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <div class="ph" style="aspect-ratio:1;border:1px solid var(--line);border-radius:10px">
        No photo yet
      </div>
    <?php endif; ?>
  </div>

  <div>
    <?php if (!empty($pr['brand_name'])): ?>
      <div style="font-size:.74rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--blue);margin-bottom:.5rem">
        <?= e($pr['brand_name']) ?>
      </div>
    <?php endif; ?>

    <h1 style="margin:0 0 .5rem;font-size:1.7rem;letter-spacing:-.025em;line-height:1.2"><?= e($pr['name']) ?></h1>
    <div class="sku" style="font-size:.8rem;margin-bottom:1.25rem">SKU <?= e($pr['sku']) ?></div>

    <?php if ($price > 0): ?>
      <div style="font-size:2rem;font-weight:800;color:var(--navy);margin-bottom:.35rem"><?= money($price) ?></div>
      <div style="font-size:.82rem;color:var(--mut);margin-bottom:1.5rem">
        per <?= e($pr['uom_code'] ?: 'each') ?><?php
          if ((int)($pr['units_per_case'] ?? 0) > 1): ?> &nbsp;·&nbsp; <?= (int)$pr['units_per_case'] ?> per case<?php endif; ?>
      </div>
    <?php else: ?>
      <div style="font-size:1.1rem;font-weight:700;color:var(--hot);margin-bottom:.35rem">Call or email for best price</div>
      <div style="font-size:.82rem;color:var(--mut);margin-bottom:1.5rem">
        Bulk and freight-shipped items are quoted individually.
      </div>
    <?php endif; ?>

    <?php if (count($variants) > 1): ?>
      <!--
          Real links, one per thickness, not a <select> that needs JavaScript to do
          anything. Each thickness is a separate product with its own SKU and price,
          so each deserves its own URL: it can be linked to, shared and indexed, and
          the price on screen always belongs to the SKU shown above it.
      -->
      <div style="margin-bottom:1.5rem">
        <div style="font-size:.74rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;
                    color:var(--mut);margin-bottom:.5rem">
          Thickness
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
          <?php foreach ($variants as $v): ?>
            <?php $on = $v['sku'] === $pr['sku']; ?>
            <a href="/product/<?= e(rawurlencode((string)$v['sku'])) ?>"
               aria-current="<?= $on ? 'true' : 'false' ?>"
               style="display:block;padding:.55rem .9rem;border-radius:7px;text-decoration:none;
                      border:2px solid <?= $on ? 'var(--blue)' : 'var(--line)' ?>;
                      background:<?= $on ? '#eff4ff' : '#fff' ?>;min-width:5.5rem;text-align:center">
              <div style="font-weight:700;font-size:.95rem;color:<?= $on ? 'var(--blue)' : 'var(--ink)' ?>">
                <?= e($v['variant_label'] ?: $v['sku']) ?>
              </div>
              <div style="font-size:.78rem;color:var(--mut);margin-top:.1rem">
                <?= (float)$v['website_price'] > 0 ? money((float)$v['website_price']) : 'ask' ?>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:1.75rem">
      <a class="b b-p" href="/quote?sku=<?= e(rawurlencode((string)$pr['sku'])) ?>">Request a quote</a>
      <a class="b b-s" href="tel:<?= e(preg_replace('/\D/','',config('site.company.phone'))) ?>">
        Call <?= e(config('site.company.phone')) ?>
      </a>
    </div>

    <?php if (!empty($pr['short_description'])): ?>
      <p style="font-size:.95rem;color:#374151;margin:0 0 1.25rem"><?= e($pr['short_description']) ?></p>
    <?php endif; ?>

    <?php if (!empty($pr['description']) && $pr['description'] !== ($pr['short_description'] ?? '')): ?>
      <p style="font-size:.9rem;color:var(--mut);margin:0 0 1.5rem"><?= nl2br(e($pr['description'])) ?></p>
    <?php endif; ?>

    <table style="border-collapse:collapse;width:100%">
      <?php
        $spec('Unit',        $pr['uom_code'] ?? '');
        $spec('Units/case',  $pr['units_per_case'] ?? null);
        $spec('Ship weight', $pr['gross_weight'] ?? null, ' ' . ($pr['weight_unit'] ?: 'lb'));
        $spec('Net weight',  $pr['net_weight'] ?? null, ' ' . ($pr['weight_unit'] ?: 'lb'));
        if ((float)($pr['height'] ?? 0) > 0) {
            $d = fn($k) => rtrim(rtrim(number_format((float)($pr[$k] ?? 0), 2), '0'), '.');
            $spec('Dimensions', $d('height') . ' × ' . $d('width') . ' × ' . $d('depth'),
                  ' ' . ($pr['dim_unit'] ?: 'in'));
        }
      ?>
    </table>
  </div>
</div>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/site.php';
