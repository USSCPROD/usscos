<?php ob_start();
$c       = $customer;
$invoices = $open_invoices ?? [];
$preselId = $preselected_invoice ?? null;

$methods = [
    'check'       => 'Check',
    'ach'         => 'ACH / EFT',
    'credit_card' => 'Credit Card',
    'wire'        => 'Wire Transfer',
    'cash'        => 'Cash',
    'other'       => 'Other',
];

$totalBalance = array_sum(array_column($invoices, 'balance_due'));
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
    <div>
        <a href="/customers/<?= (int)$c['id'] ?>" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            <?= e($c['company_name']) ?>
        </a>
        <h1 class="page-title" style="margin-top:.25rem">Receive Payment</h1>
    </div>
</div>

<?php if (empty($invoices)): ?>
<div class="card" style="padding:2rem;text-align:center;color:#6b7280">
    <p style="font-size:1.1rem;margin-bottom:.5rem">No open invoices for this customer.</p>
    <p style="font-size:.9rem">All invoices are paid in full.</p>
    <a href="/customers/<?= (int)$c['id'] ?>" class="btn btn--secondary" style="margin-top:1rem">Back to Customer</a>
</div>
<?php else: ?>

<form method="post" action="/customers/<?= (int)$c['id'] ?>/payment" id="payForm">

