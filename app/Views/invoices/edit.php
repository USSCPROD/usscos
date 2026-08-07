<?php ob_start(); ?>
<?php $inv = $invoice; ?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/invoices/<?= (int)$inv['id'] ?>" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Invoice #<?= e($inv['invoice_number']) ?>
        </a>
        <h1 class="page-title">Edit Invoice #<?= e($inv['invoice_number']) ?></h1>
        <p class="page-subtitle">
            <a href="/customers/<?= (int)$inv['customer_id'] ?>" class="link"><?= e($inv['company_name']) ?></a>
        </p>
    </div>
</div>

<form method="POST" action="/invoices/<?= (int)$inv['id'] ?>/edit">
<?= csrf_field() ?>

<div class="detail-layout">

    <!-- Left: Invoice Details -->
    <div class="detail-layout__side">

        <div class="card">
            <div class="card__header">Invoice Details</div>
            <div class="card__body">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="input">
                        <?php foreach (['draft'=>'Draft','pending'=>'Pending','partial'=>'Partial','paid'=>'Paid','overdue'=>'Overdue','void'=>'Void'] as $val => $label): ?>
                            <option value="<?= $val ?>" <?= $inv['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Invoice Date</label>
                    <input type="date" name="invoice_date" class="input" value="<?= e(substr($inv['invoice_date'], 0, 10)) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" class="input" value="<?= e(substr($inv['due_date'], 0, 10)) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">PO Number</label>
                    <input type="text" name="po_number" class="input" value="<?= e($inv['po_number'] ?? '') ?>" placeholder="Customer PO #">
                </div>
                <div class="form-group">
                    <label class="form-label">Memo (customer-facing)</label>
                    <textarea name="memo" class="input" rows="3" placeholder="Appears on the invoice…"><?= e($inv['memo'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Internal Notes</label>
                    <textarea name="internal_notes" class="input" rows="3" placeholder="Internal only…"><?= e($inv['internal_notes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

    </div>

    <!-- Right: Ship To -->
    <div class="detail-layout__main">

        <?php
        $shipSameBilling = !$inv['ship_address_1'] && !$inv['ship_city'];
        ?>
        <div class="card" id="ship">
            <div class="card__header" style="display:flex;justify-content:space-between;align-items:center">
                Ship To
                <label class="checkbox-label">
                    <input type="checkbox" id="shipToBilling" name="ship_to_billing" value="1" <?= $shipSameBilling ? 'checked' : '' ?>>
                    Same as billing address
                </label>
            </div>
            <div class="card__body" id="shipFields" <?= $shipSameBilling ? 'style="display:none"' : '' ?>>
                <div class="form-row">
                    <div class="form-group" style="flex:1">
                        <label class="form-label">Address Line 1</label>
                        <input type="text" name="ship_address_1" id="ship_address_1" class="input"
                               value="<?= e($inv['ship_address_1'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="flex:1">
                        <label class="form-label">Address Line 2</label>
                        <input type="text" name="ship_address_2" id="ship_address_2" class="input"
                               value="<?= e($inv['ship_address_2'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex:2">
                        <label class="form-label">City</label>
                        <input type="text" name="ship_city" id="ship_city" class="input"
                               value="<?= e($inv['ship_city'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="flex:1">
                        <label class="form-label">State</label>
                        <input type="text" name="ship_state" id="ship_state" class="input" maxlength="2"
                               value="<?= e($inv['ship_state'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="flex:1">
                        <label class="form-label">ZIP</label>
                        <input type="text" name="ship_zip" id="ship_zip" class="input"
                               value="<?= e($inv['ship_zip'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Ship Via</label>
                    <input type="text" name="ship_via" class="input" value="<?= e($inv['ship_via'] ?? '') ?>" placeholder="UPS, FedEx, Freight…">
                </div>
            </div>
            <?php if ($shipSameBilling): ?>
            <div class="card__body" id="shipBillingSummary">
                <address class="address-block text-muted">
                    <?= e($inv['bill_address_1'] ?? '') ?><?= $inv['bill_city'] ? ', ' . e($inv['bill_city']) : '' ?><?= $inv['bill_state'] ? ', ' . e($inv['bill_state']) : '' ?> <?= e($inv['bill_zip'] ?? '') ?>
                </address>
            </div>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <a href="/invoices/<?= (int)$inv['id'] ?>" class="btn btn--secondary">Cancel</a>
            <button type="submit" class="btn btn--primary">Save Changes</button>
        </div>

    </div>
</div>

</form>

<script>
(function() {
    var billing = {
        address_1: <?= json_encode($inv['bill_address_1'] ?? '') ?>,
        address_2: <?= json_encode($inv['bill_address_2'] ?? '') ?>,
        city:      <?= json_encode($inv['bill_city']      ?? '') ?>,
        state:     <?= json_encode($inv['bill_state']     ?? '') ?>,
        zip:       <?= json_encode($inv['bill_zip']       ?? '') ?>,
    };

    var cb      = document.getElementById('shipToBilling');
    var fields  = document.getElementById('shipFields');
    var summary = document.getElementById('shipBillingSummary');

    function applyState() {
        if (cb.checked) {
            fields.style.display = 'none';
            document.getElementById('ship_address_1').value = billing.address_1;
            document.getElementById('ship_address_2').value = billing.address_2;
            document.getElementById('ship_city').value      = billing.city;
            document.getElementById('ship_state').value     = billing.state;
            document.getElementById('ship_zip').value       = billing.zip;
            if (summary) summary.style.display = '';
        } else {
            fields.style.display = '';
            if (summary) summary.style.display = 'none';
        }
    }

    cb.addEventListener('change', applyState);
    applyState();
})();
</script>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
