<?php ob_start(); ?>
<?php
$th = 'padding:.5rem .8rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;'
    . 'color:#6b7280;background:#f8f9fb;border-bottom:1px solid #e5e7eb;text-align:left';
$td = 'padding:.6rem .8rem;font-size:.87rem';
$inp = 'width:100%;padding:.55rem .7rem;font-size:.9rem;font-family:inherit;border:1px solid #d1d5db;'
     . 'border-radius:6px;box-sizing:border-box;background:#fff;color:#111';

if (!function_exists('rtQty')) {
    function rtQty($n): string { return rtrim(rtrim(number_format((float)$n, 2), '0'), '.'); }
}
if (!function_exists('rtBadge')) {
    function rtBadge(string $s): array
    {
        return [
            'draft'     => ['badge--neutral', 'Being booked in'],
            'received'  => ['badge--success', 'Booked in'],
            'closed'    => ['badge--neutral', 'Closed'],
            'cancelled' => ['badge--neutral', 'Cancelled'],
        ][$s] ?? ['badge--neutral', $s];
    }
}

$owed = array_sum(array_map(fn($c) => (float)$c['credit_amount'], $credits));
?>

<div class="page-header">
    <div class="page-header__left">
        <div style="font-size:.85rem;color:var(--color-text-muted);margin-bottom:.25rem">
            <a href="/inventory" style="color:inherit">Inventory</a> &rsaquo; Returns
        </div>
        <h1 class="page-title" style="margin:0">Returns</h1>
        <p class="page-subtitle" style="margin:.25rem 0 0">
            What came back, whether it can be sold again, and what we owe for it. Only
            resellable stock goes back on the shelf.
        </p>
    </div>
</div>

<?php if (!empty($credits)): ?>
    <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:1rem 1.1rem;margin-bottom:1.25rem">
        <div style="font-weight:600;color:#92400e;margin-bottom:.4rem">
            <?= money($owed) ?> of credits still owed to customers
        </div>
        <p style="margin:0 0 .6rem;font-size:.85rem;color:#92400e">
            These returns are booked in and the stock is dealt with, but the customer has not
            been credited yet. Credits are raised in QuickBooks for now — see the note at the
            bottom.
        </p>
        <table style="width:100%;border-collapse:collapse">
            <?php foreach ($credits as $c): ?>
                <tr style="border-top:1px solid #fde68a">
                    <td style="padding:.35rem .2rem;font-size:.87rem">
                        <a href="/inventory/returns/<?= (int)$c['id'] ?>" style="color:#92400e;font-weight:600;text-decoration:none">
                            <?= e($c['return_number']) ?>
                        </a>
                        · <?= e($c['company_name']) ?>
                        <?php if ($c['invoice_number']): ?>
                            <span style="color:#a16207">· invoice <?= e($c['invoice_number']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:.35rem .2rem;text-align:right;font-weight:600;color:#92400e;width:8rem">
                        <?= money((float)$c['credit_amount']) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>

<div class="card" style="padding:1.1rem 1.25rem;margin-bottom:1.5rem;background:#f8fafc">
    <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.7rem">
        Start a return
    </div>
    <form method="POST" action="/inventory/returns">
        <?= csrf_field() ?>
        <table style="width:100%;border-collapse:separate;border-spacing:.5rem 0;margin:0 -.5rem">
            <tr>
                <td style="width:30%">
                    <input type="number" name="customer_id" required placeholder="Customer ID" style="<?= $inp ?>">
                </td>
                <td style="width:24%">
                    <select name="reason" required style="<?= $inp ?>">
                        <option value="">Why —</option>
                        <?php foreach ($reasons as $k => $v): ?>
                            <option value="<?= e($k) ?>"><?= e($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <input type="text" name="notes" maxlength="500" placeholder="Note — optional" style="<?= $inp ?>">
                </td>
                <td style="width:140px">
                    <button type="submit" class="btn btn--primary" style="width:100%">Start</button>
                </td>
            </tr>
        </table>
    </form>
    <div style="font-size:.75rem;color:#9ca3af;margin-top:.6rem">
        The invoice it came off is chosen on the next screen, which is what prices the credit
        at what the customer actually paid.
    </div>
</div>

<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden">
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr>
                <th style="<?= $th ?>">Return</th>
                <th style="<?= $th ?>">Customer</th>
                <th style="<?= $th ?>">Against</th>
                <th style="<?= $th ?>;text-align:center">Status</th>
                <th style="<?= $th ?>;text-align:right">Items</th>
                <th style="<?= $th ?>;text-align:right">Credit</th>
                <th style="<?= $th ?>">When</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($returns)): ?>
            <tr><td colspan="7" style="padding:2rem;text-align:center;color:#9ca3af">No returns yet.</td></tr>
        <?php else: ?>
            <?php foreach ($returns as $r): [$cls, $label] = rtBadge($r['status']); ?>
                <tr style="border-bottom:1px solid #f3f4f6">
                    <td style="<?= $td ?>">
                        <a href="/inventory/returns/<?= (int)$r['id'] ?>"
                           style="color:#0A3D91;font-weight:600;text-decoration:none"><?= e($r['return_number']) ?></a>
                    </td>
                    <td style="<?= $td ?>"><?= e($r['company_name']) ?></td>
                    <td style="<?= $td ?>;color:#6b7280"><?= e($r['invoice_number'] ?? '—') ?></td>
                    <td style="<?= $td ?>;text-align:center"><span class="badge <?= $cls ?>"><?= $label ?></span></td>
                    <td style="<?= $td ?>;text-align:right"><?= rtQty($r['qty_total']) ?></td>
                    <td style="<?= $td ?>;text-align:right">
                        <?= money((float)$r['credit_amount']) ?>
                        <?php if ($r['credit_status'] === 'pending' && (float)$r['credit_amount'] > 0 && $r['status'] === 'received'): ?>
                            <div style="font-size:.72rem;color:#b45309">owed</div>
                        <?php elseif ($r['credit_status'] === 'issued'): ?>
                            <div style="font-size:.72rem;color:#16a34a">credited</div>
                        <?php endif; ?>
                    </td>
                    <td style="<?= $td ?>;color:#9ca3af;font-size:.82rem"><?= date('M j', strtotime($r['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<p style="font-size:.78rem;color:#9ca3af;margin:1rem 0 2rem;max-width:46rem">
    <strong>Credits are calculated here but raised in QuickBooks.</strong> USSCOS works out
    what is owed, including tax at the rate the original invoice was charged at, and lists it
    above. It does not raise a credit memo, because nothing in the reporting yet distinguishes
    one from an invoice — a credit memo today would be counted as revenue by Sales by Rep,
    A/R aging and customer lifetime value.
</p>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
