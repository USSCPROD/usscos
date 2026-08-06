<?php ob_start(); ?>
<?php
$th = 'padding:.5rem .8rem;font-size:.7rem;font-weight:700;text-transform:uppercase;'
    . 'letter-spacing:.04em;color:#6b7280;background:#f8f9fb;border-bottom:1px solid #e5e7eb';
$td = 'padding:.55rem .8rem;font-size:.87rem';

$total    = array_sum(array_map(fn($e) => (float)$e['revenue'], $employees));
$invoices = array_sum(array_map(fn($e) => (int)$e['invoice_count'], $employees));
?>

<div class="page-header">
    <div class="page-header__left">
        <div style="font-size:.85rem;color:var(--color-text-muted);margin-bottom:.25rem">
            <a href="/accounting" style="color:inherit">Accounting</a> &rsaquo; Sales by Employee
        </div>
        <h1 class="page-title" style="margin:0">Sales by Employee</h1>
        <p class="page-subtitle" style="margin:.25rem 0 0">
            Who keyed each order in, from the QuickBooks <strong>Processed by</strong> field.
            This is workload, not commission — the sale itself is credited to the rep on the
            invoice, shown under <a href="/accounting/reps">Sales by Rep</a>.
        </p>
    </div>
    <div class="page-header__right">
        <a href="/accounting/reps" class="btn btn--secondary" style="margin-right:.5rem">Sales by Rep</a>
        <form method="GET" action="/accounting/employees" style="display:inline-block;vertical-align:middle" id="periodForm">
            <?php $ctl = 'height:2.2rem;padding:.3rem .5rem;font-size:.85rem;width:auto;vertical-align:middle'; ?>
            <select name="period" class="input" id="periodSelect" onchange="onPresetChange()" style="<?= $ctl ?>">
                <?php foreach ($options as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $period->preset === $key ? 'selected' : '' ?>>
                        <?= e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="from" id="fromDate" value="<?= e($period->from ?? '') ?>"
                   class="input" onchange="onDateEdit()" title="From date" style="<?= $ctl ?>">
            <span style="color:#9ca3af;font-size:.85rem">to</span>
            <input type="date" name="to" id="toDate" value="<?= e($period->to ?? '') ?>"
                   class="input" onchange="onDateEdit()" title="To date" style="<?= $ctl ?>">
            <button type="submit" class="btn btn--sm btn--primary" style="vertical-align:middle">Apply</button>
        </form>
    </div>
</div>

<table style="width:100%;border-collapse:separate;border-spacing:1rem 0;margin:0 -1rem 1.25rem">
    <tr>
        <?php
        $cards = [
            ['Revenue entered', money($total), $period->label, '#16a34a'],
            ['Invoices',        number_format($invoices), null, '#222b59'],
            ['Order takers',    (string)count($employees), null, '#222b59'],
        ];
        foreach ($cards as [$label, $val, $sub, $col]): ?>
            <td style="width:33.3%;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:.9rem 1rem;text-align:center">
                <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.3rem"><?= $label ?></div>
                <div style="font-size:1.25rem;font-weight:600;color:<?= $col ?>"><?= $val ?></div>
                <?php if ($sub): ?><div style="font-size:.72rem;color:#9ca3af;margin-top:.2rem"><?= e($sub) ?></div><?php endif; ?>
            </td>
        <?php endforeach; ?>
    </tr>
</table>

<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden">
    <div style="padding:.7rem 1rem;border-bottom:1px solid #e5e7eb;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280">
        Order Takers — <?= e($period->label) ?>
    </div>
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr>
                <th style="<?= $th ?>;text-align:left">Processed by</th>
                <th style="<?= $th ?>;text-align:left">USSCOS login</th>
                <th style="<?= $th ?>;text-align:right">Revenue</th>
                <th style="<?= $th ?>;text-align:right">Invoices</th>
                <th style="<?= $th ?>;text-align:right">Avg Invoice</th>
                <th style="<?= $th ?>;text-align:right">Customers</th>
                <th style="<?= $th ?>;text-align:right">Reps Entered For</th>
                <th style="<?= $th ?>;text-align:left">Last Entry</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($employees)): ?>
            <tr><td colspan="8" style="padding:2rem;text-align:center;color:#9ca3af">
                No orders keyed in this period.
            </td></tr>
        <?php else: ?>
            <?php foreach ($employees as $emp):
                $inv = (int)$emp['invoice_count'];
                $rev = (float)$emp['revenue']; ?>
            <tr style="border-bottom:1px solid #f3f4f6">
                <td style="<?= $td ?>;font-weight:500"><?= e($emp['name']) ?></td>
                <td style="<?= $td ?>;font-size:.8rem">
                    <?php if (!empty($emp['user_id'])): ?>
                        <a href="/admin/users/<?= (int)$emp['user_id'] ?>/edit" style="color:#0A3D91;text-decoration:none">
                            <?= e($emp['user_name']) ?>
                        </a>
                    <?php else: ?>
                        <span style="color:#9ca3af">no account</span>
                    <?php endif; ?>
                </td>
                <td style="<?= $td ?>;text-align:right;font-weight:600"><?= money($rev) ?></td>
                <td style="<?= $td ?>;text-align:right;color:#6b7280"><?= number_format($inv) ?></td>
                <td style="<?= $td ?>;text-align:right;color:#6b7280"><?= $inv > 0 ? money($rev / $inv) : '—' ?></td>
                <td style="<?= $td ?>;text-align:right;color:#6b7280"><?= number_format((int)$emp['customers']) ?></td>
                <td style="<?= $td ?>;text-align:right;color:#6b7280"><?= number_format((int)$emp['distinct_reps']) ?></td>
                <td style="<?= $td ?>;color:#6b7280">
                    <?= !empty($emp['last_entry']) ? date('M j, Y', strtotime($emp['last_entry'])) : '—' ?>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<p style="font-size:.78rem;color:#9ca3af;margin:1rem 0 2rem">
    <strong>Reps Entered For</strong> counts the distinct sales reps credited on the invoices
    each person keyed. A high number means they are entering orders on reps' behalf, which is
    the norm until reps can enter their own. Processed by came from a QuickBooks export
    covering <strong>2026</strong>, so earlier periods are empty. The names are QuickBooks
    logins, matched to a USSCOS account by first name where one exists.
</p>

<script>
function onPresetChange() {
    document.getElementById('periodForm').submit();
}

function onDateEdit() {
    document.getElementById('periodSelect').value = 'custom';
}
</script>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
