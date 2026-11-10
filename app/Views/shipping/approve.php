<?php ob_start(); ?>
<?php
/**
 * Gate one. Nothing reaches the warehouse until it is approved here.
 *
 * Split by bench, because parcel and freight are worked by different people and
 * neither wants the other's orders mixed into their list.
 */
$byBench = [
    'unassigned' => [], 'parcel' => [], 'freight' => [],
    'delivery'   => [], 'pickup' => [], 'other'   => [],
];

foreach ($orders as $o) {
    // No carrier at all is its own case, listed first. It is not the same as a carrier
    // we simply do not sort to a bench, and reading them as one made an order going out
    // on our own truck look like one nobody had routed.
    $bench = empty($o['ship_via_id']) ? 'unassigned' : ($o['ship_mode'] ?? 'other');
    $byBench[$bench][] = $o;
}

$benchLabel = [
    'unassigned' => 'No carrier yet',
    'parcel'     => 'Parcel — FedEx, UPS, USPS',
    'freight'    => 'Freight',
    'delivery'   => 'Our own truck',
    'pickup'     => 'CPU — customer pick-up',
    'other'      => 'Other',
];

$benchNote = [
    'unassigned' => 'Say how each one ships before approving it, or the warehouse cannot tell whose work it is.',
    'parcel'     => 'Boxes on the parcel bench.',
    'freight'    => 'Pallets and LTL. Worked separately from parcel.',
    'delivery'   => 'USSC delivery. No carrier label to buy.',
    'pickup'     => 'The customer collects it. Nothing to label and no carrier to buy.',
    'other'      => 'Not sorted to a bench.',
];

$prio = [
    'urgent' => ['#fef2f2', '#fecaca', '#991b1b'],
    'high'   => ['#fffbeb', '#fde68a', '#92400e'],
];
?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title">Orders to approve</h1>
        <p class="page-subtitle">
            <?= count($orders) ?> waiting. The warehouse cannot see an order until it is approved here.
        </p>
    </div>
    <div style="display:flex;gap:.75rem">
        <a href="/shipping/pull" class="btn btn--secondary">Pull list</a>
        <a href="/shipping" class="btn btn--secondary">Queue</a>
    </div>
</div>

<?php if ($orders === []): ?>
    <div class="card" style="padding:2.5rem;text-align:center;color:#6b7280">
        <p style="font-size:1.05rem;margin-bottom:.4rem">Nothing waiting on you.</p>
        <p style="font-size:.9rem">Every confirmed order has been let out to the floor.</p>
    </div>
<?php else: ?>

<form method="POST" action="/shipping/packing-slips" target="_blank">
<?= csrf_field() ?>

