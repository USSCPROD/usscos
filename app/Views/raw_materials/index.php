<?php ob_start(); ?>
<?php
$flash      = \App\Core\Session::getFlash('success');
$flashError = \App\Core\Session::getFlash('error');
$p          = $paginator;
?>

<?php if ($flash): ?>
    <div class="alert alert--success" style="margin-bottom:1rem"><?= e($flash) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert--danger" style="margin-bottom:1rem"><?= e($flashError) ?></div>
<?php endif; ?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title">Raw Materials</h1>
        <p class="page-subtitle"><?= number_format($p['total']) ?> material<?= $p['total'] !== 1 ? 's' : '' ?></p>
    </div>
    <div class="page-header__right">
        <a href="/raw-materials/create" class="btn btn--primary">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Material
        </a>
    </div>
</div>

<form method="GET" action="/raw-materials" style="margin-bottom:1rem">
    <table style="width:100%;border-collapse:collapse">
        <tr>
            <td style="padding-right:.5rem">
                <input type="text" name="q" value="<?= e($search) ?>"
                       placeholder="Search name or SKU…"
                       class="input" style="width:100%">
            </td>
            <td style="width:80px">
                <button type="submit" class="btn btn--primary" style="width:100%">Search</button>
            </td>
        </tr>
    </table>
</form>

<div class="card" style="padding:0;overflow:hidden">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>SKU / Code</th>
                    <th>Vendor Part #</th>
                    <th>Preferred Vendor</th>
                    <th class="text-right">Unit Cost</th>
                    <th>UOM</th>
                    <th class="text-right">On Hand</th>
                    <th class="text-right">Reorder Pt</th>
                    <th class="text-center">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($p['data'])): ?>
                    <tr><td colspan="10" class="table__empty">No raw materials found. <a href="/raw-materials/create">Add one.</a></td></tr>
                <?php else: ?>
                    <?php foreach ($p['data'] as $m): ?>
                        <tr>
                            <td style="font-weight:600"><?= e($m['name']) ?></td>
                            <td style="font-family:monospace;font-size:.85rem"><?= e($m['sku'] ?? '—') ?></td>
                            <td class="text-muted" style="font-size:.875rem"><?= e($m['vendor_part_number'] ?? '—') ?></td>
                            <td class="text-muted" style="font-size:.875rem"><?= e($m['preferred_vendor_name'] ?? '—') ?></td>
                            <td class="text-right" style="font-size:.875rem">
                                <?= $m['cost'] ? money((float)$m['cost']) : '—' ?>
                            </td>
                            <td class="text-muted" style="font-size:.875rem"><?= e($m['uom_code'] ?? '—') ?></td>
                            <td class="text-right" style="font-size:.875rem;
                                font-weight:<?= (float)($m['qty_on_hand'] ?? 0) <= 0 ? '700' : '400' ?>;
                                color:<?= (float)($m['qty_on_hand'] ?? 0) <= 0 ? '#dc2626' : 'inherit' ?>">
                                <?= number_format((float)($m['qty_on_hand'] ?? 0), 2) ?>
                            </td>
                            <td class="text-right text-muted" style="font-size:.875rem">
                                <?= $m['reorder_point'] !== null ? number_format((float)$m['reorder_point'], 2) : '—' ?>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $m['is_active'] ? 'badge--success' : 'badge--neutral' ?>">
                                    <?= $m['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="text-right">
                                <a href="/raw-materials/<?= (int)$m['id'] ?>/edit"
                                   class="btn btn--secondary" style="padding:.3rem .75rem;font-size:.8rem">Edit</a>
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
        <?php for ($pg = 1; $pg <= $p['last_page']; $pg++): ?>
            <a href="?q=<?= urlencode($search) ?>&page=<?= $pg ?>"
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
