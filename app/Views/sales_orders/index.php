<?php ob_start(); ?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title">Sales Orders</h1>
        <p class="page-subtitle"><?= number_format((int)$stats['total_orders']) ?> total &mdash; <?= number_format((int)$stats['open_count']) ?> open</p>
    </div>
    <div class="page-header__right">
        <a href="/sales-orders/create" class="btn btn--primary">+ New Order</a>
    </div>
</div>

<!-- KPIs -->
<div class="kpi-row">
    <div class="kpi-card">
        <div class="kpi-card__label">Open Orders</div>
        <div class="kpi-card__value"><?= number_format((int)$stats['open_count']) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Open Value</div>
        <div class="kpi-card__value">$<?= number_format((float)($stats['open_value'] ?? 0), 2) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Drafts</div>
        <div class="kpi-card__value"><?= number_format((int)$stats['draft_count']) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Paid (Unshipped)</div>
        <div class="kpi-card__value" style="color:#16a34a"><?= number_format((int)($stats['paid_count'] ?? 0)) ?></div>
    </div>
</div>

<!-- Filters -->
<div class="filter-bar">
    <form method="get" class="filter-bar__search">
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search SO#, customer, PO#&hellip;" class="input input--search">
        <input type="hidden" name="status" value="<?= e($status) ?>">
        <input type="hidden" name="sort"   value="<?= e($sort) ?>">
    </form>

    <div class="filter-bar__chips">
        <?php
        $statuses = [
            'open'              => 'Open',
            'all'               => 'All',
            'draft'             => 'Draft',
            'confirmed'         => 'Confirmed',
            'processing'        => 'Processing',
            'partially_shipped' => 'Part. Shipped',
            'paid'              => 'Paid',
            'shipped'           => 'Shipped',
            'invoiced'          => 'Invoiced',
            'cancelled'         => 'Cancelled',
        ];
        foreach ($statuses as $val => $label):
            $active = $status === $val ? 'filter-chip--active' : '';
            $qs = http_build_query(['q' => $search, 'status' => $val, 'sort' => $sort]);
        ?>
            <a href="?<?= $qs ?>" class="filter-chip <?= $active ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </div>

    <div class="filter-bar__sort">
        <select class="input input--sm" onchange="location.href='?'+new URLSearchParams({q:'<?= e($search) ?>',status:'<?= e($status) ?>',sort:this.value}).toString()">
            <?php foreach (['date_desc' => 'Newest First', 'date_asc' => 'Oldest First', 'number_desc' => 'SO# Desc', 'total_desc' => 'Highest Value'] as $val => $label): ?>
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
                    <th>SO #</th>
                    <th>Customer</th>
                    <th>Order Date</th>
                    <th>Ship By</th>
                    <th>PO #</th>
                    <th>Status</th>
                    <th class="text-right">Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pagination['data'])): ?>
                    <tr><td colspan="8" class="table__empty">No sales orders found.</td></tr>
                <?php else: ?>
                    <?php
                    $statusBadge = [
                        'draft'             => 'badge--neutral',
                        'confirmed'         => 'badge--info',
                        'processing'        => 'badge--info',
                        'partially_shipped' => 'badge--warning',
                        'paid'              => 'badge--success',
                        'shipped'           => 'badge--success',
                        'invoiced'          => 'badge--success',
                        'cancelled'         => 'badge--neutral',
                    ];
                    $statusLabel = [
                        'draft'             => 'Draft',
                        'confirmed'         => 'Confirmed',
                        'processing'        => 'Processing',
                        'partially_shipped' => 'Part. Shipped',
                        'paid'              => 'Paid',
                        'shipped'           => 'Shipped',
                        'invoiced'          => 'Invoiced',
                        'cancelled'         => 'Cancelled',
                    ];
                    foreach ($pagination['data'] as $row): ?>
                        <tr class="table__row--clickable" onclick="window.location='/sales-orders/<?= (int)$row['id'] ?>'">
                            <td class="font-mono"><?= e($row['so_number']) ?></td>
                            <td>
                                <a href="/customers/<?= (int)$row['customer_id'] ?>" class="link" onclick="event.stopPropagation()"><?= e($row['company_name']) ?></a>
                            </td>
                            <td><?= date('M j, Y', strtotime($row['order_date'])) ?></td>
                            <td class="text-muted text-sm">
                                <?= $row['requested_ship_date'] ? date('M j, Y', strtotime($row['requested_ship_date'])) : '—' ?>
                            </td>
                            <td class="text-sm text-muted"><?= e($row['po_number'] ?? '—') ?></td>
                            <td>
                                <span class="badge <?= $statusBadge[$row['status']] ?? 'badge--neutral' ?>">
                                    <?= $statusLabel[$row['status']] ?? ucfirst($row['status']) ?>
                                </span>
                            </td>
                            <td class="text-right font-mono">$<?= number_format((float)$row['total_amount'], 2) ?></td>
                            <td class="text-right">
                                <a href="/sales-orders/<?= (int)$row['id'] ?>/edit" class="btn btn--xs btn--secondary" onclick="event.stopPropagation()">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pagination['last_page'] > 1): ?>
    <div class="table-pagination">
        <span class="table-pagination__info">
            <?= number_format($pagination['from']) ?>–<?= number_format($pagination['to']) ?> of <?= number_format($pagination['total']) ?>
        </span>
        <div class="table-pagination__pages">
            <?php if ($pagination['current_page'] > 1): ?>
                <a href="?<?= http_build_query(['q' => $search, 'status' => $status, 'sort' => $sort, 'page' => $pagination['current_page'] - 1]) ?>" class="btn btn--xs btn--secondary">&larr;</a>
            <?php endif; ?>
            <span class="table-pagination__current">Page <?= $pagination['current_page'] ?> of <?= $pagination['last_page'] ?></span>
            <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                <a href="?<?= http_build_query(['q' => $search, 'status' => $status, 'sort' => $sort, 'page' => $pagination['current_page'] + 1]) ?>" class="btn btn--xs btn--secondary">&rarr;</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
