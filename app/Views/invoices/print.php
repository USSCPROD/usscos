<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice #<?= e($invoice['invoice_number']) ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; font-size: 11pt; color: #1a1a1a; background: #fff; }
.page { max-width: 780px; margin: 0 auto; padding: 32px; }

.hdr { display: table; width: 100%; margin-bottom: 28px; }
.hdr-l { display: table-cell; vertical-align: top; width: 55%; }
.hdr-r { display: table-cell; vertical-align: top; text-align: right; }
.company-name { font-size: 20pt; font-weight: 700; color: #222b59; }
.company-info { font-size: 9pt; color: #555; margin-top: 4px; line-height: 1.6; }
.doc-title { font-size: 22pt; font-weight: 700; color: #222b59; }
.doc-num { font-size: 11pt; color: #555; margin-top: 4px; }

.info-row { display: table; width: 100%; margin-bottom: 24px; border-top: 2px solid #222b59; padding-top: 16px; }
.info-col { display: table-cell; vertical-align: top; padding-right: 12px; }
.lbl { font-size: 8pt; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #888; margin-bottom: 4px; }
.val { font-size: 10pt; line-height: 1.55; }

.badge { display: inline-block; padding: 2px 10px; border-radius: 99px; font-size: 9pt; font-weight: 700; text-transform: uppercase; }
.badge-draft    { background:#f3f4f6; color:#374151; }
.badge-sent     { background:#eff6ff; color:#1d4ed8; }
.badge-partial  { background:#fffbeb; color:#92400e; }
.badge-paid     { background:#f0fdf4; color:#166534; }
.badge-overdue  { background:#fef2f2; color:#991b1b; }
.badge-void     { background:#f3f4f6; color:#6b7280; }

table.lines { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
table.lines th { background: #222b59; color: #fff; padding: 7px 10px; font-size: 9pt; text-align: left; font-weight: 600; }
table.lines th.r { text-align: right; }
table.lines td { padding: 7px 10px; font-size: 10pt; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
table.lines td.r { text-align: right; white-space: nowrap; }
table.lines tr:nth-child(even) td { background: #f9fafb; }
.sku { font-family: monospace; font-size: 9pt; color: #666; }

.totals { float: right; width: 240px; margin-bottom: 24px; }
.totals table { width: 100%; border-collapse: collapse; }
.totals td { padding: 5px 0; font-size: 10pt; }
.totals .tlbl { color: #555; }
.totals .tamt { text-align: right; font-weight: 600; }
.totals .grand td { border-top: 2px solid #222b59; padding-top: 8px; font-weight: 700; font-size: 13pt; color: #222b59; }
.clearfix::after { content:''; display:table; clear:both; }

.notes { margin-top: 20px; border-top: 1px solid #e5e7eb; padding-top: 14px; font-size: 10pt; color: #444; line-height: 1.6; }
.nlbl { font-size: 8pt; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #888; margin-bottom: 5px; }

.footer { margin-top: 40px; border-top: 1px solid #e5e7eb; padding-top: 12px; font-size: 8pt; color: #999; text-align: center; }

.terms-box { margin-top: 24px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px 16px; font-size: 10pt; }

@media print {
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .no-print { display: none !important; }
    .page { padding: 16px; }
}
</style>
</head>
<body>

<div class="no-print" style="background:#f3f4f6;padding:10px 32px;display:flex;gap:10px;align-items:center">
    <button onclick="window.print()" style="background:#222b59;color:#fff;border:none;padding:8px 20px;border-radius:5px;font-size:11pt;cursor:pointer;font-weight:600">🖨 Print / Save PDF</button>
    <a href="#" onclick="window.close();return false;" style="color:#222b59;font-size:10pt;text-decoration:none">&larr; Close</a>
</div>

<div class="page">

    <div class="hdr">
        <div class="hdr-l">
            <div class="company-name"><?= e($company['name'] ?? 'US Specialty Coatings') ?></div>
            <div class="company-info">
                <?php if ($company['address_line1'] ?? ''): ?><?= e($company['address_line1']) ?><br><?php endif; ?>
                <?php if ($company['city'] ?? ''): ?><?= e($company['city']) ?><?= ($company['state'] ?? '') ? ', ' . e($company['state']) : '' ?> <?= e($company['postal_code'] ?? '') ?><br><?php endif; ?>
                <?php if ($company['phone'] ?? ''): ?><?= e($company['phone']) ?><br><?php endif; ?>
                <?php if ($company['email'] ?? ''): ?><?= e($company['email']) ?><?php endif; ?>
            </div>
        </div>
        <div class="hdr-r">
            <div class="doc-title">INVOICE</div>
            <div class="doc-num">#<?= e($invoice['invoice_number']) ?></div>
            <div style="margin-top:8px">
                <span class="badge badge-<?= e($invoice['status']) ?>"><?= ucfirst($invoice['status']) ?></span>
            </div>
        </div>
    </div>

    <div class="info-row">
        <div class="info-col" style="width:38%">
            <div class="lbl">Bill To</div>
            <div class="val">
                <strong><?= e($invoice['company_name']) ?></strong><br>
                <?php if ($invoice['bill_address_1'] ?? ''): ?><?= e($invoice['bill_address_1']) ?><br><?php endif; ?>
                <?php if ($invoice['bill_city'] ?? ''): ?><?= e($invoice['bill_city']) ?><?= ($invoice['bill_state'] ?? '') ? ', ' . e($invoice['bill_state']) : '' ?> <?= e($invoice['bill_zip'] ?? '') ?><br><?php endif; ?>
                <?php if ($invoice['email'] ?? ''): ?><?= e($invoice['email']) ?><br><?php endif; ?>
                <?php if ($invoice['phone'] ?? ''): ?><?= e($invoice['phone']) ?><?php endif; ?>
            </div>
        </div>
        <?php if (!empty($invoice['ship_to_name']) || !empty($invoice['ship_to_address_1'])): ?>
        <div class="info-col" style="width:32%">
            <div class="lbl">Ship To</div>
            <div class="val">
                <?= e($invoice['ship_to_name'] ?? '') ?><br>
                <?php if ($invoice['ship_to_address_1'] ?? ''): ?><?= e($invoice['ship_to_address_1']) ?><br><?php endif; ?>
                <?php if ($invoice['ship_to_city'] ?? ''): ?><?= e($invoice['ship_to_city']) ?><?= ($invoice['ship_to_state'] ?? '') ? ', ' . e($invoice['ship_to_state']) : '' ?> <?= e($invoice['ship_to_zip'] ?? '') ?><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <div class="info-col" style="width:30%;padding-right:0">
            <div class="lbl">Details</div>
            <div class="val">
                <table style="border-collapse:collapse">
                    <tr><td style="color:#888;padding-right:10px;padding-bottom:3px">Invoice Date:</td><td style="font-weight:600"><?= e(date('M j, Y', strtotime($invoice['invoice_date']))) ?></td></tr>
                    <tr><td style="color:#888;padding-right:10px;padding-bottom:3px">Due Date:</td><td style="font-weight:600"><?= e(date('M j, Y', strtotime($invoice['due_date']))) ?></td></tr>
                    <?php if ($invoice['payment_term_name'] ?? ''): ?><tr><td style="color:#888;padding-right:10px;padding-bottom:3px">Terms:</td><td><?= e($invoice['payment_term_name']) ?></td></tr><?php endif; ?>
                    <?php if ($invoice['po_number'] ?? ''): ?><tr><td style="color:#888;padding-right:10px">Customer PO:</td><td><?= e($invoice['po_number']) ?></td></tr><?php endif; ?>
                    <?php if (($invoice['rep_first'] ?? '') || ($invoice['rep_last'] ?? '')): ?><tr><td style="color:#888;padding-right:10px">Rep:</td><td><?= e(trim(($invoice['rep_first'] ?? '') . ' ' . ($invoice['rep_last'] ?? ''))) ?></td></tr><?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <table class="lines">
        <thead>
            <tr>
                <th style="width:12%">SKU</th>
                <th>Description</th>
                <th class="r" style="width:9%">Qty</th>
                <th class="r" style="width:13%">Unit Price</th>
                <?php if (!empty($hasDiscount)): ?><th class="r" style="width:10%">Disc</th><?php endif; ?>
                <th class="r" style="width:13%">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($line_items as $li): ?>
            <?php
                $mainDesc = trim($li['product_name'] ?? $li['description'] ?? '');
                $subDesc  = trim($li['description'] ?? '');
                $showSub  = $subDesc !== '' && strtolower($subDesc) !== strtolower($mainDesc);
            ?>
            <tr>
                <td class="sku"><?= e($li['sku'] ?? $li['quickbooks_item'] ?? '') ?></td>
                <td>
                    <?= e($mainDesc) ?>
                    <?php if ($showSub): ?>
                        <div style="font-size:9pt;color:#666;margin-top:2px"><?= e($subDesc) ?></div>
                    <?php endif; ?>
                </td>
                <td class="r"><?= number_format((float)($li['qty'] ?? $li['quantity'] ?? 0), 2) ?> <?= e($li['uom_code'] ?? '') ?></td>
                <td class="r"><?= money((float)$li['unit_price']) ?></td>
                <?php if (!empty($hasDiscount)): ?><td class="r"><?= $li['discount_pct'] ? number_format((float)$li['discount_pct'], 1) . '%' : '' ?></td><?php endif; ?>
                <td class="r"><?= money((float)$li['line_total']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="clearfix">
        <div class="totals">
            <table>
                <tr><td class="tlbl">Subtotal</td><td class="tamt"><?= money((float)$invoice['subtotal']) ?></td></tr>
                <?php if ((float)($invoice['discount_amount'] ?? 0) > 0): ?>
                <tr><td class="tlbl">Discount</td><td class="tamt">(<?= money((float)$invoice['discount_amount']) ?>)</td></tr>
                <?php endif; ?>
                <?php if ((float)($invoice['tax_amount'] ?? 0) > 0): ?>
                <tr><td class="tlbl">Tax</td><td class="tamt"><?= money((float)$invoice['tax_amount']) ?></td></tr>
                <?php endif; ?>
                <?php if ((float)($invoice['shipping_amount'] ?? 0) > 0): ?>
                <tr><td class="tlbl">Shipping</td><td class="tamt"><?= money((float)$invoice['shipping_amount']) ?></td></tr>
                <?php endif; ?>
                <tr class="grand"><td class="tlbl">Total</td><td class="tamt"><?= money((float)$invoice['total_amount']) ?></td></tr>
                <?php if ((float)($invoice['amount_paid'] ?? 0) > 0): ?>
                <tr><td class="tlbl" style="font-size:10pt">Amount Paid</td><td class="tamt" style="color:#16a34a">(<?= money((float)$invoice['amount_paid']) ?>)</td></tr>
                <tr><td class="tlbl" style="font-weight:700">Balance Due</td><td class="tamt" style="color:#dc2626;font-size:13pt"><?= money((float)$invoice['balance_due']) ?></td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <?php if ($invoice['customer_message'] ?? ''): ?>
    <div class="terms-box"><?= e($invoice['customer_message']) ?></div>
    <?php endif; ?>

    <?php if ($invoice['notes'] ?? ''): ?>
    <div class="notes"><div class="nlbl">Notes</div><?= nl2br(e($invoice['notes'])) ?></div>
    <?php endif; ?>

    <div class="footer">
        <?= e($company['name'] ?? 'US Specialty Coatings') ?>
        <?php if ($company['website'] ?? ''): ?> &bull; <?= e($company['website']) ?><?php endif; ?>
        &bull; Generated <?= date('M j, Y') ?>
    </div>

</div>
</body>
</html>
