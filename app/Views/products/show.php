<?php ob_start(); ?>
<?php
$pr        = $product;
$images    = $images       ?? [];
$documents = $documents    ?? [];
$mainImage = $primaryImage ?? null;

$flash      = \App\Core\Session::getFlash('success');
$flashError = \App\Core\Session::getFlash('error');

/* ---------- view helpers (guarded so they can never collide) ---------- */
if (!function_exists('pfield')) {
    function pfield(string $label, $value, string $prefix = '', string $suffix = ''): void {
        $display = ($value !== null && $value !== '')
            ? e($prefix . $value . $suffix)
            : '<span class="text-muted">—</span>';
        echo '<tr>'
           . '<td class="text-muted" style="width:42%;padding:.45rem .6rem;font-size:.875rem;vertical-align:top">' . $label . '</td>'
           . '<td style="padding:.45rem .6rem;font-size:.875rem;vertical-align:top">' . $display . '</td>'
           . '</tr>';
    }
}
if (!function_exists('pmoney')) {
    function pmoney($v): string {
        return ($v !== null && $v !== '') ? '$' . number_format((float)$v, 2) : '<span class="text-muted">—</span>';
    }
}
if (!function_exists('pDims')) {
    /** Join dimension parts as "10.00 × 11.00 × 9.00 Inches" */
    function pDims(array $parts, ?string $unit): ?string {
        $vals = array_values(array_filter($parts, fn($v) => $v !== null && $v !== ''));
        if (empty($vals)) return null;
        return implode(' × ', array_map(fn($v) => number_format((float)$v, 2), $vals))
             . ($unit ? ' ' . $unit : '');
    }
}
if (!function_exists('pWeight')) {
    function pWeight($v, ?string $unit): ?string {
        if ($v === null || $v === '') return null;
        return number_format((float)$v, 2) . ($unit ? ' ' . $unit : '');
    }
}
if (!function_exists('pQty')) {
    /** Quantities are DECIMAL(12,4) — show at most 2 places, no trailing zeros. */
    function pQty($value): string {
        if ($value === null || $value === '') return '0';
        if (!is_numeric($value))              return (string)$value;

        $s = number_format(round((float)$value, 2), 2, '.', '');
        // Only strip zeros after a decimal point — otherwise "60" becomes "6"
        if (str_contains($s, '.')) {
            $s = rtrim(rtrim($s, '0'), '.');
        }
        return $s;
    }
}
if (!function_exists('pFileSize')) {
    function pFileSize(?int $bytes): string {
        if (!$bytes) return '—';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)    return number_format($bytes / 1024, 0) . ' KB';
        return $bytes . ' B';
    }
}

$docTypeLabels = [
    'sds'         => 'SDS',
    'tds'         => 'TDS',
    'sales_flyer' => 'Sales Flyer',
    'catalog'     => 'Catalog',
    'color_chart' => 'Color Chart',
    'spec_sheet'  => 'Spec Sheet',
    'certificate' => 'Certificate',
    'other'       => 'Other',
];

$cardStyle = 'background:#fff;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:1.25rem';
$cardHead  = 'padding:.7rem 1rem;border-bottom:1px solid #e5e7eb;font-size:.72rem;font-weight:700;'
           . 'text-transform:uppercase;letter-spacing:.06em;color:#6b7280';
$colStyle  = 'vertical-align:top;padding:0';
?>

<?php if ($flash): ?>
    <div class="alert alert--success" style="margin-bottom:1rem"><?= e($flash) ?></div>
<?php endif; ?>
<?php if ($flashError): ?>
    <div class="alert alert--danger" style="margin-bottom:1rem"><?= e($flashError) ?></div>
<?php endif; ?>

