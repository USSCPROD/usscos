<?php ob_start(); ?>
<?php
// Deliberately larger type and targets than the rest of the app: this is a shared
// tablet at a bench, used standing up, sometimes with gloves on.
$cell   = 'padding:.9rem .8rem;font-size:1rem;vertical-align:middle';
$head   = 'padding:.6rem .8rem;font-size:.75rem;font-weight:700;text-transform:uppercase;'
        . 'letter-spacing:.04em;color:#6b7280;background:#f8f9fb;border-bottom:1px solid #e5e7eb;text-align:left';

$totalOrdered = array_sum(array_map(fn($l) => (float)$l['qty_ordered'], $lines));
$totalPicked  = array_sum(array_map(fn($l) => (float)$l['qty_picked'], $lines));
$pctDone      = $totalOrdered > 0 ? min(100, round($totalPicked / $totalOrdered * 100)) : 0;

$qty = fn($n) => rtrim(rtrim(number_format((float)$n, 2), '0'), '.');
?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/shipping" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Shipping Queue
        </a>
        <h1 class="page-title" style="margin:.25rem 0 0">
            SO #<?= e($order['so_number']) ?>
            <span style="font-weight:400;color:#6b7280"> — <?= e($order['company_name']) ?></span>
        </h1>
    </div>
</div>

<!-- Scan box: always focused, so a keyboard-mode scanner just works -->
<div class="card" style="padding:1.25rem;margin-bottom:1.25rem;background:#f8fafc">
    <label for="scanInput" style="display:block;font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.5rem">
        Scan item
    </label>
    <input type="text" id="scanInput" autocomplete="off" autocapitalize="off" autocorrect="off"
           placeholder="Scan a barcode, or type a SKU and press Enter"
           style="width:100%;padding:1rem 1.1rem;font-size:1.2rem;font-family:monospace;
                  border:2px solid #cbd5e1;border-radius:8px;box-sizing:border-box">

    <div id="scanResult" style="margin-top:.9rem;padding:.9rem 1rem;border-radius:8px;display:none;font-size:1.05rem;font-weight:600"></div>

    <div style="margin-top:.8rem;font-size:.8rem;color:#9ca3af">
        Scanning a case barcode counts the whole case. If a label won't scan, type the SKU
        or use <strong>Set</strong> on the line.
    </div>
</div>

<!-- Progress -->
<div style="margin-bottom:1rem">
    <div style="display:flex;justify-content:space-between;font-size:.85rem;color:#6b7280;margin-bottom:.35rem">
        <span id="progressText"><?= $qty($totalPicked) ?> of <?= $qty($totalOrdered) ?> picked</span>
        <span id="progressPct"><?= $pctDone ?>%</span>
    </div>
    <div style="height:10px;background:#e5e7eb;border-radius:5px;overflow:hidden">
        <div id="progressBar" style="height:100%;width:<?= $pctDone ?>%;background:#16a34a;transition:width .2s"></div>
    </div>
</div>

<!-- Lines -->
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin-bottom:1.25rem">
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr>
                <th style="<?= $head ?>">Item</th>
                <th style="<?= $head ?>;text-align:right">Ordered</th>
                <th style="<?= $head ?>;text-align:right">Picked</th>
                <th style="<?= $head ?>"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($lines as $l):
            $ordered = (float)$l['qty_ordered'];
            $picked  = (float)$l['qty_picked'];
            $done    = $picked >= $ordered && $ordered > 0;
            $over    = $picked > $ordered;
        ?>
            <tr id="line-<?= (int)$l['id'] ?>" style="border-bottom:1px solid #f3f4f6;background:<?= $over ? '#fef2f2' : ($done ? '#f0fdf4' : '#fff') ?>">
                <td style="<?= $cell ?>">
                    <div style="font-weight:600"><?= e($l['product_name'] ?? $l['description'] ?? 'Item') ?></div>
                    <div style="font-size:.8rem;color:#6b7280;font-family:monospace">
                        <?= e($l['sku'] ?? $l['quickbooks_item'] ?? '') ?>
                        <?php if (!empty($l['uom_code'])): ?> · <?= e($l['uom_code']) ?><?php endif; ?>
                    </div>
                    <?php if (!empty($l['pick_note'])): ?>
                        <div style="font-size:.8rem;color:#b91c1c;margin-top:.2rem">⚠ <?= e($l['pick_note']) ?></div>
                    <?php endif; ?>
                </td>
                <td style="<?= $cell ?>;text-align:right;font-size:1.1rem"><?= $qty($ordered) ?></td>
                <td style="<?= $cell ?>;text-align:right;font-size:1.25rem;font-weight:700;color:<?= $over ? '#dc2626' : ($done ? '#16a34a' : '#111') ?>"
                    id="picked-<?= (int)$l['id'] ?>"><?= $qty($picked) ?></td>
                <td style="<?= $cell ?>;text-align:right;white-space:nowrap">
                    <form method="POST" action="/shipping/<?= (int)$order['id'] ?>/pick/line" style="display:inline-flex;gap:.4rem;align-items:center">
                        <?= csrf_field() ?>
                        <input type="hidden" name="line_id" value="<?= (int)$l['id'] ?>">
                        <input type="number" name="qty" step="0.01" min="0" value="<?= $qty($picked) ?>"
                               style="width:5.5rem;padding:.6rem;font-size:1rem;border:1px solid #d1d5db;border-radius:6px;text-align:right">
                        <button type="submit" class="btn btn--secondary" style="padding:.6rem 1rem;font-size:.95rem">Set</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPicked > 0 && $totalPicked >= $totalOrdered): ?>
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:1rem 1.1rem;margin-bottom:1.25rem">
        <strong style="color:#166534">Everything picked.</strong>
        <span style="color:#166534">Now verify the box against the pick before it is sealed.</span>
        <a href="/shipping/<?= (int)$order['id'] ?>/pack" class="btn btn--primary"
           style="margin-left:.6rem;padding:.5rem 1.1rem">Pack &amp; Verify</a>
    </div>
