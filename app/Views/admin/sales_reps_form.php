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
    'house'    => 'House account — a long-standing customer with no rep',
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

<div class="card" style="max-width:680px;margin-top:1.25rem">
    <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.9rem">
        Login
    </div>

    <?php if ($loginUser !== null): ?>
        <div style="font-size:.8rem;color:#166534;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;padding:.6rem .8rem;margin-bottom:.9rem">
            Has a login — <strong><?= e($loginUser['email']) ?></strong>
            (<?= e($loginUser['role']) ?><?= (int)$loginUser['is_active'] === 0 ? ', inactive' : '' ?>).
            Leave the password blank to keep the current one.
        </div>
    <?php else: ?>
        <div style="font-size:.8rem;color:#6b7280;background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:.6rem .8rem;margin-bottom:.9rem">
            No login yet. Fill both fields to create one — leave them blank and the rep is
            saved without access, which is right for outside or former reps.
        </div>
    <?php endif; ?>

    <table style="width:100%;border-collapse:collapse">
        <tr>
            <td style="<?= $lbl ?>">Email</td>
            <td style="padding:.35rem 0">
                <input type="email" name="login_email" autocomplete="off"
                       value="<?= e($loginUser['email'] ?? '') ?>"
                       placeholder="e.g. firstname@usscproducts.com" style="<?= $inp ?>">
            </td>
        </tr>
        <tr>
            <td style="<?= $lbl ?>">
                Password
                <div style="font-size:.72rem;color:#9ca3af;margin-top:.15rem">
                    At least <?= (int)$minPasswordLength ?> characters<?= $loginUser !== null ? '. Blank leaves it unchanged' : '' ?>
                </div>
            </td>
            <td style="padding:.35rem 0">
                <input type="password" name="login_password" autocomplete="new-password"
                       placeholder="<?= $loginUser !== null ? 'Leave blank to keep current password' : 'Set a password' ?>"
                       style="<?= $inp ?>">
            </td>
        </tr>
    </table>

    <div style="font-size:.72rem;color:#9ca3af;margin-top:.7rem">
        A <strong>Sales Rep</strong> gets the rep role, a <strong>Partner</strong> the
        distributor role — each limited to their own customers' orders and invoices.
        Deactivating a login is done on the <a href="/admin/users" style="color:#0A3D91">Users</a> page.
    </div>
</div>

<div style="padding:1rem 0 2rem;max-width:680px;text-align:right">
    <a href="/admin/sales-reps" class="btn btn--secondary" style="margin-right:.75rem">Cancel</a>
    <button type="submit" class="btn btn--primary"><?= $isNew ? 'Add Rep' : 'Save Rep' ?></button>
</div>

</form>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
