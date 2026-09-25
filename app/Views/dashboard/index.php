<?php
ob_start();
$d       = $dashboard ?? [];
$kpis    = $d['kpis']            ?? [];
$focus   = $d['focus_items']     ?? [];
$activity = $d['recent_activity'] ?? [];
$chart   = $d['revenue_chart']   ?? [];
$products = $d['top_products']   ?? [];

function kpiTrend(float $pct): string {
    if ($pct > 0)  return '<span class="kpi-card__trend kpi-card__trend--up">▲ ' . number_format(abs($pct), 1) . '%</span>';
    if ($pct < 0)  return '<span class="kpi-card__trend kpi-card__trend--down">▼ ' . number_format(abs($pct), 1) . '%</span>';
    return '<span class="kpi-card__trend kpi-card__trend--neutral">— 0%</span>';
}
?>

<!-- KPI Cards -->
<div class="kpi-grid">

    <div class="kpi-card">
        <div class="kpi-card__header">
            <span class="kpi-card__label">Sales (YTD)</span>
            <div class="kpi-card__icon kpi-card__icon--green">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <div class="kpi-card__value">$<?= number_format((float)($kpis['sales_ytd'] ?? 0), 0) ?></div>
        <div class="kpi-card__meta">
            <span class="kpi-card__trend kpi-card__trend--neutral">invoiced this year</span>
            <span>vs last month</span>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-card__header">
            <span class="kpi-card__label">Sales (MTD)</span>
            <div class="kpi-card__icon kpi-card__icon--blue">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
        </div>
        <div class="kpi-card__value"><?= isset($kpis['sales_mtd']) ? '$' . number_format((float)$kpis['sales_mtd'], 0) : '$0' ?></div>
        <div class="kpi-card__meta">
            <?= kpiTrend((float)($kpis['sales_trend'] ?? 0)) ?>
            <span style="margin-left:.4rem;color:var(--color-text-tertiary);font-size:.78rem">
                vs same days last month
            </span>
            <span>vs last month</span>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-card__header">
            <span class="kpi-card__label">Overdue</span>
            <div class="kpi-card__icon kpi-card__icon--purple">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
        </div>
        <div class="kpi-card__value">$<?= number_format((float)($kpis['overdue'] ?? 0), 0) ?></div>
        <div class="kpi-card__meta">
            <span class="kpi-card__trend <?= (int)($kpis['overdue_count'] ?? 0) > 0 ? 'kpi-card__trend--down' : 'kpi-card__trend--neutral' ?>">
                <?= (int)($kpis['overdue_count'] ?? 0) ?> past due
            </span>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-card__header">
            <span class="kpi-card__label">Open Invoices</span>
            <div class="kpi-card__icon kpi-card__icon--orange">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
        </div>
        <div class="kpi-card__value"><?= isset($kpis['open_invoices']) ? '$' . number_format((float)$kpis['open_invoices'], 0) : '$0' ?></div>
        <div class="kpi-card__meta">
            <span><?= number_format((int)($kpis['open_invoices_count'] ?? 0)) ?> unpaid</span>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-card__header">
            <span class="kpi-card__label">Stock on Hand</span>
            <div class="kpi-card__icon kpi-card__icon--teal">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
        </div>
        <?php // Units, not dollars — who owns inventory valuation is still open with the accountant. ?>
        <div class="kpi-card__value"><?= number_format((float)($kpis['stock_units'] ?? 0), 0) ?></div>
        <div class="kpi-card__meta">
            <span><?= number_format((int)($kpis['stock_products'] ?? 0)) ?> products in a location</span>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-card__header">
            <span class="kpi-card__label">Open POs</span>
            <div class="kpi-card__icon kpi-card__icon--indigo">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
            </div>
        </div>
        <div class="kpi-card__value"><?= isset($kpis['open_pos']) ? '$' . number_format((float)$kpis['open_pos'], 0) : '$0' ?></div>
        <div class="kpi-card__meta">
            <span><?= number_format((int)($kpis['open_pos_count'] ?? 0)) ?> awaiting delivery</span>
        </div>
    </div>

