<?php ob_start();

$ship_via_options  = $ship_via_options  ?? [];
$tax_rates         = $tax_rates         ?? [];
$customer_messages = $customer_messages ?? [];
$reps              = $reps              ?? [];
$payment_terms     = $payment_terms     ?? [];
$users             = $users             ?? [];
$line_items        = $line_items        ?? [];

$isLocked = in_array($so['status'], ['invoiced', 'cancelled']);
$isPaid   = $so['status'] === 'paid';

$statusBadge = [
    'draft'             => 'badge--neutral',
    'confirmed'         => 'badge--info',
    'processing'        => 'badge--info',
    'partially_shipped' => 'badge--warning',
    'paid'              => 'badge--success',
    'shipped'           => 'badge--success',
    'invoiced'          => 'badge--success',
    'cancelled'         => 'badge--neutral',
];
$statusLabel = [
    'draft'             => 'Draft',
    'confirmed'         => 'Confirmed',
    'processing'        => 'Processing',
    'partially_shipped' => 'Partially Shipped',
    'paid'              => 'Paid',
    'shipped'           => 'Shipped',
    'invoiced'          => 'Invoiced',
    'cancelled'         => 'Cancelled',
];

$defaultTaxId  = 0;
$defaultTaxPct = 0;
foreach ($tax_rates as $tr) {
    if ((int)$tr['id'] === (int)($so['tax_rate_id'] ?? 0)) {
        $defaultTaxId  = (int)$tr['id'];
        $defaultTaxPct = (float)$tr['rate'] * 100;
        break;
    }
}

$hasShipAddr = !empty($so['ship_address_1']);

function soLbl(): string { return 'font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.4rem'; }
function soInp(): string { return 'width:100%;padding:.62rem .75rem;font-size:.95rem;font-family:inherit;background:#fff;color:#111;border:1px solid #d1d5db;border-radius:6px;box-sizing:border-box'; }
function soRO(): string  { return 'padding:.6rem .85rem;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:6px;font-size:.95rem;min-height:2.4rem;color:#374151'; }
function soTh(): string  { return 'padding:.6rem .75rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;white-space:nowrap;text-align:left'; }
?>

<!-- Page header -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
    <div>
        <a href="/sales-orders" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Sales Orders
        </a>
        <div style="display:flex;align-items:center;gap:.75rem;margin-top:.25rem">
            <h1 class="page-title">SO #<?= e($so['so_number']) ?></h1>
            <span class="badge <?= $statusBadge[$so['status']] ?? 'badge--neutral' ?>"><?= $statusLabel[$so['status']] ?? ucfirst($so['status']) ?></span>
        </div>
        <p class="page-subtitle"><a href="/customers/<?= (int)$so['customer_id'] ?>" class="link"><?= e($so['company_name']) ?></a></p>
    </div>
    <div style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap">
        <?php if (!$isLocked): ?>
            <button class="btn btn--primary" onclick="document.getElementById('shipModal').style.display='flex'">Ship &amp; Invoice</button>
        <?php endif; ?>
        <?php if (in_array($so['status'], ['confirmed', 'processing', 'partially_shipped'])): ?>
            <button class="btn btn--success" onclick="document.getElementById('paymentModal').style.display='flex'">Collect Payment</button>
        <?php endif; ?>
        <a href="/sales-orders/<?= (int)$so['id'] ?>/print" target="_blank" class="btn btn--secondary">Print / PDF</a>
        <a href="/sales-orders/<?= (int)$so['id'] ?>/packing-slip" target="_blank" class="btn btn--secondary">Packing Slip</a>
        <button class="btn btn--secondary" onclick="document.getElementById('emailModal').style.display='flex'">Email</button>
    </div>
</div>

<!-- Ship & Invoice Modal -->
<div id="shipModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:10px;padding:28px 32px;width:460px;max-width:95vw;box-shadow:0 8px 40px rgba(0,0,0,.25)">
        <h3 style="margin:0 0 6px;color:#222b59;font-size:1.1rem">Ship &amp; Invoice — SO #<?= e($so['so_number']) ?></h3>
        <p style="margin:0 0 18px;font-size:.85rem;color:#6b7280">Creates the invoice from this order<?= $isPaid ? ' (payment already collected — invoice will show paid)' : '' ?> and closes the sales order.</p>

            <?php
            // State what will actually be invoiced. Silence here is how a short pick turns
            // into a wrong invoice.
            $pickState  = $so['pick_status'] ?? 'not_started';
            $pickedQty  = 0.0;
            $orderedQty = 0.0;
            foreach ($line_items ?? [] as $pli) {
                $orderedQty += (float)($pli['qty_ordered'] ?? 0);
                $pickedQty  += (float)($pli['qty_picked'] ?? 0);
            }
            $n = fn($v) => rtrim(rtrim(number_format((float)$v, 2), '0'), '.');
            ?>

            <?php if ($pickState === 'short'): ?>
                <div style="background:#fffbeb;border:1px solid #fcd34d;color:#92400e;border-radius:6px;padding:.7rem .85rem;margin-bottom:16px;font-size:.85rem">
                    <strong>Flagged short by shipping.</strong>
                    <?= !empty($so['pick_note']) ? '<br>"' . e($so['pick_note']) . '"' : '' ?>
                    <br>Only what was picked (<?= $n($pickedQty) ?> of <?= $n($orderedQty) ?>) will be
                    invoiced, and the order stays open for the rest.
                </div>
            <?php elseif ($pickState === 'in_progress'): ?>
                <div style="background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;border-radius:6px;padding:.7rem .85rem;margin-bottom:16px;font-size:.85rem">
                    <strong>Still being picked.</strong> Only the <?= $n($pickedQty) ?> picked so far
                    will be invoiced. Finish picking unless you mean to ship a partial.
                </div>
            <?php elseif ($pickState === 'not_started'): ?>
                <div style="background:#f9fafb;border:1px solid #e5e7eb;color:#6b7280;border-radius:6px;padding:.7rem .85rem;margin-bottom:16px;font-size:.85rem">
                    Not picked through the shipping station — the full ordered quantity will be
                    invoiced.
                    <a href="/shipping/<?= (int)$so['id'] ?>/pick" style="color:#0A3D91">Pick it first</a>
                    to verify what actually ships.
                </div>
            <?php else: ?>
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;border-radius:6px;padding:.7rem .85rem;margin-bottom:16px;font-size:.85rem">
                    <strong>Picked and verified</strong> — <?= $n($pickedQty) ?> of <?= $n($orderedQty) ?>
                    confirmed by scan.
                </div>
            <?php endif; ?>
        <form method="POST" action="/sales-orders/<?= (int)$so['id'] ?>/ship">
        <?= csrf_field() ?>
            <div style="margin-bottom:14px">
                <label style="display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:4px">Ship Date</label>
                <input type="date" name="ship_date" value="<?= date('Y-m-d') ?>" required
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:.95rem;box-sizing:border-box">
            </div>
            <div style="margin-bottom:14px">
                <label style="display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:4px">Tracking Number</label>
                <input type="text" name="tracking_number" placeholder="e.g. 1Z999AA10123456784"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:.95rem;box-sizing:border-box">
            </div>
            <div style="margin-bottom:18px">
                <label style="display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:4px">Ship Via</label>
                <input type="text" name="ship_via" value="<?= e($so['ship_via_name'] ?? '') ?>"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:.95rem;box-sizing:border-box">
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end">
                <button type="button" onclick="document.getElementById('shipModal').style.display='none'"
                    style="padding:8px 18px;border:1px solid #d1d5db;border-radius:6px;background:#fff;cursor:pointer">Cancel</button>
                <button type="submit" style="padding:8px 20px;background:#222b59;color:#fff;border:none;border-radius:6px;font-weight:600;cursor:pointer">Ship &amp; Create Invoice</button>
            </div>
        </form>
    </div>
