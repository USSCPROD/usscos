<?php ob_start(); ?>
<?php
$th = 'padding:.5rem .8rem;font-size:.7rem;font-weight:700;text-transform:uppercase;'
    . 'letter-spacing:.04em;color:#6b7280;background:#f8f9fb;border-bottom:1px solid #e5e7eb';
$td = 'padding:.55rem .8rem;font-size:.87rem';

$owed        = array_sum(array_map(fn($r) => (float)$r['tax_collected'], $liability));
$taxedSales  = array_sum(array_map(fn($r) => (float)$r['taxable_sales'], $liability));
$amazonTax   = array_sum(array_map(fn($r) => (float)$r['tax_collected_by_marketplace'], $marketplace));

// Uniquely named and guarded — see the CLAUDE.md note on views defining the same function.
if (!function_exists('taxPct')) {
    function taxPct($rate): string
    {
        return rtrim(rtrim(number_format((float)$rate * 100, 3), '0'), '.') . '%';
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <div style="font-size:.85rem;color:var(--color-text-muted);margin-bottom:.25rem">
            <a href="/accounting" style="color:inherit">Accounting</a> &rsaquo; Sales Tax
        </div>
        <h1 class="page-title" style="margin:0">Sales Tax</h1>
        <p class="page-subtitle" style="margin:.25rem 0 0">
            What we collected and owe, broken out by jurisdiction — the shape the Georgia
            return asks for. Figures come from what was frozen onto each invoice, so
            changing a rate today cannot alter a past period.
        </p>
    </div>
    <div class="page-header__right">
        <form method="GET" action="/accounting/tax" style="display:inline-block;vertical-align:middle" id="periodForm">
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

<?php if (!empty($unverified)): ?>
    <div class="alert" style="background:#fffbeb;border:1px solid #fcd34d;color:#92400e;padding:.9rem 1.1rem;margin-bottom:1.25rem">
        <strong>Unverified rates are in use.</strong>
        Nobody has confirmed these against the state's published figures, so any tax
        calculated with them is a guess that looks certain.
        <ul style="margin:.5rem 0 0 1.1rem;padding:0;font-size:.87rem">
            <?php foreach ($unverified as $u): ?>
                <li>
                    <strong><?= e($u['name']) ?></strong> — <?= taxPct($u['rate']) ?>
                    <?php if (!empty($u['source_note'])): ?>
                        <span style="color:#a16207">· <?= e($u['source_note']) ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<table style="width:100%;border-collapse:separate;border-spacing:1rem 0;margin:0 -1rem 1.25rem">
    <tr>
        <?php
        $cards = [
            ['Tax we owe',      money($owed),      $period->label,                     '#b91c1c'],
            ['Taxable sales',   money($taxedSales), 'The base the rates applied to',   '#222b59'],
            ['Collected by Amazon', money($amazonTax), 'Not ours — reported, then deducted', '#6b7280'],
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

<!-- What we owe, by jurisdiction -->
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin-bottom:1.5rem">
    <div style="padding:.7rem 1rem;border-bottom:1px solid #e5e7eb;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280">
        Tax we collected, by jurisdiction
    </div>
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr>
                <th style="<?= $th ?>;text-align:left">State</th>
                <th style="<?= $th ?>;text-align:left">County</th>
                <th style="<?= $th ?>;text-align:right">Rate</th>
                <th style="<?= $th ?>;text-align:right">Invoices</th>
                <th style="<?= $th ?>;text-align:right">Taxable sales</th>
                <th style="<?= $th ?>;text-align:right">Tax collected</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($liability)): ?>
            <tr><td colspan="6" style="padding:2rem;text-align:center;color:#9ca3af">
                No taxed sales in this period.
            </td></tr>
        <?php else: ?>
            <?php foreach ($liability as $r): ?>
                <tr style="border-bottom:1px solid #f3f4f6">
                    <td style="<?= $td ?>;font-weight:600"><?= e($r['state_code']) ?></td>
                    <td style="<?= $td ?>"><?= e($r['county']) ?></td>
                    <td style="<?= $td ?>;text-align:right"><?= taxPct($r['tax_rate_applied']) ?></td>
                    <td style="<?= $td ?>;text-align:right;color:#6b7280"><?= number_format((int)$r['invoices']) ?></td>
                    <td style="<?= $td ?>;text-align:right"><?= money((float)$r['taxable_sales']) ?></td>
                    <td style="<?= $td ?>;text-align:right;font-weight:600"><?= money((float)$r['tax_collected']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Amazon -->
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin-bottom:1.5rem">
    <div style="padding:.7rem 1rem;border-bottom:1px solid #e5e7eb;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280">
        Marketplace sales — tax collected by Amazon
    </div>
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr>
                <th style="<?= $th ?>;text-align:left">Ship to</th>
                <th style="<?= $th ?>;text-align:right">Orders</th>
                <th style="<?= $th ?>;text-align:right">Sales</th>
                <th style="<?= $th ?>;text-align:right">Tax Amazon collected</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($marketplace)): ?>
            <tr><td colspan="4" style="padding:1.5rem;text-align:center;color:#9ca3af">
                No marketplace sales in this period.
            </td></tr>
        <?php else: ?>
            <?php foreach ($marketplace as $m): ?>
                <tr style="border-bottom:1px solid #f3f4f6">
                    <td style="<?= $td ?>;font-weight:600"><?= e($m['state_code']) ?></td>
                    <td style="<?= $td ?>;text-align:right;color:#6b7280"><?= number_format((int)$m['invoices']) ?></td>
                    <td style="<?= $td ?>;text-align:right"><?= money((float)$m['sales']) ?></td>
                    <td style="<?= $td ?>;text-align:right;color:#6b7280"><?= money((float)$m['tax_collected_by_marketplace']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    <div style="padding:.6rem 1rem;border-top:1px solid #f3f4f6;font-size:.78rem;color:#9ca3af">
        Amazon collects and remits on its own orders, so none of this is our liability. It is
        kept because the return generally reports marketplace sales and then deducts them,
        and because the settlement deposit cannot be reconciled without it.
    </div>
</div>

<!-- Nexus watch -->
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden">
    <div style="padding:.7rem 1rem;border-bottom:1px solid #e5e7eb;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280">
        Where we are selling — economic nexus watch
    </div>
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr>
                <th style="<?= $th ?>;text-align:left">Ship to</th>
                <th style="<?= $th ?>;text-align:center">We collect?</th>
                <th style="<?= $th ?>;text-align:right">Direct sales</th>
                <th style="<?= $th ?>;text-align:right">Marketplace</th>
                <th style="<?= $th ?>;text-align:right">Total</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($byState)): ?>
            <tr><td colspan="5" style="padding:1.5rem;text-align:center;color:#9ca3af">No sales in this period.</td></tr>
        <?php else: ?>
            <?php foreach ($byState as $b): ?>
                <?php
                // A rough flag, not advice: most states sit around $100,000, and the point
                // is to see one coming rather than to decide anything here.
                $direct = (float)$b['direct_sales'];
                $near   = !$b['collects'] && $direct >= 75000;
                ?>
                <tr style="border-bottom:1px solid #f3f4f6;<?= $near ? 'background:#fffbeb' : '' ?>">
                    <td style="<?= $td ?>;font-weight:600"><?= e($b['state_code']) ?></td>
                    <td style="<?= $td ?>;text-align:center">
                        <span class="badge <?= $b['collects'] ? 'badge--success' : 'badge--neutral' ?>">
                            <?= $b['collects'] ? 'Registered' : 'No' ?>
                        </span>
                    </td>
                    <td style="<?= $td ?>;text-align:right;<?= $near ? 'color:#b45309;font-weight:600' : '' ?>">
                        <?= money($direct) ?>
                        <?php if ($near): ?><div style="font-size:.72rem">approaching a threshold</div><?php endif; ?>
                    </td>
                    <td style="<?= $td ?>;text-align:right;color:#9ca3af"><?= money((float)$b['marketplace_sales']) ?></td>
                    <td style="<?= $td ?>;text-align:right;font-weight:600"><?= money((float)$b['total_sales']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    <div style="padding:.6rem 1rem;border-top:1px solid #f3f4f6;font-size:.78rem;color:#9ca3af">
        We charge tax only where we are registered. A state can require collection once
        nexus exists — by premises, or by crossing an economic threshold, commonly around
        $100,000 of sales into that state. Marketplace sales are shown separately because
        whether they count toward a threshold varies by state. Treat this as a prompt to ask
        the accountant, not as an answer.
    </div>
</div>

<p style="font-size:.78rem;color:#9ca3af;margin:1rem 0 2rem;max-width:48rem">
    Registered in:
    <?php foreach ($nexus as $n): ?>
        <strong><?= e($n['state_code']) ?></strong><?= $n['basis'] ? ' (' . e($n['basis']) . ')' : '' ?><?= $n !== end($nexus) ? ' · ' : '' ?>
    <?php endforeach; ?>
</p>

<script>
function onPresetChange() { document.getElementById('periodForm').submit(); }
function onDateEdit()     { document.getElementById('periodSelect').value = 'custom'; }
</script>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
