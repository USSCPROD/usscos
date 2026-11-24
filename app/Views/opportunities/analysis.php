<?php ob_start();

use App\Services\LostReasons;

/*
 * Win/loss. Two figures per row throughout — count and value — because they answer
 * different questions: a rep who wins nine small deals and loses one large one has a 90%
 * win rate and has lost the quarter.
 */

if (!function_exists('wl_rate')) {
    /**
     * Win rate as a percentage, or null when nothing has closed.
     *
     * Null rather than 0%, and rendered as a dash. A rep with no closed deals has not lost
     * them all, and a column of 0% would read as the worst performance on the page.
     */
    function wl_rate(int $won, int $lost): ?float
    {
        $total = $won + $lost;
        return $total === 0 ? null : round(($won / $total) * 100, 1);
    }
}

if (!function_exists('wl_bar')) {
    /** A won/lost proportion bar. Drawn with a table, per this project's layout convention. */
    function wl_bar(float $wonValue, float $lostValue): string
    {
        $total = $wonValue + $lostValue;
        if ($total <= 0) {
            return '<div style="height:6px;background:#e5e7eb;border-radius:3px"></div>';
        }
        $wonPct = max(0, min(100, ($wonValue / $total) * 100));

        return '<table style="width:100%;border-collapse:collapse;table-layout:fixed;height:6px">'
             . '<tr>'
             . '<td style="width:' . number_format($wonPct, 2) . '%;background:#16a34a;border-radius:3px 0 0 3px"></td>'
             . '<td style="background:#dc2626;border-radius:0 3px 3px 0"></td>'
             . '</tr></table>';
    }
}

$won      = (int)($totals['won_count']  ?? 0);
$lost     = (int)($totals['lost_count'] ?? 0);
$wonVal   = (float)($totals['won_value']  ?? 0);
$lostVal  = (float)($totals['lost_value'] ?? 0);
$rate     = wl_rate($won, $lost);

$periods  = ['3m' => 'Last 3 months', '12m' => 'Last 12 months', '24m' => 'Last 24 months', 'all' => 'All time'];
$qs       = static fn(array $over = []): string =>
    '/opportunities/win-loss?' . http_build_query(array_filter(
        array_merge(['period' => $period, 'reason' => $reason], $over),
        static fn($v) => $v !== null && $v !== ''
    ));
?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/pipeline" class="back-link">Pipeline</a>
        <h1 class="page-title">Win / loss</h1>
    </div>
</div>

<div class="card" style="padding:.75rem 1.1rem;margin-bottom:1rem;display:flex;gap:.4rem;flex-wrap:wrap">
    <?php foreach ($periods as $pKey => $pLabel): ?>
        <a href="<?= e($qs(['period' => $pKey])) ?>"
           class="btn btn--sm <?= $period === $pKey ? 'btn--primary' : 'btn--secondary' ?>"><?= e($pLabel) ?></a>
    <?php endforeach; ?>
</div>

<?php if ($won + $lost === 0): ?>
    <div class="card" style="padding:1.5rem 1.1rem;color:#6b7280;font-size:.9rem">
        Nothing has closed in this period, so there is nothing to compare yet.
        <?php if ($period !== 'all'): ?>
            <a href="<?= e($qs(['period' => 'all'])) ?>">Try all time</a>.
        <?php endif; ?>
    </div>
<?php else: ?>

<div class="kpi-row">
    <div class="kpi-card">
        <div class="kpi-card__label">Win rate</div>
        <div class="kpi-card__value"><?= $rate === null ? '—' : number_format($rate, 1) . '%' ?></div>
        <div class="kpi-card__meta"><span class="kpi-card__trend kpi-card__trend--neutral"><?= $won ?> won, <?= $lost ?> lost</span></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Won</div>
        <div class="kpi-card__value" style="color:#16a34a"><?= money($wonVal) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Lost</div>
        <div class="kpi-card__value" style="color:#dc2626"><?= money($lostVal) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Share of value won</div>
        <div class="kpi-card__value">
            <?= ($wonVal + $lostVal) > 0 ? number_format(($wonVal / ($wonVal + $lostVal)) * 100, 1) . '%' : '—' ?>
        </div>
        <div class="kpi-card__meta">
            <span class="kpi-card__trend kpi-card__trend--neutral">by money, not by deal count</span>
        </div>
    </div>
</div>

