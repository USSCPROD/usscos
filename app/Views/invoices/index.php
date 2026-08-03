<?php ob_start(); ?>
<?php
$pag    = $paginated;
$pages  = $pag['last_page'];
$page   = $pag['current_page'];
$from   = $pag['from'] ?? 1;
$to     = $pag['to'] ?? 0;
$total  = $pag['total'];
$status = $status ?? 'all';
$search = $search ?? '';
$sort   = $sort ?? 'date_desc';

$statuses = [
    'all'     => 'All',
    'overdue' => 'Overdue',
    'pending' => 'Pending',
    'partial' => 'Partial',
    'paid'    => 'Paid',
    'draft'   => 'Draft',
    'void'    => 'Void',
];

$statusBadge = [
    'draft'   => 'badge--neutral',
    'pending' => 'badge--warning',
    'partial' => 'badge--warning',
    'paid'    => 'badge--success',
    'void'    => 'badge--neutral',
    'overdue' => 'badge--danger',
];
?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title">Invoices</h1>
        <p class="page-subtitle"><?= number_format($total) ?> invoices &nbsp;·&nbsp; $<?= number_format((float)($stats['total_ar'] ?? 0), 2) ?> outstanding AR</p>
    </div>
    <div class="page-header__right">
        <a href="/invoices/create" class="btn btn--primary">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Invoice
        </a>
    </div>
</div>

<!-- AR Summary -->
<div class="kpi-row">
    <div class="kpi-card">
        <div class="kpi-card__label">Total AR</div>
        <div class="kpi-card__value <?= (float)($stats['total_ar'] ?? 0) > 0 ? 'text-warning' : '' ?>">
            $<?= number_format((float)($stats['total_ar'] ?? 0), 2) ?>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Open Invoices</div>
        <div class="kpi-card__value"><?= number_format((int)($stats['open_count'] ?? 0)) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Overdue</div>
        <div class="kpi-card__value <?= (float)($stats['overdue_ar'] ?? 0) > 0 ? 'text-danger' : '' ?>">
            $<?= number_format((float)($stats['overdue_ar'] ?? 0), 2) ?>
            <?php if (($stats['overdue_count'] ?? 0) > 0): ?>
                <span class="kpi-card__sub"><?= (int)$stats['overdue_count'] ?> invoices</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="kpi-card aging-card">
        <div class="kpi-card__label">Aging Buckets</div>
        <div class="aging-buckets">
            <div class="aging-bucket">
                <span class="aging-bucket__label">1–30</span>
                <span class="aging-bucket__value">$<?= number_format((float)($aging['bucket_1_30'] ?? 0), 0) ?></span>
            </div>
            <div class="aging-bucket">
                <span class="aging-bucket__label">31–60</span>
                <span class="aging-bucket__value text-warning">$<?= number_format((float)($aging['bucket_31_60'] ?? 0), 0) ?></span>
            </div>
            <div class="aging-bucket">
                <span class="aging-bucket__label">61–90</span>
                <span class="aging-bucket__value text-danger">$<?= number_format((float)($aging['bucket_61_90'] ?? 0), 0) ?></span>
            </div>
            <div class="aging-bucket">
                <span class="aging-bucket__label">90+</span>
                <span class="aging-bucket__value text-danger">$<?= number_format((float)($aging['bucket_90_plus'] ?? 0), 0) ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="toolbar">
    <form class="toolbar__search" method="GET" action="/invoices">
        <div class="search-wrap">
            <svg class="search-wrap__icon" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/></svg>
            <input type="text" name="q" class="input search-wrap__input" placeholder="Invoice #, customer, PO…" value="<?= e($search) ?>" autocomplete="off">
        </div>
        <input type="hidden" name="status" value="<?= e($status) ?>">
        <input type="hidden" name="sort" value="<?= e($sort) ?>">
    </form>
    <div class="toolbar__filters">
        <?php foreach ($statuses as $key => $label): ?>
            <a href="/invoices?status=<?= $key ?><?= $search ? '&q=' . urlencode($search) : '' ?>&sort=<?= e($sort) ?>"
               class="filter-chip <?= $status === $key ? 'filter-chip--active' : '' ?>">
                <?= $label ?>
            </a>
        <?php endforeach; ?>
    </div>
    <div class="toolbar__sort">
        <select class="input input--sm" onchange="window.location='/invoices?status=<?= e($status) ?>&q=<?= urlencode($search) ?>&sort='+this.value">
            <?php foreach (['date_desc' => 'Date ↓', 'date_asc' => 'Date ↑', 'due_asc' => 'Due Soon', 'balance_desc' => 'Balance ↓', 'number_desc' => 'Invoice # ↓'] as $val => $label): ?>
                <option value="<?= $val ?>" <?= $sort === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- Table -->
