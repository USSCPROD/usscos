<?php ob_start(); ?>
<?php
$pr = $product;

$cardStyle = 'background:#fff;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:1.25rem';
$cardHead  = 'padding:.7rem 1rem;border-bottom:1px solid #e5e7eb;font-size:.72rem;font-weight:700;'
           . 'text-transform:uppercase;letter-spacing:.06em;color:#6b7280';
$colStyle  = 'vertical-align:top;padding:0';

/* ---------- form field helpers ---------- */
if (!function_exists('peLabelStyle')) {
    function peLabelStyle(): string {
        return 'width:40%;padding:.4rem .5rem .4rem .7rem;font-size:.83rem;color:#6b7280;vertical-align:middle';
    }
}
if (!function_exists('peInputStyle')) {
    function peInputStyle(): string {
        return 'width:100%;padding:.42rem .55rem;font-size:.87rem;font-family:inherit;'
             . 'border:1px solid #d1d5db;border-radius:5px;box-sizing:border-box;background:#fff;color:#111';
    }
}
if (!function_exists('peInput')) {
    function peInput(string $label, string $name, $value, string $type = 'text', string $extra = ''): void {
        echo '<tr>'
           . '<td style="' . peLabelStyle() . '">' . $label . '</td>'
           . '<td style="padding:.28rem .7rem .28rem 0">'
           . '<input type="' . $type . '" name="' . $name . '" value="' . e($value ?? '') . '" '
           . $extra . ' style="' . peInputStyle() . '">'
           . '</td></tr>';
    }
}
if (!function_exists('peFmtNum')) {
    /**
     * Columns are DECIMAL(x,4) so MySQL hands back "39.9500" / "18.0000".
     * Trim to $dec places and drop pointless trailing zeros for data entry.
     */
    function peFmtNum($value, int $dec = 2): string {
        if ($value === null || $value === '') return '';
        if (!is_numeric($value))              return (string)$value;

        $s = number_format(round((float)$value, $dec), $dec, '.', '');
        // Only strip zeros after a decimal point — otherwise "60" becomes "6"
        if (str_contains($s, '.')) {
            $s = rtrim(rtrim($s, '0'), '.');
        }
        return $s;
    }
}
if (!function_exists('peNumber')) {
    function peNumber(string $label, string $name, $value, string $step = '0.01'): void {
        $dec = ($step === '1') ? 0 : 2;
        peInput($label, $name, peFmtNum($value, $dec), 'number', 'step="' . $step . '"');
    }
}
if (!function_exists('peSelect')) {
    function peSelect(string $label, string $name, $value, array $options): void {
        echo '<tr><td style="' . peLabelStyle() . '">' . $label . '</td>'
           . '<td style="padding:.28rem .7rem .28rem 0">'
           . '<select name="' . $name . '" style="' . peInputStyle() . '">';
        foreach ($options as $val => $lbl) {
            $sel = ((string)$value === (string)$val) ? ' selected' : '';
            echo '<option value="' . e((string)$val) . '"' . $sel . '>' . e($lbl) . '</option>';
        }
        echo '</select></td></tr>';
    }
}
if (!function_exists('peTextarea')) {
    function peTextarea(string $label, string $name, $value, int $rows = 3, string $hint = ''): void {
        echo '<tr><td style="' . peLabelStyle() . ';vertical-align:top;padding-top:.5rem">' . $label
           . ($hint ? '<div style="font-size:.7rem;color:#9ca3af;margin-top:.15rem">' . $hint . '</div>' : '')
           . '</td>'
           . '<td style="padding:.28rem .7rem .28rem 0">'
           . '<textarea name="' . $name . '" rows="' . $rows . '" style="' . peInputStyle() . ';resize:vertical">'
           . e($value ?? '') . '</textarea>'
           . '</td></tr>';
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <div style="font-size:.85rem;color:var(--color-text-muted);margin-bottom:.25rem">
            <a href="/products" style="color:inherit">Products</a> &rsaquo;
            <a href="/products/<?= (int)$pr['id'] ?>" style="color:inherit"><?= e($pr['sku']) ?></a> &rsaquo; Edit
        </div>
        <h1 class="page-title" style="margin:0">Edit Product</h1>
    </div>
</div>

<form method="POST" action="/products/<?= (int)$pr['id'] ?>/edit">
<?= csrf_field() ?>

<table style="width:100%;border-collapse:separate;border-spacing:1.25rem 0;margin:0 -1.25rem">
    <tr>

    <!-- ================= LEFT ================= -->
    <td style="<?= $colStyle ?>;width:50%">

        <div style="<?= $cardStyle ?>">
            <div style="<?= $cardHead ?>">Identity</div>
            <table style="width:100%;border-collapse:collapse">
                <?php
                peInput('SKU',  'sku',  $pr['sku']);
                peInput('Name', 'name', $pr['name']);
                ?>
                <tr>
                    <td style="<?= peLabelStyle() ?>">Brand</td>
                    <td style="padding:.28rem .7rem .28rem 0">
                        <select name="brand_id" style="<?= peInputStyle() ?>">
                            <option value="">— None —</option>
                            <?php foreach ($brands as $b): ?>
                                <option value="<?= (int)$b['id'] ?>" <?= (int)$pr['brand_id'] === (int)$b['id'] ? 'selected' : '' ?>>
                                    <?= e($b['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <?php
                peInput('Product Line',   'product_line',   $pr['product_line']);
                peInput('Color',          'color',          $pr['color']);
                peInput('Category',       'category',       $pr['category']);
                peInput('Brand Category', 'brand_category', $pr['brand_category']);
                peInput('Sport / Tags',   'sports',         $pr['sports'] ?? '', 'text',
                        'placeholder="Football, Soccer, Lacrosse"');
                peInput('UOM',            'uom_code',       $pr['uom_code']);
                peInput('Pack Level',     'pack_level',     $pr['pack_level']);
                peSelect('Active',        'is_active',      (int)$pr['is_active'], [1 => 'Active', 0 => 'Inactive']);
                ?>
            </table>
        </div>

        <div style="<?= $cardStyle ?>">
            <div style="<?= $cardHead ?>">Descriptions</div>
            <table style="width:100%;border-collapse:collapse">
                <?php
                peTextarea('Sales Description',   'short_description',    $pr['short_description'], 2,
                           'Short — used on quotes &amp; invoices');
                peTextarea('Long Description',    'long_description',     $pr['long_description'] ?? '', 5,
                           'Website &amp; catalog copy');
                peTextarea('Purchase Description','purchase_description', $pr['purchase_description'], 2,
                           'Shown on POs');
                peInput('EDI Description',        'edi_description',      $pr['edi_description'] ?? '', 'text',
                        'maxlength="500" placeholder="Uppercase short form for EDI feeds"');
                peTextarea('GS1 Description',     'gs1_description',      $pr['gs1_description'] ?? '', 2);
                peInput('Amazon Title',           'amazon_title',         $pr['amazon_title'], 'text', 'maxlength="500"');
                peTextarea('Amazon Bullets',      'amazon_bullets',       $pr['amazon_bullets'] ?? '', 5,
                           'One bullet per line');
                ?>
            </table>
        </div>

        <div style="<?= $cardStyle ?>">
            <div style="<?= $cardHead ?>">Vendor &amp; Barcodes</div>
            <table style="width:100%;border-collapse:collapse">
                <?php
                peInput('Preferred Vendor', 'preferred_vendor_name', $pr['preferred_vendor_name']);
                peInput('Vendor Part #',    'vendor_part_number',    $pr['vendor_part_number']);
                peInput('MPN',              'mpn',                   $pr['mpn']);
                peInput('GTIN-12 (UPC)',    'gtin12',                $pr['gtin12'], 'text', 'maxlength="14"');
                peInput('GTIN-14',          'gtin14',                $pr['gtin14'], 'text', 'maxlength="14"');
                peInput('ASIN',             'asin',                  $pr['asin'],   'text', 'maxlength="20"');
                peInput('GS1 Status',       'gs1_status',            $pr['gs1_status']);
                ?>
            </table>
        </div>

        <div style="<?= $cardStyle ?>">
            <div style="<?= $cardHead ?>">Website</div>
            <table style="width:100%;border-collapse:collapse">
                <?php
                peInput('Image URL', 'website_image_url', $pr['website_image_url'], 'text',
                        'placeholder="Used only if no image is uploaded"');
                peSelect('Publish to Website', 'publish_to_website', (int)($pr['publish_to_website'] ?? 0),
                         [1 => 'Yes', 0 => 'No']);
                ?>
            </table>
        </div>

    </td>

    <!-- ================= RIGHT ================= -->
    <td style="<?= $colStyle ?>;width:50%">

        <div style="<?= $cardStyle ?>">
            <div style="<?= $cardHead ?>">Pricing</div>
            <table style="width:100%;border-collapse:collapse">
                <?php
                peNumber('Retail Price',    'price',           $pr['price'], '0.01');
                peNumber('Dist Price (QB)', 'dist_price',      $pr['dist_price'], '0.01');
                peNumber('Dist Price 2026', 'dist_price_2026', $pr['dist_price_2026'], '0.01');
                peNumber('Stocking Dist',   'stocking_dist',   $pr['stocking_dist'], '0.01');
                peNumber('Website Price',   'website_price',   $pr['website_price'] ?? '', '0.01');
                peNumber('Amazon Price',    'amazon_price',    $pr['amazon_price']  ?? '', '0.01');
                peNumber('Cost',            'cost',            $pr['cost'], '0.01');
                peSelect('Taxable',         'is_taxable',      (int)$pr['is_taxable'], [1 => 'Yes', 0 => 'No']);
                ?>
            </table>
        </div>

        <div style="<?= $cardStyle ?>">
            <div style="<?= $cardHead ?>">Accounting</div>
            <table style="width:100%;border-collapse:collapse">
                <?php
                peInput('Income Account', 'income_account', $pr['income_account']);
                peInput('COGS Account',   'cogs_account',   $pr['cogs_account']);
                peInput('Asset Account',  'asset_account',  $pr['asset_account']);
                peInput('Tax Agency',     'tax_agency',     $pr['tax_agency']);
                ?>
            </table>
        </div>

        <div style="<?= $cardStyle ?>">
            <div style="<?= $cardHead ?>">Inventory</div>
            <table style="width:100%;border-collapse:collapse">
                <?php
                peNumber('Reorder Point',    'reorder_point',  $pr['reorder_point'], 'any');
                peNumber('Min Order Qty',    'min_order_qty',  $pr['min_order_qty'], 'any');
                peNumber('Lead Time (days)', 'lead_time_days', $pr['lead_time_days'], '1');
                ?>
            </table>
        </div>

        <div style="<?= $cardStyle ?>">
            <div style="<?= $cardHead ?>">Dimensions &amp; Weight</div>
            <table style="width:100%;border-collapse:collapse">
                <?php
                peNumber('Height',            'height',            $pr['height']);
                peNumber('Width',             'width',             $pr['width']);
                peNumber('Depth',             'depth',             $pr['depth']);
                peInput('Dim Unit',           'dim_unit',          $pr['dim_unit'], 'text', 'placeholder="Inches"');
                peNumber('Gross Weight',      'gross_weight',      $pr['gross_weight']);
                peNumber('Net Weight',        'net_weight',        $pr['net_weight']);
                peInput('Weight Unit',        'weight_unit',       $pr['weight_unit'], 'text', 'placeholder="Pounds"');
                ?>
                <tr><td colspan="2" style="padding:.5rem .7rem .2rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#9ca3af;border-top:1px solid #f3f4f6">Individual Unit</td></tr>
                <?php
                peNumber('Unit Height',       'unit_height',       $pr['unit_height'] ?? '');
                peNumber('Unit Width',        'unit_width',        $pr['unit_width']  ?? '');
                peNumber('Unit Depth',        'unit_depth',        $pr['unit_depth']  ?? '');
                peNumber('Unit Gross Weight', 'unit_gross_weight', $pr['unit_gross_weight'] ?? '');
                peNumber('Unit Net Weight',   'unit_net_weight',   $pr['unit_net_weight']   ?? '');
                ?>
            </table>
        </div>

        <div style="<?= $cardStyle ?>">
            <div style="<?= $cardHead ?>">Packaging &amp; Pallet</div>
            <table style="width:100%;border-collapse:collapse">
                <?php
                peNumber('Units Per Case',      'units_per_case',    $pr['units_per_case'] ?? '', '1');
                peNumber('Case Length',         'case_length',       $pr['case_length'] ?? '');
                peNumber('Case Width',          'case_width',        $pr['case_width']  ?? '');
                peNumber('Case Height',         'case_height',       $pr['case_height'] ?? '');
                peNumber('Case Weight (Gross)', 'case_weight_gross', $pr['case_weight_gross'] ?? '');
                peNumber('Case Weight (Net)',   'case_weight_net',   $pr['case_weight_net']   ?? '');
                peNumber('Pallet TI (per layer)','pallet_ti',        $pr['pallet_ti'] ?? '', '1');
                peNumber('Pallet HI (layers)',  'pallet_hi',         $pr['pallet_hi'] ?? '', '1');
                peNumber('Pallet Qty (cases)',  'pallet_qty',        $pr['pallet_qty'] ?? '', '1');
                ?>
            </table>
        </div>

    </td>
    </tr>
</table>

<!-- ================= Compliance & Specs (full width) ================= -->
<div style="<?= $cardStyle ?>">
    <div style="<?= $cardHead ?>">Compliance &amp; Product Specs</div>
    <table style="width:100%;border-collapse:collapse">
        <tr>
            <td style="width:33.33%;vertical-align:top;padding:.4rem 0">
                <table style="width:100%;border-collapse:collapse">
                    <?php
                    peInput('Country of Origin', 'country_of_origin', $pr['country_of_origin'] ?? '');
                    peInput('HTS Code',          'hts_code',          $pr['hts_code']          ?? '');
                    peInput('Shelf Life',        'shelf_life',        $pr['shelf_life']        ?? '', 'text', 'placeholder="3 Years"');
                    peNumber('Storage Temp Min (°F)', 'storage_temp_min', $pr['storage_temp_min'] ?? '', '1');
                    peNumber('Storage Temp Max (°F)', 'storage_temp_max', $pr['storage_temp_max'] ?? '', '1');
                    peSelect('Hazmat',           'is_hazmat',         (int)($pr['is_hazmat'] ?? 0), [0 => 'No', 1 => 'Yes']);
                    peInput('Hazmat Class',      'hazmat_class',      $pr['hazmat_class']      ?? '', 'text', 'placeholder="2.1"');
                    peInput('UN Number',         'un_number',         $pr['un_number']         ?? '', 'text', 'placeholder="UN1950"');
                    ?>
                </table>
            </td>
            <td style="width:33.33%;vertical-align:top;padding:.4rem 0">
                <table style="width:100%;border-collapse:collapse">
                    <?php
                    peInput('Paint Type',  'paint_type',  $pr['paint_type']  ?? '', 'text', 'placeholder="Acrylic"');
                    peInput('VOC',         'voc',         $pr['voc']         ?? '', 'text', 'placeholder="&lt; 50 g/L"');
                    peInput('Flash Point', 'flash_point', $pr['flash_point'] ?? '', 'text', 'placeholder="&lt; 0 °F"');
                    peInput('Propellant',  'propellant',  $pr['propellant']  ?? '', 'text', 'placeholder="DME"');
                    peInput('Odor',        'odor',        $pr['odor']        ?? '', 'text', 'placeholder="Slight"');
                    peInput('Dry Time',    'dry_time',    $pr['dry_time']    ?? '', 'text', 'placeholder="5–10 Minutes"');
                    peInput('Field Ready', 'field_ready', $pr['field_ready'] ?? '', 'text', 'placeholder="10–15 Minutes"');
                    peInput('Surface Use', 'surface_use', $pr['surface_use'] ?? '', 'text', 'placeholder="Grass, Turf, Asphalt"');
                    ?>
                </table>
            </td>
            <td style="width:33.33%;vertical-align:top;padding:.4rem 0">
                <table style="width:100%;border-collapse:collapse">
                    <?php
                    peInput('Application',     'application',     $pr['application']     ?? '', 'text', 'placeholder="Field Marking"');
                    peInput('Recommended Use', 'recommended_use', $pr['recommended_use'] ?? '', 'text', 'placeholder="Athletic Field Lines"');
                    peInput('Dilution',        'dilution',        $pr['dilution']        ?? '', 'text', 'placeholder="Do Not Dilute"');
                    peInput('Coverage',        'coverage',        $pr['coverage']        ?? '', 'text', 'placeholder="1 Case = 300 sq ft"');
                    peInput('Clean Up',        'clean_up',        $pr['clean_up']        ?? '', 'text', 'placeholder="Acetone"');
                    peInput('Warranty',        'warranty',        $pr['warranty']        ?? '', 'text', 'placeholder="1 Year"');
                    peTextarea('Notes',        'spec_notes',      $pr['spec_notes']      ?? '', 2);
                    peTextarea('Review Note',  'review_note',     $pr['review_note']     ?? '', 2,
                               'Internal data-quality note');
                    ?>
                </table>
            </td>
        </tr>
    </table>
</div>

<div style="padding:1rem 0 2rem;text-align:right">
    <a href="/products/<?= (int)$pr['id'] ?>" class="btn btn--secondary" style="margin-right:.75rem">Cancel</a>
    <button type="submit" class="btn btn--primary">Save Product</button>
</div>

</form>

<p style="font-size:.8rem;color:#9ca3af;margin:0 0 2rem">
    Images and documents are managed on the
    <a href="/products/<?= (int)$pr['id'] ?>" style="color:#0A3D91">product page</a>.
</p>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
