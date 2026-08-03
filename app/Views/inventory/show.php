<?php ob_start(); ?>
<?php
$flash      = \App\Core\Session::getFlash('success');
$flashError = \App\Core\Session::getFlash('error');

function invTxnLabel(string $type): string {
    return match($type) {
        'purchase_order'  => 'Purchase Order',
        'sales_order'     => 'Sales Order',
        'adjustment'      => 'Adjustment',
        'transfer'        => 'Transfer',
        'return'          => 'Return',
        'write_off'       => 'Write-Off',
        'opening_balance' => 'Opening Balance',
        default           => ucfirst(str_replace('_', ' ', $type)),
    };
}

function invTxnStyle(float $qty): string {
    if ($qty > 0) return 'color:#16a34a;font-weight:600';
    if ($qty < 0) return 'color:#dc2626;font-weight:600';
    return '';
}

$qtyOnHand = (float)($product['qty_on_hand'] ?? 0);
$reorder   = $product['reorder_point'] !== null ? (float)$product['reorder_point'] : null;

if ($qtyOnHand <= 0) {
    $qtyBadge = '<span style="background:#fef2f2;color:#dc2626;padding:.2rem .6rem;border-radius:999px;font-size:.8rem;font-weight:700">Out of Stock</span>';
} elseif ($reorder !== null && $qtyOnHand <= $reorder) {
    $qtyBadge = '<span style="background:#fffbeb;color:#d97706;padding:.2rem .6rem;border-radius:999px;font-size:.8rem;font-weight:700">Low Stock</span>';
} else {
    $qtyBadge = '<span style="background:#f0fdf4;color:#16a34a;padding:.2rem .6rem;border-radius:999px;font-size:.8rem;font-weight:700">In Stock</span>';
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
            <a href="/inventory" style="color:inherit">Inventory</a> &rsaquo; <?= e($product['sku']) ?>
        </div>
        <h1 class="page-title" style="margin:0"><?= e($product['name']) ?></h1>
        <div style="margin-top:.4rem"><?= $qtyBadge ?></div>
    </div>
    <div class="page-header__right">
        <a href="/products/<?= (int)$product['id'] ?>" class="btn btn--secondary">View Product</a>
    </div>
</div>

<!-- Stat tiles -->
<table style="width:100%;border-collapse:collapse;margin-bottom:1.25rem">
    <tr>
        <td style="width:25%;padding-right:.625rem;vertical-align:top">
            <div class="card" style="padding:1rem 1.25rem;text-align:center">
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--color-text-muted)">On Hand</div>
                <div style="font-size:2rem;font-weight:700;margin:.3rem 0;
                    color:<?= $qtyOnHand <= 0 ? '#dc2626' : ($reorder !== null && $qtyOnHand <= $reorder ? '#d97706' : '#16a34a') ?>">
                    <?= number_format($qtyOnHand, 2) ?>
                </div>
                <div style="font-size:.75rem;color:var(--color-text-muted)"><?= e($product['uom_code'] ?? 'units') ?></div>
            </div>
        </td>
        <td style="width:25%;padding-right:.625rem;vertical-align:top">
            <div class="card" style="padding:1rem 1.25rem;text-align:center">
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--color-text-muted)">On Sales Orders</div>
                <div style="font-size:2rem;font-weight:700;margin:.3rem 0;color:var(--color-text)">
                    <?= number_format((float)($product['qty_on_sales_order'] ?? 0), 2) ?>
                </div>
                <div style="font-size:.75rem;color:var(--color-text-muted)">committed</div>
            </div>
        </td>
        <td style="width:25%;padding-right:.625rem;vertical-align:top">
            <div class="card" style="padding:1rem 1.25rem;text-align:center">
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--color-text-muted)">On Purchase Orders</div>
                <div style="font-size:2rem;font-weight:700;margin:.3rem 0;color:var(--color-text)">
                    <?= number_format((float)($product['qty_on_po'] ?? 0), 2) ?>
                </div>
                <div style="font-size:.75rem;color:var(--color-text-muted)">incoming</div>
            </div>
        </td>
        <td style="width:25%;vertical-align:top">
            <div class="card" style="padding:1rem 1.25rem;text-align:center">
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--color-text-muted)">Reorder Point</div>
                <div style="font-size:2rem;font-weight:700;margin:.3rem 0;color:var(--color-text)">
                    <?= $reorder !== null ? number_format($reorder, 2) : '—' ?>
                </div>
                <div style="font-size:.75rem;color:var(--color-text-muted)">
                    <?= !empty($product['min_order_qty']) ? 'min order: ' . number_format((float)$product['min_order_qty'], 2) : 'no minimum set' ?>
                </div>
            </div>
        </td>
    </tr>
</table>

