<?php ob_start(); ?>
<?php
$editing = !empty($item);

// Guarded and uniquely named. admInp/admLbl are declared unguarded in six other admin
// views, which only works because one form renders per request — not a pattern to extend.
if (!function_exists('locInp')) {
    function locInp(): string {
        return 'width:100%;padding:.75rem;font-size:.95rem;font-family:inherit;background:#fff;'
             . 'border:1px solid #d1d5db;border-radius:6px;box-sizing:border-box';
    }
}
if (!function_exists('locLbl')) {
    function locLbl(string $label, string $hint = ''): string {
        $h = $hint !== '' ? '<div style="font-size:.72rem;color:#9ca3af;font-weight:400;text-transform:none;letter-spacing:0;margin-top:.15rem">' . $hint . '</div>' : '';
        return '<div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.4rem">'
             . $label . $h . '</div>';
    }
}

$types = [
    'warehouse' => 'Warehouse — a building',
    'bay'       => 'Bay — an area within a warehouse',
    'rack'      => 'Rack — a specific run of shelving',
    'bin'       => 'Bin — an individual shelf or slot',
    'staging'   => 'Staging — packed and waiting to go out',
];
?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/admin/locations" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Locations
        </a>
        <h1 class="page-title"><?= $editing ? 'Edit ' . e($item['name']) : 'Add Location' ?></h1>
    </div>
</div>

<div class="card" style="max-width:620px;padding:1.5rem">
    <form method="post" action="<?= $editing ? '/admin/locations/' . (int)$item['id'] . '/edit' : '/admin/locations' ?>">
    <?= csrf_field() ?>

        <div style="margin-bottom:1.1rem">
            <?= locLbl('Inside', 'Leave as a building for a whole warehouse') ?>
            <select name="parent_id" style="<?= locInp() ?>">
                <option value="">— A building of its own —</option>
                <?php foreach ($parents as $p): ?>
                    <option value="<?= (int)$p['id'] ?>" <?= (int)($item['parent_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>>
                        <?= str_repeat('— ', (int)$p['depth']) ?><?= e($p['name']) ?> (<?= e($p['code']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-bottom:1.1rem">
            <?= locLbl('Code', 'Short, and what people actually say — 730, A, A-12') ?>
            <input type="text" name="code" required maxlength="40"
                   value="<?= e($item['code'] ?? '') ?>"
                   style="<?= locInp() ?>;font-family:monospace;text-transform:uppercase;width:12rem">
        </div>

        <div style="margin-bottom:1.1rem">
            <?= locLbl('Name') ?>
            <input type="text" name="name" required maxlength="150"
                   value="<?= e($item['name'] ?? '') ?>"
                   placeholder="e.g. 730 Warehouse, Bay A, Rack A-12"
                   style="<?= locInp() ?>">
        </div>

        <div style="margin-bottom:1.1rem">
            <?= locLbl('Type') ?>
            <select name="location_type" style="<?= locInp() ?>">
                <?php foreach ($types as $val => $desc): ?>
                    <option value="<?= $val ?>" <?= ($item['location_type'] ?? 'bay') === $val ? 'selected' : '' ?>>
                        <?= e($desc) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-bottom:1.1rem">
            <?= locLbl('Sort order', 'Controls the order shown in lists and on the scanner') ?>
            <input type="number" name="sort_order" min="0" max="9999"
                   value="<?= (int)($item['sort_order'] ?? 0) ?>"
                   style="<?= locInp() ?>;width:8rem">
        </div>

        <div style="margin-bottom:1.1rem">
            <?= locLbl('Notes') ?>
            <input type="text" name="notes" maxlength="255"
                   value="<?= e($item['notes'] ?? '') ?>"
                   placeholder="Anything worth knowing — cold storage, restricted access"
                   style="<?= locInp() ?>">
        </div>

        <?php if ($editing): ?>
        <div style="margin-bottom:1.4rem">
            <?= locLbl('Status', 'Inactive hides it from pickers; stock already recorded there is untouched') ?>
            <select name="is_active" style="<?= locInp() ?>;width:auto">
                <option value="1" <?= ($item['is_active'] ?? 1) ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= !($item['is_active'] ?? 1) ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <?php endif; ?>

        <div style="display:flex;gap:.75rem">
            <button type="submit" class="btn btn--primary">Save</button>
            <a href="/admin/locations" class="btn btn--secondary">Cancel</a>
        </div>
    </form>
</div>

<p style="font-size:.78rem;color:#9ca3af;margin:1rem 0 2rem;max-width:620px">
    Only add the detail people will actually use. A rack per aisle is useful if pickers
    genuinely put paint back in the same place; if they do not, a building-level count is
    more honest than a shelf-level one that is always wrong.
</p>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
