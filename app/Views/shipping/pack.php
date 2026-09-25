<?php ob_start(); ?>
<?php
// Same bench, same tablet, same oversized targets as the picking screen.
$cell = 'padding:.9rem .8rem;font-size:1rem;vertical-align:middle';
$head = 'padding:.6rem .8rem;font-size:.75rem;font-weight:700;text-transform:uppercase;'
      . 'letter-spacing:.04em;color:#6b7280;background:#f8f9fb;border-bottom:1px solid #e5e7eb;text-align:left';

$qty = fn($n) => rtrim(rtrim(number_format((float)$n, 2), '0'), '.');

// Only lines that were actually picked need to be in the box. A line picked at zero is a
// deliberate short, not something to verify.
$toVerify    = array_values(array_filter($lines, fn($l) => (float)$l['qty_picked'] > 0));
$totalPicked = array_sum(array_map(fn($l) => (float)$l['qty_picked'], $toVerify));
$totalPacked = array_sum(array_map(fn($l) => (float)$l['qty_packed'], $toVerify));
$pct         = $totalPicked > 0 ? min(100, round($totalPacked / $totalPicked * 100)) : 0;

$allMatch = $toVerify !== [] && !array_filter(
    $toVerify,
    fn($l) => abs((float)$l['qty_packed'] - (float)$l['qty_picked']) > 0.0001
);

$samePerson = !empty($order['picked_by']) && !empty($order['packed_by'])
           && (int)$order['picked_by'] === (int)$order['packed_by'];
?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/shipping" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Shipping Queue
        </a>
        <h1 class="page-title" style="margin:.25rem 0 0">
            Packing SO #<?= e($order['so_number']) ?>
            <span style="font-weight:400;color:#6b7280"> — <?= e($order['company_name']) ?></span>
        </h1>
        <p class="page-subtitle" style="margin:.3rem 0 0">
            Scan each item as it goes in the box. This checks the pick — not the order — so a
            short pick is not an error here.
        </p>
    </div>
</div>

<?php if ($order['pick_status'] === 'not_started'): ?>
    <div class="alert" style="background:#fffbeb;border:1px solid #fcd34d;color:#92400e;padding:.9rem 1.1rem;margin-bottom:1.25rem">
        Nothing has been picked on this order yet, so there is nothing to verify.
        <a href="/shipping/<?= (int)$order['id'] ?>/pick" style="color:#92400e;font-weight:600">Pick it first</a>.
    </div>
<?php endif; ?>

<!-- Scan box -->
<div class="card" style="padding:1.25rem;margin-bottom:1.25rem;background:#f8fafc">
    <label for="scanInput" style="display:block;font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.5rem">
        Scan into the box
    </label>
    <input type="text" id="scanInput" autocomplete="off" autocapitalize="off" autocorrect="off"
           placeholder="Scan a barcode, or type a SKU and press Enter"
           style="width:100%;padding:1rem 1.1rem;font-size:1.2rem;font-family:monospace;
                  border:2px solid #cbd5e1;border-radius:8px;box-sizing:border-box">

    <div id="scanResult" style="margin-top:.9rem;padding:.9rem 1rem;border-radius:8px;display:none;font-size:1.05rem;font-weight:600"></div>

    <div style="margin-top:.8rem;font-size:.8rem;color:#9ca3af">
        Anything not on this order will be refused — that is the point. Scanning a case
        barcode counts the whole case.
    </div>
</div>

<!-- Progress -->
<div style="margin-bottom:1rem">
    <div style="display:flex;justify-content:space-between;font-size:.85rem;color:#6b7280;margin-bottom:.35rem">
        <span id="progressText"><?= $qty($totalPacked) ?> of <?= $qty($totalPicked) ?> in the box</span>
        <span id="progressPct"><?= $pct ?>%</span>
    </div>
    <div style="height:10px;background:#e5e7eb;border-radius:5px;overflow:hidden">
        <div id="progressBar" style="height:100%;width:<?= $pct ?>%;background:<?= $allMatch ? '#16a34a' : '#0A3D91' ?>;transition:width .2s"></div>
    </div>
</div>

