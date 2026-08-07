<?php
$isEdit     = !empty($task);
$action     = $isEdit ? '/tasks/' . (int)$task['id'] . '/edit' : '/tasks';
$v          = fn(string $k, mixed $d = '') => $isEdit ? ($task[$k] ?? $d) : ($preset[$k] ?? $d);
$hasPreset  = !empty($presetLabel);
$content    = ob_start(); ?>

<div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem">
    <a href="/tasks" style="color:#6b7280;text-decoration:none;font-size:.875rem">← Tasks</a>
    <h1 style="margin:0;font-size:1.5rem;font-weight:800;color:#111"><?= $title ?></h1>
</div>

<?php if ($hasPreset): ?>
<div style="margin-bottom:1.25rem;padding:.6rem 1rem;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;font-size:.875rem;color:#1d4ed8">
    Linked to: <strong><?= e($presetLabel) ?></strong>
</div>
<?php endif; ?>

<form method="POST" action="<?= $action ?>">
<?= csrf_field() ?>

    <?php if ($hasPreset && !$isEdit): ?>
        <?php foreach ($preset as $k => $val): ?>
            <?php if ($val): ?><input type="hidden" name="<?= $k ?>" value="<?= (int)$val ?>"><?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <div style="max-width:700px">
        <div class="card" style="padding:1.5rem">

            <!-- Title -->
            <div style="margin-bottom:1.25rem">
                <label class="label">Task Title <span style="color:#ef4444">*</span></label>
                <input type="text" name="title" value="<?= e((string)$v('title')) ?>" class="input" style="width:100%;box-sizing:border-box" required autofocus placeholder="What needs to be done?">
            </div>

            <!-- Description -->
            <div style="margin-bottom:1.25rem">
                <label class="label">Description</label>
                <textarea name="description" rows="3" class="input" style="width:100%;box-sizing:border-box;resize:vertical" placeholder="Optional details…"><?= e((string)$v('description')) ?></textarea>
            </div>

            <!-- Priority / Status row -->
            <table style="width:100%;border-collapse:collapse;margin-bottom:1.25rem">
                <tr>
                    <td style="padding-right:1rem;width:50%;vertical-align:top">
                        <label class="label">Priority</label>
                        <select name="priority" class="input" style="width:100%;box-sizing:border-box">
                            <?php foreach (['low'=>'Low','medium'=>'Medium','high'=>'High','urgent'=>'Urgent'] as $val=>$lbl): ?>
                            <option value="<?= $val ?>" <?= ($v('priority','medium')===$val)?'selected':'' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td style="width:50%;vertical-align:top">
                        <label class="label">Status</label>
                        <select name="status" class="input" style="width:100%;box-sizing:border-box">
                            <?php foreach (['open'=>'Open','in_progress'=>'In Progress','completed'=>'Completed','cancelled'=>'Cancelled'] as $val=>$lbl): ?>
                            <option value="<?= $val ?>" <?= ($v('status','open')===$val)?'selected':'' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>

            <!-- Assigned / Due date row -->
            <table style="width:100%;border-collapse:collapse;margin-bottom:1.5rem">
                <tr>
                    <td style="padding-right:1rem;width:50%;vertical-align:top">
                        <label class="label">Assigned To</label>
                        <select name="assigned_to" class="input" style="width:100%;box-sizing:border-box">
                            <option value="">— Unassigned —</option>
                            <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= ((int)$v('assigned_to')===(int)$u['id'])?'selected':'' ?>>
                                <?= e($u['first_name'].' '.$u['last_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td style="width:50%;vertical-align:top">
                        <label class="label">Due Date</label>
                        <input type="date" name="due_date" value="<?= e((string)$v('due_date')) ?>" class="input" style="width:100%;box-sizing:border-box">
                    </td>
                </tr>
            </table>

            <!-- Link To (shown when not arriving from a record page) -->
            <?php if (!$hasPreset): ?>
            <div style="border-top:1px solid #e5e7eb;padding-top:1.25rem;margin-bottom:1.5rem">
                <label class="label" style="margin-bottom:.75rem;display:block">Link To <span style="color:#9ca3af;font-weight:400">(optional)</span></label>
                <table style="width:100%;border-collapse:collapse;margin-bottom:.75rem">
                    <tr>
                        <td style="padding-right:.75rem;width:40%;vertical-align:top">
                            <label class="label" style="font-size:.75rem">Type</label>
                            <select id="linkType" class="input" style="width:100%;box-sizing:border-box" onchange="updateLinkRecord(this.value)">
                                <option value="">— None —</option>
                                <option value="customer"     <?= !empty($v('customer_id'))    ?'selected':'' ?>>Customer</option>
                                <option value="lead"         <?= !empty($v('lead_id'))        ?'selected':'' ?>>Lead</option>
                                <option value="opportunity"  <?= !empty($v('opportunity_id')) ?'selected':'' ?>>Opportunity</option>
                                <option value="quote"        <?= !empty($v('quote_id'))       ?'selected':'' ?>>Quote</option>
                                <option value="sales_order"  <?= !empty($v('sales_order_id')) ?'selected':'' ?>>Sales Order</option>
                            </select>
                        </td>
                        <td style="width:60%;vertical-align:top">
                            <label class="label" style="font-size:.75rem">Record</label>
                            <div id="linkRecordWrap">
                                <?php
                                // Build all select boxes, show only the active one
                                $linkFields = [
                                    'customer'    => ['customer_id',    $linkOptions['customers']],
                                    'lead'        => ['lead_id',        $linkOptions['leads']],
                                    'opportunity' => ['opportunity_id', $linkOptions['opportunities']],
                                    'quote'       => ['quote_id',       $linkOptions['quotes']],
                                    'sales_order' => ['sales_order_id', $linkOptions['sales_orders']],
                                ];
                                foreach ($linkFields as $type => [$field, $options]):
                                    $currentVal = (int)$v($field);
                                    $isActive   = !empty($currentVal);
                                ?>
                                <select name="<?= $field ?>" id="linkSel_<?= $type ?>"
                                    class="input" style="width:100%;box-sizing:border-box;display:<?= $isActive ? 'block' : 'none' ?>">
                                    <option value="">— Select —</option>
                                    <?php foreach ($options as $opt): ?>
                                    <option value="<?= (int)$opt['id'] ?>" <?= $currentVal===(int)$opt['id']?'selected':'' ?>><?= e($opt['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php endforeach; ?>
                                <div id="linkNone" style="color:#9ca3af;font-size:.875rem;padding:.5rem 0;<?= (!empty($v('customer_id')) || !empty($v('lead_id')) || !empty($v('opportunity_id')) || !empty($v('quote_id')) || !empty($v('sales_order_id'))) ? 'display:none' : '' ?>">Select a type first</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            <?php endif; ?>

            <!-- Actions -->
            <div style="display:flex;gap:.75rem">
                <button type="submit" class="btn btn--primary"><?= $isEdit ? 'Save Changes' : 'Create Task' ?></button>
                <a href="/tasks" class="btn btn--secondary">Cancel</a>
            </div>

        </div>
    </div>
</form>

<script>
function updateLinkRecord(type) {
    var types = ['customer','lead','opportunity','quote','sales_order'];
    types.forEach(function(t) {
        var sel = document.getElementById('linkSel_' + t);
        if (sel) {
            sel.style.display = 'none';
            sel.value = '';
        }
    });
    var none = document.getElementById('linkNone');
    if (!type) {
        if (none) none.style.display = '';
        return;
    }
    if (none) none.style.display = 'none';
    var active = document.getElementById('linkSel_' + type);
    if (active) active.style.display = 'block';
}
</script>

<?php $content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
