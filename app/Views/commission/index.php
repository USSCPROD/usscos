<?php ob_start();

$periodLabel = match ($periodType) {
    'year'    => date('Y', strtotime($periodStart)),
    'quarter' => 'Q' . ceil((int)date('n', strtotime($periodStart)) / 3) . ' ' . date('Y', strtotime($periodStart)),
    default   => date('F Y', strtotime($periodStart)),
};

$shift = static function (string $type, string $start, int $by): string {
    $unit = match ($type) { 'year' => 'year', 'quarter' => '3 months', default => 'month' };
    return date('Y-m-d', strtotime($start . ' ' . ($by > 0 ? '+' : '-') . abs($by) . ' ' . $unit));
};

$link = static function (array $over) use ($periodType, $periodDate, $rep): string {
    return '/commission?' . http_build_query(array_filter(array_merge([
        'period' => $periodType,
        'date'   => $periodDate,
        'rep'    => $rep['id'] ?? null,
    ], $over), static fn ($v) => $v !== null && $v !== ''));
};
?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title">Commission — <?= e($periodLabel) ?></h1>
    </div>
</div>

<?php if (!empty($unlinked)): ?>
    <div class="alert" style="background:#fffbeb;border:1px solid #fcd34d;color:#92400e">
        <strong>Your login isn't linked to a sales rep record yet.</strong>
        Until it is, there is no way to tell which sales are yours, so this shows nothing
        rather than guessing. Ask an admin to link it.
    </div>
<?php else: ?>

<p class="text-muted" style="margin:-.5rem 0 1rem;max-width:780px;font-size:.9rem">
    Reps buy at wholesale and keep whatever they sell above it, so commission is the margin
    on each line: <strong>(sold &minus; wholesale) &times; quantity</strong>. Credits and
    returns carry negative totals, so they net off here.
</p>

<div class="card" style="padding:.75rem 1.1rem;margin-bottom:1rem;display:flex;gap:.4rem;flex-wrap:wrap;align-items:center">
    <?php foreach (['month' => 'Monthly', 'quarter' => 'Quarterly', 'year' => 'Yearly'] as $typeKey => $typeLabel): ?>
        <a href="<?= e($link(['period' => $typeKey])) ?>"
           class="btn btn--sm <?= $periodType === $typeKey ? 'btn--primary' : 'btn--secondary' ?>"><?= e($typeLabel) ?></a>
    <?php endforeach; ?>

    <?php if (!$restricted && $reps !== []): ?>
        <form method="GET" action="/commission" style="margin-left:.6rem;display:flex;gap:.4rem;align-items:center">
            <input type="hidden" name="period" value="<?= e($periodType) ?>">
            <input type="hidden" name="date"   value="<?= e($periodDate) ?>">
            <select name="rep" style="padding:.4rem .55rem;border:1px solid #d1d5db;border-radius:6px;font-size:.875rem">
                <option value="">Everyone</option>
                <?php foreach ($reps as $r): ?>
                    <option value="<?= (int)$r['id'] ?>" <?= ($rep['id'] ?? null) == $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn--secondary btn--sm">Show</button>
        </form>
    <?php endif; ?>

    <span style="margin-left:auto;display:flex;gap:.4rem">
        <a class="btn btn--secondary btn--sm" href="<?= e($link(['date' => $shift($periodType, $periodStart, -1)])) ?>">&larr; Previous</a>
        <a class="btn btn--secondary btn--sm" href="<?= e($link(['date' => $shift($periodType, $periodStart, 1)])) ?>">Next &rarr;</a>
    </span>
</div>