<?php foreach ($byBench as $bench => $rows): ?>
    <?php if ($rows === []) { continue; } ?>

    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:1.5rem">
        <div style="padding:.8rem 1.1rem;border-bottom:1px solid #e5e7eb;display:flex;
                    align-items:baseline;justify-content:space-between;gap:1rem;flex-wrap:wrap">
            <div>
                <span style="font-size:.78rem;font-weight:700;text-transform:uppercase;
                             letter-spacing:.06em;color:#222b59"><?= e($benchLabel[$bench]) ?></span>
                <span style="font-size:.78rem;color:#9ca3af;margin-left:.5rem"><?= count($rows) ?></span>
            </div>
            <span style="font-size:.78rem;color:#6b7280"><?= e($benchNote[$bench]) ?></span>
        </div>

        <table style="width:100%;border-collapse:collapse;font-size:.9rem">
            <thead>
                <tr style="background:#f9fafb;font-size:.72rem;text-transform:uppercase;
                           letter-spacing:.05em;color:#6b7280">
                    <th style="padding:.5rem .6rem;width:34px"></th>
                    <th style="padding:.5rem .6rem;text-align:left">Order</th>
                    <th style="padding:.5rem .6rem;text-align:left">Customer</th>
                    <th style="padding:.5rem .6rem;text-align:right">Lines</th>
                    <th style="padding:.5rem .6rem;text-align:right">Qty</th>
                    <th style="padding:.5rem .6rem;text-align:left">Ship by</th>
                    <th style="padding:.5rem .6rem;text-align:left">How it ships</th>
                    <th style="padding:.5rem .6rem;text-align:right">Value</th>
                    <th style="padding:.5rem .6rem;text-align:right">Decision</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $o): ?>
                <?php
                $p    = $prio[$o['priority'] ?? ''] ?? null;
                $late = !empty($o['ship_by']) && strtotime((string)$o['ship_by']) < strtotime(date('Y-m-d'));
                ?>
                <tr style="border-top:1px solid #f3f4f6">
                    <td style="padding:.55rem .6rem">
                        <input type="checkbox" name="so[]" value="<?= (int)$o['id'] ?>">
                    </td>
                    <td style="padding:.55rem .6rem;white-space:nowrap">
                        <a href="/sales-orders/<?= (int)$o['id'] ?>" class="link" style="font-weight:600">#<?= e($o['so_number']) ?></a>
                        <?php if ($p): ?>
                            <span style="font-size:.68rem;padding:.12rem .4rem;border-radius:4px;margin-left:.3rem;
                                         background:<?= $p[0] ?>;border:1px solid <?= $p[1] ?>;color:<?= $p[2] ?>">
                                <?= strtoupper(e($o['priority'])) ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:.55rem .6rem"><?= e($o['company_name']) ?></td>
                    <td style="padding:.55rem .6rem;text-align:right;color:#6b7280"><?= (int)$o['line_count'] ?></td>
                    <td style="padding:.55rem .6rem;text-align:right;color:#6b7280"><?= (int)$o['qty_ordered'] ?></td>
                    <td style="padding:.55rem .6rem;white-space:nowrap;<?= $late ? 'color:#b91c1c' : 'color:#6b7280' ?>">
                        <?= !empty($o['ship_by']) ? date('M j', strtotime((string)$o['ship_by'])) : '—' ?>
                    </td>
                    <td style="padding:.55rem .6rem">
                        <?php if (!empty($o['ship_via_id'])): ?>
                            <?= e($o['ship_via_name']) ?>
                        <?php else: ?>
                            <select name="ship_via_id" form="approve-<?= (int)$o['id'] ?>" required
                                    style="padding:.25rem .4rem;border:1px solid #d1d5db;border-radius:5px;font-size:.82rem">
                                <option value="">Choose…</option>
                                <?php foreach ($carriers as $cv): ?>
                                    <option value="<?= (int)$cv['id'] ?>"><?= e($cv['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </td>
                    <td style="padding:.55rem .6rem;text-align:right">$<?= number_format((float)$o['total_amount'], 2) ?></td>
                    <td style="padding:.55rem .6rem;text-align:right;white-space:nowrap">
                        <?php if ((int)$o['lines_tight'] > 0): ?>
                            <span title="The counts say this may not fill. The quantity decision happens after picking."
                                  style="font-size:.7rem;color:#92400e;margin-right:.4rem">may be tight</span>
                        <?php endif; ?>
                        <button type="submit" form="approve-<?= (int)$o['id'] ?>"
                                class="btn btn--sm btn--primary">OK to ship</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>

    <div style="display:flex;gap:.75rem;align-items:center;margin-bottom:2rem">
        <button type="submit" class="btn btn--secondary">Print packing slips for ticked orders</button>
        <span style="font-size:.8rem;color:#6b7280">
            One print job, one slip per page. Labels come later — a label needs a packed box.
        </span>
    </div>
</form>

<?php /* One form per row, outside the batch form: HTML has no nested forms. */ ?>
<?php foreach ($orders as $o): ?>
    <form id="approve-<?= (int)$o['id'] ?>" method="POST"
          action="/shipping/<?= (int)$o['id'] ?>/approve" style="display:none">
        <?= csrf_field() ?>
        <?php /* Only when it is already set. Otherwise the row's own <select> carries
                 it, and a hidden 0 here would quietly beat the choice just made. */ ?>
        <?php if (!empty($o['ship_via_id'])): ?>
            <input type="hidden" name="ship_via_id" value="<?= (int)$o['ship_via_id'] ?>">
        <?php endif; ?>
    </form>
<?php endforeach; ?>

<?php endif; ?>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
