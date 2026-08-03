<?php
require 'config.php';

$success = false;
$error   = '';
$old     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;

    $required = ['first_name', 'last_name', 'email', 'subject', 'message'];
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
        $payload = json_encode([
            'form_type'  => 'contact',
            'first_name' => trim($_POST['first_name']),
            'last_name'  => trim($_POST['last_name']),
            'email'      => strtolower(trim($_POST['email'])),
            'phone'      => trim($_POST['phone'] ?? ''),
            'subject'    => trim($_POST['subject']),
            'message'    => trim($_POST['message']),
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
            $error = 'There was a problem submitting your message. Please try again or call us directly.';
        }
    }
}

$pageTitle    = 'Contact Us';
$pageSubtitle = 'General inquiry';
include '_layout.php';
?>

<div class="form-card">
    <div class="form-title">Contact Us</div>
    <p class="form-sub">Have a question? Fill out the form below and we'll get back to you within one business day.</p>

    <?php if ($success): ?>
        <div class="alert alert--success">
            <strong>Message sent!</strong> Thank you for reaching out. A member of our team will be in touch shortly.
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert--error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST" action="contact.php">

        <div class="section-label">Your Information</div>

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

        <div class="section-label">Your Message</div>

        <div class="field">
            <label>Subject <span class="req">*</span></label>
            <select name="subject" required>
                <option value="">— Select a subject —</option>
                <?php foreach (['General Inquiry', 'Pricing Question', 'Product Info', 'Other'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($old['subject'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label>Message <span class="req">*</span></label>
            <textarea name="message" rows="5" required maxlength="2000"><?= htmlspecialchars($old['message'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn-submit">Send Message</button>
    </form>
    <?php endif; ?>
</div>

</div></body></html>
