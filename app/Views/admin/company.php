<?php ob_start();
$co = $company ?? [];
?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/admin" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Admin
        </a>
        <h1 class="page-title">Company Info</h1>
    </div>
</div>

<form method="post" action="/admin/company" enctype="multipart/form-data">
<?= csrf_field() ?>

<!-- ── Company Details ──────────────────────────────────────────────────── -->
<div class="card" style="max-width:720px;padding:1.5rem;margin-bottom:1.25rem">
    <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;margin-bottom:1rem;padding-bottom:.5rem;border-bottom:1px solid #e5e7eb">Company Details</div>

    <div style="margin-bottom:1rem">
        <?= admLbl('Company Name') ?>
        <input type="text" name="name" required maxlength="255"
            value="<?= e($co['name'] ?? '') ?>"
            style="<?= admInp() ?>">
    </div>

    <div style="margin-bottom:1rem">
        <?= admLbl('Legal Name') ?>
        <input type="text" name="legal_name" maxlength="255"
            value="<?= e($co['legal_name'] ?? '') ?>"
            style="<?= admInp() ?>">
    </div>

    <table style="width:100%;border-collapse:collapse;table-layout:fixed;margin-bottom:1rem">
        <colgroup><col style="width:50%"><col style="width:50%"></colgroup>
        <tr>
            <td style="padding:0 .5rem 0 0">
                <?= admLbl('Phone') ?>
                <input type="text" name="phone" maxlength="30"
                    value="<?= e($co['phone'] ?? '') ?>"
                    style="<?= admInp() ?>">
            </td>
            <td style="padding:0 0 0 .5rem">
                <?= admLbl('Email') ?>
                <input type="email" name="email" maxlength="255"
                    value="<?= e($co['email'] ?? '') ?>"
                    style="<?= admInp() ?>">
            </td>
        </tr>
    </table>

    <div style="margin-bottom:1rem">
        <?= admLbl('Website') ?>
        <input type="text" name="website" maxlength="255"
            value="<?= e($co['website'] ?? '') ?>"
            style="<?= admInp() ?>">
    </div>

    <div style="margin-bottom:1rem">
        <?= admLbl('Address Line 1') ?>
        <input type="text" name="address_line1" maxlength="255"
            value="<?= e($co['address_line1'] ?? '') ?>"
            style="<?= admInp() ?>">
    </div>
    <div style="margin-bottom:1rem">
        <?= admLbl('Address Line 2') ?>
        <input type="text" name="address_line2" maxlength="255"
            value="<?= e($co['address_line2'] ?? '') ?>"
            style="<?= admInp() ?>">
    </div>

    <table style="width:100%;border-collapse:collapse;table-layout:fixed;margin-bottom:0">
        <colgroup><col style="width:50%"><col style="width:25%"><col style="width:25%"></colgroup>
        <tr>
            <td style="padding:0 .5rem 0 0">
                <?= admLbl('City') ?>
                <input type="text" name="city" maxlength="100"
                    value="<?= e($co['city'] ?? '') ?>"
                    style="<?= admInp() ?>">
            </td>
            <td style="padding:0 .5rem">
                <?= admLbl('State') ?>
                <input type="text" name="state" maxlength="100"
                    value="<?= e($co['state'] ?? '') ?>"
                    style="<?= admInp() ?>">
            </td>
            <td style="padding:0 0 0 .5rem">
                <?= admLbl('Zip') ?>
                <input type="text" name="postal_code" maxlength="20"
                    value="<?= e($co['postal_code'] ?? '') ?>"
                    style="<?= admInp() ?>">
            </td>
        </tr>
    </table>
</div>

<!-- ── Logo ─────────────────────────────────────────────────────────────── -->
<div class="card" style="max-width:720px;padding:1.5rem;margin-bottom:1.25rem">
    <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;margin-bottom:1rem;padding-bottom:.5rem;border-bottom:1px solid #e5e7eb">Company Logo</div>

    <table style="width:100%;border-collapse:collapse;table-layout:fixed">
        <colgroup><col style="width:180px"><col></colgroup>
        <tr>
            <td style="padding:0 1.5rem 0 0;vertical-align:middle">
                <?php if (!empty($co['logo'])): ?>
                    <img src="<?= rtrim(\App\Core\Config::get('app.url', 'https://usscos.com'), '/') . '/' . e($co['logo']) ?>" alt="Logo"
                         style="max-width:160px;max-height:80px;border:1px solid #e5e7eb;border-radius:6px;padding:8px;background:#fff;object-fit:contain">
                <?php else: ?>
                    <div style="width:160px;height:80px;border:2px dashed #d1d5db;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:.85rem">No logo</div>
                <?php endif; ?>
            </td>
            <td style="vertical-align:middle">
                <?= admLbl('Upload New Logo') ?>
                <input type="file" name="logo" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml"
                    style="font-size:.9rem;font-family:inherit;color:#374151">
                <div style="font-size:.8rem;color:#6b7280;margin-top:.4rem">JPG, PNG, GIF, WebP or SVG &bull; Max 2 MB &bull; Recommended: 300&times;100 px</div>
            </td>
        </tr>
    </table>
</div>

<!-- ── System Defaults ───────────────────────────────────────────────────── -->
<div class="card" style="max-width:720px;padding:1.5rem;margin-bottom:1.5rem">
    <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;margin-bottom:1rem;padding-bottom:.5rem;border-bottom:1px solid #e5e7eb">System Defaults</div>
    <div style="font-size:.85rem;color:#6b7280;margin-bottom:1rem">These values pre-fill new sales orders and invoices. They can always be overridden per document.</div>

    <table style="width:100%;border-collapse:collapse;table-layout:fixed;margin-bottom:0">
        <colgroup><col style="width:33.33%"><col style="width:33.33%"><col style="width:33.33%"></colgroup>
        <tr>
            <td style="padding:0 .75rem 0 0;vertical-align:top">
                <?= admLbl('Default Tax Rate') ?>
                <select name="default_tax_rate_id" style="<?= admInp() ?>">
                    <option value="">— None —</option>
                    <?php foreach ($tax_rates as $tr): ?>
                        <option value="<?= (int)$tr['id'] ?>"
                            <?= (int)($co['default_tax_rate_id'] ?? 0) === (int)$tr['id'] ? 'selected' : '' ?>>
                            <?= e($tr['name']) ?> (<?= number_format((float)$tr['rate'] * 100, 2) ?>%)
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td style="padding:0 .375rem;vertical-align:top">
                <?= admLbl('Default Payment Terms') ?>
                <select name="default_payment_term_id" style="<?= admInp() ?>">
                    <option value="">— None —</option>
                    <?php foreach ($payment_terms as $pt): ?>
                        <option value="<?= (int)$pt['id'] ?>"
                            <?= (int)($co['default_payment_term_id'] ?? 0) === (int)$pt['id'] ? 'selected' : '' ?>>
                            <?= e($pt['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td style="padding:0 0 0 .75rem;vertical-align:top">
                <?= admLbl('Default Ship Via') ?>
                <select name="default_ship_via_id" style="<?= admInp() ?>">
                    <option value="">— None —</option>
                    <?php foreach ($ship_via as $sv): ?>
                        <option value="<?= (int)$sv['id'] ?>"
                            <?= (int)($co['default_ship_via_id'] ?? 0) === (int)$sv['id'] ? 'selected' : '' ?>>
                            <?= e($sv['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    </table>
</div>

<div style="display:flex;gap:.75rem;max-width:720px">
    <button type="submit" class="btn btn--primary">Save Changes</button>
</div>

</form>

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
