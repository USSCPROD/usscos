<?php ob_start();
$editing = !empty($item);
?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/admin/departments" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Departments
        </a>
        <h1 class="page-title"><?= $editing ? 'Edit Department' : 'Add Department' ?></h1>
    </div>
</div>

<div class="card" style="max-width:520px;padding:1.5rem">
    <form method="post" action="<?= $editing ? '/admin/departments/' . (int)$item['id'] . '/edit' : '/admin/departments' ?>">
    <?= csrf_field() ?>

        <div style="margin-bottom:1rem">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.4rem">Name</div>
            <input type="text" name="name" required maxlength="100"
                value="<?= e($item['name'] ?? '') ?>"
                style="width:100%;padding:.75rem;font-size:.95rem;font-family:inherit;background:#fff;border:1px solid #d1d5db;border-radius:6px;box-sizing:border-box">
        </div>

        <div style="margin-bottom:1rem">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.4rem">Description <span style="font-weight:400;text-transform:none">(optional)</span></div>
            <input type="text" name="description" maxlength="255"
                value="<?= e($item['description'] ?? '') ?>"
                placeholder="Brief description of this department"
                style="width:100%;padding:.75rem;font-size:.95rem;font-family:inherit;background:#fff;border:1px solid #d1d5db;border-radius:6px;box-sizing:border-box">
        </div>

        <div style="margin-bottom:1rem">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.4rem">Sort Order</div>
            <input type="number" name="sort_order" min="0" max="9999"
                value="<?= (int)($item['sort_order'] ?? 0) ?>"
                style="width:120px;padding:.75rem;font-size:.95rem;font-family:inherit;background:#fff;border:1px solid #d1d5db;border-radius:6px;box-sizing:border-box">
        </div>

        <?php if ($editing): ?>
        <div style="margin-bottom:1.5rem">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.4rem">Status</div>
            <select name="is_active" style="padding:.75rem;font-size:.95rem;font-family:inherit;background:#fff;border:1px solid #d1d5db;border-radius:6px">
                <option value="1" <?= ($item['is_active'] ?? 1) ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= !($item['is_active'] ?? 1) ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <?php endif; ?>

        <div style="display:flex;gap:.75rem">
            <button type="submit" class="btn btn--primary">Save</button>
            <a href="/admin/departments" class="btn btn--secondary">Cancel</a>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