<?php endif; ?>

<!-- Short stock -->
<div class="card" style="padding:1.25rem;max-width:100%">
    <div style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.6rem">
        Can't complete this order?
    </div>
    <form method="POST" action="/shipping/<?= (int)$order['id'] ?>/short">
        <?= csrf_field() ?>
        <textarea name="pick_note" rows="2" placeholder="What's missing? e.g. only 4 cases of white in stock, need 10"
                  style="width:100%;padding:.8rem;font-size:1rem;border:1px solid #d1d5db;border-radius:8px;box-sizing:border-box;resize:vertical"><?= e($order['pick_note'] ?? '') ?></textarea>
        <div style="text-align:right;margin-top:.7rem">
            <button type="submit" class="btn" style="padding:.7rem 1.4rem;font-size:1rem;background:#fff;color:#b45309;border:1px solid #fcd34d">
                Report Short Stock
            </button>
        </div>
    </form>
</div>

<div style="height:2rem"></div>

<script>
(function () {
    var input  = document.getElementById('scanInput');
    var result = document.getElementById('scanResult');

    // A shared station: keep focus on the scan box so a scanner always lands somewhere
    // useful, but never steal it while someone is typing in another field.
    function refocus() {
        var a = document.activeElement;
        if (!a || a === document.body || a === input) { input.focus(); }
    }
    refocus();
    setInterval(refocus, 1500);
    document.addEventListener('click', function (e) {
        if (!e.target.closest('input, textarea, button, a')) { refocus(); }
    });

    function show(kind, message) {
        var colours = {
            ok:    ['#f0fdf4', '#bbf7d0', '#166534'],
            over:  ['#fffbeb', '#fcd34d', '#92400e'],
            bad:   ['#fef2f2', '#fca5a5', '#b91c1c']
        }[kind];
        result.style.display      = 'block';
        result.style.background   = colours[0];
        result.style.border       = '1px solid ' + colours[1];
        result.style.color        = colours[2];
        result.textContent        = message;
    }

    function fmt(n) { return String(parseFloat(n)); }

    input.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') { return; }
        e.preventDefault();

        var code = input.value.trim();
        input.value = '';
        if (!code) { return; }

        var body = new URLSearchParams();
        body.set('code', code);

        fetch(window.location.pathname.replace(/\/pick$/, '') + '/pick/scan', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body.toString()
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d.status === 'ok' || d.status === 'over') {
                var cell = document.getElementById('picked-' + d.line_id);
                if (cell) {
                    cell.textContent = fmt(d.qty_picked);
                    var row  = document.getElementById('line-' + d.line_id);
                    var done = parseFloat(d.qty_picked) >= parseFloat(d.qty_ordered);
                    var over = d.status === 'over';
                    row.style.background = over ? '#fef2f2' : (done ? '#f0fdf4' : '#fff');
                    cell.style.color     = over ? '#dc2626' : (done ? '#16a34a' : '#111');
                }
                show(d.status === 'over' ? 'over' : 'ok',
                     (d.status === 'over' ? '⚠ ' : '✓ ') + d.message +
                     (d.step > 1 ? '  (+' + fmt(d.step) + ')' : ''));
            } else {
                show('bad', '✕ ' + d.message);
            }
        })
        .catch(function () { show('bad', '✕ Could not reach the server — check the connection.'); });
    });
})();
</script>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
