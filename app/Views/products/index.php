<?php ob_start(); ?>
<?php
$pag    = $paginated;
$pages  = $pag['last_page'];
$page   = $pag['current_page'];
$from   = $pag['from'] ?? 1;
$to     = $pag['to'] ?? 0;
$total  = $pag['total'];
$search = $search ?? '';
$brand  = $brand  ?? '';
$filter = $filter ?? 'active';
?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title">Products</h1>
        <p class="page-subtitle"><?= number_format($total) ?> product<?= $total !== 1 ? 's' : '' ?></p>
    </div>
</div>

<!-- Filters -->
<div class="toolbar">
    <form class="toolbar__search" method="GET" action="/products" id="product-filter-form">
        <div class="search-wrap">
            <svg class="search-wrap__icon" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/></svg>
            <input type="text" name="q" class="input search-wrap__input" placeholder="Search SKU, name…" value="<?= e($search) ?>" autocomplete="off" style="padding:.65rem .85rem .65rem 2.5rem;font-size:1rem;height:auto">
        </div>
        <input type="hidden" name="filter" value="<?= e($filter) ?>">
        <input type="hidden" name="brand"  value="<?= e($brand) ?>">
    </form>
    <div class="toolbar__filters">
        <?php foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $key => $label): ?>
            <a href="/products?filter=<?= $key ?><?= $search ? '&q=' . urlencode($search) : '' ?><?= $brand ? '&brand=' . urlencode($brand) : '' ?>"
               class="filter-chip <?= $filter === $key ? 'filter-chip--active' : '' ?>">
                <?= $label ?>
            </a>
        <?php endforeach; ?>

        <!-- Brand dropdown -->
        <select name="brand" form="product-filter-form" onchange="this.form.submit()"
                class="input" style="height:2.2rem;padding:.3rem .6rem;font-size:.85rem;min-width:140px">
            <option value="">All Brands</option>
            <?php foreach ($brands as $b): ?>
                <option value="<?= e($b['name']) ?>" <?= $brand === $b['name'] ? 'selected' : '' ?>>
                    <?= e($b['name']) ?>
                </option>
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
                    <th>SKU</th>
                    <th>Name</th>
                    <th>Brand</th>
                    <th>Category</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Cost</th>
                    <th class="text-right">On Hand</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pag['data'])): ?>
                    <tr><td colspan="8" class="table__empty">No products found.</td></tr>
                <?php else: ?>
                    <?php foreach ($pag['data'] as $p): ?>
                        <tr class="table__row--clickable" onclick="window.location='/products/<?= (int)$p['id'] ?>'">
                            <td>
                                <code style="font-size:.8rem;background:var(--color-surface-2);padding:.1rem .4rem;border-radius:3px"><?= e($p['sku']) ?></code>
                            </td>
                            <td>
                                <div style="font-weight:500"><?= e($p['name']) ?></div>
                                <?php if (!empty($p['color'])): ?>
                                    <div class="text-xs text-muted"><?= e($p['color']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= e($p['brand_name'] ?? '—') ?></td>
                            <td class="text-muted"><?= e($p['category'] ?? '—') ?></td>
                            <td class="text-right">
                                <?= $p['price'] !== null ? '$' . number_format((float)$p['price'], 2) : '—' ?>
                            </td>
                            <td class="text-right text-muted">
                                <?= $p['cost'] !== null ? '$' . number_format((float)$p['cost'], 2) : '—' ?>
                            </td>
                            <td class="text-right">
                                <?php $qty = (float)($p['qty_on_hand'] ?? 0); ?>
                                <span class="<?= $qty <= 0 ? 'text-muted' : ($qty < 10 ? 'text-warning' : '') ?>">
                                    <?= number_format($qty, 0) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $p['is_active'] ? 'badge--success' : 'badge--neutral' ?>">
                                    <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
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
            <a href="?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>&filter=<?= e($filter) ?>&brand=<?= urlencode($brand) ?>" class="pagination__btn">‹ Prev</a>
        <?php endif; ?>
        <?php
        $start = max(1, $page - 2);
        $end   = min($pages, $page + 2);
        if ($start > 1): ?><a href="?page=1&q=<?= urlencode($search) ?>&filter=<?= e($filter) ?>&brand=<?= urlencode($brand) ?>" class="pagination__btn">1</a><?php if ($start > 2): ?><span class="pagination__dots">…</span><?php endif; endif;
        for ($p = $start; $p <= $end; $p++): ?>
            <a href="?page=<?= $p ?>&q=<?= urlencode($search) ?>&filter=<?= e($filter) ?>&brand=<?= urlencode($brand) ?>"
               class="pagination__btn <?= $p === $page ? 'pagination__btn--active' : '' ?>">
                <?= $p ?>
            </a>
        <?php endfor;
        if ($end < $pages): if ($end < $pages - 1): ?><span class="pagination__dots">…</span><?php endif; ?><a href="?page=<?= $pages ?>&q=<?= urlencode($search) ?>&filter=<?= e($filter) ?>&brand=<?= urlencode($brand) ?>" class="pagination__btn"><?= $pages ?></a><?php endif; ?>
        <?php if ($page < $pages): ?>
            <a href="?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>&filter=<?= e($filter) ?>&brand=<?= urlencode($brand) ?>" class="pagination__btn">Next ›</a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
