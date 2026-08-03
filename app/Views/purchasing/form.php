<?php ob_start(); ?>
<?php
$editing = !empty($po['id']);
?>

<div class="page-header">
    <div class="page-header__left">
        <div style="font-size:.85rem;color:var(--color-text-muted);margin-bottom:.25rem">
            <a href="/purchasing" style="color:inherit">Purchase Orders</a>
            &rsaquo; <?= $editing ? e($po['po_number']) : 'New PO' ?>
        </div>
        <h1 class="page-title" style="margin:0"><?= $editing ? 'Edit ' . e($po['po_number']) : 'New Purchase Order' ?></h1>
    </div>
</div>

<form method="POST" action="<?= $editing ? '/purchasing/' . (int)$po['id'] . '/edit' : '/purchasing' ?>"
      id="poForm">
    <?= csrf_field() ?>

    <table style="width:100%;border-collapse:collapse;vertical-align:top">
        <tr style="vertical-align:top">
            <td style="width:60%;padding-right:1.25rem">

                <!-- Header -->
                <div class="card" style="padding:1.5rem;margin-bottom:1.25rem">
                    <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:1rem">Order Details</div>

                    <div style="margin-bottom:.9rem">
                        <label class="label">Vendor <span style="color:var(--color-danger)">*</span></label>
                        <select name="vendor_id" required class="input" style="width:100%">
                            <option value="">— Select Vendor —</option>
                            <?php foreach ($vendors as $v): ?>
                                <option value="<?= (int)$v['id'] ?>"
                                    <?= ($po['vendor_id'] ?? '') == $v['id'] ? 'selected' : '' ?>>
                                    <?= e($v['company_name']) ?>
                                    <?= $v['account_number'] ? '(' . e($v['account_number']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <table style="width:100%;border-collapse:collapse">
                        <tr>
                            <td style="width:50%;padding-right:.5rem;padding-bottom:.9rem;vertical-align:top">
                                <label class="label">Order Date <span style="color:var(--color-danger)">*</span></label>
                                <input type="date" name="order_date" required
                                       value="<?= e($po['order_date'] ?? date('Y-m-d')) ?>"
                                       class="input" style="width:100%">
                            </td>
                            <td style="width:50%;padding-left:.5rem;padding-bottom:.9rem;vertical-align:top">
                                <label class="label">Expected Date</label>
                                <input type="date" name="expected_date"
                                       value="<?= e($po['expected_date'] ?? '') ?>"
                                       class="input" style="width:100%">
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-right:.5rem;padding-bottom:.9rem;vertical-align:top">
                                <label class="label">Status</label>
                                <select name="status" class="input" style="width:100%">
                                    <?php
                                    $statuses = ['draft','sent','partial','received','closed','cancelled'];
                                    foreach ($statuses as $s):
                                    ?>
                                    <option value="<?= $s ?>" <?= ($po['status'] ?? 'draft') === $s ? 'selected' : '' ?>>
                                        <?= ucfirst($s) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td style="padding-left:.5rem;padding-bottom:.9rem;vertical-align:top">
                                <label class="label">Vendor Ref #</label>
                                <input type="text" name="vendor_ref" maxlength="100"
                                       value="<?= e($po['vendor_ref'] ?? '') ?>"
                                       class="input" style="width:100%"
                                       placeholder="Vendor's order/confirmation #">
                            </td>
                        </tr>
                    </table>

                    <div style="margin-bottom:.9rem">
                        <label class="label">Memo / Notes to Vendor</label>
                        <textarea name="memo" rows="2" class="input" style="width:100%;resize:vertical"
                                  placeholder="Notes that will appear on the PO…"><?= e($po['memo'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="label">Internal Notes</label>
                        <textarea name="internal_notes" rows="2" class="input" style="width:100%;resize:vertical"
                                  placeholder="Internal notes (not printed on PO)…"><?= e($po['internal_notes'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Ship To -->
                <div class="card" style="padding:1.5rem;margin-bottom:1.25rem">
                    <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:1rem">Ship To (Leave blank to use company address)</div>
                    <div style="margin-bottom:.9rem">
                        <label class="label">Name / Attention</label>
                        <input type="text" name="ship_to_name" maxlength="255"
                               value="<?= e($po['ship_to_name'] ?? 'US Specialty Coatings') ?>"
                               class="input" style="width:100%">
                    </div>
                    <div style="margin-bottom:.9rem">
                        <label class="label">Address</label>
                        <input type="text" name="ship_to_address_1" maxlength="255"
                               value="<?= e($po['ship_to_address_1'] ?? '') ?>"
                               class="input" style="width:100%">
                    </div>
                    <table style="width:100%;border-collapse:collapse">
                        <tr>
                            <td style="width:45%;padding-right:.5rem;padding-bottom:.9rem;vertical-align:top">
                                <label class="label">City</label>
                                <input type="text" name="ship_to_city" maxlength="100"
                                       value="<?= e($po['ship_to_city'] ?? '') ?>"
                                       class="input" style="width:100%">
                            </td>
                            <td style="width:20%;padding-right:.5rem;padding-left:.5rem;padding-bottom:.9rem;vertical-align:top">
                                <label class="label">State</label>
                                <input type="text" name="ship_to_state" maxlength="50"
                                       value="<?= e($po['ship_to_state'] ?? '') ?>"
                                       class="input" style="width:100%">
                            </td>
                            <td style="width:35%;padding-left:.5rem;padding-bottom:.9rem;vertical-align:top">
                                <label class="label">ZIP</label>
                                <input type="text" name="ship_to_zip" maxlength="20"
                                       value="<?= e($po['ship_to_zip'] ?? '') ?>"
                                       class="input" style="width:100%">
                            </td>
                        </tr>
                    </table>
                </div>

            </td>
            <td style="width:40%;vertical-align:top">

                <!-- Totals summary -->
                <div class="card" style="padding:1.25rem;margin-bottom:1.25rem">
                    <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:.75rem">Totals</div>
                    <table style="width:100%;border-collapse:collapse;font-size:.875rem">
                        <tr>
                            <td style="padding:.35rem 0;color:var(--color-text-muted)">Subtotal</td>
                            <td class="text-right" id="subtotalDisplay" style="font-weight:600">$0.00</td>
                        </tr>
                        <tr>
                            <td style="padding:.35rem 0;color:var(--color-text-muted)">Tax</td>
                            <td class="text-right">
                                <input type="number" name="tax_amount" step="0.01" min="0"
                                       value="<?= number_format((float)($po['tax_amount'] ?? 0), 2) ?>"
                                       class="input" style="width:90px;text-align:right;padding:.25rem .4rem"
                                       oninput="recalcTotal()">
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:.35rem 0;color:var(--color-text-muted)">Shipping</td>
                            <td class="text-right">
                                <input type="number" name="shipping_cost" step="0.01" min="0"
                                       value="<?= number_format((float)($po['shipping_cost'] ?? 0), 2) ?>"
                                       class="input" style="width:90px;text-align:right;padding:.25rem .4rem"
                                       oninput="recalcTotal()">
                            </td>
                        </tr>
                        <tr style="border-top:2px solid var(--color-border)">
                            <td style="padding:.5rem 0 0;font-weight:700">Total</td>
                            <td class="text-right" id="totalDisplay" style="font-weight:700;font-size:1.1rem">$0.00</td>
                        </tr>
                    </table>
                </div>

            </td>
        </tr>
    </table>

    <!-- Line Items -->
    <div class="card" style="padding:0;overflow:hidden;margin-bottom:1.25rem">
        <div style="padding:.875rem 1.25rem;border-bottom:1px solid var(--color-border);font-weight:600">Line Items</div>
        <div class="table-wrap">
            <table class="table" id="linesTable">
                <thead>
                    <tr>
                        <th style="width:30%">Product</th>
                        <th>Description</th>
                        <th style="width:90px" class="text-right">Qty</th>
                        <th style="width:110px" class="text-right">Unit Cost</th>
                        <th style="width:110px" class="text-right">Line Total</th>
                        <th style="width:40px"></th>
                    </tr>
                </thead>
                <tbody id="linesBody">
                    <?php
                    $existingLines = $lines ?? [];
                    $rowCount      = max(count($existingLines), 5);
                    for ($i = 0; $i < $rowCount; $i++):
                        $line = $existingLines[$i] ?? [];
                    ?>
                        <tr class="line-row" data-index="<?= $i ?>">
                            <td style="padding:.4rem .75rem">
                                <input type="hidden" name="line_product_id[]" class="line-pid"
                                       value="<?= (int)($line['product_id'] ?? 0) ?: '' ?>">
                                <input type="text" class="input line-product-search" style="width:100%;font-size:.85rem"
                                       placeholder="SKU or name…"
                                       value="<?= e($line['sku'] ?? ($line['product_name'] ?? '')) ?>"
                                       autocomplete="off">
                                <div class="po-autocomplete-dropdown" style="display:none;position:absolute;z-index:100;background:var(--color-bg);border:1px solid var(--color-border);border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,.1);min-width:320px;max-height:220px;overflow-y:auto"></div>
                            </td>
                            <td style="padding:.4rem .75rem">
                                <input type="text" name="line_description[]" class="input" style="width:100%;font-size:.85rem"
                                       value="<?= e($line['description'] ?? '') ?>">
                            </td>
                            <td style="padding:.4rem .75rem">
                                <input type="number" name="line_qty[]" class="input line-qty" step="0.01" min="0"
                                       style="width:100%;text-align:right;font-size:.85rem"
                                       value="<?= $line['qty_ordered'] ?? '' ?>"
                                       oninput="recalcRow(this)">
                            </td>
                            <td style="padding:.4rem .75rem">
                                <input type="number" name="line_cost[]" class="input line-cost" step="0.0001" min="0"
                                       style="width:100%;text-align:right;font-size:.85rem"
                                       value="<?= $line['unit_cost'] ?? '' ?>"
                                       oninput="recalcRow(this)">
                            </td>
                            <td style="padding:.4rem .75rem;text-align:right;font-size:.875rem;white-space:nowrap" class="line-total">
                                <?php
                                $lt = (float)($line['qty_ordered'] ?? 0) * (float)($line['unit_cost'] ?? 0);
                                echo $lt > 0 ? '$' . number_format($lt, 2) : '';
                                ?>
                            </td>
                            <td style="padding:.4rem .5rem;text-align:center">
                                <button type="button" class="btn btn--secondary" style="padding:.2rem .5rem;font-size:.8rem;color:var(--color-danger)"
                                        onclick="removeLine(this)">✕</button>
                            </td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
        <div style="padding:.75rem 1.25rem;border-top:1px solid var(--color-border)">
            <button type="button" class="btn btn--secondary" onclick="addLine()">+ Add Line</button>
        </div>
    </div>

    <!-- Save bar -->
    <div style="display:flex;gap:.75rem;align-items:center">
        <button type="submit" class="btn btn--primary"><?= $editing ? 'Save Changes' : 'Create Purchase Order' ?></button>
        <a href="<?= $editing ? '/purchasing/' . (int)$po['id'] : '/purchasing' ?>" class="btn btn--secondary">Cancel</a>
    </div>
</form>

<script>
var lineIndex = <?= $rowCount ?>;

function addLine() {
    var tbody = document.getElementById('linesBody');
    var tr = document.createElement('tr');
    tr.className = 'line-row';
    tr.dataset.index = lineIndex;
    tr.innerHTML = `
        <td style="padding:.4rem .75rem;position:relative">
            <input type="hidden" name="line_product_id[]" class="line-pid" value="">
            <input type="text" class="input line-product-search" style="width:100%;font-size:.85rem"
                   placeholder="SKU or name…" autocomplete="off">
            <div class="po-autocomplete-dropdown" style="display:none;position:absolute;z-index:100;background:var(--color-bg);border:1px solid var(--color-border);border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,.1);min-width:320px;max-height:220px;overflow-y:auto"></div>
        </td>
        <td style="padding:.4rem .75rem">
            <input type="text" name="line_description[]" class="input" style="width:100%;font-size:.85rem" value="">
        </td>
        <td style="padding:.4rem .75rem">
            <input type="number" name="line_qty[]" class="input line-qty" step="0.01" min="0"
                   style="width:100%;text-align:right;font-size:.85rem" oninput="recalcRow(this)">
        </td>
        <td style="padding:.4rem .75rem">
            <input type="number" name="line_cost[]" class="input line-cost" step="0.0001" min="0"
                   style="width:100%;text-align:right;font-size:.85rem" oninput="recalcRow(this)">
        </td>
        <td style="padding:.4rem .75rem;text-align:right;font-size:.875rem;white-space:nowrap" class="line-total"></td>
        <td style="padding:.4rem .5rem;text-align:center">
            <button type="button" class="btn btn--secondary" style="padding:.2rem .5rem;font-size:.8rem;color:var(--color-danger)"
                    onclick="removeLine(this)">✕</button>
        </td>`;
    tbody.appendChild(tr);
    bindSearch(tr.querySelector('.line-product-search'));
    lineIndex++;
}

function removeLine(btn) {
    var rows = document.querySelectorAll('.line-row');
    if (rows.length <= 1) return;
    btn.closest('tr').remove();
    recalcTotal();
}

function recalcRow(input) {
    var row   = input.closest('tr');
    var qty   = parseFloat(row.querySelector('.line-qty').value) || 0;
    var cost  = parseFloat(row.querySelector('.line-cost').value) || 0;
    var total = qty * cost;
    row.querySelector('.line-total').textContent = total > 0 ? '$' + total.toFixed(2) : '';
    recalcTotal();
}

function recalcTotal() {
    var subtotal = 0;
    document.querySelectorAll('.line-row').forEach(function(row) {
        var qty  = parseFloat(row.querySelector('.line-qty').value) || 0;
        var cost = parseFloat(row.querySelector('.line-cost').value) || 0;
        subtotal += qty * cost;
    });
    var tax      = parseFloat(document.querySelector('[name=tax_amount]').value) || 0;
    var shipping = parseFloat(document.querySelector('[name=shipping_cost]').value) || 0;
    var total    = subtotal + tax + shipping;

    document.getElementById('subtotalDisplay').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('totalDisplay').textContent    = '$' + total.toFixed(2);
}

// Product autocomplete
function bindSearch(input) {
    if (!input) return;
    var dropdown = input.parentElement.querySelector('.po-autocomplete-dropdown');
    var pidInput = input.parentElement.querySelector('.line-pid');
    var timer;

    input.addEventListener('input', function() {
        clearTimeout(timer);
        var q = this.value.trim();
        if (q.length < 2) { dropdown.style.display = 'none'; return; }
        timer = setTimeout(function() {
            fetch('/products/autocomplete-all?q=' + encodeURIComponent(q))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    dropdown.innerHTML = '';
                    if (!data.length) { dropdown.style.display = 'none'; return; }
                    data.forEach(function(p) {
                        var div = document.createElement('div');
                        div.style.cssText = 'padding:.5rem .75rem;cursor:pointer;font-size:.85rem;border-bottom:1px solid var(--color-border)';
                        var tag = p.item_type === 'raw_material'
                            ? ' <span style="font-size:.75rem;background:#eff6ff;color:#1d4ed8;padding:.1rem .4rem;border-radius:4px;margin-left:.35rem">Raw Material</span>'
                            : '';
                        div.innerHTML = '<strong>' + (p.sku || p.name) + '</strong>' + tag + '<br><span style="color:var(--color-text-muted)">' + p.name + '</span>' +
                            (p.cost ? ' <span style="color:var(--color-text-muted);float:right">Cost: $' + parseFloat(p.cost).toFixed(2) + '</span>' : '');
                        div.addEventListener('mousedown', function(e) {
                            e.preventDefault();
                            input.value = p.sku + ' — ' + p.name;
                            pidInput.value = p.id;
                            // Auto-fill cost if empty
                            var costInput = input.closest('tr').querySelector('.line-cost');
                            if (p.cost && (!costInput.value || costInput.value === '0')) {
                                costInput.value = parseFloat(p.cost).toFixed(4);
                            }
                            // Auto-fill description if empty
                            var descInput = input.closest('tr').querySelector('[name="line_description[]"]');
                            if (!descInput.value) descInput.value = p.name;
                            dropdown.style.display = 'none';
                            recalcTotal();
                        });
                        dropdown.appendChild(div);
                    });
                    dropdown.style.display = '';
                });
        }, 250);
    });

    input.addEventListener('blur', function() {
        setTimeout(function() { dropdown.style.display = 'none'; }, 200);
    });
}

// Bind autocomplete on existing rows
document.querySelectorAll('.line-product-search').forEach(function(inp) {
    bindSearch(inp);
});

// Wrap each search cell in position:relative if not already
document.querySelectorAll('.line-row td:first-child').forEach(function(td) {
    td.style.position = 'relative';
});

recalcTotal();
</script>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
