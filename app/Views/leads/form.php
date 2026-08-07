<?php ob_start();
$isEdit = $lead !== null;
$action = $isEdit ? '/leads/' . (int)$lead['id'] . '/edit' : '/leads';
$val    = fn(string $k) => e($lead[$k] ?? '');
?>

<div class="page-header">
    <div class="page-header__left">
        <a href="<?= $isEdit ? '/leads/' . (int)$lead['id'] : '/leads' ?>" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            <?= $isEdit ? e($lead['company_name']) : 'Leads' ?>
        </a>
        <h1 class="page-title"><?= e($title) ?></h1>
    </div>
</div>

<form method="POST" action="<?= $action ?>">
<?= csrf_field() ?>
<div style="max-width:900px">
    <div class="card" style="padding:1.5rem">
            <h3 class="card__section-title">Company &amp; Contact</h3>
            <table style="width:100%;border-collapse:collapse">
                <tr>
                    <td style="padding:.5rem .75rem .5rem 0;width:50%;vertical-align:top">
                        <label class="form-label">Company Name <span style="color:#dc2626">*</span></label>
                        <input type="text" name="company_name" required value="<?= $val('company_name') ?>" class="input" style="width:100%;box-sizing:border-box">
                    </td>
                    <td style="padding:.5rem 0 .5rem .75rem;width:50%;vertical-align:top">
                        <label class="form-label">Website</label>
                        <input type="text" name="website" value="<?= $val('website') ?>" class="input" style="width:100%;box-sizing:border-box" placeholder="https://">
                    </td>
                </tr>
                <tr>
                    <td style="padding:.5rem .75rem .5rem 0;vertical-align:top">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" value="<?= $val('first_name') ?>" class="input" style="width:100%;box-sizing:border-box">
                    </td>
                    <td style="padding:.5rem 0 .5rem .75rem;vertical-align:top">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" value="<?= $val('last_name') ?>" class="input" style="width:100%;box-sizing:border-box">
                    </td>
                </tr>
                <tr>
                    <td style="padding:.5rem .75rem .5rem 0;vertical-align:top">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="<?= $val('email') ?>" class="input" style="width:100%;box-sizing:border-box">
                    </td>
                    <td style="padding:.5rem 0 .5rem .75rem;vertical-align:top">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" value="<?= $val('phone') ?>" class="input" style="width:100%;box-sizing:border-box">
                    </td>
                </tr>
            </table>

            <h3 class="card__section-title" style="margin-top:1.5rem">Lead Details</h3>
            <table style="width:100%;border-collapse:collapse">
                <tr>
                    <td style="padding:.5rem .75rem .5rem 0;width:33%;vertical-align:top">
                        <label class="form-label">Source</label>
                        <select name="source" class="input" style="width:100%;box-sizing:border-box">
                            <?php foreach (['web' => 'Web', 'referral' => 'Referral', 'trade_show' => 'Trade Show', 'cold_call' => 'Cold Call', 'social' => 'Social Media', 'email_campaign' => 'Email Campaign', 'other' => 'Other'] as $val2 => $lbl): ?>
                                <option value="<?= $val2 ?>" <?= ($lead['source'] ?? 'other') === $val2 ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td style="padding:.5rem .75rem .5rem .75rem;width:33%;vertical-align:top">
                        <label class="form-label">Status</label>
                        <select name="status" class="input" style="width:100%;box-sizing:border-box">
                            <?php foreach (['new' => 'New', 'contacted' => 'Contacted', 'qualified' => 'Qualified', 'converted' => 'Converted', 'dead' => 'Dead'] as $val2 => $lbl): ?>
                                <option value="<?= $val2 ?>" <?= ($lead['status'] ?? 'new') === $val2 ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td style="padding:.5rem 0 .5rem .75rem;width:33%;vertical-align:top">
                        <label class="form-label">Assigned Rep</label>
                        <select name="rep_id" class="input" style="width:100%;box-sizing:border-box">
                            <option value="">— Unassigned —</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= (int)$u['id'] ?>" <?= (string)($lead['rep_id'] ?? '') === (string)$u['id'] ? 'selected' : '' ?>>
                                    <?= e($u['first_name'] . ' ' . $u['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>

            <div style="margin-top:1rem">
                <label class="form-label">Notes</label>
                <textarea name="notes" rows="4" class="input" style="width:100%;box-sizing:border-box;resize:vertical"><?= e($lead['notes'] ?? '') ?></textarea>
            </div>
    </div>
</div>

<!-- Save bar -->
<div class="save-bar">
    <a href="<?= $isEdit ? '/leads/' . (int)$lead['id'] : '/leads' ?>" class="btn btn--secondary">Cancel</a>
    <button type="submit" class="btn btn--primary"><?= $isEdit ? 'Save Lead' : 'Create Lead' ?></button>
</div>
</form>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
