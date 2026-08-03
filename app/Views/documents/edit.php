<?php ob_start(); ?>
<?php
$docAudiences = explode(',', $doc['audience'] ?? 'internal');
?>

<div class="page-header">
    <div class="page-header__left">
        <div style="font-size:.85rem;color:var(--color-text-muted);margin-bottom:.25rem">
            <a href="/documents" style="color:inherit">Documents</a> &rsaquo; Edit
        </div>
        <h1 class="page-title" style="margin:0">Edit Document</h1>
    </div>
</div>

<div style="max-width:580px">
    <form method="POST" action="/documents/<?= (int)$doc['id'] ?>/edit">
        <?= csrf_field() ?>

        <div class="card" style="padding:1.5rem">

            <div style="margin-bottom:1rem">
                <label class="label">Title <span style="color:var(--color-danger)">*</span></label>
                <input type="text" name="title" value="<?= e($doc['title']) ?>" class="input" style="width:100%" required>
            </div>

            <div style="margin-bottom:1rem">
                <label class="label">Category <span style="color:var(--color-danger)">*</span></label>
                <select name="document_category_id" class="input" style="width:100%" required>
                    <?php foreach ($allCategories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= (int)$doc['document_category_id'] === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom:1rem">
                <label class="label">Visible To <span style="color:var(--color-danger)">*</span></label>
                <div style="display:flex;flex-direction:column;gap:.5rem;margin-top:.4rem">
                    <?php foreach ($allAudiences as $key => $label): ?>
                        <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;font-size:.9rem">
                            <input type="checkbox" name="audience[]" value="<?= $key ?>"
                                   <?= in_array($key, $docAudiences) ? 'checked' : '' ?>
                                   style="width:1rem;height:1rem">
                            <?= e($label) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="margin-bottom:1.5rem">
                <label class="label">Description <span class="text-muted" style="font-weight:400">(optional)</span></label>
                <textarea name="description" class="input" rows="3" style="width:100%"><?= e($doc['description'] ?? '') ?></textarea>
            </div>

            <div class="text-muted" style="font-size:.8rem;margin-bottom:1.25rem;padding:.75rem;background:var(--color-surface-2);border-radius:6px">
                <strong>File:</strong> <?= e($doc['original_filename']) ?><br>
                <em>To replace the file, delete this document and upload a new one.</em>
            </div>

            <div style="display:flex;gap:.75rem;justify-content:flex-end">
                <a href="/documents" class="btn btn--secondary">Cancel</a>
                <button type="submit" class="btn btn--primary">Save Changes</button>
            </div>

        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
