<?php ob_start();
$inv = $invoice;

$currentUser  = \App\Core\Auth::user();
$userRole     = $currentUser['role'] ?? 'user';
$isPaid       = in_array($inv['status'], ['paid', 'void']);
$isPrivileged = in_array($userRole, ['owner', 'bookkeeper']);
$canEditLines  = !$isPaid;
$canEditHeader = !$isPaid || $isPrivileged;
$isEditable    = $canEditHeader || $canEditLines;
$fromSO        = !empty($inv['sales_order_id']);

// Current tax rate — match stored tax_amount back to a rate for the selector
$currentTaxRateId  = 0;
$currentTaxRatePct = 0;
if ((float)$inv['subtotal'] > 0 && (float)$inv['tax_amount'] > 0) {
    // Try to match by rate value
    $effectiveRate = round((float)$inv['tax_amount'] / ((float)$inv['subtotal'] - (float)$inv['discount_amount']) * 100, 4);
    foreach ($tax_rates as $tr) {
        if (abs((float)$tr['rate'] * 100 - $effectiveRate) < 0.01) {
            $currentTaxRateId  = (int)$tr['id'];
            $currentTaxRatePct = (float)$tr['rate'] * 100;
            break;
        }
    }
}

$statusBadge = [
    'draft'   => 'badge--neutral',
    'pending' => 'badge--warning',
    'partial' => 'badge--warning',
    'paid'    => 'badge--success',
    'void'    => 'badge--neutral',
    'overdue' => 'badge--danger',
];
$statusLabel = [
    'draft'   => 'Draft',
    'pending' => 'Pending',
    'partial' => 'Partial',
    'paid'    => 'Paid',
    'void'    => 'Void',
    'overdue' => 'Overdue',
];

$shipAddr1 = $inv['ship_address_1'] ?: ($inv['bill_address_1'] ?? '');
$shipAddr2 = $inv['ship_address_2'] ?? '';
$shipCity  = $inv['ship_city']      ?: ($inv['bill_city']  ?? '');
$shipState = $inv['ship_state']     ?: ($inv['bill_state'] ?? '');
$shipZip   = $inv['ship_zip']       ?: ($inv['bill_zip']   ?? '');
$hasSeparateShip = !empty($inv['ship_address_1']);

function invLbl(): string {
    return 'font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.4rem';
}
function invTh(): string {
    return 'padding:.6rem .75rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;white-space:nowrap;text-align:left';
}
function invInp(): string {
    return 'width:100%;padding:.6rem .75rem;font-size:.95rem;font-family:inherit;background:#fff;border:1px solid #d1d5db;border-radius:6px;box-sizing:border-box;color:#111';
}
function invReadBox(): string {
    return 'padding:.6rem .85rem;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:6px;font-size:.95rem;min-height:2.4rem;color:#374151';
}
?>

<!-- Page header -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
    <div>
        <a href="/invoices" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Invoices
        </a>
        <div style="display:flex;align-items:center;gap:.75rem;margin-top:.25rem">
            <h1 class="page-title">Invoice #<?= e($inv['invoice_number']) ?></h1>
            <span class="badge <?= $statusBadge[$inv['status']] ?? 'badge--neutral' ?>"><?= ucfirst($inv['status']) ?></span>
        </div>
    </div>
    <div style="display:flex;gap:.75rem;align-items:center">
        <?php if ($isPaid && $isPrivileged): ?>
            <span class="text-muted text-sm">Paid — line items locked</span>
        <?php endif; ?>
        <?php if ((float)$inv['balance_due'] > 0): ?>
            <a href="/customers/<?= (int)$inv['customer_id'] ?>/payment?invoice=<?= (int)$inv['id'] ?>" class="btn btn--primary">Receive Payment</a>
        <?php endif; ?>
        <a href="/invoices/<?= (int)$inv['id'] ?>/print" target="_blank" class="btn btn--secondary">Print / PDF</a>
        <a href="/invoices/<?= (int)$inv['id'] ?>/packing-slip" target="_blank" class="btn btn--secondary">Packing Slip</a>
        <button class="btn btn--secondary" onclick="document.getElementById('emailModal').style.display='flex'">Email</button>
    </div>
</div>

<!-- Email Modal -->
<?php $emailUser = \App\Core\Auth::user(); ?>
<div id="emailModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:10px;padding:28px 32px;width:460px;max-width:95vw;box-shadow:0 8px 40px rgba(0,0,0,.25)">
        <h3 style="margin:0 0 18px;color:#222b59;font-size:1.1rem">Email Invoice #<?= e($inv['invoice_number']) ?></h3>
        <form method="POST" action="/invoices/<?= (int)$inv['id'] ?>/email">
            <div style="margin-bottom:14px">
                <label style="display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:4px">From</label>
                <input type="email" name="email_from" required value="<?= e($emailUser['email'] ?? '') ?>"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:.95rem;box-sizing:border-box">
            </div>
            <div style="margin-bottom:14px">
                <label style="display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:4px">To</label>
                <input type="email" name="email_to" required value="<?= e($inv['email'] ?? '') ?>"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:.95rem;box-sizing:border-box">
            </div>
            <div style="margin-bottom:18px">
                <label style="display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:4px">Note (optional)</label>
                <textarea name="email_note" rows="3"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:.95rem;box-sizing:border-box;resize:vertical"></textarea>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end">
                <button type="button" onclick="document.getElementById('emailModal').style.display='none'"
                    style="padding:8px 18px;border:1px solid #d1d5db;border-radius:6px;background:#fff;cursor:pointer">Cancel</button>
                <button type="submit" style="padding:8px 20px;background:#222b59;color:#fff;border:none;border-radius:6px;font-weight:600;cursor:pointer">Send Email</button>
            </div>
        </form>
    </div>
