<?php ob_start(); ?>
<?php
$pag    = $paginated;
$pages  = $pag['last_page'];
$page   = $pag['current_page'];
$from   = $pag['from'] ?? 1;
$to     = $pag['to'] ?? 0;
$total  = $pag['total'];
$search   = $search   ?? '';
$brand    = $brand    ?? '';
$filter   = $filter   ?? 'active';
$category = $category ?? '';
$categoryTree = $categoryTree ?? [];

$flash      = \App\Core\Session::getFlash('success');
$flashError = \App\Core\Session::getFlash('error');

// Preserve the current filters when the bulk form redirects back
$currentUrl = '/products?' . http_build_query(array_filter([
    'q' => $search, 'brand' => $brand, 'filter' => $filter,
    'category' => $category, 'page' => $page > 1 ? $page : null,
]));

// Filters carried through every pagination link (page is added separately)
$pageQs = '&q=' . urlencode($search)
        . '&filter=' . urlencode($filter)
        . '&brand=' . urlencode($brand)
        . '&category=' . urlencode($category);
?>

<?php if ($flash): ?>
    <div class="alert alert--success" style="margin-bottom:1rem"><?= e($flash) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert--danger" style="margin-bottom:1rem"><?= e($flashError) ?></div>
<?php endif; ?>

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
        <input type="hidden" name="filter"   value="<?= e($filter) ?>">
        <input type="hidden" name="brand"    value="<?= e($brand) ?>">
        <input type="hidden" name="category" value="<?= e($category) ?>">
    </form>
    <div class="toolbar__filters">
        <?php
        $keep = ($search ? '&q=' . urlencode($search) : '')
              . ($brand ? '&brand=' . urlencode($brand) : '')
              . ($category !== '' ? '&category=' . urlencode($category) : '');
        foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $key => $label): ?>
            <a href="/products?filter=<?= $key ?><?= $keep ?>"
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

        <!-- Category dropdown -->
        <select name="category" form="product-filter-form" onchange="this.form.submit()"
                class="input" style="height:2.2rem;padding:.3rem .6rem;font-size:.85rem;min-width:170px">
            <option value="">All Categories</option>
            <option value="none" <?= $category === 'none' ? 'selected' : '' ?>>⚠ Uncategorised</option>
            <?php foreach ($categoryTree as $parent): ?>
                <option value="<?= (int)$parent['id'] ?>" <?= $category === (string)$parent['id'] ? 'selected' : '' ?>>
                    <?= e($parent['name']) ?>
                </option>
                <?php foreach ($parent['children'] as $child): ?>
                    <option value="<?= (int)$child['id'] ?>" <?= $category === (string)$child['id'] ? 'selected' : '' ?>>
                        &nbsp;&nbsp;— <?= e($child['name']) ?>
                    </option>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- Bulk categorise -->
<form method="POST" action="/categories/bulk-assign" id="bulkCatForm">
<?= csrf_field() ?>
<input type="hidden" name="redirect" value="<?= e($currentUrl) ?>">

<div id="bulkBar" style="display:none;background:#222b59;color:#fff;border-radius:8px;padding:.6rem 1rem;margin-bottom:.75rem">
    <table style="width:100%;border-collapse:collapse">
        <tr>
            <td style="color:#fff;font-size:.88rem">
                <strong><span id="bulkCount">0</span></strong> selected
            </td>
            <td style="text-align:right;white-space:nowrap">
                <select name="category_id" required
                        style="padding:.35rem .5rem;border:none;border-radius:5px;font-size:.85rem;min-width:190px">
                    <option value="">Add to category…</option>
                    <?php foreach ($categoryTree as $parent): ?>
                        <option value="<?= (int)$parent['id'] ?>"><?= e($parent['name']) ?></option>
                        <?php foreach ($parent['children'] as $child): ?>
                            <option value="<?= (int)$child['id'] ?>">&nbsp;&nbsp;— <?= e($child['name']) ?></option>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn--sm" style="background:#fff;color:#222b59;margin-left:.4rem">Assign</button>
                <button type="button" onclick="clearBulk()" class="btn btn--sm"
                        style="background:transparent;color:#c7d2fe;border:1px solid #4b5563;margin-left:.25rem">Clear</button>
            </td>
        </tr>
    </table>
</div>

<!-- Table -->
<div class="card" style="padding:0;overflow:hidden">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:34px">
                        <input type="checkbox" id="bulkAll" onclick="toggleAll(this)" style="accent-color:#222b59;cursor:pointer">
                    </th>
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
                    <tr><td colspan="9" class="table__empty">No products found.</td></tr>
                <?php else: ?>
                    <?php foreach ($pag['data'] as $p): ?>
                        <tr class="table__row--clickable" onclick="window.location='/products/<?= (int)$p['id'] ?>'">
                            <td onclick="event.stopPropagation()" style="text-align:center">
                                <input type="checkbox" name="product_ids[]" value="<?= (int)$p['id'] ?>"
                                       class="bulkCb" onchange="updateBulk()" style="accent-color:#222b59;cursor:pointer">
                            </td>
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
                            <td class="text-muted">
                                <?php if (!empty($p['primary_category'])): ?>
                                    <?= e($p['primary_category']) ?>
                                <?php else: ?>
                                    <span style="color:#d97706;font-size:.8rem">Uncategorised</span>
                                <?php endif; ?>
                            </td>
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
</form>

<script>
function updateBulk() {
    var boxes = document.querySelectorAll('.bulkCb:checked');
    var bar   = document.getElementById('bulkBar');
    document.getElementById('bulkCount').textContent = boxes.length;
    bar.style.display = boxes.length ? '' : 'none';

    var all   = document.querySelectorAll('.bulkCb');
    var head  = document.getElementById('bulkAll');
    head.checked       = boxes.length > 0 && boxes.length === all.length;
    head.indeterminate = boxes.length > 0 && boxes.length < all.length;
}
function toggleAll(master) {
    document.querySelectorAll('.bulkCb').forEach(function (cb) { cb.checked = master.checked; });
    updateBulk();
}
function clearBulk() {
    document.querySelectorAll('.bulkCb').forEach(function (cb) { cb.checked = false; });
    document.getElementById('bulkAll').checked = false;
    updateBulk();
}
</script>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<div class="pagination">
    <span class="pagination__info">
        Showing <?= number_format($from) ?>–<?= number_format($to) ?> of <?= number_format($total) ?>
    </span>
    <div class="pagination__pages">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?><?= $pageQs ?>" class="pagination__btn">‹ Prev</a>
        <?php endif; ?>
        <?php
        $start = max(1, $page - 2);
        $end   = min($pages, $page + 2);
        if ($start > 1): ?><a href="?page=1<?= $pageQs ?>" class="pagination__btn">1</a><?php if ($start > 2): ?><span class="pagination__dots">…</span><?php endif; endif;
        for ($p = $start; $p <= $end; $p++): ?>
            <a href="?page=<?= $p ?><?= $pageQs ?>"
               class="pagination__btn <?= $p === $page ? 'pagination__btn--active' : '' ?>">
                <?= $p ?>
            </a>
        <?php endfor;
        if ($end < $pages): if ($end < $pages - 1): ?><span class="pagination__dots">…</span><?php endif; ?><a href="?page=<?= $pages ?><?= $pageQs ?>" class="pagination__btn"><?= $pages ?></a><?php endif; ?>
        <?php if ($page < $pages): ?>
            <a href="?page=<?= $page + 1 ?><?= $pageQs ?>" class="pagination__btn">Next ›</a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
