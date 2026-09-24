<?php ob_start(); ?>
<?php
$th = 'padding:.5rem .8rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;'
    . 'color:#6b7280;background:#f8f9fb;border-bottom:1px solid #e5e7eb;text-align:left';
$td = 'padding:.6rem .8rem;font-size:.9rem';
$inp = 'width:100%;padding:.65rem .8rem;font-size:1rem;font-family:inherit;border:1px solid #d1d5db;'
     . 'border-radius:6px;box-sizing:border-box;background:#fff;color:#111';

if (!function_exists('tsQty')) {
    function tsQty($n): string { return rtrim(rtrim(number_format((float)$n, 2), '0'), '.'); }
}

$isDraft   = $transfer['status'] === 'draft';
$isTransit = $transfer['status'] === 'in_transit';
$isClosed  = in_array($transfer['status'], ['received', 'short', 'cancelled'], true);

$statusMap = [
    'draft'      => ['badge--neutral', 'Loading'],
    'in_transit' => ['badge--warning', 'On the truck'],
    'received'   => ['badge--success', 'Arrived'],
    'short'      => ['badge--danger',  'Arrived short'],
    'cancelled'  => ['badge--neutral', 'Cancelled'],
];
[$badgeCls, $badgeText] = $statusMap[$transfer['status']] ?? ['badge--neutral', $transfer['status']];

$totalSent = array_sum(array_map(fn($l) => (float)$l['qty_sent'], $lines));
$totalIn   = array_sum(array_map(fn($l) => (float)$l['qty_received'], $lines));
?>

<div class="page-header">
    <div class="page-header__left">
        <div style="font-size:.85rem;color:var(--color-text-muted);margin-bottom:.25rem">
            <a href="/inventory/transfers" style="color:inherit">Transfers</a> &rsaquo; <?= e($transfer['transfer_number']) ?>
        </div>
        <h1 class="page-title" style="margin:0">
            <?= e($transfer['from_code']) ?> &rarr; <?= e($transfer['to_code']) ?>
            <span class="badge <?= $badgeCls ?>" style="vertical-align:middle;margin-left:.5rem"><?= $badgeText ?></span>
        </h1>
        <p class="page-subtitle" style="margin:.25rem 0 0">
            <?php if ($isDraft): ?>
                Scan everything going on the truck, then send it. Nothing moves until you do.
            <?php elseif ($isTransit): ?>
                On its way. Scan each item as it comes off the truck at <?= e($transfer['to_code']) ?>.
            <?php else: ?>
                Sent <?= $transfer['sent_at'] ? date('M j, g:ia', strtotime($transfer['sent_at'])) : '' ?>
                <?= $transfer['sent_by_name'] ? 'by ' . e($transfer['sent_by_name']) : '' ?>
                <?php if ($transfer['received_at']): ?>
                    · received <?= date('M j, g:ia', strtotime($transfer['received_at'])) ?>
                    <?= $transfer['received_by_name'] ? 'by ' . e($transfer['received_by_name']) : '' ?>
                <?php endif; ?>
            <?php endif; ?>
        </p>
    </div>
</div>

<?php if (!$isClosed): ?>
    <div class="card" style="padding:1.25rem;margin-bottom:1.25rem;background:#f8fafc">
        <label for="scanInput" style="display:block;font-size:.72rem;font-weight:700;text-transform:uppercase;
                                      letter-spacing:.05em;color:#6b7280;margin-bottom:.3rem">
            <?= $isDraft ? 'Scan onto the truck' : 'Scan off the truck' ?>
        </label>
        <table style="width:100%;border-collapse:separate;border-spacing:.5rem 0;margin:0 -.5rem">
            <tr>
                <td>
                    <input type="text" id="scanInput" autocomplete="off" autocapitalize="off"
                           placeholder="Barcode or SKU, then Enter"
                           style="<?= $inp ?>;font-family:monospace;font-size:1.1rem;border-width:2px;border-color:#cbd5e1">
                </td>
                <td style="width:9rem">
                    <input type="number" id="qtyInput" value="1" step="0.01" min="0"
                           style="<?= $inp ?>;text-align:right;font-weight:600" title="Quantity">
                </td>
            </tr>
        </table>
        <div id="scanResult" style="margin-top:.7rem;display:none;padding:.7rem .9rem;border-radius:6px;font-size:.95rem"></div>
    </div>
<?php endif; ?>

