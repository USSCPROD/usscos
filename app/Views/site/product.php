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
          <?= e($pr['variant_axis'] ?: 'Options') ?>
        </div>
        <?php if (strcasecmp((string)($pr['variant_axis'] ?? ''), 'Color') === 0): ?>
          <!--
              Dots, not labelled buttons. Thirty-six colors as buttons is ten rows and
              leaves no room for the product copy; as dots it is two. The name and
              price of whichever one is hovered or selected is written underneath, so
              nothing is lost by shrinking them.
          -->
          <div style="display:flex;gap:.45rem;flex-wrap:wrap;max-width:30rem" id="swatches">
            <?php foreach ($variants as $v): ?>
              <?php
                $on    = $v['sku'] === $pr['sku'];
                $label = (string)($v['variant_label'] ?: $v['sku']);
                $css   = \App\Services\ColorSwatch::css($label);
                $price = (float)$v['website_price'] > 0 ? money((float)$v['website_price']) : 'ask';
              ?>
              <a href="/product/<?= e(rawurlencode((string)$v['sku'])) ?>"
                 aria-current="<?= $on ? 'true' : 'false' ?>"
                 title="<?= e($label . ' — ' . $price) ?>"
                 data-label="<?= e($label) ?>" data-price="<?= e($price) ?>"
                 style="display:block;width:1.9rem;height:1.9rem;border-radius:999px;flex-shrink:0;
                        background:<?= $css !== null ? $css : '#f3f4f6' ?>;
                        border:1px solid <?= \App\Services\ColorSwatch::needsOutline($label) ? '#9ca3af' : '#00000026' ?>;
                        box-shadow:<?= $on ? '0 0 0 2px #fff, 0 0 0 4px var(--blue)' : 'none' ?>;
                        <?= $css === null ? 'font-size:.55rem;line-height:1.9rem;text-align:center;color:#6b7280' : '' ?>">
                <?= $css === null ? e(mb_substr($label, 0, 3)) : '' ?>
              </a>
            <?php endforeach; ?>
          </div>
          <div id="swatchName" style="margin-top:.5rem;font-size:.88rem;color:var(--ink)">
            <strong><?= e((string)($pr['variant_label'] ?? '')) ?></strong>
            <span style="color:var(--mut)">
              · <?= (float)($pr['website_price'] ?? 0) > 0 ? money((float)$pr['website_price']) : 'call for price' ?>
            </span>
          </div>
          <script>
          (function () {
            var box = document.getElementById('swatches'), out = document.getElementById('swatchName');
            if (!box || !out) { return; }
            var original = out.innerHTML;
            box.addEventListener('mouseover', function (e) {
              var a = e.target.closest('a[data-label]');
              if (a) { out.innerHTML = '<strong>' + a.dataset.label + '</strong>' +
                       '<span style="color:var(--mut)"> · ' + a.dataset.price + '</span>'; }
            });
            // Back to the selected color when the pointer leaves, so the line always
            // describes what is actually in the basket-to-be rather than the last dot
            // the mouse happened to cross.
            box.addEventListener('mouseleave', function () { out.innerHTML = original; });
          }());
          </script>
        <?php else: ?>
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
        <?php endif; ?>
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

  </div>
</div>

<?php
/*
    Tabs below the buy area.

    Built from what the product actually has: a tab with nothing in it is not drawn,
    and when only one has content the strip is skipped entirely and the content shown
    plainly. An empty tab bar is a promise of information that is not there.
*/
$longText = trim((string)($pr['website_description'] ?? '')) ?: trim((string)($pr['long_description'] ?? ''));
$colorList = [];

foreach ($variants as $v) {
    if (strcasecmp((string)($pr['variant_axis'] ?? ''), 'Color') === 0 && ($v['variant_label'] ?? '') !== '') {
        $colorList[] = (string)$v['variant_label'];
    }
}

$hasAdditional = (float)($pr['gross_weight'] ?? 0) > 0 || (float)($pr['height'] ?? 0) > 0
              || $colorList !== [] || ($pr['uom_code'] ?? '') !== '';

$tabs = [];
if ($longText !== '')                 { $tabs['description']  = 'Description'; }
if ($hasAdditional)                   { $tabs['additional']   = 'Additional information'; }
if (!empty($docs['color-deck']))      { $tabs['color-deck']   = 'Color deck'; }
if (!empty($docs['specs']))           { $tabs['specs']        = 'Specs'; }
if (!empty($docs['literature']))      { $tabs['literature']   = 'Literature'; }
if (!empty($videos))                  { $tabs['videos']       = 'Videos'; }

