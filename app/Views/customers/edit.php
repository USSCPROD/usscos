<?php ob_start(); ?>
<?php $c = $customer; ?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/customers/<?= (int)$c['id'] ?>" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            <?= e($c['company_name']) ?>
        </a>
        <h1 class="page-title">Edit Customer</h1>
    </div>
</div>

<form method="POST" action="/customers/<?= (int)$c['id'] ?>/edit">

<!-- Tab bar -->
<div class="card" style="padding:0;margin-bottom:1.5rem;overflow:hidden">
    <div style="display:flex;border-bottom:2px solid #e5e7eb;background:#f8f9fb">
        <button type="button" id="etab-company" onclick="switchEditTab('company')"
            style="padding:.75rem 1.25rem;font-size:.875rem;font-weight:600;border:none;background:none;cursor:pointer;border-bottom:2px solid #0A3D91;margin-bottom:-2px;color:#0A3D91;white-space:nowrap">
            Company &amp; Address
        </button>
        <button type="button" id="etab-payment" onclick="switchEditTab('payment')"
            style="padding:.75rem 1.25rem;font-size:.875rem;font-weight:600;border:none;background:none;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;color:#6b7280;white-space:nowrap">
            Payment Settings
        </button>
        <button type="button" id="etab-tax" onclick="switchEditTab('tax')"
            style="padding:.75rem 1.25rem;font-size:.875rem;font-weight:600;border:none;background:none;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;color:#6b7280;white-space:nowrap">
            Sales Tax
        </button>
        <button type="button" id="etab-additional" onclick="switchEditTab('additional')"
            style="padding:.75rem 1.25rem;font-size:.875rem;font-weight:600;border:none;background:none;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;color:#6b7280;white-space:nowrap">
            Additional Info
        </button>
    </div>

    <!-- ================================================================
         TAB 1: Company & Address
    ================================================================ -->
    <div id="epanel-company" style="padding:1.5rem">
        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse">
            <tr valign="top">
                <!-- Company Info -->
                <td style="width:50%;padding-right:1.5rem">
                    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:1rem">Company Information</div>

                    <div class="form-group">
                        <label class="form-label">Company Name <span class="text-danger">*</span></label>
                        <input type="text" name="company_name" class="input" required value="<?= e($c['company_name']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Account Number</label>
                        <input type="text" name="account_number" class="input" value="<?= e($c['account_number'] ?? '') ?>">
                    </div>
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="padding-right:.75rem">
                                <div class="form-group">
                                    <label class="form-label">First Name</label>
                                    <input type="text" name="first_name" class="input" value="<?= e($c['first_name'] ?? '') ?>">
                                </div>
                            </td>
                            <td>
                                <div class="form-group">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" name="last_name" class="input" value="<?= e($c['last_name'] ?? '') ?>">
                                </div>
                            </td>
                        </tr>
                    </table>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="input" value="<?= e($c['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">CC Email</label>
                        <input type="email" name="cc_email" class="input" value="<?= e($c['cc_email'] ?? '') ?>">
                    </div>
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="padding-right:.75rem">
                                <div class="form-group">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" name="phone" class="input" value="<?= e($c['phone'] ?? '') ?>">
                                </div>
                            </td>
                            <td>
                                <div class="form-group">
                                    <label class="form-label">Work Phone</label>
                                    <input type="tel" name="work_phone" class="input" value="<?= e($c['work_phone'] ?? '') ?>">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-right:.75rem">
                                <div class="form-group">
                                    <label class="form-label">Mobile</label>
                                    <input type="tel" name="mobile" class="input" value="<?= e($c['mobile'] ?? '') ?>">
                                </div>
                            </td>
                            <td>
                                <div class="form-group">
                                    <label class="form-label">Fax</label>
                                    <input type="tel" name="fax" class="input" value="<?= e($c['fax'] ?? '') ?>">
                                </div>
                            </td>
                        </tr>
                    </table>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="input" rows="3"><?= e($c['notes'] ?? '') ?></textarea>
                    </div>
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="padding-right:2rem">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="is_active" value="1" <?= $c['is_active'] ? 'checked' : '' ?>>
                                    Active
                                </label>
                            </td>
                        </tr>
                    </table>
                </td>

                <!-- Addresses -->
                <td style="width:50%">
                    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:1rem">Billing Address</div>

                    <div class="form-group">
                        <label class="form-label">Address Line 1</label>
                        <input type="text" name="bill_address_1" class="input" value="<?= e($c['bill_address_1'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Address Line 2</label>
                        <input type="text" name="bill_address_2" class="input" value="<?= e($c['bill_address_2'] ?? '') ?>">
                    </div>
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="width:40%;padding-right:.75rem">
                                <div class="form-group">
                                    <label class="form-label">City</label>
                                    <input type="text" name="bill_city" class="input" value="<?= e($c['bill_city'] ?? '') ?>">
                                </div>
                            </td>
                            <td style="width:70px;padding-right:.75rem">
                                <div class="form-group">
                                    <label class="form-label">State</label>
                                    <input type="text" name="bill_state" class="input" maxlength="2" value="<?= e($c['bill_state'] ?? '') ?>">
                                </div>
                            </td>
                            <td>
                                <div class="form-group">
                                    <label class="form-label">ZIP</label>
                                    <input type="text" name="bill_zip" class="input" value="<?= e($c['bill_zip'] ?? '') ?>">
                                </div>
                            </td>
                        </tr>
                    </table>

                    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin:1.25rem 0 .75rem">
                        Shipping Address
                        <label class="checkbox-label" style="font-size:.8rem;font-weight:400;text-transform:none;letter-spacing:0;margin-left:1rem;display:inline-flex">
                            <input type="checkbox" id="shipToBilling" name="ship_to_billing" value="1"
                                   <?= (!$c['ship_address_1'] && !$c['ship_city']) ? 'checked' : '' ?>>
                            Same as billing
                        </label>
                    </div>

                    <div id="shipFields" <?= (!$c['ship_address_1'] && !$c['ship_city']) ? 'style="display:none"' : '' ?>>
                        <div class="form-group">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="ship_company" id="ship_company" class="input" value="<?= e($c['ship_company'] ?? '') ?>">
                        </div>
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="padding-right:.75rem">
                                    <div class="form-group">
                                        <label class="form-label">Contact Name</label>
                                        <input type="text" name="ship_contact" id="ship_contact" class="input" value="<?= e($c['ship_contact'] ?? '') ?>">
                                    </div>
                                </td>
                                <td>
                                    <div class="form-group">
                                        <label class="form-label">Phone</label>
                                        <input type="tel" name="ship_phone" id="ship_phone" class="input" value="<?= e($c['ship_phone'] ?? '') ?>">
                                    </div>
                                </td>
                            </tr>
                        </table>
                        <div class="form-group">
                            <label class="form-label">Address Line 1</label>
                            <input type="text" name="ship_address_1" id="ship_address_1" class="input" value="<?= e($c['ship_address_1'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Address Line 2</label>
                            <input type="text" name="ship_address_2" id="ship_address_2" class="input" value="<?= e($c['ship_address_2'] ?? '') ?>">
                        </div>
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="width:40%;padding-right:.75rem">
                                    <div class="form-group">
                                        <label class="form-label">City</label>
                                        <input type="text" name="ship_city" id="ship_city" class="input" value="<?= e($c['ship_city'] ?? '') ?>">
                                    </div>
                                </td>
                                <td style="width:70px;padding-right:.75rem">
                                    <div class="form-group">
                                        <label class="form-label">State</label>
                                        <input type="text" name="ship_state" id="ship_state" class="input" maxlength="2" value="<?= e($c['ship_state'] ?? '') ?>">
                                    </div>
                                </td>
                                <td>
                                    <div class="form-group">
                                        <label class="form-label">ZIP</label>
                                        <input type="text" name="ship_zip" id="ship_zip" class="input" value="<?= e($c['ship_zip'] ?? '') ?>">
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- ================================================================
         TAB 2: Payment Settings
    ================================================================ -->
    <div id="epanel-payment" style="display:none;padding:1.5rem">
        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse">
            <tr valign="top">
                <td style="width:50%;padding-right:1.5rem">
                    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:1rem">Payment Preferences</div>

                    <div class="form-group">
                        <label class="form-label">Payment Terms</label>
                        <select name="payment_term_id" class="input">
                            <option value="">— None —</option>
                            <?php foreach ($payment_terms as $pt): ?>
                                <option value="<?= (int)$pt['id'] ?>" <?= (int)($c['payment_term_id'] ?? 0) === (int)$pt['id'] ? 'selected' : '' ?>>
                                    <?= e($pt['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Credit Limit</label>
                        <input type="number" name="credit_limit" class="input" step="0.01" min="0"
                               value="<?= $c['credit_limit'] !== null ? e($c['credit_limit']) : '' ?>" placeholder="No limit">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Preferred Delivery Method</label>
                        <select name="preferred_delivery_method" class="input">
                            <option value="">— None —</option>
                            <option value="email" <?= ($c['preferred_delivery_method'] ?? '') === 'email' ? 'selected' : '' ?>>Email</option>
                            <option value="mail"  <?= ($c['preferred_delivery_method'] ?? '') === 'mail'  ? 'selected' : '' ?>>Mail</option>
                            <option value="none"  <?= ($c['preferred_delivery_method'] ?? '') === 'none'  ? 'selected' : '' ?>>None</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Preferred Payment Method</label>
                        <select name="preferred_payment_method" class="input" id="prefPayMethod" onchange="toggleCardOnFile()">
                            <option value="">— None —</option>
                            <option value="cash"        <?= ($c['preferred_payment_method'] ?? '') === 'cash'        ? 'selected' : '' ?>>Cash</option>
                            <option value="check"       <?= ($c['preferred_payment_method'] ?? '') === 'check'       ? 'selected' : '' ?>>Check</option>
                            <option value="credit_card" <?= ($c['preferred_payment_method'] ?? '') === 'credit_card' ? 'selected' : '' ?>>Credit Card</option>
                        </select>
                    </div>
                </td>

                <td style="width:50%">
                    <div id="cardOnFileSection" style="<?= ($c['preferred_payment_method'] ?? '') === 'credit_card' ? '' : 'display:none' ?>">
                        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:1rem">Card on File</div>

                        <div class="form-group">
                            <label class="form-label">Card Number</label>
                            <input type="text" name="cc_number" class="input" maxlength="20"
                                   value="<?= e($c['cc_number'] ?? '') ?>" placeholder="•••• •••• •••• ••••" autocomplete="off">
                        </div>
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="padding-right:.75rem">
                                    <div class="form-group">
                                        <label class="form-label">Expiration Date</label>
                                        <input type="text" name="cc_exp_date" class="input" maxlength="7"
                                               value="<?= e($c['cc_exp_date'] ?? '') ?>" placeholder="MM/YYYY" autocomplete="off">
                                    </div>
                                </td>
                                <td>
                                    <div class="form-group">
                                        <label class="form-label">&nbsp;</label>
                                        <span class="text-muted text-sm" style="display:block;margin-top:.5rem">CVV is not stored.</span>
                                    </div>
                                </td>
                            </tr>
                        </table>
                        <div class="form-group">
                            <label class="form-label">Name on Card</label>
                            <input type="text" name="cc_name" class="input" value="<?= e($c['cc_name'] ?? '') ?>" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Billing Address</label>
                            <input type="text" name="cc_billing_address" class="input" value="<?= e($c['cc_billing_address'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Billing ZIP</label>
                            <input type="text" name="cc_billing_zip" class="input" style="max-width:140px" value="<?= e($c['cc_billing_zip'] ?? '') ?>">
                        </div>
                    </div>
                    <div id="cardOnFilePlaceholder" style="<?= ($c['preferred_payment_method'] ?? '') === 'credit_card' ? 'display:none' : '' ?>;padding-top:2rem">
                        <p class="text-muted text-sm">Select <strong>Credit Card</strong> as the preferred payment method to enter card-on-file details.</p>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- ================================================================
         TAB 3: Sales Tax
    ================================================================ -->
    <div id="epanel-tax" style="display:none;padding:1.5rem;max-width:520px">
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:1rem">Sales Tax Settings</div>

        <div class="form-group">
            <label class="form-label">Tax Code</label>
            <input type="text" name="sales_tax_code" class="input" maxlength="50"
                   value="<?= e($c['sales_tax_code'] ?? '') ?>" placeholder="e.g. Tax, Non, Out of State">
        </div>
        <div class="form-group">
            <label class="form-label">Tax Item (Rate)</label>
            <select name="tax_rate_id" class="input">
                <option value="">— None —</option>
                <?php foreach ($tax_rates as $tr): ?>
                    <?php if (!$tr['is_active'] && (int)($c['tax_rate_id'] ?? 0) !== (int)$tr['id']) continue; ?>
                    <option value="<?= (int)$tr['id'] ?>" <?= (int)($c['tax_rate_id'] ?? 0) === (int)$tr['id'] ? 'selected' : '' ?>>
                        <?= e($tr['name']) ?> (<?= number_format((float)$tr['rate'] * 100, 4) ?>%)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Tax Exempt</label>
            <label class="checkbox-label" style="margin-top:.4rem">
                <input type="checkbox" name="tax_exempt" value="1" <?= $c['tax_exempt'] ? 'checked' : '' ?>>
                This customer is tax exempt
            </label>
        </div>
        <div class="form-group">
            <label class="form-label">Resale Number</label>
            <input type="text" name="resale_number" class="input" maxlength="50" value="<?= e($c['resale_number'] ?? '') ?>">
        </div>
    </div>

    <!-- ================================================================
         TAB 4: Additional Info
    ================================================================ -->
    <div id="epanel-additional" style="display:none;padding:1.5rem;max-width:520px">
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:1rem">Classification</div>

        <div class="form-group">
            <label class="form-label">Customer Type</label>
            <select name="customer_type" class="input">
                <option value="">— None —</option>
                <?php foreach ($customer_types as $ct): ?>
                    <option value="<?= e($ct['name']) ?>" <?= ($c['customer_type'] ?? '') === $ct['name'] ? 'selected' : '' ?>>
                        <?= e($ct['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Rep / Distributor</label>
            <select name="rep_id" class="input">
                <option value="">— None —</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= (int)$u['id'] ?>" <?= (int)($c['rep_id'] ?? 0) === (int)$u['id'] ? 'selected' : '' ?>>
                        <?= e($u['first_name'] . ' ' . $u['last_name']) ?>
                        <?php if ($u['role'] ?? ''): ?>(<?= e($u['role']) ?>)<?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="text-muted text-sm" style="margin-top:.35rem">The internal rep or distributor manager assigned to this account.</div>
        </div>

        <div style="border-top:1px solid #e5e7eb;margin:1.5rem 0"></div>
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:1rem">Status</div>

        <label class="checkbox-label">
            <input type="checkbox" name="is_active" value="1" <?= $c['is_active'] ? 'checked' : '' ?>>
            Active customer
        </label>
    </div>

</div><!-- end tab card -->

<div class="form-actions">
    <a href="/customers/<?= (int)$c['id'] ?>" class="btn btn--secondary">Cancel</a>
    <button type="submit" class="btn btn--primary">Save Changes</button>
</div>

</form>

<script>
function switchEditTab(name) {
    var tabs = ['company','payment','tax','additional'];
    var active = '#0A3D91', inactive = '#6b7280';
    tabs.forEach(function(t) {
        var btn   = document.getElementById('etab-' + t);
        var panel = document.getElementById('epanel-' + t);
        var on    = (t === name);
        btn.style.color        = on ? active : inactive;
        btn.style.borderBottom = on ? '2px solid ' + active : '2px solid transparent';
        panel.style.display    = on ? '' : 'none';
    });
}

function toggleCardOnFile() {
    var val = document.getElementById('prefPayMethod').value;
    document.getElementById('cardOnFileSection').style.display    = (val === 'credit_card') ? '' : 'none';
    document.getElementById('cardOnFilePlaceholder').style.display = (val === 'credit_card') ? 'none' : '';
}

(function() {
    var cb     = document.getElementById('shipToBilling');
    var fields = document.getElementById('shipFields');

    function getBilling() {
        return {
            address_1: document.querySelector('[name="bill_address_1"]').value,
            address_2: document.querySelector('[name="bill_address_2"]').value,
            city:      document.querySelector('[name="bill_city"]').value,
            state:     document.querySelector('[name="bill_state"]').value,
            zip:       document.querySelector('[name="bill_zip"]').value,
        };
    }

    function applyState() {
        if (cb.checked) {
            fields.style.display = 'none';
            var b = getBilling();
            document.getElementById('ship_address_1').value = b.address_1;
            document.getElementById('ship_address_2').value = b.address_2;
            document.getElementById('ship_city').value      = b.city;
            document.getElementById('ship_state').value     = b.state;
            document.getElementById('ship_zip').value       = b.zip;
        } else {
            fields.style.display = '';
        }
    }

    cb.addEventListener('change', applyState);
})();
</script>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