</div>

<!-- Main Grid: Chart + Focus Items -->
<div class="dashboard-grid" style="margin-bottom:var(--space-6)">

    <!-- Business Overview Chart -->
    <div class="chart-card">
        <div class="chart-card__header">
            <div>
                <div class="chart-card__title">Business Overview</div>
                <div class="chart-card__legend" style="margin-top:var(--space-2)">
                    <div class="chart-legend-item">
                        <div class="chart-legend-dot chart-legend-dot--solid"></div>
                        This Month
                    </div>
                    <div class="chart-legend-item">
                        <div class="chart-legend-dot chart-legend-dot--dashed"></div>
                        Last Month
                    </div>
                </div>
            </div>
            <select class="chart-select" id="chartMetric">
                <option value="revenue">Revenue</option>
                <option value="profit">Profit</option>
                <option value="invoices">Invoices</option>
            </select>
        </div>
        <div class="chart-card__body">
            <div class="chart-wrap">
                <canvas id="overviewChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Things to Focus On -->
    <div class="focus-card">
        <div class="focus-card__header">
            <div class="focus-card__title">Things to Focus On</div>
        </div>
        <div class="focus-list">
            <?php if (empty($focus)): ?>
                <div class="focus-empty">
                    <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="margin:0 auto var(--space-3);color:var(--color-text-tertiary)">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    All caught up! Nothing needs attention right now.
                </div>
            <?php else: ?>
                <?php foreach ($focus as $item): ?>
                <a href="<?= e($item['url'] ?? '#') ?>" class="focus-item">
                    <div class="focus-item__icon focus-item__icon--<?= e($item['color'] ?? 'blue') ?>">
                        <?= $item['icon'] ?? '' ?>
                    </div>
                    <div class="focus-item__body">
                        <div class="focus-item__title"><?= e($item['title']) ?></div>
                        <div class="focus-item__sub"><?= e($item['subtitle'] ?? '') ?></div>
                    </div>
                    <svg class="focus-item__chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- My Tasks -->
