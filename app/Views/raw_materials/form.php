<?php ob_start(); ?>
<?php
$flash      = \App\Core\Session::getFlash('success');
$flashError = \App\Core\Session::getFlash('error');
$editing    = !empty($item);
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
            <a href="/raw-materials" style="color:inherit">Raw Materials</a>
            &rsaquo; <?= $editing ? e($item['name']) : 'Add Material' ?>
        </div>
        <h1 class="page-title" style="margin:0"><?= $editing ? e($item['name']) : 'Add Raw Material' ?></h1>
    </div>
    <?php if ($editing): ?>
    <div class="page-header__right">
        <a href="/inventory/<?= (int)$item['id'] ?>" class="btn btn--secondary">View Inventory</a>
    </div>
    <?php endif; ?>
</div>

<form method="POST"
      action="<?= $editing ? '/raw-materials/' . (int)$item['id'] . '/edit' : '/raw-materials' ?>"
      style="max-width:640px">
    <?= csrf_field() ?>

    <div class="card" style="padding:1.5rem;margin-bottom:1.25rem">
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:1rem">Identity</div>

        <div style="margin-bottom:.9rem">
            <label class="label">Name <span style="color:var(--color-danger)">*</span></label>
            <input type="text" name="name" required maxlength="255"
                   value="<?= e($item['name'] ?? '') ?>"
                   class="input" style="width:100%"
                   placeholder="e.g. Titanium Dioxide, Alkyd Resin 60%">
        </div>

        <table style="width:100%;border-collapse:collapse">
            <tr>
                <td style="width:50%;padding-right:.5rem;padding-bottom:.9rem;vertical-align:top">
                    <label class="label">Internal SKU / Code</label>
                    <input type="text" name="sku" maxlength="100"
                           value="<?= e($item['sku'] ?? '') ?>"
                           class="input" style="width:100%"
                           placeholder="Your internal code">
                </td>
                <td style="width:50%;padding-left:.5rem;padding-bottom:.9rem;vertical-align:top">
                    <label class="label">Unit of Measure</label>
                    <input type="text" name="uom_code" maxlength="20"
                           value="<?= e($item['uom_code'] ?? '') ?>"
                           class="input" style="width:100%"
                           placeholder="e.g. LB, GAL, KG, DRUM">
                </td>
            </tr>
        </table>

        <div>
            <label class="label">Description / Notes</label>
            <textarea name="purchase_description" rows="3" class="input" style="width:100%;resize:vertical"
                      placeholder="Specifications, grade, notes…"><?= e($item['purchase_description'] ?? '') ?></textarea>
        </div>
    </div>

    <div class="card" style="padding:1.5rem;margin-bottom:1.25rem">
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:1rem">Vendor &amp; Cost</div>

        <table style="width:100%;border-collapse:collapse">
            <tr>
                <td style="width:60%;padding-right:.5rem;padding-bottom:.9rem;vertical-align:top">
                    <label class="label">Preferred Vendor</label>
                    <input type="text" name="preferred_vendor_name" maxlength="255"
                           value="<?= e($item['preferred_vendor_name'] ?? '') ?>"
                           class="input" style="width:100%">
                </td>
                <td style="width:40%;padding-left:.5rem;padding-bottom:.9rem;vertical-align:top">
                    <label class="label">Vendor Part #</label>
                    <input type="text" name="vendor_part_number" maxlength="100"
                           value="<?= e($item['vendor_part_number'] ?? '') ?>"
                           class="input" style="width:100%">
                </td>
            </tr>
            <tr>
                <td style="padding-right:.5rem;padding-bottom:.9rem;vertical-align:top">
                    <label class="label">Unit Cost</label>
                    <input type="number" name="cost" step="0.0001" min="0"
                           value="<?= e($item['cost'] ?? '') ?>"
                           class="input" style="width:100%"
                           placeholder="0.0000">
                </td>
                <td style="padding-left:.5rem;padding-bottom:.9rem;vertical-align:top">
                    <label class="label">Min Order Qty</label>
                    <input type="number" name="min_order_qty" step="0.01" min="0"
                           value="<?= e($item['min_order_qty'] ?? '') ?>"
                           class="input" style="width:100%">
                </td>
            </tr>
        </table>
    </div>

    <div class="card" style="padding:1.5rem;margin-bottom:1.25rem">
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:1rem">Inventory</div>

        <table style="width:100%;border-collapse:collapse">
            <tr>
                <td style="width:50%;padding-right:.5rem;padding-bottom:.9rem;vertical-align:top">
                    <label class="label">Qty On Hand</label>
                    <input type="number" name="qty_on_hand" step="0.01"
                           value="<?= e($item['qty_on_hand'] ?? '0') ?>"
                           class="input" style="width:100%">
                </td>
                <td style="width:50%;padding-left:.5rem;padding-bottom:.9rem;vertical-align:top">
                    <label class="label">Reorder Point</label>
                    <input type="number" name="reorder_point" step="0.01" min="0"
                           value="<?= e($item['reorder_point'] ?? '') ?>"
                           class="input" style="width:100%"
                           placeholder="Alert when qty drops below…">
                </td>
            </tr>
        </table>

        <?php if ($editing): ?>
        <div>
            <label class="label">Status</label>
            <select name="is_active" class="input" style="width:auto;min-width:160px">
                <option value="1" <?= ($item['is_active'] ?? 1) ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= !($item['is_active'] ?? 1) ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <?php endif; ?>
    </div>

    <div style="display:flex;gap:.75rem">
        <button type="submit" class="btn btn--primary">
            <?= $editing ? 'Save Changes' : 'Add Raw Material' ?>
        </button>
        <a href="/raw-materials" class="btn btn--secondary">Cancel</a>
    </div>
</form>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
