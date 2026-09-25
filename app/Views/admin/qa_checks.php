<?php ob_start(); ?>
<?php
$inp = 'width:100%;padding:.55rem .7rem;font-size:.92rem;font-family:inherit;border:1px solid #d1d5db;'
     . 'border-radius:6px;box-sizing:border-box;background:#fff;color:#111';
$active = array_filter($checks, fn($c) => (int)$c['is_active'] === 1);
?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/admin" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Admin
        </a>
        <h1 class="page-title">Quality Checks</h1>
        <p class="page-subtitle" style="margin:.25rem 0 0">
            What gets checked on a job before it ships. These appear on every sales order,
            and the answers stay with the job.
        </p>
    </div>
</div>

<?php if (empty($active)): ?>
    <div class="alert" style="background:#fffbeb;border:1px solid #fcd34d;color:#92400e;padding:1rem 1.15rem;margin-bottom:1.25rem">
        <strong>No checks yet, and none have been invented for you.</strong>
        <p style="margin:.4rem 0 0;font-size:.88rem">
            A plausible checklist would get followed, and what USSC actually checks before a
            job ships is not something to guess at. Add the checks people really do — even
            if that is only two or three to begin with. A short list that is honest beats a
            long one that gets ticked without looking.
        </p>
    </div>
<?php endif; ?>

<div class="card" style="padding:1.1rem 1.25rem;margin-bottom:1.5rem;background:#f8fafc">
    <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.7rem">
        Add a check
    </div>
    <form method="POST" action="/admin/qa-checks">
        <?= csrf_field() ?>
        <table style="width:100%;border-collapse:separate;border-spacing:.5rem 0;margin:0 -.5rem">
            <tr>
                <td>
                    <input type="text" name="label" required maxlength="200"
                           placeholder="The question as somebody at the bench reads it"
                           style="<?= $inp ?>">
                </td>
                <td>
                    <input type="text" name="help" maxlength="255"
                           placeholder="What good looks like — optional"
                           style="<?= $inp ?>">
                </td>
                <td style="width:9rem;white-space:nowrap">
                    <label style="font-size:.85rem;color:#374151;display:flex;align-items:center;gap:.35rem">
                        <input type="checkbox" name="is_required" value="1" checked>
                        Required
                    </label>
                </td>
                <td style="width:7rem">
                    <button type="submit" class="btn btn--primary" style="width:100%">Add</button>
                </td>
            </tr>
        </table>
    </form>
</div>

<div class="card" style="padding:0">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Check</th>
                    <th class="text-center">Required</th>
                    <th class="text-right">Answered on</th>
                    <th class="text-center">In use</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($checks)): ?>
                <tr><td colspan="5" class="table__empty">No checks defined.</td></tr>
            <?php else: ?>
                <?php foreach ($checks as $c): ?>
                    <tr<?= (int)$c['is_active'] === 0 ? ' style="opacity:.55"' : '' ?>>
                        <td>
                            <div style="font-weight:500"><?= e($c['label']) ?></div>
                            <?php if (!empty($c['help'])): ?>
                                <div class="text-xs text-muted"><?= e($c['help']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?= (int)$c['is_required'] === 1 ? 'Yes' : '<span class="text-muted">Optional</span>' ?>
                        </td>
                        <td class="text-right text-sm text-muted">
                            <?= (int)$c['times_answered'] > 0 ? number_format((int)$c['times_answered']) . ' jobs' : '—' ?>
                        </td>
                        <td class="text-center">
                            <span class="badge <?= (int)$c['is_active'] === 1 ? 'badge--success' : 'badge--neutral' ?>">
                                <?= (int)$c['is_active'] === 1 ? 'In use' : 'Retired' ?>
                            </span>
                        </td>
                        <td class="text-right">
                            <form method="POST" action="/admin/qa-checks/<?= (int)$c['id'] ?>/retire" style="margin:0">
                                <?= csrf_field() ?>
                                <?php if ((int)$c['is_active'] === 1): ?>
                                    <button type="submit" class="btn btn--secondary btn--sm">Retire</button>
                                <?php else: ?>
                                    <input type="hidden" name="restore" value="1">
                                    <button type="submit" class="btn btn--secondary btn--sm">Put back</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<p style="font-size:.78rem;color:#9ca3af;margin:1rem 0 2rem;max-width:46rem">
    A check is <strong>retired, never deleted</strong>. Answers already recorded against it
    are evidence of what was checked on jobs that have shipped, and deleting the check would
    take them with it. Retiring it removes it from new jobs and leaves the history intact.
</p>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