<!-- Why we lose -->
<div class="card" style="padding:0;overflow:hidden;margin-bottom:1rem">
    <div style="padding:.85rem 1.1rem;border-bottom:1px solid #e5e7eb;background:#f8f9fb">
        <h3 style="margin:0;font-size:.95rem;font-weight:700;color:#111">Why we lose</h3>
        <div class="text-muted" style="font-size:.78rem;margin-top:.15rem">
            Ordered by money, not by how often. Ten small deals lost on price and one large one
            we could not supply are not the same problem.
        </div>
    </div>

    <?php if ($reasons === []): ?>
        <div style="padding:1.5rem 1.1rem;color:#6b7280;font-size:.88rem">No losses in this period.</div>
    <?php else: ?>
        <table style="width:100%;border-collapse:collapse">
            <tbody>
            <?php foreach ($reasons as $r):
                $code     = $r['code'] ?? null;
                $selected = ($reason !== null && (string)$reason === (string)$code);
                $noReason = ($code === null || $code === '');
            ?>
                <tr style="border-bottom:1px solid #f1f2f5;<?= $selected ? 'background:#f8f9fb' : '' ?>">
                    <td style="padding:.65rem 1.1rem">
                        <a href="<?= e($qs(['reason' => $noReason ? 'none' : $code])) ?>"
                           style="color:<?= $noReason ? '#b45309' : '#0A3D91' ?>;text-decoration:none;font-weight:600">
                            <?= e(LostReasons::label($noReason ? null : (string)$code)) ?>
                        </a>
                        <?php if ($noReason): ?>
                            <div class="text-muted" style="font-size:.78rem">
                                Closed before a reason was required, or closed without choosing one.
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="padding:.65rem .5rem;width:30%;vertical-align:middle">
                        <?= wl_bar(0, (float)$r['value']) ?>
                    </td>
                    <td style="padding:.65rem 1.1rem;text-align:right;white-space:nowrap">
                        <div style="font-weight:700;color:#111"><?= money((float)$r['value']) ?></div>
                        <div class="text-muted" style="font-size:.78rem"><?= (int)$r['n'] ?> deal<?= (int)$r['n'] === 1 ? '' : 's' ?></div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php if ($reason !== null): ?>
    <div class="card" style="padding:0;overflow:hidden;margin-bottom:1rem">
        <div style="padding:.85rem 1.1rem;border-bottom:1px solid #e5e7eb;background:#f8f9fb;display:flex;justify-content:space-between;gap:.75rem">
            <h3 style="margin:0;font-size:.95rem;font-weight:700;color:#111">
                <?= e(LostReasons::label($reason === 'none' ? null : (string)$reason)) ?>
            </h3>
            <a href="<?= e($qs(['reason' => null])) ?>" style="font-size:.82rem;color:#0A3D91">Clear</a>
        </div>
        <?php if ($losses === []): ?>
            <div style="padding:1.5rem 1.1rem;color:#6b7280;font-size:.88rem">Nothing to show.</div>
        <?php else: ?>
            <table style="width:100%;border-collapse:collapse">
                <tbody>
                <?php foreach ($losses as $lossRow): ?>
                    <tr style="border-bottom:1px solid #f1f2f5">
                        <td style="padding:.6rem 1.1rem">
                            <a href="/opportunities/<?= (int)$lossRow['id'] ?>" style="color:#0A3D91;text-decoration:none;font-weight:600"><?= e($lossRow['name']) ?></a>
                            <div class="text-muted" style="font-size:.8rem">
                                <?= $lossRow['account_name'] ? e($lossRow['account_name']) : 'no account linked' ?>
                                <?php if (!empty($lossRow['rep_name'])): ?> &middot; <?= e($lossRow['rep_name']) ?><?php endif; ?>
                            </div>
                            <?php if (!empty($lossRow['lost_reason'])): ?>
                                <div style="font-size:.82rem;color:#374151;margin-top:.15rem"><?= e($lossRow['lost_reason']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="padding:.6rem 1.1rem;text-align:right;white-space:nowrap">
                            <div style="font-weight:700"><?= money((float)$lossRow['expected_value']) ?></div>
                            <div class="text-muted" style="font-size:.78rem"><?= date('M j, Y', strtotime((string)$lossRow['updated_at'])) ?></div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- By rep -->
<div class="card" style="padding:0;overflow:hidden">
    <div style="padding:.85rem 1.1rem;border-bottom:1px solid #e5e7eb;background:#f8f9fb">
        <h3 style="margin:0;font-size:.95rem;font-weight:700;color:#111">By rep</h3>
    </div>
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr style="background:#fafafa;border-bottom:1px solid #e5e7eb">
                <th style="text-align:left;padding:.55rem 1.1rem;font-size:.75rem;color:#6b7280;text-transform:uppercase">Rep</th>
                <th style="text-align:left;padding:.55rem .5rem;font-size:.75rem;color:#6b7280;text-transform:uppercase">Won / lost by value</th>
                <th style="text-align:right;padding:.55rem 1.1rem;font-size:.75rem;color:#6b7280;text-transform:uppercase">Win rate</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($byRep as $repRow):
            $rWon  = (int)$repRow['won_count'];
            $rLost = (int)$repRow['lost_count'];
            $rRate = wl_rate($rWon, $rLost);
        ?>
            <tr style="border-bottom:1px solid #f1f2f5">
                <td style="padding:.6rem 1.1rem">
                    <?= trim((string)$repRow['rep_name']) !== '' ? e($repRow['rep_name']) : '<span class="text-muted">Unassigned</span>' ?>
                    <div class="text-muted" style="font-size:.78rem">
                        <?= money((float)$repRow['won_value']) ?> won &middot; <?= money((float)$repRow['lost_value']) ?> lost
                    </div>
                </td>
                <td style="padding:.6rem .5rem;width:40%;vertical-align:middle">
                    <?= wl_bar((float)$repRow['won_value'], (float)$repRow['lost_value']) ?>
                </td>
                <td style="padding:.6rem 1.1rem;text-align:right;white-space:nowrap">
                    <div style="font-weight:700;color:#111"><?= $rRate === null ? '—' : number_format($rRate, 1) . '%' ?></div>
                    <div class="text-muted" style="font-size:.78rem"><?= $rWon ?>W / <?= $rLost ?>L</div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php endif; ?>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
