<?php ob_start(); ?>
<?php
$r     = $item;             // null when creating
$isNew = $r === null;

$lbl = 'width:32%;padding:.5rem .6rem .5rem 0;font-size:.85rem;color:#6b7280;vertical-align:middle';
$inp = 'width:100%;padding:.5rem .65rem;font-size:.9rem;font-family:inherit;border:1px solid #d1d5db;'
     . 'border-radius:5px;box-sizing:border-box;background:#fff;color:#111';

$types = [
    'person'   => 'Sales Rep — counts in rep reporting and commission',
    'employee' => 'Employee — internal, excluded from rep reporting',
    'owner'    => 'Owner — excluded from rep reporting',
    'partner'  => 'Partner / distributor',
    'house'    => 'House account',
    'website'  => 'Website / online orders',
    'none'     => 'Placeholder (e.g. "No sales rep")',
];
?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/admin/sales-reps" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Sales Reps
        </a>
        <h1 class="page-title"><?= $isNew ? 'Add Sales Rep' : 'Edit ' . e($r['name']) ?></h1>
    </div>
</div>

<form method="POST" action="<?= $isNew ? '/admin/sales-reps' : '/admin/sales-reps/' . (int)$r['id'] . '/edit' ?>">
<?= csrf_field() ?>

<div class="card" style="max-width:680px">
    <table style="width:100%;border-collapse:collapse">
        <tr>
            <td style="<?= $lbl ?>">Name</td>
            <td style="padding:.35rem 0">
                <input type="text" name="name" required value="<?= e($r['name'] ?? '') ?>" style="<?= $inp ?>">
            </td>
        </tr>
        <tr>
            <td style="<?= $lbl ?>">
                QuickBooks name
                <div style="font-size:.72rem;color:#9ca3af;margin-top:.15rem">
                    Must match the QB Rep list exactly — imports join on this
                </div>
            </td>
            <td style="padding:.35rem 0">
                <input type="text" name="quickbooks_name" value="<?= e($r['quickbooks_name'] ?? '') ?>"
                       placeholder="Defaults to the name above" style="<?= $inp ?>;font-family:monospace">
            </td>
        </tr>
        <tr>
            <td style="<?= $lbl ?>">Type</td>
            <td style="padding:.35rem 0">
                <select name="rep_type" style="<?= $inp ?>">
                    <?php foreach ($types as $val => $desc): ?>
                        <option value="<?= $val ?>" <?= ($r['rep_type'] ?? 'person') === $val ? 'selected' : '' ?>>
                            <?= e($desc) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td style="<?= $lbl ?>">
                Linked login
                <div style="font-size:.72rem;color:#9ca3af;margin-top:.15rem">
                    Only if this rep has a USSCOS account
                </div>
            </td>
            <td style="padding:.35rem 0">
                <select name="user_id" style="<?= $inp ?>">
                    <option value="">— None —</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= (int)$u['id'] ?>" <?= (int)($r['user_id'] ?? 0) === (int)$u['id'] ? 'selected' : '' ?>>
                            <?= e(trim($u['first_name'] . ' ' . $u['last_name'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td style="<?= $lbl ?>">Commission rate (%)</td>
            <td style="padding:.35rem 0">
                <input type="number" step="0.01" name="commission_rate"
                       value="<?= e($r['commission_rate'] ?? '') ?>" placeholder="e.g. 5.00" style="<?= $inp ?>">
            </td>
        </tr>
        <tr>
            <td style="<?= $lbl ?>">
                Active
                <div style="font-size:.72rem;color:#9ca3af;margin-top:.15rem">
                    Inactive hides them from pickers; history is untouched
                </div>
            </td>
            <td style="padding:.35rem 0">
                <select name="is_active" style="<?= $inp ?>">
                    <option value="1" <?= (int)($r['is_active'] ?? 1) === 1 ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= (int)($r['is_active'] ?? 1) === 0 ? 'selected' : '' ?>>Inactive — no longer with the company</option>
                </select>
            </td>
        </tr>
        <tr>
            <td style="<?= $lbl ?>;vertical-align:top;padding-top:.6rem">Notes</td>
            <td style="padding:.35rem 0">
                <textarea name="notes" rows="3" maxlength="255" style="<?= $inp ?>;resize:vertical"><?= e($r['notes'] ?? '') ?></textarea>
            </td>
        </tr>
    </table>
</div>

<div style="padding:1rem 0 2rem;max-width:680px;text-align:right">
    <a href="/admin/sales-reps" class="btn btn--secondary" style="margin-right:.75rem">Cancel</a>
    <button type="submit" class="btn btn--primary"><?= $isNew ? 'Add Rep' : 'Save Rep' ?></button>
</div>

</form>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
