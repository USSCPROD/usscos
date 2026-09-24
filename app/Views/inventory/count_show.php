<?php ob_start(); ?>
<?php
$th = 'padding:.5rem .8rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;'
    . 'color:#6b7280;background:#f8f9fb;border-bottom:1px solid #e5e7eb;text-align:left';
$td = 'padding:.6rem .8rem;font-size:.9rem';
$inp = 'width:100%;padding:.65rem .8rem;font-size:1rem;font-family:inherit;border:1px solid #d1d5db;'
     . 'border-radius:6px;box-sizing:border-box;background:#fff;color:#111';

if (!function_exists('csQty')) {
    function csQty($n): string { return rtrim(rtrim(number_format((float)$n, 2), '0'), '.'); }
}

$isCounting = $count['status'] === 'counting';
$isReview   = $count['status'] === 'review';
$isApplied  = $count['status'] === 'applied';

$counted   = count(array_filter($lines, fn($l) => $l['counted_qty'] !== null));
$variances = $isCounting ? 0 : count(array_filter(
    $lines,
    fn($l) => $l['counted_qty'] !== null && abs((float)$l['counted_qty'] - (float)$l['expected_qty']) > 0.0001
));
?>

<div class="page-header">
    <div class="page-header__left">
        <div style="font-size:.85rem;color:var(--color-text-muted);margin-bottom:.25rem">
            <a href="/inventory/counts" style="color:inherit">Counts</a> &rsaquo; <?= e($count['count_number']) ?>
        </div>
        <h1 class="page-title" style="margin:0">
            Counting <?= e($count['location_code']) ?>
            <span class="badge <?= $isCounting ? 'badge--warning' : ($isReview ? 'badge--info' : 'badge--success') ?>"
                  style="vertical-align:middle;margin-left:.5rem">
                <?= $isCounting ? 'Counting' : ($isReview ? 'Awaiting review' : ucfirst($count['status'])) ?>
            </span>
        </h1>
        <p class="page-subtitle" style="margin:.25rem 0 0">
            <?php if ($isCounting): ?>
                <?= $counted ?> of <?= count($lines) ?> counted. Count what is on the shelf —
                you are not being shown what the system expects, and that is deliberate.
            <?php elseif ($isReview): ?>
                <?= $variances ?> line<?= $variances === 1 ? '' : 's' ?> disagree with the system.
                Nothing has moved yet.
            <?php else: ?>
                Applied <?= $count['applied_at'] ? date('M j, g:ia', strtotime($count['applied_at'])) : '' ?>
                <?= $count['applied_by_name'] ? 'by ' . e($count['applied_by_name']) : '' ?>
            <?php endif; ?>
        </p>
    </div>
</div>

<?php if ($isCounting): ?>
    <div class="card" style="padding:1.25rem;margin-bottom:1.25rem;background:#f8fafc">
        <table style="width:100%;border-collapse:separate;border-spacing:.5rem 0;margin:0 -.5rem">
            <tr>
                <td>
                    <input type="text" id="scanInput" autocomplete="off" autocapitalize="off"
                           placeholder="Scan or type a SKU"
                           style="<?= $inp ?>;font-family:monospace;font-size:1.1rem;border-width:2px;border-color:#cbd5e1">
                </td>
                <td style="width:9rem">
                    <input type="number" id="qtyInput" step="0.01" min="0" placeholder="How many"
                           style="<?= $inp ?>;text-align:right;font-weight:600">
                </td>
                <td style="width:8rem">
                    <button type="button" class="btn btn--primary" style="width:100%" onclick="record()">Count</button>
                </td>
            </tr>
        </table>
        <div id="result" style="margin-top:.7rem;display:none;padding:.7rem .9rem;border-radius:6px;font-size:.95rem"></div>
        <div style="font-size:.78rem;color:#9ca3af;margin-top:.7rem">
            <strong>Zero is a valid answer.</strong> Counting zero says the shelf is empty,
            which is a finding. Leaving a line uncounted says nobody looked, and those are
            left alone when the count is applied.
        </div>
    </div>
<?php endif; ?>

