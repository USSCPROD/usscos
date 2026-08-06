<?php ob_start(); ?>
<?php
$th = 'padding:.5rem .8rem;font-size:.7rem;font-weight:700;text-transform:uppercase;'
    . 'letter-spacing:.04em;color:#6b7280;background:#f8f9fb;border-bottom:1px solid #e5e7eb';
$td = 'padding:.55rem .8rem;font-size:.87rem';

$repTotal   = array_sum(array_map(fn($r) => (float)$r['revenue'], $reps));
$unattTotal = array_sum(array_map(fn($r) => (float)$r['revenue'], $unattributed));
$grand      = $repTotal + $unattTotal;
?>

<div class="page-header">
    <div class="page-header__left">
        <div style="font-size:.85rem;color:var(--color-text-muted);margin-bottom:.25rem">
            <a href="/accounting" style="color:inherit">Accounting</a> &rsaquo; Sales by Rep
        </div>
        <h1 class="page-title" style="margin:0">Sales by Rep</h1>
        <p class="page-subtitle" style="margin:.25rem 0 0">
            Revenue is summed from invoices credited to each rep — not from their customers'
            lifetime totals, which include sales credited elsewhere.
        </p>
    </div>
    <div class="page-header__right">
        <a href="/admin/sales-reps" class="btn btn--secondary" style="margin-right:.5rem">Manage Reps</a>
        <form method="GET" action="/accounting/reps" style="display:inline" id="periodForm">
            <select name="period" class="input" id="periodSelect"
                    onchange="onPeriodChange()"
                    style="height:2.2rem;padding:.3rem .6rem;font-size:.85rem">
                <?php foreach ($options as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $period->preset === $key ? 'selected' : '' ?>>
                        <?= e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span id="customRange" style="<?= $period->preset === 'custom' ? '' : 'display:none' ?>">
                <input type="date" name="from" value="<?= e($period->from ?? '') ?>" class="input"
                       style="height:2.2rem;padding:.3rem .5rem;font-size:.85rem;width:auto">
                <span style="color:#9ca3af;font-size:.85rem">to</span>
                <input type="date" name="to" value="<?= e($period->to ?? '') ?>" class="input"
                       style="height:2.2rem;padding:.3rem .5rem;font-size:.85rem;width:auto">
                <button type="submit" class="btn btn--sm btn--primary">Apply</button>
            </span>
        </form>
    </div>
</div>

<!-- Attribution split -->
<table style="width:100%;border-collapse:separate;border-spacing:1rem 0;margin:0 -1rem 1.25rem">
    <tr>
        <?php
        $pctRep = $grand > 0 ? round(($repTotal / $grand) * 100) : 0;
        $cards = [
            ['Credited to a rep',  money($repTotal),   $pctRep . '% of revenue',        '#16a34a'],
            ['Not rep-credited',   money($unattTotal), (100 - $pctRep) . '% of revenue', '#d97706'],
            ['Total',              money($grand),      null,                             '#222b59'],
        ];
        foreach ($cards as [$label, $val, $sub, $col]): ?>
            <td style="width:33.3%;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:.9rem 1rem;text-align:center">
                <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.3rem"><?= $label ?></div>
                <div style="font-size:1.25rem;font-weight:600;color:<?= $col ?>"><?= $val ?></div>
                <?php if ($sub): ?><div style="font-size:.72rem;color:#9ca3af;margin-top:.2rem"><?= $sub ?></div><?php endif; ?>
            </td>
        <?php endforeach; ?>
    </tr>
</table>

<!-- Reps -->
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin-bottom:1.25rem">
    <div style="padding:.7rem 1rem;border-bottom:1px solid #e5e7eb;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280">
        Sales Reps — <?= e($period->label) ?>
        <?php if (!$period->isUnbounded()): ?>
            <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#9ca3af">
                (<?= e($period->from ?? 'start') ?> to <?= e($period->to ?? 'today') ?>)
            </span>
        <?php endif; ?>
    </div>
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr>
                <th style="<?= $th ?>;text-align:left">Rep</th>
                <th style="<?= $th ?>;text-align:right">Revenue</th>
                <th style="<?= $th ?>;text-align:right">Invoices</th>
                <th style="<?= $th ?>;text-align:right">Avg Invoice</th>
                <th style="<?= $th ?>;text-align:right">Customers Sold To</th>
                <th style="<?= $th ?>;text-align:right">Assigned</th>
                <th style="<?= $th ?>;text-align:left">Last Sale</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($reps)): ?>
            <tr><td colspan="7" style="padding:2rem;text-align:center;color:#9ca3af">No rep activity in this period.</td></tr>
        <?php else: ?>
            <?php foreach ($reps as $r):
                $inv = (int)$r['invoice_count'];
                $rev = (float)$r['revenue'];
                $avg = $inv > 0 ? $rev / $inv : 0; ?>
            <tr style="border-bottom:1px solid #f3f4f6">
                <td style="<?= $td ?>">
                    <a href="/customers?rep=<?= (int)$r['id'] ?>" style="color:#222b59;font-weight:500;text-decoration:none">
                        <?= e($r['name']) ?>
                    </a>
                </td>
                <td style="<?= $td ?>;text-align:right;font-weight:600"><?= money($rev) ?></td>
                <td style="<?= $td ?>;text-align:right;color:#6b7280"><?= number_format($inv) ?></td>
                <td style="<?= $td ?>;text-align:right;color:#6b7280"><?= $inv ? money($avg) : '—' ?></td>
                <td style="<?= $td ?>;text-align:right;color:#6b7280"><?= number_format((int)$r['customers_invoiced']) ?></td>
                <td style="<?= $td ?>;text-align:right">
                    <a href="/customers?rep=<?= (int)$r['id'] ?>" style="color:#0A3D91;text-decoration:none">
                        <?= number_format((int)$r['assigned_customers']) ?>
                    </a>
                </td>
                <td style="<?= $td ?>;color:#6b7280">
                    <?= !empty($r['last_sale']) ? date('M j, Y', strtotime($r['last_sale'])) : '—' ?>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Everything not credited to a rep -->
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden">
    <div style="padding:.7rem 1rem;border-bottom:1px solid #e5e7eb;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280">
        Not Credited to a Rep — <?= e($period->label) ?>
    </div>
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr>
                <th style="<?= $th ?>;text-align:left">Category</th>
                <th style="<?= $th ?>;text-align:right">Revenue</th>
                <th style="<?= $th ?>;text-align:right">Invoices</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($unattributed as $u):
            $labels = [
                'none'     => 'no rep set in QuickBooks',
                'house'    => 'house account',
                'website'  => 'online order',
                'partner'  => 'partner / distributor',
                'employee' => 'employee, not a rep',
                'owner'    => 'owner',
                'unset'    => 'never imported — outside the rep export range',
            ]; ?>
            <tr style="border-bottom:1px solid #f3f4f6">
                <td style="<?= $td ?>">
                    <?= e($u['label']) ?>
                    <span style="font-size:.75rem;color:#9ca3af">— <?= $labels[$u['rep_type']] ?? e($u['rep_type']) ?></span>
                </td>
                <td style="<?= $td ?>;text-align:right;font-weight:500"><?= money((float)$u['revenue']) ?></td>
                <td style="<?= $td ?>;text-align:right;color:#6b7280"><?= number_format((int)$u['invoice_count']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<p style="font-size:.78rem;color:#9ca3af;margin:1rem 0 2rem">
    Figures cover invoices dated in the selected period, excluding voided ones, and default
    to year to date. Rep attribution came from a QuickBooks Sales&nbsp;by&nbsp;Rep export
    covering <strong>2026 only</strong>, so periods before that show as "never imported".
    Employees, the owner, and placeholder entries are deliberately excluded from the rep
    table — Larry Fitzpatrick alone accounts for 1,945 invoices and would otherwise
    dominate it.
</p>

<script>
// "Custom range…" reveals the two date inputs instead of reloading; every other preset
// is self-contained, so it submits immediately. The date inputs are disabled for
// non-custom presets so they stay out of the query string.
function syncPeriodFields() {
    var isCustom = document.getElementById('periodSelect').value === 'custom';
    var custom   = document.getElementById('customRange');

    custom.style.display = isCustom ? '' : 'none';
    custom.querySelectorAll('input').forEach(function (i) { i.disabled = !isCustom; });

    return isCustom;
}

function onPeriodChange() {
    // Only submit on an actual change — never on load, which would reload forever.
    if (!syncPeriodFields()) {
        document.getElementById('periodForm').submit();
    }
}

syncPeriodFields();
</script>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
