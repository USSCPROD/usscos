<?php ob_start(); ?>
<?php
$flash      = \App\Core\Session::getFlash('success');
$flashError = \App\Core\Session::getFlash('error');

$typeLabel = [
    'person'   => 'Sales Rep',
    'employee' => 'Employee',
    'owner'    => 'Owner',
    'partner'  => 'Partner',
    'house'    => 'House Account',
    'website'  => 'Website',
    'none'     => 'Placeholder',
];
$typeBadge = [
    'person'   => 'badge--success',
    'employee' => 'badge--info',
    'owner'    => 'badge--info',
    'partner'  => 'badge--neutral',
    'house'    => 'badge--neutral',
    'website'  => 'badge--neutral',
    'none'     => 'badge--neutral',
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
        <a href="/admin" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Admin
        </a>
        <h1 class="page-title">Sales Reps<?= $showAll ? ' — All Types' : '' ?></h1>
        <p class="page-subtitle" style="margin:.25rem 0 0">
            <?php if ($showAll): ?>
                Every row in the QuickBooks Rep list, including the attribution buckets.
                Only <strong>Sales Rep</strong> rows count toward rep reporting and commission.
            <?php else: ?>
                Imported from the QuickBooks Rep list. Employees, the owner, house accounts
                (long-standing customers with no rep), website and placeholder rows aren't
                sales reps, so they're hidden here.
            <?php endif; ?>
        </p>
    </div>
    <div class="page-header__right">
        <a href="/accounting/reps" class="btn btn--secondary">Sales by Rep</a>
        <a href="/admin/sales-reps/create" class="btn btn--primary">Add Rep</a>
    </div>
</div>

<div class="card" style="padding:0">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>QuickBooks Name</th>
                    <th class="text-center">Type</th>
                    <th>Linked Login</th>
                    <th class="text-right">Commission</th>
                    <th class="text-right">Customers</th>
                    <th class="text-right">Invoices</th>
                    <th class="text-center">Active</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($items)): ?>
                <tr><td colspan="9" class="table__empty">
                    <?= $showAll ? 'No sales reps yet.' : 'No sales reps yet — nobody is marked as type Sales Rep.' ?>
                </td></tr>
            <?php else: ?>
                <?php
                $lastType = null;
                foreach ($items as $r):
                    // Only one type is listed unless showing all, so the group header would be noise.
                    if ($showAll && $r['rep_type'] !== $lastType):
                        $lastType = $r['rep_type']; ?>
                        <tr style="background:#f8f9fb">
                            <td colspan="9" style="padding:.45rem .9rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280">
                                <?= $typeLabel[$r['rep_type']] ?? e($r['rep_type']) ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <tr<?= (int)$r['is_active'] === 0 ? ' style="opacity:.55"' : '' ?>>
                        <td>
                            <span style="font-weight:500"><?= e($r['name']) ?></span>
                            <?php if (!empty($r['notes'])): ?>
                                <div class="text-xs text-muted" style="max-width:340px"><?= e($r['notes']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="font-mono text-sm text-muted"><?= e($r['quickbooks_name']) ?></td>
                        <td class="text-center">
                            <span class="badge <?= $typeBadge[$r['rep_type']] ?? 'badge--neutral' ?>">
                                <?= $typeLabel[$r['rep_type']] ?? e($r['rep_type']) ?>
                            </span>
                        </td>
                        <td class="text-sm text-muted"><?= !empty($r['user_name']) ? e($r['user_name']) : '—' ?></td>
                        <td class="text-right text-sm">
                            <?= $r['commission_rate'] !== null ? number_format((float)$r['commission_rate'], 2) . '%' : '<span class="text-muted">—</span>' ?>
                        </td>
                        <td class="text-right text-sm">
                            <?php if ((int)$r['customer_count'] > 0): ?>
                                <a href="/customers?rep=<?= (int)$r['id'] ?>" style="color:#0A3D91;text-decoration:none"><?= number_format((int)$r['customer_count']) ?></a>
                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                        </td>
                        <td class="text-right text-sm text-muted">
                            <?= (int)$r['invoice_count'] > 0 ? number_format((int)$r['invoice_count']) : '—' ?>
                        </td>
                        <td class="text-center">
                            <span class="badge <?= $r['is_active'] ? 'badge--success' : 'badge--neutral' ?>">
                                <?= $r['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td class="text-right">
                            <a href="/admin/sales-reps/<?= (int)$r['id'] ?>/edit" class="btn btn--xs btn--secondary">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<p style="font-size:.78rem;color:#9ca3af;margin:1rem 0 2rem">
    <?php if ($showAll): ?>
        Showing every type. <a href="/admin/sales-reps" style="color:#0A3D91">Show sales reps only</a>.
        The non-rep rows carry invoice history and are listed here only so their records
        stay editable.
    <?php elseif ($hiddenCount > 0): ?>
        <?= $hiddenCount ?> non-rep <?= $hiddenCount === 1 ? 'row is' : 'rows are' ?> hidden
        (employees, owner, house accounts, website, placeholders).
        <a href="/admin/sales-reps?all=1" style="color:#0A3D91">Show all types</a> to edit them.
    <?php endif; ?>
    Marking someone <strong>Inactive</strong> keeps them out of pickers and current reporting
    without touching their historical invoices.
</p>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