<div class="card" style="padding:0;overflow:hidden">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Due</th>
                    <th>PO #</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Balance</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pag['data'])): ?>
                    <tr><td colspan="8" class="table__empty">No invoices found.</td></tr>
                <?php else: ?>
                    <?php foreach ($pag['data'] as $inv): ?>
                        <tr class="table__row--clickable" onclick="window.location='/invoices/<?= (int)$inv['id'] ?>'">
                            <td class="font-mono"><?= e($inv['invoice_number']) ?></td>
                            <td>
                                <a href="/customers/<?= (int)$inv['customer_id'] ?>" class="link" onclick="event.stopPropagation()">
                                    <?= e($inv['company_name']) ?>
                                </a>
                            </td>
                            <td class="text-muted"><?= date('M j, Y', strtotime($inv['invoice_date'])) ?></td>
                            <td class="<?= ($inv['status'] === 'overdue') ? 'text-danger' : 'text-muted' ?>">
                                <?= date('M j, Y', strtotime($inv['due_date'])) ?>
                                <?php if (($inv['aging_days'] ?? 0) > 0 && $inv['status'] === 'overdue'): ?>
                                    <span class="text-xs">(<?= (int)$inv['aging_days'] ?>d)</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted text-sm"><?= e($inv['po_number'] ?? '—') ?></td>
                            <td class="text-right">$<?= number_format((float)$inv['total_amount'], 2) ?></td>
                            <td class="text-right <?= (float)$inv['balance_due'] > 0 ? 'text-warning' : 'text-muted' ?>">
                                <?= (float)$inv['balance_due'] > 0 ? '$' . number_format((float)$inv['balance_due'], 2) : '—' ?>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $statusBadge[$inv['status']] ?? 'badge--neutral' ?>">
                                    <?= ucfirst($inv['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<div class="pagination">
    <span class="pagination__info">
        Showing <?= number_format($from) ?>–<?= number_format($to) ?> of <?= number_format($total) ?>
    </span>
    <div class="pagination__pages">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page-1 ?>&q=<?= urlencode($search) ?>&status=<?= e($status) ?>&sort=<?= e($sort) ?>" class="pagination__btn">‹ Prev</a>
        <?php endif; ?>
        <?php
        $start = max(1, $page - 2);
        $end   = min($pages, $page + 2);
        if ($start > 1): ?><a href="?page=1&q=<?= urlencode($search) ?>&status=<?= e($status) ?>&sort=<?= e($sort) ?>" class="pagination__btn">1</a><?php if ($start > 2): ?><span class="pagination__dots">…</span><?php endif; endif;
        for ($p = $start; $p <= $end; $p++): ?>
            <a href="?page=<?= $p ?>&q=<?= urlencode($search) ?>&status=<?= e($status) ?>&sort=<?= e($sort) ?>"
               class="pagination__btn <?= $p === $page ? 'pagination__btn--active' : '' ?>"><?= $p ?></a>
        <?php endfor;
        if ($end < $pages): if ($end < $pages-1): ?><span class="pagination__dots">…</span><?php endif; ?><a href="?page=<?= $pages ?>&q=<?= urlencode($search) ?>&status=<?= e($status) ?>&sort=<?= e($sort) ?>" class="pagination__btn"><?= $pages ?></a><?php endif; ?>
        <?php if ($page < $pages): ?>
            <a href="?page=<?= $page+1 ?>&q=<?= urlencode($search) ?>&status=<?= e($status) ?>&sort=<?= e($sort) ?>" class="pagination__btn">Next ›</a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