<!-- Header -->
<table style="width:100%;border-collapse:collapse;margin-bottom:1.25rem">
    <tr>
        <td style="vertical-align:top">
            <div style="font-size:.85rem;color:#6b7280;margin-bottom:.25rem">
                <a href="/products" style="color:inherit">Products</a> &rsaquo; <?= e($pr['sku']) ?>
            </div>
            <h1 class="page-title" style="margin:0"><?= e($pr['name']) ?></h1>
            <div style="margin-top:.45rem">
                <?php if (!empty($pr['brand_name'])): ?>
                    <span class="badge badge--info"><?= e($pr['brand_name']) ?></span>
                <?php endif; ?>
                <?php if (!empty($pr['category'])): ?>
                    <span class="badge badge--neutral"><?= e($pr['category']) ?></span>
                <?php endif; ?>
                <?php if (!empty($pr['color'])): ?>
                    <span class="badge badge--neutral"><?= e($pr['color']) ?></span>
                <?php endif; ?>
                <span class="badge <?= $pr['is_active'] ? 'badge--success' : 'badge--neutral' ?>">
                    <?= $pr['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
                <?php if (!empty($pr['is_hazmat'])): ?>
                    <span class="badge badge--warning">Hazmat</span>
                <?php endif; ?>
            </div>
        </td>
        <td style="vertical-align:top;text-align:right;white-space:nowrap">
            <a href="/products/<?= (int)$pr['id'] ?>/edit" class="btn btn--secondary">Edit</a>
        </td>
    </tr>
</table>

<!-- KPI row -->
<table style="width:100%;border-collapse:separate;border-spacing:1rem 0;margin:0 -1rem 1rem">
    <tr>
        <?php
        $onHand = number_format((float)($pr['qty_on_hand'] ?? 0), 0)
                . (!empty($pr['uom_code']) ? ' ' . e($pr['uom_code']) : '');
        $kpis = [
            ['Retail Price', pmoney($pr['price'])],
            ['Dist Price',   pmoney($pr['dist_price'])],
            ['Cost',         pmoney($pr['cost'])],
            ['On Hand',      $onHand],
        ];
        foreach ($kpis as [$label, $val]): ?>
            <td style="width:25%;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:.9rem 1rem;text-align:center">
                <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.3rem"><?= $label ?></div>
                <div style="font-size:1.3rem;font-weight:600"><?= $val ?></div>
            </td>
        <?php endforeach; ?>
    </tr>
</table>

<!-- ============ ROW 1: Image/Gallery | Identity | Pricing+Accounting+Inventory ============ -->
<table style="width:100%;border-collapse:separate;border-spacing:1.25rem 0;margin:0 -1.25rem">
    <tr>

        <!-- ---------- Column 1: product image + gallery ---------- -->
        <td style="<?= $colStyle ?>;width:32%">

            <div style="<?= $cardStyle ?>">
                <div style="<?= $cardHead ?>">Product Image</div>
                <div style="padding:1rem;text-align:center">
                    <?php if ($mainImage): ?>
                        <img src="<?= e($mainImage['file_path']) ?>" alt="<?= e($pr['name']) ?>"
                             style="max-width:100%;max-height:300px;border-radius:6px">
                    <?php elseif (!empty($pr['website_image_url'])): ?>
                        <img src="<?= e($pr['website_image_url']) ?>" alt="<?= e($pr['name']) ?>"
                             style="max-width:100%;max-height:300px;border-radius:6px">
                    <?php else: ?>
                        <div style="padding:3rem 1rem;color:#9ca3af;font-size:.875rem;background:#f8f9fb;border-radius:6px">
                            No image uploaded
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div style="<?= $cardStyle ?>">
                <div style="<?= $cardHead ?>">
                    Gallery<?= count($images) ? ' (' . count($images) . ')' : '' ?>
                </div>

                <?php if (!empty($images)): ?>
                <div style="padding:.75rem">
                    <table style="width:100%;border-collapse:separate;border-spacing:.4rem">
                        <?php foreach (array_chunk($images, 3) as $rowImgs): ?>
                        <tr>
                            <?php foreach ($rowImgs as $img): ?>
                            <td style="width:33.33%;vertical-align:top;text-align:center">
                                <a href="<?= e($img['file_path']) ?>" target="_blank">
                                    <img src="<?= e($img['file_path']) ?>" alt="<?= e($img['caption'] ?? '') ?>"
                                         style="width:100%;height:70px;object-fit:cover;border-radius:5px;border:2px solid <?= (int)$img['is_primary'] === 1 ? '#222b59' : '#e5e7eb' ?>">
                                </a>
                                <div style="margin-top:.2rem;font-size:.68rem;line-height:1.5">
                                    <?php if ((int)$img['is_primary'] === 1): ?>
                                        <span style="color:#222b59;font-weight:700">Main</span>
                                    <?php else: ?>
                                        <form method="POST" action="/products/<?= (int)$pr['id'] ?>/images/<?= (int)$img['id'] ?>/primary" style="display:inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" style="background:none;border:none;padding:0;color:#0A3D91;cursor:pointer;font-size:.68rem;text-decoration:underline">Set main</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" action="/products/<?= (int)$pr['id'] ?>/images/<?= (int)$img['id'] ?>/delete" style="display:inline"
                                          onsubmit="return confirm('Delete this image?')">
                                        <?= csrf_field() ?>
                                        <button type="submit" title="Delete image"
                                                style="background:none;border:none;padding:0 0 0 .3rem;color:#dc2626;cursor:pointer;font-size:.68rem;text-decoration:underline">Delete</button>
                                    </form>
                                </div>
                            </td>
                            <?php endforeach; ?>
                            <?php for ($i = count($rowImgs); $i < 3; $i++): ?>
                                <td style="width:33.33%"></td>
                            <?php endfor; ?>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php else: ?>
                    <div style="padding:1.25rem 1rem;text-align:center;color:#9ca3af;font-size:.85rem">No images yet.</div>
                <?php endif; ?>

                <!-- Upload image -->
                <div style="padding:.75rem 1rem;border-top:1px solid #e5e7eb;background:#fafafa">
                    <form method="POST" action="/products/<?= (int)$pr['id'] ?>/images" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif,.webp" required
                               style="width:100%;font-size:.8rem;margin-bottom:.4rem">
                        <input type="text" name="caption" placeholder="Caption (optional)"
                               style="width:100%;padding:.4rem .5rem;border:1px solid #d1d5db;border-radius:5px;font-size:.8rem;box-sizing:border-box;margin-bottom:.4rem">
                        <button type="submit" class="btn btn--sm btn--secondary" style="width:100%">Upload Image</button>
                        <div style="font-size:.7rem;color:#9ca3af;margin-top:.3rem">JPG, PNG, GIF or WEBP · max 10 MB</div>
                    </form>
                </div>
            </div>

        </td>

        <!-- ---------- Column 2: identity ---------- -->
        <td style="<?= $colStyle ?>;width:34%">
            <div style="<?= $cardStyle ?>">
                <div style="<?= $cardHead ?>">Identity</div>
                <table style="width:100%;border-collapse:collapse">
                    <?php
                    pfield('SKU', $pr['sku']);
                    pfield('Product Line', $pr['product_line']);
                    pfield('Color', $pr['color']);
                    pfield('Brand', $pr['brand_name']);
                    ?>
                    <tr>
                        <td class="text-muted" style="width:42%;padding:.45rem .6rem;font-size:.875rem;vertical-align:top">Categories</td>
                        <td style="padding:.45rem .6rem;vertical-align:top">
                            <?php if (!empty($productCategories)): ?>
                                <?php foreach ($productCategories as $pc): ?>
                                    <a href="/categories/<?= (int)$pc['id'] ?>/edit"
                                       class="badge <?= (int)$pc['is_primary'] === 1 ? 'badge--info' : 'badge--neutral' ?>"
                                       style="font-size:.72rem;text-decoration:none;margin-bottom:.15rem;display:inline-block"
                                       title="<?= (int)$pc['is_primary'] === 1 ? 'Primary category' : '' ?>">
                                        <?= !empty($pc['parent_name']) ? e($pc['parent_name']) . ' › ' : '' ?><?= e($pc['name']) ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-muted">— </span>
                                <a href="/products/<?= (int)$pr['id'] ?>/edit" style="font-size:.78rem;color:#0A3D91">assign</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                    pfield('Brand Category', $pr['brand_category']);
                    pfield('UOM', $pr['uom_code']);
                    pfield('Pack Level', $pr['pack_level']);
                    ?>
                    <?php if (!empty($pr['sports'])): ?>
                    <tr>
                        <td class="text-muted" style="width:42%;padding:.45rem .6rem;font-size:.875rem;vertical-align:top">Sport</td>
                        <td style="padding:.45rem .6rem">
                            <?php foreach (array_filter(array_map('trim', explode(',', $pr['sports']))) as $sport): ?>
                                <span class="badge badge--info" style="font-size:.72rem"><?= e($sport) ?></span>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="text-muted" style="width:42%;padding:.45rem .6rem;font-size:.875rem;vertical-align:top">Short Description</td>
                        <td style="padding:.45rem .6rem;font-size:.875rem;vertical-align:top">
                            <?php if (!empty($pr['short_description'])): ?>
                                <?= e($pr['short_description']) ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted" style="width:42%;padding:.45rem .6rem;font-size:.875rem;vertical-align:top">Long Description</td>
                        <td style="padding:.45rem .6rem;font-size:.83rem;color:#4b5563;line-height:1.55;vertical-align:top">
                            <?php if (!empty($pr['long_description'])): ?>
                                <?= nl2br(e($pr['long_description'])) ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </td>

        <!-- ---------- Column 3: pricing / accounting / inventory ---------- -->
        <td style="<?= $colStyle ?>;width:34%">

            <div style="<?= $cardStyle ?>">
                <div style="<?= $cardHead ?>">Pricing</div>
                <table style="width:100%;border-collapse:collapse">
                    <tr>
                        <td class="text-muted" style="width:42%;padding:.45rem .6rem;font-size:.875rem">Retail Price</td>
                        <td style="padding:.45rem .6rem;font-size:.875rem"><?= pmoney($pr['price']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted" style="padding:.45rem .6rem;font-size:.875rem">Dist Price (QB)</td>
                        <td style="padding:.45rem .6rem;font-size:.875rem"><?= pmoney($pr['dist_price']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted" style="padding:.45rem .6rem;font-size:.875rem">Dist Price 2026</td>
                        <td style="padding:.45rem .6rem;font-size:.875rem"><?= pmoney($pr['dist_price_2026']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted" style="padding:.45rem .6rem;font-size:.875rem">Stocking Dist</td>
                        <td style="padding:.45rem .6rem;font-size:.875rem"><?= pmoney($pr['stocking_dist']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted" style="padding:.45rem .6rem;font-size:.875rem">Website Price</td>
                        <td style="padding:.45rem .6rem;font-size:.875rem"><?= pmoney($pr['website_price'] ?? null) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted" style="padding:.45rem .6rem;font-size:.875rem">Amazon Price</td>
                        <td style="padding:.45rem .6rem;font-size:.875rem"><?= pmoney($pr['amazon_price'] ?? null) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted" style="padding:.45rem .6rem;font-size:.875rem">Cost</td>
                        <td style="padding:.45rem .6rem;font-size:.875rem"><?= pmoney($pr['cost']) ?></td>
                    </tr>
                    <?php if (!empty($pr['price']) && !empty($pr['cost'])): ?>
                    <tr>
                        <td class="text-muted" style="padding:.45rem .6rem;font-size:.875rem">Margin</td>
                        <td style="padding:.45rem .6rem;font-size:.875rem">
                            <?php $margin = ((float)$pr['price'] - (float)$pr['cost']) / (float)$pr['price'] * 100; ?>
                            <span style="font-weight:600;color:<?= $margin < 20 ? '#d97706' : '#16a34a' ?>">
                                <?= number_format($margin, 1) ?>%
                            </span>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <div style="<?= $cardStyle ?>">
                <div style="<?= $cardHead ?>">Accounting</div>
                <table style="width:100%;border-collapse:collapse">
                    <?php
                    pfield('Income Account', $pr['income_account']);
                    pfield('COGS Account',   $pr['cogs_account']);
                    pfield('Asset Account',  $pr['asset_account']);
                    pfield('Tax Agency',     $pr['tax_agency']);
                    pfield('Taxable',        $pr['is_taxable'] ? 'Yes' : 'No');
                    ?>
                </table>
            </div>

            <div style="<?= $cardStyle ?>">
                <div style="<?= $cardHead ?>">Inventory</div>
                <table style="width:100%;border-collapse:collapse">
                    <?php
                    pfield('On Hand',        pQty($pr['qty_on_hand'] ?? 0));
                    pfield('On Sales Order', pQty($pr['qty_on_sales_order'] ?? 0));
                    pfield('On PO',          pQty($pr['qty_on_po'] ?? 0));
                    pfield('Reorder Point',  $pr['reorder_point']  !== null ? pQty($pr['reorder_point'])  : null);
                    pfield('Min Order Qty',  $pr['min_order_qty']  !== null ? pQty($pr['min_order_qty'])  : null);
                    pfield('Lead Time',      $pr['lead_time_days'], '', $pr['lead_time_days'] ? ' days' : '');
                    ?>
                </table>
            </div>

        </td>
    </tr>
</table>

<!-- ============ ROW 2: Vendor/Barcodes | Dimensions | Packaging ============ -->
<table style="width:100%;border-collapse:separate;border-spacing:1.25rem 0;margin:0 -1.25rem">
    <tr>
        <td style="<?= $colStyle ?>;width:33.33%">
            <div style="<?= $cardStyle ?>">
                <div style="<?= $cardHead ?>">Vendor &amp; Barcodes</div>
                <table style="width:100%;border-collapse:collapse">
                    <?php
                    pfield('Preferred Vendor', $pr['preferred_vendor_name']);
                    pfield('Vendor Part #',    $pr['vendor_part_number']);
                    pfield('MPN',              $pr['mpn']);
                    pfield('GTIN-12 (UPC)',    $pr['gtin12']);
                    pfield('GTIN-14',          $pr['gtin14']);
                    pfield('ASIN',             $pr['asin']);
                    ?>
                </table>
            </div>
        </td>

        <td style="<?= $colStyle ?>;width:33.33%">
            <div style="<?= $cardStyle ?>">
                <div style="<?= $cardHead ?>">GS1 / Dimensions</div>
                <table style="width:100%;border-collapse:collapse">
                    <?php
                    pfield('Dimensions (H×W×D)', pDims([$pr['height'], $pr['width'], $pr['depth']], $pr['dim_unit']));
                    pfield('Unit Dimensions (H×W×D)', pDims(
                        [$pr['unit_height'] ?? null, $pr['unit_width'] ?? null, $pr['unit_depth'] ?? null],
                        $pr['dim_unit']
                    ));
                    pfield('Gross Weight',      pWeight($pr['gross_weight'], $pr['weight_unit']));
                    pfield('Net Weight',        pWeight($pr['net_weight'],   $pr['weight_unit']));
                    pfield('Unit Gross Weight', pWeight($pr['unit_gross_weight'] ?? null, $pr['weight_unit']));
                    pfield('Unit Net Weight',   pWeight($pr['unit_net_weight']   ?? null, $pr['weight_unit']));
                    pfield('GS1 Status',        $pr['gs1_status']);
                    ?>
                </table>
            </div>
        </td>

        <td style="<?= $colStyle ?>;width:33.33%">
            <div style="<?= $cardStyle ?>">
                <div style="<?= $cardHead ?>">Packaging</div>
                <table style="width:100%;border-collapse:collapse">
                    <?php
                    pfield('Pack Level',          $pr['pack_level']);
                    pfield('Units Per Case',      $pr['units_per_case'] ?? null);
                    pfield('Case Dimensions (L×W×H)', pDims(
                        [$pr['case_length'] ?? null, $pr['case_width'] ?? null, $pr['case_height'] ?? null],
                        $pr['dim_unit']
                    ));
                    pfield('Case Weight (Gross)', pWeight($pr['case_weight_gross'] ?? null, $pr['weight_unit']));
                    pfield('Case Weight (Net)',   pWeight($pr['case_weight_net']   ?? null, $pr['weight_unit']));
                    $ti = $pr['pallet_ti'] ?? null; $hi = $pr['pallet_hi'] ?? null;
                    pfield('Pallet TI / HI', ($ti || $hi) ? (($ti ?: '—') . ' / ' . ($hi ?: '—')) : null);
                    pfield('Pallet Qty', $pr['pallet_qty'] ?? null, '', !empty($pr['pallet_qty']) ? ' Cases' : '');
                    ?>
                </table>
            </div>
        </td>
    </tr>
</table>

<!-- ============ ROW 3: Descriptions | Documents ============ -->
<table style="width:100%;border-collapse:separate;border-spacing:1.25rem 0;margin:0 -1.25rem">
    <tr>

        <td style="<?= $colStyle ?>;width:50%">
            <div style="<?= $cardStyle ?>">
                <div style="<?= $cardHead ?>">Descriptions</div>
                <table style="width:100%;border-collapse:collapse">
                    <?php
                    pfield('Sales',        $pr['short_description']);
                    pfield('Purchase',     $pr['purchase_description']);
                    pfield('EDI Description', $pr['edi_description']   ?? null);
                    pfield('GS1 Description', $pr['gs1_description']   ?? null);
                    pfield('Amazon Title',    $pr['amazon_title']);
                    ?>
                    <?php if (!empty($pr['amazon_bullets'])): ?>
                    <tr>
                        <td class="text-muted" style="width:42%;padding:.45rem .6rem;font-size:.875rem;vertical-align:top">Amazon Bullet Points</td>
                        <td style="padding:.45rem .6rem;font-size:.85rem">
                            <ul style="margin:0;padding-left:1.1rem">
                                <?php foreach (array_filter(array_map('trim', explode("\n", $pr['amazon_bullets']))) as $b): ?>
                                    <li style="margin-bottom:.15rem"><?= e($b) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($pr['long_description'])): ?>
                    <tr>
                        <td class="text-muted" style="padding:.45rem .6rem;font-size:.875rem;vertical-align:top">Long / Website</td>
                        <td style="padding:.45rem .6rem;font-size:.85rem;line-height:1.55"><?= nl2br(e($pr['long_description'])) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </td>

        <td style="<?= $colStyle ?>;width:50%">
            <div style="<?= $cardStyle ?>">
                <div style="<?= $cardHead ?>">Documents</div>

                <?php if (!empty($documents)): ?>
                <table style="width:100%;border-collapse:collapse">
                    <thead>
                        <tr style="background:#f8f9fb;border-bottom:1px solid #e5e7eb">
                            <th style="padding:.45rem .6rem;font-size:.68rem;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;text-align:left">Document Name</th>
                            <th style="padding:.45rem .6rem;font-size:.68rem;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;text-align:left">Type</th>
                            <th style="padding:.45rem .6rem;font-size:.68rem;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;text-align:right">Size</th>
                            <th style="padding:.45rem .6rem;font-size:.68rem;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;text-align:left">Updated</th>
                            <th style="width:60px"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($documents as $doc): ?>
                        <tr style="border-bottom:1px solid #f3f4f6">
                            <td style="padding:.5rem .6rem;font-size:.85rem">
                                <a href="<?= e($doc['file_path']) ?>" target="_blank" style="color:#0A3D91;font-weight:500"><?= e($doc['title']) ?></a>
                                <?php if (empty($doc['is_public'])): ?>
                                    <span class="badge badge--neutral" style="font-size:.62rem;margin-left:.25rem">Internal</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:.5rem .6rem;font-size:.8rem;color:#6b7280"><?= $docTypeLabels[$doc['doc_type']] ?? e($doc['doc_type']) ?></td>
                            <td style="padding:.5rem .6rem;font-size:.8rem;color:#6b7280;text-align:right"><?= pFileSize($doc['file_size'] !== null ? (int)$doc['file_size'] : null) ?></td>
                            <td style="padding:.5rem .6rem;font-size:.8rem;color:#6b7280"><?= !empty($doc['updated_at']) ? date('m/d/Y', strtotime($doc['updated_at'])) : '' ?></td>
                            <td style="padding:.5rem .6rem;text-align:right;white-space:nowrap">
                                <a href="<?= e($doc['file_path']) ?>" download title="Download" style="color:#6b7280;text-decoration:none;font-size:.8rem">↓</a>
                                <form method="POST" action="/products/<?= (int)$pr['id'] ?>/documents/<?= (int)$doc['id'] ?>/delete" style="display:inline"
                                      onsubmit="return confirm('Delete this document?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" title="Delete"
                                            style="background:none;border:none;padding:0 0 0 .35rem;color:#dc2626;cursor:pointer;font-size:.8rem">×</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div style="padding:1.5rem 1rem;text-align:center;color:#9ca3af;font-size:.85rem">
                        No documents yet. Add an SDS, TDS, flyer or color chart below.
                    </div>
                <?php endif; ?>

                <!-- Upload document -->
                <div style="padding:.75rem 1rem;border-top:1px solid #e5e7eb;background:#fafafa">
                    <form method="POST" action="/products/<?= (int)$pr['id'] ?>/documents" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <table style="width:100%;border-collapse:collapse">
                            <tr>
                                <td style="padding:0 .4rem .4rem 0">
                                    <input type="text" name="title" placeholder="Document title (defaults to file name)"
                                           style="width:100%;padding:.4rem .5rem;border:1px solid #d1d5db;border-radius:5px;font-size:.8rem;box-sizing:border-box">
                                </td>
                                <td style="width:135px;padding:0 0 .4rem 0">
                                    <select name="doc_type" style="width:100%;padding:.4rem .5rem;border:1px solid #d1d5db;border-radius:5px;font-size:.8rem">
                                        <?php foreach ($docTypeLabels as $val => $lbl): ?>
                                            <option value="<?= $val ?>"><?= $lbl ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding:0">
                                    <input type="file" name="document" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.jpg,.jpeg,.png" required
                                           style="width:100%;font-size:.8rem;margin-bottom:.4rem">
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:0;font-size:.75rem;color:#6b7280">
                                    <label style="cursor:pointer">
                                        <input type="checkbox" name="is_public" value="1" checked> Visible on website / customer portal
                                    </label>
                                </td>
                                <td style="padding:0;text-align:right">
                                    <button type="submit" class="btn btn--sm btn--secondary">Upload Document</button>
                                </td>
                            </tr>
                        </table>
                        <div style="font-size:.7rem;color:#9ca3af;margin-top:.35rem">PDF, Word, Excel or image · max 25 MB</div>
                    </form>
                </div>
            </div>
        </td>
    </tr>
</table>

<!-- ============ ROW 4: Additional information ============ -->
<?php
$specGroups = [
    [
        ['Country of Origin', $pr['country_of_origin'] ?? null],
        ['HTS Code',          $pr['hts_code']          ?? null],
        ['Shelf Life',        $pr['shelf_life']        ?? null],
        ['Storage Temp Min',  isset($pr['storage_temp_min']) && $pr['storage_temp_min'] !== null ? $pr['storage_temp_min'] . ' °F' : null],
        ['Storage Temp Max',  isset($pr['storage_temp_max']) && $pr['storage_temp_max'] !== null ? $pr['storage_temp_max'] . ' °F' : null],
        ['Hazmat',            !empty($pr['is_hazmat']) ? 'Yes' : 'No'],
        ['Hazmat Class',      $pr['hazmat_class']      ?? null],
        ['UN Number',         $pr['un_number']         ?? null],
    ],
    [
        ['VOC',          $pr['voc']          ?? null],
        ['Flash Point',  $pr['flash_point']  ?? null],
        ['Propellant',   $pr['propellant']   ?? null],
        ['Odor',         $pr['odor']         ?? null],
        ['Dry Time',     $pr['dry_time']     ?? null],
        ['Field Ready',  $pr['field_ready']  ?? null],
        ['Paint Type',   $pr['paint_type']   ?? null],
        ['Surface Use',  $pr['surface_use']  ?? null],
    ],
    [
        ['Application',      $pr['application']      ?? null],
        ['Recommended Use',  $pr['recommended_use']  ?? null],
        ['Dilution',         $pr['dilution']         ?? null],
        ['Coverage',         $pr['coverage']         ?? null],
        ['Clean Up',         $pr['clean_up']         ?? null],
        ['Warranty',         $pr['warranty']         ?? null],
        ['Notes',            $pr['spec_notes']       ?? null],
        ['Review Note',      $pr['review_note']      ?? null],
    ],
];
$hasSpecs = false;
foreach ($specGroups as $g) {
    foreach ($g as [$l, $v]) {
        if ($v !== null && $v !== '' && $l !== 'Hazmat') { $hasSpecs = true; break 2; }
    }
}
?>
<div style="<?= $cardStyle ?>">
    <div style="<?= $cardHead ?>">Additional Information</div>
    <?php if ($hasSpecs): ?>
    <table style="width:100%;border-collapse:collapse">
        <tr>
            <?php foreach ($specGroups as $group): ?>
            <td style="width:33.33%;vertical-align:top;padding:.5rem .5rem">
                <table style="width:100%;border-collapse:collapse">
                    <?php foreach ($group as [$label, $value]) { pfield($label, $value); } ?>
                </table>
            </td>
            <?php endforeach; ?>
        </tr>
    </table>
    <?php else: ?>
        <div style="padding:1.5rem 1rem;text-align:center;color:#9ca3af;font-size:.85rem">
            No specs recorded yet — add them from <a href="/products/<?= (int)$pr['id'] ?>/edit" style="color:#0A3D91">Edit</a>.
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
