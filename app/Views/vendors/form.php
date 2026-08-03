<?php ob_start(); ?>
<?php
$flash      = \App\Core\Session::getFlash('success');
$flashError = \App\Core\Session::getFlash('error');
$editing    = !empty($item);
?>

<?php if ($flash): ?>
    <div class="alert alert--success" style="margin-bottom:1rem"><?= e($flash) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert--danger" style="margin-bottom:1rem"><?= e($flashError) ?></div>
<?php endif; ?>

<div class="page-header">
    <div class="page-header__left">
        <div style="font-size:.85rem;color:var(--color-text-muted);margin-bottom:.25rem">
            <a href="/vendors" style="color:inherit">Vendors</a> &rsaquo; <?= $editing ? e($item['company_name']) : 'Add Vendor' ?>
        </div>
        <h1 class="page-title" style="margin:0"><?= $editing ? e($item['company_name']) : 'Add Vendor' ?></h1>
    </div>
    <?php if ($editing): ?>
    <div class="page-header__right">
        <a href="/purchasing?vendor=<?= (int)$item['id'] ?>" class="btn btn--secondary">View POs</a>
    </div>
    <?php endif; ?>
</div>

<form method="POST" action="<?= $editing ? '/vendors/' . (int)$item['id'] . '/edit' : '/vendors' ?>"
      style="max-width:720px">
    <?= csrf_field() ?>

    <!-- Company Info -->
    <div class="card" style="padding:1.5rem;margin-bottom:1.25rem">
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:1rem">Company</div>

        <div style="margin-bottom:.9rem">
            <label class="label">Company Name <span style="color:var(--color-danger)">*</span></label>
            <input type="text" name="company_name" required maxlength="255"
                   value="<?= e($item['company_name'] ?? '') ?>" class="input" style="width:100%">
        </div>

        <table style="width:100%;border-collapse:collapse">
            <tr>
                <td style="width:50%;padding-right:.5rem;padding-bottom:.9rem;vertical-align:top">
                    <label class="label">First Name</label>
                    <input type="text" name="first_name" maxlength="100"
                           value="<?= e($item['first_name'] ?? '') ?>" class="input" style="width:100%">
                </td>
                <td style="width:50%;padding-left:.5rem;padding-bottom:.9rem;vertical-align:top">
                    <label class="label">Last Name</label>
                    <input type="text" name="last_name" maxlength="100"
                           value="<?= e($item['last_name'] ?? '') ?>" class="input" style="width:100%">
                </td>
            </tr>
            <tr>
                <td style="padding-right:.5rem;padding-bottom:.9rem;vertical-align:top">
                    <label class="label">Phone</label>
                    <input type="text" name="phone" maxlength="30"
                           value="<?= e($item['phone'] ?? '') ?>" class="input" style="width:100%">
                </td>
                <td style="padding-left:.5rem;padding-bottom:.9rem;vertical-align:top">
                    <label class="label">Fax</label>
                    <input type="text" name="fax" maxlength="30"
                           value="<?= e($item['fax'] ?? '') ?>" class="input" style="width:100%">
                </td>
            </tr>
            <tr>
                <td style="padding-right:.5rem;padding-bottom:.9rem;vertical-align:top">
                    <label class="label">Email</label>
                    <input type="email" name="email" maxlength="255"
                           value="<?= e($item['email'] ?? '') ?>" class="input" style="width:100%">
                </td>
                <td style="padding-left:.5rem;padding-bottom:.9rem;vertical-align:top">
                    <label class="label">Our Account #</label>
                    <input type="text" name="account_number" maxlength="50"
                           value="<?= e($item['account_number'] ?? '') ?>" class="input" style="width:100%"
                           placeholder="Your account number with this vendor">
                </td>
            </tr>
        </table>
    </div>

    <!-- Address -->
    <div class="card" style="padding:1.5rem;margin-bottom:1.25rem">
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:1rem">Remit-To Address</div>

        <div style="margin-bottom:.9rem">
            <label class="label">Address Line 1</label>
            <input type="text" name="address_1" maxlength="255"
                   value="<?= e($item['address_1'] ?? '') ?>" class="input" style="width:100%">
        </div>
        <div style="margin-bottom:.9rem">
            <label class="label">Address Line 2</label>
            <input type="text" name="address_2" maxlength="255"
                   value="<?= e($item['address_2'] ?? '') ?>" class="input" style="width:100%">
        </div>
        <table style="width:100%;border-collapse:collapse">
            <tr>
                <td style="width:45%;padding-right:.5rem;padding-bottom:.9rem;vertical-align:top">
                    <label class="label">City</label>
                    <input type="text" name="city" maxlength="100"
                           value="<?= e($item['city'] ?? '') ?>" class="input" style="width:100%">
                </td>
                <td style="width:20%;padding-right:.5rem;padding-left:.5rem;padding-bottom:.9rem;vertical-align:top">
                    <label class="label">State</label>
                    <input type="text" name="state" maxlength="50"
                           value="<?= e($item['state'] ?? '') ?>" class="input" style="width:100%">
                </td>
                <td style="width:35%;padding-left:.5rem;padding-bottom:.9rem;vertical-align:top">
                    <label class="label">ZIP</label>
                    <input type="text" name="zip" maxlength="20"
                           value="<?= e($item['zip'] ?? '') ?>" class="input" style="width:100%">
                </td>
            </tr>
        </table>
    </div>

    <!-- Terms & Tax -->
    <div class="card" style="padding:1.5rem;margin-bottom:1.25rem">
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:1rem">Terms &amp; Tax</div>

        <table style="width:100%;border-collapse:collapse">
            <tr>
                <td style="width:50%;padding-right:.5rem;padding-bottom:.9rem;vertical-align:top">
                    <label class="label">Payment Terms</label>
                    <select name="payment_term_id" class="input" style="width:100%">
                        <option value="">— None —</option>
                        <?php foreach ($paymentTerms as $pt): ?>
                            <option value="<?= (int)$pt['id'] ?>"
                                <?= ($item['payment_term_id'] ?? '') == $pt['id'] ? 'selected' : '' ?>>
                                <?= e($pt['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td style="width:50%;padding-left:.5rem;padding-bottom:.9rem;vertical-align:top">
                    <label class="label">Tax ID / EIN</label>
                    <input type="text" name="tax_id" maxlength="30"
                           value="<?= e($item['tax_id'] ?? '') ?>" class="input" style="width:100%">
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding-bottom:.9rem;vertical-align:top">
                    <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.875rem">
                        <input type="checkbox" name="is_1099" value="1"
                               <?= ($item['is_1099'] ?? 0) ? 'checked' : '' ?>>
                        Track for 1099 reporting
                    </label>
                </td>
            </tr>
        </table>

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

    <!-- Notes -->
    <div class="card" style="padding:1.5rem;margin-bottom:1.25rem">
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:1rem">Notes</div>
        <textarea name="notes" rows="3" class="input" style="width:100%;resize:vertical"
                  placeholder="Internal notes about this vendor…"><?= e($item['notes'] ?? '') ?></textarea>
    </div>

    <div style="display:flex;gap:.75rem">
        <button type="submit" class="btn btn--primary"><?= $editing ? 'Save Changes' : 'Create Vendor' ?></button>
        <a href="/vendors" class="btn btn--secondary">Cancel</a>
    </div>
</form>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