<div style="background:#fff;border:1px solid #d1d5db;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.08);font-family:inherit">

    <!-- Customer bar -->
    <div style="padding:.85rem 1.5rem;background:#f8f9fb;border-bottom:1px solid #d1d5db">
        <div style="display:flex;align-items:center;gap:1rem">
            <span style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">Customer</span>
            <span style="font-size:1rem;font-weight:700;color:#0A3D91"><?= e($c['company_name']) ?></span>
            <span style="color:#9ca3af;font-size:.9rem">Total open balance: <strong style="color:#d97706">$<?= number_format($totalBalance, 2) ?></strong></span>
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
                <div style="<?= rpmLbl() ?>">Payment Date</div>
                <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required style="<?= rpmInp() ?>">
            </td>
            <td style="padding:1.25rem 1.5rem;vertical-align:top;border-right:1px solid #d1d5db">
                <div style="<?= rpmLbl() ?>">Payment Method</div>
                <select name="payment_method" id="methodSelect" style="<?= rpmInp() ?>">
                    <?php foreach ($methods as $val => $label): ?>
                        <option value="<?= $val ?>" <?= $val === 'check' ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td style="padding:1.25rem 1.5rem;vertical-align:top;border-right:1px solid #d1d5db">
                <div style="<?= rpmLbl() ?>" id="refLabel">Check #</div>
                <input type="text" name="reference_number" id="refNumber" maxlength="100" placeholder="Check number" style="<?= rpmInp() ?>">
            </td>
            <td style="padding:1.25rem 1.5rem;vertical-align:top">
                <div style="<?= rpmLbl() ?>">Payment Amount</div>
                <input type="number" name="amount" id="paymentAmount" step="0.01" min="0.01"
                    value="" placeholder="0.00" required
                    style="<?= rpmInp() ?>;text-align:right;font-weight:700;font-size:1.15rem">
                <div style="font-size:.75rem;color:#6b7280;margin-top:.35rem">Total open balance: $<?= number_format($totalBalance, 2) ?></div>
            </td>
        </tr>
    </table>

    <!-- Invoice table -->
    <div style="border-bottom:2px solid #d1d5db">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:.6rem 1.25rem;background:#f8f9fb;border-bottom:1px solid #d1d5db">
            <span style="font-size:.8rem;font-weight:700;color:#374151">Apply to Invoices</span>
            <div style="display:flex;gap:.5rem">
                <button type="button" id="applyOldestBtn" class="btn btn--sm btn--secondary">Apply Oldest First</button>
                <button type="button" id="clearAllBtn" class="btn btn--sm btn--secondary">Clear All</button>
            </div>
        </div>
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:.925rem">
                <colgroup>
                    <col style="width:120px">
                    <col style="width:110px">
                    <col style="width:110px">
                    <col style="width:110px">
                    <col>
                    <col style="width:140px">
                </colgroup>
                <thead>
                    <tr style="background:#f8f9fb;border-bottom:1px solid #d1d5db">
                        <th style="<?= rpmTh() ?>;text-align:center;width:40px">
                            <input type="checkbox" id="checkAll" title="Select all" style="accent-color:#0A3D91;width:16px;height:16px;cursor:pointer">
                        </th>
                        <th style="<?= rpmTh() ?>">Invoice #</th>
                        <th style="<?= rpmTh() ?>">Date</th>
                        <th style="<?= rpmTh() ?>">Due Date</th>
                        <th style="<?= rpmTh() ?>;text-align:right">Invoice Total</th>
                        <th style="<?= rpmTh() ?>;text-align:right">Balance Due</th>
                        <th style="<?= rpmTh() ?>;text-align:right">Amount to Apply</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $inv): ?>
                    <?php
                        $isPresel = $preselId && (int)$inv['id'] === (int)$preselId;
                        $balance  = (float)$inv['balance_due'];
                        $overdue  = $inv['status'] === 'overdue';
                    ?>
                    <tr style="border-bottom:1px solid #f3f4f6<?= $isPresel ? ';background:#fffbeb' : '' ?>">
                        <input type="hidden" name="invoice_id[]" value="<?= (int)$inv['id'] ?>">
                        <td style="padding:.55rem .75rem;text-align:center">
                            <input type="checkbox" class="row-check" data-balance="<?= number_format($balance, 2, '.', '') ?>"
                                <?= $isPresel ? 'checked' : '' ?>
                                style="accent-color:#0A3D91;width:16px;height:16px;cursor:pointer">
                        </td>
                        <td style="padding:.55rem .75rem;font-family:monospace;font-size:.875rem">
                            <a href="/invoices/<?= (int)$inv['id'] ?>" class="link" target="_blank"><?= e($inv['invoice_number']) ?></a>
                        </td>
                        <td style="padding:.55rem .75rem;color:#6b7280;font-size:.875rem"><?= date('M j, Y', strtotime($inv['invoice_date'])) ?></td>
                        <td style="padding:.55rem .75rem;font-size:.875rem;<?= $overdue ? 'color:#dc2626;font-weight:600' : 'color:#6b7280' ?>">
                            <?= date('M j, Y', strtotime($inv['due_date'])) ?>
                            <?= $overdue ? '<span style="font-size:.75rem"> (overdue)</span>' : '' ?>
                        </td>
                        <td style="padding:.55rem .75rem;text-align:right;font-family:monospace">$<?= number_format((float)$inv['total_amount'], 2) ?></td>
                        <td style="padding:.55rem .75rem;text-align:right;font-family:monospace;font-weight:600;color:#d97706">$<?= number_format($balance, 2) ?></td>
                        <td style="padding:.35rem .75rem;text-align:right">
                            <input type="number" name="apply[]"
                                class="apply-input"
                                data-balance="<?= number_format($balance, 2, '.', '') ?>"
                                value="<?= $isPresel ? number_format($balance, 2, '.', '') : '' ?>"
                                step="0.01" min="0" max="<?= number_format($balance, 2, '.', '') ?>"
                                placeholder="0.00"
                                style="width:120px;padding:.4rem .6rem;font-size:.95rem;font-family:monospace;font-weight:600;text-align:right;background:#fff;border:1px solid #d1d5db;border-radius:6px;box-sizing:border-box">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Running totals -->
    <table style="width:100%;border-collapse:collapse;border-bottom:2px solid #d1d5db;background:#f8f9fb;table-layout:fixed">
        <colgroup><col style="width:25%"><col style="width:25%"><col style="width:25%"><col style="width:25%"></colgroup>
        <tr>
            <td style="padding:.85rem 1.5rem;vertical-align:top;border-right:1px solid #d1d5db">
                <div style="<?= rpmLbl() ?>">Payment Amount</div>
                <div style="font-size:1.1rem;font-weight:700;color:#111" id="dispPayment">$0.00</div>
            </td>
            <td style="padding:.85rem 1.5rem;vertical-align:top;border-right:1px solid #d1d5db">
                <div style="<?= rpmLbl() ?>">Total Applied</div>
                <div style="font-size:1.1rem;font-weight:700;color:#111" id="dispApplied">$0.00</div>
            </td>
            <td style="padding:.85rem 1.5rem;vertical-align:top;border-right:1px solid #d1d5db">
                <div style="<?= rpmLbl() ?>">Difference</div>
                <div style="font-size:1.1rem;font-weight:700" id="dispDiff">$0.00</div>
            </td>
            <td style="padding:.85rem 1.5rem;vertical-align:top">
                <div style="<?= rpmLbl() ?>">Status</div>
                <div style="font-size:.95rem;font-weight:600" id="dispStatus">&mdash;</div>
            </td>
        </tr>
    </table>

    <!-- Memo + Actions -->
    <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #d1d5db">
        <div style="<?= rpmLbl() ?>">Memo / Note</div>
        <textarea name="memo" rows="2" placeholder="Optional note about this payment…" style="<?= rpmInp() ?>;resize:vertical"></textarea>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:.75rem;padding:1rem 1.5rem;background:#f8f9fb">
        <a href="/customers/<?= (int)$c['id'] ?>" class="btn btn--secondary">Cancel</a>
        <button type="submit" class="btn btn--primary" id="saveBtn">Save Payment</button>
    </div>