</div>

<!-- Email Modal -->
<?php $emailUser = \App\Core\Auth::user(); ?>
<div id="emailModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:10px;padding:28px 32px;width:460px;max-width:95vw;box-shadow:0 8px 40px rgba(0,0,0,.25)">
        <h3 style="margin:0 0 18px;color:#222b59;font-size:1.1rem">Email Sales Order #<?= e($so['so_number']) ?></h3>
        <form method="POST" action="/sales-orders/<?= (int)$so['id'] ?>/email">
        <?= csrf_field() ?>
            <div style="margin-bottom:14px">
                <label style="display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:4px">From</label>
                <input type="email" name="email_from" required value="<?= e($emailUser['email'] ?? '') ?>"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:.95rem;box-sizing:border-box">
            </div>
            <div style="margin-bottom:14px">
                <label style="display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:4px">To</label>
                <input type="email" name="email_to" required value="<?= e($so['email'] ?? '') ?>"
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

<!-- Payment Modal -->
<div id="paymentModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:10px;padding:28px 32px;width:460px;max-width:95vw;box-shadow:0 8px 40px rgba(0,0,0,.25)">
        <h3 style="margin:0 0 18px;color:#222b59;font-size:1.1rem">Collect Payment — SO #<?= e($so['so_number']) ?></h3>
        <form method="POST" action="/sales-orders/<?= (int)$so['id'] ?>/payment">
        <?= csrf_field() ?>
            <div style="margin-bottom:14px">
                <label style="display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:4px">Payment Method</label>
                <select name="payment_method" required style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:.95rem;box-sizing:border-box;background:#fff">
                    <option value="">— Select —</option>
                    <option value="credit_card">Credit Card</option>
                    <option value="check">Check</option>
                    <option value="ach">ACH / Wire</option>
                    <option value="cash">Cash</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div style="margin-bottom:14px">
                <label style="display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:4px">Reference / Check # <span style="font-weight:400;color:#9ca3af">(optional)</span></label>
                <input type="text" name="payment_reference" placeholder="Check #, transaction ID, etc."
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:.95rem;box-sizing:border-box">
            </div>
            <div style="margin-bottom:20px">
                <label style="display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:4px">Amount</label>
                <input type="number" name="payment_amount" step="0.01" min="0.01" required
                    value="<?= number_format((float)$so['total_amount'], 2, '.', '') ?>"
                    style="width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:.95rem;box-sizing:border-box">
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end">
                <button type="button" onclick="document.getElementById('paymentModal').style.display='none'"
                    style="padding:8px 18px;border:1px solid #d1d5db;border-radius:6px;background:#fff;cursor:pointer">Cancel</button>
                <button type="submit" style="padding:8px 20px;background:#16a34a;color:#fff;border:none;border-radius:6px;font-weight:600;cursor:pointer">Mark as Paid</button>
            </div>
        </form>
    </div>
</div>

<?php if ($isPaid): ?>
<div style="background:#dcfce7;border:1px solid #86efac;border-radius:8px;padding:.85rem 1.1rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:.75rem">
    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#16a34a" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <div style="font-size:.9rem;color:#15803d">
        <strong>Payment Collected</strong> —
        <?= ucwords(str_replace('_', ' ', $so['payment_method'] ?? '')) ?>
        <?php if ($so['payment_reference']): ?>&nbsp;&bull;&nbsp;Ref: <?= e($so['payment_reference']) ?><?php endif; ?>
        &nbsp;&bull;&nbsp;$<?= number_format((float)$so['payment_amount'], 2) ?>
        <?php if ($so['paid_at']): ?>&nbsp;&bull;&nbsp;<?= date('M j, Y g:i a', strtotime($so['paid_at'])) ?><?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- KPI row -->
<div class="kpi-row">
    <div class="kpi-card">
        <div class="kpi-card__label">Order Total</div>
        <div class="kpi-card__value" id="kpiTotal">$<?= number_format((float)$so['total_amount'], 2) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Order Date</div>
        <div class="kpi-card__value kpi-card__value--sm"><?= date('M j, Y', strtotime($so['order_date'])) ?></div>
    </div>
    <?php if ($so['requested_ship_date']): ?>
    <div class="kpi-card">
        <div class="kpi-card__label">Ship By</div>
        <div class="kpi-card__value kpi-card__value--sm"><?= date('M j, Y', strtotime($so['requested_ship_date'])) ?></div>
    </div>
    <?php endif; ?>
    <?php if ($so['ship_via_name']): ?>
    <div class="kpi-card">
        <div class="kpi-card__label">Ship Via</div>
        <div class="kpi-card__value kpi-card__value--sm"><?= e($so['ship_via_name']) ?></div>
    </div>
    <?php endif; ?>
</div>

<?php if (!$isLocked): ?>
<form method="POST" action="/sales-orders/<?= (int)$so['id'] ?>/edit" id="soForm">
<?= csrf_field() ?>
    <input type="hidden" name="customer_id" value="<?= (int)$so['customer_id'] ?>">
<?php endif; ?>

