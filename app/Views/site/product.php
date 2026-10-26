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
    <?php if (!empty($pr['website_image_url'])): ?>
      <img src="<?= e($pr['website_image_url']) ?>" alt="<?= e($pr['name']) ?>"
           style="border:1px solid var(--line);border-radius:10px;aspect-ratio:1;object-fit:cover">
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