<?php if ($allMatch): ?>
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;border-radius:8px;padding:1rem 1.1rem;margin-bottom:1.25rem">
        <strong>✓ The box matches the pick.</strong> Ready to seal and ship.
        <?php if ($samePerson): ?>
            <div style="font-size:.85rem;margin-top:.3rem">
                Picked and packed by the same person — worth knowing, since a check on your own
                work catches less than a second pair of eyes.
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Lines -->
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin-bottom:1.25rem">
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr>
                <th style="<?= $head ?>">Item</th>
                <th style="<?= $head ?>;text-align:right">Picked</th>
                <th style="<?= $head ?>;text-align:right">In the box</th>
                <th style="<?= $head ?>"></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($toVerify)): ?>
            <tr><td colspan="4" style="padding:2rem;text-align:center;color:#9ca3af">
                Nothing picked on this order.
            </td></tr>
        <?php else: ?>
            <?php foreach ($toVerify as $l):
                $picked = (float)$l['qty_picked'];
                $packed = (float)$l['qty_packed'];
                $match  = abs($packed - $picked) < 0.0001;
                $over   = $packed > $picked;
            ?>
                <tr id="line-<?= (int)$l['id'] ?>"
                    style="border-bottom:1px solid #f3f4f6;background:<?= $over ? '#fef2f2' : ($match ? '#f0fdf4' : '#fff') ?>">
                    <td style="<?= $cell ?>">
                        <div style="font-weight:600"><?= e($l['product_name'] ?? $l['description'] ?? 'Item') ?></div>
                        <div style="font-size:.8rem;color:#6b7280;font-family:monospace">
                            <?= e($l['sku'] ?? $l['quickbooks_item'] ?? '') ?>
                            <?php if (!empty($l['uom_code'])): ?> · <?= e($l['uom_code']) ?><?php endif; ?>
                        </div>
                        <?php if (!empty($l['pack_note'])): ?>
                            <div style="font-size:.8rem;color:#b45309;margin-top:.2rem">⚠ <?= e($l['pack_note']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="<?= $cell ?>;text-align:right;font-size:1.1rem"><?= $qty($picked) ?></td>
                    <td style="<?= $cell ?>;text-align:right;font-size:1.25rem;font-weight:700;color:<?= $over ? '#dc2626' : ($match ? '#16a34a' : '#111') ?>"
                        id="packed-<?= (int)$l['id'] ?>"><?= $qty($packed) ?></td>
                    <td style="<?= $cell ?>;text-align:right;white-space:nowrap">
                        <form method="POST" action="/shipping/<?= (int)$order['id'] ?>/pack/line" style="display:inline-flex;gap:.4rem;align-items:center">
                            <?= csrf_field() ?>
                            <input type="hidden" name="line_id" value="<?= (int)$l['id'] ?>">
                            <input type="number" name="qty" step="0.01" min="0" value="<?= $qty($packed) ?>"
                                   style="width:5.5rem;padding:.6rem;font-size:1rem;border:1px solid #d1d5db;border-radius:6px;text-align:right">
                            <button type="submit" class="btn btn--secondary" style="padding:.6rem 1rem;font-size:.95rem">Set</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Sealing a box that does not match -->
<?php if (!$allMatch && !empty($toVerify)): ?>
    <div class="card" style="padding:1.25rem">
        <div style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.6rem">
            Shipping it anyway?
        </div>
        <p style="margin:0 0 .7rem;font-size:.9rem;color:#6b7280">
            Sometimes the box is right and the pick was wrong. Say what happened and it is
            recorded as a mismatch rather than a clean verification — which is the honest
            version, and the only way anyone learns where the errors come from.
        </p>
        <form method="POST" action="/shipping/<?= (int)$order['id'] ?>/pack/mismatch">
            <?= csrf_field() ?>
            <textarea name="pack_note" rows="2" placeholder="e.g. one case was damaged and swapped, pick count was wrong"
                      style="width:100%;padding:.8rem;font-size:1rem;border:1px solid #d1d5db;border-radius:8px;box-sizing:border-box;resize:vertical"><?= e($order['pack_note'] ?? '') ?></textarea>
            <div style="text-align:right;margin-top:.7rem">
                <button type="submit" class="btn" style="padding:.7rem 1.4rem;font-size:1rem;background:#fff;color:#b45309;border:1px solid #fcd34d">
                    Record Mismatch &amp; Continue
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>

<div style="height:2rem"></div>

<script>
(function () {
    var input  = document.getElementById('scanInput');
    var result = document.getElementById('scanResult');

    function refocus() {
        var a = document.activeElement;
        if (!a || a === document.body || a === input) { input.focus(); }
    }
    refocus();
    setInterval(refocus, 1500);
    document.addEventListener('click', function (e) {
        if (!e.target.closest('input, textarea, button, a')) { refocus(); }
    });

    function show(kind, text) {
        var c = {
            ok:   ['#f0fdf4', '#bbf7d0', '#166534'],
            warn: ['#fffbeb', '#fcd34d', '#92400e'],
            bad:  ['#fef2f2', '#fca5a5', '#b91c1c']
        }[kind];
        result.style.display    = 'block';
        result.style.background = c[0];
        result.style.border     = '2px solid ' + c[1];
        result.style.color      = c[2];
        result.textContent      = text;
    }

    input.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') { return; }
        e.preventDefault();

        var code = input.value.trim();
        input.value = '';
        if (!code) { return; }

        var body = new URLSearchParams();
        body.set('code', code);

        fetch('/shipping/<?= (int)$order['id'] ?>/pack/scan', {
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
            if (d.status === 'not_on_order') {
                // The error worth stopping for. Loud, and it does not clear itself.
                show('bad', '✕ ' + d.message);
                return;
            }
            if (!d.ok) { show(d.status === 'over' ? 'warn' : 'bad', '⚠ ' + d.message); }
            else       { show('ok', '✓ ' + d.message); }

            var cell = document.getElementById('packed-' + d.line_id);
            if (cell) { cell.textContent = String(d.qty_packed).replace(/\.00$/, ''); }

            setTimeout(function () { window.location.reload(); }, 600);
        })
        .catch(function () { show('bad', '✕ Could not reach the server.'); });
    });
})();
</script>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
