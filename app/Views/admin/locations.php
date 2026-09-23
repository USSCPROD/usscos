<?php ob_start(); ?>
<?php
$typeLabel = [
    'warehouse' => 'Warehouse',
    'bay'       => 'Bay',
    'rack'      => 'Rack',
    'bin'       => 'Bin',
    'transit'   => 'In transit',
    'staging'   => 'Staging',
];
$typeBadge = [
    'warehouse' => 'badge--info',
    'bay'       => 'badge--neutral',
    'rack'      => 'badge--neutral',
    'bin'       => 'badge--neutral',
    'transit'   => 'badge--warning',
    'staging'   => 'badge--neutral',
];

$qty = fn($n) => rtrim(rtrim(number_format((float)$n, 2), '0'), '.');
?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/admin" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Admin
        </a>
        <h1 class="page-title">Locations</h1>
        <p class="page-subtitle" style="margin:.25rem 0 0">
            Where stock physically sits. Warehouses contain bays, bays contain racks —
            add as much detail as people actually use when putting paint away.
        </p>
    </div>
    <div class="page-header__right">
        <a href="/admin/locations/create" class="btn btn--primary">Add Location</a>
    </div>
</div>

<div class="card" style="padding:0">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Location</th>
                    <th>Code</th>
                    <th class="text-center">Type</th>
                    <th class="text-right">Products</th>
                    <th class="text-right">Units</th>
                    <th class="text-center">Active</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="7" class="table__empty">No locations yet.</td></tr>
            <?php else: ?>
                <?php foreach ($items as $l): ?>
                    <tr<?= (int)$l['is_active'] === 0 ? ' style="opacity:.55"' : '' ?>>
                        <td>
                            <?php // Indentation carries the tree; depth is computed in the repository. ?>
                            <span style="padding-left:<?= (int)$l['depth'] * 1.4 ?>rem">
                                <?php if ((int)$l['depth'] > 0): ?><span style="color:#d1d5db">└ </span><?php endif; ?>
                                <span style="font-weight:<?= $l['location_type'] === 'warehouse' ? '600' : '400' ?>">
                                    <?= e($l['name']) ?>
                                </span>
                            </span>
                            <?php if (!empty($l['notes'])): ?>
                                <div class="text-xs text-muted" style="padding-left:<?= (int)$l['depth'] * 1.4 + 1 ?>rem"><?= e($l['notes']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="font-mono text-sm text-muted"><?= e($l['code']) ?></td>
                        <td class="text-center">
                            <span class="badge <?= $typeBadge[$l['location_type']] ?? 'badge--neutral' ?>">
                                <?= $typeLabel[$l['location_type']] ?? e($l['location_type']) ?>
                            </span>
                        </td>
                        <td class="text-right text-sm text-muted">
                            <?= (int)$l['products_here'] > 0 ? number_format((int)$l['products_here']) : '—' ?>
                        </td>
                        <td class="text-right text-sm">
                            <?= (float)$l['units_here'] != 0.0 ? $qty($l['units_here']) : '<span class="text-muted">—</span>' ?>
                        </td>
                        <td class="text-center">
                            <span class="badge <?= $l['is_active'] ? 'badge--success' : 'badge--neutral' ?>">
                                <?= $l['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="text-right" style="white-space:nowrap">
                            <?php
                            $actEntity    = 'locations';
                            $actId        = (int)$l['id'];
                            $actActive    = (bool)$l['is_active'];
                            $actEditUrl   = '/admin/locations/' . (int)$l['id'] . '/edit';
                            $actLabel     = 'location';
                            $actDeletable = true;
                            $actRefs      = $refCounts[(int)$l['id']] ?? 0;
                            include BASE_PATH . '/app/Views/admin/_actions.php';
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<p style="font-size:.78rem;color:#9ca3af;margin:1rem 0 2rem">
    Stock moving between buildings is <strong>not</strong> a location. A transfer is its own
    record — scanned out of one building, scanned in at the other — so it knows where stock
    came from <em>and where it is going</em>, which a location cannot express. Until it is
    received it counts at neither end, which is correct: it cannot be sold from either
    building while it is on a truck.
</p>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
