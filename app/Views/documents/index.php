<?php ob_start(); ?>
<?php
$flash      = \App\Core\Session::getFlash('success');
$flashError = \App\Core\Session::getFlash('error');

function docFileIcon(string $mime): string {
    if (str_contains($mime, 'pdf'))        return '📄';
    if (str_contains($mime, 'word'))       return '📝';
    if (str_contains($mime, 'excel') || str_contains($mime, 'spreadsheet') || str_contains($mime, 'csv')) return '📊';
    if (str_contains($mime, 'powerpoint') || str_contains($mime, 'presentation')) return '📋';
    if (str_contains($mime, 'image'))      return '🖼';
    return '📎';
}

function docFileSize(int $bytes): string {
    if ($bytes < 1024)        return $bytes . ' B';
    if ($bytes < 1048576)     return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 1) . ' MB';
}

function docAudienceBadges(string $audience): string {
    $labels = ['internal' => 'Internal', 'reps' => 'Reps', 'distributors' => 'Distributors', 'customers' => 'Customers'];
    $colors = ['internal' => 'badge--neutral', 'reps' => 'badge--info', 'distributors' => 'badge--info', 'customers' => 'badge--success'];
    $parts = explode(',', $audience);
    $out = [];
    foreach ($parts as $a) {
        $a = trim($a);
        if (isset($labels[$a])) {
            $out[] = '<span class="badge ' . $colors[$a] . '" style="font-size:.7rem">' . $labels[$a] . '</span>';
        }
    }
    return implode(' ', $out);
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
        <h1 class="page-title">Documents</h1>
        <p class="page-subtitle">Company resources and files</p>
    </div>
    <?php if ($canUpload): ?>
    <div class="page-header__right">
        <button class="btn btn--primary" onclick="document.getElementById('upload-modal').style.display='flex'">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Upload Document
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Search -->
<div class="toolbar" style="margin-bottom:1.25rem">
    <form class="toolbar__search" method="GET" action="/documents">
        <div class="search-wrap">
            <svg class="search-wrap__icon" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/></svg>
            <input type="text" name="q" class="input search-wrap__input" placeholder="Search documents…" value="<?= e($search) ?>" autocomplete="off" style="padding:.65rem .85rem .65rem 2.5rem;font-size:1rem;height:auto">
        </div>
    </form>
</div>

<?php if (empty($grouped)): ?>
    <div class="card" style="padding:3rem;text-align:center;color:var(--color-text-muted)">
        <?= $search ? 'No documents match your search.' : 'No documents have been uploaded yet.' ?>
    </div>
<?php else: ?>
    <?php foreach ($grouped as $categoryName => $docs): ?>
        <div class="card" style="margin-bottom:1.25rem;padding:0;overflow:hidden">
            <!-- Category header -->
            <div style="padding:.75rem 1.25rem;background:var(--color-surface-2);border-bottom:1px solid var(--color-border);display:flex;align-items:center;gap:.6rem">
                <span style="font-weight:600;font-size:.95rem"><?= e($categoryName) ?></span>
                <span class="text-muted" style="font-size:.8rem;margin-left:auto"><?= count($docs) ?> file<?= count($docs) !== 1 ? 's' : '' ?></span>
            </div>
            <!-- Files -->
            <table class="table" style="margin:0">
                <tbody>
                    <?php foreach ($docs as $doc): ?>
                        <tr>
                            <td style="width:2rem;padding:.7rem .75rem .7rem 1.25rem;font-size:1.2rem">
                                <?= docFileIcon($doc['mime_type'] ?? '') ?>
                            </td>
                            <td style="padding:.7rem .5rem">
                                <div style="font-weight:500"><?= e($doc['title']) ?></div>
                                <?php if (!empty($doc['description'])): ?>
                                    <div class="text-xs text-muted"><?= e($doc['description']) ?></div>
                                <?php endif; ?>
                                <?php if ($canUpload): ?>
                                    <div style="margin-top:.25rem"><?= docAudienceBadges($doc['audience'] ?? 'internal') ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted" style="padding:.7rem .5rem;font-size:.8rem;white-space:nowrap">
                                <?= e($doc['original_filename']) ?>
                            </td>
                            <td class="text-muted" style="padding:.7rem .5rem;font-size:.8rem;white-space:nowrap">
                                <?= docFileSize((int)$doc['file_size']) ?>
                            </td>
                            <td class="text-muted" style="padding:.7rem .5rem;font-size:.8rem;white-space:nowrap">
                                <?= date('M j, Y', strtotime($doc['created_at'])) ?>
                            </td>
                            <td style="padding:.7rem 1.25rem .7rem .5rem;text-align:right;white-space:nowrap">
                                <a href="/documents/<?= (int)$doc['id'] ?>/download"
                                   class="btn btn--secondary" style="padding:.3rem .75rem;font-size:.8rem">
                                    Download
                                </a>
                                <?php if ($canUpload): ?>
                                    <a href="/documents/<?= (int)$doc['id'] ?>/edit"
                                       class="btn btn--secondary" style="padding:.3rem .75rem;font-size:.8rem;margin-left:.35rem">
                                        Edit
                                    </a>
                                    <form method="POST" action="/documents/<?= (int)$doc['id'] ?>/delete"
                                          style="display:inline-block;margin-left:.35rem"
                                          onsubmit="return confirm('Remove this document?')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn--danger" style="padding:.3rem .75rem;font-size:.8rem">
                                            Remove
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if ($canUpload): ?>
<!-- Upload Modal -->
<div id="upload-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center">
    <div class="card" style="width:100%;max-width:540px;margin:1rem;padding:1.5rem;max-height:90vh;overflow-y:auto">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
            <h2 style="margin:0;font-size:1.1rem">Upload Document</h2>
            <button type="button" onclick="document.getElementById('upload-modal').style.display='none'"
                    style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:var(--color-text-muted)">&times;</button>
        </div>

        <form method="POST" action="/documents" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div style="margin-bottom:1rem">
                <label class="label">Title <span style="color:var(--color-danger)">*</span></label>
                <input type="text" name="title" class="input" style="width:100%" required placeholder="e.g. Employee Handbook 2026">
            </div>

            <div style="margin-bottom:1rem">
                <label class="label">Category <span style="color:var(--color-danger)">*</span></label>
                <select name="document_category_id" class="input" style="width:100%" required>
                    <option value="">— Select category —</option>
                    <?php foreach ($allCategories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>"><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom:1rem">
                <label class="label">Visible To <span style="color:var(--color-danger)">*</span></label>
                <div style="display:flex;flex-direction:column;gap:.5rem;margin-top:.4rem">
                    <?php foreach ($allAudiences as $key => $label): ?>
                        <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;font-size:.9rem">
                            <input type="checkbox" name="audience[]" value="<?= $key ?>"
                                   <?= $key === 'internal' ? 'checked' : '' ?>
                                   style="width:1rem;height:1rem">
                            <?= e($label) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="margin-bottom:1rem">
                <label class="label">Description <span class="text-muted" style="font-weight:400">(optional)</span></label>
                <textarea name="description" class="input" rows="2" style="width:100%" placeholder="Brief description of this document"></textarea>
            </div>

            <div style="margin-bottom:1.5rem">
                <label class="label">File <span style="color:var(--color-danger)">*</span></label>
                <input type="file" name="file" class="input" style="width:100%;padding:.5rem" required
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.webp,.txt,.csv">
                <div class="text-xs text-muted" style="margin-top:.3rem">PDF, Word, Excel, PowerPoint, images, CSV — max 64 MB</div>
            </div>

            <div style="display:flex;gap:.75rem;justify-content:flex-end">
                <button type="button" onclick="document.getElementById('upload-modal').style.display='none'" class="btn btn--secondary">Cancel</button>
                <button type="submit" class="btn btn--primary">Upload</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