</div>

<!-- KPI row -->
<div class="kpi-row">
    <div class="kpi-card">
        <div class="kpi-card__label">Invoice Total</div>
        <div class="kpi-card__value" id="kpiTotal">$<?= number_format((float)$inv['total_amount'], 2) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Amount Paid</div>
        <div class="kpi-card__value text-success">$<?= number_format((float)$inv['amount_paid'], 2) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Balance Due</div>
        <div class="kpi-card__value <?= (float)$inv['balance_due'] > 0 ? 'text-warning' : 'text-success' ?>">
            $<?= number_format((float)$inv['balance_due'], 2) ?>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Due Date</div>
        <div class="kpi-card__value kpi-card__value--sm <?= $inv['status'] === 'overdue' ? 'text-danger' : '' ?>">
            <?= date('M j, Y', strtotime($inv['due_date'])) ?>
            <?php if (($inv['aging_days'] ?? 0) > 0 && $inv['status'] === 'overdue'): ?>
                <span class="kpi-card__sub text-danger"><?= (int)$inv['aging_days'] ?> days past due</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($isEditable): ?>
<form method="POST" action="/invoices/<?= (int)$inv['id'] ?>/edit" id="invForm">
<?php endif; ?>

<!-- ═══ INVOICE DOCUMENT ═══ -->
<div style="background:#fff;border:1px solid #d1d5db;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.08);font-family:inherit">

    <!-- Customer bar -->
    <div style="padding:.85rem 1.5rem;background:#f8f9fb;border-bottom:1px solid #d1d5db">
        <div style="display:flex;align-items:center;gap:1rem">
            <span style="<?= invLbl() ?>">Customer / Job</span>
            <a href="/customers/<?= (int)$inv['customer_id'] ?>" style="font-size:1rem;font-weight:700;color:#0A3D91"><?= e($inv['company_name']) ?></a>
            <?php if ($fromSO): ?>
                <span style="<?= invLbl() ?>;margin-left:1rem">From Sales Order</span>
                <a href="/sales-orders/<?= (int)$inv['sales_order_id'] ?>" style="font-size:.9rem;font-weight:600;color:#0A3D91">SO #<?= e($inv['so_number'] ?? $inv['sales_order_id']) ?></a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Header: Invoice title | Dates | Bill To | Ship To -->
    <table style="width:100%;border-collapse:collapse;border-bottom:2px solid #d1d5db;table-layout:fixed">
        <colgroup>
            <col style="width:200px">
            <col style="width:200px">
            <col>
            <col>
        </colgroup>
        <tr>
            <!-- Invoice title + number -->
            <td style="padding:1.25rem 1.5rem;vertical-align:top;border-right:1px solid #d1d5db">
                <div style="font-size:1.75rem;font-weight:700;color:#111;letter-spacing:-.02em;margin-bottom:1.25rem">Invoice</div>
                <div style="<?= invLbl() ?>">Invoice No.</div>
                <div style="padding:.6rem .85rem;background:#f3f4f6;border:1px solid #d1d5db;border-radius:6px;font-size:1rem;font-weight:700;color:#374151;margin-bottom:.85rem"><?= e($inv['invoice_number']) ?></div>
                <div style="<?= invLbl() ?>">Status</div>
                <div style="padding:.5rem .85rem;border-radius:6px;font-size:.875rem;font-weight:600;display:inline-block;
                    <?php if ($inv['status'] === 'paid') echo 'background:#dcfce7;color:#166534';
                          elseif ($inv['status'] === 'overdue') echo 'background:#fee2e2;color:#991b1b';
                          elseif ($inv['status'] === 'partial') echo 'background:#fef9c3;color:#854d0e';
                          else echo 'background:#e0f2fe;color:#0369a1'; ?>">
                    <?= ucfirst($inv['status']) ?>
                </div>
            </td>

            <!-- Dates + Terms -->
            <td style="padding:1rem 1.25rem;vertical-align:top;border-right:1px solid #d1d5db">
                <div style="<?= invLbl() ?>">Invoice Date</div>
                <?php if ($canEditHeader): ?>
                    <input type="date" name="invoice_date" id="invDate" value="<?= e($inv['invoice_date']) ?>" required style="<?= invInp() ?>;margin-bottom:.75rem">
                    <div style="<?= invLbl() ?>">Due Date</div>
                    <input type="date" name="due_date" id="dueDate" value="<?= e($inv['due_date']) ?>" required style="<?= invInp() ?>;margin-bottom:.75rem">
                    <div style="<?= invLbl() ?>">Terms</div>
                    <select name="payment_term_id" id="termSelect" style="<?= invInp() ?>">
                        <option value="">— None —</option>
                        <?php foreach ($payment_terms as $pt): ?>
                            <option value="<?= (int)$pt['id'] ?>" <?= (int)($inv['payment_term_id'] ?? 0) === (int)$pt['id'] ? 'selected' : '' ?>><?= e($pt['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <div style="<?= invReadBox() ?>;margin-bottom:.75rem"><?= date('M j, Y', strtotime($inv['invoice_date'])) ?></div>
                    <div style="<?= invLbl() ?>">Due Date</div>
                    <div style="<?= invReadBox() ?>;margin-bottom:.75rem;<?= $inv['status'] === 'overdue' ? 'color:#dc2626;font-weight:600' : '' ?>"><?= date('M j, Y', strtotime($inv['due_date'])) ?></div>
                    <?php if ($inv['payment_term_name'] ?? ''): ?>
                        <div style="<?= invLbl() ?>">Terms</div>
                        <div style="<?= invReadBox() ?>"><?= e($inv['payment_term_name']) ?></div>
                    <?php endif; ?>
                <?php endif; ?>
            </td>

            <!-- Bill To -->
            <td style="padding:1rem 1.25rem;vertical-align:top;border-right:1px solid #d1d5db">
                <div style="<?= invLbl() ?>">Bill To</div>
                <div style="padding:.75rem 1rem;min-height:120px;background:#f8f9fb;border:1px solid #d1d5db;border-radius:6px;font-size:.9rem;line-height:1.8;color:#374151">
                    <strong><?= e($inv['company_name']) ?></strong>
                    <?php if ($inv['bill_address_1'] ?? ''): ?><br><?= e($inv['bill_address_1']) ?><?php endif; ?>
                    <?php if ($inv['bill_address_2'] ?? ''): ?><br><?= e($inv['bill_address_2']) ?><?php endif; ?>
                    <?php if ($inv['bill_city'] ?? ''): ?>
                        <br><?= e($inv['bill_city']) ?><?= ($inv['bill_state'] ?? '') ? ', ' . e($inv['bill_state']) : '' ?> <?= e($inv['bill_zip'] ?? '') ?>
                    <?php endif; ?>
                    <?php if ($inv['phone'] ?? ''): ?><br><span style="color:#9ca3af"><?= e($inv['phone']) ?></span><?php endif; ?>
                </div>
            </td>

            <!-- Ship To -->
            <td style="padding:1rem 1.25rem;vertical-align:top">
                <?php if ($canEditHeader): ?>
                    <div style="<?= invLbl() ?>;display:flex;align-items:center;gap:.6rem">
                        Ship To
                        <label style="font-size:.8rem;font-weight:600;text-transform:none;letter-spacing:0;display:inline-flex;align-items:center;gap:.3rem;cursor:pointer;color:#6b7280">
                            <input type="checkbox" id="shipSameCb" name="ship_same_as_billing" value="1" <?= !$hasSeparateShip ? 'checked' : '' ?>> Same as billing
                        </label>
                    </div>
                    <div id="shipSameBox" style="padding:.75rem 1rem;min-height:110px;background:#f8f9fb;border:1px solid #d1d5db;border-radius:6px;font-size:.9rem;color:#9ca3af<?= $hasSeparateShip ? ';display:none' : '' ?>">
                        Same as billing address
                    </div>
                    <div id="shipEditBox" style="<?= !$hasSeparateShip ? 'display:none' : '' ?>">
                        <div style="display:flex;flex-direction:column;gap:.4rem;margin-top:.4rem">
                            <input type="text" name="ship_address_1" placeholder="Address" value="<?= e($inv['ship_address_1'] ?? '') ?>" style="<?= invInp() ?>">
                            <input type="text" name="ship_address_2" placeholder="Address 2" value="<?= e($inv['ship_address_2'] ?? '') ?>" style="<?= invInp() ?>">
                            <table width="100%" cellpadding="0" cellspacing="0"><tr>
                                <td style="padding-right:.3rem"><input type="text" name="ship_city" placeholder="City" value="<?= e($inv['ship_city'] ?? '') ?>" style="<?= invInp() ?>"></td>
                                <td style="width:48px;padding-right:.3rem"><input type="text" name="ship_state" placeholder="ST" maxlength="2" value="<?= e($inv['ship_state'] ?? '') ?>" style="<?= invInp() ?>"></td>
                                <td style="width:85px"><input type="text" name="ship_zip" placeholder="Zip" value="<?= e($inv['ship_zip'] ?? '') ?>" style="<?= invInp() ?>"></td>
                            </tr></table>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="<?= invLbl() ?>">Ship To</div>
                    <div style="padding:.75rem 1rem;min-height:120px;background:#f8f9fb;border:1px solid #d1d5db;border-radius:6px;font-size:.9rem;line-height:1.8;color:#374151">
                        <strong><?= e($inv['company_name']) ?></strong>
                        <?php if ($shipAddr1): ?><br><?= e($shipAddr1) ?><?php endif; ?>
                        <?php if ($shipAddr2): ?><br><?= e($shipAddr2) ?><?php endif; ?>
                        <?php if ($shipCity): ?><br><?= e($shipCity) ?><?= $shipState ? ', ' . e($shipState) : '' ?> <?= e($shipZip) ?><?php endif; ?>
                        <?php if (!$hasSeparateShip): ?><br><span style="color:#9ca3af;font-size:.8rem">Same as billing</span><?php endif; ?>
                    </div>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <!-- Meta row: PO · Ship Via · Rep · Sales Order -->
    <table style="width:100%;border-collapse:collapse;border-bottom:2px solid #d1d5db;background:#f8f9fb;table-layout:fixed">
        <tr>
            <td style="padding:.85rem 1.25rem;border-right:1px solid #d1d5db;vertical-align:top;width:25%">
                <div style="<?= invLbl() ?>">P.O. No.</div>
                <?php if ($canEditHeader): ?>
                    <input type="text" name="po_number" maxlength="100" value="<?= e($inv['po_number'] ?? '') ?>" style="<?= invInp() ?>">
                <?php else: ?>
                    <div style="<?= invReadBox() ?>"><?= e($inv['po_number'] ?? '—') ?></div>
                <?php endif; ?>
            </td>
            <td style="padding:.85rem 1.25rem;border-right:1px solid #d1d5db;vertical-align:top;width:25%">
                <div style="<?= invLbl() ?>">Ship Via</div>
                <?php if ($canEditHeader): ?>
                    <select name="ship_via" style="<?= invInp() ?>">
                        <option value="">— None —</option>
                        <?php foreach ($ship_via_options as $sv): ?>
                            <option value="<?= e($sv['name']) ?>" <?= ($inv['ship_via'] ?? '') === $sv['name'] ? 'selected' : '' ?>><?= e($sv['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <div style="<?= invReadBox() ?>"><?= e($inv['ship_via'] ?? '—') ?></div>
                <?php endif; ?>
                <div style="<?= invLbl() ?>;margin-top:.6rem">Tracking #</div>
                <?php if ($canEditHeader): ?>
                    <input type="text" name="tracking_number" maxlength="100" value="<?= e($inv['tracking_number'] ?? '') ?>" style="<?= invInp() ?>">
                <?php else: ?>
                    <div style="<?= invReadBox() ?>;font-family:monospace"><?= e($inv['tracking_number'] ?? '—') ?></div>
                <?php endif; ?>
            </td>
            <td style="padding:.85rem 1.25rem;border-right:1px solid #d1d5db;vertical-align:top;width:25%">
                <div style="<?= invLbl() ?>">Rep</div>
                <?php if ($canEditHeader): ?>
                    <select name="rep_id" style="<?= invInp() ?>">
                        <option value="">— None —</option>
                        <?php foreach ($reps as $rep): ?>
                            <option value="<?= (int)$rep['id'] ?>" <?= (int)($inv['rep_id'] ?? 0) === (int)$rep['id'] ? 'selected' : '' ?>>
                                <?= e($rep['last_name'] . ', ' . $rep['first_name']) ?><?= $rep['rep_code'] ? ' (' . e($rep['rep_code']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <div style="<?= invReadBox() ?>"><?= ($inv['rep_first'] ?? '') ? e($inv['rep_first'] . ' ' . $inv['rep_last']) : '—' ?></div>
                <?php endif; ?>
            </td>
            <td style="padding:.85rem 1.25rem;vertical-align:top;width:25%">
                <div style="<?= invLbl() ?>">Sales Order</div>
                <div style="<?= invReadBox() ?>">
                    <?php if ($fromSO): ?>
                        <a href="/sales-orders/<?= (int)$inv['sales_order_id'] ?>" style="color:#0A3D91">SO #<?= e($inv['so_number'] ?? $inv['sales_order_id']) ?></a>
                    <?php else: ?>—<?php endif; ?>
                </div>

                <?php // Who keyed the order in. Not the rep — that's the Rep field, which is who earns it. ?>
                <div style="<?= invLbl() ?>;margin-top:.6rem">Processed By</div>
                <div style="<?= invReadBox() ?>" title="Who entered this order, from QuickBooks. Not the same as the rep credited with the sale.">
                    <?= ($inv['processed_by'] ?? '') !== '' ? e($inv['processed_by']) : '—' ?>
                </div>
            </td>
        </tr>
    </table>

    <!-- Line Items -->
    <div style="border-bottom:2px solid #d1d5db">
        <?php if ($canEditLines): ?>
            <div style="display:flex;justify-content:flex-end;padding:.6rem 1rem;background:#f8f9fb;border-bottom:1px solid #d1d5db">
                <button type="button" class="btn btn--sm btn--secondary" id="addLineBtn">+ Add Line</button>
            </div>
        <?php endif; ?>
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:.925rem">
                <colgroup>
                    <col style="width:155px">
                    <col>
                    <col style="width:80px">
                    <col style="width:60px">
                    <col style="width:115px">
                    <?php if ($canEditLines): ?><col style="width:72px"><?php endif; ?>
                    <col style="width:50px">
                    <col style="width:120px">
                    <?php if ($canEditLines): ?><col style="width:34px"><?php endif; ?>
                </colgroup>
                <thead>
                    <tr style="background:#f8f9fb;border-bottom:1px solid #d1d5db">
                        <th style="<?= invTh() ?>">Item</th>
                        <th style="<?= invTh() ?>">Description</th>
                        <th style="<?= invTh() ?>;text-align:right">Qty</th>
                        <th style="<?= invTh() ?>">U/M</th>
                        <th style="<?= invTh() ?>;text-align:right">Price Each</th>
                        <?php if ($canEditLines): ?><th style="<?= invTh() ?>;text-align:right">Disc %</th><?php endif; ?>
                        <th style="<?= invTh() ?>;text-align:center">Tax</th>
                        <th style="<?= invTh() ?>;text-align:right">Amount</th>
                        <?php if ($canEditLines): ?><th style="<?= invTh() ?>"></th><?php endif; ?>
                    </tr>
                </thead>
                <tbody id="lineBody">
                    <?php if (!$canEditLines): ?>
                        <?php if (empty($line_items)): ?>
                            <tr><td colspan="6" class="table__empty">No line items on record.</td></tr>
                        <?php else: ?>
                            <?php foreach ($line_items as $li): ?>
                                <tr style="border-bottom:1px solid #f3f4f6">
                                    <td style="padding:.6rem .75rem;font-family:monospace;font-size:.875rem">
                                        <?php if ($li['product_id']): ?>
                                            <a href="/products/<?= (int)$li['product_id'] ?>" style="color:#0A3D91;font-weight:700"><?= e($li['sku'] ?? $li['quickbooks_item']) ?></a>
                                        <?php else: ?>
                                            <span style="color:#6b7280"><?= e($li['quickbooks_item'] ?? '—') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding:.6rem .75rem;font-size:.875rem;color:#374151"><?= e($li['description'] ?? $li['quickbooks_item'] ?? '') ?></td>
                                    <td style="padding:.6rem .75rem;text-align:right"><?= (int)$li['qty'] ?></td>
                                    <td style="padding:.6rem .75rem;font-size:.8rem;color:#6b7280"><?= e($li['uom_code'] ?? '') ?></td>
                                    <td style="padding:.6rem .75rem;text-align:right">$<?= number_format((float)$li['unit_price'], 2) ?></td>
                                    <td style="padding:.6rem .75rem;text-align:center;font-size:.8rem"><?= $li['is_taxable'] ? '✓' : '' ?></td>
                                    <td style="padding:.6rem .75rem;text-align:right;font-family:monospace;font-weight:600">$<?= number_format((float)$li['line_total'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                    <!-- Editable rows injected by JS when $canEditLines -->
                </tbody>
                <tfoot>
                    <?php if ((float)$inv['discount_amount'] > 0 && !$canEditLines): ?>
                        <tr style="border-top:1px solid #e5e7eb">
                            <td colspan="<?= $canEditLines ? '8' : '6' ?>" style="padding:.5rem .75rem;text-align:right;color:#6b7280;font-size:.9rem">Discount</td>
                            <td style="padding:.5rem .75rem;text-align:right;font-family:monospace;color:#ef4444">−$<?= number_format((float)$inv['discount_amount'], 2) ?></td>
                            <?php if ($canEditLines): ?><td></td><?php endif; ?>
                        </tr>
                    <?php endif; ?>

                    <?php if ($canEditLines): ?>
                        <!-- JS-managed totals -->
                        <tr style="border-top:1px solid #e5e7eb">
                            <td colspan="7" style="padding:.6rem .75rem;text-align:right;color:#6b7280;font-size:.9rem">Subtotal</td>
                            <td style="padding:.6rem .75rem;text-align:right;font-family:monospace;font-weight:600" id="fSubtotal">$0.00</td>
                            <td></td>
                        </tr>
                        <tr id="fDiscRow" style="display:none">
                            <td colspan="7" style="padding:.4rem .75rem;text-align:right;color:#6b7280;font-size:.9rem">Discount</td>
                            <td style="padding:.4rem .75rem;text-align:right;font-family:monospace;color:#ef4444" id="fDiscount"></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="7" style="padding:.7rem .75rem;text-align:right">
                                <label style="display:inline-flex;align-items:center;gap:.6rem;font-weight:600;color:#374151">
                                    Tax
                                    <select name="tax_rate_id" id="taxRateSelect" style="padding:.4rem .6rem;font-size:.875rem;border:1px solid #d1d5db;border-radius:6px;background:#fff">
                                        <option value="" data-rate="0">— None —</option>
                                        <?php foreach ($tax_rates as $tr): ?>
                                            <option value="<?= (int)$tr['id'] ?>" data-rate="<?= (float)$tr['rate'] * 100 ?>"
                                                <?= (int)$tr['id'] === $currentTaxRateId ? 'selected' : '' ?>><?= e($tr['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <input type="hidden" name="tax_rate_pct" id="taxRatePct" value="<?= $currentTaxRatePct ?>">
                            </td>
                            <td style="padding:.7rem .75rem;text-align:right;font-family:monospace;font-weight:600" id="fTax">$0.00</td>
                            <td></td>
                        </tr>
                        <tr style="border-top:2px solid #d1d5db">
                            <td colspan="7" style="padding:.85rem .75rem;text-align:right;font-weight:700;font-size:1.05rem">Total</td>
                            <td style="padding:.85rem .75rem;text-align:right;font-family:monospace;font-weight:700;font-size:1.1rem" id="fTotal">$0.00</td>
                            <td></td>
                        </tr>
                    <?php else: ?>
                        <?php if ((float)$inv['tax_amount'] > 0): ?>
                            <tr>
                                <td colspan="6" style="padding:.5rem .75rem;text-align:right;color:#6b7280;font-size:.9rem">Tax</td>
                                <td style="padding:.5rem .75rem;text-align:right;font-family:monospace">$<?= number_format((float)$inv['tax_amount'], 2) ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr style="border-top:2px solid #d1d5db">
                            <td colspan="6" style="padding:.85rem .75rem;text-align:right;font-weight:700;font-size:1.05rem">Total</td>
                            <td style="padding:.85rem .75rem;text-align:right;font-family:monospace;font-weight:700;font-size:1.1rem">$<?= number_format((float)$inv['total_amount'], 2) ?></td>
                        </tr>
                        <?php if ((float)$inv['amount_paid'] > 0): ?>
                            <tr>
                                <td colspan="6" style="padding:.5rem .75rem;text-align:right;color:#6b7280;font-size:.9rem">Paid</td>
                                <td style="padding:.5rem .75rem;text-align:right;font-family:monospace;color:#16a34a">−$<?= number_format((float)$inv['amount_paid'], 2) ?></td>
                            </tr>
                            <tr style="border-top:2px solid #d1d5db;background:#f8f9fb">
                                <td colspan="6" style="padding:.85rem .75rem;text-align:right;font-weight:700;font-size:1.05rem">Balance Due</td>
                                <td style="padding:.85rem .75rem;text-align:right;font-family:monospace;font-weight:700;font-size:1.1rem;<?= (float)$inv['balance_due'] > 0 ? 'color:#d97706' : 'color:#16a34a' ?>">
                                    $<?= number_format((float)$inv['balance_due'], 2) ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endif; ?>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Footer: Memo / Internal Notes -->
    <table width="100%" cellpadding="0" cellspacing="0" style="border-bottom:<?= !empty($payments) ? '2px solid #d1d5db' : 'none' ?>">
        <tr valign="top">
            <td style="padding:1.25rem 1.5rem;width:50%;border-right:1px solid #e5e7eb">
                <div style="<?= invLbl() ?>">Memo</div>
                <?php if ($canEditHeader): ?>
                    <textarea name="memo" rows="3" style="<?= invInp() ?>;resize:vertical"><?= e($inv['memo'] ?? '') ?></textarea>
                <?php else: ?>
                    <div style="font-size:.9rem;color:#374151;line-height:1.6;min-height:2rem"><?= $inv['memo'] ? nl2br(e($inv['memo'])) : '<span style="color:#9ca3af">—</span>' ?></div>
                <?php endif; ?>
            </td>
            <td style="padding:1.25rem 1.5rem;width:50%">
                <div style="<?= invLbl() ?>">Internal Notes</div>
                <?php if ($canEditHeader): ?>
                    <textarea name="internal_notes" rows="3" style="<?= invInp() ?>;resize:vertical"><?= e($inv['internal_notes'] ?? '') ?></textarea>
                <?php else: ?>
                    <div style="font-size:.9rem;color:#6b7280;line-height:1.6;min-height:2rem"><?= $inv['internal_notes'] ? nl2br(e($inv['internal_notes'])) : '<span style="color:#9ca3af">—</span>' ?></div>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <?php if ($isEditable): ?>
    <!-- Save bar -->
    <div style="display:flex;justify-content:flex-end;gap:.75rem;padding:1rem 1.5rem;background:#f8f9fb;border-top:1px solid #d1d5db">
        <a href="/invoices" class="btn btn--secondary">Cancel</a>
        <button type="submit" class="btn btn--primary">Save Invoice</button>
    </div>
    <?php endif; ?>

    <?php if (!empty($payments)): ?>
    <!-- Payment History -->
    <div style="padding:1.25rem 1.5rem;border-top:2px solid #d1d5db">
        <div style="<?= invLbl() ?>;margin-bottom:.75rem">Payment History</div>
        <table style="width:100%;border-collapse:collapse;font-size:.9rem">
            <thead>
                <tr style="background:#f8f9fb;border-bottom:1px solid #d1d5db">
                    <th style="<?= invTh() ?>">Date</th>
                    <th style="<?= invTh() ?>">Method</th>
                    <th style="<?= invTh() ?>">Reference #</th>
                    <th style="<?= invTh() ?>">Memo</th>
                    <th style="<?= invTh() ?>;text-align:right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $pmt): ?>
                    <tr style="border-bottom:1px solid #f3f4f6">
                        <td style="padding:.5rem .75rem"><?= date('M j, Y', strtotime($pmt['payment_date'])) ?></td>
                        <td style="padding:.5rem .75rem"><?= e(ucwords(str_replace('_', ' ', $pmt['payment_method']))) ?></td>
                        <td style="padding:.5rem .75rem;font-family:monospace;font-size:.85rem"><?= e($pmt['reference_number'] ?? '—') ?></td>
                        <td style="padding:.5rem .75rem;color:#6b7280"><?= e($pmt['memo'] ?? '—') ?></td>
                        <td style="padding:.5rem .75rem;text-align:right;font-family:monospace;font-weight:600;color:#16a34a">$<?= number_format((float)$pmt['amount_applied'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div><!-- /invoice document -->

<?php if ($isEditable): ?>
</form>
<?php endif; ?>

<?php if ($canEditLines): ?>
<script>
(function(){
    var LINES = <?= json_encode(array_values($line_items), JSON_HEX_TAG) ?>;

    function fmt(n){return '$'+(parseFloat(n)||0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,',');}
    function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');}

    const INP  = 'width:100%;padding:.35rem .5rem;font-size:.9rem;font-family:inherit;background:#fff;border:1px solid #d1d5db;border-radius:5px;box-sizing:border-box';
    const INP_R= INP+';text-align:right';

    function addLine(d){
        d=d||{};
        const tr=document.createElement('tr');
        tr.className='inv-line';
        tr.style.borderBottom='1px solid #e5e7eb';
        tr.innerHTML=`
            <td style="padding:.3rem .4rem;vertical-align:middle;position:relative">
                <input type="hidden" name="line_product_id[]" class="f-pid" value="${esc(d.product_id||'')}">
                <input type="text" name="line_item[]" value="${esc(d.sku||d.quickbooks_item||'')}" class="f-code" placeholder="Item" autocomplete="off" style="${INP}">
                <div class="f-drop" style="display:none;position:absolute;top:100%;left:0;min-width:440px;z-index:9999;margin-top:1px;background:#fff;border:1px solid #c8c8c8;border-radius:6px;box-shadow:0 8px 28px rgba(0,0,0,.18);max-height:300px;overflow-y:auto"></div>
            </td>
            <td style="padding:.3rem .4rem;vertical-align:middle"><input type="text" name="line_desc[]" value="${esc(d.description||d.name||d.quickbooks_item||'')}" class="f-desc" placeholder="Description" style="${INP}"></td>
            <td style="padding:.3rem .4rem;vertical-align:middle"><input type="number" name="line_qty[]"      value="${d.qty||1}"            class="f-qty f-calc"   step="1" min="0" style="${INP_R}"></td>
            <td style="padding:.3rem .4rem;vertical-align:middle"><input type="text"   name="line_uom[]"      value="${esc(d.uom_code||d.uom||'')}" class="f-uom" maxlength="10" style="${INP}"></td>
            <td style="padding:.3rem .4rem;vertical-align:middle"><input type="number" name="line_price[]"    value="${d.unit_price||d.price||''}" class="f-price f-calc" step="0.01" min="0" placeholder="0.00" style="${INP_R}"></td>
            <td style="padding:.3rem .4rem;vertical-align:middle"><input type="number" name="line_discount[]" value="${d.discount_pct||d.disc||0}" class="f-disc f-calc" step="any" min="0" max="100" style="${INP_R}"></td>
            <td style="padding:.3rem .4rem;vertical-align:middle;text-align:center"><input type="checkbox" name="line_taxable[]" value="1" class="f-tax f-calc" ${d.is_taxable?'checked':''}></td>
            <td style="padding:.3rem .75rem;vertical-align:middle;text-align:right;font-family:monospace;font-weight:600" class="f-total">$0.00</td>
            <td style="padding:.3rem .4rem;vertical-align:middle"><button type="button" class="inv-del-btn" title="Remove" style="background:none;border:none;font-size:1.3rem;cursor:pointer;color:#9ca3af;line-height:1;padding:0 .3rem;border-radius:4px">&times;</button></td>`;
        document.getElementById('lineBody').appendChild(tr);
        recalcRow(tr); bindRow(tr);
    }

    function recalcRow(tr){
        const q=parseFloat(tr.querySelector('.f-qty').value)||0;
        const p=parseFloat(tr.querySelector('.f-price').value)||0;
        const d=parseFloat(tr.querySelector('.f-disc').value)||0;
        tr.querySelector('.f-total').textContent=fmt(q*p*(1-d/100));
    }
    function recalcAll(){
        let sub=0,disc=0,taxable=0;
        document.querySelectorAll('.inv-line').forEach(tr=>{
            const q=parseFloat(tr.querySelector('.f-qty').value)||0;
            const p=parseFloat(tr.querySelector('.f-price').value)||0;
            const d=parseFloat(tr.querySelector('.f-disc').value)||0;
            const gross=q*p,da=gross*d/100,net=gross-da;
            sub+=gross;disc+=da;
            if(tr.querySelector('.f-tax').checked) taxable+=net;
        });
        const rate=parseFloat(document.getElementById('taxRatePct').value)||0;
        const taxAmt=taxable*rate/100;
        document.getElementById('fSubtotal').textContent=fmt(sub);
        const dr=document.getElementById('fDiscRow');
        dr.style.display=disc>0?'':'none';
        if(disc>0) document.getElementById('fDiscount').textContent='−'+fmt(disc);
        document.getElementById('fTax').textContent=fmt(taxAmt);
        document.getElementById('fTotal').textContent=fmt(sub-disc+taxAmt);
    }
    function bindRow(tr){
        tr.querySelectorAll('.f-calc').forEach(el=>{
            el.addEventListener('input',()=>{recalcRow(tr);recalcAll();});
            el.addEventListener('change',()=>{recalcRow(tr);recalcAll();});
        });
        tr.querySelector('.inv-del-btn').addEventListener('click',()=>{tr.remove();recalcAll();});
        const codeEl=tr.querySelector('.f-code'),dropEl=tr.querySelector('.f-drop');
        let timer;
        codeEl.addEventListener('input',function(){
            clearTimeout(timer);
            const q=this.value.trim();
            if(!q){dropEl.style.display='none';return;}
            timer=setTimeout(()=>{
                fetch('/products/autocomplete?q='+encodeURIComponent(q))
                    .then(r=>r.json()).then(items=>showDrop(items,dropEl,tr)).catch(()=>{});
            },220);
        });
        codeEl.addEventListener('blur',()=>setTimeout(()=>{dropEl.style.display='none';},180));
    }
    function showDrop(items,dropEl,tr){
        dropEl.innerHTML='';
        if(!items||!items.length){dropEl.style.display='none';return;}
        items.forEach(p=>{
            const d=document.createElement('div');
            d.style.cssText='display:flex;align-items:baseline;gap:.75rem;padding:.6rem 1rem;cursor:pointer;border-bottom:1px solid #eee;font-size:.9rem;color:#111';
            d.innerHTML=`<span style="font-weight:700;font-family:monospace;color:#0A3D91;min-width:110px;flex-shrink:0">${esc(p.sku||p.quickbooks_item)}</span>`+
                `<span style="flex:1;color:#222">${esc(p.name||'')}</span>`+
                (p.price?`<span style="margin-left:auto;color:#6b7280;font-size:.85rem">$${parseFloat(p.price).toFixed(2)}</span>`:'');
            d.addEventListener('mouseover',()=>d.style.background='#f0f4ff');
            d.addEventListener('mouseout', ()=>d.style.background='');
            d.addEventListener('mousedown',e=>{
                e.preventDefault();
                tr.querySelector('.f-code').value=p.sku||p.quickbooks_item;
                tr.querySelector('.f-pid').value=p.id;
                tr.querySelector('.f-desc').value=p.name||'';
                if(p.price) tr.querySelector('.f-price').value=p.price;
                dropEl.style.display='none';
                recalcRow(tr);recalcAll();
                tr.querySelector('.f-qty').focus();
            });
            dropEl.appendChild(d);
        });
        dropEl.style.display='block';
    }

    document.getElementById('taxRateSelect').addEventListener('change',function(){
        document.getElementById('taxRatePct').value=this.options[this.selectedIndex].dataset.rate||0;
        recalcAll();
    });
    document.getElementById('addLineBtn').addEventListener('click',()=>addLine());

    // Same-as-billing ship toggle
    const shipCb=document.getElementById('shipSameCb');
    if(shipCb){
        shipCb.addEventListener('change',function(){
            document.getElementById('shipEditBox').style.display=this.checked?'none':'block';
            document.getElementById('shipSameBox').style.display=this.checked?'block':'none';
        });
    }

    // Terms → auto-update due date
    const termSel=document.getElementById('termSelect');
    const invDateEl=document.getElementById('invDate');
    const dueDateEl=document.getElementById('dueDate');
    if(termSel && invDateEl && dueDateEl){
        termSel.addEventListener('change',function(){
            const m=(this.options[this.selectedIndex]?.text||'').match(/(\d+)/);
            if(!m||!invDateEl.value) return;
            const base=new Date(invDateEl.value+'T00:00:00');
            base.setDate(base.getDate()+parseInt(m[1]));
            dueDateEl.value=base.toISOString().slice(0,10);
        });
    }

    // Load existing lines
    LINES.forEach(l=>addLine(l));
    recalcAll();
})();
</script>
<?php elseif ($canEditHeader): ?>
<script>
// Ship-to toggle for header-only edit (paid + privileged)
(function(){
    const cb=document.getElementById('shipSameCb');
    if(cb){
        cb.addEventListener('change',function(){
            document.getElementById('shipEditBox').style.display=this.checked?'none':'block';
            document.getElementById('shipSameBox').style.display=this.checked?'block':'none';
        });
    }
    const termSel=document.getElementById('termSelect');
    const invDateEl=document.getElementById('invDate');
    const dueDateEl=document.getElementById('dueDate');
    if(termSel && invDateEl && dueDateEl){
        termSel.addEventListener('change',function(){
            const m=(this.options[this.selectedIndex]?.text||'').match(/(\d+)/);
            if(!m||!invDateEl.value) return;
            const base=new Date(invDateEl.value+'T00:00:00');
            base.setDate(base.getDate()+parseInt(m[1]));
            dueDateEl.value=base.toISOString().slice(0,10);
        });
    }
})();
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
