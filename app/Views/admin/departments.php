<?php ob_start(); ?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/admin" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Admin
        </a>
        <h1 class="page-title">Departments</h1>
    </div>
    <div class="page-header__right">
        <a href="/admin/departments/create" class="btn btn--primary">Add Department</a>
    </div>
</div>

<div class="card" style="padding:0">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th class="text-center">Sort Order</th>
                    <th class="text-center">Active</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="5" class="table__empty">No departments found.</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td style="font-weight:600"><?= e($item['name']) ?></td>
                            <td class="text-muted"><?= e($item['description'] ?? '—') ?></td>
                            <td class="text-center text-muted"><?= (int)$item['sort_order'] ?></td>
                            <td class="text-center">
                                <span class="badge <?= $item['is_active'] ? 'badge--success' : 'badge--neutral' ?>">
                                    <?= $item['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="text-right">
                                <?php
                                $actEntity    = 'departments';
                                $actId        = (int)$item['id'];
                                $actActive    = (bool)$item['is_active'];
                                $actEditUrl   = '/admin/departments/' . (int)$item['id'] . '/edit';
                                $actLabel     = 'department';
                                $actDeletable = true;
                                $actRefs      = $refCounts[(int)$item['id']] ?? 0;
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

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
