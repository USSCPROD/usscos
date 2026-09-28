<?php ob_start(); ?>
<?php
// Bench sizing, like the picking screen — used standing up, sometimes with gloves.
$inp  = 'width:100%;padding:.65rem .8rem;font-size:1rem;font-family:inherit;border:1px solid #d1d5db;'
      . 'border-radius:6px;box-sizing:border-box;background:#fff;color:#111';
$lbl  = 'display:block;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;'
      . 'color:#6b7280;margin-bottom:.3rem';
$head = 'padding:.6rem .8rem;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;'
      . 'color:#6b7280;background:#f8f9fb;border-bottom:1px solid #e5e7eb;text-align:left';
$cell = 'padding:.8rem;font-size:1rem;vertical-align:middle';

if (!function_exists('poRcvQty')) {
    function poRcvQty($n): string { return rtrim(rtrim(number_format((float)$n, 2), '0'), '.'); }
}

$outstandingTotal = 0.0;
foreach ($lines as $l) {
    $outstandingTotal += max(0, (float)$l['qty_ordered'] - (float)$l['qty_received']);
}
?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/receiving" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Receiving
        </a>
        <h1 class="page-title" style="margin:.25rem 0 0">
            <?= e($po['po_number']) ?>
            <?php if (!empty($po['vendor_name'])): ?>
                <span style="font-weight:400;color:#6b7280"> — <?= e($po['vendor_name']) ?></span>
            <?php endif; ?>
        </h1>
        <p class="page-subtitle" style="margin:.3rem 0 0">
            Enter what actually arrived against each line. It does not have to match the PO —
            if it differs, that difference is recorded and reviewed rather than trimmed away.
        </p>
    </div>
</div>

