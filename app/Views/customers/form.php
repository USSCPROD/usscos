<?php ob_start(); ?>
<?php
$lbl = 'width:30%;padding:.5rem .6rem .5rem 0;font-size:.85rem;color:#6b7280;vertical-align:middle';
$inp = 'width:100%;padding:.5rem .65rem;font-size:.9rem;font-family:inherit;border:1px solid #d1d5db;'
     . 'border-radius:5px;box-sizing:border-box;background:#fff;color:#111';

// Prefixed to avoid clashing with any loop variable further down this view.
$custVal = fn(string $k) => e($old[$k] ?? '');
?>

<div class="page-header">
    <div class="page-header__left">
        <div style="font-size:.85rem;color:var(--color-text-muted);margin-bottom:.25rem">
            <a href="/customers" style="color:inherit">Customers</a> &rsaquo; Add
        </div>
        <h1 class="page-title" style="margin:0">Add Customer</h1>
    </div>
</div>

<?php if (!empty($duplicates)): ?>
<div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:1rem 1.15rem;margin-bottom:1.25rem;max-width:720px">
    <div style="font-weight:600;color:#92400e;margin-bottom:.5rem">
        This may already exist — <?= count($duplicates) ?> similar
        customer<?= count($duplicates) === 1 ? '' : 's' ?> found
    </div>
    <table style="width:100%;border-collapse:collapse;font-size:.85rem">
        <?php foreach ($duplicates as $dup): ?>
            <tr style="border-top:1px solid #fde68a">
                <td style="padding:.45rem .5rem .45rem 0">
                    <?php if ($dup['visible'] && $dup['id'] !== null): ?>
                        <a href="/customers/<?= (int)$dup['id'] ?>" style="color:#0A3D91;font-weight:500"><?= e($dup['company_name']) ?></a>
                    <?php else: ?>
                        <span style="font-weight:500"><?= e($dup['company_name']) ?></span>
                    <?php endif; ?>
                    <?php if (!$dup['is_active']): ?>
                        <span class="badge badge--neutral" style="margin-left:.35rem">Inactive</span>
                    <?php endif; ?>
                </td>
                <td style="padding:.45rem .5rem;color:#6b7280">
                    <?php if ($dup['visible']): ?>
                        <?= e($dup['email'] ?: '') ?><?= $dup['email'] && $dup['phone'] ? ' · ' : '' ?><?= e($dup['phone'] ?: '') ?>
                    <?php else: ?>
                        <em>Assigned to <?= e($dup['sales_rep_name'] ?: 'another rep') ?> — ask the office before adding it again</em>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <div style="margin-top:.7rem;font-size:.8rem;color:#92400e">
        If none of these is the same company, submit again to create it anyway.
    </div>
</div>
<?php endif; ?>

<form method="POST" action="/customers">
<?= csrf_field() ?>
<?php if (!empty($duplicates)): ?>
    <?php // Present only after the warning, so creating a duplicate is always deliberate. ?>
    <input type="hidden" name="confirm_duplicate" value="1">
<?php endif; ?>

<div class="card" style="max-width:720px;padding:1.5rem">
    <table style="width:100%;border-collapse:collapse">
        <tr>
            <td style="<?= $lbl ?>">Company name <span style="color:#dc2626">*</span></td>
            <td style="padding:.35rem 0">
                <input type="text" name="company_name" required value="<?= $custVal('company_name') ?>" style="<?= $inp ?>">
            </td>
        </tr>
        <tr>
            <td style="<?= $lbl ?>">
                QuickBooks name
                <div style="font-size:.72rem;color:#9ca3af;margin-top:.15rem">Defaults to the company name</div>
            </td>
            <td style="padding:.35rem 0">
                <input type="text" name="quickbooks_name" value="<?= $custVal('quickbooks_name') ?>" style="<?= $inp ?>;font-family:monospace">
            </td>
        </tr>
        <tr>
            <td style="<?= $lbl ?>">Email</td>
            <td style="padding:.35rem 0">
                <input type="email" name="email" value="<?= $custVal('email') ?>" style="<?= $inp ?>">
            </td>
        </tr>
        <tr>
            <td style="<?= $lbl ?>">Phone</td>
            <td style="padding:.35rem 0">
                <input type="text" name="phone" value="<?= $custVal('phone') ?>" style="<?= $inp ?>">
            </td>
        </tr>
        <tr>
            <td style="<?= $lbl ?>">Billing address</td>
            <td style="padding:.35rem 0">
                <input type="text" name="bill_address_1" value="<?= $custVal('bill_address_1') ?>" style="<?= $inp ?>">
            </td>
        </tr>
        <tr>
            <td style="<?= $lbl ?>">City / State / ZIP</td>
            <td style="padding:.35rem 0">
                <table style="width:100%;border-collapse:separate;border-spacing:.4rem 0;margin:0 -.4rem">
                    <tr>
                        <td style="width:50%"><input type="text" name="bill_city" placeholder="City" value="<?= $custVal('bill_city') ?>" style="<?= $inp ?>"></td>
                        <td style="width:20%"><input type="text" name="bill_state" maxlength="2" placeholder="ST" value="<?= $custVal('bill_state') ?>" style="<?= $inp ?>"></td>
                        <td style="width:30%"><input type="text" name="bill_zip" placeholder="ZIP" value="<?= $custVal('bill_zip') ?>" style="<?= $inp ?>"></td>
                    </tr>
                </table>
            </td>
        </tr>
        <?php if (!empty($reps)): ?>
        <tr>
            <td style="<?= $lbl ?>">Sales rep</td>
            <td style="padding:.35rem 0">
                <select name="sales_rep_id" style="<?= $inp ?>">
                    <option value="">— None —</option>
                    <?php foreach ($reps as $repRow): ?>
                        <option value="<?= (int)$repRow['id'] ?>" <?= (string)($old['sales_rep_id'] ?? '') === (string)$repRow['id'] ? 'selected' : '' ?>>
                            <?= e($repRow['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <?php else: ?>
            <?php // Restricted users don't choose — the customer is assigned to them. ?>
        <?php endif; ?>
    </table>
</div>

<div style="padding:1rem 0 2rem;max-width:720px;text-align:right">
    <a href="/customers" class="btn btn--secondary" style="margin-right:.75rem">Cancel</a>
    <button type="submit" class="btn btn--primary">
        <?= !empty($duplicates) ? 'Create Anyway' : 'Add Customer' ?>
    </button>
</div>

</form>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
