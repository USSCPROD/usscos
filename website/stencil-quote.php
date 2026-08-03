<?php
require 'config.php';

$success = false;
$error   = '';
$old     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $required = ['first_name', 'last_name', 'email', 'project_description'];
    foreach ($required as $f) {
        if (empty(trim($_POST[$f] ?? ''))) {
            $error = 'Please fill in all required fields.';
            break;
        }
    }

    if (!$error && !filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    }

    // Handle file upload
    $attachmentPath = null;
    if (!$error && !empty($_FILES['attachment']['tmp_name'])) {
        $file    = $_FILES['attachment'];
        $allowed = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'application/postscript',
                    'application/illustrator', 'image/vnd.adobe.photoshop'];
        $allowedExt = ['pdf','jpg','jpeg','png','ai','eps','gif'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mime    = mime_content_type($file['tmp_name']);

        if (!in_array($ext, $allowedExt)) {
            $error = 'File type not allowed. Please upload a PDF, JPG, PNG, AI, or EPS file.';
        } elseif ($file['size'] > 10 * 1024 * 1024) {
            $error = 'File must be under 10MB.';
        } else {
            $uploadDir = __DIR__ . '/uploads/stencil/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $filename = uniqid('stencil_', true) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                $attachmentPath = 'uploads/stencil/' . $filename;
            } else {
                $error = 'File upload failed. Please try again.';
            }
        }
    }

    if (!$error) {
        $payload = json_encode([
            'form_type'          => 'stencil_quote',
            'first_name'         => trim($_POST['first_name']),
            'last_name'          => trim($_POST['last_name']),
            'company'            => trim($_POST['company'] ?? ''),
            'email'              => strtolower(trim($_POST['email'])),
            'phone'              => trim($_POST['phone'] ?? ''),
            'address_line1'      => trim($_POST['address_line1'] ?? ''),
            'address_line2'      => trim($_POST['address_line2'] ?? ''),
            'city'               => trim($_POST['city'] ?? ''),
            'state'              => trim($_POST['state'] ?? ''),
            'postal_code'        => trim($_POST['postal_code'] ?? ''),
            'needs_paint'        => trim($_POST['needs_paint'] ?? ''),
            'paint_gallons'      => trim($_POST['paint_gallons'] ?? ''),
            'project_description'=> trim($_POST['project_description']),
            'needed_by'          => trim($_POST['needed_by'] ?? ''),
            'hear_about_us'      => trim($_POST['hear_about_us'] ?? ''),
            'attachment'         => $attachmentPath,
        ]);

        $ch = curl_init(BUSINESSOS_WEBHOOK);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
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

$pageTitle = 'Stencil Quote Request';
include '_layout.php';
?>

<div class="form-card">
    <div class="form-title">Stencil Quote Request</div>
    <p class="form-sub">Tell us about your stencil project. You can upload artwork or a reference file and we'll put together a custom quote.</p>

    <?php if ($success): ?>
        <div class="alert alert--success">
            <strong>Quote request received!</strong> Thank you — someone from our stencil team will be in touch with you shortly.
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert--error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST" action="stencil-quote.php" enctype="multipart/form-data">

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
                    <label>City</label>
                    <input type="text" name="city" maxlength="100" value="<?= htmlspecialchars($old['city'] ?? '') ?>">
                </td>
                <td>
                    <label>State</label>
                    <select name="state">
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

        <div class="section-label">Project Details</div>

        <div class="field">
            <label>Project Description <span class="req">*</span></label>
            <textarea name="project_description" rows="4" required maxlength="2000"><?= htmlspecialchars($old['project_description'] ?? '') ?></textarea>
            <div class="hint">Describe what you need — size, quantity, material, intended use, etc.</div>
        </div>

        <div class="field">
            <label>When do you need this?</label>
            <select name="needed_by">
                <option value="">— Select —</option>
                <?php foreach (['As soon as possible', 'Within 1 week', '1–2 weeks', '2–4 weeks', 'More than a month'] as $opt): ?>
                    <option value="<?= $opt ?>" <?= ($old['needed_by'] ?? '') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label>Will you need paint with this order?</label>
            <div class="radio-group">
                <label><input type="radio" name="needs_paint" value="yes" <?= ($old['needs_paint'] ?? '') === 'yes' ? 'checked' : '' ?> onchange="togglePaint()"> Yes</label>
                <label><input type="radio" name="needs_paint" value="no"  <?= ($old['needs_paint'] ?? '') === 'no'  ? 'checked' : '' ?> onchange="togglePaint()"> No</label>
            </div>
            <div id="paint_field" class="conditional">
                <label>Approximately how many gallons?</label>
                <input type="number" name="paint_gallons" min="1" step="1" style="max-width:140px" value="<?= htmlspecialchars($old['paint_gallons'] ?? '') ?>">
                <div class="hint">This helps us coordinate with our paint sales team.</div>
            </div>
        </div>

        <div class="field">
            <label>Attach artwork or reference file</label>
            <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.ai,.eps,.gif" style="padding:.4rem 0;border:none;box-shadow:none">
            <div class="hint">Accepted: PDF, JPG, PNG, AI, EPS — max 10MB</div>
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
function togglePaint() {
    var val = document.querySelector('input[name="needs_paint"]:checked');
    document.getElementById('paint_field').style.display = (val && val.value === 'yes') ? 'block' : 'none';
}
togglePaint();
</script>

</div></body></html>
