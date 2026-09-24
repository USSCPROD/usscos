<?php ob_start(); ?>
<?php
$th = 'padding:.5rem .8rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;'
    . 'color:#6b7280;background:#f8f9fb;border-bottom:1px solid #e5e7eb;text-align:left';
$td = 'padding:.6rem .8rem;font-size:.87rem';
$inp = 'width:100%;padding:.55rem .7rem;font-size:.9rem;font-family:inherit;border:1px solid #d1d5db;'
     . 'border-radius:6px;box-sizing:border-box;background:#fff;color:#111';

// Guarded and uniquely named — see the CLAUDE.md note.
if (!function_exists('trQty')) {
    function trQty($n): string { return rtrim(rtrim(number_format((float)$n, 2), '0'), '.'); }
}
if (!function_exists('trBadge')) {
    function trBadge(string $s): array
    {
        return [
            'draft'      => ['badge--neutral', 'Loading'],
            'in_transit' => ['badge--warning', 'On the truck'],
            'received'   => ['badge--success', 'Arrived'],
            'short'      => ['badge--danger',  'Arrived short'],
            'cancelled'  => ['badge--neutral', 'Cancelled'],
        ][$s] ?? ['badge--neutral', $s];
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <div style="font-size:.85rem;color:var(--color-text-muted);margin-bottom:.25rem">
            <a href="/inventory" style="color:inherit">Inventory</a> &rsaquo; Transfers
        </div>
        <h1 class="page-title" style="margin:0">Transfers</h1>
        <p class="page-subtitle" style="margin:.25rem 0 0">
            Stock moving between buildings. Scanned out of one and into the other, so a
            pallet that never arrives is a dated question rather than a slow mystery.
        </p>
    </div>
</div>

<?php if (!empty($inTransit)): ?>
    <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:1rem 1.1rem;margin-bottom:1.25rem">
        <div style="font-weight:600;color:#92400e;margin-bottom:.4rem">On a truck right now</div>
        <p style="margin:0 0 .6rem;font-size:.85rem;color:#92400e">
            This stock has left one building and not arrived at the other, so it counts at
            neither and the on-hand total is lower by this much. That is correct — it cannot
            be picked at either end while it is moving.
        </p>
        <table style="width:100%;border-collapse:collapse">
            <?php foreach ($inTransit as $t): ?>
                <tr style="border-top:1px solid #fde68a">
                    <td style="padding:.35rem .2rem;font-size:.87rem">
                        <?= e($t['product_name']) ?>
                        <span class="font-mono text-xs" style="color:#a16207"> · <?= e($t['sku'] ?? '') ?></span>
                    </td>
                    <td style="padding:.35rem .2rem;font-size:.87rem;font-weight:600;text-align:right;width:6rem;color:#92400e">
                        <?= trQty($t['qty']) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>

<div class="card" style="padding:1.1rem 1.25rem;margin-bottom:1.5rem;background:#f8fafc">
    <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.7rem">
        Start a transfer
    </div>
    <form method="POST" action="/inventory/transfers">
        <?= csrf_field() ?>
        <table style="width:100%;border-collapse:separate;border-spacing:.5rem 0;margin:0 -.5rem">
            <tr>
                <td style="width:26%">
                    <select name="from_location_id" required style="<?= $inp ?>">
                        <option value="">From —</option>
                        <?php foreach ($locations as $l): ?>
                            <option value="<?= (int)$l['id'] ?>">
                                <?= $l['parent_code'] ? e($l['parent_code']) . ' · ' : '' ?><?= e($l['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td style="width:26%">
                    <select name="to_location_id" required style="<?= $inp ?>">
                        <option value="">To —</option>
                        <?php foreach ($locations as $l): ?>
                            <option value="<?= (int)$l['id'] ?>">
                                <?= $l['parent_code'] ? e($l['parent_code']) . ' · ' : '' ?><?= e($l['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <input type="text" name="notes" maxlength="500" placeholder="Note — optional" style="<?= $inp ?>">
                </td>
                <td style="width:150px">
                    <button type="submit" class="btn btn--primary" style="width:100%">Start Loading</button>
                </td>
            </tr>
        </table>
    </form>
</div>

<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden">
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr>
                <th style="<?= $th ?>">Transfer</th>
                <th style="<?= $th ?>">Route</th>
                <th style="<?= $th ?>;text-align:center">Status</th>
                <th style="<?= $th ?>;text-align:right">Sent</th>
                <th style="<?= $th ?>;text-align:right">Arrived</th>
                <th style="<?= $th ?>">When</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($transfers)): ?>
            <tr><td colspan="6" style="padding:2rem;text-align:center;color:#9ca3af">
                No transfers yet.
            </td></tr>
        <?php else: ?>
            <?php foreach ($transfers as $t): [$cls, $label] = trBadge($t['status']); ?>
                <tr style="border-bottom:1px solid #f3f4f6">
                    <td style="<?= $td ?>">
                        <a href="/inventory/transfers/<?= (int)$t['id'] ?>"
                           style="color:#0A3D91;font-weight:600;text-decoration:none"><?= e($t['transfer_number']) ?></a>
                        <div class="text-xs text-muted"><?= (int)$t['line_count'] ?> item<?= (int)$t['line_count'] === 1 ? '' : 's' ?></div>
                    </td>
                    <td style="<?= $td ?>"><?= e($t['from_code']) ?> &rarr; <?= e($t['to_code']) ?></td>
                    <td style="<?= $td ?>;text-align:center"><span class="badge <?= $cls ?>"><?= $label ?></span></td>
                    <td style="<?= $td ?>;text-align:right"><?= trQty($t['qty_sent']) ?></td>
                    <td style="<?= $td ?>;text-align:right;<?= $t['status'] === 'short' ? 'color:#b91c1c;font-weight:600' : '' ?>">
                        <?= trQty($t['qty_received']) ?>
                    </td>
                    <td style="<?= $td ?>;color:#9ca3af;font-size:.82rem">
                        <?= date('M j', strtotime($t['created_at'])) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div style="height:2rem"></div>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
