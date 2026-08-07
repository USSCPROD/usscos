<?php
$priorityBadge = ['low'=>'badge--secondary','medium'=>'badge--info','high'=>'badge--warning','urgent'=>'badge--danger'];
$priorityLabel = ['low'=>'Low','medium'=>'Medium','high'=>'High','urgent'=>'Urgent'];
$statusBadge   = ['open'=>'badge--secondary','in_progress'=>'badge--info','completed'=>'badge--success','cancelled'=>'badge--secondary'];
$statusLabel   = ['open'=>'Open','in_progress'=>'In Progress','completed'=>'Completed','cancelled'=>'Cancelled'];

$content = ob_start(); ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem">
    <div>
        <h1 style="margin:0;font-size:1.6rem;font-weight:800;color:#111">Tasks</h1>
        <div style="color:#6b7280;font-size:.875rem;margin-top:.2rem"><?= number_format($paginated['total']) ?> total</div>
    </div>
    <a href="/tasks/create" class="btn btn--primary">+ New Task</a>
</div>

<!-- Stats row -->
<div style="margin-bottom:1.5rem">
    <table style="width:100%;border-collapse:separate;border-spacing:.75rem 0">
        <tr>
            <?php
            $statCards = [
                ['label'=>'Open',        'val'=>$stats['open_count'],        'color'=>'#3b82f6'],
                ['label'=>'In Progress', 'val'=>$stats['in_progress_count'], 'color'=>'#f59e0b'],
                ['label'=>'Overdue',     'val'=>$stats['overdue_count'],     'color'=>'#ef4444'],
                ['label'=>'Urgent',      'val'=>$stats['urgent_count'],      'color'=>'#8b5cf6'],
                ['label'=>'Completed',   'val'=>$stats['completed_count'],   'color'=>'#10b981'],
            ];
            foreach ($statCards as $sc): ?>
            <td style="width:20%">
                <div class="card" style="padding:1rem 1.25rem">
                    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280"><?= $sc['label'] ?></div>
                    <div style="font-size:1.75rem;font-weight:800;color:<?= $sc['color'] ?>;margin-top:.25rem"><?= (int)$sc['val'] ?></div>
                </div>
            </td>
            <?php endforeach; ?>
        </tr>
    </table>
</div>