<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin-bottom:1.25rem">
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr>
                <th style="<?= $th ?>">Product</th>
                <th style="<?= $th ?>;text-align:right">Sent</th>
                <?php if (!$isDraft): ?>
                    <th style="<?= $th ?>;text-align:right">Arrived</th>
                    <th style="<?= $th ?>;text-align:right">Difference</th>
                <?php endif; ?>
                <th style="<?= $th ?>"></th>
            </tr>
        </thead>
        <tbody id="lineBody">
        <?php if (empty($lines)): ?>
            <tr><td colspan="5" style="padding:2rem;text-align:center;color:#9ca3af">
                Nothing on this transfer yet.
            </td></tr>
        <?php else: ?>
            <?php foreach ($lines as $l): $gap = (float)$l['qty_sent'] - (float)$l['qty_received']; ?>
                <tr style="border-bottom:1px solid #f3f4f6">
                    <td style="<?= $td ?>">
                        <?= e($l['product_name']) ?>
                        <div class="font-mono text-xs text-muted"><?= e($l['sku'] ?? '') ?></div>
                        <?php if (!empty($l['note'])): ?>
                            <div style="font-size:.8rem;color:#b45309"><?= e($l['note']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="<?= $td ?>;text-align:right;font-weight:600"><?= tsQty($l['qty_sent']) ?></td>
                    <?php if (!$isDraft): ?>
                        <td style="<?= $td ?>;text-align:right"><?= tsQty($l['qty_received']) ?></td>
                        <td style="<?= $td ?>;text-align:right;font-weight:600;color:<?= abs($gap) < 0.0001 ? '#16a34a' : '#b91c1c' ?>">
                            <?= abs($gap) < 0.0001 ? '✓' : ($gap > 0 ? '−' . tsQty($gap) : '+' . tsQty(-$gap)) ?>
                        </td>
                    <?php endif; ?>
                    <td style="<?= $td ?>;text-align:right;width:5rem">
                        <?php if ($isDraft): ?>
                            <form method="POST" action="/inventory/transfers/<?= (int)$transfer['id'] ?>/line/<?= (int)$l['id'] ?>/remove" style="margin:0">
                                <?= csrf_field() ?>
                                <button type="submit" style="background:none;border:none;padding:0;cursor:pointer;
                                                             color:#9ca3af;font-size:.75rem;text-decoration:underline">Remove</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
        <?php if ($lines && !$isDraft): ?>
            <tfoot>
                <tr style="background:#f8f9fb;border-top:2px solid #e5e7eb">
                    <td style="<?= $td ?>;font-weight:600">Total</td>
                    <td style="<?= $td ?>;text-align:right;font-weight:600"><?= tsQty($totalSent) ?></td>
                    <td style="<?= $td ?>;text-align:right;font-weight:600"><?= tsQty($totalIn) ?></td>
                    <td style="<?= $td ?>;text-align:right;font-weight:700;color:<?= abs($totalSent - $totalIn) < 0.0001 ? '#16a34a' : '#b91c1c' ?>">
                        <?= abs($totalSent - $totalIn) < 0.0001 ? '✓' : tsQty($totalSent - $totalIn) . ' missing' ?>
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>
</div>

<?php if ($isDraft && $lines): ?>
    <form method="POST" action="/inventory/transfers/<?= (int)$transfer['id'] ?>/send" style="display:inline">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn--primary"
                onclick="return confirm('Send this transfer? The stock leaves <?= e($transfer['from_code']) ?> now and counts at neither end until it is received.')">
            Send — Stock Leaves <?= e($transfer['from_code']) ?>
        </button>
    </form>
    <form method="POST" action="/inventory/transfers/<?= (int)$transfer['id'] ?>/cancel" style="display:inline;margin-left:.5rem">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn--secondary"
                onclick="return confirm('Cancel this transfer?')">Cancel</button>
    </form>
<?php elseif ($isTransit): ?>
    <form method="POST" action="/inventory/transfers/<?= (int)$transfer['id'] ?>/close">
        <?= csrf_field() ?>
        <table style="width:100%;border-collapse:separate;border-spacing:.5rem 0;margin:0 -.5rem">
            <tr>
                <td>
                    <input type="text" name="note" maxlength="255"
                           placeholder="If anything is missing, say what happened"
                           style="<?= $inp ?>">
                </td>
                <td style="width:15rem">
                    <button type="submit" class="btn btn--primary" style="width:100%">Finish Unloading</button>
                </td>
            </tr>
        </table>
    </form>
    <p style="font-size:.78rem;color:#9ca3af;margin:.7rem 0 0;max-width:44rem">
        Anything not scanned in stays missing rather than quietly arriving. A short transfer
        is recorded as short — the truck has gone, and pretending otherwise is how the count
        stops meaning anything.
    </p>
<?php endif; ?>

<div style="height:2rem"></div>

<?php if (!$isClosed): ?>
<script>
(function () {
    var input = document.getElementById('scanInput');
    var qty   = document.getElementById('qtyInput');
    var out   = document.getElementById('scanResult');
    var dir   = <?= $isDraft ? "'out'" : "'in'" ?>;

    function show(kind, html) {
        var c = {
            ok:   ['#f0fdf4', '#bbf7d0', '#166534'],
            warn: ['#fffbeb', '#fcd34d', '#92400e'],
            bad:  ['#fef2f2', '#fca5a5', '#b91c1c']
        }[kind];
        out.style.display    = 'block';
        out.style.background = c[0];
        out.style.border     = '1px solid ' + c[1];
        out.style.color      = c[2];
        out.innerHTML        = html;
    }

    input.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') { return; }
        e.preventDefault();

        var code = input.value.trim();
        if (!code) { return; }

        var body = new URLSearchParams();
        body.set('code', code);
        body.set('qty', qty.value || '1');
        body.set('direction', dir);

        fetch('/inventory/transfers/<?= (int)$transfer['id'] ?>/scan', {
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
            if (!d.ok) { show('bad', '✕ ' + d.message); return; }

            input.value = '';

            if (dir === 'out') {
                var short = d.available !== undefined && d.available < parseFloat(qty.value || '1');
                show(short ? 'warn' : 'ok',
                     (short ? '⚠ ' : '✓ ') + '<strong>' + d.product.name + '</strong> added' +
                     (d.available !== undefined
                        ? '<div style="font-size:.85rem;margin-top:.2rem">' + d.available +
                          ' recorded at this location' +
                          (short ? ' — less than you are sending. Worth a count.' : '') + '</div>'
                        : ''));
            } else {
                var done = d.received >= d.sent;
                show(done ? 'ok' : 'warn',
                     (done ? '✓ ' : '• ') + '<strong>' + d.product.name + '</strong>' +
                     '<div style="font-size:.85rem;margin-top:.2rem">' + d.received + ' of ' + d.sent +
                     ' received</div>');
            }

            // Reload so the table reflects reality rather than a guess about it.
            setTimeout(function () { window.location.reload(); }, 700);
        })
        .catch(function () { show('bad', '✕ Could not reach the server.'); });
    });

    input.focus();
})();
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
