<?php ob_start(); ?>
<?php
// Sized for a handheld held at chest height in a warehouse, like the picking screen.
$inp = 'width:100%;padding:.7rem .8rem;font-size:1rem;font-family:inherit;border:1px solid #d1d5db;'
     . 'border-radius:6px;box-sizing:border-box;background:#fff;color:#111';
$lbl = 'display:block;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;'
     . 'color:#6b7280;margin-bottom:.35rem';
$qty = fn($n) => rtrim(rtrim(number_format((float)$n, 2), '0'), '.');
?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title" style="margin:0">Receiving</h1>
        <p class="page-subtitle" style="margin:.25rem 0 0">
            Book in paint as it arrives. Scan a barcode or type a SKU — whatever is on the
            carton.
        </p>
    </div>
</div>

<div class="card" style="padding:1.25rem;margin-bottom:1.25rem;background:#f8fafc">
    <form method="POST" action="/receiving" id="receiveForm">
        <?= csrf_field() ?>
        <input type="hidden" name="product_id"   id="productId">
        <input type="hidden" name="qty_expected" id="qtyExpected">

        <label for="scanInput" style="<?= $lbl ?>">Scan or type</label>
        <input type="text" id="scanInput" autocomplete="off" autocapitalize="off" autocorrect="off"
               placeholder="Barcode or SKU, then Enter"
               style="<?= $inp ?>;font-size:1.15rem;font-family:monospace;border-width:2px;border-color:#cbd5e1">

        <div id="scanResult" style="margin-top:.8rem;padding:.85rem 1rem;border-radius:8px;display:none;font-size:1rem"></div>

        <table style="width:100%;border-collapse:separate;border-spacing:.5rem 0;margin:.9rem -.5rem 0">
            <tr>
                <td style="width:26%">
                    <label for="unitSel" style="<?= $lbl ?>">Arriving as</label>
                    <select id="unitSel" style="<?= $inp ?>">
                        <option value="unit">Units</option>
                        <option value="case">Cases</option>
                        <option value="pallet">Pallets</option>
                    </select>
                </td>
                <td style="width:22%">
                    <label for="qtyInput" style="<?= $lbl ?>">Quantity</label>
                    <input type="number" name="qty" id="qtyInput" step="0.01" min="0"
                           style="<?= $inp ?>;font-size:1.15rem;font-weight:600;text-align:right">
                </td>
                <td>
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
            </tr>
        </table>

        <table style="width:100%;border-collapse:separate;border-spacing:.5rem 0;margin:.7rem -.5rem 0">
            <tr>
                <td style="width:30%">
                    <label for="refInput" style="<?= $lbl ?>">Reference</label>
                    <input type="text" name="reference_num" id="refInput" maxlength="50"
                           placeholder="PO, BOL or batch" style="<?= $inp ?>">
                </td>
                <td>
                    <label for="notesInput" style="<?= $lbl ?>">Notes</label>
                    <input type="text" name="notes" id="notesInput" maxlength="255"
                           placeholder="Damage, short delivery, anything worth knowing" style="<?= $inp ?>">
                </td>
                <td style="width:160px;vertical-align:bottom">
                    <button type="submit" class="btn btn--primary" style="width:100%;padding:.7rem;font-size:1rem">
                        Book In
                    </button>
                </td>
            </tr>
        </table>
    </form>

    <div style="font-size:.78rem;color:#9ca3af;margin-top:.8rem">
        The quantity is a suggestion from the pack size — <strong>change it to what actually
        arrived.</strong> A batch does not always yield what the pallet says, and a corrected
        figure is recorded so a wrong pack quantity gets noticed.
    </div>
</div>

