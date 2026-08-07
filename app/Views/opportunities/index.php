<?php ob_start();
$stageLabel = ['prospecting' => 'Prospecting', 'proposal' => 'Proposal', 'negotiation' => 'Negotiation'];
$stageColor = ['prospecting' => '#6b7280', 'proposal' => '#0A3D91', 'negotiation' => '#d97706'];
?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title">Pipeline</h1>
    </div>
    <div class="page-header__right">
        <a href="/leads" class="btn btn--secondary">Leads</a>
        <a href="/opportunities/create" class="btn btn--primary">+ New Opportunity</a>
    </div>
</div>

<!-- KPIs -->
<div class="kpi-row">
    <div class="kpi-card">
        <div class="kpi-card__label">Pipeline Value</div>
        <div class="kpi-card__value">$<?= number_format((float)($stats['pipeline_value'] ?? 0), 0) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Weighted Value</div>
        <div class="kpi-card__value">$<?= number_format((float)($stats['weighted_value'] ?? 0), 0) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Open Deals</div>
        <div class="kpi-card__value"><?= (int)($stats['open_count'] ?? 0) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Closed Won</div>
        <div class="kpi-card__value" style="color:#16a34a">$<?= number_format((float)($stats['won_value'] ?? 0), 0) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Won Deals</div>
        <div class="kpi-card__value" style="color:#16a34a"><?= (int)($stats['won_count'] ?? 0) ?></div>
    </div>
</div>

<!-- Pipeline Board -->
<table style="width:100%;border-collapse:separate;border-spacing:.75rem 0;table-layout:fixed">
    <thead>
        <tr>
            <?php foreach ($stageLabel as $stage => $label): ?>
            <th style="padding:.6rem .85rem;background:<?= $stageColor[$stage] ?>;color:#fff;border-radius:6px 6px 0 0;font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em">
                <?= $label ?>
                <span style="font-weight:400;opacity:.8;margin-left:.4rem">(<?= count($stages[$stage] ?? []) ?>)</span>
                <span style="float:right;font-weight:400;opacity:.8">
                    $<?= number_format(array_sum(array_column($stages[$stage] ?? [], 'expected_value')), 0) ?>
                </span>
            </th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <tr style="vertical-align:top">
            <?php foreach ($stageLabel as $stage => $label): ?>
            <td style="background:#f8f9fb;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 6px 6px;padding:.6rem;vertical-align:top">
                <?php if (empty($stages[$stage])): ?>
                    <div style="text-align:center;color:#9ca3af;font-size:.85rem;padding:1.5rem 0">No deals</div>
                <?php else: ?>
                    <?php foreach ($stages[$stage] as $opp):
                        $name = $opp['customer_name'] ?? $opp['lead_name'] ?? '—';
                        $daysToClose = $opp['expected_close'] ? (int)((strtotime($opp['expected_close']) - time()) / 86400) : null;
                        $closeColor = $daysToClose !== null && $daysToClose < 0 ? '#dc2626' : ($daysToClose !== null && $daysToClose <= 14 ? '#d97706' : '#6b7280');
                    ?>
                    <div style="background:#fff;border:1px solid #e5e7eb;border-radius:7px;padding:.85rem 1rem;margin-bottom:.6rem;cursor:pointer" onclick="window.location='/opportunities/<?= (int)$opp['id'] ?>'">
                        <div style="font-weight:700;font-size:.9rem;color:#111;margin-bottom:.3rem"><?= e($opp['name']) ?></div>
                        <div style="font-size:.8rem;color:#6b7280;margin-bottom:.5rem"><?= e($name) ?></div>
                        <table style="width:100%;border-collapse:collapse">
                            <tr>
                                <td style="font-size:.85rem;font-weight:700;color:#222b59">$<?= number_format((float)$opp['expected_value'], 0) ?></td>
                                <td style="text-align:right;font-size:.78rem;color:#6b7280"><?= (int)$opp['probability'] ?>% likely</td>
                            </tr>
                        </table>
                        <?php if ($opp['rep_first']): ?>
                            <div style="font-size:.75rem;color:#9ca3af;margin-top:.4rem"><?= e($opp['rep_first'] . ' ' . $opp['rep_last']) ?></div>
                        <?php endif; ?>
                        <?php if ($opp['expected_close']): ?>
                            <div style="font-size:.75rem;color:<?= $closeColor ?>;margin-top:.3rem">
                                <?= $daysToClose < 0 ? 'Overdue: ' : 'Closes: ' ?><?= date('M j, Y', strtotime($opp['expected_close'])) ?>
                            </div>
                        <?php endif; ?>
                        <!-- Quick stage move -->
                        <div style="margin-top:.6rem;display:flex;gap:.3rem;flex-wrap:wrap" onclick="event.stopPropagation()">
                            <?php foreach (['prospecting' => 'Prosp.', 'proposal' => 'Prop.', 'negotiation' => 'Neg.', 'closed_won' => 'Won', 'closed_lost' => 'Lost'] as $s => $sl): ?>
                                <?php if ($s !== $stage): ?>
                                <form method="POST" action="/opportunities/<?= (int)$opp['id'] ?>/stage" style="display:inline">
                                <?= csrf_field() ?>
                                    <input type="hidden" name="stage" value="<?= $s ?>">
                                    <button type="submit" style="font-size:.7rem;padding:.15rem .45rem;border:1px solid #d1d5db;border-radius:4px;background:#fff;cursor:pointer;color:<?= in_array($s, ['closed_won']) ? '#16a34a' : (in_array($s, ['closed_lost']) ? '#dc2626' : '#374151') ?>"><?= $sl ?></button>
                                </form>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </td>
            <?php endforeach; ?>
        </tr>
    </tbody>
</table>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
