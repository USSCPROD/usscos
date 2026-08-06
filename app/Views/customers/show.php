<?php ob_start(); ?>
<?php
$c     = $customer;
$stats = $stats ?? [];
$notes = $notes ?? [];

$noteTypeLabel = ['note' => 'Note', 'call' => 'Call', 'email' => 'Email', 'meeting' => 'Meeting'];
$noteTypeIcon  = [
    'note'    => '<svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>',
    'call'    => '<svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>',
    'email'   => '<svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>',
    'meeting' => '<svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
];
$noteTypeBg = ['note' => '#f3f4f6', 'call' => '#d1fae5', 'email' => '#dbeafe', 'meeting' => '#ede9fe'];
$noteTypeColor = ['note' => '#374151', 'call' => '#065f46', 'email' => '#1e40af', 'meeting' => '#5b21b6'];

$isAtRisk = isset($stats['days_since']) && $stats['days_since'] !== null && $stats['days_since'] >= 90;
?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/customers" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Customers
        </a>
        <h1 class="page-title"><?= e($c['company_name']) ?></h1>
        <div style="display:flex;flex-wrap:wrap;gap:.4rem;align-items:center;margin-top:.3rem">
            <?php if (!empty($c['customer_type'])): ?>
                <span class="badge badge--info"><?= e($c['customer_type']) ?></span>
            <?php endif; ?>
            <?php if ($isAtRisk): ?>
                <span class="badge badge--warning">At Risk — <?= $stats['days_since'] ?> days since last order</span>
            <?php endif; ?>
            <?php if (!empty($c['parent_name'])): ?>
                <span class="badge badge--neutral">Under: <a href="/customers/<?= (int)$c['parent_id'] ?>" style="color:inherit;font-weight:700"><?= e($c['parent_name']) ?></a></span>
            <?php endif; ?>
            <?php if (!empty($c['rep_first_name'])): ?>
                <span class="badge badge--neutral">Rep: <?= e($c['rep_first_name'] . ' ' . $c['rep_last_name']) ?></span>
            <?php endif; ?>
        </div>
        <?php if ($c['quickbooks_name'] !== $c['company_name']): ?>
            <p class="page-subtitle">QB: <?= e($c['quickbooks_name']) ?></p>
        <?php endif; ?>
    </div>
    <div class="page-header__right">
        <a href="/customers/<?= (int)$c['id'] ?>/edit" class="btn btn--secondary">Edit</a>
        <a href="/quotes/create?customer_id=<?= (int)$c['id'] ?>" class="btn btn--secondary">New Quote</a>
        <a href="/sales-orders/create?customer_id=<?= (int)$c['id'] ?>" class="btn btn--secondary">New Sales Order</a>
        <a href="/customers/<?= (int)$c['id'] ?>/payment" class="btn btn--secondary">Receive Payment</a>
        <a href="/invoices/create?customer_id=<?= (int)$c['id'] ?>" class="btn btn--primary">New Invoice</a>
    </div>
</div>

<!-- KPI Row -->
<div class="kpi-row">
    <div class="kpi-card">
        <div class="kpi-card__label">Lifetime Revenue</div>
        <div class="kpi-card__value">$<?= number_format((float)($stats['lifetime_revenue'] ?? 0), 0) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Open Balance</div>
        <div class="kpi-card__value <?= (float)$c['qb_balance'] > 0 ? 'text-warning' : '' ?>">
            $<?= number_format((float)$c['qb_balance'], 2) ?>
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Avg Order</div>
        <div class="kpi-card__value">$<?= number_format((float)($stats['avg_invoice'] ?? 0), 0) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Total Orders</div>
        <div class="kpi-card__value"><?= (int)($stats['invoice_count'] ?? 0) + (int)($stats['so_count'] ?? 0) ?></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-card__label">Last Activity</div>
        <div class="kpi-card__value kpi-card__value--sm <?= $isAtRisk ? 'text-warning' : '' ?>">
            <?php if ($stats['last_activity'] ?? null): ?>
                <?= date('M j, Y', strtotime($stats['last_activity'])) ?>
            <?php else: ?>
                <span class="text-muted">None</span>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($c['payment_term_name']): ?>
    <div class="kpi-card">
        <div class="kpi-card__label">Payment Terms</div>
        <div class="kpi-card__value kpi-card__value--sm"><?= e($c['payment_term_name']) ?></div>
    </div>
    <?php endif; ?>
</div>

<?php
/* ---------------- Customer Intelligence ---------------- */
$prof      = $profile          ?? [];
$topProds  = $top_products     ?? [];
$revMonths = $revenue_by_month ?? [];
$ciAlerts  = $alerts           ?? [];

