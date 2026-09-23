<?php ob_start(); ?>
<?php
$flash      = \App\Core\Session::getFlash('success');
$flashError = \App\Core\Session::getFlash('error');

$statusBadge = [
    'draft'     => ['badge--neutral', 'Draft'],
    'sent'      => ['badge--info',    'Sent'],
    'partial'   => ['badge--warning', 'Partial'],
    'received'  => ['badge--success', 'Received'],
    'closed'    => ['badge--neutral', 'Closed'],
    'cancelled' => ['badge--danger',  'Cancelled'],
];

[$sBadge, $sLabel] = $statusBadge[$po['status']] ?? ['badge--neutral', ucfirst($po['status'])];

$canReceive = in_array($po['status'], ['sent', 'partial'], true);
$canEdit    = !in_array($po['status'], ['received', 'closed', 'cancelled'], true);

$totalOrdered  = 0;
$totalReceived = 0;
foreach ($lines as $line) {
    $totalOrdered  += (float)$line['qty_ordered'];
    $totalReceived += (float)$line['qty_received'];
}
?>

<?php if ($flash): ?>
    <div class="alert alert--success" style="margin-bottom:1rem"><?= e($flash) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert--danger" style="margin-bottom:1rem"><?= e($flashError) ?></div>
<?php endif; ?>