<div style="background:#fff;border:1px solid #d1d5db;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.08);font-family:inherit">

    <!-- Customer bar -->
    <div style="padding:.85rem 1.5rem;background:#f8f9fb;border-bottom:1px solid #d1d5db">
        <div style="display:flex;align-items:center;gap:1rem">
            <span style="<?= soLbl() ?>">Customer / Job</span>
            <a href="/customers/<?= (int)$so['customer_id'] ?>" style="font-size:1rem;font-weight:700;color:#0A3D91"><?= e($so['company_name']) ?></a>
        </div>
    </div>

    <!-- Header: SO title | Dates+Status | Bill To | Ship To -->
    <table style="width:100%;border-collapse:collapse;border-bottom:2px solid #d1d5db;table-layout:fixed">
        <colgroup>
            <col style="width:200px">
            <col style="width:185px">
            <col>
            <col>
        </colgroup>
        <tr>
            <!-- SO title + number + Status -->
            <td style="padding:1.25rem 1.5rem;vertical-align:top;border-right:1px solid #d1d5db">
                <div style="font-size:1.75rem;font-weight:700;color:#111;letter-spacing:-.02em;margin-bottom:1.25rem">Sales Order</div>
                <div style="<?= soLbl() ?>">S.O. No.</div>
                <div style="padding:.6rem .85rem;background:#f3f4f6;border:1px solid #d1d5db;border-radius:6px;font-size:1rem;font-weight:700;color:#374151;margin-bottom:.85rem"><?= e($so['so_number']) ?></div>
                <div style="<?= soLbl() ?>">Status</div>
                <?php if (!$isLocked): ?>
                    <select name="status" style="<?= soInp() ?>">
                        <?php foreach ([
                            'draft'             => 'Draft',
                            'confirmed'         => 'Confirmed',
                            'processing'        => 'Processing',
                            'partially_shipped' => 'Partially Shipped',
                            'shipped'           => 'Shipped',
                            'invoiced'          => 'Invoiced',
                            'cancelled'         => 'Cancelled',
                        ] as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= ($so['status'] ?? '') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <div style="<?= soRO() ?>"><?= $statusLabel[$so['status']] ?? ucfirst($so['status']) ?></div>
                <?php endif; ?>
            </td>

            <!-- Dates -->
            <td style="padding:1rem 1.25rem;vertical-align:top;border-right:1px solid #d1d5db">
                <div style="<?= soLbl() ?>">Order Date</div>
                <?php if (!$isLocked): ?>
                    <input type="date" name="order_date" value="<?= e($so['order_date']) ?>" required style="<?= soInp() ?>;margin-bottom:.75rem">
                    <div style="<?= soLbl() ?>">Ship Date</div>
                    <input type="date" name="requested_ship_date" value="<?= e($so['requested_ship_date'] ?? '') ?>" style="<?= soInp() ?>">
                <?php else: ?>
                    <div style="<?= soRO() ?>;margin-bottom:.75rem"><?= date('M j, Y', strtotime($so['order_date'])) ?></div>
                    <?php if ($so['requested_ship_date']): ?>
                        <div style="<?= soLbl() ?>">Ship Date</div>
                        <div style="<?= soRO() ?>"><?= date('M j, Y', strtotime($so['requested_ship_date'])) ?></div>
                    <?php endif; ?>
                <?php endif; ?>
            </td>

            <!-- Bill To -->
            <td style="padding:1rem 1.25rem;vertical-align:top;border-right:1px solid #d1d5db">
                <div style="<?= soLbl() ?>">Bill To</div>
                <div style="padding:.75rem 1rem;min-height:120px;background:#f8f9fb;border:1px solid #d1d5db;border-radius:6px;font-size:.9rem;line-height:1.8;color:#374151">
                    <strong><?= e($so['company_name']) ?></strong>
                    <?php if ($so['bill_address_1'] ?? ''): ?><br><?= e($so['bill_address_1']) ?><?php endif; ?>
                    <?php if ($so['bill_city'] ?? ''): ?>
                        <br><?= e($so['bill_city']) ?><?= ($so['bill_state'] ?? '') ? ', ' . e($so['bill_state']) : '' ?> <?= e($so['bill_zip'] ?? '') ?>
                    <?php endif; ?>
                    <?php if ($so['phone'] ?? ''): ?><br><span style="color:#9ca3af"><?= e($so['phone']) ?></span><?php endif; ?>
                </div>
            </td>

            <!-- Ship To -->
            <td style="padding:1rem 1.25rem;vertical-align:top">
                <?php if (!$isLocked): ?>
                    <div style="<?= soLbl() ?>;display:flex;align-items:center;gap:.5rem">
                        Ship To
                        <label style="font-size:.8rem;font-weight:600;text-transform:none;letter-spacing:0;display:inline-flex;align-items:center;gap:.3rem;cursor:pointer;color:#6b7280">
                            <input type="checkbox" id="sameAsBilling" <?= !$hasShipAddr ? 'checked' : '' ?>> Same as billing
                        </label>
                    </div>
                    <div id="shipSameBox" style="padding:.75rem 1rem;min-height:110px;background:#f8f9fb;border:1px solid #d1d5db;border-radius:6px;font-size:.9rem;color:#9ca3af;<?= $hasShipAddr ? 'display:none' : '' ?>">
                        Same as billing address
                    </div>
                    <div id="shipEditBox" style="<?= !$hasShipAddr ? 'display:none' : '' ?>">
                        <div style="display:flex;flex-direction:column;gap:.4rem;margin-top:.4rem">
                            <input type="text" name="ship_name"      value="<?= e($so['ship_name'] ?? '') ?>"      placeholder="Name / Attn"  style="<?= soInp() ?>">
                            <input type="text" name="ship_address_1" value="<?= e($so['ship_address_1'] ?? '') ?>" placeholder="Address"      style="<?= soInp() ?>">
                            <input type="text" name="ship_address_2" value="<?= e($so['ship_address_2'] ?? '') ?>" placeholder="Address 2"    style="<?= soInp() ?>">
                            <table width="100%" cellpadding="0" cellspacing="0"><tr>
                                <td style="padding-right:.3rem"><input type="text" name="ship_city"  value="<?= e($so['ship_city']  ?? '') ?>" placeholder="City" style="<?= soInp() ?>"></td>
                                <td style="width:48px;padding-right:.3rem"><input type="text" name="ship_state" value="<?= e($so['ship_state'] ?? '') ?>" placeholder="ST" maxlength="2" style="<?= soInp() ?>"></td>
                                <td style="width:85px"><input type="text" name="ship_zip" value="<?= e($so['ship_zip'] ?? '') ?>" placeholder="Zip" style="<?= soInp() ?>"></td>
                            </tr></table>
                            <input type="text" name="ship_phone" value="<?= e($so['ship_phone'] ?? '') ?>" placeholder="Phone" style="<?= soInp() ?>">
                        </div>
                    </div>
                <?php else: ?>
                    <div style="<?= soLbl() ?>">Ship To</div>
                    <div style="padding:.75rem 1rem;min-height:120px;background:#f8f9fb;border:1px solid #d1d5db;border-radius:6px;font-size:.9rem;line-height:1.8;color:#374151">
                        <?php
                        $sName  = $so['ship_name']      ?: $so['company_name'];
                        $sAddr1 = $so['ship_address_1'] ?: ($so['bill_address_1'] ?? '');
                        $sCity  = $so['ship_city']      ?: ($so['bill_city']  ?? '');
                        $sSt    = $so['ship_state']     ?: ($so['bill_state'] ?? '');
                        $sZip   = $so['ship_zip']       ?: ($so['bill_zip']   ?? '');
                        ?>
                        <strong><?= e($sName) ?></strong>
                        <?php if ($sAddr1): ?><br><?= e($sAddr1) ?><?php endif; ?>
                        <?php if ($sCity): ?><br><?= e($sCity) ?><?= $sSt ? ', ' . e($sSt) : '' ?> <?= e($sZip) ?><?php endif; ?>
                        <?php if (!$hasShipAddr): ?><br><span style="color:#9ca3af;font-size:.8rem">Same as billing</span><?php endif; ?>
                    </div>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <!-- Meta row: PO · Ship Via · Rep · Processed By -->
    <table style="width:100%;border-collapse:collapse;border-bottom:2px solid #d1d5db;background:#f8f9fb;table-layout:fixed">
        <tr>
            <td style="padding:.85rem 1.25rem;border-right:1px solid #d1d5db;vertical-align:top;width:25%">
                <div style="<?= soLbl() ?>">P.O. No.</div>
                <?php if (!$isLocked): ?>
                    <input type="text" name="po_number" value="<?= e($so['po_number'] ?? '') ?>" maxlength="100" style="<?= soInp() ?>">
                <?php else: ?>
                    <div style="<?= soRO() ?>"><?= e($so['po_number'] ?? '—') ?></div>
                <?php endif; ?>
            </td>
            <td style="padding:.85rem 1.25rem;border-right:1px solid #d1d5db;vertical-align:top;width:25%">
                <div style="<?= soLbl() ?>">Ship Via</div>
                <?php if (!$isLocked): ?>
                    <select name="ship_via_id" style="<?= soInp() ?>">
                        <option value="">— Select —</option>
                        <?php foreach ($ship_via_options as $sv): ?>
                            <option value="<?= (int)$sv['id'] ?>" <?= (string)($so['ship_via_id'] ?? '') === (string)$sv['id'] ? 'selected' : '' ?>><?= e($sv['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <div style="<?= soRO() ?>"><?= e($so['ship_via_name'] ?? '—') ?></div>
                <?php endif; ?>
            </td>
            <td style="padding:.85rem 1.25rem;border-right:1px solid #d1d5db;vertical-align:top;width:25%">
                <div style="<?= soLbl() ?>">Rep</div>
                <?php if (!$isLocked): ?>
                    <select name="rep_id" style="<?= soInp() ?>">
                        <option value="">— None —</option>
                        <?php foreach ($reps as $rep): ?>
                            <option value="<?= (int)$rep['id'] ?>" <?= (string)($so['rep_id'] ?? '') === (string)$rep['id'] ? 'selected' : '' ?>><?= e($rep['last_name'] . ', ' . $rep['first_name']) ?><?= $rep['rep_code'] ? ' (' . e($rep['rep_code']) . ')' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <div style="<?= soRO() ?>"><?= ($so['rep_first'] ?? '') ? e($so['rep_first'] . ' ' . $so['rep_last']) : '—' ?></div>
                <?php endif; ?>
            </td>
            <td style="padding:.85rem 1.25rem;vertical-align:top;width:25%">
                <div style="<?= soLbl() ?>">Processed By</div>
                <?php if (!$isLocked): ?>
                    <select name="processed_by_id" style="<?= soInp() ?>">
                        <option value="">— None —</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= (int)$u['id'] ?>" <?= (string)($so['created_by'] ?? '') === (string)$u['id'] ? 'selected' : '' ?>><?= e($u['first_name'] . ' ' . $u['last_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <div style="<?= soRO() ?>"><?= e(trim(($so['created_first'] ?? '') . ' ' . ($so['created_last'] ?? '')) ?: '—') ?></div>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <!-- Line Items -->
    <div style="border-bottom:2px solid #d1d5db">
        <?php if (!$isLocked): ?>
        <div style="display:flex;justify-content:flex-end;padding:.6rem 1rem;background:#f8f9fb;border-bottom:1px solid #d1d5db">
            <button type="button" class="btn btn--sm btn--secondary" id="addLineBtn">+ Add Line</button>
        </div>
        <?php endif; ?>
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:.925rem">
                <colgroup>
                    <col style="width:155px">
                    <col>
                    <col style="width:88px">
                    <col style="width:65px">
                    <col style="width:120px">
                    <?php if (!$isLocked): ?><col style="width:72px"><?php endif; ?>
                    <col style="width:50px">
                    <col style="width:120px">
                    <?php if (!$isLocked): ?><col style="width:34px"><?php endif; ?>
                </colgroup>
                <thead>
                    <tr style="background:#f8f9fb;border-bottom:1px solid #d1d5db">
                        <th style="<?= soTh() ?>">Item</th>
                        <th style="<?= soTh() ?>">Description</th>
                        <th style="<?= soTh() ?>;text-align:right">Qty</th>
                        <th style="<?= soTh() ?>">U/M</th>
                        <th style="<?= soTh() ?>;text-align:right">Price Each</th>
                        <?php if (!$isLocked): ?><th style="<?= soTh() ?>;text-align:right">Disc %</th><?php endif; ?>
                        <th style="<?= soTh() ?>;text-align:center">Tax</th>
                        <th style="<?= soTh() ?>;text-align:right">Amount</th>
                        <?php if (!$isLocked): ?><th style="<?= soTh() ?>"></th><?php endif; ?>
                    </tr>
                </thead>
                <tbody id="lineBody">
                    <?php if ($isLocked): ?>
                        <?php if (empty($line_items)): ?>
                            <tr><td colspan="7" class="table__empty">No line items.</td></tr>
                        <?php else: ?>
                            <?php foreach ($line_items as $li): ?>
                            <tr style="border-bottom:1px solid #f3f4f6">
                                <td style="padding:.6rem .75rem;font-family:monospace;font-size:.875rem">
                                    <?php if ($li['product_id']): ?>
                                        <a href="/products/<?= (int)$li['product_id'] ?>" style="color:#0A3D91;font-weight:700"><?= e($li['sku'] ?? $li['quickbooks_item'] ?? '') ?></a>
                                    <?php else: ?>
                                        <span style="color:#6b7280"><?= e($li['quickbooks_item'] ?? '—') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:.6rem .75rem;font-size:.875rem;color:#374151"><?= e($li['description'] ?? $li['product_name'] ?? '') ?></td>
                                <td style="padding:.6rem .75rem;text-align:right"><?= rtrim(rtrim(number_format((float)$li['qty_ordered'], 4), '0'), '.') ?></td>
                                <td style="padding:.6rem .75rem;font-size:.8rem;color:#6b7280"><?= e($li['uom_code'] ?? '') ?></td>
                                <td style="padding:.6rem .75rem;text-align:right">$<?= number_format((float)$li['unit_price'], 2) ?></td>
                                <td style="padding:.6rem .75rem;text-align:center;font-size:.8rem"><?= $li['taxable'] ? '✓' : '' ?></td>
                                <td style="padding:.6rem .75rem;text-align:right;font-family:monospace;font-weight:600">$<?= number_format((float)$li['line_total'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                    <!-- Editable rows injected by JS when not locked -->
                </tbody>
                <tfoot>
                    <?php if ($isLocked): ?>
                        <?php if ((float)$so['discount_amount'] > 0): ?>
                        <tr>
                            <td colspan="6" style="padding:.5rem .75rem;text-align:right;color:#6b7280;font-size:.9rem">Discount</td>
                            <td style="padding:.5rem .75rem;text-align:right;font-family:monospace;color:#ef4444">−$<?= number_format((float)$so['discount_amount'], 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ((float)$so['tax_amount'] > 0): ?>
                        <tr>
                            <td colspan="6" style="padding:.5rem .75rem;text-align:right;color:#6b7280;font-size:.9rem">Tax <?= $so['tax_rate_name'] ? '(' . e($so['tax_rate_name']) . ')' : '' ?></td>
                            <td style="padding:.5rem .75rem;text-align:right;font-family:monospace">$<?= number_format((float)$so['tax_amount'], 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr style="border-top:2px solid #d1d5db">
                            <td colspan="6" style="padding:.85rem .75rem;text-align:right;font-weight:700;font-size:1.05rem">Total</td>
                            <td style="padding:.85rem .75rem;text-align:right;font-family:monospace;font-weight:700;font-size:1.1rem">$<?= number_format((float)$so['total_amount'], 2) ?></td>
                        </tr>
                    <?php else: ?>
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
                                                <?= (int)$tr['id'] === $defaultTaxId ? 'selected' : '' ?>><?= e($tr['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <input type="hidden" name="tax_rate_pct" id="taxRatePct" value="<?= $defaultTaxPct ?>">
                            </td>
                            <td style="padding:.7rem .75rem;text-align:right;font-family:monospace;font-weight:600" id="fTax">$0.00</td>
                            <td></td>
                        </tr>
                        <tr style="border-top:2px solid #d1d5db">
                            <td colspan="7" style="padding:.85rem .75rem;text-align:right;font-weight:700;font-size:1.05rem">Total</td>
                            <td style="padding:.85rem .75rem;text-align:right;font-family:monospace;font-weight:700;font-size:1.1rem" id="fTotal">$0.00</td>
                            <td></td>
                        </tr>
                    <?php endif; ?>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Footer: Memo · Customer Message · Internal Notes -->
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr valign="top">
            <td style="padding:1.25rem 1.5rem;width:34%;border-right:1px solid #e5e7eb">
                <div style="<?= soLbl() ?>">Memo</div>
                <?php if (!$isLocked): ?>
                    <textarea name="memo" rows="3" style="<?= soInp() ?>;resize:vertical"><?= e($so['memo'] ?? '') ?></textarea>
                <?php else: ?>
                    <div style="font-size:.9rem;color:#374151;line-height:1.6;min-height:2rem"><?= $so['memo'] ? nl2br(e($so['memo'])) : '<span style="color:#9ca3af">—</span>' ?></div>
                <?php endif; ?>
            </td>
            <td style="padding:1.25rem 1.5rem;width:33%;border-right:1px solid #e5e7eb">
                <div style="<?= soLbl() ?>">Customer Message</div>
                <?php if (!$isLocked): ?>
                    <select name="customer_message_id" style="<?= soInp() ?>">
                        <option value="">— None —</option>
                        <?php foreach ($customer_messages as $cm): ?>
                            <option value="<?= (int)$cm['id'] ?>" <?= (string)($so['customer_message_id'] ?? '') === (string)$cm['id'] ? 'selected' : '' ?>><?= e($cm['message']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <div style="font-size:.9rem;color:#374151;line-height:1.6;min-height:2rem"><?= $so['customer_message'] ? e($so['customer_message']) : '<span style="color:#9ca3af">—</span>' ?></div>
                <?php endif; ?>
            </td>
            <td style="padding:1.25rem 1.5rem;width:33%">
                <div style="<?= soLbl() ?>">Internal Notes</div>
                <?php if (!$isLocked): ?>
                    <textarea name="internal_notes" rows="3" style="<?= soInp() ?>;resize:vertical"><?= e($so['internal_notes'] ?? '') ?></textarea>
                <?php else: ?>
                    <div style="font-size:.9rem;color:#6b7280;line-height:1.6;min-height:2rem"><?= $so['internal_notes'] ? nl2br(e($so['internal_notes'])) : '<span style="color:#9ca3af">—</span>' ?></div>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <?php if (!$isLocked): ?>
    <!-- Save bar -->
    <div style="display:flex;justify-content:flex-end;gap:.75rem;padding:1rem 1.5rem;background:#f8f9fb;border-top:1px solid #d1d5db">
        <a href="/sales-orders" class="btn btn--secondary">Cancel</a>
        <button type="submit" class="btn btn--primary">Save Order</button>
    </div>
    <?php endif; ?>

</div><!-- /so document -->

<?php if (!$isLocked): ?>
</form>

<script>
var EXISTING_LINES = <?= json_encode(array_values($line_items), JSON_HEX_TAG) ?>;
</script>

<script>
(function(){
    function fmt(n){return '$'+(parseFloat(n)||0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,',');}
    function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');}

    const INP  = 'width:100%;padding:.35rem .5rem;font-size:.9rem;font-family:inherit;background:#fff;border:1px solid #d1d5db;border-radius:5px;box-sizing:border-box';
    const INP_R= INP+';text-align:right';

    function addLine(d){
        d=d||{};
        const tr=document.createElement('tr');
        tr.className='so-line';
        tr.style.borderBottom='1px solid #e5e7eb';
        tr.innerHTML=`
            <td style="padding:.3rem .4rem;vertical-align:middle;position:relative">
                <input type="hidden" name="line_product_id[]" class="f-pid" value="${esc(d.product_id||'')}">
                <input type="text" name="line_item[]" value="${esc(d.sku||d.quickbooks_item||'')}" class="f-code" placeholder="Item" autocomplete="off" style="${INP}">
                <div class="f-drop" style="display:none;position:absolute;top:100%;left:0;min-width:440px;z-index:9999;margin-top:1px;background:#fff;border:1px solid #c8c8c8;border-radius:6px;box-shadow:0 8px 28px rgba(0,0,0,.18);max-height:300px;overflow-y:auto"></div>
            </td>
            <td style="padding:.3rem .4rem;vertical-align:middle"><input type="text" name="line_desc[]" value="${esc(d.description||d.product_name||'')}" class="f-desc" placeholder="Description" style="${INP}"></td>
            <td style="padding:.3rem .4rem;vertical-align:middle"><input type="number" name="line_qty[]"      value="${d.qty_ordered!=null?parseFloat(d.qty_ordered):1}" class="f-qty f-calc" step="0.01" min="0" style="${INP_R}"></td>
            <td style="padding:.3rem .4rem;vertical-align:middle"><input type="text"   name="line_uom[]"      value="${esc(d.uom_code||'')}" class="f-uom" maxlength="10" style="${INP}"></td>
            <td style="padding:.3rem .4rem;vertical-align:middle"><input type="number" name="line_price[]"    value="${d.unit_price!=null?parseFloat(d.unit_price).toFixed(2):''}" class="f-price f-calc" step="0.01" min="0" placeholder="0.00" style="${INP_R}"></td>
            <td style="padding:.3rem .4rem;vertical-align:middle"><input type="number" name="line_discount[]" value="${d.discount_pct!=null?d.discount_pct:0}" class="f-disc f-calc" step="any" min="0" max="100" style="${INP_R}"></td>
            <td style="padding:.3rem .4rem;vertical-align:middle;text-align:center"><input type="checkbox" name="line_taxable[]" value="1" class="f-tax f-calc" ${d.taxable==1?'checked':''}></td>
            <td style="padding:.3rem .75rem;vertical-align:middle;text-align:right;font-family:monospace;font-weight:600" class="f-total">$0.00</td>
            <td style="padding:.3rem .4rem;vertical-align:middle"><button type="button" class="so-del-btn" title="Remove" style="background:none;border:none;font-size:1.3rem;cursor:pointer;color:#9ca3af;line-height:1;padding:0 .3rem;border-radius:4px">&times;</button></td>`;
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
        document.querySelectorAll('.so-line').forEach(tr=>{
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
        const total=sub-disc+taxAmt;
        document.getElementById('fTotal').textContent=fmt(total);
        document.getElementById('kpiTotal').textContent=fmt(total);
    }
    function bindRow(tr){
        tr.querySelectorAll('.f-calc').forEach(el=>{
            el.addEventListener('input',()=>{recalcRow(tr);recalcAll();});
            el.addEventListener('change',()=>{recalcRow(tr);recalcAll();});
        });
        tr.querySelector('.so-del-btn').addEventListener('click',()=>{tr.remove();recalcAll();});
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
                (p.price?`<span style="margin-left:auto;color:#6b7280;font-size:.85rem">${fmt(p.price)}</span>`:'');
            d.addEventListener('mouseover',()=>d.style.background='#f0f4ff');
            d.addEventListener('mouseout', ()=>d.style.background='');
            d.addEventListener('mousedown',e=>{
                e.preventDefault();
                tr.querySelector('.f-code').value=p.sku||p.quickbooks_item;
                tr.querySelector('.f-pid').value=p.id;
                tr.querySelector('.f-desc').value=p.name||'';
                if(p.price) tr.querySelector('.f-price').value=parseFloat(p.price).toFixed(2);
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

    const sameCb=document.getElementById('sameAsBilling');
    sameCb.addEventListener('change',function(){
        document.getElementById('shipEditBox').style.display=this.checked?'none':'block';
        document.getElementById('shipSameBox').style.display=this.checked?'block':'none';
    });

    if(EXISTING_LINES && EXISTING_LINES.length){
        EXISTING_LINES.forEach(l=>addLine(l));
    } else {
        addLine();addLine();addLine();
    }
    recalcAll();
})();
</script>
<?php endif; ?>

<?php
// ---------------------------------------------------------------- Digital Job Binder
// Helpers are guarded and jb-prefixed: two views declaring the same function name in one
// request is a fatal error, and there is no global fileSize().
if (!function_exists('jbFileSize')) {
    function jbFileSize(?int $bytes): string {
        if (!$bytes) return '';
        $u = ['B','KB','MB','GB']; $i = 0;
        while ($bytes >= 1024 && $i < 3) { $bytes /= 1024; $i++; }
        return round($bytes, $i ? 1 : 0) . ' ' . $u[$i];
    }
}
if (!function_exists('jbIsImage')) {
    function jbIsImage(?string $mime, string $path): bool {
        if ($mime && str_starts_with($mime, 'image/')) return true;
        return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['png','jpg','jpeg','gif','webp'], true);
    }
}
if (!function_exists('jbSourceLabel')) {
    function jbSourceLabel(?string $s): string {
        return [
            'internal'        => 'internally',
            'customer_email'  => 'by email',
            'customer_phone'  => 'by phone',
            'customer_portal' => 'via the portal',
        ][$s] ?? '';
    }
}

// Named uniquely and guarded: two views defining the same function in one request is a
// fatal error, and there is no global fileSize() to lean on.
if (!function_exists('jbDocType')) {
    function jbDocType(string $t): string
    {
        return \App\Services\JobDocumentService::TYPES[$t] ?? 'Document';
    }
}

if (!function_exists('jbFileSize')) {
    function jbFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024) . ' KB';
        return $bytes . ' B';
    }
}

$jbInp = 'width:100%;padding:.5rem .65rem;font-size:.9rem;font-family:inherit;border:1px solid #d1d5db;'
       . 'border-radius:5px;box-sizing:border-box;background:#fff;color:#111';
?>

<!-- Job documents — the customer's PO and the rest of the paperwork -->
<div id="documents" style="margin-top:1.5rem">
    <div style="display:block;border-bottom:2px solid #d1d5db;padding-bottom:.5rem;margin-bottom:1rem">
        <span style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280">
            Documents
        </span>
        <?php if (!empty($jobDocuments)): ?>
            <span style="font-size:.78rem;color:#9ca3af">
                &nbsp;— <?= count($jobDocuments) ?> on file
            </span>
        <?php endif; ?>
    </div>

    <?php if (empty($jobDocuments)): ?>
        <p style="color:#9ca3af;font-size:.9rem;margin:0 0 1rem">
            Nothing filed yet. The customer's PO belongs here — it stays with this job for
            good, and is still one click from the invoice long after the order is closed.
        </p>
    <?php else: ?>
        <table style="width:100%;border-collapse:collapse;margin-bottom:1rem">
            <?php foreach ($jobDocuments as $jd): ?>
                <?php
                $isPo = $jd['doc_type'] === 'customer_po';
                $td   = 'padding:.6rem .5rem;border-bottom:1px solid #f3f4f6;vertical-align:top';
                ?>
                <tr>
                    <td style="<?= $td ?>;width:8.5rem">
                        <span class="badge <?= $isPo ? 'badge--info' : 'badge--neutral' ?>">
                            <?= e(jbDocType($jd['doc_type'])) ?>
                        </span>
                    </td>
                    <td style="<?= $td ?>">
                        <a href="<?= e($jd['file_path']) ?>" target="_blank" rel="noopener"
                           style="color:#0A3D91;font-weight:600;font-size:.92rem;text-decoration:none">
                            <?= e($jd['reference_num'] ?: ($jd['title'] ?: $jd['file_name'])) ?>
                        </a>
                        <?php if ($jd['reference_num'] && $jd['title']): ?>
                            <span style="color:#6b7280;font-size:.85rem"> — <?= e($jd['title']) ?></span>
                        <?php endif; ?>
                        <div style="font-size:.78rem;color:#9ca3af;margin-top:.15rem">
                            <?= e($jd['file_name']) ?><?= $jd['file_size'] ? ' · ' . jbFileSize((int)$jd['file_size']) : '' ?>
                        </div>
                        <?php if (!empty($jd['notes'])): ?>
                            <div style="font-size:.82rem;color:#6b7280;margin-top:.2rem"><?= e($jd['notes']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="<?= $td ?>;width:11rem;font-size:.78rem;color:#9ca3af;text-align:right">
                        <?= date('M j, Y', strtotime($jd['created_at'])) ?>
                        <?php if (!empty($jd['uploaded_by_name'])): ?>
                            <div><?= e($jd['uploaded_by_name']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="<?= $td ?>;width:5rem;text-align:right">
                        <form method="POST" action="/sales-orders/<?= (int)$so['id'] ?>/documents/<?= (int)$jd['id'] ?>/remove"
                              onsubmit="return confirm('Take this off the binder? The file is kept.')" style="margin:0">
                            <?= csrf_field() ?>
                            <button type="submit" style="background:none;border:none;padding:0;cursor:pointer;color:#9ca3af;
                                                         font-size:.75rem;text-decoration:underline">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <!-- File a document -->
    <div class="card" style="padding:1.25rem;background:#f8fafc">
        <div style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.8rem">
            File a document
        </div>
        <form method="POST" action="/sales-orders/<?= (int)$so['id'] ?>/documents" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <table style="width:100%;border-collapse:collapse">
                <tr>
                    <td style="padding:.3rem .6rem .3rem 0;font-size:.85rem;color:#6b7280;width:7rem">Kind</td>
                    <td style="padding:.3rem 0;width:40%">
                        <select name="doc_type" style="<?= $jbInp ?>">
                            <?php foreach (\App\Services\JobDocumentService::TYPES as $typeKey => $typeLabel): ?>
                                <option value="<?= e($typeKey) ?>"><?= e($typeLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td style="padding:.3rem .6rem .3rem 1rem;font-size:.85rem;color:#6b7280;width:7rem">PO / reference</td>
                    <td style="padding:.3rem 0">
                        <input type="text" name="reference_num" maxlength="100"
                               placeholder="The customer's PO number"
                               value="<?= e($so['po_number'] ?? '') ?>" style="<?= $jbInp ?>">
                    </td>
                </tr>
                <tr>
                    <td style="padding:.3rem .6rem .3rem 0;font-size:.85rem;color:#6b7280">File</td>
                    <td style="padding:.3rem 0"><input type="file" name="document_file" required style="font-size:.9rem"></td>
                    <td style="padding:.3rem .6rem .3rem 1rem;font-size:.85rem;color:#6b7280">Notes</td>
                    <td style="padding:.3rem 0">
                        <input type="text" name="notes" maxlength="500" placeholder="Optional" style="<?= $jbInp ?>">
                    </td>
                </tr>
            </table>
            <div style="text-align:right;margin-top:.8rem">
                <button type="submit" class="btn btn--primary">File on Binder</button>
            </div>
        </form>
        <div style="font-size:.75rem;color:#9ca3af;margin-top:.7rem">
            Accepted: PDF, images, Office files, email files — up to 20 MB.
            A customer PO needs its number, because that is what people search by later.
        </div>
    </div>
</div>

<!-- Artwork -->
<div id="artwork" style="margin-top:1.5rem">
    <div style="display:block;border-bottom:2px solid #d1d5db;padding-bottom:.5rem;margin-bottom:1rem">
        <span style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280">
            Artwork
        </span>
        <?php if (($artworkCounts['items'] ?? 0) > 0): ?>
            <span style="font-size:.78rem;color:#9ca3af">
                &nbsp;— <?= (int)$artworkCounts['items'] ?> design<?= $artworkCounts['items'] === 1 ? '' : 's' ?>,
                <?= (int)$artworkCounts['revisions'] ?> revision<?= $artworkCounts['revisions'] === 1 ? '' : 's' ?>
                <?php if (($artworkCounts['pending'] ?? 0) > 0): ?>
                    · <strong style="color:#b45309"><?= (int)$artworkCounts['pending'] ?> awaiting approval</strong>
                <?php endif; ?>
            </span>
        <?php endif; ?>
    </div>

    <?php if (empty($artwork)): ?>
        <p style="color:#9ca3af;font-size:.9rem;margin:0 0 1rem">
            No artwork on this job yet. Upload the first proof below — every later version is
            kept, so you can always see what the customer approved.
        </p>
    <?php endif; ?>

    <?php foreach ($artwork as $art): ?>
        <?php
        $latest   = $art['latest'] ?? null;
        $approved = $art['approved'] ?? null;
        $stale    = $approved && $latest && (int)$approved['id'] !== (int)$latest['id'];
        ?>
        <div class="card" style="padding:1.25rem;margin-bottom:1rem">
            <table style="width:100%;border-collapse:collapse;margin-bottom:.75rem">
                <tr>
                    <td style="vertical-align:top">
                        <div style="font-weight:600;font-size:1rem"><?= e($art['title']) ?></div>
                        <?php if (!empty($art['notes'])): ?>
                            <div style="font-size:.85rem;color:#6b7280;margin-top:.2rem"><?= e($art['notes']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="vertical-align:top;text-align:right;white-space:nowrap">
                        <?php if ($approved && !$stale): ?>
                            <span class="badge badge--success">Approved</span>
                        <?php elseif ($stale): ?>
                            <span class="badge badge--warning">Rev <?= (int)$approved['revision_no'] ?> approved, newer version pending</span>
                        <?php elseif ($latest && $latest['status'] === 'rejected'): ?>
                            <span class="badge badge--danger">Rejected</span>
                        <?php else: ?>
                            <span class="badge badge--neutral">Awaiting approval</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <?php foreach ($art['revisions'] as $rev): ?>
                <?php
                $isApproved = $rev['status'] === 'approved';
                $isRejected = $rev['status'] === 'rejected';
                $edge       = $isApproved ? '#16a34a' : ($isRejected ? '#dc2626' : '#d1d5db');
                ?>
                <table style="width:100%;border-collapse:collapse;border-left:3px solid <?= $edge ?>;
                              background:#fafafa;margin-bottom:.6rem">
                    <tr>
                        <td style="padding:.7rem .9rem;width:84px;vertical-align:top">
                            <?php if (jbIsImage($rev['mime_type'], $rev['file_path'])): ?>
                                <a href="<?= e($rev['file_path']) ?>" target="_blank">
                                    <img src="<?= e($rev['file_path']) ?>" alt=""
                                         style="width:72px;height:72px;object-fit:cover;border:1px solid #e5e7eb;border-radius:4px;background:#fff">
                                </a>
                            <?php else: ?>
                                <a href="<?= e($rev['file_path']) ?>" target="_blank"
                                   style="display:block;width:72px;height:72px;border:1px solid #e5e7eb;border-radius:4px;
                                          background:#fff;text-align:center;line-height:72px;font-size:.7rem;
                                          font-weight:700;color:#6b7280;text-decoration:none">
                                    <?= strtoupper(pathinfo($rev['file_path'], PATHINFO_EXTENSION)) ?>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td style="padding:.7rem .9rem .7rem 0;vertical-align:top">
                            <div style="font-size:.9rem">
                                <strong>Rev <?= (int)$rev['revision_no'] ?></strong>
                                &nbsp;<a href="<?= e($rev['file_path']) ?>" target="_blank" style="color:#0A3D91"><?= e($rev['file_name']) ?></a>
                                <span style="color:#9ca3af;font-size:.8rem"><?= jbFileSize($rev['file_size'] ? (int)$rev['file_size'] : null) ?></span>
                            </div>
                            <div style="font-size:.78rem;color:#6b7280;margin-top:.2rem">
                                Uploaded <?= date('M j, Y', strtotime($rev['created_at'])) ?>
                                <?= $rev['uploaded_by_name'] ? 'by ' . e($rev['uploaded_by_name']) : '' ?>
                            </div>
                            <?php if (!empty($rev['notes'])): ?>
                                <div style="font-size:.82rem;color:#374151;margin-top:.3rem"><?= e($rev['notes']) ?></div>
                            <?php endif; ?>

                            <?php if ($isApproved || $isRejected): ?>
                                <div style="font-size:.8rem;margin-top:.4rem;color:<?= $isApproved ? '#166534' : '#b91c1c' ?>">
                                    <?= $isApproved ? '✓ Approved' : '✕ Rejected' ?>
                                    <?= $rev['decided_name'] ? ' by ' . e($rev['decided_name']) : '' ?>
                                    <?= jbSourceLabel($rev['decision_source']) ?>
                                    <?= $rev['decided_at'] ? ' on ' . date('M j, Y', strtotime($rev['decided_at'])) : '' ?>
                                    <?= $rev['decided_by_name'] ? ' — recorded by ' . e($rev['decided_by_name']) : '' ?>
                                </div>
                                <?php if (!empty($rev['decision_note'])): ?>
                                    <div style="font-size:.8rem;color:#6b7280;margin-top:.2rem">"<?= e($rev['decision_note']) ?>"</div>
                                <?php endif; ?>
                            <?php else: ?>
                                <form method="POST" action="/sales-orders/<?= (int)$so['id'] ?>/revision/<?= (int)$rev['id'] ?>/decision"
                                      style="margin-top:.5rem">
                                    <?= csrf_field() ?>
                                    <table style="width:100%;border-collapse:separate;border-spacing:.35rem 0;margin:0 -.35rem">
                                        <tr>
                                            <td style="width:26%">
                                                <select name="source" style="<?= $jbInp ?>;font-size:.82rem;padding:.35rem .5rem">
                                                    <option value="customer_email">Customer, by email</option>
                                                    <option value="customer_phone">Customer, by phone</option>
                                                    <option value="internal">Internal sign-off</option>
                                                </select>
                                            </td>
                                            <td style="width:26%">
                                                <input type="text" name="decided_name" placeholder="Their name"
                                                       style="<?= $jbInp ?>;font-size:.82rem;padding:.35rem .5rem">
                                            </td>
                                            <td>
                                                <input type="text" name="decision_note" placeholder="Note (optional)"
                                                       style="<?= $jbInp ?>;font-size:.82rem;padding:.35rem .5rem">
                                            </td>
                                            <td style="width:150px;white-space:nowrap;text-align:right">
                                                <button type="submit" name="decision" value="approved" class="btn btn--xs"
                                                        style="background:#16a34a;color:#fff;border-color:#16a34a">Approve</button>
                                                <button type="submit" name="decision" value="rejected" class="btn btn--xs"
                                                        style="background:#fff;color:#b91c1c;border-color:#fca5a5">Reject</button>
                                            </td>
                                        </tr>
                                    </table>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            <?php endforeach; ?>

            <!-- New revision -->
            <form method="POST" action="/sales-orders/<?= (int)$so['id'] ?>/artwork/<?= (int)$art['id'] ?>/revision"
                  enctype="multipart/form-data" style="margin-top:.6rem;padding-top:.7rem;border-top:1px dashed #e5e7eb">
                <?= csrf_field() ?>
                <table style="width:100%;border-collapse:separate;border-spacing:.4rem 0;margin:0 -.4rem">
                    <tr>
                        <td style="width:36%"><input type="file" name="artwork_file" required style="font-size:.82rem"></td>
                        <td><input type="text" name="revision_notes" placeholder="What changed in this version?"
                                   style="<?= $jbInp ?>;font-size:.82rem;padding:.4rem .5rem"></td>
                        <td style="width:210px;white-space:nowrap;text-align:right">
                            <button type="submit" class="btn btn--xs btn--secondary">Upload Revision</button>
                        </td>
                    </tr>
                </table>
            </form>

            <form method="POST" action="/sales-orders/<?= (int)$so['id'] ?>/artwork/<?= (int)$art['id'] ?>/remove"
                  style="text-align:right;margin-top:.4rem"
                  onsubmit="return confirm('Remove this artwork from the binder? Its revision history is kept.')">
                <?= csrf_field() ?>
                <button type="submit" style="background:none;border:none;padding:0;color:#9ca3af;cursor:pointer;
                                             font-size:.75rem;text-decoration:underline">Remove this artwork</button>
            </form>
        </div>
    <?php endforeach; ?>

    <!-- Add artwork -->
    <div class="card" style="padding:1.25rem;background:#f8fafc">
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.8rem">
            Add artwork
        </div>
        <form method="POST" action="/sales-orders/<?= (int)$so['id'] ?>/artwork" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <table style="width:100%;border-collapse:collapse">
                <tr>
                    <td style="width:30%;padding:.3rem .6rem .3rem 0;font-size:.85rem;color:#6b7280">Name</td>
                    <td style="padding:.3rem 0">
                        <input type="text" name="title" required placeholder='e.g. 48in Walking Man' style="<?= $jbInp ?>">
                    </td>
                </tr>
                <tr>
                    <td style="padding:.3rem .6rem .3rem 0;font-size:.85rem;color:#6b7280">File</td>
                    <td style="padding:.3rem 0"><input type="file" name="artwork_file" required style="font-size:.9rem"></td>
                </tr>
                <tr>
                    <td style="padding:.3rem .6rem .3rem 0;font-size:.85rem;color:#6b7280">Notes</td>
                    <td style="padding:.3rem 0">
                        <input type="text" name="revision_notes" placeholder="Optional — anything worth knowing about this proof"
                               style="<?= $jbInp ?>">
                    </td>
                </tr>
            </table>
            <div style="text-align:right;margin-top:.8rem">
                <button type="submit" class="btn btn--primary">Add Artwork</button>
            </div>
        </form>
        <div style="font-size:.75rem;color:#9ca3af;margin-top:.7rem">
            Accepted: PDF, PNG, JPG, GIF, WebP, SVG, AI, EPS, DXF, DWG — up to 30 MB.
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
