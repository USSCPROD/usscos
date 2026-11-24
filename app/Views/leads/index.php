<?php ob_start(); ?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title">Leads</h1>
        <p class="page-subtitle"><?= number_format((int)($stats['total'] ?? 0)) ?> total</p>
    </div>
    <div class="page-header__right">
        <a href="/pipeline" class="btn btn--secondary">Pipeline</a>
        <a href="/leads/create" class="btn btn--primary">+ New Lead</a>
    </div>
</div>

<!-- KPIs -->
<div class="kpi-row">
    <div class="kpi-card">
        <div class="kpi-card__label">New</div>
        <div class="kpi-card__value"><?= (int)($stats['new_count'] ?? 0) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Contacted</div>
        <div class="kpi-card__value"><?= (int)($stats['contacted_count'] ?? 0) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Qualified</div>
        <div class="kpi-card__value"><?= (int)($stats['qualified_count'] ?? 0) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Converted</div>
        <div class="kpi-card__value" style="color:#16a34a"><?= (int)($stats['converted_count'] ?? 0) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Dead</div>
        <div class="kpi-card__value" style="color:#9ca3af"><?= (int)($stats['dead_count'] ?? 0) ?></div>
    </div>
</div>

<?php
/*
 * Web leads by originating site. Shown only when there is more than one row to compare,
 * because a single line reading "usscos.com 4" is a statistic nobody asked for.
 *
 * A blank site is reported as not recorded and kept separate. Those are the leads taken
 * before migration 127, plus anything posting to the webhook without identifying itself -
 * folding them into whichever site looks likely is how 117 deleted leads became a
 * question that can no longer be answered.
 */
$bySite = $bySite ?? [];
if (count($bySite) > 1):
?>
<div class="card" style="padding:.85rem 1.1rem;margin-bottom:1rem">
    <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.03em;color:#6b7280;margin-bottom:.5rem">
        Web leads by site
    </div>
    <table style="border-collapse:collapse">
        <tbody>
        <?php foreach ($bySite as $row): ?>
            <tr>
                <td style="padding:.2rem 1.25rem .2rem 0;font-size:.88rem;color:#374151">
                    <?= $row['site'] !== '' ? e($row['site']) : '<span class="text-muted">site not recorded</span>' ?>
                </td>
                <td style="padding:.2rem 1.25rem .2rem 0;font-size:.88rem;font-weight:700;color:#111;text-align:right">
                    <?= number_format((int)$row['n']) ?>
                </td>
                <td style="padding:.2rem 0;font-size:.8rem;color:#9ca3af">
                    <?= date('M Y', strtotime((string)$row['first_at'])) ?>
                    &ndash; <?= date('M Y', strtotime((string)$row['last_at'])) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="filter-bar">
    <form method="get" class="filter-bar__search">
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search company, name, email&hellip;" class="input input--search">
        <input type="hidden" name="status" value="<?= e($status) ?>">
    </form>
    <div class="filter-bar__chips">
        <?php
        $statuses = ['all' => 'All', 'new' => 'New', 'contacted' => 'Contacted', 'qualified' => 'Qualified', 'converted' => 'Converted', 'dead' => 'Dead'];
        foreach ($statuses as $val => $label):
            $active = $status === $val ? 'filter-chip--active' : '';
        ?>
            <a href="?<?= http_build_query(['q' => $search, 'status' => $val]) ?>" class="filter-chip <?= $active ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Table -->
<div class="card" style="padding:0;overflow:hidden">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Source</th>
                    <th>Rep</th>
                    <th class="text-center">Status</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pagination['data'])): ?>
                    <tr><td colspan="9" class="table__empty">No leads found.</td></tr>
                <?php else: ?>
                    <?php
                    $statusBadge = ['new' => 'badge--info', 'contacted' => 'badge--warning', 'qualified' => 'badge--success', 'converted' => 'badge--success', 'dead' => 'badge--neutral'];
                    $sourceLabel = ['web' => 'Web', 'referral' => 'Referral', 'trade_show' => 'Trade Show', 'cold_call' => 'Cold Call', 'social' => 'Social', 'email_campaign' => 'Email', 'other' => 'Other'];
                    foreach ($pagination['data'] as $lead): ?>
                        <tr class="table__row--clickable" onclick="window.location='/leads/<?= (int)$lead['id'] ?>'">
                            <td style="font-weight:600"><?= e($lead['company_name']) ?></td>
                            <td class="text-sm"><?= e(trim(($lead['first_name'] ?? '') . ' ' . ($lead['last_name'] ?? ''))) ?: '—' ?></td>
                            <td class="text-sm text-muted"><?= $lead['email'] ? '<a href="mailto:' . e($lead['email']) . '" onclick="event.stopPropagation()">' . e($lead['email']) . '</a>' : '—' ?></td>
                            <td class="text-sm text-muted"><?= e($lead['phone'] ?? '—') ?></td>
                            <td class="text-sm text-muted">
                                <?= $sourceLabel[$lead['source']] ?? e($lead['source']) ?>
                                <?php /* Which form, when we know. "Web" alone cannot tell a
                                         two-line enquiry from somebody asking to be quoted
                                         for a pallet, and those want answering differently. */ ?>
                                <?php if (!empty($lead['source_detail'])): ?>
                                    <div style="font-size:.78rem;color:#9ca3af">
                                        <?= e(str_replace('Website — ', '', $lead['source_detail'])) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-sm"><?= $lead['rep_first'] ? e($lead['rep_first'] . ' ' . $lead['rep_last']) : '<span class="text-muted">—</span>' ?></td>
                            <td class="text-center">
                                <span class="badge <?= $statusBadge[$lead['status']] ?? 'badge--neutral' ?>"><?= ucfirst($lead['status']) ?></span>
                            </td>
                            <td class="text-sm text-muted"><?= date('M j, Y', strtotime($lead['created_at'])) ?></td>
                            <td class="text-right">
                                <a href="/leads/<?= (int)$lead['id'] ?>/edit" class="btn btn--xs btn--secondary" onclick="event.stopPropagation()">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pagination['last_page'] > 1): ?>
    <div class="table-pagination">
        <span class="table-pagination__info"><?= number_format($pagination['from']) ?>–<?= number_format($pagination['to']) ?> of <?= number_format($pagination['total']) ?></span>
        <div class="table-pagination__pages">
            <?php if ($pagination['current_page'] > 1): ?>
                <a href="?<?= http_build_query(['q' => $search, 'status' => $status, 'page' => $pagination['current_page'] - 1]) ?>" class="btn btn--xs btn--secondary">&larr;</a>
            <?php endif; ?>
            <span class="table-pagination__current">Page <?= $pagination['current_page'] ?> of <?= $pagination['last_page'] ?></span>
            <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                <a href="?<?= http_build_query(['q' => $search, 'status' => $status, 'page' => $pagination['current_page'] + 1]) ?>" class="btn btn--xs btn--secondary">&rarr;</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