<div class="page-header">
    <div class="page-header__left">
        <div style="font-size:.85rem;color:var(--color-text-muted);margin-bottom:.25rem">
            <a href="/purchasing" style="color:inherit">Purchase Orders</a> &rsaquo; <?= e($po['po_number']) ?>
        </div>
        <h1 class="page-title" style="margin:0;display:inline-flex;align-items:center;gap:.75rem">
            <?= e($po['po_number']) ?>
            <span class="badge <?= $sBadge ?>"><?= $sLabel ?></span>
        </h1>
    </div>
    <div class="page-header__right" style="display:flex;gap:.5rem">
        <a href="/purchasing/<?= (int)$po['id'] ?>/print" target="_blank" class="btn btn--secondary">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:3px"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print
        </a>
        <button type="button" class="btn btn--secondary" onclick="document.getElementById('emailModal').style.display='flex'">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:3px"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            Email
        </button>
        <?php if ($canEdit): ?>
            <a href="/purchasing/<?= (int)$po['id'] ?>/edit" class="btn btn--secondary">Edit</a>
        <?php endif; ?>

        <!-- Status change -->
        <form method="POST" action="/purchasing/<?= (int)$po['id'] ?>/status" style="display:inline">
            <?= csrf_field() ?>
            <select name="status" class="input" style="width:auto;min-width:130px;display:inline-block"
                    onchange="this.form.submit()">
                <?php foreach (['draft','sent','partial','received','closed','cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $po['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<!-- Summary row -->
<table style="width:100%;border-collapse:collapse;margin-bottom:1.25rem">
    <tr style="vertical-align:top">
        <td style="width:33.3%;padding-right:.625rem">
            <div class="card" style="padding:1rem 1.25rem">
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--color-text-muted)">Vendor</div>
                <div style="font-weight:600;margin-top:.3rem"><?= e($po['vendor_name']) ?></div>
                <?php if ($po['vendor_account_number']): ?>
                    <div style="font-size:.8rem;color:var(--color-text-muted)">Acct: <?= e($po['vendor_account_number']) ?></div>
                <?php endif; ?>
                <?php if ($po['vendor_phone']): ?>
                    <div style="font-size:.8rem;color:var(--color-text-muted)"><?= e($po['vendor_phone']) ?></div>
                <?php endif; ?>
                <?php if ($po['vendor_email']): ?>
                    <div style="font-size:.8rem;color:var(--color-text-muted)"><?= e($po['vendor_email']) ?></div>
                <?php endif; ?>
            </div>
        </td>
        <td style="width:33.3%;padding-right:.625rem;padding-left:.625rem">
            <div class="card" style="padding:1rem 1.25rem">
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--color-text-muted)">Dates</div>
                <div style="font-size:.875rem;margin-top:.3rem">
                    <span style="color:var(--color-text-muted)">Ordered:</span>
                    <strong><?= e(date('M j, Y', strtotime($po['order_date']))) ?></strong>
                </div>
                <?php if ($po['expected_date']): ?>
                <div style="font-size:.875rem;margin-top:.2rem">
                    <span style="color:var(--color-text-muted)">Expected:</span>
                    <strong><?= e(date('M j, Y', strtotime($po['expected_date']))) ?></strong>
                </div>
                <?php endif; ?>
                <?php if ($po['received_date']): ?>
                <div style="font-size:.875rem;margin-top:.2rem">
                    <span style="color:var(--color-text-muted)">Received:</span>
                    <strong><?= e(date('M j, Y', strtotime($po['received_date']))) ?></strong>
                </div>
                <?php endif; ?>
                <?php if ($po['vendor_ref']): ?>
                <div style="font-size:.875rem;margin-top:.2rem">
                    <span style="color:var(--color-text-muted)">Vendor Ref:</span>
                    <strong><?= e($po['vendor_ref']) ?></strong>
                </div>
                <?php endif; ?>
            </div>
        </td>
        <td style="width:33.3%;padding-left:.625rem">
            <div class="card" style="padding:1rem 1.25rem">
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--color-text-muted)">Totals</div>
                <table style="width:100%;border-collapse:collapse;font-size:.875rem;margin-top:.3rem">
                    <tr>
                        <td style="color:var(--color-text-muted);padding:.15rem 0">Subtotal</td>
                        <td class="text-right"><?= money((float)$po['subtotal']) ?></td>
                    </tr>
                    <?php if ((float)$po['tax_amount'] > 0): ?>
                    <tr>
                        <td style="color:var(--color-text-muted);padding:.15rem 0">Tax</td>
                        <td class="text-right"><?= money((float)$po['tax_amount']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ((float)$po['shipping_cost'] > 0): ?>
                    <tr>
                        <td style="color:var(--color-text-muted);padding:.15rem 0">Shipping</td>
                        <td class="text-right"><?= money((float)$po['shipping_cost']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr style="border-top:1px solid var(--color-border)">
                        <td style="padding:.3rem 0 0;font-weight:700">Total</td>
                        <td class="text-right" style="font-weight:700;font-size:1rem"><?= money((float)$po['total_amount']) ?></td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>

<!-- Line Items + Receive form -->
<?php if ($canReceive): ?>
<form method="POST" action="/purchasing/<?= (int)$po['id'] ?>/receive">
    <?= csrf_field() ?>
<?php endif; ?>

<div class="card" style="padding:0;overflow:hidden;margin-bottom:1.25rem">
    <div style="padding:.875rem 1.25rem;border-bottom:1px solid var(--color-border);display:flex;align-items:center;justify-content:space-between">
        <div style="font-weight:600">Line Items</div>
        <?php if ($totalOrdered > 0): ?>
        <div style="font-size:.8rem;color:var(--color-text-muted)">
            <?= number_format($totalReceived, 2) ?> / <?= number_format($totalOrdered, 2) ?> received
        </div>
        <?php endif; ?>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Product / Description</th>
                    <th class="text-right">Ordered</th>
                    <th class="text-right">Received</th>
                    <th class="text-right">Outstanding</th>
                    <th class="text-right">Unit Cost</th>
                    <th class="text-right">Line Total</th>
                    <?php if ($canReceive): ?>
                        <th class="text-right">Receive Qty</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lines)): ?>
                    <tr><td colspan="<?= $canReceive ? 8 : 7 ?>" class="table__empty">No line items.</td></tr>
                <?php else: ?>
                    <?php foreach ($lines as $line):
                        $outstanding = (float)$line['qty_ordered'] - (float)$line['qty_received'];
                    ?>
                        <tr>
                            <td style="font-family:monospace;font-size:.85rem"><?= e($line['sku'] ?? '—') ?></td>
                            <td>
                                <?php if ($line['product_id']): ?>
                                    <a href="/products/<?= (int)$line['product_id'] ?>"
                                       style="color:var(--color-primary);text-decoration:none;font-size:.875rem">
                                        <?= e($line['product_name']) ?>
                                    </a>
                                <?php endif; ?>
                                <?php if ($line['description']): ?>
                                    <div style="font-size:.8rem;color:var(--color-text-muted)"><?= e($line['description']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-right" style="font-size:.875rem"><?= number_format((float)$line['qty_ordered'], 2) ?></td>
                            <td class="text-right" style="font-size:.875rem;color:<?= (float)$line['qty_received'] > 0 ? '#16a34a' : 'var(--color-text-muted)' ?>">
                                <?= number_format((float)$line['qty_received'], 2) ?>
                            </td>
                            <td class="text-right" style="font-size:.875rem;font-weight:<?= $outstanding > 0 ? '600' : '400' ?>;color:<?= $outstanding > 0 ? '#d97706' : 'var(--color-text-muted)' ?>">
                                <?= number_format($outstanding, 2) ?>
                            </td>
                            <td class="text-right" style="font-size:.875rem"><?= money((float)$line['unit_cost']) ?></td>
                            <td class="text-right" style="font-size:.875rem;font-weight:600"><?= money((float)$line['line_total']) ?></td>
                            <?php if ($canReceive): ?>
                                <td class="text-right" style="padding:.4rem .75rem">
                                    <?php if ($outstanding > 0): ?>
                                        <input type="number" name="receive_qty[<?= (int)$line['id'] ?>]"
                                               step="0.01" min="0"
                                               class="input" style="width:80px;text-align:right;font-size:.85rem"
                                               placeholder="<?= number_format($outstanding, 2, '.', '') ?>">
                                    <?php else: ?>
                                        <?php // No max, and still receivable when the line is full: a batch that
                                              // over-yields is a fact, and refusing to record it does not make it
                                              // untrue — it just moves the problem to the count. ?>
                                        <input type="number" name="receive_qty[<?= (int)$line['id'] ?>]"
                                               step="0.01" min="0"
                                               class="input" style="width:80px;text-align:right;font-size:.85rem"
                                               placeholder="0.00" title="Already complete — anything entered here is an over-delivery">
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($canReceive): ?>
    <div style="padding:.875rem 1.25rem;border-top:1px solid var(--color-border)">
        <table style="width:100%;border-collapse:separate;border-spacing:.6rem 0;margin:0 -.6rem .75rem">
            <tr>
                <td style="width:16rem">
                    <label for="poLoc" style="display:block;font-size:.72rem;font-weight:700;text-transform:uppercase;
                                              letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:.3rem">
                        Putting it in
                    </label>
                    <select name="location_id" id="poLoc" required class="input" style="width:100%;font-size:.875rem">
                        <option value="">— Choose —</option>
                        <?php foreach (($locations ?? []) as $poLocation): ?>
                            <option value="<?= (int)$poLocation['id'] ?>">
                                <?= $poLocation['parent_code'] ? e($poLocation['parent_code']) . ' · ' : '' ?><?= e($poLocation['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <label for="poNotes" style="display:block;font-size:.72rem;font-weight:700;text-transform:uppercase;
                                                letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:.3rem">
                        Notes
                    </label>
                    <input type="text" name="receipt_notes" id="poNotes" maxlength="255" class="input"
                           style="width:100%;font-size:.875rem"
                           placeholder="Damage, short delivery, anything worth knowing">
                </td>
            </tr>
        </table>
        <button type="submit" class="btn btn--primary">Receive &amp; Put Into Stock</button>
        <span style="font-size:.8rem;color:var(--color-text-muted);margin-left:.75rem">
            Enter what actually arrived, even if it differs from the PO — the difference is
            recorded and goes to <a href="/purchasing/variances" style="color:var(--color-primary)">Variances</a>
            for review.
        </span>
    </div>
    <?php endif; ?>
</div>

<?php if ($canReceive): ?>
</form>
<?php endif; ?>

<?php if ($po['memo']): ?>
<div class="card" style="padding:1rem 1.25rem;margin-bottom:1.25rem">
    <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:.4rem">Memo</div>
    <div style="font-size:.875rem"><?= nl2br(e($po['memo'])) ?></div>
</div>
<?php endif; ?>

<?php if ($po['internal_notes']): ?>
<div class="card" style="padding:1rem 1.25rem">
    <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:.4rem">Internal Notes</div>
    <div style="font-size:.875rem"><?= nl2br(e($po['internal_notes'])) ?></div>
</div>
<?php endif; ?>

<!-- Email Modal -->
<div id="emailModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center">
    <div class="card" style="width:100%;max-width:480px;padding:1.5rem;margin:1rem">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
            <div style="font-weight:700;font-size:1.05rem">Email Purchase Order</div>
            <button type="button" onclick="document.getElementById('emailModal').style.display='none'"
                    style="background:none;border:none;font-size:1.25rem;cursor:pointer;color:var(--color-text-muted)">&times;</button>
        </div>
        <form method="POST" action="/purchasing/<?= (int)$po['id'] ?>/email">
            <?= csrf_field() ?>
            <div style="margin-bottom:.9rem">
                <label class="label">Send To <span style="color:var(--color-danger)">*</span></label>
                <input type="email" name="email_to" required class="input" style="width:100%"
                       value="<?= e($po['vendor_email'] ?? '') ?>"
                       placeholder="vendor@example.com">
            </div>
            <div style="margin-bottom:1.25rem">
                <label class="label">Message / Note <span style="font-weight:400;color:var(--color-text-muted)">(optional)</span></label>
                <textarea name="email_note" rows="3" class="input" style="width:100%;resize:vertical"
                          placeholder="Please find our purchase order attached…"></textarea>
            </div>
            <div style="display:flex;gap:.75rem">
                <button type="submit" class="btn btn--primary">Send Email</button>
                <button type="button" class="btn btn--secondary"
                        onclick="document.getElementById('emailModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
