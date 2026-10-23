<?php ob_start(); ?>
<?php
// Variables, not view-local functions — see CLAUDE.md on duplicate declarations.
$lbQ = fn($k, $v) => http_build_query(array_merge(
    ['q' => $query, 'symbol' => $symbol, 'copies' => $copies],
    [$k => $v]
));
$lbTab = 'display:inline-block;padding:.4rem .8rem;border:1px solid #d1d5db;border-radius:5px;'
       . 'text-decoration:none;font-size:.85rem;margin-right:.35rem';
?>

<style>
/*
   Two layouts in one document. On screen this is a normal page with controls;
   on paper the controls and the chrome vanish and what is left is a grid of
   labels at a fixed physical size.

   The sizes below are in millimetres on purpose. A barcode is only scannable
   within a tolerance of its nominal width, so the label must come off the
   printer at a real-world size rather than at whatever a percentage works out
   to on the day.
*/
.lbl-sheet { display:grid; grid-template-columns:repeat(3, 63.5mm); gap:0; }
.lbl {
    width:63.5mm; height:38.1mm;           /* Avery 5160-ish, 3 across */
    box-sizing:border-box; padding:2.5mm;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    text-align:center; overflow:hidden; break-inside:avoid;
    border:1px dashed #e5e7eb;             /* a cutting guide on screen only */
}
.lbl svg { max-width:100%; height:auto; }
.lbl-sku  { font-family:monospace; font-weight:700; font-size:9pt; margin-bottom:1mm; }
.lbl-name { font-size:6.5pt; color:#374151; line-height:1.2; margin-top:1mm;
            display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }

@media print {
    @page { margin:12.7mm 4.7mm; }
    .no-print, .page-header, .sidebar, nav, header, footer { display:none !important; }
    .lbl { border:none; }
    body { background:#fff; }
}
</style>

<div class="page-header no-print" style="display:flex;justify-content:space-between;align-items:flex-start">
    <div>
        <h1 class="page-title" style="margin:0">Print barcode labels</h1>
        <p class="page-subtitle" style="margin:.3rem 0 0">
            <?= count($products) ?> label<?= count($products) === 1 ? '' : 's' ?><?php
            if ($copies > 1): ?> &times; <?= (int)$copies ?> copies<?php endif; ?>.
            Printed at true size — don't let the print dialog scale to fit, or the bars
            come out the wrong width and the scanner won't read them.
        </p>
    </div>
    <div>
        <button type="button" class="btn btn--primary" onclick="window.print()">Print</button>
        <a href="/products" class="btn btn--secondary">Products</a>
    </div>
</div>

<div class="card no-print" style="padding:1rem;margin-bottom:1.25rem">
    <form method="GET" action="/products/labels" style="display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap">
        <div>
            <label style="display:block;font-size:.7rem;font-weight:700;text-transform:uppercase;
                          letter-spacing:.05em;color:#6b7280;margin-bottom:.3rem">Which products</label>
            <input type="text" name="q" value="<?= e($query) ?>" placeholder="SKU or name, blank for all active"
                   style="width:22rem;padding:.45rem .6rem;border:1px solid #d1d5db;border-radius:5px">
        </div>
        <div>
            <label style="display:block;font-size:.7rem;font-weight:700;text-transform:uppercase;
                          letter-spacing:.05em;color:#6b7280;margin-bottom:.3rem">Symbol</label>
            <select name="symbol" style="padding:.45rem .6rem;border:1px solid #d1d5db;border-radius:5px">
                <option value="auto" <?= $symbol === 'auto' ? 'selected' : '' ?>>Best available</option>
                <option value="upc"  <?= $symbol === 'upc'  ? 'selected' : '' ?>>UPC-A (unit)</option>
                <option value="case" <?= $symbol === 'case' ? 'selected' : '' ?>>GS1-128 (case)</option>
                <option value="sku"  <?= $symbol === 'sku'  ? 'selected' : '' ?>>Code 128 (our SKU)</option>
            </select>
        </div>
        <div>
            <label style="display:block;font-size:.7rem;font-weight:700;text-transform:uppercase;
                          letter-spacing:.05em;color:#6b7280;margin-bottom:.3rem">Copies each</label>
            <input type="number" name="copies" min="1" max="50" step="1" value="<?= (int)$copies ?>"
                   style="width:5rem;padding:.45rem .6rem;border:1px solid #d1d5db;border-radius:5px">
        </div>
        <button type="submit" class="btn btn--secondary">Build sheet</button>
    </form>

    <p class="text-muted" style="margin:.8rem 0 0;font-size:.82rem">
        <strong>Best available</strong> prints the UPC where a product has one and a Code 128
        of the SKU where it doesn't — which is how the 738 products with no GTIN become
        scannable without waiting for the revised product file.
    </p>
</div>

<?php if (!empty($skipped)): ?>
    <div class="alert no-print" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;margin-bottom:1.25rem">
        <strong><?= count($skipped) ?> not printed.</strong>
        A code that fails its own check digit scans as nothing, or as a different product,
        so these are left out rather than printed wrong:
        <ul style="margin:.5rem 0 0;padding-left:1.2rem">
            <?php foreach (array_slice($skipped, 0, 15) as $s): ?>
                <li><code><?= e($s['sku']) ?></code> — <?= e($s['reason']) ?></li>
            <?php endforeach; ?>
            <?php if (count($skipped) > 15): ?>
                <li>…and <?= count($skipped) - 15 ?> more</li>
            <?php endif; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (empty($products)): ?>
    <div class="card no-print" style="padding:2rem;text-align:center;color:#6b7280">
        Nothing matched. Try a different search, or a different symbol.
    </div>
<?php else: ?>
    <div class="lbl-sheet">
        <?php foreach ($products as $p): ?>
            <?php for ($c = 0; $c < $copies; $c++): ?>
                <div class="lbl">
                    <div class="lbl-sku"><?= e($p['sku']) ?></div>
                    <?= $p['svg'] /* built by App\Services\Barcode, not user input */ ?>
                    <div class="lbl-name"><?= e($p['name']) ?></div>
                </div>
            <?php endfor; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
