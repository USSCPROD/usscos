<?php ob_start();

$flash      = \App\Core\Session::getFlash('success');
$flashError = \App\Core\Session::getFlash('error');

$periodLabel = match ($periodType) {
    'year'    => date('Y', strtotime($periodStart)),
    'quarter' => 'Q' . ceil((int)date('n', strtotime($periodStart)) / 3) . ' ' . date('Y', strtotime($periodStart)),
    default   => date('F Y', strtotime($periodStart)),
};

/* Shifting by period, not by a date picker. "Last month" is the question people have; the
   date is only how it is stored. */
$shift = static function (string $type, string $start, int $by): string {
    $unit = match ($type) { 'year' => 'year', 'quarter' => '3 months', default => 'month' };
    return date('Y-m-d', strtotime($start . ' ' . ($by > 0 ? '+' : '-') . abs($by) . ' ' . $unit));
};
?>

<?php if ($flash): ?><div class="alert alert--success" style="margin-bottom:1rem"><?= e($flash) ?></div><?php endif; ?>
<?php if ($flashError): ?><div class="alert alert--danger" style="margin-bottom:1rem"><?= e($flashError) ?></div><?php endif; ?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/admin" class="back-link">Admin</a>
        <h1 class="page-title">Sales goals — <?= e($periodLabel) ?></h1>
    </div>
</div>

<p class="text-muted" style="margin:-.5rem 0 1rem;max-width:760px;font-size:.9rem">
    Targets per rep, compared against what has actually been invoiced to their accounts.
    Leave a box empty to leave that rep's target alone; enter <strong>0</strong> to remove one.
</p>

<div class="card" style="padding:.75rem 1.1rem;margin-bottom:1rem;display:flex;gap:.4rem;flex-wrap:wrap;align-items:center">
    <?php foreach (['month' => 'Monthly', 'quarter' => 'Quarterly', 'year' => 'Yearly'] as $typeKey => $typeLabel): ?>
        <a href="/admin/sales-goals?period=<?= e($typeKey) ?>"
           class="btn btn--sm <?= $periodType === $typeKey ? 'btn--primary' : 'btn--secondary' ?>"><?= e($typeLabel) ?></a>
    <?php endforeach; ?>
    <span style="margin-left:auto;display:flex;gap:.4rem">
        <a class="btn btn--secondary btn--sm" href="/admin/sales-goals?period=<?= e($periodType) ?>&date=<?= e($shift($periodType, $periodStart, -1)) ?>">&larr; Previous</a>
        <a class="btn btn--secondary btn--sm" href="/admin/sales-goals?period=<?= e($periodType) ?>&date=<?= e($shift($periodType, $periodStart, 1)) ?>">Next &rarr;</a>
    </span>
</div>

<?php if ($reps === []): ?>
    <div class="card" style="padding:1.5rem 1.1rem;color:#6b7280;font-size:.9rem">
        No active selling reps. Goals apply to reps of type Sales Rep, Employee or Owner.
    </div>
<?php else: ?>
<form method="POST" action="/admin/sales-goals">
    <?= csrf_field() ?>
    <input type="hidden" name="period_type"  value="<?= e($periodType) ?>">
    <input type="hidden" name="period_start" value="<?= e($periodStart) ?>">

    <div class="card" style="padding:0;overflow:hidden;max-width:900px">
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="background:#fafafa;border-bottom:1px solid #e5e7eb">
                    <th style="text-align:left;padding:.55rem 1.1rem;font-size:.75rem;color:#6b7280;text-transform:uppercase">Rep</th>
                    <th style="text-align:right;padding:.55rem .5rem;font-size:.75rem;color:#6b7280;text-transform:uppercase">Target</th>
                    <th style="text-align:right;padding:.55rem .5rem;font-size:.75rem;color:#6b7280;text-transform:uppercase">Invoiced</th>
                    <th style="text-align:right;padding:.55rem 1.1rem;font-size:.75rem;color:#6b7280;text-transform:uppercase">Progress</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($reps as $rep):
                $repId  = (int)$rep['id'];
                $goal   = (float)($goals[$repId]['goal_amount'] ?? 0);
                $actual = (float)($actuals[$repId]['total'] ?? 0);
                $pct    = $goal > 0 ? ($actual / $goal) * 100 : null;
            ?>
                <tr style="border-bottom:1px solid #f1f2f5">
                    <td style="padding:.55rem 1.1rem">
                        <?= e($rep['name']) ?>
                        <?php if (($rep['rep_type'] ?? '') !== 'person'): ?>
                            <span class="badge badge--neutral" style="margin-left:.35rem"><?= e(ucfirst((string)$rep['rep_type'])) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:.4rem .5rem;text-align:right">
                        <input type="number" name="goal[<?= $repId ?>]" step="0.01" min="0"
                               value="<?= $goal > 0 ? number_format($goal, 2, '.', '') : '' ?>"
                               placeholder="—"
                               style="width:9rem;padding:.35rem .5rem;border:1px solid #d1d5db;border-radius:5px;text-align:right">
                    </td>
                    <td style="padding:.55rem .5rem;text-align:right;white-space:nowrap">
                        <?= money($actual) ?>
                        <?php if (($actuals[$repId]['n'] ?? 0) > 0): ?>
                            <?php $invCount = (int)$actuals[$repId]['n']; ?>
                            <div class="text-muted" style="font-size:.76rem"><?= $invCount ?> invoice<?= $invCount === 1 ? '' : 's' ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="padding:.55rem 1.1rem;text-align:right;white-space:nowrap">
                        <?php if ($pct === null): ?>
                            <span class="text-muted">no target</span>
                        <?php else: ?>
                            <strong style="color:<?= $pct >= 100 ? '#16a34a' : ($pct >= 70 ? '#111' : '#b45309') ?>">
                                <?= number_format($pct, 0) ?>%
                            </strong>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div style="padding:.75rem 1.1rem;border-top:1px solid #e5e7eb;background:#fafafa;text-align:right">
            <button type="submit" class="btn btn--primary btn--sm">Save targets</button>
        </div>
    </div>
</form>
<?php endif; ?>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
