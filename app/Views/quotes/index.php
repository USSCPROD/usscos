<?php ob_start();
$stats = $stats ?? [];

$statusBadge = [
    'draft'    => 'badge--secondary',
    'sent'     => 'badge--info',
    'accepted' => 'badge--success',
    'declined' => 'badge--danger',
    'expired'  => 'badge--warning',
];
$statusLabel = [
    'draft'    => 'Draft',
    'sent'     => 'Sent',
    'accepted' => 'Accepted',
    'declined' => 'Declined',
    'expired'  => 'Expired',
];
?>

<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-title">Quotes</h1>
    </div>
    <div class="page-header__right">
        <a href="/quotes/create" class="btn btn--primary">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Quote
        </a>
    </div>
</div>

<!-- KPI tiles -->
<table style="width:100%;border-collapse:separate;border-spacing:.75rem;margin:-0.75rem 0 1rem -0.75rem;width:calc(100% + 1.5rem)">
    <tr>
        <td style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:1rem 1.25rem;vertical-align:top">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280">Pipeline Value</div>
            <div style="font-size:1.5rem;font-weight:700;color:#111827;margin-top:.25rem"><?= money((float)($stats['pipeline_value'] ?? 0)) ?></div>
            <div style="font-size:.8rem;color:#6b7280;margin-top:.2rem">Draft + Sent</div>
        </td>
        <td style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:1rem 1.25rem;vertical-align:top">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280">Awaiting Response</div>
            <div style="font-size:1.5rem;font-weight:700;color:#2563eb;margin-top:.25rem"><?= (int)($stats['sent_count'] ?? 0) ?></div>
            <div style="font-size:.8rem;color:#6b7280;margin-top:.2rem">Sent quotes</div>
        </td>
        <td style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:1rem 1.25rem;vertical-align:top">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280">Accepted</div>
            <div style="font-size:1.5rem;font-weight:700;color:#16a34a;margin-top:.25rem"><?= (int)($stats['accepted_count'] ?? 0) ?></div>
            <div style="font-size:.8rem;color:#6b7280;margin-top:.2rem">Converted to SO</div>
        </td>
        <td style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:1rem 1.25rem;vertical-align:top">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280">Win Rate</div>
            <div style="font-size:1.5rem;font-weight:700;color:#7c3aed;margin-top:.25rem"><?= $stats['win_rate'] !== null ? $stats['win_rate'] . '%' : '—' ?></div>
            <div style="font-size:.8rem;color:#6b7280;margin-top:.2rem">Accepted / (Acc + Dec)</div>
        </td>
        <td style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:1rem 1.25rem;vertical-align:top">
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280">Declined / Expired</div>
            <div style="font-size:1.5rem;font-weight:700;color:#dc2626;margin-top:.25rem"><?= (int)($stats['declined_count'] ?? 0) + (int)($stats['expired_count'] ?? 0) ?></div>
            <div style="font-size:.8rem;color:#6b7280;margin-top:.2rem">Lost quotes</div>
        </td>
    </tr>
</table>

<!-- Filters -->
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap">
    <form method="get" action="/quotes" style="display:flex;gap:.5rem;flex:1;min-width:200px">
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search quotes…"
               style="flex:1;padding:.6rem .875rem;font-size:.9rem;border:1px solid #d1d5db;border-radius:6px;background:#fff">
        <button type="submit" class="btn btn--secondary">Search</button>
        <?php if ($search): ?><a href="/quotes" class="btn btn--secondary">Clear</a><?php endif; ?>
    </form>

    <div style="display:flex;gap:.375rem;flex-wrap:wrap">
        <?php foreach (['all'=>'All','draft'=>'Draft','sent'=>'Sent','accepted'=>'Accepted','declined'=>'Declined','expired'=>'Expired'] as $val => $lbl): ?>
            <a href="?status=<?= $val ?><?= $search ? '&q='.urlencode($search) : '' ?>"
               style="padding:.35rem .75rem;border-radius:20px;font-size:.8rem;font-weight:600;text-decoration:none;border:1px solid <?= $status === $val ? '#222b59' : '#d1d5db' ?>;background:<?= $status === $val ? '#222b59' : '#fff' ?>;color:<?= $status === $val ? '#fff' : '#374151' ?>">
                <?= $lbl ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Table -->
