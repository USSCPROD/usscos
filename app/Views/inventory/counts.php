<?php ob_start(); ?>
<?php
$th = 'padding:.5rem .8rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;'
    . 'color:#6b7280;background:#f8f9fb;border-bottom:1px solid #e5e7eb;text-align:left';
$td = 'padding:.6rem .8rem;font-size:.87rem';
$inp = 'width:100%;padding:.55rem .7rem;font-size:.9rem;font-family:inherit;border:1px solid #d1d5db;'
     . 'border-radius:6px;box-sizing:border-box;background:#fff;color:#111';

if (!function_exists('ccBadge')) {
    function ccBadge(string $s): array
    {
        return [
            'counting'  => ['badge--warning', 'Counting'],
            'review'    => ['badge--info',    'Awaiting review'],
            'applied'   => ['badge--success', 'Applied'],
            'cancelled' => ['badge--neutral', 'Cancelled'],
        ][$s] ?? ['badge--neutral', $s];
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <div style="font-size:.85rem;color:var(--color-text-muted);margin-bottom:.25rem">
            <a href="/inventory" style="color:inherit">Inventory</a> &rsaquo; Counts
        </div>
        <h1 class="page-title" style="margin:0">Cycle Counts</h1>
        <p class="page-subtitle" style="margin:.25rem 0 0">
            Rolling counts, so an error shows up in days rather than at year end. Counting is
            blind — the expected quantity is never shown to the person counting.
        </p>
    </div>
</div>

<div class="card" style="padding:1.1rem 1.25rem;margin-bottom:1.5rem;background:#f8fafc">
    <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.7rem">
        Start a count
    </div>
    <form method="POST" action="/inventory/counts">
        <?= csrf_field() ?>
        <table style="width:100%;border-collapse:separate;border-spacing:.5rem 0;margin:0 -.5rem">
            <tr>
                <td style="width:28%">
                    <select name="location_id" required style="<?= $inp ?>">
                        <option value="">Which location —</option>
                        <?php foreach ($locations as $l): ?>
                            <option value="<?= (int)$l['id'] ?>">
                                <?= $l['parent_code'] ? e($l['parent_code']) . ' · ' : '' ?><?= e($l['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td style="width:20%">
                    <select name="count_type" style="<?= $inp ?>">
                        <option value="cycle">Cycle count</option>
                        <option value="full">Full count</option>
                    </select>
                </td>
                <td>
                    <input type="text" name="notes" maxlength="500" placeholder="Note — optional" style="<?= $inp ?>">
                </td>
                <td style="width:140px">
                    <button type="submit" class="btn btn--primary" style="width:100%">Start</button>
                </td>
            </tr>
        </table>
    </form>
    <div style="font-size:.75rem;color:#9ca3af;margin-top:.6rem">
        The sheet is seeded with what the system thinks is there, but anything else found can
        be counted onto it — stock turning up where the system says there is none is exactly
        what these counts exist to find.
    </div>
</div>

<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden">
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr>
                <th style="<?= $th ?>">Count</th>
                <th style="<?= $th ?>">Where</th>
                <th style="<?= $th ?>;text-align:center">Status</th>
                <th style="<?= $th ?>;text-align:right">Progress</th>
                <th style="<?= $th ?>;text-align:right">Variances</th>
                <th style="<?= $th ?>">When</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($counts)): ?>
            <tr><td colspan="6" style="padding:2rem;text-align:center;color:#9ca3af">No counts yet.</td></tr>
        <?php else: ?>
            <?php foreach ($counts as $c): [$cls, $label] = ccBadge($c['status']); ?>
                <tr style="border-bottom:1px solid #f3f4f6">
                    <td style="<?= $td ?>">
                        <a href="/inventory/counts/<?= (int)$c['id'] ?>"
                           style="color:#0A3D91;font-weight:600;text-decoration:none"><?= e($c['count_number']) ?></a>
                        <?php if ($c['count_type'] === 'full'): ?>
                            <span class="badge badge--info" style="margin-left:.3rem">Full</span>
                        <?php endif; ?>
                    </td>
                    <td style="<?= $td ?>"><?= e($c['location_code']) ?></td>
                    <td style="<?= $td ?>;text-align:center"><span class="badge <?= $cls ?>"><?= $label ?></span></td>
                    <td style="<?= $td ?>;text-align:right">
                        <?= (int)$c['counted'] ?> of <?= (int)$c['line_count'] ?>
                    </td>
                    <td style="<?= $td ?>;text-align:right;<?= (int)$c['variances'] > 0 ? 'color:#b45309;font-weight:600' : 'color:#9ca3af' ?>">
                        <?= $c['status'] === 'counting' ? '—' : (int)$c['variances'] ?>
                    </td>
                    <td style="<?= $td ?>;color:#9ca3af;font-size:.82rem"><?= date('M j', strtotime($c['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<p style="font-size:.78rem;color:#9ca3af;margin:1rem 0 2rem;max-width:46rem">
    Variances are hidden while a count is in progress, on purpose. Shown the expected figure,
    a tired person at the end of a shift confirms it rather than counts it — and the count
    becomes a transcription of the number it was meant to check.
</p>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
