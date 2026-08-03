<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Packing Slip — Order #<?= e($so['so_number']) ?></title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, sans-serif; font-size: 11pt; color: #1a1a1a; background: #fff; }
.page { max-width: 780px; margin: 0 auto; padding: 32px; }

.hdr { display: table; width: 100%; margin-bottom: 20px; }
.hdr-l { display: table-cell; vertical-align: top; width: 55%; }
.hdr-r { display: table-cell; vertical-align: top; text-align: right; }
.company-name { font-size: 18pt; font-weight: 700; color: #222b59; }
.company-info { font-size: 9pt; color: #555; margin-top: 4px; line-height: 1.6; }
.doc-title { font-size: 20pt; font-weight: 700; color: #222b59; }
.doc-num { font-size: 11pt; color: #555; margin-top: 4px; }

.addr-row { display: table; width: 100%; margin-bottom: 20px; border-top: 2px solid #222b59; padding-top: 14px; }
.addr-col { display: table-cell; vertical-align: top; width: 50%; padding-right: 16px; }
.lbl { font-size: 8pt; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #888; margin-bottom: 5px; }
.val { font-size: 10pt; line-height: 1.6; }

.details-bar { background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 4px; padding: 8px 14px; margin-bottom: 18px; font-size: 10pt; }
.details-bar table { width: 100%; border-collapse: collapse; }
.details-bar td { padding: 2px 12px 2px 0; }
.details-bar .dlbl { color: #666; font-size: 9pt; }

table.lines { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
table.lines th { background: #222b59; color: #fff; padding: 7px 10px; font-size: 9pt; text-align: left; font-weight: 600; }
table.lines th.r { text-align: right; }
table.lines td { padding: 8px 10px; font-size: 10pt; border-bottom: 1px solid #e5e7eb; vertical-align: middle; }
table.lines td.r { text-align: right; }
table.lines tr:nth-child(even) td { background: #f9fafb; }
.sku { font-family: monospace; font-size: 9pt; color: #666; }

/* Check boxes for warehouse */
.qty-check { display: inline-block; width: 28px; height: 28px; border: 2px solid #374151; border-radius: 4px; vertical-align: middle; }

.sig-row { display: table; width: 100%; margin-top: 32px; }
.sig-col { display: table-cell; width: 45%; padding-right: 24px; vertical-align: bottom; }
.sig-line { border-top: 1px solid #1a1a1a; margin-top: 40px; padding-top: 4px; font-size: 9pt; color: #555; }

.footer { margin-top: 32px; border-top: 1px solid #e5e7eb; padding-top: 12px; font-size: 8pt; color: #999; text-align: center; }

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
                <?php if ($company['phone'] ?? ''): ?><?= e($company['phone']) ?><?php endif; ?>
            </div>
        </div>
        <div class="hdr-r">
            <div class="doc-title">PACKING SLIP</div>
            <div class="doc-num">Order #<?= e($so['so_number']) ?></div>
            <div style="font-size:9pt;color:#888;margin-top:6px"><?= date('M j, Y') ?></div>
        </div>
    </div>

    <!-- Addresses -->
    <div class="addr-row">
        <div class="addr-col">
            <div class="lbl">Ship To</div>
            <div class="val">
                <strong><?= e($so['ship_to_name'] ?? $so['company_name']) ?></strong><br>
                <?php $addr = $so['ship_to_address_1'] ?? $so['bill_address_1'] ?? ''; ?>
                <?php if ($addr): ?><?= e($addr) ?><br><?php endif; ?>
                <?php
                $city  = $so['ship_to_city']  ?? $so['bill_city']  ?? '';
                $state = $so['ship_to_state'] ?? $so['bill_state'] ?? '';
                $zip   = $so['ship_to_zip']   ?? $so['bill_zip']   ?? '';
                if ($city): ?><?= e($city) ?><?= $state ? ', ' . e($state) : '' ?> <?= e($zip) ?><?php endif; ?>
            </div>
        </div>
        <div class="addr-col" style="padding-right:0">
            <div class="lbl">Order Info</div>
            <div class="val">
                <?php if ($so['po_number'] ?? ''): ?><strong>Customer PO:</strong> <?= e($so['po_number']) ?><br><?php endif; ?>
                <?php if ($so['requested_ship_date'] ?? ''): ?><strong>Ship Date:</strong> <?= e(date('M j, Y', strtotime($so['requested_ship_date']))) ?><br><?php endif; ?>
                <?php if ($so['ship_via_name'] ?? ''): ?><strong>Ship Via:</strong> <?= e($so['ship_via_name']) ?><br><?php endif; ?>
                <?php if (($so['ship_to_phone'] ?? '') || ($so['phone'] ?? '')): ?>
                    <strong>Phone:</strong> <?= e($so['ship_to_phone'] ?? $so['phone'] ?? '') ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Line items — NO prices on packing slip -->
    <table class="lines">
        <thead>
            <tr>
                <th style="width:14%">SKU</th>
                <th>Item Description</th>
                <th class="r" style="width:12%">Ordered</th>
                <th class="r" style="width:12%">Shipped</th>
                <th class="r" style="width:10%">&#10003;</th>
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
                <td class="r"><?= number_format((float)($li['qty_ordered'] ?? $li['qty'] ?? 0), 2) ?> <?= e($li['uom_code'] ?? '') ?></td>
                <td class="r"><span class="qty-check"></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($so['notes'] ?? ''): ?>
    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:4px;padding:10px 14px;font-size:10pt;margin-bottom:20px">
        <strong>Note:</strong> <?= nl2br(e($so['notes'])) ?>
    </div>
    <?php endif; ?>

    <!-- Signature lines -->
    <div class="sig-row">
        <div class="sig-col">
            <div class="sig-line">Packed By</div>
        </div>
        <div class="sig-col">
            <div class="sig-line">Checked By</div>
        </div>
    </div>

    <div class="footer">
        <?= e($company['name'] ?? 'US Specialty Coatings') ?> &bull; Thank you for your business!
    </div>

</div>
</body>
</html>