<div class="card" style="padding:0;overflow:hidden">
    <?php if (empty($paginated['data'])): ?>
        <div style="padding:3rem;text-align:center;color:#6b7280">
            No quotes found.
            <a href="/quotes/create" class="link" style="margin-left:.5rem">Create your first quote →</a>
        </div>
    <?php else: ?>
    <table style="width:100%;border-collapse:collapse">
        <thead>
            <tr style="background:#f8f9fb;border-bottom:2px solid #e5e7eb">
                <th style="padding:.75rem 1rem;text-align:left;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">Quote #</th>
                <th style="padding:.75rem 1rem;text-align:left;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">Customer</th>
                <th style="padding:.75rem 1rem;text-align:left;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">Date</th>
                <th style="padding:.75rem 1rem;text-align:left;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">Expires</th>
                <th style="padding:.75rem 1rem;text-align:left;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">Status</th>
                <th style="padding:.75rem 1rem;text-align:right;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">Total</th>
                <th style="padding:.75rem 1rem;text-align:left;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">Rep</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($paginated['data'] as $q): ?>
            <?php
                $isExpired = $q['status'] === 'sent' && !empty($q['expiry_date']) && $q['expiry_date'] < date('Y-m-d');
            ?>
            <tr style="border-bottom:1px solid #f3f4f6" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=''">
                <td style="padding:.75rem 1rem">
                    <a href="/quotes/<?= (int)$q['id'] ?>" class="link" style="font-weight:600"><?= e($q['quote_number']) ?></a>
                </td>
                <td style="padding:.75rem 1rem">
                    <a href="/customers/<?= (int)$q['customer_id'] ?>" class="link"><?= e($q['company_name']) ?></a>
                </td>
                <td style="padding:.75rem 1rem;color:#374151;font-size:.9rem"><?= date('M j, Y', strtotime($q['quote_date'])) ?></td>
                <td style="padding:.75rem 1rem;font-size:.9rem;color:<?= $isExpired ? '#dc2626' : '#374151' ?>">
                    <?= !empty($q['expiry_date']) ? date('M j, Y', strtotime($q['expiry_date'])) : '—' ?>
                </td>
                <td style="padding:.75rem 1rem">
                    <span class="badge <?= $statusBadge[$q['status']] ?? 'badge--secondary' ?>">
                        <?= $statusLabel[$q['status']] ?? e($q['status']) ?>
                    </span>
                </td>
                <td style="padding:.75rem 1rem;text-align:right;font-weight:600;font-family:monospace"><?= money((float)$q['total_amount']) ?></td>
                <td style="padding:.75rem 1rem;font-size:.85rem;color:#6b7280">
                    <?= $q['rep_first'] ? e($q['rep_first'] . ' ' . $q['rep_last']) : '—' ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($paginated['last_page'] > 1): ?>
    <div style="padding:.75rem 1rem;border-top:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;font-size:.85rem;color:#6b7280">
        <span>Showing <?= $paginated['from'] ?>–<?= $paginated['to'] ?> of <?= $paginated['total'] ?></span>
        <div style="display:flex;gap:.375rem">
            <?php for ($p = 1; $p <= $paginated['last_page']; $p++): ?>
                <a href="?page=<?= $p ?>&status=<?= $status ?>&q=<?= urlencode($search) ?>"
                   style="padding:.3rem .6rem;border-radius:4px;text-decoration:none;font-weight:600;background:<?= $p === $paginated['current_page'] ? '#222b59' : '#f3f4f6' ?>;color:<?= $p === $paginated['current_page'] ? '#fff' : '#374151' ?>">
                    <?= $p ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
