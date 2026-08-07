<?php ob_start(); ?>
<?php
$lbl = 'width:32%;padding:.5rem .6rem .5rem 0;font-size:.85rem;color:#6b7280;vertical-align:middle';
$inp = 'width:100%;padding:.5rem .65rem;font-size:.9rem;font-family:inherit;border:1px solid #d1d5db;'
     . 'border-radius:5px;box-sizing:border-box;background:#fff;color:#111';
$box = 'padding:.5rem .65rem;font-size:.9rem;background:#f9fafb;border:1px solid #e5e7eb;border-radius:5px;color:#374151';

$roleLabels = [
    'owner'       => 'Owner',
    'admin'       => 'Admin',
    'bookkeeper'  => 'Bookkeeper',
    'manager'     => 'Manager',
    'shipping'    => 'Shipping',
    'employee'    => 'Employee',
    'rep'         => 'Sales Rep',
    'distributor' => 'Distributor',
    'readonly'    => 'Read Only',
];
?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title" style="margin:0">My Profile</h1>
        <p class="page-subtitle" style="margin:.25rem 0 0">
            Your account details. Name, email and role are managed by an administrator —
            your password is yours to change.
        </p>
    </div>
</div>

<div class="card" style="max-width:620px;padding:1.5rem">
    <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.9rem">
        Account
    </div>
    <table style="width:100%;border-collapse:collapse">
        <tr>
            <td style="<?= $lbl ?>">Name</td>
            <td style="padding:.35rem 0">
                <div style="<?= $box ?>"><?= e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))) ?></div>
            </td>
        </tr>
        <tr>
            <td style="<?= $lbl ?>">Email</td>
            <td style="padding:.35rem 0">
                <div style="<?= $box ?>"><?= e($user['email'] ?? '') ?></div>
            </td>
        </tr>
        <tr>
            <td style="<?= $lbl ?>">Role</td>
            <td style="padding:.35rem 0">
                <div style="<?= $box ?>"><?= e($roleLabels[$user['role'] ?? ''] ?? ($user['role'] ?? '')) ?></div>
            </td>
        </tr>
    </table>
</div>

<div class="card" style="max-width:620px;margin-top:1.25rem;padding:1.5rem">
    <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.9rem">
        Change Password
    </div>

    <form method="POST" action="/settings/profile/password" autocomplete="off">
        <?= csrf_field() ?>
        <table style="width:100%;border-collapse:collapse">
            <tr>
                <td style="<?= $lbl ?>">Current password</td>
                <td style="padding:.35rem 0">
                    <input type="password" name="current_password" required
                           autocomplete="current-password" style="<?= $inp ?>">
                </td>
            </tr>
            <tr>
                <td style="<?= $lbl ?>">
                    New password
                    <div style="font-size:.72rem;color:#9ca3af;margin-top:.15rem">
                        At least <?= (int)$minPasswordLength ?> characters
                    </div>
                </td>
                <td style="padding:.35rem 0">
                    <input type="password" name="new_password" required minlength="<?= (int)$minPasswordLength ?>"
                           autocomplete="new-password" style="<?= $inp ?>">
                </td>
            </tr>
            <tr>
                <td style="<?= $lbl ?>">Confirm new password</td>
                <td style="padding:.35rem 0">
                    <input type="password" name="confirm_password" required minlength="<?= (int)$minPasswordLength ?>"
                           autocomplete="new-password" style="<?= $inp ?>">
                </td>
            </tr>
        </table>

        <div style="text-align:right;margin-top:1rem">
            <button type="submit" class="btn btn--primary">Change Password</button>
        </div>
    </form>
</div>

<p style="font-size:.78rem;color:#9ca3af;margin:1rem 0 2rem;max-width:620px">
    Your current password is required so that nobody can change it from an unattended
    screen. If you've forgotten it, sign out and use
    <a href="/forgot-password" style="color:#0A3D91">Forgot password</a> instead.
</p>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
