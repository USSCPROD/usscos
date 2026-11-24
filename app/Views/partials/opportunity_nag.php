<?php
/**
 * Open opportunities nobody is doing anything about.
 *
 * Expects $nag — rows from OpportunityRepository::needingAttention().
 * Optional $nagTitle, $nagEmpty.
 *
 * Included inside a page that has already started its buffer: no ob_start(), no layout.
 * The helper is guarded and uniquely named — two views declaring one function name in a
 * single request is fatal, and the page's buffer is discarded, so it reads as a broken
 * layout rather than a crash.
 */

if (!function_exists('nag_reason_label')) {
    /**
     * One reason per row, said in words a rep can act on. The query already picked the
     * strongest reason that applies, so this never has to combine them.
     *
     * @return array{0:string,1:string} [label, colour]
     */
    function nag_reason_label(string $reason, ?int $daysLate, int $daysQuiet): array
    {
        return match ($reason) {
            'next_step_overdue' => [
                'Next step ' . max(1, (int)$daysLate) . ' day' . ((int)$daysLate === 1 ? '' : 's') . ' late',
                '#b91c1c',
            ],
            'no_next_step'      => ['No next step set', '#b45309'],
            'close_date_passed' => [
                'Close date passed ' . max(1, (int)$daysLate) . ' day' . ((int)$daysLate === 1 ? '' : 's') . ' ago',
                '#0A3D91',
            ],
            default             => ['Nothing recorded for ' . $daysQuiet . ' days', '#475569'],
        };
    }
}

$nag      = $nag      ?? [];
$nagTitle = $nagTitle ?? 'Needs attention';
$nagEmpty = $nagEmpty ?? 'Nothing neglected — every open opportunity has a next step.';
?>

<div class="card" style="padding:0;overflow:hidden;margin-bottom:1rem">
    <div style="padding:.85rem 1.1rem;border-bottom:1px solid #e5e7eb;background:#f8f9fb;display:flex;align-items:center;gap:.5rem">
        <h3 style="margin:0;font-size:.95rem;font-weight:700;color:#111"><?= e($nagTitle) ?></h3>
        <?php if ($nag !== []): ?>
            <span style="background:#b91c1c;color:#fff;border-radius:999px;padding:.1rem .55rem;font-size:.72rem;font-weight:700">
                <?= count($nag) ?>
            </span>
        <?php endif; ?>
    </div>

    <?php if ($nag === []): ?>
        <div style="padding:1.5rem 1.1rem;color:#6b7280;font-size:.88rem"><?= e($nagEmpty) ?></div>
    <?php else: ?>
        <table style="width:100%;border-collapse:collapse">
            <tbody>
            <?php foreach ($nag as $row):
                [$label, $colour] = nag_reason_label(
                    (string)$row['reason'],
                    $row['days_late'] !== null ? (int)$row['days_late'] : null,
                    (int)$row['days_quiet']
                );
            ?>
                <tr style="border-bottom:1px solid #f1f2f5">
                    <td style="padding:.65rem 1.1rem;vertical-align:top">
                        <a href="/opportunities/<?= (int)$row['id'] ?>"
                           style="font-weight:600;color:#0A3D91;text-decoration:none"><?= e($row['name']) ?></a>
                        <div style="font-size:.8rem;color:#6b7280">
                            <?= $row['party'] ? e($row['party']) : '<span class="text-muted">no account linked</span>' ?>
                            &middot; <?= e(str_replace('_', ' ', (string)$row['stage'])) ?>
                            <?php if (!empty($row['rep_name'])): ?>&middot; <?= e($row['rep_name']) ?><?php endif; ?>
                        </div>
                        <?php if (!empty($row['next_step'])): ?>
                            <div style="font-size:.8rem;color:#374151;margin-top:.2rem">
                                Next: <?= e($row['next_step']) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="padding:.65rem 1.1rem;vertical-align:top;white-space:nowrap;text-align:right">
                        <div style="font-size:.88rem;font-weight:700;color:#111"><?= money((float)$row['expected_value']) ?></div>
                        <div style="font-size:.76rem;font-weight:700;color:<?= $colour ?>;margin-top:.2rem"><?= e($label) ?></div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
