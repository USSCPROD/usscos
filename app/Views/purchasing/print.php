<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>PO <?= e($po['po_number']) ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; font-size: 11pt; color: #1a1a1a; background: #fff; }

.page { max-width: 780px; margin: 0 auto; padding: 32px; }

/* Header */
.po-header { display: table; width: 100%; margin-bottom: 28px; }
.po-header-left { display: table-cell; vertical-align: top; width: 60%; }
.po-header-right { display: table-cell; vertical-align: top; text-align: right; }
.company-name { font-size: 20pt; font-weight: 700; color: #222b59; }
.company-info { font-size: 9pt; color: #555; margin-top: 4px; line-height: 1.5; }
.po-title { font-size: 22pt; font-weight: 700; color: #222b59; }
.po-number { font-size: 11pt; color: #555; margin-top: 4px; }

/* Info grid */
.info-grid { display: table; width: 100%; margin-bottom: 24px; border-top: 2px solid #222b59; padding-top: 16px; }
.info-col { display: table-cell; vertical-align: top; width: 33.3%; padding-right: 12px; }
.info-label { font-size: 8pt; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #888; margin-bottom: 4px; }
.info-value { font-size: 10pt; line-height: 1.5; }

/* Status badge */
.status-badge { display: inline-block; padding: 2px 10px; border-radius: 99px; font-size: 9pt; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
.status-draft     { background: #f3f4f6; color: #374151; }
.status-sent      { background: #eff6ff; color: #1d4ed8; }
.status-partial   { background: #fffbeb; color: #92400e; }
.status-received  { background: #f0fdf4; color: #166534; }
.status-closed    { background: #f3f4f6; color: #374151; }
.status-cancelled { background: #fef2f2; color: #991b1b; }

/* Table */
.lines-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.lines-table th { background: #222b59; color: #fff; padding: 7px 10px; font-size: 9pt; text-align: left; font-weight: 600; }
.lines-table th.right { text-align: right; }
.lines-table td { padding: 7px 10px; font-size: 10pt; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
.lines-table td.right { text-align: right; }
.lines-table tr:nth-child(even) td { background: #f9fafb; }
.sku { font-family: monospace; font-size: 9pt; color: #555; }

/* Totals */
.totals { float: right; width: 240px; margin-bottom: 24px; }
.totals table { width: 100%; border-collapse: collapse; }
.totals td { padding: 5px 0; font-size: 10pt; }
.totals td.label { color: #555; }
.totals td.amount { text-align: right; font-weight: 600; }
.totals tr.grand td { border-top: 2px solid #222b59; padding-top: 8px; font-weight: 700; font-size: 12pt; color: #222b59; }
.clearfix::after { content: ''; display: table; clear: both; }

/* Notes */
.notes-section { margin-top: 24px; border-top: 1px solid #e5e7eb; padding-top: 16px; }
.notes-label { font-size: 9pt; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #888; margin-bottom: 6px; }
.notes-text { font-size: 10pt; line-height: 1.6; color: #444; }

/* Footer */
.po-footer { margin-top: 40px; border-top: 1px solid #e5e7eb; padding-top: 12px; font-size: 8pt; color: #999; text-align: center; }

/* Signature line */
.sig-block { display: table; width: 100%; margin-top: 32px; }
.sig-col { display: table-cell; width: 45%; vertical-align: bottom; padding-right: 24px; }
.sig-line { border-top: 1px solid #1a1a1a; margin-top: 36px; padding-top: 4px; font-size: 9pt; color: #555; }

@media print {
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .no-print { display: none !important; }
    .page { padding: 16px; }
}
</style>
</head>
<body>

<!-- Print / Back buttons (hidden when printing) -->
<div class="no-print" style="background:#f3f4f6;padding:10px 32px;display:flex;gap:10px;align-items:center">
    <button onclick="window.print()" style="background:#222b59;color:#fff;border:none;padding:8px 20px;border-radius:5px;font-size:11pt;cursor:pointer;font-weight:600">
        🖨 Print / Save PDF
    </button>
    <a href="#" onclick="window.close();return false;" style="color:#222b59;font-size:10pt;text-decoration:none">&larr; Close</a>
</div>

<div class="page">

    <!-- Header -->
    <div class="po-header">
        <div class="po-header-left">
            <div class="company-name"><?= e($company['name'] ?? 'US Specialty Coatings') ?></div>
            <div class="company-info">
                <?php if ($company['address_line1'] ?? ''): ?>
                    <?= e($company['address_line1']) ?><br>
                <?php endif; ?>
                <?php if ($company['city'] ?? ''): ?>
                    <?= e($company['city']) ?><?= ($company['state'] ?? '') ? ', ' . e($company['state']) : '' ?> <?= e($company['postal_code'] ?? '') ?><br>
                <?php endif; ?>
                <?php if ($company['phone'] ?? ''): ?>
                    <?= e($company['phone']) ?><br>
                <?php endif; ?>
                <?php if ($company['email'] ?? ''): ?>
                    <?= e($company['email']) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="po-header-right">
            <div class="po-title">PURCHASE ORDER</div>
            <div class="po-number"><?= e($po['po_number']) ?></div>
            <div style="margin-top:8px">
                <span class="status-badge status-<?= e($po['status']) ?>"><?= ucfirst($po['status']) ?></span>
            </div>
        </div>
    </div>

    <!-- Info Grid -->
    <div class="info-grid">
        <div class="info-col">
            <div class="info-label">Vendor</div>
            <div class="info-value">
                <strong><?= e($po['vendor_name']) ?></strong><br>
                <?php if ($po['vendor_address_1'] ?? ''): ?>
                    <?= e($po['vendor_address_1']) ?><br>
                <?php endif; ?>
                <?php if ($po['vendor_city'] ?? ''): ?>
                    <?= e($po['vendor_city']) ?><?= ($po['vendor_state'] ?? '') ? ', ' . e($po['vendor_state']) : '' ?> <?= e($po['vendor_zip'] ?? '') ?><br>
                <?php endif; ?>
                <?php if ($po['vendor_phone'] ?? ''): ?><?= e($po['vendor_phone']) ?><br><?php endif; ?>
                <?php if ($po['vendor_email'] ?? ''): ?><?= e($po['vendor_email']) ?><?php endif; ?>
                <?php if ($po['vendor_account_number'] ?? ''): ?>
                    <br><span style="color:#888;font-size:9pt">Acct: <?= e($po['vendor_account_number']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="info-col">
            <div class="info-label">Ship To</div>
            <div class="info-value">
                <?= e($po['ship_to_name'] ?? $company['name'] ?? '') ?><br>
                <?php if ($po['ship_to_address_1'] ?? ''): ?>
                    <?= e($po['ship_to_address_1']) ?><br>
                <?php endif; ?>
                <?php if ($po['ship_to_city'] ?? ''): ?>
                    <?= e($po['ship_to_city']) ?><?= ($po['ship_to_state'] ?? '') ? ', ' . e($po['ship_to_state']) : '' ?> <?= e($po['ship_to_zip'] ?? '') ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="info-col" style="padding-right:0">
            <div class="info-label">Dates</div>
            <div class="info-value">
                <table style="border-collapse:collapse">
                    <tr>
                        <td style="color:#888;padding-right:10px;padding-bottom:3px">Order Date:</td>
                        <td style="font-weight:600"><?= e(date('M j, Y', strtotime($po['order_date']))) ?></td>
                    </tr>
                    <?php if ($po['expected_date'] ?? ''): ?>
                    <tr>
                        <td style="color:#888;padding-right:10px;padding-bottom:3px">Expected:</td>
                        <td style="font-weight:600"><?= e(date('M j, Y', strtotime($po['expected_date']))) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($po['vendor_ref'] ?? ''): ?>
                    <tr>
                        <td style="color:#888;padding-right:10px">Vendor Ref:</td>
                        <td><?= e($po['vendor_ref']) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Line Items -->
    <table class="lines-table">
        <thead>
            <tr>
                <th style="width:12%">SKU</th>
                <th>Description</th>
                <th class="right" style="width:10%">Qty</th>
                <th class="right" style="width:13%">Unit Cost</th>
                <th class="right" style="width:13%">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lines as $line): ?>
            <tr>
                <td class="sku"><?= e($line['sku'] ?? '') ?></td>
                <td>
                    <?= e($line['product_name'] ?? '') ?>
                    <?php if (!empty($line['description']) && $line['description'] !== $line['product_name']): ?>
                        <div style="font-size:9pt;color:#666;margin-top:2px"><?= e($line['description']) ?></div>
                    <?php endif; ?>
                </td>
                <td class="right"><?= number_format((float)$line['qty_ordered'], 2) ?></td>
                <td class="right"><?= money((float)$line['unit_cost']) ?></td>
                <td class="right"><?= money((float)$line['line_total']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Totals -->
    <div class="clearfix">
        <div class="totals">
            <table>
                <tr>
                    <td class="label">Subtotal</td>
                    <td class="amount"><?= money((float)$po['subtotal']) ?></td>
                </tr>
                <?php if ((float)$po['tax_amount'] > 0): ?>
                <tr>
                    <td class="label">Tax</td>
                    <td class="amount"><?= money((float)$po['tax_amount']) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ((float)$po['shipping_cost'] > 0): ?>
                <tr>
                    <td class="label">Shipping</td>
                    <td class="amount"><?= money((float)$po['shipping_cost']) ?></td>
                </tr>
                <?php endif; ?>
                <tr class="grand">
                    <td class="label">Total</td>
                    <td class="amount"><?= money((float)$po['total_amount']) ?></td>
                </tr>
            </table>
        </div>
    </div>

    <?php if ($po['memo'] ?? ''): ?>
    <div class="notes-section">
        <div class="notes-label">Notes / Special Instructions</div>
        <div class="notes-text"><?= nl2br(e($po['memo'])) ?></div>
    </div>
    <?php endif; ?>

    <!-- Signature -->
    <div class="sig-block" style="margin-top:40px">
        <div class="sig-col">
            <div class="sig-line">Authorized Signature</div>
        </div>
        <div class="sig-col">
            <div class="sig-line">Date</div>
        </div>
    </div>

    <div class="po-footer">
        <?= e($company['name'] ?? 'US Specialty Coatings') ?>
        <?php if ($company['website'] ?? ''): ?> &bull; <?= e($company['website']) ?><?php endif; ?>
        &bull; Generated <?= date('M j, Y') ?>
    </div>

</div>

<script>
// Auto-open print dialog when loaded directly
if (window.location.search.indexOf('autoprint') !== -1) {
    window.onload = function() { window.print(); };
}
</script>
</body>
</html>