<table style="width:100%;border-collapse:collapse;gap:1.25rem">
    <tr style="vertical-align:top">
        <td style="width:55%;padding-right:1.25rem">

            <!-- Transaction history -->
            <div class="card" style="padding:0;overflow:hidden">
                <div style="padding:.875rem 1.25rem;border-bottom:1px solid var(--color-border);display:flex;align-items:center;justify-content:space-between">
                    <div style="font-weight:600">Transaction History</div>
                    <div style="font-size:.8rem;color:var(--color-text-muted)">Last 100 transactions</div>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th class="text-right">Qty Change</th>
                                <th class="text-right">On Hand After</th>
                                <th>By</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                                <tr><td colspan="6" class="table__empty">No transactions recorded yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $txn): ?>
                                    <tr>
                                        <td style="white-space:nowrap;font-size:.85rem">
                                            <?= e(date('M j, Y', strtotime($txn['transaction_date']))) ?>
                                        </td>
                                        <td style="font-size:.85rem"><?= invTxnLabel($txn['transaction_type']) ?></td>
                                        <td class="text-right">
                                            <span style="<?= invTxnStyle((float)($txn['qty'] ?? 0)) ?>;font-family:monospace;font-size:.875rem">
                                                <?= (float)($txn['qty'] ?? 0) >= 0 ? '+' : '' ?><?= number_format((float)($txn['qty'] ?? 0), 2) ?>
                                            </span>
                                        </td>
                                        <td class="text-right text-muted" style="font-family:monospace;font-size:.875rem">
                                            <?= $txn['qty_on_hand_after'] !== null ? number_format((float)$txn['qty_on_hand_after'], 2) : '—' ?>
                                        </td>
                                        <td style="font-size:.85rem;white-space:nowrap">
                                            <?= $txn['user_first'] ? e($txn['user_first'] . ' ' . $txn['user_last']) : '—' ?>
                                        </td>
                                        <td style="font-size:.8rem;color:var(--color-text-muted);max-width:160px">
                                            <?= e($txn['notes'] ?? '') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </td>
        <td style="width:45%;vertical-align:top">

            <!-- Product info -->
            <div class="card" style="padding:1.25rem;margin-bottom:1.25rem">
                <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:.75rem">Product Info</div>
                <table style="width:100%;border-collapse:collapse;font-size:.875rem">
                    <?php
                    $infoFields = [
                        ['SKU',           $product['sku']],
                        ['Brand',         $product['brand_name'] ?? ''],
                        ['Color',         $product['color'] ?? ''],
                        ['UOM',           $product['uom_code'] ?? ''],
                        ['Pack Level',    $product['pack_level'] ?? ''],
                        ['Cost',          $product['cost'] ? money((float)$product['cost']) : ''],
                        ['Retail Price',  $product['retail_price'] ? money((float)$product['retail_price']) : ''],
                        ['Dist Price',    $product['dist_price'] ? money((float)$product['dist_price']) : ''],
                        ['Vendor SKU',    $product['vendor_part_number'] ?? ''],
                        ['Preferred Vendor', $product['preferred_vendor_name'] ?? ''],
                    ];
                    foreach ($infoFields as [$label, $val]):
                        if ((string)$val === '') continue;
                    ?>
                    <tr style="border-bottom:1px solid var(--color-border)">
                        <td style="padding:.4rem .5rem .4rem 0;color:var(--color-text-muted);width:45%;white-space:nowrap"><?= e($label) ?></td>
                        <td style="padding:.4rem 0;font-weight:<?= $label === 'SKU' ? '600' : '400' ?>;font-family:<?= $label === 'SKU' ? 'monospace' : 'inherit' ?>"><?= e((string)$val) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- Adjustment form -->
            <?php if ($canAdjust): ?>
            <div class="card" style="padding:1.25rem">
                <div style="font-weight:600;margin-bottom:1rem">Adjust Inventory</div>
                <form method="POST" action="/inventory/<?= (int)$product['id'] ?>/adjust">
                    <?= csrf_field() ?>

                    <div style="margin-bottom:.9rem">
                        <label class="label">Adjustment Type</label>
                        <select name="adjust_type" id="adjustType" class="input" style="width:100%" onchange="updateQtyLabel()">
                            <option value="add">Add to current quantity</option>
                            <option value="subtract">Subtract from current quantity</option>
                            <option value="set">Set exact quantity</option>
                        </select>
                    </div>

                    <div style="margin-bottom:.9rem">
                        <label class="label" id="qtyLabel">Quantity to Add</label>
                        <input type="number" name="qty" id="qtyInput" step="0.01" min="0" required
                               class="input" style="width:100%" placeholder="0.00">
                        <div style="font-size:.8rem;color:var(--color-text-muted);margin-top:.3rem">
                            Current on hand: <strong><?= number_format($qtyOnHand, 2) ?> <?= e($product['uom_code'] ?? '') ?></strong>
                        </div>
                    </div>

                    <div style="margin-bottom:1rem">
                        <label class="label">Reason / Notes</label>
                        <textarea name="notes" rows="2" class="input" style="width:100%;resize:vertical"
                                  placeholder="e.g. Physical count, damaged goods, received shipment…"></textarea>
                    </div>

                    <button type="submit" class="btn btn--primary">Apply Adjustment</button>
                </form>
            </div>
            <?php else: ?>
            <div class="card" style="padding:1.25rem;background:var(--color-bg-subtle)">
                <div style="font-size:.875rem;color:var(--color-text-muted)">
                    Inventory adjustments require Manager, Bookkeeper, Shipping, or Admin access.
                </div>
            </div>
            <?php endif; ?>

        </td>
    </tr>
</table>

<script>
function updateQtyLabel() {
    var type = document.getElementById('adjustType').value;
    var label = document.getElementById('qtyLabel');
    var input = document.getElementById('qtyInput');
    if (type === 'add')      { label.textContent = 'Quantity to Add';      input.placeholder = '0.00'; }
    if (type === 'subtract') { label.textContent = 'Quantity to Subtract'; input.placeholder = '0.00'; }
    if (type === 'set')      { label.textContent = 'New Exact Quantity';   input.placeholder = '<?= number_format($qtyOnHand, 2) ?>'; }
}
</script>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
