<?php ob_start(); ?>
<?php
$editing = !empty($item);
$old     = $old ?? [];
$val     = fn(string $k) => e($item[$k] ?? $old[$k] ?? '');
?>
<?php if (!empty($error)): ?>
<div style="background:#fef2f2;border:1px solid #fca5a5;color:#b91c1c;padding:.85rem 1.1rem;border-radius:7px;margin-bottom:1.1rem;font-size:.95rem">
    <?= e($error) ?>
</div>
<?php endif; ?>
<?php
$roles = [
    'owner'       => 'Owner — Full access',
    'admin'       => 'Admin — Full access (no owner mgmt)',
    'bookkeeper'  => 'Bookkeeper — Invoices, payments, reports',
    'manager'     => 'Manager — Sales, customers, products',
    'employee'    => 'Employee — Create SOs and invoices',
    'shipping'    => 'Shipping — Inventory adjustments + fulfillment',
    'rep'         => 'Sales Rep — Rep portal, assigned customers',
    'distributor' => 'Distributor — Distributor portal',
    'readonly'    => 'Read Only — View only',
];
$repRoles = ['rep', 'distributor'];
?>

<div class="page-header">
    <div class="page-header__left">
        <div style="font-size:.85rem;color:var(--color-text-muted);margin-bottom:.25rem">
            <a href="/admin/users" style="color:inherit">Users</a> &rsaquo; <?= $editing ? 'Edit' : 'Add User' ?>
        </div>
        <h1 class="page-title" style="margin:0"><?= $editing ? 'Edit ' . e($item['first_name'] . ' ' . $item['last_name']) : 'Add User' ?></h1>
    </div>
</div>

<form method="POST" action="<?= $editing ? '/admin/users/' . (int)$item['id'] . '/edit' : '/admin/users' ?>"
      style="max-width:640px">
    <?= csrf_field() ?>

    <div class="card" style="padding:1.5rem;margin-bottom:1.25rem">
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:1rem">Personal Info</div>

        <table style="width:100%;border-collapse:collapse">
            <tr>
                <td style="padding:0 .5rem .9rem 0;width:50%;vertical-align:top">
                    <label class="label">First Name <span style="color:var(--color-danger)">*</span></label>
                    <input type="text" name="first_name" required maxlength="100"
                           value="<?= $val('first_name') ?>" class="input" style="width:100%">
                </td>
                <td style="padding:0 0 .9rem .5rem;width:50%;vertical-align:top">
                    <label class="label">Last Name <span style="color:var(--color-danger)">*</span></label>
                    <input type="text" name="last_name" required maxlength="100"
                           value="<?= $val('last_name') ?>" class="input" style="width:100%">
                </td>
            </tr>
            <tr>
                <td style="padding:0 .5rem .9rem 0;vertical-align:top">
                    <label class="label">Email <span style="color:var(--color-danger)">*</span></label>
                    <input type="email" name="email" required maxlength="255"
                           value="<?= $val('email') ?>" class="input" style="width:100%">
                </td>
                <td style="padding:0 0 .9rem .5rem;vertical-align:top">
                    <label class="label">Phone</label>
                    <input type="text" name="phone" maxlength="30"
                           value="<?= $val('phone') ?>" class="input" style="width:100%">
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding:0 0 .9rem 0;vertical-align:top">
                    <label class="label">Job Title</label>
                    <input type="text" name="title" maxlength="100"
                           value="<?= $val('title') ?>" class="input" style="width:100%"
                           placeholder="e.g. Sales Manager, Bookkeeper">
                </td>
            </tr>
        </table>
    </div>

    <div class="card" style="padding:1.5rem;margin-bottom:1.25rem">
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:1rem">Access &amp; Role</div>

        <div style="margin-bottom:.9rem">
            <label class="label">Role <span style="color:var(--color-danger)">*</span></label>
            <select name="role" id="roleSelect" class="input" style="width:100%" onchange="toggleRepFields()">
                <?php // NB: do not name the loop variable $val — it would clobber the $val() closure above.
                foreach ($roles as $roleKey => $roleLabel): ?>
                    <option value="<?= $roleKey ?>" <?= ($item['role'] ?? $old['role'] ?? 'employee') === $roleKey ? 'selected' : '' ?>>
                        <?= e($roleLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-bottom:.9rem">
            <label class="label">Department</label>
            <select name="department_id" class="input" style="width:100%">
                <option value="">— None —</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?= (int)$dept['id'] ?>" <?= (int)($item['department_id'] ?? $old['department_id'] ?? 0) === (int)$dept['id'] ? 'selected' : '' ?>>
                        <?= e($dept['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="repFields" style="display:none">
            <table style="width:100%;border-collapse:collapse">
                <tr>
                    <td style="padding:0 .5rem .9rem 0;width:50%;vertical-align:top">
                        <label class="label">Rep Code</label>
                        <input type="text" name="rep_code" maxlength="20"
                               value="<?= $val('rep_code') ?>" class="input" style="width:100%"
                               placeholder="e.g. JD01">
                    </td>
                    <td style="padding:0 0 .9rem .5rem;width:50%;vertical-align:top">
                        <label class="label">Commission Rate (%)</label>
                        <input type="number" name="commission_rate" step="0.01" min="0" max="100"
                               value="<?= $val('commission_rate') ?>" class="input" style="width:100%"
                               placeholder="e.g. 5.00">
                    </td>
                </tr>
            </table>
        </div>

        <?php if ($editing): ?>
        <div>
            <label class="label">Status</label>
            <select name="is_active" class="input" style="width:auto;min-width:160px">
                <option value="1" <?= ($item['is_active'] ?? 1) ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= !($item['is_active'] ?? 1) ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <?php endif; ?>
    </div>

    <div class="card" style="padding:1.5rem;margin-bottom:1.25rem">
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:1rem">
            <?= $editing ? 'Change Password' : 'Set Password' ?>
        </div>

        <div style="margin-bottom:.9rem">
            <label class="label">
                Password <?= $editing ? '<span class="text-muted" style="font-weight:400">(leave blank to keep current)</span>' : '<span style="color:var(--color-danger)">*</span>' ?>
            </label>
            <input type="password" name="password" <?= $editing ? '' : 'required' ?>
                   minlength="8" autocomplete="new-password"
                   class="input" style="width:100%;max-width:360px"
                   placeholder="<?= $editing ? 'Enter new password to change' : 'Minimum 8 characters' ?>">
        </div>
        <?php if (!$editing): ?>
        <div>
            <label class="label">Confirm Password <span style="color:var(--color-danger)">*</span></label>
            <input type="password" name="password_confirm" required minlength="8" autocomplete="new-password"
                   class="input" style="width:100%;max-width:360px" placeholder="Re-enter password">
        </div>
        <?php endif; ?>
    </div>

    <div style="display:flex;gap:.75rem">
        <button type="submit" class="btn btn--primary">
            <?= $editing ? 'Save Changes' : 'Create User' ?>
        </button>
        <a href="/admin/users" class="btn btn--secondary">Cancel</a>
    </div>

</form>

<script>
var repRoles = <?= json_encode($repRoles) ?>;
function toggleRepFields() {
    var role = document.getElementById('roleSelect').value;
    document.getElementById('repFields').style.display = repRoles.indexOf(role) !== -1 ? '' : 'none';
}
toggleRepFields();
</script>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
