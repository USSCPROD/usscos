<?php ob_start(); ?>
<?php
$th = 'padding:.5rem .8rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;'
    . 'color:#6b7280;background:#f8f9fb;border-bottom:1px solid #e5e7eb;text-align:left';
$td = 'padding:.6rem .8rem;font-size:.9rem';
$inp = 'width:100%;padding:.6rem .75rem;font-size:.95rem;font-family:inherit;border:1px solid #d1d5db;'
     . 'border-radius:6px;box-sizing:border-box;background:#fff;color:#111';
$lbl = 'display:block;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;'
     . 'color:#6b7280;margin-bottom:.3rem';

if (!function_exists('rsQty')) {
    function rsQty($n): string { return rtrim(rtrim(number_format((float)$n, 2), '0'), '.'); }
}

$isDraft   = $return['status'] === 'draft';
$restocked = 0.0;
$scrapped  = 0.0;
foreach ($lines as $l) {
    if ($l['item_condition'] === 'resellable') { $restocked += (float)$l['qty']; }
    else                                       { $scrapped  += (float)$l['qty']; }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <div style="font-size:.85rem;color:var(--color-text-muted);margin-bottom:.25rem">
            <a href="/inventory/returns" style="color:inherit">Returns</a> &rsaquo; <?= e($return['return_number']) ?>
        </div>
        <h1 class="page-title" style="margin:0">
            <?= e($return['company_name']) ?>
            <span class="badge <?= $isDraft ? 'badge--neutral' : 'badge--success' ?>" style="vertical-align:middle;margin-left:.5rem">
                <?= $isDraft ? 'Being booked in' : 'Booked in' ?>
            </span>
        </h1>
        <p class="page-subtitle" style="margin:.25rem 0 0">
            <?= e(\App\Services\ReturnService::REASONS[$return['reason']] ?? '') ?>
            <?php if ($return['invoice_number']): ?>
                · against invoice <?= e($return['invoice_number']) ?>
            <?php endif; ?>
            <?php if ($return['received_at']): ?>
                · booked in <?= date('M j, g:ia', strtotime($return['received_at'])) ?>
                <?= $return['received_by_name'] ? 'by ' . e($return['received_by_name']) : '' ?>
            <?php endif; ?>
        </p>
    </div>
</div>

<?php if ($isDraft && empty($return['invoice_id']) && !empty($invoices)): ?>
    <div class="alert" style="background:#fffbeb;border:1px solid #fcd34d;color:#92400e;padding:.85rem 1.1rem;margin-bottom:1.25rem;font-size:.88rem">
        No invoice chosen, so nothing can be priced. Lines added now will credit nothing.
        Recent invoices for this customer:
        <?php foreach (array_slice($invoices, 0, 5) as $iv): ?>
            <a href="/invoices/<?= (int)$iv['id'] ?>" style="color:#92400e;font-weight:600"><?= e($iv['invoice_number']) ?></a><?= $iv !== end($invoices) ? ' · ' : '' ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($isDraft): ?>
    <div class="card" style="padding:1.25rem;margin-bottom:1.25rem;background:#f8fafc">
        <form method="POST" action="/inventory/returns/<?= (int)$return['id'] ?>/line">
            <?= csrf_field() ?>
            <table style="width:100%;border-collapse:separate;border-spacing:.5rem 0;margin:0 -.5rem">
                <tr>
                    <td>
                        <label for="code" style="<?= $lbl ?>">Product</label>
                        <input type="text" name="code" id="code" required autocomplete="off"
                               placeholder="Scan or type a SKU"
                               style="<?= $inp ?>;font-family:monospace">
                    </td>
                    <td style="width:7rem">
                        <label for="qty" style="<?= $lbl ?>">Quantity</label>
                        <input type="number" name="qty" id="qty" step="0.01" min="0" required
                               style="<?= $inp ?>;text-align:right;font-weight:600">
                    </td>
                    <td style="width:16rem">
                        <label for="cond" style="<?= $lbl ?>">Condition</label>
                        <select name="item_condition" id="cond" required style="<?= $inp ?>" onchange="toggleLocation()">
                            <option value="">— Choose —</option>
                            <?php foreach ($conditions as $k => $v): ?>
                                <option value="<?= e($k) ?>"><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td style="width:14rem">
                        <label for="loc" style="<?= $lbl ?>">Back onto</label>
                        <select name="location_id" id="loc" style="<?= $inp ?>">
                            <option value="">— Not restocked —</option>
                            <?php foreach ($locations as $l): ?>
                                <option value="<?= (int)$l['id'] ?>">
                                    <?= $l['parent_code'] ? e($l['parent_code']) . ' · ' : '' ?><?= e($l['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td style="width:9rem;vertical-align:bottom">
                        <button type="submit" class="btn btn--primary" style="width:100%">Add</button>
                    </td>
                </tr>
            </table>
        </form>
        <div style="font-size:.78rem;color:#9ca3af;margin-top:.7rem">
            Only <strong>resellable</strong> goods go back on a shelf. Anything else is
            recorded but moves no stock — it left inventory when it was sold and is not going
            back on sale, so the count is already right.
        </div>
    </div>
<?php endif; ?>

<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin-bottom:1.25rem">
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr>
                <th style="<?= $th ?>">Product</th>
                <th style="<?= $th ?>;text-align:right">Qty</th>
                <th style="<?= $th ?>">Condition</th>
                <th style="<?= $th ?>">Back onto</th>
                <th style="<?= $th ?>;text-align:right">Credit</th>
                <th style="<?= $th ?>"></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($lines)): ?>
            <tr><td colspan="6" style="padding:2rem;text-align:center;color:#9ca3af">Nothing on this return yet.</td></tr>
        <?php else: ?>
            <?php foreach ($lines as $l): $sellable = $l['item_condition'] === 'resellable'; ?>
                <tr style="border-bottom:1px solid #f3f4f6">
                    <td style="<?= $td ?>">
                        <?= e($l['product_name']) ?>
                        <div class="font-mono text-xs text-muted"><?= e($l['sku'] ?? '') ?></div>
                        <?php if (!empty($l['note'])): ?>
                            <div style="font-size:.8rem;color:#6b7280"><?= e($l['note']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="<?= $td ?>;text-align:right;font-weight:600"><?= rsQty($l['qty']) ?></td>
                    <td style="<?= $td ?>">
                        <span class="badge <?= $sellable ? 'badge--success' : 'badge--warning' ?>">
                            <?= e(ucfirst($l['item_condition'])) ?>
                        </span>
                    </td>
                    <td style="<?= $td ?>;color:#6b7280">
                        <?= $sellable ? e($l['location_code'] ?? '—') : '<span style="color:#9ca3af">not restocked</span>' ?>
                    </td>
                    <td style="<?= $td ?>;text-align:right"><?= money((float)$l['line_credit']) ?></td>
                    <td style="<?= $td ?>;text-align:right;width:5rem">
                        <?php if ($isDraft): ?>
                            <form method="POST" action="/inventory/returns/<?= (int)$return['id'] ?>/line/<?= (int)$l['id'] ?>/remove" style="margin:0">
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
        <?php if ($lines): ?>
            <tfoot>
                <tr style="background:#f8f9fb;border-top:2px solid #e5e7eb">
                    <td style="<?= $td ?>;font-weight:600">
                        <?= rsQty($restocked) ?> back on the shelf<?= $scrapped > 0 ? ', ' . rsQty($scrapped) . ' not' : '' ?>
                    </td>
                    <td colspan="3"></td>
                    <td style="<?= $td ?>;text-align:right;font-weight:700">
                        <?= money((float)$return['credit_amount']) ?>
                        <?php if ((float)$return['credit_tax'] > 0): ?>
                            <div style="font-size:.75rem;font-weight:400;color:#6b7280">
                                incl. <?= money((float)$return['credit_tax']) ?> tax
                            </div>
                        <?php endif; ?>
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        <?php endif; ?>
    </table>
</div>

<?php if ($isDraft && $lines): ?>
    <form method="POST" action="/inventory/returns/<?= (int)$return['id'] ?>/receive" style="display:inline">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn--primary">Book It In</button>
    </form>
    <form method="POST" action="/inventory/returns/<?= (int)$return['id'] ?>/cancel" style="display:inline;margin-left:.5rem">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn--secondary" onclick="return confirm('Cancel this return?')">Cancel</button>
    </form>
<?php elseif (!$isDraft && $return['credit_status'] === 'pending' && (float)$return['credit_amount'] > 0): ?>
    <div class="card" style="padding:1.1rem 1.25rem;background:#fffbeb;border:1px solid #fcd34d">
        <div style="font-weight:600;color:#92400e;margin-bottom:.3rem">
            <?= money((float)$return['credit_amount']) ?> owed to this customer
        </div>
        <p style="margin:0 0 .7rem;font-size:.85rem;color:#92400e">
            Raise the credit in QuickBooks, then mark it here so it drops off the owed list.
            Tax is credited at <?= rtrim(rtrim(number_format((float)($return['tax_rate_applied'] ?? 0) * 100, 3), '0'), '.') ?>%,
            the rate the original invoice was charged at.
        </p>
        <form method="POST" action="/inventory/returns/<?= (int)$return['id'] ?>/credited">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--secondary btn--sm">Mark as Credited</button>
        </form>
    </div>
<?php endif; ?>

<div style="height:2rem"></div>

<script>
function toggleLocation() {
    var sellable = document.getElementById('cond').value === 'resellable';
    var loc = document.getElementById('loc');
    loc.required = sellable;
    loc.disabled = !sellable;
    if (!sellable) { loc.value = ''; }
}
toggleLocation();
</script>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
