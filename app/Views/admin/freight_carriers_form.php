<?php ob_start(); ?>
<?php $isEdit = $item !== null; ?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/admin/freight-carriers" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Freight Carriers
        </a>
        <h1 class="page-title"><?= $isEdit ? 'Edit Carrier' : 'Add Carrier' ?></h1>
    </div>
</div>

<form method="POST" action="<?= $isEdit ? '/admin/freight-carriers/' . (int)$item['id'] . '/edit' : '/admin/freight-carriers' ?>">
    <?= csrf_field() ?>
    <div class="card" style="padding:1.5rem;max-width:620px">

        <div class="form-group">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="input" required
                   value="<?= e($item['name'] ?? '') ?>" placeholder="Old Dominion Freight Line">
        </div>

        <div class="form-group">
            <label class="form-label">SCAC</label>
            <input type="text" name="scac" class="input" maxlength="10"
                   value="<?= e($item['scac'] ?? '') ?>" placeholder="ODFL">
            <div class="text-muted text-sm" style="margin-top:.35rem">
                The four-letter carrier code on the bill of lading. Optional.
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Tracking link</label>
            <input type="text" name="tracking_url_template" class="input"
                   value="<?= e($item['tracking_url_template'] ?? '') ?>"
                   placeholder="https://www.odfl.com/trace?pro={tracking}">
            <div class="text-muted text-sm" style="margin-top:.35rem">
                <code>{tracking}</code> is replaced by the PRO number. Leave it empty if the
                carrier has no public lookup — a link that goes nowhere is worse than
                showing the number plainly.
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="input" value="<?= e($item['phone'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label class="form-label">Notes</label>
            <input type="text" name="notes" class="input" value="<?= e($item['notes'] ?? '') ?>"
                   placeholder="e.g. booked through Keubix, 3-day lanes to the southeast">
        </div>

        <div class="form-group">
            <label class="form-label">Sort order</label>
            <input type="number" name="sort_order" class="input" style="max-width:8rem"
                   value="<?= (int)($item['sort_order'] ?? 0) ?>">
        </div>

        <label class="checkbox-label">
            <input type="checkbox" name="is_active" value="1" <?= ($item['is_active'] ?? 1) ? 'checked' : '' ?>>
            Active — offered when recording a freight shipment
        </label>
    </div>

    <div class="form-actions">
        <a href="/admin/freight-carriers" class="btn btn--secondary">Cancel</a>
        <button type="submit" class="btn btn--primary">Save Carrier</button>
    </div>
</form>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
