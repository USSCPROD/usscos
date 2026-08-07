<?php ob_start();
$pmt      = $payment;
$applied  = array_sum(array_column($applications, 'amount_applied'));
$unapp    = round((float)$pmt['amount'] - $applied, 2);

// Index existing applications by invoice_id for easy lookup
$appByInv = [];
foreach ($applications as $app) {
    $appByInv[(int)$app['invoice_id']] = (float)$app['amount_applied'];
}

$methods = [
    'check'       => 'Check',
    'ach'         => 'ACH / EFT',
    'credit_card' => 'Credit Card',
    'wire'        => 'Wire Transfer',
    'cash'        => 'Cash',
    'other'       => 'Other',
];
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
    <div>
        <a href="/customers/<?= (int)$pmt['customer_id'] ?>" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            <?= e($pmt['company_name']) ?>
        </a>
        <h1 class="page-title" style="margin-top:.25rem">Edit Payment</h1>
    </div>
</div>

<!-- Summary KPIs -->
<div class="kpi-row" style="margin-bottom:1.5rem">
    <div class="kpi-card">
        <div class="kpi-card__label">Customer</div>
        <div class="kpi-card__value kpi-card__value--sm">
            <a href="/customers/<?= (int)$pmt['customer_id'] ?>" class="link"><?= e($pmt['company_name']) ?></a>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Payment Amount</div>
        <div class="kpi-card__value">$<?= number_format((float)$pmt['amount'], 2) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Applied</div>
        <div class="kpi-card__value text-success">$<?= number_format($applied, 2) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Unapplied</div>
        <div class="kpi-card__value <?= $unapp > 0.01 ? 'text-warning' : 'text-success' ?>">
            $<?= number_format($unapp, 2) ?>
        </div>
    </div>
</div>

<form method="post" action="/payments/<?= (int)$pmt['id'] ?>/edit" id="payForm">
<?= csrf_field() ?>