<?php
$myTasks    = $d['my_tasks'] ?? [];
$taskStats  = $d['my_task_stats'] ?? [];
$priBadge   = ['low'=>'badge--secondary','medium'=>'badge--info','high'=>'badge--warning','urgent'=>'badge--danger'];
?>
<div class="card" style="padding:0;overflow:hidden;margin-bottom:1.5rem">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;border-bottom:1px solid #e5e7eb">
        <div style="display:flex;align-items:center;gap:1rem">
            <span style="font-size:1rem;font-weight:700;color:#111">My Tasks</span>
            <?php if ((int)($taskStats['overdue_count'] ?? 0) > 0): ?>
                <span style="background:#ef4444;color:white;border-radius:10px;padding:.15rem .55rem;font-size:.75rem;font-weight:600"><?= (int)$taskStats['overdue_count'] ?> overdue</span>
            <?php endif; ?>
            <?php if ((int)($taskStats['urgent_count'] ?? 0) > 0): ?>
                <span style="background:#8b5cf6;color:white;border-radius:10px;padding:.15rem .55rem;font-size:.75rem;font-weight:600"><?= (int)$taskStats['urgent_count'] ?> urgent</span>
            <?php endif; ?>
        </div>
        <div style="display:flex;align-items:center;gap:.75rem">
            <span style="font-size:.8rem;color:#6b7280"><?= (int)($taskStats['open_count'] ?? 0) + (int)($taskStats['in_progress_count'] ?? 0) ?> open</span>
            <a href="/tasks/create" class="btn btn--xs btn--primary">+ New Task</a>
            <a href="/tasks" class="btn btn--xs btn--secondary">View All</a>
        </div>
    </div>
    <?php if (empty($myTasks)): ?>
        <div style="padding:2rem;text-align:center;color:#6b7280;font-size:.875rem">
            <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" style="margin:0 auto .5rem;display:block;color:#d1d5db">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            No open tasks assigned to you.
        </div>
    <?php else: ?>
    <table style="width:100%;border-collapse:collapse">
        <?php foreach ($myTasks as $t):
            $isOverdue = !empty($t['due_date']) && $t['due_date'] < date('Y-m-d');
            $isDueToday = !empty($t['due_date']) && $t['due_date'] === date('Y-m-d');
            $linked = $t['customer_name'] ?? $t['lead_name'] ?? $t['opportunity_name'] ?? null;
        ?>
        <tr style="border-bottom:1px solid #f3f4f6;cursor:pointer" onclick="window.location='/tasks/<?= (int)$t['id'] ?>/edit'">
            <td style="padding:.7rem .75rem .7rem 1rem;width:32px" onclick="event.stopPropagation()">
                <form method="POST" action="/tasks/<?= (int)$t['id'] ?>/status">
                <?= csrf_field() ?>
                    <input type="hidden" name="status" value="completed">
                    <input type="hidden" name="redirect" value="/dashboard">
                    <button type="submit" title="Mark complete"
                        style="width:20px;height:20px;border-radius:50%;border:2px solid #d1d5db;background:white;cursor:pointer;padding:0;display:flex;align-items:center;justify-content:center">
                    </button>
                </form>
            </td>
            <td style="padding:.7rem .5rem">
                <div style="font-weight:600;font-size:.9rem;color:#111"><?= e($t['title']) ?></div>
                <?php if ($linked): ?>
                    <div style="font-size:.78rem;color:#6b7280;margin-top:.1rem"><?= e($linked) ?></div>
                <?php endif; ?>
            </td>
            <td style="padding:.7rem .75rem;white-space:nowrap">
                <span class="badge <?= $priBadge[$t['priority']] ?? 'badge--secondary' ?>" style="font-size:.72rem"><?= ucfirst($t['priority']) ?></span>
            </td>
            <td style="padding:.7rem 1rem .7rem .75rem;text-align:right;white-space:nowrap;font-size:.82rem;font-weight:<?= $isOverdue||$isDueToday?'600':'400' ?>;color:<?= $isOverdue?'#ef4444':($isDueToday?'#f59e0b':'#6b7280') ?>">
                <?php if (!empty($t['due_date'])): ?>
                    <?= $isOverdue ? 'Overdue · ' : ($isDueToday ? 'Today' : date('M j', strtotime($t['due_date']))) ?>
                    <?= $isOverdue ? date('M j', strtotime($t['due_date'])) : '' ?>
                <?php else: ?>
                    <span style="color:#d1d5db">No due date</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php if (count($myTasks) >= 10): ?>
        <div style="padding:.65rem 1.25rem;border-top:1px solid #f3f4f6;text-align:center">
            <a href="/tasks?assigned_to=<?= (int)(\App\Core\Auth::user()['id'] ?? 0) ?>" class="btn btn--ghost btn--sm">View all my tasks →</a>
        </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Bottom Grid: Activity + Products + AI -->
