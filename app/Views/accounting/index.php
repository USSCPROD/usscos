<?php ob_start(); ?>
<?php
$s = $summary;

$acCard = 'background:#fff;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:1.25rem';
$acHead = 'padding:.7rem 1rem;border-bottom:1px solid #e5e7eb;font-size:.72rem;font-weight:700;'
        . 'text-transform:uppercase;letter-spacing:.06em;color:#6b7280';
?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title">Accounting</h1>
        <p class="page-subtitle">Receivables, payables, and the chart of accounts.</p>
    </div>
</div>

<!-- Headline figures -->
<table style="width:100%;border-collapse:separate;border-spacing:1rem 0;margin:0 -1rem 1rem">
    <tr>
        <?php
        $kpis = [
            ['Revenue — MTD',    money($s['revenue_mtd']), null],
            ['Revenue — YTD',    money($s['revenue_ytd']), null],
            ['A/R Outstanding',  money($s['ar_total']),    $s['ar_overdue'] > 0 ? money($s['ar_overdue']) . ' overdue' : null],
            ['A/P Outstanding',  money($s['ap_total']),    $s['ap_overdue'] > 0 ? money($s['ap_overdue']) . ' overdue' : null],
        ];
        foreach ($kpis as [$label, $val, $sub]): ?>
            <td style="width:25%;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:.9rem 1rem;text-align:center">
                <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.3rem"><?= $label ?></div>
                <div style="font-size:1.3rem;font-weight:600"><?= $val ?></div>
                <?php if ($sub): ?>
                    <div style="font-size:.72rem;color:#dc2626;margin-top:.2rem"><?= $sub ?></div>
                <?php endif; ?>
            </td>
        <?php endforeach; ?>
    </tr>
</table>

<table style="width:100%;border-collapse:separate;border-spacing:1.25rem 0;margin:0 -1.25rem">
    <tr>

    <!-- Revenue trend -->
    <td style="vertical-align:top;padding:0;width:58%">
        <div style="<?= $acCard ?>">
            <div style="<?= $acHead ?>">Revenue — Last 12 Months</div>
            <?php if (empty($revenue)): ?>
                <div style="padding:1.5rem 1rem;text-align:center;color:#9ca3af;font-size:.85rem">No invoices yet.</div>
            <?php else:
                $peak = max(array_map(fn($m) => (float)$m['revenue'], $revenue)) ?: 1; ?>
            <table style="width:100%;border-collapse:collapse">
                <?php foreach ($revenue as $m):
                    $pct = max(2, (int)round(((float)$m['revenue'] / $peak) * 100)); ?>
                <tr>
                    <td style="padding:.25rem .5rem .25rem 1rem;font-size:.75rem;color:#6b7280;white-space:nowrap;width:1%">
                        <?= date('M Y', strtotime($m['period'] . '-01')) ?>
                    </td>
                    <td style="padding:.25rem .5rem">
                        <table style="width:100%;border-collapse:collapse"><tr>
                            <td style="width:<?= $pct ?>%;background:#222b59;height:14px;border-radius:2px"></td>
                            <td style="width:<?= 100 - $pct ?>%"></td>
                        </tr></table>
                    </td>
                    <td style="padding:.25rem 1rem .25rem 0;font-size:.78rem;text-align:right;white-space:nowrap;font-weight:500">
                        <?= money((float)$m['revenue']) ?>
                    </td>
                    <td style="padding:.25rem 1rem .25rem 0;font-size:.72rem;color:#9ca3af;text-align:right;white-space:nowrap">
                        <?= (int)$m['invoices'] ?> inv
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </td>

    <!-- Reports + ledger status -->
    <td style="vertical-align:top;padding:0;width:42%">
        <div style="<?= $acCard ?>">
            <div style="<?= $acHead ?>">Reports</div>
            <table style="width:100%;border-collapse:collapse">
                <tr style="border-bottom:1px solid #f3f4f6">
                    <td style="padding:.6rem 1rem">
                        <a href="/accounting/ar-aging" style="color:#0A3D91;font-weight:500;font-size:.9rem;text-decoration:none">A/R Aging</a>
                        <div style="font-size:.75rem;color:#9ca3af">Who owes what, and how overdue</div>
                    </td>
                    <td style="padding:.6rem 1rem;text-align:right;white-space:nowrap;font-size:.85rem;font-weight:600">
                        <?= money($s['ar_total']) ?>
                    </td>
                </tr>
                <tr style="border-bottom:1px solid #f3f4f6">
                    <td style="padding:.6rem 1rem">
                        <a href="/accounting/accounts" style="color:#0A3D91;font-weight:500;font-size:.9rem;text-decoration:none">Chart of Accounts</a>
                        <div style="font-size:.75rem;color:#9ca3af">Account structure</div>
                    </td>
                    <td style="padding:.6rem 1rem;text-align:right;white-space:nowrap;font-size:.85rem;color:#6b7280">
                        <?= (int)$s['coa_count'] ?> accounts
                    </td>
                </tr>
                <tr>
                    <td style="padding:.6rem 1rem">
                        <a href="/invoices" style="color:#0A3D91;font-weight:500;font-size:.9rem;text-decoration:none">Invoices</a>
                        <div style="font-size:.75rem;color:#9ca3af">All customer invoices</div>
                    </td>
                    <td style="padding:.6rem 1rem;text-align:right;white-space:nowrap;font-size:.85rem;color:#6b7280">
                        <?= (int)$s['ap_count'] ?> open bills
                    </td>
                </tr>
            </table>
        </div>

        <div style="<?= $acCard ?>">
            <div style="<?= $acHead ?>">General Ledger</div>
            <div style="padding:1rem">
                <p style="margin:0 0 .6rem;font-size:.87rem;color:#374151">
                    The chart of accounts is seeded (<?= (int)$s['coa_count'] ?> accounts), and
                    <strong><?= number_format($s['je_count']) ?></strong> journal entries are posted.
                </p>
                <p style="margin:0;font-size:.8rem;color:#6b7280;line-height:1.55">
                    QuickBooks remains the ledger of record. Whether USSCOS takes that over —
                    and therefore needs opening balances and period close — is an open decision.
                    See <code style="font-size:.78rem">docs/USSCos_Accounting_Module_Spec.md</code>.
                </p>
            </div>
        </div>
    </td>

    </tr>
</table>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
