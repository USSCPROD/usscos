<?php ob_start(); ?>
<?php
$inp = 'width:100%;padding:.6rem .7rem;font-size:.95rem;font-family:inherit;border:1px solid #d1d5db;'
     . 'border-radius:6px;box-sizing:border-box;background:#fff;color:#111';
$lbl = 'display:block;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;'
     . 'color:#6b7280;margin-bottom:.3rem';
$th  = 'padding:.5rem .8rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;'
     . 'color:#6b7280;background:#f8f9fb;border-bottom:1px solid #e5e7eb;text-align:left';
$td  = 'padding:.55rem .8rem;font-size:.87rem';

// Uniquely named and guarded — see the CLAUDE.md note about two views defining the same
// function in one request.
if (!function_exists('adjQty')) {
    function adjQty($n): string
    {
        return rtrim(rtrim(number_format((float)$n, 2), '0'), '.');
    }
}
if (!function_exists('adjReason')) {
    function adjReason(?string $code): string
    {
        $label = \App\Services\AdjustmentService::REASONS[$code ?? ''] ?? 'Adjustment';

        return explode(' — ', $label)[0];
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <div style="font-size:.85rem;color:var(--color-text-muted);margin-bottom:.25rem">
            <a href="/inventory" style="color:inherit">Inventory</a> &rsaquo; Adjustments
        </div>
        <h1 class="page-title" style="margin:0">Stock Adjustments</h1>
        <p class="page-subtitle" style="margin:.25rem 0 0">
            Put the count right when it disagrees with the shelf. Every adjustment needs a
            reason, so that a year from now the pattern is readable — what we lose to damage,
            which products are always short, which location drifts.
        </p>
    </div>
</div>

<?php if (!empty($negatives)): ?>
    <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:1rem 1.1rem;margin-bottom:1.25rem">
        <div style="font-weight:600;color:#b91c1c;margin-bottom:.5rem">
            <?= count($negatives) ?> item<?= count($negatives) === 1 ? '' : 's' ?> showing below zero
        </div>
        <p style="margin:0 0 .6rem;font-size:.85rem;color:#7f1d1d">
            Stock is allowed to go negative on purpose — more went out than the count knew
            about, and refusing to record it would only hide the problem. Each of these is
            asking to be counted.
        </p>
        <table style="width:100%;border-collapse:collapse">
            <?php foreach ($negatives as $n): ?>
                <tr style="border-top:1px solid #fecaca">
                    <td style="padding:.4rem .2rem;font-size:.87rem">
                        <strong><?= e($n['product_name']) ?></strong>
                        <span class="font-mono text-xs" style="color:#b91c1c"> · <?= e($n['sku'] ?? '') ?></span>
                    </td>
                    <td style="padding:.4rem .2rem;font-size:.87rem;color:#7f1d1d"><?= e($n['location_code']) ?></td>
                    <td style="padding:.4rem .2rem;font-size:.95rem;font-weight:700;color:#b91c1c;text-align:right;width:6rem">
                        <?= adjQty($n['qty_on_hand']) ?>
                    </td>
                    <td style="padding:.4rem .2rem;text-align:right;width:7rem">
                        <button type="button" class="btn btn--sm btn--secondary"
                                onclick="fixNegative('<?= e($n['sku'] ?? '') ?>', <?= (int)$n['location_id'] ?>)">
                            Count it
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>

<div class="card" style="padding:1.25rem;margin-bottom:1.5rem;background:#f8fafc">
    <form method="POST" action="/inventory/adjustments" id="adjForm">
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" id="productId">

        <label for="scanInput" style="<?= $lbl ?>">Product</label>
        <input type="text" id="scanInput" autocomplete="off" autocapitalize="off"
               placeholder="Scan a barcode or type a SKU, then Enter"
               style="<?= $inp ?>;font-family:monospace;border-width:2px;border-color:#cbd5e1">

        <div id="scanResult" style="margin-top:.7rem;display:none"></div>

        <table style="width:100%;border-collapse:separate;border-spacing:.5rem 0;margin:.9rem -.5rem 0">
            <tr>
                <td style="width:28%">
                    <label for="locSel" style="<?= $lbl ?>">Location</label>
                    <select name="location_id" id="locSel" required style="<?= $inp ?>" onchange="showCurrent()">
                        <option value="">— Choose —</option>
                        <?php foreach ($locations as $l): ?>
                            <option value="<?= (int)$l['id'] ?>">
                                <?= $l['parent_code'] ? e($l['parent_code']) . ' · ' : '' ?><?= e($l['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div id="currentQty" style="font-size:.78rem;color:#6b7280;margin-top:.3rem;min-height:1rem"></div>
                </td>
                <td style="width:22%">
                    <label for="modeSel" style="<?= $lbl ?>">What happened</label>
                    <select name="mode" id="modeSel" style="<?= $inp ?>" onchange="showCurrent()">
                        <option value="set">Counted — set it to</option>
                        <option value="remove">Take off</option>
                        <option value="add">Put on</option>
                    </select>
                </td>
                <td style="width:15%">
                    <label for="qtyInput" style="<?= $lbl ?>">Quantity</label>
                    <input type="number" name="qty" id="qtyInput" step="0.01" required
                           style="<?= $inp ?>;text-align:right;font-weight:600">
                </td>
                <td>
                    <label for="reasonSel" style="<?= $lbl ?>">Reason</label>
                    <select name="reason_code" id="reasonSel" required style="<?= $inp ?>">
                        <option value="">— Choose —</option>
                        <?php foreach ($reasons as $code => $label): ?>
                            <option value="<?= e($code) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>

        <table style="width:100%;border-collapse:separate;border-spacing:.5rem 0;margin:.7rem -.5rem 0">
            <tr>
                <td>
                    <label for="notesInput" style="<?= $lbl ?>">Note</label>
                    <input type="text" name="notes" id="notesInput" maxlength="255"
                           placeholder="What happened — required for a known loss or 'other'"
                           style="<?= $inp ?>">
                </td>
                <td style="width:170px;vertical-align:bottom">
                    <button type="submit" class="btn btn--primary" style="width:100%;padding:.6rem">
                        Correct the Count
                    </button>
                </td>
            </tr>
        </table>
    </form>

    <div style="font-size:.78rem;color:#9ca3af;margin-top:.8rem">
        <strong>Counted</strong> is what somebody with a clipboard says — it records what the
        system had claimed, which is the finding worth keeping. <strong>Take off</strong> and
        <strong>put on</strong> are for a known event, like a dropped pail.
    </div>
</div>

<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden">
    <div style="padding:.7rem 1rem;border-bottom:1px solid #e5e7eb;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280">
        Recent adjustments
    </div>
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr>
                <th style="<?= $th ?>">Product</th>
                <th style="<?= $th ?>">Where</th>
                <th style="<?= $th ?>;text-align:right">Change</th>
                <th style="<?= $th ?>">Reason</th>
                <th style="<?= $th ?>">Note</th>
                <th style="<?= $th ?>">By</th>
                <th style="<?= $th ?>">When</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($recent)): ?>
            <tr><td colspan="7" style="padding:2rem;text-align:center;color:#9ca3af">
                No adjustments yet.
            </td></tr>
        <?php else: ?>
            <?php foreach ($recent as $a): $up = (float)$a['qty'] > 0; ?>
                <tr style="border-bottom:1px solid #f3f4f6">
                    <td style="<?= $td ?>">
                        <?= e($a['product_name']) ?>
                        <div class="font-mono text-xs text-muted"><?= e($a['sku'] ?? '') ?></div>
                    </td>
                    <td style="<?= $td ?>;color:#6b7280"><?= e($a['location_code'] ?? '—') ?></td>
                    <td style="<?= $td ?>;text-align:right;font-weight:600;color:<?= $up ? '#16a34a' : '#b45309' ?>">
                        <?= $up ? '+' : '' ?><?= adjQty($a['qty']) ?>
                    </td>
                    <td style="<?= $td ?>">
                        <span class="badge badge--neutral"><?= e(adjReason($a['reason_code'])) ?></span>
                    </td>
                    <td style="<?= $td ?>;color:#6b7280;font-size:.82rem"><?= e($a['notes'] ?? '') ?></td>
                    <td style="<?= $td ?>;color:#6b7280;font-size:.82rem"><?= e($a['user_name'] ?? '') ?></td>
                    <td style="<?= $td ?>;color:#9ca3af;font-size:.82rem"><?= date('M j, g:ia', strtotime($a['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div style="height:2rem"></div>

<script>
(function () {
    var stockHere = {};   // location id -> qty, for the product currently selected

    window.showCurrent = function () {
        var loc  = document.getElementById('locSel').value;
        var mode = document.getElementById('modeSel').value;
        var out  = document.getElementById('currentQty');
        var qty  = document.getElementById('qtyInput');

        if (!loc || !document.getElementById('productId').value) { out.textContent = ''; return; }

        var have = stockHere[loc] !== undefined ? stockHere[loc] : 0;
        out.textContent = 'System says ' + have + ' here';

        // Prefill a count with what the system thinks, so the common case — it agrees — is
        // one keystroke, and a disagreement is a deliberate edit rather than a blank field.
        if (mode === 'set' && !qty.value) { qty.value = have; }
    };

    window.fixNegative = function (sku, locationId) {
        document.getElementById('scanInput').value = sku;
        lookup(sku, locationId);
    };

    function lookup(code, preselectLocation) {
        var body = new URLSearchParams();
        body.set('code', code);

        fetch('/inventory/adjustments/lookup', {
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
            var out = document.getElementById('scanResult');
            out.style.display = 'block';

            if (!d.found) {
                document.getElementById('productId').value = '';
                out.innerHTML = '<div style="background:#fef2f2;border:1px solid #fca5a5;color:#b91c1c;' +
                                'padding:.7rem .9rem;border-radius:6px">✕ ' + d.message + '</div>';
                return;
            }

            document.getElementById('productId').value = d.product.id;

            stockHere = {};
            var rows = '';
            (d.locations || []).forEach(function (l) {
                stockHere[l.location_id] = parseFloat(l.qty_on_hand);
                rows += '<tr><td style="padding:.2rem .6rem .2rem 0;color:#6b7280">' + (l.code || '') +
                        '</td><td style="padding:.2rem 0;font-weight:600">' + parseFloat(l.qty_on_hand) + '</td></tr>';
            });

            out.innerHTML =
                '<div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;padding:.7rem .9rem;border-radius:6px">' +
                '<strong>' + d.product.name + '</strong> <span style="font-family:monospace;font-size:.85rem">' +
                (d.product.sku || '') + '</span>' +
                (rows ? '<table style="margin-top:.4rem;font-size:.85rem">' + rows + '</table>'
                      : '<div style="font-size:.85rem;margin-top:.3rem">Not recorded at any location yet.</div>') +
                '</div>';

            if (preselectLocation) { document.getElementById('locSel').value = preselectLocation; }
            document.getElementById('qtyInput').value = '';
            showCurrent();
            document.getElementById('qtyInput').focus();
        })
        .catch(function () {
            document.getElementById('scanResult').style.display = 'block';
            document.getElementById('scanResult').innerHTML =
                '<div style="background:#fef2f2;border:1px solid #fca5a5;color:#b91c1c;padding:.7rem .9rem;' +
                'border-radius:6px">✕ Could not reach the server.</div>';
        });
    }

    document.getElementById('scanInput').addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') { return; }
        e.preventDefault();
        var code = this.value.trim();
        if (code) { lookup(code); }
    });

    document.getElementById('adjForm').addEventListener('submit', function (e) {
        if (!document.getElementById('productId').value) {
            e.preventDefault();
            alert('Scan or type a product first.');
        }
    });

    document.getElementById('scanInput').focus();
})();
</script>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