</div>
</form>

<?php endif; ?>

<?php
function rpmInp(): string {
    return 'width:100%;padding:.75rem .75rem;font-size:.95rem;font-family:inherit;line-height:1.45;background:#fff;color:#111;border:1px solid #d1d5db;border-radius:6px;box-sizing:border-box';
}
function rpmLbl(): string {
    return 'font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.4rem';
}
function rpmTh(): string {
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

        if(payment === 0 && applied === 0){
            dispDiff.style.color = '#6b7280';
            dispStatus.textContent = '—';
            dispStatus.style.color = '#6b7280';
        } else if(Math.abs(diff) < 0.01){
            dispDiff.style.color = '#16a34a';
            dispStatus.innerHTML = '&#10003; Balanced';
            dispStatus.style.color = '#16a34a';
        } else if(diff > 0){
            dispDiff.style.color = '#d97706';
            dispStatus.textContent = 'Unapplied: ' + fmt(diff);
            dispStatus.style.color = '#d97706';
        } else {
            dispDiff.style.color = '#dc2626';
            dispStatus.textContent = 'Over by: ' + fmt(Math.abs(diff));
            dispStatus.style.color = '#dc2626';
        }
    }

    /* Checkbox — fill or clear the apply input on the same row */
    document.querySelectorAll('.row-check').forEach(function(cb){
        cb.addEventListener('change', function(){
            var row   = this.closest('tr');
            var input = row.querySelector('.apply-input');
            input.value = this.checked ? parseFloat(this.dataset.balance).toFixed(2) : '';
            recalc();
        });
        /* Sync checkbox state with manual input changes */
    });

    /* Check-all header checkbox */
    document.getElementById('checkAll').addEventListener('change', function(){
        var checked = this.checked;
        document.querySelectorAll('.row-check').forEach(function(cb){
            cb.checked = checked;
            var row   = cb.closest('tr');
            var input = row.querySelector('.apply-input');
            input.value = checked ? parseFloat(cb.dataset.balance).toFixed(2) : '';
        });
        recalc();
    });

    /* When an apply input is manually cleared, uncheck its row checkbox */
    paymentInput.addEventListener('input', recalc);
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
            var bal = parseFloat(el.dataset.balance)||0;
            var apply = Math.min(bal, remaining);
            el.value = apply > 0 ? apply.toFixed(2) : '';
            remaining = Math.max(0, remaining - apply);
        });
        recalc();
    });

    /* Clear All */
    document.getElementById('clearAllBtn').addEventListener('click', function(){
        applyInputs.forEach(function(el){ el.value = ''; });
        recalc();
    });

    /* Method label */
    var refLabels = {
        check:'Check #', ach:'Transaction / Trace #', credit_card:'Auth / Transaction #',
        wire:'Wire Reference #', cash:'Receipt #', other:'Reference #'
    };
    var refPlaceholders = {
        check:'Check number', ach:'ACH transaction ID', credit_card:'Authorization code',
        wire:'Wire reference number', cash:'', other:'Reference number'
    };
    document.getElementById('methodSelect').addEventListener('change', function(){
        document.getElementById('refLabel').textContent = refLabels[this.value]||'Reference #';
        document.getElementById('refNumber').placeholder = refPlaceholders[this.value]||'';
    });

    recalc();

    /* Warn on submit if totals don't match */
    document.getElementById('payForm').addEventListener('submit', function(e){
        var payment = parseFloat(paymentInput.value)||0;
        var applied = 0;
        applyInputs.forEach(function(el){ applied += parseFloat(el.value)||0; });
        var diff = Math.abs(payment - applied);
        if(payment <= 0){
            e.preventDefault();
            alert('Please enter a payment amount.');
            return;
        }
        if(applied <= 0){
            e.preventDefault();
            alert('No amount has been applied to any invoice.');
            return;
        }
        if(diff >= 0.01){
            var msg = 'Payment amount ($' + payment.toFixed(2) + ') does not match total applied ($' + applied.toFixed(2) + ').\n\nDifference: $' + diff.toFixed(2) + '\n\nSave anyway?';
            if(!confirm(msg)) e.preventDefault();
        }
    });
})();
</script>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