<!-- Filters -->
<div class="card" style="padding:1rem 1.25rem;margin-bottom:1.25rem">
    <form method="GET" action="/tasks">
        <table style="width:100%;border-collapse:collapse">
            <tr>
                <td style="padding-right:.75rem;width:30%">
                    <input type="text" name="q" value="<?= e($filters['search']) ?>" placeholder="Search tasks…" class="input" style="width:100%;box-sizing:border-box">
                </td>
                <td style="padding-right:.75rem;width:15%">
                    <select name="status" class="input" style="width:100%;box-sizing:border-box">
                        <option value="all" <?= $filters['status']==='all'?'selected':'' ?>>All Statuses</option>
                        <?php foreach (['open'=>'Open','in_progress'=>'In Progress','completed'=>'Completed','cancelled'=>'Cancelled'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= $filters['status']===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td style="padding-right:.75rem;width:15%">
                    <select name="priority" class="input" style="width:100%;box-sizing:border-box">
                        <option value="all" <?= $filters['priority']==='all'?'selected':'' ?>>All Priorities</option>
                        <?php foreach (['urgent'=>'Urgent','high'=>'High','medium'=>'Medium','low'=>'Low'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= $filters['priority']===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td style="padding-right:.75rem;width:15%">
                    <select name="due" class="input" style="width:100%;box-sizing:border-box">
                        <option value="" <?= $filters['due']===''?'selected':'' ?>>Any Due Date</option>
                        <option value="overdue" <?= $filters['due']==='overdue'?'selected':'' ?>>Overdue</option>
                        <option value="today"   <?= $filters['due']==='today'?'selected':'' ?>>Due Today</option>
                        <option value="week"    <?= $filters['due']==='week'?'selected':'' ?>>Due This Week</option>
                    </select>
                </td>
                <td style="padding-right:.75rem;width:18%">
                    <select name="assigned_to" class="input" style="width:100%;box-sizing:border-box">
                        <option value="">All Assignees</option>
                        <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= (int)$filters['assigned_to']===(int)$u['id']?'selected':'' ?>><?= e($u['first_name'].' '.$u['last_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td style="width:7%">
                    <button type="submit" class="btn btn--secondary" style="width:100%">Filter</button>
                </td>
            </tr>
        </table>
    </form>
</div>

<!-- Table -->
<div class="card" style="padding:0;overflow:hidden">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:36px"></th>
                    <th>Task</th>
                    <th>Linked To</th>
                    <th>Assigned</th>
                    <th>Due</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($paginated['data'])): ?>
                <tr><td colspan="8" class="table__empty">No tasks found.</td></tr>
            <?php else: ?>
                <?php foreach ($paginated['data'] as $t):
                    $isOverdue = !empty($t['due_date']) && $t['due_date'] < date('Y-m-d') && !in_array($t['status'], ['completed','cancelled']);
                    $isDueToday = !empty($t['due_date']) && $t['due_date'] === date('Y-m-d');
                ?>
                <tr class="table__row--clickable" onclick="window.location='/tasks/<?= (int)$t['id'] ?>/edit'" style="cursor:pointer">
                    <!-- Quick complete checkbox -->
                    <td onclick="event.stopPropagation()">
                        <form method="POST" action="/tasks/<?= (int)$t['id'] ?>/status">
                        <?= csrf_field() ?>
                            <input type="hidden" name="status" value="<?= $t['status']==='completed' ? 'open' : 'completed' ?>">
                            <input type="hidden" name="redirect" value="/tasks?<?= e(http_build_query(array_filter($filters))) ?>">
                            <button type="submit" title="<?= $t['status']==='completed' ? 'Mark open' : 'Mark complete' ?>"
                                style="width:20px;height:20px;border-radius:50%;border:2px solid <?= $t['status']==='completed' ? '#10b981' : '#d1d5db' ?>;background:<?= $t['status']==='completed' ? '#10b981' : 'white' ?>;cursor:pointer;padding:0;display:flex;align-items:center;justify-content:center">
                                <?php if ($t['status']==='completed'): ?>
                                <svg width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M2 5l2.5 2.5L8 3" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <?php endif; ?>
                            </button>
                        </form>
                    </td>
                    <td>
                        <div style="font-weight:600;color:<?= $t['status']==='completed' ? '#9ca3af' : '#111' ?>;<?= $t['status']==='completed' ? 'text-decoration:line-through' : '' ?>"><?= e($t['title']) ?></div>
                        <?php if (!empty($t['description'])): ?>
                            <div style="font-size:.8rem;color:#6b7280;margin-top:.15rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:300px"><?= e($t['description']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.875rem;color:#374151">
                        <?php if ($t['customer_name']): ?>
                            <a href="/customers/<?= (int)$t['customer_id'] ?>" class="link" onclick="event.stopPropagation()"><?= e($t['customer_name']) ?></a>
                        <?php elseif ($t['lead_name']): ?>
                            <a href="/leads/<?= (int)$t['lead_id'] ?>" class="link" onclick="event.stopPropagation()"><?= e($t['lead_name']) ?></a>
                            <span style="font-size:.75rem;color:#d97706"> Lead</span>
                        <?php elseif ($t['opportunity_name']): ?>
                            <?= e($t['opportunity_name']) ?>
                        <?php elseif ($t['quote_number']): ?>
                            <a href="/quotes/<?= (int)$t['quote_id'] ?>" class="link" onclick="event.stopPropagation()"><?= e($t['quote_number']) ?></a>
                        <?php elseif ($t['so_number']): ?>
                            <a href="/sales-orders/<?= (int)$t['sales_order_id'] ?>" class="link" onclick="event.stopPropagation()"><?= e($t['so_number']) ?></a>
                        <?php else: ?>
                            <span style="color:#9ca3af">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.875rem">
                        <?= !empty($t['assigned_first']) ? e($t['assigned_first'].' '.$t['assigned_last']) : '<span style="color:#9ca3af">Unassigned</span>' ?>
                    </td>
                    <td style="font-size:.875rem;color:<?= $isOverdue ? '#ef4444' : ($isDueToday ? '#f59e0b' : '#374151') ?>;font-weight:<?= $isOverdue||$isDueToday ? '600' : '400' ?>">
                        <?= !empty($t['due_date']) ? date('M j, Y', strtotime($t['due_date'])) : '—' ?>
                        <?php if ($isOverdue): ?><div style="font-size:.75rem;color:#ef4444">Overdue</div><?php endif; ?>
                        <?php if ($isDueToday): ?><div style="font-size:.75rem;color:#f59e0b">Today</div><?php endif; ?>
                    </td>
                    <td><span class="badge <?= $priorityBadge[$t['priority']] ?? 'badge--secondary' ?>"><?= $priorityLabel[$t['priority']] ?? ucfirst($t['priority']) ?></span></td>
                    <td><span class="badge <?= $statusBadge[$t['status']] ?? 'badge--secondary' ?>"><?= $statusLabel[$t['status']] ?? ucfirst($t['status']) ?></span></td>
                    <td><a href="/tasks/<?= (int)$t['id'] ?>/edit" class="btn btn--xs btn--secondary" onclick="event.stopPropagation()">Edit</a></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($paginated['last_page'] > 1): ?>
    <div style="padding:.75rem 1.25rem;border-top:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between">
        <div style="font-size:.875rem;color:#6b7280">
            Showing <?= $paginated['from'] ?>–<?= $paginated['to'] ?> of <?= $paginated['total'] ?>
        </div>
        <div style="display:flex;gap:.35rem">
            <?php for ($p = 1; $p <= $paginated['last_page']; $p++): ?>
            <a href="?<?= e(http_build_query(array_merge(array_filter($filters), ['page'=>$p]))) ?>"
               style="padding:.3rem .65rem;border-radius:6px;border:1px solid <?= $p===$paginated['current_page']?'#222b59':'#d1d5db' ?>;background:<?= $p===$paginated['current_page']?'#222b59':'white' ?>;color:<?= $p===$paginated['current_page']?'white':'#374151' ?>;font-size:.875rem;text-decoration:none"><?= $p ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
