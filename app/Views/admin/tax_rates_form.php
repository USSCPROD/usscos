<?php ob_start();
$editing = !empty($item);
?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/admin/tax-rates" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Tax Rates
        </a>
        <h1 class="page-title"><?= $editing ? 'Edit Tax Rate' : 'Add Tax Rate' ?></h1>
    </div>
</div>

<div class="card" style="max-width:520px;padding:1.5rem">
    <form method="post" action="<?= $editing ? '/admin/tax-rates/' . (int)$item['id'] . '/edit' : '/admin/tax-rates' ?>">
        <div style="margin-bottom:1rem">
            <?= admLbl('Name') ?>
            <input type="text" name="name" required maxlength="100"
                placeholder="e.g. GA - Forsyth County"
                value="<?= e($item['name'] ?? '') ?>"
                style="<?= admInp() ?>">
        </div>
        <div style="margin-bottom:1rem">
            <?= admLbl('State Code') ?>
            <input type="text" name="state_code" maxlength="2" placeholder="GA"
                value="<?= e($item['state_code'] ?? '') ?>"
                style="<?= admInp() ?>;width:80px;text-transform:uppercase">
        </div>
        <div style="margin-bottom:1rem">
            <?= admLbl('Rate (%)') ?>
            <input type="number" name="rate" step="0.0001" min="0" max="100" required
                placeholder="7.0000"
                value="<?= $editing ? number_format((float)$item['rate'] * 100, 4) : '' ?>"
                style="<?= admInp() ?>;width:160px">
            <div style="font-size:.8rem;color:#6b7280;margin-top:.3rem">Enter as a percentage, e.g. 7.0000 for 7%</div>
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
            <a href="/admin/tax-rates" class="btn btn--secondary">Cancel</a>
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
