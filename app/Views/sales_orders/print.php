<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Sales Order #<?= e($so['so_number']) ?></title>
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

.badge { display: inline-block; padding: 2px 10px; border-radius: 99px; font-size: 9pt; font-weight: 700; text-transform: uppercase; }
.badge-draft      { background:#f3f4f6; color:#374151; }
.badge-confirmed  { background:#eff6ff; color:#1d4ed8; }
.badge-processing { background:#fffbeb; color:#92400e; }
.badge-shipped    { background:#f0fdf4; color:#166534; }
.badge-invoiced   { background:#f0fdf4; color:#166534; }
.badge-cancelled  { background:#fef2f2; color:#991b1b; }

.info-row { display: table; width: 100%; margin-bottom: 24px; border-top: 2px solid #222b59; padding-top: 16px; }
.info-col { display: table-cell; vertical-align: top; padding-right: 12px; }
.lbl { font-size: 8pt; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #888; margin-bottom: 4px; }
.val { font-size: 10pt; line-height: 1.55; }

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
            <div class="doc-title">SALES ORDER</div>
            <div class="doc-num">#<?= e($so['so_number']) ?></div>
            <div style="margin-top:8px">
                <span class="badge badge-<?= e($so['status']) ?>"><?= ucfirst($so['status']) ?></span>
            </div>
        </div>
    </div>

    <div class="info-row">
        <div class="info-col" style="width:38%">
            <div class="lbl">Bill To</div>
            <div class="val">
                <strong><?= e($so['company_name']) ?></strong><br>
                <?php if ($so['bill_address_1'] ?? ''): ?><?= e($so['bill_address_1']) ?><br><?php endif; ?>
                <?php if ($so['bill_city'] ?? ''): ?><?= e($so['bill_city']) ?><?= ($so['bill_state'] ?? '') ? ', ' . e($so['bill_state']) : '' ?> <?= e($so['bill_zip'] ?? '') ?><br><?php endif; ?>
                <?php if ($so['email'] ?? ''): ?><?= e($so['email']) ?><br><?php endif; ?>
                <?php if ($so['phone'] ?? ''): ?><?= e($so['phone']) ?><?php endif; ?>
            </div>
        </div>
        <?php if (!empty($so['ship_to_name']) || !empty($so['ship_to_address_1'])): ?>
        <div class="info-col" style="width:32%">
            <div class="lbl">Ship To</div>
            <div class="val">
                <?= e($so['ship_to_name'] ?? $so['company_name']) ?><br>
                <?php if ($so['ship_to_address_1'] ?? ''): ?><?= e($so['ship_to_address_1']) ?><br><?php endif; ?>
                <?php if ($so['ship_to_city'] ?? ''): ?><?= e($so['ship_to_city']) ?><?= ($so['ship_to_state'] ?? '') ? ', ' . e($so['ship_to_state']) : '' ?> <?= e($so['ship_to_zip'] ?? '') ?><?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <div class="info-col" style="width:30%;padding-right:0">
            <div class="lbl">Details</div>
            <div class="val">
                <table style="border-collapse:collapse">
                    <tr><td style="color:#888;padding-right:10px;padding-bottom:3px">Order Date:</td><td style="font-weight:600"><?= e(date('M j, Y', strtotime($so['order_date']))) ?></td></tr>
                    <?php if ($so['requested_ship_date'] ?? ''): ?><tr><td style="color:#888;padding-right:10px;padding-bottom:3px">Ship Date:</td><td style="font-weight:600"><?= e(date('M j, Y', strtotime($so['requested_ship_date']))) ?></td></tr><?php endif; ?>
                    <?php if ($so['po_number'] ?? ''): ?><tr><td style="color:#888;padding-right:10px;padding-bottom:3px">Customer PO:</td><td><?= e($so['po_number']) ?></td></tr><?php endif; ?>
                    <?php if ($so['ship_via_name'] ?? ''): ?><tr><td style="color:#888;padding-right:10px;padding-bottom:3px">Ship Via:</td><td><?= e($so['ship_via_name']) ?></td></tr><?php endif; ?>
                    <?php if (($so['rep_first'] ?? '') || ($so['rep_last'] ?? '')): ?><tr><td style="color:#888;padding-right:10px">Rep:</td><td><?= e(trim(($so['rep_first'] ?? '') . ' ' . ($so['rep_last'] ?? ''))) ?></td></tr><?php endif; ?>
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
                <td class="r"><?= number_format((float)($li['qty_ordered'] ?? $li['qty'] ?? 0), 2) ?> <?= e($li['uom_code'] ?? '') ?></td>
                <td class="r"><?= money((float)$li['unit_price']) ?></td>
                <td class="r"><?= money((float)$li['line_total']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="clearfix">
        <div class="totals">
            <table>
                <tr><td class="tlbl">Subtotal</td><td class="tamt"><?= money((float)$so['subtotal']) ?></td></tr>
                <?php if ((float)($so['tax_amount'] ?? 0) > 0): ?>
                <tr><td class="tlbl">Tax (<?= e($so['tax_rate_name'] ?? '') ?>)</td><td class="tamt"><?= money((float)$so['tax_amount']) ?></td></tr>
                <?php endif; ?>
                <?php if ((float)($so['shipping_amount'] ?? 0) > 0): ?>
                <tr><td class="tlbl">Shipping</td><td class="tamt"><?= money((float)$so['shipping_amount']) ?></td></tr>
                <?php endif; ?>
                <tr class="grand"><td class="tlbl">Total</td><td class="tamt"><?= money((float)$so['total_amount']) ?></td></tr>
            </table>
        </div>
    </div>

    <?php if ($so['customer_message'] ?? ''): ?>
    <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:12px 16px;font-size:10pt;margin-top:8px"><?= e($so['customer_message']) ?></div>
    <?php endif; ?>

    <?php if ($so['notes'] ?? ''): ?>
    <div class="notes"><div class="nlbl">Notes</div><?= nl2br(e($so['notes'])) ?></div>
    <?php endif; ?>

    <div class="footer">
        <?= e($company['name'] ?? 'US Specialty Coatings') ?>
        <?php if ($company['website'] ?? ''): ?> &bull; <?= e($company['website']) ?><?php endif; ?>
        &bull; Generated <?= date('M j, Y') ?>
    </div>

</div>
</body>
</html>
