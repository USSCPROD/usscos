<?php ob_start(); ?>
<?php
$t  = $totals;
$th = 'padding:.5rem .7rem;font-size:.68rem;font-weight:700;text-transform:uppercase;'
    . 'letter-spacing:.04em;color:#6b7280;background:#f8f9fb;border-bottom:1px solid #e5e7eb';
$td = 'padding:.5rem .7rem;font-size:.85rem;text-align:right;white-space:nowrap';

/** Only render a figure when there's something in the bucket — keeps the grid readable. */
if (!function_exists('agAmt')) {
    function agAmt($v, string $color = '#374151'): string {
        $v = (float)$v;
        return $v > 0
            ? '<span style="color:' . $color . '">' . money($v) . '</span>'
            : '<span style="color:#d1d5db">—</span>';
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <div style="font-size:.85rem;color:var(--color-text-muted);margin-bottom:.25rem">
            <a href="/accounting" style="color:inherit">Accounting</a> &rsaquo; A/R Aging
        </div>
        <h1 class="page-title" style="margin:0">A/R Aging</h1>
        <p class="page-subtitle" style="margin:.25rem 0 0">
            <?= number_format((int)($t['invoice_count'] ?? 0)) ?> open invoices across
            <?= number_format((int)($t['customer_count'] ?? 0)) ?> customers.
            Buckets are measured from the <strong>due date</strong>, so an unpaid Net 30
            invoice stays current until day 31.
        </p>
    </div>
</div>

<!-- Bucket totals -->
<table style="width:100%;border-collapse:separate;border-spacing:.75rem 0;margin:0 -.75rem 1.25rem">
    <tr>
        <?php
        $buckets = [
            ['Current',   $t['current_due'] ?? 0, '#16a34a'],
            ['1–30',      $t['d1_30']       ?? 0, '#65a30d'],
            ['31–60',     $t['d31_60']      ?? 0, '#d97706'],
            ['61–90',     $t['d61_90']      ?? 0, '#ea580c'],
            ['90+',       $t['d90_plus']    ?? 0, '#dc2626'],
            ['Total',     $t['total_due']   ?? 0, '#222b59'],
        ];
        foreach ($buckets as $i => [$label, $val, $col]):
            $isTotal = $i === count($buckets) - 1; ?>
            <td style="width:16.6%;background:<?= $isTotal ? '#222b59' : '#fff' ?>;border:1px solid <?= $isTotal ? '#222b59' : '#e5e7eb' ?>;border-radius:8px;padding:.75rem .5rem;text-align:center">
                <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.25rem;color:<?= $isTotal ? '#c7d2fe' : '#6b7280' ?>"><?= $label ?></div>
                <div style="font-size:1.05rem;font-weight:600;color:<?= $isTotal ? '#fff' : $col ?>"><?= money((float)$val) ?></div>
            </td>
        <?php endforeach; ?>
    </tr>
</table>

<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden">
    <div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr>
                <th style="<?= $th ?>;text-align:left">Customer</th>
                <th style="<?= $th ?>;text-align:center;width:60px">Inv</th>
                <th style="<?= $th ?>;text-align:right">Current</th>
                <th style="<?= $th ?>;text-align:right">1–30</th>
                <th style="<?= $th ?>;text-align:right">31–60</th>
                <th style="<?= $th ?>;text-align:right">61–90</th>
                <th style="<?= $th ?>;text-align:right">90+</th>
                <th style="<?= $th ?>;text-align:right">Total</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="8" style="padding:2.5rem;text-align:center;color:#9ca3af">
                Nothing outstanding — every invoice is paid.
            </td></tr>
        <?php else: ?>
            <?php foreach ($rows as $r):
                $worst = (int)($r['worst_days'] ?? 0); ?>
            <tr style="border-bottom:1px solid #f3f4f6">
                <td style="padding:.5rem .7rem;font-size:.87rem">
                    <a href="/customers/<?= (int)$r['customer_id'] ?>" style="color:#222b59;font-weight:500;text-decoration:none">
                        <?= e($r['company_name']) ?>
                    </a>
                    <?php if ($worst > 90): ?>
                        <span class="badge badge--danger" style="font-size:.62rem;margin-left:.35rem"><?= $worst ?>d</span>
                    <?php elseif ($worst > 60): ?>
                        <span class="badge badge--warning" style="font-size:.62rem;margin-left:.35rem"><?= $worst ?>d</span>
                    <?php endif; ?>
                </td>
                <td style="<?= $td ?>;text-align:center;color:#9ca3af"><?= (int)$r['invoice_count'] ?></td>
                <td style="<?= $td ?>"><?= agAmt($r['current_due'], '#16a34a') ?></td>
                <td style="<?= $td ?>"><?= agAmt($r['d1_30'],  '#65a30d') ?></td>
                <td style="<?= $td ?>"><?= agAmt($r['d31_60'], '#d97706') ?></td>
                <td style="<?= $td ?>"><?= agAmt($r['d61_90'], '#ea580c') ?></td>
                <td style="<?= $td ?>"><?= agAmt($r['d90_plus'], '#dc2626') ?></td>
                <td style="<?= $td ?>;font-weight:600"><?= money((float)$r['total_due']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
        <?php if (!empty($rows)): ?>
        <tfoot>
            <tr style="background:#f8f9fb;border-top:2px solid #d1d5db;font-weight:600">
                <td style="padding:.6rem .7rem;font-size:.85rem">Total</td>
                <td style="<?= $td ?>;text-align:center"><?= number_format((int)($t['invoice_count'] ?? 0)) ?></td>
                <td style="<?= $td ?>"><?= money((float)($t['current_due'] ?? 0)) ?></td>
                <td style="<?= $td ?>"><?= money((float)($t['d1_30']    ?? 0)) ?></td>
                <td style="<?= $td ?>"><?= money((float)($t['d31_60']   ?? 0)) ?></td>
                <td style="<?= $td ?>"><?= money((float)($t['d61_90']   ?? 0)) ?></td>
                <td style="<?= $td ?>"><?= money((float)($t['d90_plus'] ?? 0)) ?></td>
                <td style="<?= $td ?>"><?= money((float)($t['total_due'] ?? 0)) ?></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>
    </div>
</div>

<p style="font-size:.78rem;color:#9ca3af;margin:1rem 0 2rem">
    Sorted worst-first — customers with 90+ day balances at the top.
    Rows are computed directly from invoices, not from the general ledger, so this report
    works regardless of whether journal entries are being posted.
</p>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
