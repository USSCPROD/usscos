<?php ob_start(); ?>
<?php
$flash      = \App\Core\Session::getFlash('success');
$flashError = \App\Core\Session::getFlash('error');

$cardStyle = 'background:#fff;border:1px solid #e5e7eb;border-radius:8px';
$thStyle   = 'padding:.55rem .75rem;font-size:.7rem;font-weight:700;text-transform:uppercase;'
           . 'letter-spacing:.05em;color:#6b7280;text-align:left;border-bottom:1px solid #e5e7eb;background:#f8f9fb';

if (!function_exists('catRow')) {
    function catRow(array $c, bool $isChild = false): void {
        $indent  = $isChild ? 'padding-left:2.4rem' : 'padding-left:.75rem';
        $count   = (int)$c['product_count'];
        $hidden  = (int)$c['show_on_website'] === 0;
        $off     = (int)$c['is_active'] === 0;
        ?>
        <tr style="border-bottom:1px solid #f3f4f6">
            <td style="padding:.6rem .75rem;<?= $indent ?>">
                <span style="font-weight:<?= $isChild ? '500' : '600' ?>;<?= $off ? 'color:#9ca3af' : '' ?>">
                    <?= $isChild ? '↳ ' : '' ?><?= e($c['name']) ?>
                </span>
                <?php if ($hidden): ?>
                    <span class="badge badge--neutral" style="font-size:.65rem;margin-left:.4rem">Internal</span>
                <?php endif; ?>
                <?php if ($off): ?>
                    <span class="badge badge--warning" style="font-size:.65rem;margin-left:.4rem">Inactive</span>
                <?php endif; ?>
            </td>
            <td style="padding:.6rem .75rem;font-family:monospace;font-size:.78rem;color:#6b7280"><?= e($c['slug']) ?></td>
            <td style="padding:.6rem .75rem;text-align:right;font-size:.85rem">
                <?= $count ? number_format($count) : '<span class="text-muted">—</span>' ?>
            </td>
            <td style="padding:.6rem .75rem;text-align:center;font-size:.85rem;color:#6b7280"><?= (int)$c['sort_order'] ?></td>
            <td style="padding:.6rem .75rem;text-align:right;white-space:nowrap">
                <a href="/categories/<?= (int)$c['id'] ?>/edit" class="btn btn--xs btn--secondary">Edit</a>
                <form method="POST" action="/categories/<?= (int)$c['id'] ?>/delete" style="display:inline"
                      onsubmit="return confirm('Delete <?= e($c['name']) ?>?<?= $count ? "\\n\\n" . $count . " product(s) will become uncategorised." : "" ?>')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn--xs btn--secondary"
                            style="color:#dc2626;margin-left:.25rem">Delete</button>
                </form>
            </td>
        </tr>
        <?php
    }
}
?>

<?php if ($flash): ?>
    <div class="alert alert--success" style="margin-bottom:1rem"><?= e($flash) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert--danger" style="margin-bottom:1rem"><?= e($flashError) ?></div>
<?php endif; ?>

<table style="width:100%;border-collapse:collapse;margin-bottom:1.25rem">
    <tr>
        <td style="vertical-align:top">
            <h1 class="page-title" style="margin:0">Categories</h1>
            <p class="page-subtitle" style="margin:.25rem 0 0">
                Product taxonomy for the catalog and the public website.
            </p>
        </td>
        <td style="vertical-align:top;text-align:right;white-space:nowrap">
            <a href="/categories/create" class="btn btn--primary">+ New Category</a>
        </td>
    </tr>
</table>

<?php if ($uncategorised > 0): ?>
<div class="alert alert--warning" style="margin-bottom:1.25rem">
    <strong><?= number_format($uncategorised) ?> products</strong> aren't in any category —
    they won't appear anywhere on the website until they are.
    <a href="/products?filter=active" style="color:inherit;text-decoration:underline">Review products</a>
</div>
<?php endif; ?>

<div style="<?= $cardStyle ?>;overflow:hidden">
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr>
                <th style="<?= $thStyle ?>">Category</th>
                <th style="<?= $thStyle ?>">Slug</th>
                <th style="<?= $thStyle ?>;text-align:right">Products</th>
                <th style="<?= $thStyle ?>;text-align:center">Order</th>
                <th style="<?= $thStyle ?>;width:150px"></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tree)): ?>
                <tr><td colspan="5" style="padding:2rem;text-align:center;color:#9ca3af">
                    No categories yet.
                </td></tr>
            <?php else: ?>
                <?php foreach ($tree as $parent): ?>
                    <?php catRow($parent); ?>
                    <?php foreach ($parent['children'] as $child): ?>
                        <?php catRow($child, true); ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<p style="font-size:.8rem;color:#9ca3af;margin-top:1rem">
    Categories marked <strong>Internal</strong> are hidden from the public website —
    used for classifications like toll manufacturing and resale that aren't customer-facing.
</p>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