<div class="dashboard-grid--3col">

    <!-- Recent Activity -->
    <div class="activity-card">
        <div class="activity-card__header">
            <span class="card__title">Recent Activity</span>
        </div>
        <?php if (empty($activity)): ?>
            <div style="padding:var(--space-8);text-align:center;color:var(--color-text-tertiary);font-size:var(--font-size-sm)">
                No activity yet. Transactions will appear here.
            </div>
        <?php else: ?>
        <table class="activity-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Reference</th>
                    <th>Time</th>
                    <th>User</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($activity as $row): ?>
                <tr>
                    <td>
                        <div class="activity-type-wrap">
                            <div class="activity-type-icon activity-type-icon--<?= e($row['type'] ?? 'invoice') ?>">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <?= e($row['description'] ?? '') ?>
                        </div>
                    </td>
                    <td><?= e($row['description'] ?? '') ?></td>
                    <td><a href="#" class="activity-ref"><?= e($row['reference'] ?? '') ?></a></td>
                    <td class="activity-time"><?= e($row['time'] ?? '') ?></td>
                    <td>
                        <div class="activity-user">
                            <div class="avatar avatar--xs" style="background:var(--color-accent)"><?= e(strtoupper(substr($row['user'] ?? 'U', 0, 1))) ?></div>
                            <?= e($row['user'] ?? '') ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        <div class="activity-card__footer">
            <a href="/activity" class="btn btn--ghost btn--sm">View all activity →</a>
        </div>
    </div>

    <!-- Top Selling Products -->
    <div class="products-card">
        <div class="products-card__header">
            <span class="card__title">Top Selling Products</span>
            <select class="chart-select">
                <option>This Month</option>
                <option>Last Month</option>
                <option>This Year</option>
            </select>
        </div>
        <?php if (empty($products)): ?>
            <div style="padding:var(--space-8);text-align:center;color:var(--color-text-tertiary);font-size:var(--font-size-sm)">
                No sales data yet.
            </div>
        <?php else: ?>
        <table class="products-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Sold</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td>
                        <div class="product-name-wrap">
                            <div class="product-icon">📦</div>
                            <?= e($product['name']) ?>
                        </div>
                    </td>
                    <td><?= number_format((int)($product['sold'] ?? 0)) ?></td>
                    <td>$<?= number_format((float)($product['revenue'] ?? 0), 0) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        <div class="products-card__footer">
            <a href="/inventory" class="btn btn--ghost btn--sm">View all products →</a>
        </div>
    </div>

    <!-- AI Assistant -->
    <div class="ai-card">
        <div class="ai-card__header">
            <div class="ai-card__title-wrap">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" style="color:var(--color-ai)">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                <span class="ai-card__title">AI Assistant</span>
                <span class="ai-badge">BETA</span>
            </div>
            <button class="topbar__icon-btn" style="width:28px;height:28px" title="Options">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                </svg>
            </button>
        </div>

        <div class="ai-card__messages" id="aiMessages">
            <p class="ai-placeholder">Ask anything about your business...</p>
        </div>

        <div class="ai-card__actions">
            <button class="ai-action-btn" onclick="USSCOS.AI.ask('Who owes us money?')">Who owes us money?</button>
            <button class="ai-action-btn" onclick="USSCOS.AI.ask('What should I focus on today?')">What to focus on?</button>
            <button class="ai-action-btn" onclick="USSCOS.AI.ask('Why did profits decrease?')">Why did profits decrease?</button>
        </div>

        <div class="ai-card__input-wrap">
            <input
                type="text"
                class="ai-input"
                id="aiInput"
                placeholder="Ask a follow up..."
                autocomplete="off"
            >
            <button class="ai-send-btn" id="aiSend">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
            </button>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();

// Build chart data for JS
$chartLabels    = json_encode($chart['labels']     ?? []);
$chartThisMonth = json_encode($chart['this_month'] ?? []);
$chartLastMonth = json_encode($chart['last_month'] ?? []);

