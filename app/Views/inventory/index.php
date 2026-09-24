<?php ob_start(); ?>
<?php
$flash      = \App\Core\Session::getFlash('success');
$flashError = \App\Core\Session::getFlash('error');

$p = $paginator;

function invQtyColor(mixed $qty, mixed $reorder): string {
    $qty     = (float)($qty ?? 0);
    $reorder = $reorder !== null ? (float)$reorder : null;
    if ($qty <= 0) return 'color:#dc2626;font-weight:700';
    if ($reorder !== null && $qty <= $reorder) return 'color:#d97706;font-weight:600';
    return 'color:#16a34a;font-weight:600';
}
?>

<?php if ($flash): ?>
    <div class="alert alert--success" style="margin-bottom:1rem"><?= e($flash) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert--danger" style="margin-bottom:1rem"><?= e($flashError) ?></div>
<?php endif; ?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title">Inventory</h1>
        <p class="page-subtitle"><?= number_format($p['total']) ?> active products</p>
    </div>
    <div class="page-header__right">
        <a href="/inventory/adjustments" class="btn btn--secondary">Adjustments</a>
    </div>
</div>

<!-- Stat tiles -->
<table style="width:100%;border-collapse:collapse;margin-bottom:1.25rem">
    <tr>
        <td style="width:50%;padding-right:.625rem;vertical-align:top">
            <a href="?filter=low<?= $search !== '' ? '&search=' . urlencode($search) : '' ?>"
               style="text-decoration:none;display:block">
                <div class="card" style="padding:1rem 1.25rem;border-left:4px solid #d97706">
                    <div style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted)">Low Stock</div>
                    <div style="font-size:1.75rem;font-weight:700;color:#d97706;margin-top:.25rem"><?= number_format($stats['low_stock']) ?></div>
                    <div style="font-size:.8rem;color:var(--color-text-muted)">at or below reorder point</div>
                </div>
            </a>
        </td>
        <td style="width:50%;padding-left:.625rem;vertical-align:top">
            <a href="?filter=out<?= $search !== '' ? '&search=' . urlencode($search) : '' ?>"
               style="text-decoration:none;display:block">
                <div class="card" style="padding:1rem 1.25rem;border-left:4px solid #dc2626">
                    <div style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted)">Out of Stock</div>
                    <div style="font-size:1.75rem;font-weight:700;color:#dc2626;margin-top:.25rem"><?= number_format($stats['out_of_stock']) ?></div>
                    <div style="font-size:.8rem;color:var(--color-text-muted)">zero or negative on hand</div>
                </div>
            </a>
        </td>
    </tr>
</table>

<!-- Filters -->
<form method="GET" action="/inventory" style="margin-bottom:1rem">
    <table style="width:100%;border-collapse:collapse">
        <tr>
            <td style="padding-right:.5rem;vertical-align:top">
                <input type="text" name="search" value="<?= e($search) ?>"
                       placeholder="Search SKU or name…"
                       class="input" style="width:100%">
            </td>
            <td style="width:180px;padding-right:.5rem;vertical-align:top">
                <select name="brand" class="input" style="width:100%">
                    <option value="">All Brands</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?= e($b['name']) ?>" <?= $brand === $b['name'] ? 'selected' : '' ?>>
                            <?= e($b['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td style="width:160px;padding-right:.5rem;vertical-align:top">
                <select name="filter" class="input" style="width:100%">
                    <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Products</option>
                    <option value="low" <?= $filter === 'low' ? 'selected' : '' ?>>Low Stock</option>
                    <option value="out" <?= $filter === 'out' ? 'selected' : '' ?>>Out of Stock</option>
                </select>
            </td>
            <td style="width:80px;vertical-align:top">
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
                    <th>SKU</th>
                    <th>Product</th>
                    <th>Brand</th>
                    <th class="text-center">On Hand</th>
                    <th class="text-center">On S/O</th>
                    <th class="text-center">On P/O</th>
                    <th class="text-center">Reorder Pt</th>
                    <th class="text-right">Cost</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($p['data'])): ?>
                    <tr><td colspan="9" class="table__empty">No products found.</td></tr>
                <?php else: ?>
                    <?php foreach ($p['data'] as $row): ?>
                        <tr>
                            <td style="font-family:monospace;font-size:.85rem"><?= e($row['sku']) ?></td>
                            <td>
                                <a href="/inventory/<?= (int)$row['id'] ?>" style="font-weight:500;text-decoration:none;color:var(--color-primary)">
                                    <?= e($row['name']) ?>
                                </a>
                                <?php if (!empty($row['color'])): ?>
                                    <div class="text-xs text-muted"><?= e($row['color']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted" style="font-size:.85rem"><?= e($row['brand_name'] ?? '—') ?></td>
                            <td class="text-center">
                                <span style="<?= invQtyColor($row['qty_on_hand'], $row['reorder_point']) ?>">
                                    <?= number_format((float)($row['qty_on_hand'] ?? 0), 2) ?>
                                </span>
                                <?php if (!empty($row['uom_code'])): ?>
                                    <span class="text-xs text-muted"> <?= e($row['uom_code']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center text-muted" style="font-size:.875rem">
                                <?= number_format((float)($row['qty_on_sales_order'] ?? 0), 2) ?>
                            </td>
                            <td class="text-center text-muted" style="font-size:.875rem">
                                <?= number_format((float)($row['qty_on_po'] ?? 0), 2) ?>
                            </td>
                            <td class="text-center text-muted" style="font-size:.875rem">
                                <?= $row['reorder_point'] !== null ? number_format((float)$row['reorder_point'], 2) : '—' ?>
                            </td>
                            <td class="text-right text-muted" style="font-size:.875rem">
                                <?= $row['cost'] ? money((float)$row['cost']) : '—' ?>
                            </td>
                            <td class="text-right">
                                <a href="/inventory/<?= (int)$row['id'] ?>" class="btn btn--secondary"
                                   style="padding:.3rem .75rem;font-size:.8rem">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($p['last_page'] > 1): ?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-top:1rem;font-size:.875rem;color:var(--color-text-muted)">
    <div>Showing <?= number_format($p['from']) ?>–<?= number_format($p['to']) ?> of <?= number_format($p['total']) ?></div>
    <div style="display:flex;gap:.35rem">
        <?php
        $qs = http_build_query(array_filter(['search' => $search, 'brand' => $brand, 'filter' => $filter !== 'all' ? $filter : '']));
        for ($pg = 1; $pg <= $p['last_page']; $pg++):
            if ($p['last_page'] > 10 && abs($pg - $p['current_page']) > 2 && $pg !== 1 && $pg !== $p['last_page']) {
                if ($pg === 2 || $pg === $p['last_page'] - 1) echo '<span style="padding:.3rem .5rem">…</span>';
                continue;
            }
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