$ciCard = 'background:#fff;border:1px solid #e5e7eb;border-radius:8px;margin-bottom:1.25rem';
$ciHead = 'padding:.7rem 1rem;border-bottom:1px solid #e5e7eb;font-size:.72rem;font-weight:700;'
        . 'text-transform:uppercase;letter-spacing:.06em;color:#6b7280';
$ciLbl  = 'padding:.45rem .9rem;font-size:.82rem;color:#6b7280;white-space:nowrap';
$ciVal  = 'padding:.45rem .9rem;font-size:.9rem;font-weight:600;text-align:right';
?>

<?php if (!empty($ciAlerts)): ?>
<div style="margin-bottom:1.25rem">
    <?php foreach ($ciAlerts as $a):
        $bg = ['danger' => '#fef2f2', 'warning' => '#fffbeb', 'neutral' => '#f8f9fb'][$a['level']] ?? '#f8f9fb';
        $bd = ['danger' => '#dc2626', 'warning' => '#d97706', 'neutral' => '#9ca3af'][$a['level']] ?? '#9ca3af';
    ?>
        <div style="background:<?= $bg ?>;border-left:4px solid <?= $bd ?>;padding:.6rem .9rem;margin-bottom:.4rem;border-radius:4px;font-size:.87rem">
            <?= e($a['text']) ?>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<table style="width:100%;border-collapse:separate;border-spacing:1.25rem 0;margin:0 -1.25rem">
    <tr>

    <!-- Purchase rhythm -->
    <td style="vertical-align:top;padding:0;width:34%">
        <div style="<?= $ciCard ?>">
            <div style="<?= $ciHead ?>">Buying Pattern</div>
            <table style="width:100%;border-collapse:collapse">
                <tr style="border-bottom:1px solid #f3f4f6">
                    <td style="<?= $ciLbl ?>">Orders per year</td>
                    <td style="<?= $ciVal ?>">
                        <?= $prof['orders_per_year'] !== null ? number_format((float)$prof['orders_per_year'], 1) : '<span class="text-muted">—</span>' ?>
                    </td>
                </tr>
                <tr style="border-bottom:1px solid #f3f4f6">
                    <td style="<?= $ciLbl ?>">Typically orders every</td>
                    <td style="<?= $ciVal ?>">
                        <?= $prof['order_frequency_days'] !== null ? (int)$prof['order_frequency_days'] . ' days' : '<span class="text-muted">—</span>' ?>
                    </td>
                </tr>
                <tr style="border-bottom:1px solid #f3f4f6">
                    <td style="<?= $ciLbl ?>">Last order</td>
                    <td style="<?= $ciVal ?>">
                        <?php if (!empty($prof['last_order_date'])):
                            $d = (int)$prof['days_since_order'];
                            $col = $d >= 180 ? '#dc2626' : ($d >= 90 ? '#d97706' : '#111'); ?>
                            <span style="color:<?= $col ?>"><?= $d ?> days ago</span>
                        <?php else: ?><span class="text-muted">Never</span><?php endif; ?>
                    </td>
                </tr>
                <tr style="border-bottom:1px solid #f3f4f6">
                    <td style="<?= $ciLbl ?>">Customer since</td>
                    <td style="<?= $ciVal ?>">
                        <?= !empty($prof['first_order_date']) ? date('M Y', strtotime($prof['first_order_date'])) : '<span class="text-muted">—</span>' ?>
                    </td>
                </tr>
                <tr>
                    <td style="<?= $ciLbl ?>">Overdue</td>
                    <td style="<?= $ciVal ?>">
                        <?php $od = (float)($prof['overdue_balance'] ?? 0); ?>
                        <span style="color:<?= $od > 0 ? '#dc2626' : '#16a34a' ?>"><?= money($od) ?></span>
                    </td>
                </tr>
            </table>
        </div>
    </td>

    <!-- Top products -->
    <td style="vertical-align:top;padding:0;width:38%">
        <div style="<?= $ciCard ?>">
            <div style="<?= $ciHead ?>">Buys Most</div>
            <?php if (empty($topProds)): ?>
                <div style="padding:1.4rem 1rem;text-align:center;color:#9ca3af;font-size:.85rem">
                    No product-level history yet.
                </div>
            <?php else: ?>
            <table style="width:100%;border-collapse:collapse">
                <?php foreach ($topProds as $tp): ?>
                <tr style="border-bottom:1px solid #f3f4f6">
                    <td style="padding:.45rem .9rem">
                        <a href="/products/<?= (int)$tp['id'] ?>" style="color:#222b59;font-weight:500;font-size:.85rem;text-decoration:none">
                            <?= e($tp['name']) ?>
                        </a>
                        <div style="font-size:.72rem;color:#9ca3af;font-family:monospace"><?= e($tp['sku']) ?></div>
                    </td>
                    <td style="padding:.45rem .9rem;text-align:right;white-space:nowrap">
                        <div style="font-size:.85rem;font-weight:600"><?= money((float)$tp['total_revenue']) ?></div>
                        <div style="font-size:.72rem;color:#9ca3af"><?= (int)$tp['times_ordered'] ?>× ordered</div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </td>

    <!-- Revenue trend -->
    <td style="vertical-align:top;padding:0;width:28%">
        <div style="<?= $ciCard ?>">
            <div style="<?= $ciHead ?>">Last 12 Months</div>
            <?php if (empty($revMonths)): ?>
                <div style="padding:1.4rem 1rem;text-align:center;color:#9ca3af;font-size:.85rem">
                    No orders in the last year.
                </div>
            <?php else:
                $peak = max(array_map(fn($m) => (float)$m['revenue'], $revMonths)) ?: 1; ?>
            <table style="width:100%;border-collapse:collapse">
                <?php foreach ($revMonths as $m):
                    $pct = max(2, (int)round(((float)$m['revenue'] / $peak) * 100)); ?>
                <tr>
                    <td style="padding:.2rem .5rem .2rem .9rem;font-size:.72rem;color:#6b7280;white-space:nowrap;width:1%">
                        <?= date('M y', strtotime($m['period'] . '-01')) ?>
                    </td>
                    <td style="padding:.2rem .9rem .2rem 0">
                        <table style="width:100%;border-collapse:collapse"><tr>
                            <td style="width:<?= $pct ?>%;background:#222b59;height:12px;border-radius:2px"></td>
                            <td style="width:<?= 100 - $pct ?>%"></td>
                        </tr></table>
                    </td>
                    <td style="padding:.2rem .9rem .2rem 0;font-size:.72rem;color:#374151;text-align:right;white-space:nowrap">
                        <?= money((float)$m['revenue']) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </td>

    </tr>
