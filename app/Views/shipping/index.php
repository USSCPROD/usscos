<?php ob_start();

$statusLabel = [
    'confirmed'         => 'Confirmed',
    'processing'        => 'Processing',
    'partially_shipped' => 'Partial',
    'paid'              => 'Paid',
];
$statusBadge = [
    'confirmed'         => 'badge--info',
    'processing'        => 'badge--info',
    'partially_shipped' => 'badge--warning',
    'paid'              => 'badge--success',
];

$today    = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Build tab list: All + one per ship_via that has orders
// shipVias comes from DB (all active); only show tabs that have orders
$tabsWithOrders = [];
foreach ($shipVias as $sv) {
    if (!empty($grouped[(string)$sv['id']])) {
        $tabsWithOrders[] = $sv;
    }
}
$hasUnassigned = !empty($grouped['none']);

$activeTab = $_GET['tab'] ?? 'all';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
    <div>
        <h1 class="page-title">Shipping Queue</h1>
        <p class="page-subtitle"><?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?> ready to ship</p>
    </div>
</div>

<!-- Tabs -->
<div style="border-bottom:2px solid #d1d5db;margin-bottom:1.25rem;overflow-x:auto;white-space:nowrap">
    <?php
    $tabs = [['id' => 'all', 'name' => 'All', 'count' => count($orders)]];
    foreach ($tabsWithOrders as $sv) {
        $tabs[] = ['id' => (string)$sv['id'], 'name' => $sv['name'], 'count' => count($grouped[(string)$sv['id']] ?? [])];
    }
    if ($hasUnassigned) {
        $tabs[] = ['id' => 'none', 'name' => 'No Carrier', 'count' => count($grouped['none'] ?? [])];
    }
    foreach ($tabs as $tab):
        $isActive = $activeTab === $tab['id'];
    ?>
    <a href="?tab=<?= urlencode($tab['id']) ?>"
       style="display:inline-block;padding:.6rem 1.1rem;font-size:.9rem;font-weight:<?= $isActive ? '700' : '500' ?>;color:<?= $isActive ? '#222b59' : '#6b7280' ?>;border-bottom:<?= $isActive ? '3px solid #222b59' : '3px solid transparent' ?>;margin-bottom:-2px;text-decoration:none;white-space:nowrap">
        <?= e($tab['name']) ?>
        <span style="display:inline-block;background:<?= $isActive ? '#222b59' : '#e5e7eb' ?>;color:<?= $isActive ? '#fff' : '#374151' ?>;font-size:.72rem;font-weight:700;border-radius:999px;padding:.1rem .5rem;margin-left:.35rem"><?= $tab['count'] ?></span>
    </a>
    <?php endforeach; ?>
</div>

<?php
// Decide which orders to show for active tab
if ($activeTab === 'all') {
    $displayOrders = $orders;
} elseif ($activeTab === 'none') {
    $displayOrders = $grouped['none'] ?? [];
} else {
    $displayOrders = $grouped[$activeTab] ?? [];
}
?>

<?php if (empty($displayOrders)): ?>
    <div style="background:#fff;border:1px solid #d1d5db;border-radius:8px;padding:3rem;text-align:center;color:#6b7280">
        <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2" style="color:#d1d5db;margin:0 auto 1rem;display:block">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
        </svg>
        <div style="font-size:1.1rem;font-weight:600;color:#374151;margin-bottom:.4rem">No orders in this queue</div>
        <div style="font-size:.9rem">Confirmed and processing orders will appear here.</div>
    </div>
