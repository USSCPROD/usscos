<?php ob_start(); ?>
<?php
$flash      = \App\Core\Session::getFlash('success');
$flashError = \App\Core\Session::getFlash('error');
$s = $settings ?? [];
$val = fn(string $k) => e($s[$k] ?? '');

// Paint round-robin pool — stored as comma-separated user IDs
$paintPool = array_filter(explode(',', $s['lead_routing_paint_pool'] ?? ''));
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
            <a href="/admin" style="color:inherit">Admin</a> &rsaquo; Lead Routing
        </div>
        <h1 class="page-title" style="margin:0">Lead Routing</h1>
        <p class="page-subtitle">Control who gets notified and who new leads are assigned to for each form type.</p>
    </div>
</div>

<form method="POST" action="/admin/lead-routing" style="max-width:680px">
    <?= csrf_field() ?>

    <!-- Stencil -->
    <div class="card" style="padding:1.5rem;margin-bottom:1.25rem">
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:.25rem">Stencil Quote Requests</div>
        <div style="font-size:.875rem;color:var(--color-text-muted);margin-bottom:1.25rem">Leads submitted through the Stencil Quote form on the website.</div>
        <table style="width:100%;border-collapse:collapse">
            <tr>
                <td style="padding:0 .75rem 1rem 0;width:50%;vertical-align:top">
                    <label class="label">Notification Email</label>
                    <input type="email" name="lead_routing_stencil_email" class="input" style="width:100%"
                           value="<?= $val('lead_routing_stencil_email') ?>" placeholder="e.g. chip@usscproducts.com">
                    <div style="font-size:.78rem;color:var(--color-text-muted);margin-top:.3rem">Receives the new lead notification email.</div>
                </td>
                <td style="padding:0 0 1rem .75rem;width:50%;vertical-align:top">
                    <label class="label">Auto-Assign To</label>
                    <select name="lead_routing_stencil_user_id" class="input" style="width:100%">
                        <option value="">— Unassigned —</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= (int)$u['id'] ?>" <?= (string)($s['lead_routing_stencil_user_id'] ?? '') === (string)$u['id'] ? 'selected' : '' ?>>
                                <?= e($u['first_name'] . ' ' . $u['last_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div style="font-size:.78rem;color:var(--color-text-muted);margin-top:.3rem">Lead will be assigned to this person in USSCOS.</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Paint — round robin -->
    <div class="card" style="padding:1.5rem;margin-bottom:1.25rem">
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:.25rem">Paint Quote Requests</div>
        <div style="font-size:.875rem;color:var(--color-text-muted);margin-bottom:1.25rem">
            Leads are distributed in round-robin order across the reps below. Each new lead goes to the next person in the list.
        </div>

        <div style="margin-bottom:1.25rem">
            <label class="label">Notification Email</label>
            <input type="email" name="lead_routing_paint_email" class="input" style="max-width:320px"
                   value="<?= $val('lead_routing_paint_email') ?>" placeholder="e.g. sales@usscproducts.com">
            <div style="font-size:.78rem;color:var(--color-text-muted);margin-top:.3rem">Fallback notification email (also used if no pool is set).</div>
        </div>

        <label class="label">Round-Robin Pool</label>
        <div style="font-size:.78rem;color:var(--color-text-muted);margin-bottom:.6rem">Check the reps who should receive paint leads. Order is top-to-bottom.</div>

        <div style="border:1px solid var(--color-border);border-radius:7px;overflow:hidden">
            <?php foreach ($users as $i => $u): ?>
                <label style="display:flex;align-items:center;gap:.75rem;padding:.65rem 1rem;cursor:pointer;background:<?= $i % 2 === 0 ? 'var(--color-surface)' : 'transparent' ?>;border-bottom:<?= $i < count($users) - 1 ? '1px solid var(--color-border)' : 'none' ?>">
                    <input type="checkbox" name="lead_routing_paint_pool[]" value="<?= (int)$u['id'] ?>"
                           <?= in_array((string)$u['id'], $paintPool) ? 'checked' : '' ?>
                           style="width:16px;height:16px;cursor:pointer;flex-shrink:0">
                    <span style="flex:1;font-size:.9rem">
                        <?= e($u['first_name'] . ' ' . $u['last_name']) ?>
                    </span>
                    <span style="font-size:.78rem;color:var(--color-text-muted)"><?= e($u['email']) ?></span>
                </label>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($s['lead_routing_paint_next_index'])): ?>
        <div style="margin-top:.75rem;font-size:.8rem;color:var(--color-text-muted)">
            Next assignment: position <?= (int)$s['lead_routing_paint_next_index'] + 1 ?> in the pool.
            <a href="/admin/lead-routing/reset-paint" style="color:var(--color-primary);margin-left:.5rem">Reset rotation</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- General -->
    <div class="card" style="padding:1.5rem;margin-bottom:1.25rem">
        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--color-text-muted);margin-bottom:.25rem">General Contact Form</div>
        <div style="font-size:.875rem;color:var(--color-text-muted);margin-bottom:1.25rem">Leads submitted through the Contact Us form on the website.</div>
        <table style="width:100%;border-collapse:collapse">
            <tr>
                <td style="padding:0 .75rem 1rem 0;width:50%;vertical-align:top">
                    <label class="label">Notification Email</label>
                    <input type="email" name="lead_routing_general_email" class="input" style="width:100%"
                           value="<?= $val('lead_routing_general_email') ?>" placeholder="e.g. sales@usscproducts.com">
                    <div style="font-size:.78rem;color:var(--color-text-muted);margin-top:.3rem">Receives the new lead notification email.</div>
                </td>
                <td style="padding:0 0 1rem .75rem;width:50%;vertical-align:top">
                    <label class="label">Auto-Assign To</label>
                    <select name="lead_routing_general_user_id" class="input" style="width:100%">
                        <option value="">— Unassigned —</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= (int)$u['id'] ?>" <?= (string)($s['lead_routing_general_user_id'] ?? '') === (string)$u['id'] ? 'selected' : '' ?>>
                                <?= e($u['first_name'] . ' ' . $u['last_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div style="font-size:.78rem;color:var(--color-text-muted);margin-top:.3rem">Lead will be assigned to this person in USSCOS.</div>
                </td>
            </tr>
        </table>
    </div>

    <div style="display:flex;gap:.75rem">
        <button type="submit" class="btn btn--primary">Save Settings</button>
        <a href="/admin" class="btn btn--secondary">Cancel</a>
    </div>
</form>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