$tabKeys  = array_keys($tabs);
$firstTab = $tabKeys[0] ?? null;

$docList = function (array $rows): void {
    foreach ($rows as $d) {
        $kb = (int)($d['file_size'] ?? 0) > 0 ? ' · ' . number_format($d['file_size'] / 1024) . ' KB' : '';
        printf(
            '<div style="padding:.6rem 0;border-bottom:1px solid #f3f4f6">'
            . '<a href="%s" target="_blank" rel="noopener" style="font-weight:600">%s</a>'
            . '<span style="color:#6b7280;font-size:.82rem">%s</span></div>',
            e((string)$d['file_path']),
            e((string)($d['title'] ?: $d['file_name'])),
            e($kb)
        );
    }
};
?>

<?php if ($firstTab !== null): ?>
<div class="wrap" style="padding-bottom:3rem">

  <?php if (count($tabs) > 1): ?>
    <div style="display:flex;gap:.25rem;border-bottom:1px solid var(--line);overflow-x:auto" id="ptabs">
      <?php foreach ($tabs as $key => $label): ?>
        <button type="button" data-tab="<?= e($key) ?>"
                style="appearance:none;background:none;border:0;border-bottom:3px solid <?= $key === $firstTab ? 'var(--blue)' : 'transparent' ?>;
                       padding:.8rem 1rem;font:inherit;font-size:.82rem;font-weight:700;cursor:pointer;
                       text-transform:uppercase;letter-spacing:.05em;white-space:nowrap;
                       color:<?= $key === $firstTab ? 'var(--blue)' : 'var(--mut)' ?>">
          <?= e($label) ?>
        </button>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div style="padding-top:1.5rem;max-width:62rem">

    <?php foreach ($tabs as $key => $label): ?>
      <section data-panel="<?= e($key) ?>" style="<?= $key === $firstTab ? '' : 'display:none' ?>">
        <?php if ($key === 'description'): ?>
          <div style="font-size:.95rem;color:#374151;line-height:1.7"><?= nl2br(e($longText)) ?></div>

        <?php elseif ($key === 'additional'): ?>
          <table style="border-collapse:collapse;width:100%;max-width:40rem">
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
            <?php if ($colorList !== []): ?>
              <tr>
                <th style="text-align:left;padding:.5rem .9rem .5rem 0;font-weight:600;color:#6b7280;
                           font-size:.84rem;vertical-align:top;white-space:nowrap">Colors</th>
                <td style="padding:.5rem 0;font-size:.9rem;line-height:1.7"><?= e(implode(', ', $colorList)) ?></td>
              </tr>
            <?php endif; ?>
          </table>

        <?php elseif ($key === 'videos'): ?>
          <?php foreach ($videos as $v): ?>
            <?php if ($v['embed'] !== null): ?>
              <div style="position:relative;padding-top:56.25%;margin-bottom:1.25rem;max-width:44rem">
                <iframe src="<?= e($v['embed']) ?>" title="Product video" loading="lazy"
                        allow="accelerometer; clipboard-write; encrypted-media; picture-in-picture"
                        allowfullscreen
                        style="position:absolute;inset:0;width:100%;height:100%;border:0;border-radius:10px"></iframe>
              </div>
            <?php else: ?>
              <div style="padding:.6rem 0">
                <a href="<?= e($v['url']) ?>" target="_blank" rel="noopener">Watch video</a>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>

        <?php else: ?>
          <?php $docList($docs[$key] ?? []); ?>
        <?php endif; ?>
      </section>
    <?php endforeach; ?>

  </div>
</div>

<?php if (count($tabs) > 1): ?>
<script>
(function () {
  var bar = document.getElementById('ptabs');
  if (!bar) { return; }
  bar.addEventListener('click', function (e) {
    var btn = e.target.closest('button[data-tab]');
    if (!btn) { return; }
    bar.querySelectorAll('button').forEach(function (b) {
      var on = b === btn;
      b.style.borderBottomColor = on ? 'var(--blue)' : 'transparent';
      b.style.color = on ? 'var(--blue)' : 'var(--mut)';
    });
    document.querySelectorAll('section[data-panel]').forEach(function (s) {
      s.style.display = s.dataset.panel === btn.dataset.tab ? '' : 'none';
    });
  });
}());
</script>
<?php endif; ?>
<?php endif; ?>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/site.php';
