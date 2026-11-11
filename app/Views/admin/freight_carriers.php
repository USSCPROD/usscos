<?php ob_start(); ?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/admin" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Admin
        </a>
        <h1 class="page-title">Freight Carriers</h1>
        <p class="page-subtitle">
            Who actually moves a pallet. Separate from Ship Via, which is how an order
            ships and is chosen on the sales order weeks before anybody books a truck.
        </p>
    </div>
    <div class="page-header__right">
        <a href="/admin/freight-carriers/create" class="btn btn--primary">Add Carrier</a>
    </div>
</div>

<div class="card" style="padding:0">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>SCAC</th>
                    <th>Tracking link</th>
                    <th>Phone</th>
                    <th class="text-center">Active</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="6" class="table__empty">
                        No carriers yet. Add the ones Keubix book most often — the rest can be
                        typed in as one-offs on the shipment itself.
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><strong><?= e($item['name']) ?></strong>
                                <?php if (!empty($item['notes'])): ?>
                                    <div class="text-muted text-sm"><?= e($item['notes']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted" style="font-family:monospace"><?= e($item['scac'] ?? '—') ?></td>
                            <td class="text-muted text-sm">
                                <?= !empty($item['tracking_url_template']) ? 'yes' : '—' ?>
                            </td>
                            <td class="text-muted"><?= e($item['phone'] ?? '—') ?></td>
                            <td class="text-center">
                                <span class="badge <?= $item['is_active'] ? 'badge--success' : 'badge--neutral' ?>">
                                    <?= $item['is_active'] ? 'Active' : 'Retired' ?>
                                </span>
                            </td>
                            <td class="text-right">
                                <a href="/admin/freight-carriers/<?= (int)$item['id'] ?>/edit" class="btn btn--sm btn--secondary">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