</table>

<div class="detail-layout">

    <!-- Left: Tabbed Customer Info -->
    <div class="detail-layout__side">
        <div class="card" style="padding:0;overflow:hidden">

            <!-- Info tab bar -->
            <div style="display:flex;flex-wrap:wrap;border-bottom:2px solid #e5e7eb;background:#f8f9fb">
                <button type="button" id="itab-contact" onclick="switchInfoTab('contact')"
                    style="padding:.65rem 1rem;font-size:.8rem;font-weight:600;border:none;background:none;cursor:pointer;border-bottom:2px solid #0A3D91;margin-bottom:-2px;color:#0A3D91;white-space:nowrap">
                    Contact
                </button>
                <button type="button" id="itab-address" onclick="switchInfoTab('address')"
                    style="padding:.65rem 1rem;font-size:.8rem;font-weight:600;border:none;background:none;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;color:#6b7280;white-space:nowrap">
                    Address
                </button>
            </div>

            <!-- Contact tab -->
            <div id="ipanel-contact" style="padding:1.25rem">
                <dl class="detail-list">
                    <?php if ($c['first_name'] || $c['last_name']): ?>
                        <dt>Contact</dt><dd><?= e(trim($c['first_name'] . ' ' . $c['last_name'])) ?></dd>
                    <?php endif; ?>
                    <?php if ($c['phone']): ?>
                        <dt>Phone</dt><dd><a href="tel:<?= e($c['phone']) ?>"><?= e($c['phone']) ?></a></dd>
                    <?php endif; ?>
                    <?php if ($c['work_phone'] ?? null): ?>
                        <dt>Work Phone</dt><dd><a href="tel:<?= e($c['work_phone']) ?>"><?= e($c['work_phone']) ?></a></dd>
                    <?php endif; ?>
                    <?php if ($c['mobile'] ?? null): ?>
                        <dt>Mobile</dt><dd><a href="tel:<?= e($c['mobile']) ?>"><?= e($c['mobile']) ?></a></dd>
                    <?php endif; ?>
                    <?php if ($c['fax']): ?>
                        <dt>Fax</dt><dd><?= e($c['fax']) ?></dd>
                    <?php endif; ?>
                    <?php if ($c['email']): ?>
                        <dt>Email</dt><dd><a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a></dd>
                    <?php endif; ?>
                    <?php if ($c['cc_email'] ?? null): ?>
                        <dt>CC Email</dt><dd><?= e($c['cc_email']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>

            <!-- Address tab -->
            <div id="ipanel-address" style="display:none;padding:1.25rem">
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.5rem">Billing Address</div>
                <?php if ($c['bill_address_1'] || $c['bill_city']): ?>
                    <address class="address-block" style="margin-bottom:1.25rem">
                        <?php if ($c['bill_address_1']): ?><?= e($c['bill_address_1']) ?><br><?php endif; ?>
                        <?php if ($c['bill_address_2']): ?><?= e($c['bill_address_2']) ?><br><?php endif; ?>
                        <?php if ($c['bill_city']): ?>
                            <?= e($c['bill_city']) ?><?= $c['bill_state'] ? ', ' . e($c['bill_state']) : '' ?> <?= e($c['bill_zip'] ?? '') ?>
                        <?php endif; ?>
                        <?php if (($c['bill_country'] ?? 'US') !== 'US'): ?>
                            <br><?= e($c['bill_country']) ?>
                        <?php endif; ?>
                    </address>
                <?php else: ?>
                    <p class="text-muted text-sm" style="margin-bottom:1.25rem">No billing address on file.</p>
                <?php endif; ?>

                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.5rem">Shipping Address</div>
                <?php if ($c['ship_address_1'] || $c['ship_city']): ?>
                    <address class="address-block">
                        <?php if ($c['ship_address_1']): ?><?= e($c['ship_address_1']) ?><br><?php endif; ?>
                        <?php if ($c['ship_address_2']): ?><?= e($c['ship_address_2']) ?><br><?php endif; ?>
                        <?php if ($c['ship_city']): ?>
                            <?= e($c['ship_city']) ?><?= $c['ship_state'] ? ', ' . e($c['ship_state']) : '' ?> <?= e($c['ship_zip'] ?? '') ?>
                        <?php endif; ?>
                        <?php if (($c['ship_country'] ?? 'US') !== 'US'): ?>
                            <br><?= e($c['ship_country']) ?>
                        <?php endif; ?>
                    </address>
                <?php else: ?>
                    <p class="text-muted text-sm">Same as billing / not set.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Right: Tabbed History -->
    <div class="detail-layout__main">
        <div class="card" style="padding:0;overflow:hidden">

            <!-- Tab bar -->
            <div style="display:flex;flex-wrap:wrap;border-bottom:2px solid #e5e7eb;background:#f8f9fb">
                <button type="button" id="tab-notes" onclick="switchTab('notes')"
                    style="padding:.75rem 1.25rem;font-size:.875rem;font-weight:600;border:none;background:none;cursor:pointer;border-bottom:2px solid #0A3D91;margin-bottom:-2px;color:#0A3D91">
                    Notes <?php if (count($notes)): ?><span style="font-size:.75rem;background:#222b59;color:#fff;border-radius:999px;padding:.1rem .5rem;margin-left:.3rem"><?= count($notes) ?></span><?php endif; ?>
                </button>
                <button type="button" id="tab-all" onclick="switchTab('all')"
                    style="padding:.75rem 1.25rem;font-size:.875rem;font-weight:600;border:none;background:none;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;color:#6b7280">
                    All Activity
                </button>
                <button type="button" id="tab-invoices" onclick="switchTab('invoices')"
                    style="padding:.75rem 1.25rem;font-size:.875rem;font-weight:600;border:none;background:none;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;color:#6b7280">
                    Invoices
                </button>
                <button type="button" id="tab-salesorders" onclick="switchTab('salesorders')"
                    style="padding:.75rem 1.25rem;font-size:.875rem;font-weight:600;border:none;background:none;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;color:#6b7280">
                    Sales Orders
                </button>
                <button type="button" id="tab-quotes" onclick="switchTab('quotes')"
                    style="padding:.75rem 1.25rem;font-size:.875rem;font-weight:600;border:none;background:none;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;color:#6b7280">
                    Quotes
                </button>
                <button type="button" id="tab-payments" onclick="switchTab('payments')"
                    style="padding:.75rem 1.25rem;font-size:.875rem;font-weight:600;border:none;background:none;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;color:#6b7280">
                    Payments
                </button>
                <button type="button" id="tab-tasks" onclick="switchTab('tasks')"
                    style="padding:.75rem 1.25rem;font-size:.875rem;font-weight:600;border:none;background:none;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;color:#6b7280">
                    Tasks <?php if (!empty($tasks)): ?><span style="background:#ef4444;color:white;border-radius:10px;padding:.1rem .45rem;font-size:.7rem;margin-left:.3rem"><?= count(array_filter($tasks, fn($t) => !in_array($t['status'],['completed','cancelled']))) ?></span><?php endif; ?>
                </button>
            </div>

            <!-- Notes tab -->
            <div id="panel-notes" id="notes">
                <!-- Log a note -->
                <form method="POST" action="/customers/<?= (int)$c['id'] ?>/note" style="padding:1.1rem 1.25rem;border-bottom:1px solid #e5e7eb;background:#fafafa">
                    <div style="display:flex;gap:.6rem;margin-bottom:.6rem">
                        <?php foreach (['note' => 'Note', 'call' => 'Call', 'email' => 'Email', 'meeting' => 'Meeting'] as $val => $lbl): ?>
                            <label style="display:inline-flex;align-items:center;gap:.3rem;font-size:.85rem;font-weight:600;cursor:pointer;padding:.3rem .75rem;border:1px solid #d1d5db;border-radius:5px;color:#374151;background:#fff">
                                <input type="radio" name="note_type" value="<?= $val ?>" <?= $val === 'note' ? 'checked' : '' ?> style="accent-color:#222b59">
                                <?= $lbl ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <textarea name="body" rows="2" required placeholder="Log a note, call, email, or meeting…"
                        style="width:100%;padding:.6rem .75rem;border:1px solid #d1d5db;border-radius:6px;font-family:inherit;font-size:.9rem;resize:vertical;box-sizing:border-box"></textarea>
                    <div style="display:flex;justify-content:flex-end;margin-top:.5rem">
                        <button type="submit" class="btn btn--primary btn--sm">Save</button>
                    </div>
                </form>

                <!-- Note feed -->
                <?php if (empty($notes)): ?>
                    <div style="padding:2rem;text-align:center;color:#9ca3af;font-size:.9rem">No notes yet. Log the first one above.</div>
                <?php else: ?>
                    <div style="padding:.75rem 1.25rem;display:flex;flex-direction:column;gap:.75rem">
                        <?php foreach ($notes as $note):
                            $bg    = $noteTypeBg[$note['note_type']]    ?? '#f3f4f6';
                            $color = $noteTypeColor[$note['note_type']] ?? '#374151';
                            $icon  = $noteTypeIcon[$note['note_type']]  ?? '';
                            $label = $noteTypeLabel[$note['note_type']] ?? 'Note';
                            $author = trim(($note['first_name'] ?? '') . ' ' . ($note['last_name'] ?? '')) ?: 'Unknown';
                        ?>
                        <div style="border:1px solid #e5e7eb;border-radius:7px;padding:.85rem 1rem;background:#fff">
                            <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem">
                                <span style="display:inline-flex;align-items:center;gap:.3rem;font-size:.75rem;font-weight:700;padding:.2rem .6rem;border-radius:4px;background:<?= $bg ?>;color:<?= $color ?>">
                                    <?= $icon ?> <?= $label ?>
                                </span>
                                <span style="font-size:.8rem;font-weight:600;color:#374151"><?= e($author) ?></span>
                                <span style="font-size:.78rem;color:#9ca3af;margin-left:auto"><?= date('M j, Y g:i a', strtotime($note['created_at'])) ?></span>
                            </div>
                            <div style="font-size:.9rem;color:#374151;line-height:1.6;white-space:pre-wrap"><?= e($note['body']) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- All Activity tab -->
            <div id="panel-all" style="display:none">
                <?php
                $activity = [];
                foreach ($invoices as $inv) {
                    $activity[] = [
                        'sort_date' => $inv['invoice_date'],
                        'type'      => 'Invoice',
                        'number'    => $inv['invoice_number'],
                        'url'       => '/invoices/' . (int)$inv['id'],
                        'date'      => date('M j, Y', strtotime($inv['invoice_date'])),
                        'ref'       => $inv['po_number'] ?? '',
                        'amount'    => (float)$inv['total_amount'],
                        'status'    => ucfirst($inv['status']),
                        'status_class' => match($inv['status']) {
                            'paid'    => 'badge--success',
                            'overdue' => 'badge--danger',
                            'partial' => 'badge--warning',
                            'void'    => 'badge--neutral',
                            default   => 'badge--info',
                        },
                    ];
                }
                foreach ($sales_orders as $so) {
                    $soLabel = ['draft'=>'Draft','confirmed'=>'Confirmed','processing'=>'Processing','partially_shipped'=>'Partial','shipped'=>'Shipped','invoiced'=>'Invoiced','cancelled'=>'Cancelled'];
                    $soBadge = ['draft'=>'badge--neutral','confirmed'=>'badge--info','processing'=>'badge--info','partially_shipped'=>'badge--warning','shipped'=>'badge--success','invoiced'=>'badge--success','cancelled'=>'badge--neutral'];
                    $activity[] = [
                        'sort_date'    => $so['order_date'],
                        'type'         => 'Sales Order',
                        'number'       => $so['so_number'],
                        'url'          => '/sales-orders/' . (int)$so['id'],
                        'date'         => date('M j, Y', strtotime($so['order_date'])),
                        'ref'          => $so['po_number'] ?? '',
                        'amount'       => (float)$so['total_amount'],
                        'status'       => $soLabel[$so['status']] ?? ucfirst($so['status']),
                        'status_class' => $soBadge[$so['status']] ?? 'badge--neutral',
                    ];
                }
                foreach ($payments as $pmt) {
                    $activity[] = [
                        'sort_date'    => $pmt['payment_date'],
                        'type'         => 'Payment',
                        'number'       => $pmt['reference_number'] ?? '',
                        'url'          => '/payments/' . (int)$pmt['id'] . '/edit',
                        'date'         => date('M j, Y', strtotime($pmt['payment_date'])),
                        'ref'          => ucwords(str_replace('_', ' ', $pmt['payment_method'])),
                        'amount'       => (float)$pmt['amount'],
                        'status'       => '',
                        'status_class' => '',
                    ];
                }
                usort($activity, fn($a, $b) => strcmp($b['sort_date'], $a['sort_date']));
                ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th><th>Type</th><th>Number / Ref</th>
                                <th>PO / Method</th><th class="text-right">Amount</th><th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($activity)): ?>
                                <tr><td colspan="6" class="table__empty">No activity on record.</td></tr>
                            <?php else: ?>
                                <?php foreach ($activity as $row): ?>
                                    <tr class="table__row--clickable" onclick="window.location='<?= e($row['url']) ?>'">
                                        <td><?= $row['date'] ?></td>
                                        <td class="text-sm text-muted"><?= $row['type'] ?></td>
                                        <td class="font-mono text-sm"><?= e($row['number']) ?: '<span class="text-muted">—</span>' ?></td>
                                        <td class="text-sm text-muted"><?= e($row['ref']) ?: '—' ?></td>
                                        <td class="text-right font-mono">$<?= number_format($row['amount'], 2) ?></td>
                                        <td class="text-center">
                                            <?php if ($row['status']): ?>
                                                <span class="badge <?= $row['status_class'] ?>"><?= $row['status'] ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Invoices tab -->
            <div id="panel-invoices" style="display:none">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Invoice #</th><th>Date</th><th>Due</th><th>PO #</th>
                                <th class="text-right">Total</th><th class="text-right">Balance</th><th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invoices)): ?>
                                <tr><td colspan="7" class="table__empty">No invoices on record.</td></tr>
                            <?php else: ?>
                                <?php foreach ($invoices as $inv): ?>
                                    <tr class="table__row--clickable" onclick="window.location='/invoices/<?= (int)$inv['id'] ?>'">
                                        <td class="font-mono"><?= e($inv['invoice_number']) ?></td>
                                        <td><?= date('M j, Y', strtotime($inv['invoice_date'])) ?></td>
                                        <td class="<?= ($inv['aging_days'] ?? 0) > 0 ? 'text-danger' : '' ?>"><?= date('M j, Y', strtotime($inv['due_date'])) ?></td>
                                        <td class="text-muted text-sm"><?= e($inv['po_number'] ?? '—') ?></td>
                                        <td class="text-right">$<?= number_format((float)$inv['total_amount'], 2) ?></td>
                                        <td class="text-right <?= (float)$inv['balance_due'] > 0 ? 'text-warning' : 'text-muted' ?>">
                                            <?= (float)$inv['balance_due'] > 0 ? '$' . number_format((float)$inv['balance_due'], 2) : '—' ?>
                                        </td>
                                        <td class="text-center">
                                            <?php $sc = match($inv['status']) { 'paid'=>'badge--success','overdue'=>'badge--danger','partial'=>'badge--warning','void'=>'badge--neutral',default=>'badge--info' }; ?>
                                            <span class="badge <?= $sc ?>"><?= ucfirst($inv['status']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sales Orders tab -->
            <div id="panel-salesorders" style="display:none">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>SO #</th><th>Date</th><th>Ship By</th><th>PO #</th>
                                <th>Rep</th><th class="text-right">Total</th><th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sales_orders)): ?>
                                <tr><td colspan="7" class="table__empty">No sales orders on record.</td></tr>
                            <?php else: ?>
                                <?php
                                $soBadge = ['draft'=>'badge--neutral','confirmed'=>'badge--info','processing'=>'badge--info','partially_shipped'=>'badge--warning','shipped'=>'badge--success','invoiced'=>'badge--success','cancelled'=>'badge--neutral'];
                                $soLabel = ['draft'=>'Draft','confirmed'=>'Confirmed','processing'=>'Processing','partially_shipped'=>'Partial','shipped'=>'Shipped','invoiced'=>'Invoiced','cancelled'=>'Cancelled'];
                                ?>
                                <?php foreach ($sales_orders as $so): ?>
                                    <tr class="table__row--clickable" onclick="window.location='/sales-orders/<?= (int)$so['id'] ?>'">
                                        <td class="font-mono"><?= e($so['so_number']) ?></td>
                                        <td><?= date('M j, Y', strtotime($so['order_date'])) ?></td>
                                        <td class="text-muted text-sm"><?= $so['requested_ship_date'] ? date('M j, Y', strtotime($so['requested_ship_date'])) : '—' ?></td>
                                        <td class="text-muted text-sm"><?= e($so['po_number'] ?? '—') ?></td>
                                        <td class="text-sm"><?= $so['rep_first'] ? e($so['rep_first'] . ' ' . $so['rep_last']) : '<span class="text-muted">—</span>' ?></td>
                                        <td class="text-right">$<?= number_format((float)$so['total_amount'], 2) ?></td>
                                        <td class="text-center">
                                            <span class="badge <?= $soBadge[$so['status']] ?? 'badge--neutral' ?>"><?= $soLabel[$so['status']] ?? ucfirst($so['status']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quotes tab -->
            <div id="panel-quotes" style="display:none">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Quote #</th><th>Date</th><th>Expires</th><th>PO #</th>
                                <th>Rep</th><th class="text-right">Total</th><th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($quotes)): ?>
                                <tr><td colspan="7" class="table__empty">No quotes on record.</td></tr>
                            <?php else: ?>
                                <?php
                                $qBadge = ['draft'=>'badge--secondary','sent'=>'badge--info','accepted'=>'badge--success','declined'=>'badge--danger','expired'=>'badge--warning'];
                                $qLabel = ['draft'=>'Draft','sent'=>'Sent','accepted'=>'Accepted','declined'=>'Declined','expired'=>'Expired'];
                                ?>
                                <?php foreach ($quotes as $qt): ?>
                                    <tr class="table__row--clickable" onclick="window.location='/quotes/<?= (int)$qt['id'] ?>'">
                                        <td class="font-mono"><?= e($qt['quote_number']) ?></td>
                                        <td><?= date('M j, Y', strtotime($qt['quote_date'])) ?></td>
                                        <td class="text-sm text-muted"><?= !empty($qt['expiry_date']) ? date('M j, Y', strtotime($qt['expiry_date'])) : '—' ?></td>
                                        <td class="text-sm text-muted"><?= e($qt['po_number'] ?? '—') ?></td>
                                        <td class="text-sm"><?= $qt['rep_first'] ? e($qt['rep_first'] . ' ' . $qt['rep_last']) : '<span class="text-muted">—</span>' ?></td>
                                        <td class="text-right font-mono">$<?= number_format((float)$qt['total_amount'], 2) ?></td>
                                        <td class="text-center">
                                            <span class="badge <?= $qBadge[$qt['status']] ?? 'badge--secondary' ?>"><?= $qLabel[$qt['status']] ?? ucfirst($qt['status']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tasks tab -->
            <div id="panel-tasks" style="display:none">
                <div style="padding:.75rem 1.25rem;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between">
                    <span style="font-size:.875rem;color:#6b7280"><?= count($tasks ?? []) ?> task<?= count($tasks ?? []) !== 1 ? 's' : '' ?></span>
                    <a href="/tasks/create?customer_id=<?= (int)$customer['id'] ?>" class="btn btn--xs btn--primary">+ Add Task</a>
                </div>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width:36px"></th>
                                <th>Task</th>
                                <th>Assigned</th>
                                <th>Due</th>
                                <th>Priority</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($tasks)): ?>
                            <tr><td colspan="6" class="table__empty">No tasks yet. <a href="/tasks/create?customer_id=<?= (int)$customer['id'] ?>" class="link">Add one</a></td></tr>
                        <?php else:
                            $tPriBadge = ['low'=>'badge--secondary','medium'=>'badge--info','high'=>'badge--warning','urgent'=>'badge--danger'];
                            $tStsBadge = ['open'=>'badge--secondary','in_progress'=>'badge--info','completed'=>'badge--success','cancelled'=>'badge--secondary'];
                            foreach ($tasks as $t):
                                $isOverdue = !empty($t['due_date']) && $t['due_date'] < date('Y-m-d') && !in_array($t['status'],['completed','cancelled']);
                        ?>
                            <tr class="table__row--clickable" onclick="window.location='/tasks/<?= (int)$t['id'] ?>/edit'" style="cursor:pointer">
                                <td onclick="event.stopPropagation()">
                                    <form method="POST" action="/tasks/<?= (int)$t['id'] ?>/status">
                                        <input type="hidden" name="status" value="<?= $t['status']==='completed' ? 'open' : 'completed' ?>">
                                        <input type="hidden" name="redirect" value="/customers/<?= (int)$customer['id'] ?>">
                                        <button type="submit" style="width:20px;height:20px;border-radius:50%;border:2px solid <?= $t['status']==='completed' ? '#10b981' : '#d1d5db' ?>;background:<?= $t['status']==='completed' ? '#10b981' : 'white' ?>;cursor:pointer;padding:0;display:flex;align-items:center;justify-content:center">
                                            <?php if ($t['status']==='completed'): ?>
                                            <svg width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M2 5l2.5 2.5L8 3" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            <?php endif; ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <div style="font-weight:600;<?= $t['status']==='completed' ? 'color:#9ca3af;text-decoration:line-through' : '' ?>"><?= e($t['title']) ?></div>
                                    <?php if (!empty($t['description'])): ?>
                                        <div style="font-size:.8rem;color:#6b7280"><?= e(mb_strimwidth($t['description'], 0, 80, '…')) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:.875rem"><?= !empty($t['assigned_first']) ? e($t['assigned_first'].' '.$t['assigned_last']) : '<span style="color:#9ca3af">—</span>' ?></td>
                                <td style="font-size:.875rem;color:<?= $isOverdue ? '#ef4444' : '#374151' ?>;font-weight:<?= $isOverdue ? '600' : '400' ?>">
                                    <?= !empty($t['due_date']) ? date('M j, Y', strtotime($t['due_date'])) : '—' ?>
                                </td>
                                <td><span class="badge <?= $tPriBadge[$t['priority']] ?? 'badge--secondary' ?>"><?= ucfirst($t['priority']) ?></span></td>
                                <td><span class="badge <?= $tStsBadge[$t['status']] ?? 'badge--secondary' ?>"><?= ucfirst(str_replace('_',' ',$t['status'])) ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payments tab -->
            <div id="panel-payments" style="display:none">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th><th>Method</th><th>Reference #</th>
                                <th>Applied To</th><th>Memo</th><th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payments)): ?>
                                <tr><td colspan="6" class="table__empty">No payments on record.</td></tr>
                            <?php else: ?>
                                <?php foreach ($payments as $pmt): ?>
                                    <tr class="table__row--clickable" onclick="window.location='/payments/<?= (int)$pmt['id'] ?>/edit'">
                                        <td><?= date('M j, Y', strtotime($pmt['payment_date'])) ?></td>
                                        <td><?= e(ucwords(str_replace('_', ' ', $pmt['payment_method']))) ?></td>
                                        <td class="font-mono text-sm"><?= e($pmt['reference_number'] ?? '—') ?></td>
                                        <td class="text-sm text-muted"><?= e($pmt['applied_to'] ?? '—') ?></td>
                                        <td class="text-sm text-muted"><?= e($pmt['memo'] ?? '—') ?></td>
                                        <td class="text-right font-mono <?= (float)$pmt['amount'] > (float)$pmt['total_applied'] ? 'text-warning' : 'text-success' ?>">
                                            $<?= number_format((float)$pmt['amount'], 2) ?>
                                            <?php if ((float)$pmt['amount'] > (float)($pmt['total_applied'] ?? 0) + 0.01): ?>
                                                <?php if (!empty($pmt['sales_order_id'])): ?>
                                                    <span style="font-size:.75rem;font-weight:600;color:#6b7280;display:block">Pending Invoice</span>
                                                <?php else: ?>
                                                    <span style="font-size:.75rem;font-weight:600;color:#d97706;display:block">$<?= number_format((float)$pmt['amount'] - (float)$pmt['total_applied'], 2) ?> unapplied</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

</div>

<script>
var _activeTab = 'notes';
if (window.location.hash === '#notes') _activeTab = 'notes';
function switchTab(name) {
    var tabs = ['notes','all','invoices','salesorders','quotes','payments','tasks'];
    var active = '#0A3D91', inactive = '#6b7280';
    tabs.forEach(function(t) {
        var btn   = document.getElementById('tab-' + t);
        var panel = document.getElementById('panel-' + t);
        var on    = (t === name);
        if (btn)   { btn.style.color = on ? active : inactive; btn.style.borderBottom = on ? '2px solid ' + active : '2px solid transparent'; }
        if (panel) panel.style.display = on ? '' : 'none';
    });
    _activeTab = name;
}
function switchInfoTab(name) {
    var tabs = ['contact','address'];
    var active = '#0A3D91', inactive = '#6b7280';
    tabs.forEach(function(t) {
        var btn   = document.getElementById('itab-' + t);
        var panel = document.getElementById('ipanel-' + t);
        var on    = (t === name);
        btn.style.color        = on ? active : inactive;
        btn.style.borderBottom = on ? '2px solid ' + active : '2px solid transparent';
        panel.style.display    = on ? '' : 'none';
    });
}
switchTab(_activeTab);
</script>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
