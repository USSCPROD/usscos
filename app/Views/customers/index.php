<?php ob_start(); ?>
<?php
$pag      = $paginated;
$pages    = $pag['last_page'];
$page     = $pag['current_page'];
$from     = $pag['from'] ?? 1;
$to       = $pag['to'] ?? 0;
$total    = $pag['total'];
$filter   = $filter ?? 'all';
$search   = $search ?? '';
?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title">Customers</h1>
        <p class="page-subtitle"><?= number_format($total) ?> total &nbsp;·&nbsp; $<?= number_format($total_ar, 2) ?> outstanding AR</p>
    </div>
    <div class="page-header__right">
        <a href="/customers/new" class="btn btn--primary">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Customer
        </a>
    </div>
</div>

<!-- Filters -->
<div class="toolbar">
    <form class="toolbar__search" method="GET" action="/customers">
        <div class="search-wrap">
            <svg class="search-wrap__icon" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/></svg>
            <input type="text" name="q" class="input search-wrap__input" placeholder="Search customers…" value="<?= e($search) ?>" autocomplete="off" style="padding:.65rem .85rem .65rem 2.5rem;font-size:1rem;height:auto">
        </div>
        <input type="hidden" name="filter" value="<?= e($filter) ?>">
    </form>
    <div class="toolbar__filters">
        <?php foreach (['all' => 'All', 'balance' => 'Has Balance', 'inactive' => 'Inactive'] as $key => $label): ?>
            <a href="/customers?filter=<?= $key ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
               class="filter-chip <?= $filter === $key ? 'filter-chip--active' : '' ?>">
                <?= $label ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Table -->
<div class="card" style="padding:0;overflow:hidden">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th class="text-right">Balance</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pag['data'])): ?>
                    <tr><td colspan="5" class="table__empty">No customers found.</td></tr>
                <?php else: ?>
                    <?php foreach ($pag['data'] as $c): ?>
                        <tr class="table__row--clickable" onclick="window.location='/customers/<?= (int)$c['id'] ?>'">
                            <td>
                                <div class="customer-name"><?= e($c['company_name']) ?></div>
                                <?php if ($c['quickbooks_name'] !== $c['company_name']): ?>
                                    <div class="text-xs text-muted"><?= e($c['quickbooks_name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= e($c['phone'] ?? '—') ?></td>
                            <td class="text-muted"><?= e($c['email'] ?? '—') ?></td>
                            <td class="text-right <?= (float)$c['qb_balance'] > 0 ? 'text-warning' : 'text-muted' ?>">
                                <?= (float)$c['qb_balance'] > 0 ? '$' . number_format((float)$c['qb_balance'], 2) : '—' ?>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $c['is_active'] ? 'badge--success' : 'badge--neutral' ?>">
                                    <?= $c['is_active'] ? 'Active' : 'Inactive' ?>
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
            <a href="?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>&filter=<?= e($filter) ?>" class="pagination__btn">‹ Prev</a>
        <?php endif; ?>
        <?php
        $start = max(1, $page - 2);
        $end   = min($pages, $page + 2);
        if ($start > 1): ?><a href="?page=1&q=<?= urlencode($search) ?>&filter=<?= e($filter) ?>" class="pagination__btn">1</a><?php if ($start > 2): ?><span class="pagination__dots">…</span><?php endif; endif;
        for ($p = $start; $p <= $end; $p++): ?>
            <a href="?page=<?= $p ?>&q=<?= urlencode($search) ?>&filter=<?= e($filter) ?>"
               class="pagination__btn <?= $p === $page ? 'pagination__btn--active' : '' ?>">
                <?= $p ?>
            </a>
        <?php endfor;
        if ($end < $pages): if ($end < $pages - 1): ?><span class="pagination__dots">…</span><?php endif; ?><a href="?page=<?= $pages ?>&q=<?= urlencode($search) ?>&filter=<?= e($filter) ?>" class="pagination__btn"><?= $pages ?></a><?php endif; ?>
        <?php if ($page < $pages): ?>
            <a href="?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>&filter=<?= e($filter) ?>" class="pagination__btn">Next ›</a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