<form method="POST" action="/receiving/po/<?= (int)$po['id'] ?>">
    <?= csrf_field() ?>

    <!-- Where it is going, and an optional scan to jump to a line -->
    <div class="card" style="padding:1.25rem;margin-bottom:1.25rem;background:#f8fafc">
        <table style="width:100%;border-collapse:separate;border-spacing:.6rem 0;margin:0 -.6rem">
            <tr>
                <td style="width:20rem">
                    <label for="locSel" style="<?= $lbl ?>">Putting it in</label>
                    <select name="location_id" id="locSel" required style="<?= $inp ?>">
                        <option value="">— Choose —</option>
                        <?php foreach ($locations as $l): ?>
                            <option value="<?= (int)$l['id'] ?>">
                                <?= $l['parent_code'] ? e($l['parent_code']) . ' · ' : '' ?><?= e($l['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <label for="scanInput" style="<?= $lbl ?>">
                        Scan or type to jump to a line — optional
                    </label>
                    <input type="text" id="scanInput" autocomplete="off" autocapitalize="off"
                           placeholder="Barcode or SKU, then Enter"
                           style="<?= $inp ?>;font-family:monospace">
                </td>
            </tr>
        </table>
        <div id="scanResult" style="margin-top:.7rem;display:none;padding:.65rem .85rem;border-radius:6px;font-size:.95rem"></div>
        <div style="font-size:.78rem;color:#9ca3af;margin-top:.7rem">
            You are holding the PO, so the quantities below are the quick way. Scanning just
            moves the cursor to the right line — it books nothing in on its own.
        </div>
    </div>

    <!-- The PO's own lines -->
    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin-bottom:1.25rem">
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr>
                    <th style="<?= $head ?>">Item</th>
                    <th style="<?= $head ?>;text-align:right">Ordered</th>
                    <th style="<?= $head ?>;text-align:right">Already in</th>
                    <th style="<?= $head ?>;text-align:right">Outstanding</th>
                    <th style="<?= $head ?>;text-align:right;width:9rem">Arrived now</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($lines)): ?>
                <tr><td colspan="5" style="padding:2rem;text-align:center;color:#9ca3af">Nothing on this PO.</td></tr>
            <?php else: ?>
                <?php foreach ($lines as $l): ?>
                    <?php $outstanding = (float)$l['qty_ordered'] - (float)$l['qty_received']; ?>
                    <tr id="row-<?= (int)$l['id'] ?>" style="border-bottom:1px solid #f3f4f6;<?= $outstanding <= 0 ? 'background:#fcfcfd' : '' ?>">
                        <td style="<?= $cell ?>">
                            <div style="font-weight:600"><?= e($l['product_name'] ?? $l['description'] ?? 'Item') ?></div>
                            <div style="font-size:.8rem;color:#6b7280;font-family:monospace">
                                <?= e($l['sku'] ?? '') ?>
                                <?php if (!empty($l['uom_code'])): ?> · <?= e($l['uom_code']) ?><?php endif; ?>
                            </div>
                        </td>
                        <td style="<?= $cell ?>;text-align:right"><?= poRcvQty($l['qty_ordered']) ?></td>
                        <td style="<?= $cell ?>;text-align:right;color:<?= (float)$l['qty_received'] > 0 ? '#16a34a' : '#9ca3af' ?>">
                            <?= poRcvQty($l['qty_received']) ?>
                        </td>
                        <td style="<?= $cell ?>;text-align:right;font-weight:<?= $outstanding > 0 ? '700' : '400' ?>;color:<?= $outstanding > 0 ? '#d97706' : '#9ca3af' ?>">
                            <?= $outstanding > 0 ? poRcvQty($outstanding) : '✓' ?>
                        </td>
                        <td style="<?= $cell ?>;text-align:right">
                            <?php // No max, and always enterable: a batch that over-yields is a fact. ?>
                            <input type="number" name="receive_qty[<?= (int)$l['id'] ?>]" id="qty-<?= (int)$l['id'] ?>"
                                   step="0.01" min="0"
                                   placeholder="<?= $outstanding > 0 ? poRcvQty($outstanding) : '0' ?>"
                                   style="width:100%;padding:.6rem;font-size:1.05rem;font-weight:600;text-align:right;
                                          border:1px solid #d1d5db;border-radius:6px;box-sizing:border-box">
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card" style="padding:1.1rem 1.25rem">
        <table style="width:100%;border-collapse:separate;border-spacing:.6rem 0;margin:0 -.6rem">
            <tr>
                <td>
                    <label for="notes" style="<?= $lbl ?>">Notes</label>
                    <input type="text" name="receipt_notes" id="notes" maxlength="255"
                           placeholder="Damage, short delivery, anything worth knowing"
                           style="<?= $inp ?>">
                </td>
                <td style="width:16rem;vertical-align:bottom">
                    <button type="submit" class="btn btn--primary" style="width:100%;padding:.75rem;font-size:1rem">
                        Book In &amp; Put Into Stock
                    </button>
                </td>
            </tr>
        </table>
        <div style="font-size:.78rem;color:#9ca3af;margin-top:.7rem">
            <?= poRcvQty($outstandingTotal) ?> still outstanding on this PO. Leave a line blank
            if none of it came — blank means nothing arrived, not zero ordered.
        </div>
    </div>
</form>

<div style="height:2rem"></div>

<script>
(function () {
    var input = document.getElementById('scanInput');
    var out   = document.getElementById('scanResult');

    function show(kind, html) {
        var c = { ok:['#f0fdf4','#bbf7d0','#166534'], bad:['#fef2f2','#fca5a5','#b91c1c'] }[kind];
        out.style.display = 'block';
        out.style.background = c[0];
        out.style.border = '1px solid ' + c[1];
        out.style.color = c[2];
        out.innerHTML = html;
    }

    input.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') { return; }
        e.preventDefault();

        var code = input.value.trim();
        input.value = '';
        if (!code) { return; }

        var body = new URLSearchParams();
        body.set('code', code);

        fetch('/receiving/po/<?= (int)$po['id'] ?>/lookup', {
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
            if (!d.found) { show('bad', '✕ ' + d.message); return; }

            var field = document.getElementById('qty-' + d.line_id);
            var row   = document.getElementById('row-' + d.line_id);

            if (!field) { show('bad', '✕ That line is not on screen.'); return; }

            // Fill the suggestion and select it, so the next keystroke replaces it. The
            // person still confirms — a scan never books anything in by itself.
            field.value = d.suggested;
            field.focus();
            field.select();

            if (row) {
                row.style.transition = 'background .4s';
                row.style.background = '#fffbeb';
                setTimeout(function () { row.style.background = ''; }, 1200);
            }

            show('ok', '✓ <strong>' + d.product + '</strong> — ' + d.outstanding + ' outstanding');
        })
        .catch(function () { show('bad', '✕ Could not reach the server.'); });
    });

    input.focus();
})();
</script>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