<?php if ($summary !== null): ?>
    <?php
    $estimated   = (int)$summary['lines_estimated'];
    $noWholesale = (int)$summary['lines_no_wholesale'];
    ?>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-card__label">Commission</div>
            <div class="kpi-card__value" style="color:<?= $summary['total'] < 0 ? '#b91c1c' : '#16a34a' ?>">
                <?= money((float)$summary['total']) ?>
            </div>
            <div class="kpi-card__meta">
                <span class="kpi-card__trend kpi-card__trend--neutral">
                    <?= (int)$summary['invoices'] ?> invoice<?= (int)$summary['invoices'] === 1 ? '' : 's' ?>,
                    <?= (int)$summary['lines'] ?> line<?= (int)$summary['lines'] === 1 ? '' : 's' ?>
                </span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-card__label">Sold</div>
            <div class="kpi-card__value"><?= money((float)$summary['revenue']) ?></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-card__label">At wholesale</div>
            <div class="kpi-card__value"><?= money((float)$summary['wholesale']) ?></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-card__label">Margin</div>
            <div class="kpi-card__value">
                <?= (float)$summary['revenue'] > 0
                    ? number_format(((float)$summary['total'] / (float)$summary['revenue']) * 100, 1) . '%'
                    : '—' ?>
            </div>
        </div>
    </div>

    <?php if ($estimated > 0 || $noWholesale > 0): ?>
        <?php /* Said before the number is acted on, not in a footnote. A statement that
                 looks settled and is not is worse than one that says so. */ ?>
        <div class="alert" style="background:#fffbeb;border:1px solid #fcd34d;color:#92400e;margin-bottom:1rem">
            <?php if ($estimated > 0): ?>
                <div><strong><?= $estimated ?> of <?= (int)$summary['lines'] ?> lines are estimated.</strong>
                They were invoiced before the wholesale price was recorded on the line, so they
                are priced against <em>today's</em> list — if a price has changed since, this
                figure has moved with it. Lines invoiced from 24 Nov 2026 carry their own copy
                and do not move.</div>
            <?php endif; ?>
            <?php if ($noWholesale > 0): ?>
                <div style="margin-top:<?= $estimated > 0 ? '.4rem' : '0' ?>">
                    <strong><?= $noWholesale ?> line<?= $noWholesale === 1 ? ' has' : 's have' ?> no wholesale price at all</strong>
                    — the product has neither a 2026 nor a QuickBooks distributor price, so the
                    whole line counts as margin and this total is overstated by that much.
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="card" style="padding:0;overflow:hidden">
        <div style="padding:.85rem 1.1rem;border-bottom:1px solid #e5e7eb;background:#f8f9fb">
            <h3 style="margin:0;font-size:.95rem;font-weight:700;color:#111">
                <?= $rep ? e($rep['name']) . ' — lines' : 'Lines' ?>
            </h3>
        </div>
        <?php if ($lines === []): ?>
            <div style="padding:1.5rem 1.1rem;color:#6b7280;font-size:.9rem">Nothing invoiced in this period.</div>
        <?php else: ?>
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="background:#fafafa;border-bottom:1px solid #e5e7eb">
                        <th style="text-align:left;padding:.5rem 1.1rem;font-size:.72rem;color:#6b7280;text-transform:uppercase">Invoice</th>
                        <th style="text-align:left;padding:.5rem .5rem;font-size:.72rem;color:#6b7280;text-transform:uppercase">Item</th>
                        <th style="text-align:right;padding:.5rem .5rem;font-size:.72rem;color:#6b7280;text-transform:uppercase">Qty</th>
                        <th style="text-align:right;padding:.5rem .5rem;font-size:.72rem;color:#6b7280;text-transform:uppercase">Sold</th>
                        <th style="text-align:right;padding:.5rem .5rem;font-size:.72rem;color:#6b7280;text-transform:uppercase">Wholesale</th>
                        <th style="text-align:right;padding:.5rem 1.1rem;font-size:.72rem;color:#6b7280;text-transform:uppercase">Margin</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($lines as $ln):
                    $marginValue = (float)$ln['margin'];
                ?>
                    <tr style="border-bottom:1px solid #f1f2f5">
                        <td style="padding:.5rem 1.1rem;white-space:nowrap">
                            <a href="/invoices/<?= (int)$ln['invoice_id'] ?>" style="color:#0A3D91;text-decoration:none"><?= e($ln['invoice_number']) ?></a>
                            <div class="text-muted" style="font-size:.76rem"><?= date('M j, Y', strtotime((string)$ln['invoice_date'])) ?></div>
                        </td>
                        <td style="padding:.5rem .5rem">
                            <strong style="font-size:.85rem"><?= e((string)$ln['sku']) ?></strong>
                            <div class="text-muted" style="font-size:.78rem"><?= e(str_limit((string)$ln['company_name'], 44)) ?></div>
                        </td>
                        <td style="padding:.5rem .5rem;text-align:right;font-size:.85rem"><?= rtrim(rtrim(number_format((float)$ln['qty'], 2), '0'), '.') ?></td>
                        <td style="padding:.5rem .5rem;text-align:right;font-size:.85rem"><?= money((float)$ln['unit_price']) ?></td>
                        <td style="padding:.5rem .5rem;text-align:right;font-size:.85rem">
                            <?php if ($ln['wholesale'] === null): ?>
                                <span style="color:#b91c1c">none</span>
                            <?php else: ?>
                                <?= money((float)$ln['wholesale']) ?>
                                <?php if (empty($ln['is_snapshot'])): ?>
                                    <div style="font-size:.72rem;color:#b45309">est.</div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td style="padding:.5rem 1.1rem;text-align:right;white-space:nowrap">
                            <strong style="color:<?= $marginValue < 0 ? '#b91c1c' : '#111' ?>"><?= money($marginValue) ?></strong>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (!$restricted && $byRep !== []): ?>
    <div class="card" style="padding:0;overflow:hidden;margin-top:1.25rem">
        <div style="padding:.85rem 1.1rem;border-bottom:1px solid #e5e7eb;background:#f8f9fb">
            <h3 style="margin:0;font-size:.95rem;font-weight:700;color:#111">Everyone — <?= e($periodLabel) ?></h3>
        </div>
        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="background:#fafafa;border-bottom:1px solid #e5e7eb">
                    <th style="text-align:left;padding:.5rem 1.1rem;font-size:.72rem;color:#6b7280;text-transform:uppercase">Rep</th>
                    <th style="text-align:right;padding:.5rem .5rem;font-size:.72rem;color:#6b7280;text-transform:uppercase">Sold</th>
                    <th style="text-align:right;padding:.5rem 1.1rem;font-size:.72rem;color:#6b7280;text-transform:uppercase">Commission</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($byRep as $r): ?>
                <tr style="border-bottom:1px solid #f1f2f5">
                    <td style="padding:.5rem 1.1rem">
                        <a href="<?= e($link(['rep' => (int)$r['sales_rep_id']])) ?>" style="color:#0A3D91;text-decoration:none"><?= e($r['rep_name']) ?></a>
                        <?php if ((int)$r['lines_no_wholesale'] > 0): ?>
                            <div style="font-size:.74rem;color:#b45309"><?= (int)$r['lines_no_wholesale'] ?> line<?= (int)$r['lines_no_wholesale'] === 1 ? '' : 's' ?> with no wholesale price</div>
                        <?php endif; ?>
                    </td>
                    <td style="padding:.5rem .5rem;text-align:right;font-size:.88rem"><?= money((float)$r['revenue']) ?></td>
                    <td style="padding:.5rem 1.1rem;text-align:right;white-space:nowrap">
                        <strong style="color:<?= (float)$r['total'] < 0 ? '#b91c1c' : '#111' ?>"><?= money((float)$r['total']) ?></strong>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php endif; ?>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