ob_start();
?>
<script>
(function() {
    // -------------------------------------------------------------------------
    // Revenue Chart
    // -------------------------------------------------------------------------
    const labels    = <?= $chartLabels ?>;
    const thisMonth = <?= $chartThisMonth ?>;
    const lastMonth = <?= $chartLastMonth ?>;

    const ctx = document.getElementById('overviewChart');
    if (!ctx) return;

    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels.length ? labels : generateDayLabels(),
            datasets: [
                {
                    label: 'This Month',
                    data: thisMonth.length ? thisMonth : [],
                    borderColor: '#2563EB',
                    backgroundColor: 'rgba(37,99,235,0.08)',
                    borderWidth: 2.5,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    fill: true,
                    tension: 0.4,
                },
                {
                    label: 'Last Month',
                    data: lastMonth.length ? lastMonth : [],
                    borderColor: '#D1D5DB',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    fill: false,
                    tension: 0.4,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#111827',
                    titleColor: '#F9FAFB',
                    bodyColor: '#D1D5DB',
                    padding: 12,
                    borderColor: '#374151',
                    borderWidth: 1,
                    callbacks: {
                        label: ctx => ' $' + ctx.parsed.y.toLocaleString()
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#9CA3AF', font: { size: 11, family: 'Inter' } }
                },
                y: {
                    grid: { color: '#F3F4F6' },
                    ticks: {
                        color: '#9CA3AF',
                        font: { size: 11, family: 'Inter' },
                        callback: v => '$' + (v >= 1000 ? (v/1000).toFixed(0) + 'k' : v)
                    },
                    border: { dash: [3, 3] }
                }
            }
        }
    });

    function generateDayLabels() {
        const days = [];
        const now = new Date();
        const daysInMonth = new Date(now.getFullYear(), now.getMonth()+1, 0).getDate();
        for (let i = 1; i <= daysInMonth; i += 5) {
            days.push('Day ' + i);
        }
        return days;
    }

    // -------------------------------------------------------------------------
    // AI Assistant
    // -------------------------------------------------------------------------
    window.USSCOS = window.USSCOS || {};
    window.USSCOS.AI = {
        messagesEl: document.getElementById('aiMessages'),
        inputEl:    document.getElementById('aiInput'),
        sendBtn:    document.getElementById('aiSend'),

        init() {
            this.sendBtn?.addEventListener('click', () => this.submit());
            this.inputEl?.addEventListener('keydown', e => {
                if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); this.submit(); }
            });
        },

        ask(question) {
            if (this.inputEl) this.inputEl.value = question;
            this.submit();
        },

        submit() {
            const question = this.inputEl?.value?.trim();
            if (!question) return;

            // Clear placeholder
            const placeholder = this.messagesEl?.querySelector('.ai-placeholder');
            if (placeholder) placeholder.remove();

            this.addMessage('user', question);
            this.inputEl.value = '';

            this.addTyping();
            this.sendToAPI(question);
        },

        addMessage(role, content) {
            const msg = document.createElement('div');
            msg.className = `ai-message ai-message--${role}`;
            msg.innerHTML = `<div class="ai-bubble">${this.escapeHtml(content)}</div>`;
            this.messagesEl?.appendChild(msg);
            this.scrollToBottom();
        },

        addTyping() {
            const msg = document.createElement('div');
            msg.className = 'ai-message ai-message--assistant';
            msg.id = 'ai-typing';
            msg.innerHTML = '<div class="ai-bubble" style="color:var(--color-text-tertiary)">Thinking...</div>';
            this.messagesEl?.appendChild(msg);
            this.scrollToBottom();
        },

        removeTyping() {
            document.getElementById('ai-typing')?.remove();
        },

        async sendToAPI(question) {
            try {
                const res = await window.USSCOS.Http.post('/ai/chat', { message: question });
                this.removeTyping();
                this.addMessage('assistant', res.reply || 'I need more data to answer that. Connect your modules first.');
            } catch (e) {
                this.removeTyping();
                this.addMessage('assistant', 'AI Assistant is not yet configured. Add your OpenAI API key in Settings → Integrations.');
            }
        },

        scrollToBottom() {
            if (this.messagesEl) this.messagesEl.scrollTop = this.messagesEl.scrollHeight;
        },

        escapeHtml(str) {
            return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }
    };

    window.USSCOS.AI.init();

})();
</script>
<?php
$scripts = ob_get_clean();

include BASE_PATH . '/app/Views/layouts/app.php';
