<?php ob_start(); ?>
<?php
$th = 'padding:.5rem .9rem;font-size:.7rem;font-weight:700;text-transform:uppercase;'
    . 'letter-spacing:.05em;color:#6b7280;text-align:left;background:#f8f9fb;border-bottom:1px solid #e5e7eb';
?>

<div class="page-header">
    <div class="page-header__left">
        <div style="font-size:.85rem;color:var(--color-text-muted);margin-bottom:.25rem">
            <a href="/accounting" style="color:inherit">Accounting</a> &rsaquo; Chart of Accounts
        </div>
        <h1 class="page-title" style="margin:0">Chart of Accounts</h1>
    </div>
</div>

<div class="toolbar" style="margin-bottom:1rem">
    <form class="toolbar__search" method="GET" action="/accounting/accounts">
        <div class="search-wrap">
            <svg class="search-wrap__icon" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/></svg>
            <input type="text" name="q" class="input search-wrap__input" placeholder="Search accounts…"
                   value="<?= e($search) ?>" autocomplete="off"
                   style="padding:.65rem .85rem .65rem 2.5rem;font-size:1rem;height:auto">
        </div>
    </form>
</div>

<?php if (empty($grouped)): ?>
    <div class="card" style="padding:2rem;text-align:center;color:#9ca3af">
        No accounts match that search.
    </div>
<?php endif; ?>

<?php foreach ($grouped as $section => $accounts): ?>
<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:1.25rem;overflow:hidden">
    <div style="padding:.7rem 1rem;border-bottom:1px solid #e5e7eb;background:#222b59;color:#fff;font-size:.8rem;font-weight:700;letter-spacing:.03em">
        <?= e($section) ?>
        <span style="float:right;font-weight:400;opacity:.75"><?= count($accounts) ?></span>
    </div>
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr>
                <th style="<?= $th ?>;width:90px">Code</th>
                <th style="<?= $th ?>">Account</th>
                <th style="<?= $th ?>;width:180px">Subtype</th>
                <th style="<?= $th ?>;width:90px;text-align:center">Normal</th>
                <th style="<?= $th ?>">Purpose</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($accounts as $a): ?>
            <tr style="border-bottom:1px solid #f3f4f6<?= (int)$a['is_active'] === 0 ? ';opacity:.5' : '' ?>">
                <td style="padding:.5rem .9rem;font-family:monospace;font-size:.82rem;color:#374151">
                    <?= e($a['account_code'] ?? '—') ?>
                </td>
                <td style="padding:.5rem .9rem;font-size:.88rem;font-weight:500">
                    <?= e($a['name']) ?>
                    <?php if (!empty($a['is_bank_account'])): ?>
                        <span class="badge badge--info" style="font-size:.62rem;margin-left:.3rem">Bank</span>
                    <?php endif; ?>
                    <?php if ((int)$a['is_active'] === 0): ?>
                        <span class="badge badge--neutral" style="font-size:.62rem;margin-left:.3rem">Inactive</span>
                    <?php endif; ?>
                </td>
                <td style="padding:.5rem .9rem;font-size:.8rem;color:#6b7280"><?= e($a['account_subtype'] ?? '—') ?></td>
                <td style="padding:.5rem .9rem;text-align:center">
                    <span style="font-size:.72rem;font-weight:700;color:<?= $a['normal_balance'] === 'debit' ? '#0A3D91' : '#7c3aed' ?>">
                        <?= strtoupper(substr($a['normal_balance'], 0, 2)) ?>
                    </span>
                </td>
                <td style="padding:.5rem .9rem;font-size:.78rem;color:#9ca3af;line-height:1.5">
                    <?= e($a['description'] ?? '') ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endforeach; ?>

<p style="font-size:.78rem;color:#9ca3af;margin:0 0 2rem">
    <strong>DR</strong> / <strong>CR</strong> is the account's normal balance — the side that
    increases it. Contra accounts run against their type on purpose: Accumulated Depreciation
    and Allowance for Doubtful Accounts are credit-normal assets, Sales Returns and Discounts
    are debit-normal revenue, and Dividends Declared is debit-normal equity.
</p>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
