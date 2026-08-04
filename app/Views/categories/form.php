<?php ob_start(); ?>
<?php
$c    = $category;                 // null when creating
$isNew = $c === null;

$cardStyle = 'background:#fff;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:1.25rem';
$cardHead  = 'padding:.7rem 1rem;border-bottom:1px solid #e5e7eb;font-size:.72rem;font-weight:700;'
           . 'text-transform:uppercase;letter-spacing:.06em;color:#6b7280';

if (!function_exists('catLbl')) {
    function catLbl(): string {
        return 'width:34%;padding:.45rem .5rem .45rem .7rem;font-size:.83rem;color:#6b7280;vertical-align:middle';
    }
}
if (!function_exists('catInp')) {
    function catInp(): string {
        return 'width:100%;padding:.45rem .6rem;font-size:.88rem;font-family:inherit;'
             . 'border:1px solid #d1d5db;border-radius:5px;box-sizing:border-box;background:#fff;color:#111';
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <div style="font-size:.85rem;color:var(--color-text-muted);margin-bottom:.25rem">
            <a href="/categories" style="color:inherit">Categories</a> &rsaquo;
            <?= $isNew ? 'New' : e($c['name']) ?>
        </div>
        <h1 class="page-title" style="margin:0"><?= $isNew ? 'New Category' : 'Edit Category' ?></h1>
    </div>
</div>

<form method="POST" action="<?= $isNew ? '/categories' : '/categories/' . (int)$c['id'] . '/edit' ?>">
<?= csrf_field() ?>

<table style="width:100%;border-collapse:separate;border-spacing:1.25rem 0;margin:0 -1.25rem">
    <tr>

    <td style="vertical-align:top;padding:0;width:55%">
        <div style="<?= $cardStyle ?>">
            <div style="<?= $cardHead ?>">Details</div>
            <table style="width:100%;border-collapse:collapse">
                <tr>
                    <td style="<?= catLbl() ?>">Name</td>
                    <td style="padding:.3rem .7rem .3rem 0">
                        <input type="text" name="name" required value="<?= e($c['name'] ?? '') ?>" style="<?= catInp() ?>">
                    </td>
                </tr>
                <tr>
                    <td style="<?= catLbl() ?>">
                        Slug
                        <div style="font-size:.7rem;color:#9ca3af;margin-top:.1rem">Leave blank to generate</div>
                    </td>
                    <td style="padding:.3rem .7rem .3rem 0">
                        <input type="text" name="slug" value="<?= e($c['slug'] ?? '') ?>"
                               placeholder="auto-generated from the name" style="<?= catInp() ?>;font-family:monospace">
                    </td>
                </tr>
                <tr>
                    <td style="<?= catLbl() ?>">Parent</td>
                    <td style="padding:.3rem .7rem .3rem 0">
                        <select name="parent_id" style="<?= catInp() ?>">
                            <option value="">— Top level —</option>
                            <?php foreach ($parents as $p): ?>
                                <option value="<?= (int)$p['id'] ?>"
                                    <?= (int)($c['parent_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>>
                                    <?= e($p['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="<?= catLbl() ?>;vertical-align:top;padding-top:.55rem">Description</td>
                    <td style="padding:.3rem .7rem .3rem 0">
                        <textarea name="description" rows="4" style="<?= catInp() ?>;resize:vertical"><?= e($c['description'] ?? '') ?></textarea>
                    </td>
                </tr>
                <tr>
                    <td style="<?= catLbl() ?>">Sort order</td>
                    <td style="padding:.3rem .7rem .3rem 0">
                        <input type="number" name="sort_order" step="10" value="<?= e($c['sort_order'] ?? 0) ?>" style="<?= catInp() ?>">
                    </td>
                </tr>
            </table>
        </div>
    </td>

    <td style="vertical-align:top;padding:0;width:45%">
        <div style="<?= $cardStyle ?>">
            <div style="<?= $cardHead ?>">Visibility</div>
            <table style="width:100%;border-collapse:collapse">
                <tr>
                    <td style="<?= catLbl() ?>">Active</td>
                    <td style="padding:.3rem .7rem .3rem 0">
                        <select name="is_active" style="<?= catInp() ?>">
                            <option value="1" <?= (int)($c['is_active'] ?? 1) === 1 ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= (int)($c['is_active'] ?? 1) === 0 ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="<?= catLbl() ?>">
                        Show on website
                        <div style="font-size:.7rem;color:#9ca3af;margin-top:.1rem">Off for internal classifications</div>
                    </td>
                    <td style="padding:.3rem .7rem .3rem 0">
                        <select name="show_on_website" style="<?= catInp() ?>">
                            <option value="1" <?= (int)($c['show_on_website'] ?? 1) === 1 ? 'selected' : '' ?>>Yes — public</option>
                            <option value="0" <?= (int)($c['show_on_website'] ?? 1) === 0 ? 'selected' : '' ?>>No — internal only</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <div style="<?= $cardStyle ?>">
            <div style="<?= $cardHead ?>">Search Engine</div>
            <table style="width:100%;border-collapse:collapse">
                <tr>
                    <td style="<?= catLbl() ?>">SEO title</td>
                    <td style="padding:.3rem .7rem .3rem 0">
                        <input type="text" name="seo_title" maxlength="255" value="<?= e($c['seo_title'] ?? '') ?>"
                               placeholder="Defaults to the category name" style="<?= catInp() ?>">
                    </td>
                </tr>
                <tr>
                    <td style="<?= catLbl() ?>;vertical-align:top;padding-top:.55rem">Meta description</td>
                    <td style="padding:.3rem .7rem .3rem 0">
                        <textarea name="seo_description" rows="3" maxlength="500"
                                  style="<?= catInp() ?>;resize:vertical"><?= e($c['seo_description'] ?? '') ?></textarea>
                    </td>
                </tr>
            </table>
        </div>
    </td>

    </tr>
</table>

<div style="padding:.5rem 0 2rem;text-align:right">
    <a href="/categories" class="btn btn--secondary" style="margin-right:.75rem">Cancel</a>
    <button type="submit" class="btn btn--primary"><?= $isNew ? 'Create Category' : 'Save Category' ?></button>
</div>

</form>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