<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin-bottom:1.25rem">
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr>
                <th style="<?= $th ?>">Product</th>
                <th style="<?= $th ?>;text-align:right">Counted</th>
                <?php if (!$isCounting): ?>
                    <th style="<?= $th ?>;text-align:right">System said</th>
                    <th style="<?= $th ?>;text-align:right">Variance</th>
                    <th style="<?= $th ?>">By</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($lines)): ?>
            <tr><td colspan="5" style="padding:2rem;text-align:center;color:#9ca3af">
                Nothing on this sheet — count whatever you find and it will be added.
            </td></tr>
        <?php else: ?>
            <?php foreach ($lines as $l):
                $done = $l['counted_qty'] !== null;
                $var  = $done && !$isCounting ? (float)$l['counted_qty'] - (float)$l['expected_qty'] : 0.0;
            ?>
                <tr style="border-bottom:1px solid #f3f4f6;<?= $done ? '' : 'background:#fcfcfd' ?>">
                    <td style="<?= $td ?>">
                        <?= e($l['product_name']) ?>
                        <div class="font-mono text-xs text-muted"><?= e($l['sku'] ?? '') ?></div>
                        <?php if (!empty($l['note'])): ?>
                            <div style="font-size:.8rem;color:#6b7280"><?= e($l['note']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="<?= $td ?>;text-align:right;font-weight:600">
                        <?= $done ? csQty($l['counted_qty']) : '<span style="color:#d1d5db">not counted</span>' ?>
                    </td>
                    <?php if (!$isCounting): ?>
                        <td style="<?= $td ?>;text-align:right;color:#6b7280">
                            <?= $done ? csQty($l['expected_qty']) : '—' ?>
                        </td>
                        <td style="<?= $td ?>;text-align:right;font-weight:600;color:<?= abs($var) < 0.0001 ? '#16a34a' : ($var > 0 ? '#0A3D91' : '#b91c1c') ?>">
                            <?php if (!$done): ?>
                                <span style="color:#d1d5db">—</span>
                            <?php elseif (abs($var) < 0.0001): ?>
                                ✓
                            <?php else: ?>
                                <?= $var > 0 ? '+' : '' ?><?= csQty($var) ?>
                            <?php endif; ?>
                        </td>
                        <td style="<?= $td ?>;color:#9ca3af;font-size:.82rem"><?= e($l['counted_by_name'] ?? '') ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($isCounting): ?>
    <form method="POST" action="/inventory/counts/<?= (int)$count['id'] ?>/submit" style="display:inline">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn--primary">Finished Counting — Show Variances</button>
    </form>
    <form method="POST" action="/inventory/counts/<?= (int)$count['id'] ?>/cancel" style="display:inline;margin-left:.5rem">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn--secondary" onclick="return confirm('Cancel this count?')">Cancel</button>
    </form>
<?php elseif ($isReview): ?>
    <form method="POST" action="/inventory/counts/<?= (int)$count['id'] ?>/apply" style="display:inline">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn--primary"
                onclick="return confirm('Apply this count? Stock will be corrected to the counted figures.')">
            Apply — Correct the Stock
        </button>
    </form>
    <form method="POST" action="/inventory/counts/<?= (int)$count['id'] ?>/reopen" style="display:inline;margin-left:.5rem">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn--secondary">Back to Counting</button>
    </form>
    <p style="font-size:.78rem;color:#9ca3af;margin:.8rem 0 0;max-width:46rem">
        Applying posts an ordinary adjustment for each line that disagrees, with the reason
        <em>count correction</em> — so it appears in the adjustments history like any other
        movement. Lines nobody counted are left alone: not counting something is not evidence
        that there is none of it.
    </p>
<?php endif; ?>

<div style="height:2rem"></div>

<?php if ($isCounting): ?>
<script>
function record() {
    var code = document.getElementById('scanInput');
    var qty  = document.getElementById('qtyInput');
    var out  = document.getElementById('result');

    function show(kind, html) {
        var c = { ok:['#f0fdf4','#bbf7d0','#166534'], warn:['#fffbeb','#fcd34d','#92400e'], bad:['#fef2f2','#fca5a5','#b91c1c'] }[kind];
        out.style.display = 'block';
        out.style.background = c[0]; out.style.border = '1px solid ' + c[1]; out.style.color = c[2];
        out.innerHTML = html;
    }

    if (!code.value.trim()) { code.focus(); return; }
    if (qty.value === '')   { show('bad', '✕ Enter how many are there — zero is a valid answer.'); qty.focus(); return; }

    var body = new URLSearchParams();
    body.set('code', code.value.trim());
    body.set('qty', qty.value);

    fetch('/inventory/counts/<?= (int)$count['id'] ?>/record', {
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

        // Deliberately says nothing about whether it matched. That is the whole point of
        // counting blind, and it is also why the response carries no expected figure.
        show(d.was_added ? 'warn' : 'ok',
             (d.was_added ? '⚠ ' : '✓ ') + '<strong>' + d.product.name + '</strong> — counted ' + d.counted +
             (d.was_added ? '<div style="font-size:.85rem;margin-top:.2rem">Not on the sheet. Added.</div>' : '') +
             (d.recount ? '<div style="font-size:.85rem;margin-top:.2rem">Recounted — the new figure replaces the old one.</div>' : ''));

        code.value = ''; qty.value = '';
        code.focus();
        setTimeout(function () { window.location.reload(); }, 900);
    })
    .catch(function () { show('bad', '✕ Could not reach the server.'); });
}

document.getElementById('scanInput').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); document.getElementById('qtyInput').focus(); }
});
document.getElementById('qtyInput').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); record(); }
});
document.getElementById('scanInput').focus();
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