<!-- What has been booked in -->
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden">
    <div style="padding:.7rem 1rem;border-bottom:1px solid #e5e7eb;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280">
        Recently booked in
    </div>
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr>
                <?php $th = 'padding:.5rem .8rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;background:#f8f9fb;border-bottom:1px solid #e5e7eb;text-align:left'; ?>
                <th style="<?= $th ?>">Product</th>
                <th style="<?= $th ?>;text-align:right">Qty</th>
                <th style="<?= $th ?>">Into</th>
                <th style="<?= $th ?>">Reference</th>
                <th style="<?= $th ?>">By</th>
                <th style="<?= $th ?>">When</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($recent)): ?>
            <tr><td colspan="6" style="padding:2rem;text-align:center;color:#9ca3af">
                Nothing booked in yet.
            </td></tr>
        <?php else: ?>
            <?php foreach ($recent as $r): $td = 'padding:.55rem .8rem;font-size:.87rem'; ?>
                <tr style="border-bottom:1px solid #f3f4f6">
                    <td style="<?= $td ?>">
                        <div style="font-weight:500"><?= e($r['product_name']) ?></div>
                        <div style="font-size:.78rem;color:#9ca3af;font-family:monospace"><?= e($r['sku'] ?? '') ?></div>
                    </td>
                    <td style="<?= $td ?>;text-align:right;font-weight:600"><?= $qty($r['qty']) ?></td>
                    <td style="<?= $td ?>;color:#6b7280"><?= e($r['to_code'] ?? '—') ?></td>
                    <td style="<?= $td ?>;color:#6b7280;font-size:.8rem">
                        <?= e($r['reference_num'] ?? '') ?>
                        <?php if (!empty($r['notes'])): ?>
                            <div style="color:#b45309"><?= e($r['notes']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="<?= $td ?>;color:#6b7280;font-size:.8rem"><?= e($r['user_name'] ?? '') ?></td>
                    <td style="<?= $td ?>;color:#9ca3af;font-size:.8rem"><?= date('M j, g:ia', strtotime($r['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div style="height:2rem"></div>

<script>
(function () {
    var input = document.getElementById('scanInput');
    var out   = document.getElementById('scanResult');
    var pid   = document.getElementById('productId');
    var qtyIn = document.getElementById('qtyInput');
    var expIn = document.getElementById('qtyExpected');
    var unit  = document.getElementById('unitSel');

    // Keep the scan box focused so a handheld always lands somewhere useful, but never
    // steal focus while someone is typing a quantity or choosing a location.
    function refocus() {
        var a = document.activeElement;
        if (!a || a === document.body) { input.focus(); }
    }
    refocus();
    setInterval(refocus, 2000);

    function show(kind, html) {
        var c = { ok:['#f0fdf4','#bbf7d0','#166534'], warn:['#fffbeb','#fcd34d','#92400e'], bad:['#fef2f2','#fca5a5','#b91c1c'] }[kind];
        out.style.display    = 'block';
        out.style.background  = c[0];
        out.style.border      = '1px solid ' + c[1];
        out.style.color       = c[2];
        out.innerHTML         = html;
    }

    function lookup(code) {
        var body = new URLSearchParams();
        body.set('code', code);
        body.set('unit', unit.value);

        fetch('/receiving/lookup', {
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
            if (!d.found) { pid.value = ''; show('bad', '✕ ' + d.message); return; }

            pid.value   = d.product.id;
            qtyIn.value = d.suggested_qty;
            expIn.value = d.suggested_qty;

            var msg = '<strong>' + d.message + '</strong>'
                    + '<div style="font-size:.82rem;margin-top:.2rem">'
                    + (d.product.sku || '') + ' · suggesting ' + d.suggested_qty + ' (' + d.basis + ')'
                    + '</div>';

            if (d.varies) {
                show('warn', '⚠ ' + msg + '<div style="font-size:.82rem;margin-top:.3rem">'
                    + '<strong>Pallet quantity varies for this product — count it.</strong></div>');
            } else {
                show('ok', '✓ ' + msg);
            }
            qtyIn.focus();
            qtyIn.select();
        })
        .catch(function () { show('bad', '✕ Could not reach the server — check the connection.'); });
    }

    input.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') { return; }
        e.preventDefault();
        var code = input.value.trim();
        input.value = '';
        if (code) { lookup(code); }
    });

    // Changing the unit re-suggests, so switching to Pallets after scanning works.
    unit.addEventListener('change', function () {
        if (pid.value) { /* keep it simple: ask the user to rescan for a new suggestion */
            show('warn', 'Unit changed — rescan the item to get a new suggested quantity, or type it.');
        }
    });

    document.getElementById('receiveForm').addEventListener('submit', function (e) {
        if (!pid.value) { e.preventDefault(); show('bad', '✕ Scan or type a product first.'); }
    });
})();
</script>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
