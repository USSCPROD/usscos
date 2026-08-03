<?php
require 'config.php';

$success = false;
$error   = '';
$old     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $required = ['first_name', 'last_name', 'email', 'city', 'state'];
    foreach ($required as $f) {
        if (empty(trim($_POST[$f] ?? ''))) {
            $error = 'Please fill in all required fields.';
            break;
        }
    }

    if (!$error && !filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    }

    if (!$error) {
        $products = $_POST['products'] ?? [];

        $payload = json_encode([
            'form_type'        => 'paint_quote',
            'first_name'       => trim($_POST['first_name']),
            'last_name'        => trim($_POST['last_name']),
            'company'          => trim($_POST['company'] ?? ''),
            'email'            => strtolower(trim($_POST['email'])),
            'phone'            => trim($_POST['phone'] ?? ''),
            'address_line1'    => trim($_POST['address_line1'] ?? ''),
            'address_line2'    => trim($_POST['address_line2'] ?? ''),
            'city'             => trim($_POST['city']),
            'state'            => trim($_POST['state']),
            'postal_code'      => trim($_POST['postal_code'] ?? ''),
            'uses_paint'       => trim($_POST['uses_paint'] ?? ''),
            'current_brand'    => trim($_POST['current_brand'] ?? ''),
            'products'         => array_filter(array_merge($products, [trim($_POST['products_other'] ?? '')])),
            'hear_about_us'    => trim($_POST['hear_about_us'] ?? ''),
            'notes'            => trim($_POST['notes'] ?? ''),
        ]);

        $ch = curl_init(BUSINESSOS_WEBHOOK);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 || $code === 201) {
            $success = true;
            $old = [];
        } else {
            $error = 'There was a problem submitting your request. Please try again or call us directly.';
        }
    }
}

$pageTitle = 'Paint Quote Request';
include '_layout.php';
?>