<div style="background:#fff;border:1px solid #d1d5db;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.08);font-family:inherit">

    <!-- Customer bar -->
    <div style="padding:.85rem 1.5rem;background:#f8f9fb;border-bottom:1px solid #d1d5db">
        <div style="display:flex;align-items:center;gap:1rem">
            <span style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">Customer</span>
            <span style="font-size:1rem;font-weight:700;color:#0A3D91"><?= e($pmt['company_name']) ?></span>
        </div>
    </div>

    <!-- Payment header fields -->
    <table style="width:100%;border-collapse:collapse;border-bottom:2px solid #d1d5db;table-layout:fixed">
        <colgroup>
            <col style="width:22%">
            <col style="width:22%">
            <col style="width:22%">
            <col style="width:34%">
        </colgroup>
        <tr>
            <td style="padding:1.25rem 1.5rem;vertical-align:top;border-right:1px solid #d1d5db">
                <div style="<?= epmLbl() ?>">Payment Date</div>
                <input type="date" name="payment_date" value="<?= e($pmt['payment_date']) ?>" required style="<?= epmInp() ?>">
            </td>
            <td style="padding:1.25rem 1.5rem;vertical-align:top;border-right:1px solid #d1d5db">
                <div style="<?= epmLbl() ?>">Payment Method</div>
                <select name="payment_method" id="methodSelect" style="<?= epmInp() ?>">
                    <?php foreach ($methods as $val => $label): ?>
                        <option value="<?= $val ?>" <?= $pmt['payment_method'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td style="padding:1.25rem 1.5rem;vertical-align:top;border-right:1px solid #d1d5db">
                <div style="<?= epmLbl() ?>" id="refLabel"><?= $pmt['payment_method'] === 'check' ? 'Check #' : 'Reference #' ?></div>
                <input type="text" name="reference_number" id="refNumber" maxlength="100"
                    value="<?= e($pmt['reference_number'] ?? '') ?>"
                    style="<?= epmInp() ?>">
            </td>
            <td style="padding:1.25rem 1.5rem;vertical-align:top">
                <div style="<?= epmLbl() ?>">Payment Amount</div>
                <input type="number" name="amount" id="paymentAmount" step="0.01" min="0.01"
                    value="<?= number_format((float)$pmt['amount'], 2, '.', '') ?>" required
                    style="<?= epmInp() ?>;text-align:right;font-weight:700;font-size:1.15rem">
            </td>
        </tr>
    </table>

    <!-- Invoice application table -->
    <div style="border-bottom:2px solid #d1d5db">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:.6rem 1.25rem;background:#f8f9fb;border-bottom:1px solid #d1d5db">
            <span style="font-size:.8rem;font-weight:700;color:#374151">Apply to Invoices</span>
            <div style="display:flex;gap:.5rem">
                <button type="button" id="applyOldestBtn" class="btn btn--sm btn--secondary">Apply Oldest First</button>
                <button type="button" id="clearAllBtn" class="btn btn--sm btn--secondary">Clear All</button>
            </div>
        </div>

        <?php if (empty($open_invoices)): ?>
            <div style="padding:1.5rem;text-align:center;color:#6b7280;font-size:.9rem">No open invoices for this customer.</div>
        <?php else: ?>
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:.925rem">
                <colgroup>
                    <col style="width:40px">
                    <col style="width:120px">
                    <col style="width:110px">
                    <col style="width:110px">
                    <col style="width:110px">
                    <col>
                    <col style="width:140px">
                </colgroup>
                <thead>
                    <tr style="background:#f8f9fb;border-bottom:1px solid #d1d5db">
                        <th style="<?= epmTh() ?>;text-align:center">
                            <input type="checkbox" id="checkAll" title="Select all" style="accent-color:#0A3D91;width:16px;height:16px;cursor:pointer">
                        </th>
                        <th style="<?= epmTh() ?>">Invoice #</th>
                        <th style="<?= epmTh() ?>">Date</th>
                        <th style="<?= epmTh() ?>">Due Date</th>
                        <th style="<?= epmTh() ?>;text-align:right">Invoice Total</th>
                        <th style="<?= epmTh() ?>;text-align:right">Balance Due</th>
                        <th style="<?= epmTh() ?>;text-align:right">Amount to Apply</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($open_invoices as $inv):
                        $invId       = (int)$inv['id'];
                        $balance     = (float)($inv['balance_due'] ?? 0);
                        $existingAmt = $appByInv[$invId] ?? 0;
                        // For display, add existing applied back to balance
                        $displayBal  = round($balance + $existingAmt, 2);
                        $overdue     = ($inv['status'] ?? '') === 'overdue';
                    ?>
                    <tr style="border-bottom:1px solid #f3f4f6<?= $existingAmt > 0 ? ';background:#f0fdf4' : '' ?>">
                        <input type="hidden" name="invoice_id[]" value="<?= $invId ?>">
                        <td style="padding:.55rem .75rem;text-align:center">
                            <input type="checkbox" class="row-check"
                                data-balance="<?= number_format($displayBal, 2, '.', '') ?>"
                                <?= $existingAmt > 0 ? 'checked' : '' ?>
                                style="accent-color:#0A3D91;width:16px;height:16px;cursor:pointer">
                        </td>
                        <td style="padding:.55rem .75rem;font-family:monospace;font-size:.875rem">
                            <a href="/invoices/<?= $invId ?>" class="link" target="_blank"><?= e($inv['invoice_number']) ?></a>
                        </td>
                        <td style="padding:.55rem .75rem;color:#6b7280;font-size:.875rem"><?= date('M j, Y', strtotime($inv['invoice_date'])) ?></td>
                        <td style="padding:.55rem .75rem;font-size:.875rem;<?= $overdue ? 'color:#dc2626;font-weight:600' : 'color:#6b7280' ?>">
                            <?= date('M j, Y', strtotime($inv['due_date'])) ?>
                        </td>
                        <td style="padding:.55rem .75rem;text-align:right;font-family:monospace">$<?= number_format((float)$inv['total_amount'], 2) ?></td>
                        <td style="padding:.55rem .75rem;text-align:right;font-family:monospace;font-weight:600;color:#d97706">$<?= number_format($displayBal, 2) ?></td>
                        <td style="padding:.35rem .75rem;text-align:right">
                            <input type="number" name="apply[]"
                                class="apply-input"
                                data-balance="<?= number_format($displayBal, 2, '.', '') ?>"
                                value="<?= $existingAmt > 0 ? number_format($existingAmt, 2, '.', '') : '' ?>"
                                step="0.01" min="0"
                                placeholder="0.00"
                                style="width:120px;padding:.4rem .6rem;font-size:.95rem;font-family:monospace;font-weight:600;text-align:right;background:#fff;border:1px solid #d1d5db;border-radius:6px;box-sizing:border-box">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Running totals -->
    <table style="width:100%;border-collapse:collapse;border-bottom:2px solid #d1d5db;background:#f8f9fb;table-layout:fixed">
        <colgroup><col style="width:25%"><col style="width:25%"><col style="width:25%"><col style="width:25%"></colgroup>
        <tr>
            <td style="padding:.85rem 1.5rem;vertical-align:top;border-right:1px solid #d1d5db">
                <div style="<?= epmLbl() ?>">Payment Amount</div>
                <div style="font-size:1.1rem;font-weight:700;color:#111" id="dispPayment">$<?= number_format((float)$pmt['amount'], 2) ?></div>
            </td>
            <td style="padding:.85rem 1.5rem;vertical-align:top;border-right:1px solid #d1d5db">
                <div style="<?= epmLbl() ?>">Total Applied</div>
                <div style="font-size:1.1rem;font-weight:700;color:#111" id="dispApplied">$<?= number_format($applied, 2) ?></div>
            </td>
            <td style="padding:.85rem 1.5rem;vertical-align:top;border-right:1px solid #d1d5db">
                <div style="<?= epmLbl() ?>">Difference</div>
                <div style="font-size:1.1rem;font-weight:700" id="dispDiff">$<?= number_format(abs($unapp), 2) ?></div>
            </td>
            <td style="padding:.85rem 1.5rem;vertical-align:top">
                <div style="<?= epmLbl() ?>">Status</div>
                <div style="font-size:.95rem;font-weight:600" id="dispStatus">
                    <?php if (abs($unapp) < 0.01): ?>
                        <span style="color:#16a34a">&#10003; Balanced</span>
                    <?php elseif ($unapp > 0): ?>
                        <span style="color:#d97706">$<?= number_format($unapp, 2) ?> unapplied</span>
                    <?php else: ?>
                        <span style="color:#dc2626">Over by $<?= number_format(abs($unapp), 2) ?></span>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    </table>

    <!-- Memo + Actions -->
    <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #d1d5db">
        <div style="<?= epmLbl() ?>">Memo / Note</div>
        <textarea name="memo" rows="2" style="<?= epmInp() ?>;resize:vertical"><?= e($pmt['memo'] ?? '') ?></textarea>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:.75rem;padding:1rem 1.5rem;background:#f8f9fb">
        <a href="/customers/<?= (int)$pmt['customer_id'] ?>" class="btn btn--secondary">Cancel</a>
        <button type="submit" class="btn btn--primary">Save Changes</button>
    </div>

</div>
</form>

<?php
function epmInp(): string {
    return 'width:100%;padding:.75rem .75rem;font-size:.95rem;font-family:inherit;line-height:1.45;background:#fff;color:#111;border:1px solid #d1d5db;border-radius:6px;box-sizing:border-box';
}
function epmLbl(): string {
    return 'font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.4rem';
}
function epmTh(): string {
    return 'padding:.6rem .75rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;white-space:nowrap;text-align:left';
}
?>

<script>
(function(){
    function fmt(n){return '$'+(parseFloat(n)||0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,',');}

    var paymentInput = document.getElementById('paymentAmount');
    var applyInputs  = document.querySelectorAll('.apply-input');
    var dispPayment  = document.getElementById('dispPayment');
    var dispApplied  = document.getElementById('dispApplied');
    var dispDiff     = document.getElementById('dispDiff');
    var dispStatus   = document.getElementById('dispStatus');

    function recalc(){
        var payment = parseFloat(paymentInput.value)||0;
        var applied = 0;
        applyInputs.forEach(function(el){ applied += parseFloat(el.value)||0; });
        var diff = payment - applied;
        dispPayment.textContent = fmt(payment);
        dispApplied.textContent = fmt(applied);
        dispDiff.textContent    = fmt(Math.abs(diff));
        if(Math.abs(diff) < 0.01){
            dispDiff.style.color   = '#16a34a';
            dispStatus.innerHTML   = '<span style="color:#16a34a">&#10003; Balanced</span>';
        } else if(diff > 0){
            dispDiff.style.color   = '#d97706';
            dispStatus.innerHTML   = '<span style="color:#d97706">'+fmt(diff)+' unapplied</span>';
        } else {
            dispDiff.style.color   = '#dc2626';
            dispStatus.innerHTML   = '<span style="color:#dc2626">Over by '+fmt(Math.abs(diff))+'</span>';
        }
    }

    paymentInput.addEventListener('input', recalc);

    /* Checkbox per row */
    document.querySelectorAll('.row-check').forEach(function(cb){
        cb.addEventListener('change', function(){
            var row   = this.closest('tr');
            var input = row.querySelector('.apply-input');
            input.value = this.checked ? parseFloat(this.dataset.balance).toFixed(2) : '';
            recalc();
        });
    });

    /* Check-all */
    var checkAll = document.getElementById('checkAll');
    if(checkAll){
        checkAll.addEventListener('change', function(){
            var checked = this.checked;
            document.querySelectorAll('.row-check').forEach(function(cb){
                cb.checked = checked;
                var row = cb.closest('tr');
                var inp = row.querySelector('.apply-input');
                inp.value = checked ? parseFloat(cb.dataset.balance).toFixed(2) : '';
            });
            recalc();
        });
    }

    /* Manual input sync */
    applyInputs.forEach(function(el){
        el.addEventListener('input', function(){
            var row = this.closest('tr');
            var cb  = row ? row.querySelector('.row-check') : null;
            if(cb) cb.checked = parseFloat(this.value) > 0;
            recalc();
        });
    });

    /* Apply Oldest First */
    document.getElementById('applyOldestBtn').addEventListener('click', function(){
        var remaining = parseFloat(paymentInput.value)||0;
        if(remaining <= 0){ alert('Enter a payment amount first.'); return; }
        applyInputs.forEach(function(el){
            var bal   = parseFloat(el.dataset.balance)||0;
            var apply = Math.min(bal, remaining);
            el.value  = apply > 0 ? apply.toFixed(2) : '';
            var cb    = el.closest('tr').querySelector('.row-check');
            if(cb) cb.checked = apply > 0;
            remaining = Math.max(0, remaining - apply);
        });
        recalc();
    });

    /* Clear All */
    document.getElementById('clearAllBtn').addEventListener('click', function(){
        applyInputs.forEach(function(el){
            el.value = '';
            var cb = el.closest('tr').querySelector('.row-check');
            if(cb) cb.checked = false;
        });
        recalc();
    });

    /* Method label */
    var refLabels = {check:'Check #',ach:'Transaction / Trace #',credit_card:'Auth / Transaction #',wire:'Wire Reference #',cash:'Receipt #',other:'Reference #'};
    document.getElementById('methodSelect').addEventListener('change',function(){
        document.getElementById('refLabel').textContent = refLabels[this.value]||'Reference #';
    });

    /* Submit warning */
    document.getElementById('payForm').addEventListener('submit', function(e){
        var payment = parseFloat(paymentInput.value)||0;
        var applied = 0;
        applyInputs.forEach(function(el){ applied += parseFloat(el.value)||0; });
        var diff = Math.abs(payment - applied);
        if(payment <= 0){ e.preventDefault(); alert('Please enter a payment amount.'); return; }
        if(diff >= 0.01){
            var msg = 'Payment amount ('+fmt(payment)+') does not match total applied ('+fmt(applied)+').\n\nDifference: '+fmt(diff)+'\n\nSave anyway?';
            if(!confirm(msg)) e.preventDefault();
        }
    });

    recalc();
})();
</script>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
