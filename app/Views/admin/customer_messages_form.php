<?php ob_start();
$editing = !empty($item);
?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/admin/customer-messages" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Customer Messages
        </a>
        <h1 class="page-title"><?= $editing ? 'Edit Message' : 'Add Message' ?></h1>
    </div>
</div>

<div class="card" style="max-width:620px;padding:1.5rem">
    <form method="post" action="<?= $editing ? '/admin/customer-messages/' . (int)$item['id'] . '/edit' : '/admin/customer-messages' ?>">
        <div style="margin-bottom:1rem">
            <?= admLbl('Message') ?>
            <textarea name="message" required rows="3" style="<?= admInp() ?>;resize:vertical"><?= e($item['message'] ?? '') ?></textarea>
        </div>
        <div style="margin-bottom:1rem">
            <?= admLbl('Sort Order') ?>
            <input type="number" name="sort_order" min="0" max="9999"
                value="<?= (int)($item['sort_order'] ?? 0) ?>"
                style="<?= admInp() ?>;width:120px">
        </div>
        <?php if ($editing): ?>
        <div style="margin-bottom:1.5rem">
            <?= admLbl('Status') ?>
            <select name="is_active" style="<?= admInp() ?>;width:auto">
                <option value="1" <?= ($item['is_active'] ?? 1) ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= !($item['is_active'] ?? 1) ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <?php endif; ?>
        <div style="display:flex;gap:.75rem">
            <button type="submit" class="btn btn--primary">Save</button>
            <a href="/admin/customer-messages" class="btn btn--secondary">Cancel</a>
        </div>
    </form>
</div>

<?php
function admInp(): string {
    return 'width:100%;padding:.75rem .75rem;font-size:.95rem;font-family:inherit;background:#fff;border:1px solid #d1d5db;border-radius:6px;box-sizing:border-box';
}
function admLbl(string $label): string {
    return '<div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.4rem">' . $label . '</div>';
}
?>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
