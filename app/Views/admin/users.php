<?php ob_start(); ?>
<?php
$flash      = \App\Core\Session::getFlash('success');
$flashError = \App\Core\Session::getFlash('error');

$roleLabels = [
    'owner'       => 'Owner',
    'admin'       => 'Admin',
    'bookkeeper'  => 'Bookkeeper',
    'manager'     => 'Manager',
    'employee'    => 'Employee',
    'shipping'    => 'Shipping',
    'rep'         => 'Sales Rep',
    'distributor' => 'Distributor',
    'readonly'    => 'Read Only',
];
$roleBadge = [
    'owner'       => 'badge--danger',
    'admin'       => 'badge--warning',
    'bookkeeper'  => 'badge--info',
    'manager'     => 'badge--info',
    'shipping'    => 'badge--info',
    'employee'    => 'badge--neutral',
    'rep'         => 'badge--success',
    'distributor' => 'badge--success',
    'readonly'    => 'badge--neutral',
];
?>

<?php if ($flash): ?>
    <div class="alert alert--success" style="margin-bottom:1rem"><?= e($flash) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert--danger" style="margin-bottom:1rem"><?= e($flashError) ?></div>
<?php endif; ?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title">Users</h1>
        <p class="page-subtitle"><?= count($items) ?> team member<?= count($items) !== 1 ? 's' : '' ?></p>
    </div>
    <div class="page-header__right">
        <a href="/admin/users/create" class="btn btn--primary">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add User
        </a>
    </div>
</div>

<div class="card" style="padding:0;overflow:hidden">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Rep Code</th>
                    <th class="text-right">Commission</th>
                    <th class="text-center">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="8" class="table__empty">No users found.</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $u): ?>
                        <tr>
                            <td>
                                <div style="font-weight:600"><?= e($u['first_name'] . ' ' . $u['last_name']) ?></div>
                                <?php if (!empty($u['title'])): ?>
                                    <div class="text-xs text-muted"><?= e($u['title']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted" style="font-size:.875rem"><?= e($u['email']) ?></td>
                            <td class="text-muted" style="font-size:.875rem"><?= e($u['phone'] ?? '—') ?></td>
                            <td>
                                <span class="badge <?= $roleBadge[$u['role']] ?? 'badge--neutral' ?>">
                                    <?= $roleLabels[$u['role']] ?? ucfirst($u['role']) ?>
                                </span>
                            </td>
                            <td class="text-muted" style="font-size:.875rem"><?= e($u['department_name'] ?? '—') ?></td>
                            <td class="text-muted" style="font-size:.875rem;font-family:monospace"><?= e($u['rep_code'] ?? '—') ?></td>
                            <td class="text-right text-muted" style="font-size:.875rem">
                                <?= $u['commission_rate'] ? number_format((float)$u['commission_rate'], 2) . '%' : '—' ?>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $u['is_active'] ? 'badge--success' : 'badge--neutral' ?>">
                                    <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="text-right">
                                <?php
                                $actEntity    = 'users';
                                $actId        = (int)$u['id'];
                                $actActive    = (bool)$u['is_active'];
                                $actEditUrl   = '/admin/users/' . (int)$u['id'] . '/edit';
                                $actLabel     = 'user';
                                $actDeletable = false;
                                $actRefs      = $refCounts[(int)$u['id']] ?? 0;
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

<!-- Role legend -->
<div class="card" style="margin-top:1.25rem;padding:1rem 1.25rem">
    <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:.75rem">Role Permissions</div>
    <table style="width:100%;border-collapse:collapse;font-size:.85rem">
        <thead>
            <tr style="border-bottom:1px solid var(--color-border)">
                <th style="text-align:left;padding:.4rem .75rem .4rem 0;font-weight:600">Role</th>
                <th style="text-align:left;padding:.4rem .75rem;font-weight:600">Access</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $legend = [
                ['owner',       'badge--danger',  'Full access — all modules, settings, user management, financial data'],
                ['admin',       'badge--warning', 'Full access except cannot manage owner accounts'],
                ['bookkeeper',  'badge--info',    'Invoices, payments, reports, customer financials — can edit paid invoices'],
                ['manager',     'badge--info',    'Sales orders, invoices, customers, products — no admin or financial settings'],
                ['employee',    'badge--neutral', 'View and create sales orders and invoices — no editing paid records'],
                ['shipping',    'badge--info',    'Inventory adjustments + shipping/fulfillment access — no financials or pricing'],
                ['rep',         'badge--success', 'Their assigned customers and orders only — rep portal access'],
                ['distributor', 'badge--success', 'Distributor portal — their orders and distributor documents'],
                ['readonly',    'badge--neutral', 'View-only access across all modules — no create or edit'],
            ];
            foreach ($legend as [$role, $badge, $desc]):
            ?>
            <tr style="border-bottom:1px solid var(--color-border)">
                <td style="padding:.5rem .75rem .5rem 0;white-space:nowrap">
                    <span class="badge <?= $badge ?>"><?= $roleLabels[$role] ?></span>
                </td>
                <td style="padding:.5rem .75rem;color:var(--color-text-muted)"><?= $desc ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<p style="font-size:.78rem;color:#9ca3af;margin:1rem 0 2rem">
    Users are <strong>deactivated, never deleted</strong>. A person's name is attached to
    invoices, quotes, sales orders, leads, payments and tasks, and deleting the account
    would strip them from that history — tasks and sales goals would be removed outright.
    Deactivating blocks the login immediately and leaves every record intact.
</p>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