<?php else: ?>
    <div style="background:#fff;border:1px solid #d1d5db;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.07);overflow:hidden">
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:.925rem">
                <thead>
                    <tr style="background:#f8f9fb;border-bottom:2px solid #d1d5db">
                        <th style="padding:.7rem 1rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;white-space:nowrap">SO #</th>
                        <th style="padding:.7rem 1rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">Customer</th>
                        <th style="padding:.7rem 1rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;white-space:nowrap">PO #</th>
                        <th style="padding:.7rem 1rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;white-space:nowrap">Ship By</th>
                        <th style="padding:.7rem 1rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">Ship To</th>
                        <?php if ($activeTab === 'all' || $activeTab === 'none'): ?>
                        <th style="padding:.7rem 1rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;white-space:nowrap">Carrier</th>
                        <?php endif; ?>
                        <th style="padding:.7rem 1rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">Status</th>
                        <th style="padding:.7rem 1rem;text-align:right;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">Total</th>
                        <th style="padding:.7rem 1rem"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($displayOrders as $o):
                        $shipDate   = $o['requested_ship_date'] ?? null;
                        $isOverdue  = $shipDate && $shipDate < $today;
                        $isDueToday = $shipDate && $shipDate === $today;
                        $isDueSoon  = $shipDate && $shipDate === $tomorrow;

                        $shipAddr = trim(implode(', ', array_filter([
                            $o['ship_address_1'] ?: null,
                            $o['ship_city']      ?: null,
                            $o['ship_state']     ?: null,
                        ])));
                    ?>
                    <tr style="border-bottom:1px solid #f3f4f6;cursor:pointer" onclick="window.location='/sales-orders/<?= (int)$o['id'] ?>'" onmouseover="this.style.background='#eff6ff'" onmouseout="this.style.background=''">
                        <td style="padding:.75rem 1rem;white-space:nowrap">
                            <span style="font-weight:700;color:#0A3D91;font-family:monospace"><?= e($o['so_number']) ?></span>
                        </td>
                        <td style="padding:.75rem 1rem">
                            <div style="font-weight:600;color:#111"><?= e($o['company_name']) ?></div>
                            <?php if ($o['phone']): ?><div style="font-size:.8rem;color:#6b7280"><?= e($o['phone']) ?></div><?php endif; ?>
                        </td>
                        <td style="padding:.75rem 1rem;color:#374151;white-space:nowrap"><?= $o['po_number'] ? e($o['po_number']) : '<span style="color:#d1d5db">—</span>' ?></td>
                        <td style="padding:.75rem 1rem;white-space:nowrap">
                            <?php if ($shipDate): ?>
                                <?php if ($isOverdue): ?>
                                    <span style="color:#dc2626;font-weight:700">⚠ <?= date('M j', strtotime($shipDate)) ?><span style="font-size:.75rem;font-weight:400;display:block">Overdue</span></span>
                                <?php elseif ($isDueToday): ?>
                                    <span style="color:#d97706;font-weight:700"><?= date('M j', strtotime($shipDate)) ?><span style="font-size:.75rem;font-weight:400;display:block">Today</span></span>
                                <?php elseif ($isDueSoon): ?>
                                    <span style="color:#d97706"><?= date('M j', strtotime($shipDate)) ?><span style="font-size:.75rem;color:#9ca3af;display:block">Tomorrow</span></span>
                                <?php else: ?>
                                    <span style="color:#374151"><?= date('M j, Y', strtotime($shipDate)) ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color:#d1d5db">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:.75rem 1rem;font-size:.875rem;color:#374151"><?= $shipAddr ? e($shipAddr) : '<span style="color:#d1d5db">—</span>' ?></td>
                        <?php if ($activeTab === 'all' || $activeTab === 'none'): ?>
                        <td style="padding:.75rem 1rem;white-space:nowrap">
                            <?php if ($o['ship_via_name']): ?>
                                <span style="background:#f0f4ff;color:#0A3D91;padding:.2rem .6rem;border-radius:4px;font-size:.8rem;font-weight:600"><?= e($o['ship_via_name']) ?></span>
                            <?php else: ?>
                                <span style="color:#d1d5db">—</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <td style="padding:.75rem 1rem">
                            <span class="badge <?= $statusBadge[$o['status']] ?? 'badge--neutral' ?>"><?= $statusLabel[$o['status']] ?? e($o['status']) ?></span>
                        </td>
                        <td style="padding:.75rem 1rem;text-align:right;font-family:monospace;font-weight:600;color:#111">$<?= number_format((float)$o['total_amount'], 2) ?></td>
                        <td style="padding:.75rem 1rem;text-align:right;white-space:nowrap" onclick="event.stopPropagation()">
                            <?php
                            // Pick state, so the floor can see at a glance what's underway
                            // and what someone has already flagged as short.
                            $pickState = $o['pick_status'] ?? 'not_started';
                            $pickBadge = [
                                'in_progress' => ['#eff6ff', '#bfdbfe', '#1d4ed8', 'Picking'],
                                'ready'       => ['#f0fdf4', '#bbf7d0', '#166534', 'Picked'],
                                'short'       => ['#fffbeb', '#fcd34d', '#92400e', 'Short'],
                            ][$pickState] ?? null;
                            ?>
                            <?php
                            // Pack state matters as much as pick state: an order that is
                            // picked but unverified is the one about to go out wrong.
                            $packState = $o['pack_status'] ?? 'not_started';
                            $packBadge = [
                                'in_progress' => ['#eff6ff', '#bfdbfe', '#1d4ed8', 'Packing'],
                                'verified'    => ['#f0fdf4', '#bbf7d0', '#166534', 'Verified'],
                                'mismatch'    => ['#fef2f2', '#fca5a5', '#b91c1c', 'Mismatch'],
                            ][$packState] ?? null;
                            ?>
                            <?php if ($pickBadge): ?>
                                <span style="font-size:.75rem;padding:.25rem .55rem;border-radius:4px;margin-right:.4rem;
                                             background:<?= $pickBadge[0] ?>;border:1px solid <?= $pickBadge[1] ?>;color:<?= $pickBadge[2] ?>">
                                    <?= $pickBadge[3] ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($packBadge): ?>
                                <span style="font-size:.75rem;padding:.25rem .55rem;border-radius:4px;margin-right:.4rem;
                                             background:<?= $packBadge[0] ?>;border:1px solid <?= $packBadge[1] ?>;color:<?= $packBadge[2] ?>">
                                    <?= $packBadge[3] ?>
                                </span>
                            <?php endif; ?>
                            <a href="/shipping/<?= (int)$o['id'] ?>/pick"
                               style="font-size:.8rem;padding:.3rem .7rem;border:1px solid #0A3D91;border-radius:5px;color:#fff;background:#0A3D91;text-decoration:none;margin-right:.4rem">Pick</a>
                            <a href="/shipping/<?= (int)$o['id'] ?>/pack"
                               style="font-size:.8rem;padding:.3rem .7rem;border:1px solid #0A3D91;border-radius:5px;color:#0A3D91;text-decoration:none;margin-right:.4rem"
                               onmouseover="this.style.background='#eff6ff'" onmouseout="this.style.background=''">Pack</a>
                            <a href="/sales-orders/<?= (int)$o['id'] ?>/packing-slip" target="_blank"
                               style="font-size:.8rem;padding:.3rem .7rem;border:1px solid #d1d5db;border-radius:5px;color:#374151;text-decoration:none"
                               onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background=''">Packing Slip</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