<div class="form-card">
    <div class="form-title">Paint Quote Request</div>
    <p class="form-sub">Tell us about your paint needs and we'll put together a custom quote for you.</p>

    <?php if ($success): ?>
        <div class="alert alert--success">
            <strong>Quote request received!</strong> Thank you — a member of our paint sales team will reach out to you shortly.
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert--error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST" action="paint-quote.php">

        <div class="section-label">Contact Information</div>

        <table class="half-row" style="margin-bottom:1rem">
            <tr>
                <td>
                    <label>First Name <span class="req">*</span></label>
                    <input type="text" name="first_name" required maxlength="100" value="<?= htmlspecialchars($old['first_name'] ?? '') ?>">
                </td>
                <td>
                    <label>Last Name <span class="req">*</span></label>
                    <input type="text" name="last_name" required maxlength="100" value="<?= htmlspecialchars($old['last_name'] ?? '') ?>">
                </td>
            </tr>
        </table>

        <div class="field">
            <label>Company / Organization</label>
            <input type="text" name="company" maxlength="150" value="<?= htmlspecialchars($old['company'] ?? '') ?>">
        </div>

        <table class="half-row" style="margin-bottom:1rem">
            <tr>
                <td>
                    <label>Email <span class="req">*</span></label>
                    <input type="email" name="email" required maxlength="255" value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                </td>
                <td>
                    <label>Phone</label>
                    <input type="tel" name="phone" maxlength="30" value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
                </td>
            </tr>
        </table>

        <div class="section-label">Shipping Address</div>

        <div class="field">
            <label>Address Line 1</label>
            <input type="text" name="address_line1" maxlength="200" value="<?= htmlspecialchars($old['address_line1'] ?? '') ?>">
        </div>
        <div class="field">
            <label>Address Line 2 <span style="font-weight:400;color:#9ca3af">(Suite, Unit, etc.)</span></label>
            <input type="text" name="address_line2" maxlength="200" value="<?= htmlspecialchars($old['address_line2'] ?? '') ?>">
        </div>

        <table class="half-row" style="margin-bottom:1rem">
            <tr>
                <td>
                    <label>City <span class="req">*</span></label>
                    <input type="text" name="city" required maxlength="100" value="<?= htmlspecialchars($old['city'] ?? '') ?>">
                </td>
                <td>
                    <label>State <span class="req">*</span></label>
                    <select name="state" required>
                        <option value="">— State —</option>
                        <?php
                        $states = ['AL','AK','AZ','AR','CA','CO','CT','DE','FL','GA','HI','ID','IL','IN','IA','KS','KY','LA','ME','MD','MA','MI','MN','MS','MO','MT','NE','NV','NH','NJ','NM','NY','NC','ND','OH','OK','OR','PA','RI','SC','SD','TN','TX','UT','VT','VA','WA','WV','WI','WY'];
                        foreach ($states as $st): ?>
                            <option value="<?= $st ?>" <?= ($old['state'] ?? '') === $st ? 'selected' : '' ?>><?= $st ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>

        <div class="field" style="max-width:180px">
            <label>ZIP Code</label>
            <input type="text" name="postal_code" maxlength="10" value="<?= htmlspecialchars($old['postal_code'] ?? '') ?>">
        </div>

        <div class="section-label">Paint Details</div>

        <div class="field">
            <label>Are you currently using marking paint?</label>
            <div class="radio-group">
                <label><input type="radio" name="uses_paint" value="yes" <?= ($old['uses_paint'] ?? '') === 'yes' ? 'checked' : '' ?> onchange="toggleBrand()"> Yes</label>
                <label><input type="radio" name="uses_paint" value="no"  <?= ($old['uses_paint'] ?? '') === 'no'  ? 'checked' : '' ?> onchange="toggleBrand()"> No</label>
            </div>
            <div id="brand_field" class="conditional">
                <label>What brand are you currently using?</label>
                <input type="text" name="current_brand" maxlength="100" value="<?= htmlspecialchars($old['current_brand'] ?? '') ?>" placeholder="e.g. Rust-Oleum, Seymour">
            </div>
        </div>

        <div class="field">
            <label>Products needed <span style="font-weight:400;color:#9ca3af">(select all that apply)</span></label>
            <div class="checkbox-group">
                <?php foreach (['Aerosol', 'Bulk Paint', 'Robot Paint'] as $prod): ?>
                    <label>
                        <input type="checkbox" name="products[]" value="<?= $prod ?>" <?= in_array($prod, $old['products'] ?? []) ? 'checked' : '' ?>>
                        <?= $prod ?>
                    </label>
                <?php endforeach; ?>
                <label>
                    <input type="checkbox" name="products[]" value="Other" id="prod_other_check" <?= in_array('Other', $old['products'] ?? []) ? 'checked' : '' ?> onchange="toggleProdOther()">
                    Other
                </label>
            </div>
            <div id="prod_other_field" class="conditional" style="margin-top:.5rem">
                <input type="text" name="products_other" maxlength="150" placeholder="Please describe..." value="<?= htmlspecialchars($old['products_other'] ?? '') ?>">
            </div>
        </div>

        <div class="field">
            <label>Additional notes or questions</label>
            <textarea name="notes" rows="4" maxlength="2000"><?= htmlspecialchars($old['notes'] ?? '') ?></textarea>
        </div>

        <div class="field">
            <label>How did you hear about us?</label>
            <select name="hear_about_us">
                <option value="">— Select —</option>
                <?php foreach (['Google Search', 'Social Media', 'Referral from a friend or colleague', 'Trade show or event', 'Existing customer', 'Other'] as $src): ?>
                    <option value="<?= $src ?>" <?= ($old['hear_about_us'] ?? '') === $src ? 'selected' : '' ?>><?= $src ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn-submit">Submit Quote Request</button>
    </form>
    <?php endif; ?>
</div>

<script>
function toggleBrand() {
    var val = document.querySelector('input[name="uses_paint"]:checked');
    document.getElementById('brand_field').style.display = (val && val.value === 'yes') ? 'block' : 'none';
}
function toggleProdOther() {
    var chk = document.getElementById('prod_other_check');
    document.getElementById('prod_other_field').style.display = chk.checked ? 'block' : 'none';
}
// restore on page load (validation failure)
toggleBrand();
toggleProdOther();
</script>

</div></body></html>
