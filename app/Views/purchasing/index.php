<?php ob_start(); ?>
<?php
$flash      = \App\Core\Session::getFlash('success');
$flashError = \App\Core\Session::getFlash('error');
$p          = $paginator;

$statusBadge = [
    'draft'     => ['badge--neutral', 'Draft'],
    'sent'      => ['badge--info',    'Sent'],
    'partial'   => ['badge--warning', 'Partial'],
    'received'  => ['badge--success', 'Received'],
    'closed'    => ['badge--neutral', 'Closed'],
    'cancelled' => ['badge--danger',  'Cancelled'],
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
        <h1 class="page-title">Purchase Orders</h1>
        <p class="page-subtitle"><?= number_format($p['total']) ?> order<?= $p['total'] !== 1 ? 's' : '' ?></p>
    </div>
    <div class="page-header__right">
        <a href="/purchasing/create" class="btn btn--primary">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New PO
        </a>
    </div>
</div>

<form method="GET" action="/purchasing" style="margin-bottom:1rem">
    <table style="width:100%;border-collapse:collapse">
        <tr>
            <td style="padding-right:.5rem">
                <input type="text" name="search" value="<?= e($search) ?>"
                       placeholder="Search PO number, vendor, or vendor ref…"
                       class="input" style="width:100%">
            </td>
            <td style="width:160px;padding-right:.5rem">
                <select name="status" class="input" style="width:100%">
                    <option value="">All Statuses</option>
                    <?php foreach ($statusBadge as $val => [$cls, $label]): ?>
                        <option value="<?= $val ?>" <?= $status === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td style="width:80px">
                <button type="submit" class="btn btn--primary" style="width:100%">Filter</button>
            </td>
        </tr>
    </table>
</form>

<div class="card" style="padding:0;overflow:hidden">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>PO #</th>
                    <th>Vendor</th>
                    <th>Order Date</th>
                    <th>Expected</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Lines</th>
                    <th class="text-right">Total</th>
                    <th>Vendor Ref</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($p['data'])): ?>
                    <tr><td colspan="9" class="table__empty">No purchase orders found.</td></tr>
                <?php else: ?>
                    <?php foreach ($p['data'] as $po): ?>
                        <?php [$badge, $label] = $statusBadge[$po['status']] ?? ['badge--neutral', ucfirst($po['status'])]; ?>
                        <tr>
                            <td style="font-family:monospace;font-weight:600;font-size:.875rem">
                                <a href="/purchasing/<?= (int)$po['id'] ?>" style="color:var(--color-primary);text-decoration:none">
                                    <?= e($po['po_number']) ?>
                                </a>
                            </td>
                            <td style="font-size:.875rem"><?= e($po['vendor_name']) ?></td>
                            <td class="text-muted" style="font-size:.875rem;white-space:nowrap">
                                <?= e(date('M j, Y', strtotime($po['order_date']))) ?>
                            </td>
                            <td class="text-muted" style="font-size:.875rem;white-space:nowrap">
                                <?= $po['expected_date'] ? e(date('M j, Y', strtotime($po['expected_date']))) : '—' ?>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $badge ?>"><?= $label ?></span>
                            </td>
                            <td class="text-center text-muted" style="font-size:.875rem">
                                <?= (int)$po['line_count'] ?>
                            </td>
                            <td class="text-right" style="font-weight:600;font-size:.875rem">
                                <?= money((float)$po['total_amount']) ?>
                            </td>
                            <td class="text-muted" style="font-size:.875rem"><?= e($po['vendor_ref'] ?? '—') ?></td>
                            <td class="text-right">
                                <a href="/purchasing/<?= (int)$po['id'] ?>" class="btn btn--secondary"
                                   style="padding:.3rem .75rem;font-size:.8rem">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($p['last_page'] > 1): ?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-top:1rem;font-size:.875rem;color:var(--color-text-muted)">
    <div>Showing <?= number_format($p['from']) ?>–<?= number_format($p['to']) ?> of <?= number_format($p['total']) ?></div>
    <div style="display:flex;gap:.35rem">
        <?php
        $qs = http_build_query(array_filter(['search' => $search, 'status' => $status]));
        for ($pg = 1; $pg <= $p['last_page']; $pg++):
        ?>
            <a href="?<?= $qs ?>&page=<?= $pg ?>"
               style="padding:.3rem .6rem;border-radius:4px;text-decoration:none;
                      background:<?= $pg === $p['current_page'] ? 'var(--color-primary)' : 'var(--color-bg-subtle)' ?>;
                      color:<?= $pg === $p['current_page'] ? '#fff' : 'var(--color-text)' ?>">
                <?= $pg ?>
            </a>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
