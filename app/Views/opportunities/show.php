<?php
ob_start();

$stageOrder  = ['prospecting', 'proposal', 'negotiation', 'closed_won', 'closed_lost'];
$stageLabel  = [
    'prospecting' => 'Prospecting',
    'proposal'    => 'Proposal',
    'negotiation' => 'Negotiation',
    'closed_won'  => 'Closed Won',
    'closed_lost' => 'Closed Lost',
];
$stagePct = ['prospecting' => 25, 'proposal' => 50, 'negotiation' => 75, 'closed_won' => 100, 'closed_lost' => 100];

$stage     = $opp['stage'] ?? 'prospecting';
$isOpen    = !in_array($stage, ['closed_won', 'closed_lost']);
$companyName = $opp['customer_name'] ?? $opp['lead_name'] ?? '—';

$stageColor = [
    'prospecting' => '#6b7280',
    'proposal'    => '#0A3D91',
    'negotiation' => '#d97706',
    'closed_won'  => '#16a34a',
    'closed_lost' => '#dc2626',
];

$priorityLabel = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent'];
$priorityColor = ['low' => '#6b7280', 'medium' => '#0A3D91', 'high' => '#d97706', 'urgent' => '#dc2626'];
$statusLabel   = ['open' => 'Open', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];

$openTasks = array_filter($tasks, fn($t) => !in_array($t['status'], ['completed', 'cancelled']));
$doneTasks = array_filter($tasks, fn($t) => in_array($t['status'], ['completed', 'cancelled']));
?>

<!-- Page header -->
<table style="width:100%;border-collapse:collapse;margin-bottom:1.25rem">
    <tr style="vertical-align:middle">
        <td>
            <a href="/pipeline" class="back-link">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                Pipeline
            </a>
            <h1 class="page-title" style="margin-top:.25rem"><?= e($opp['name']) ?></h1>
        </td>
        <td style="text-align:right;white-space:nowrap;vertical-align:bottom">
            <a href="/opportunities/<?= (int)$opp['id'] ?>/edit" class="btn btn--secondary">Edit</a>
        </td>
    </tr>
</table>

<!-- Stage progress bar -->
<div style="background:#fff;border:1px solid #d1d5db;border-radius:8px;padding:1rem 1.5rem;margin-bottom:1.25rem">
    <table style="width:100%;border-collapse:collapse">
        <tr>
            <?php
            $steps = ['prospecting' => 'Prospecting', 'proposal' => 'Proposal', 'negotiation' => 'Negotiation', 'closed_won' => 'Closed Won'];
            $stageIdx = array_search($stage, array_keys($steps));
            if ($stage === 'closed_lost') $stageIdx = -1;
            $i = 0;
            foreach ($steps as $key => $label):
                $isActive  = ($key === $stage);
                $isPast    = ($i < $stageIdx);
                $isLast    = ($i === count($steps) - 1);
                $bg = $isActive ? '#222b59' : ($isPast ? '#e0e7ff' : '#f3f4f6');
                $color = $isActive ? '#fff' : ($isPast ? '#222b59' : '#9ca3af');
                $border = $isLast ? '' : 'border-right:1px solid #d1d5db';
            ?>
            <td style="padding:.6rem 1rem;text-align:center;background:<?= $bg ?>;color:<?= $color ?>;font-size:.8rem;font-weight:700;<?= $border ?>;<?= $i === 0 ? 'border-radius:6px 0 0 6px' : '' ?><?= $isLast ? 'border-radius:0 6px 6px 0' : '' ?>">
                <?= $label ?>
            </td>
            <?php $i++; endforeach; ?>
            <?php if ($stage === 'closed_lost'): ?>
            <td style="padding:.6rem 1rem;text-align:center;background:#fef2f2;color:#dc2626;font-size:.8rem;font-weight:700;border-left:1px solid #d1d5db;border-radius:0 6px 6px 0">
                Closed Lost
            </td>
            <?php endif; ?>
        </tr>
    </table>
    <?php if ($stage === 'closed_lost' && !empty($opp['lost_reason'])): ?>
    <div style="margin-top:.5rem;font-size:.85rem;color:#dc2626">Lost reason: <?= e($opp['lost_reason']) ?></div>
    <?php endif; ?>
</div>

<!-- Main two-column layout -->
<table style="width:100%;border-collapse:collapse;vertical-align:top">
    <tr style="vertical-align:top">

        <!-- LEFT: Details -->
        <td style="width:38%;padding-right:1rem">
            <div style="background:#fff;border:1px solid #d1d5db;border-radius:8px;overflow:hidden;margin-bottom:1.25rem">
                <div style="padding:.75rem 1.25rem;background:#f8f9fb;border-bottom:1px solid #d1d5db;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">Details</div>
                <table style="width:100%;border-collapse:collapse">
                    <tr style="border-bottom:1px solid #f3f4f6">
                        <td style="padding:.65rem 1.25rem;font-size:.8rem;color:#6b7280;white-space:nowrap;width:40%">Company</td>
                        <td style="padding:.65rem 1.25rem;font-size:.9rem;font-weight:600">
                            <?php if (!empty($opp['customer_id'])): ?>
                                <a href="/customers/<?= (int)$opp['customer_id'] ?>" style="color:#222b59"><?= e($companyName) ?></a>
                            <?php elseif (!empty($opp['lead_id'])): ?>
                                <a href="/leads/<?= (int)$opp['lead_id'] ?>" style="color:#222b59"><?= e($companyName) ?></a>
                            <?php else: ?>
                                <?= e($companyName) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr style="border-bottom:1px solid #f3f4f6">
                        <td style="padding:.65rem 1.25rem;font-size:.8rem;color:#6b7280">Stage</td>
                        <td style="padding:.65rem 1.25rem">
                            <span style="display:inline-block;padding:.2rem .6rem;border-radius:4px;font-size:.75rem;font-weight:700;background:<?= $stageColor[$stage] ?? '#6b7280' ?>22;color:<?= $stageColor[$stage] ?? '#6b7280' ?>"><?= $stageLabel[$stage] ?? $stage ?></span>
                        </td>
                    </tr>
                    <tr style="border-bottom:1px solid #f3f4f6">
                        <td style="padding:.65rem 1.25rem;font-size:.8rem;color:#6b7280">Value</td>
                        <td style="padding:.65rem 1.25rem;font-size:.95rem;font-weight:700;color:#222b59"><?= money((float)($opp['expected_value'] ?? 0)) ?></td>
                    </tr>
                    <tr style="border-bottom:1px solid #f3f4f6">
                        <td style="padding:.65rem 1.25rem;font-size:.8rem;color:#6b7280">Probability</td>
                        <td style="padding:.65rem 1.25rem;font-size:.9rem"><?= (int)($opp['probability'] ?? 0) ?>%</td>
                    </tr>
                    <tr style="border-bottom:1px solid #f3f4f6">
                        <td style="padding:.65rem 1.25rem;font-size:.8rem;color:#6b7280">Rep</td>
                        <td style="padding:.65rem 1.25rem;font-size:.9rem">
                            <?= !empty($opp['rep_first']) ? e($opp['rep_first'] . ' ' . $opp['rep_last']) : '—' ?>
                        </td>
                    </tr>
                    <tr style="border-bottom:1px solid #f3f4f6">
                        <td style="padding:.65rem 1.25rem;font-size:.8rem;color:#6b7280">Close Date</td>
                        <td style="padding:.65rem 1.25rem;font-size:.9rem">
                            <?= !empty($opp['expected_close']) ? date('M j, Y', strtotime($opp['expected_close'])) : '—' ?>
                        </td>
                    </tr>
                    <?php if (!empty($opp['notes'])): ?>
                    <tr>
                        <td style="padding:.65rem 1.25rem;font-size:.8rem;color:#6b7280;vertical-align:top">Notes</td>
                        <td style="padding:.65rem 1.25rem;font-size:.85rem;color:#374151"><?= nl2br(e($opp['notes'])) ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Move Stage -->
            <?php if ($isOpen): ?>
            <div style="background:#fff;border:1px solid #d1d5db;border-radius:8px;overflow:hidden">
                <div style="padding:.75rem 1.25rem;background:#f8f9fb;border-bottom:1px solid #d1d5db;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">Move Stage</div>
                <div style="padding:1rem 1.25rem">
                    <form method="post" action="/opportunities/<?= (int)$opp['id'] ?>/stage">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                        <table style="width:100%;border-collapse:collapse">
                            <tr>
                                <td style="padding-bottom:.6rem">
                                    <select name="stage" style="width:100%;padding:.55rem .75rem;border:1px solid #d1d5db;border-radius:6px;font-size:.9rem;font-family:inherit">
                                        <option value="prospecting" <?= $stage === 'prospecting' ? 'selected' : '' ?>>Prospecting</option>
                                        <option value="proposal"    <?= $stage === 'proposal'    ? 'selected' : '' ?>>Proposal</option>
                                        <option value="negotiation" <?= $stage === 'negotiation' ? 'selected' : '' ?>>Negotiation</option>
                                        <option value="closed_won"  <?= $stage === 'closed_won'  ? 'selected' : '' ?>>Closed Won</option>
                                        <option value="closed_lost" <?= $stage === 'closed_lost' ? 'selected' : '' ?>>Closed Lost</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td id="lostReasonRow" style="<?= $stage === 'closed_lost' ? '' : 'display:none' ?>;padding-bottom:.6rem">
                                    <input type="text" name="lost_reason" value="<?= e($opp['lost_reason'] ?? '') ?>" placeholder="Lost reason (optional)" style="width:100%;padding:.55rem .75rem;border:1px solid #d1d5db;border-radius:6px;font-size:.9rem;font-family:inherit;box-sizing:border-box">
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <button type="submit" class="btn btn--primary btn--sm">Update Stage</button>
                                </td>
                            </tr>
                        </table>
                    </form>
                    <script>
                    document.querySelector('[name="stage"]').addEventListener('change', function() {
                        document.getElementById('lostReasonRow').style.display = this.value === 'closed_lost' ? '' : 'none';
                    });
                    </script>
                </div>
            </div>
            <?php endif; ?>
        </td>

        <!-- RIGHT: Quotes + Tasks -->
        <td style="width:62%">

            <!-- Quotes -->
            <div style="background:#fff;border:1px solid #d1d5db;border-radius:8px;overflow:hidden;margin-bottom:1.25rem">
                <table style="width:100%;border-collapse:collapse">
                    <tr style="background:#f8f9fb;border-bottom:1px solid #d1d5db">
                        <td style="padding:.75rem 1.25rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">Quotes</td>
                        <td style="padding:.75rem 1.25rem;text-align:right">
                            <a href="/quotes/create?opportunity_id=<?= (int)$opp['id'] ?><?= !empty($opp['customer_id']) ? '&customer_id=' . (int)$opp['customer_id'] : (!empty($opp['lead_id']) ? '&lead_id=' . (int)$opp['lead_id'] : '') ?>" class="btn btn--sm btn--primary">+ New Quote</a>
                        </td>
                    </tr>
                </table>
                <?php if (empty($quotes)): ?>
                <div style="padding:1.5rem 1.25rem;text-align:center;color:#9ca3af;font-size:.9rem">No quotes yet.</div>
                <?php else: ?>
                <table style="width:100%;border-collapse:collapse">
                    <thead>
                        <tr style="background:#f8f9fb;border-bottom:1px solid #d1d5db">
                            <th style="padding:.5rem 1.25rem;font-size:.7rem;font-weight:700;text-transform:uppercase;color:#6b7280;text-align:left">Quote #</th>
                            <th style="padding:.5rem 1.25rem;font-size:.7rem;font-weight:700;text-transform:uppercase;color:#6b7280;text-align:left">Date</th>
                            <th style="padding:.5rem 1.25rem;font-size:.7rem;font-weight:700;text-transform:uppercase;color:#6b7280;text-align:right">Amount</th>
                            <th style="padding:.5rem 1.25rem;font-size:.7rem;font-weight:700;text-transform:uppercase;color:#6b7280;text-align:left">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $qStatusColor = ['draft'=>'#6b7280','sent'=>'#0A3D91','accepted'=>'#16a34a','declined'=>'#dc2626','expired'=>'#9ca3af','converted'=>'#7c3aed'];
                    foreach ($quotes as $q): ?>
                    <tr style="border-bottom:1px solid #f3f4f6">
                        <td style="padding:.65rem 1.25rem"><a href="/quotes/<?= (int)$q['id'] ?>" style="color:#222b59;font-weight:600"><?= e($q['quote_number']) ?></a></td>
                        <td style="padding:.65rem 1.25rem;font-size:.85rem;color:#6b7280"><?= !empty($q['quote_date']) ? date('M j, Y', strtotime($q['quote_date'])) : '' ?></td>
                        <td style="padding:.65rem 1.25rem;font-size:.9rem;font-weight:600;text-align:right"><?= money((float)$q['total_amount']) ?></td>
                        <td style="padding:.65rem 1.25rem">
                            <span style="font-size:.75rem;font-weight:700;color:<?= $qStatusColor[$q['status']] ?? '#6b7280' ?>"><?= ucfirst($q['status']) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <!-- Tasks -->
            <div style="background:#fff;border:1px solid #d1d5db;border-radius:8px;overflow:hidden">
                <div style="padding:.75rem 1.25rem;background:#f8f9fb;border-bottom:1px solid #d1d5db;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">Tasks</div>

                <!-- Quick-add task form -->
                <div style="padding:.85rem 1.25rem;border-bottom:1px solid #e5e7eb;background:#fafafa">
                    <form method="post" action="/tasks">
                        <input type="hidden" name="csrf_token"      value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                        <input type="hidden" name="opportunity_id"  value="<?= (int)$opp['id'] ?>">
                        <?php if (!empty($opp['customer_id'])): ?>
                        <input type="hidden" name="customer_id" value="<?= (int)$opp['customer_id'] ?>">
                        <?php elseif (!empty($opp['lead_id'])): ?>
                        <input type="hidden" name="lead_id" value="<?= (int)$opp['lead_id'] ?>">
                        <?php endif; ?>
                        <input type="hidden" name="status"   value="open">
                        <input type="hidden" name="priority" value="medium">
                        <table style="width:100%;border-collapse:collapse">
                            <tr style="vertical-align:middle">
                                <td style="padding-right:.5rem">
                                    <input type="text" name="title" placeholder="Add a task..." required
                                        style="width:100%;padding:.55rem .75rem;border:1px solid #d1d5db;border-radius:6px;font-size:.9rem;font-family:inherit;box-sizing:border-box">
                                </td>
                                <td style="white-space:nowrap;padding-right:.5rem;width:115px">
                                    <input type="date" name="due_date"
                                        style="width:100%;padding:.55rem .75rem;border:1px solid #d1d5db;border-radius:6px;font-size:.85rem;font-family:inherit;box-sizing:border-box">
                                </td>
                                <td style="white-space:nowrap;padding-right:.5rem;width:130px">
                                    <select name="assigned_to" style="width:100%;padding:.55rem .75rem;border:1px solid #d1d5db;border-radius:6px;font-size:.85rem;font-family:inherit">
                                        <option value="">Unassigned</option>
                                        <?php foreach ($users as $u): ?>
                                        <option value="<?= (int)$u['id'] ?>"><?= e($u['first_name'] . ' ' . $u['last_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td style="white-space:nowrap">
                                    <button type="submit" class="btn btn--sm btn--primary">Add</button>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>

                <!-- Open tasks -->
                <?php if (empty($openTasks) && empty($doneTasks)): ?>
                <div style="padding:1.5rem 1.25rem;text-align:center;color:#9ca3af;font-size:.9rem">No tasks yet.</div>
                <?php endif; ?>

                <?php foreach ($openTasks as $task): ?>
                <div style="padding:.7rem 1.25rem;border-bottom:1px solid #f3f4f6">
                    <table style="width:100%;border-collapse:collapse">
                        <tr style="vertical-align:middle">
                            <td style="width:28px">
                                <form method="post" action="/tasks/<?= (int)$task['id'] ?>/status" style="margin:0">
                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                                    <input type="hidden" name="status"   value="completed">
                                    <input type="hidden" name="redirect" value="/opportunities/<?= (int)$opp['id'] ?>">
                                    <button type="submit" title="Mark complete" style="background:none;border:2px solid #d1d5db;border-radius:50%;width:20px;height:20px;cursor:pointer;padding:0;vertical-align:middle"></button>
                                </form>
                            </td>
                            <td style="padding-left:.5rem">
                                <div style="font-size:.9rem;font-weight:600;color:#111"><?= e($task['title']) ?></div>
                                <?php if (!empty($task['description'])): ?>
                                <div style="font-size:.8rem;color:#6b7280;margin-top:.15rem"><?= e($task['description']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;white-space:nowrap;padding-left:.75rem">
                                <?php if (!empty($task['due_date'])): ?>
                                <?php
                                $dueTs   = strtotime($task['due_date']);
                                $todayTs = strtotime(date('Y-m-d'));
                                $overdue = $dueTs < $todayTs;
                                ?>
                                <span style="font-size:.78rem;color:<?= $overdue ? '#dc2626' : '#6b7280' ?>;font-weight:<?= $overdue ? '700' : '400' ?>">
                                    <?= date('M j', $dueTs) ?>
                                </span>
                                <?php endif; ?>
                                <?php if (!empty($task['assigned_first'])): ?>
                                <span style="font-size:.78rem;color:#6b7280;margin-left:.5rem"><?= e($task['assigned_first'] . ' ' . $task['assigned_last']) ?></span>
                                <?php endif; ?>
                                <span style="font-size:.72rem;font-weight:700;margin-left:.5rem;color:<?= $priorityColor[$task['priority']] ?? '#6b7280' ?>"><?= $priorityLabel[$task['priority']] ?? '' ?></span>
                            </td>
                        </tr>
                    </table>
                </div>
                <?php endforeach; ?>

                <!-- Completed tasks (collapsed) -->
                <?php if (!empty($doneTasks)): ?>
                <div style="padding:.5rem 1.25rem;background:#f8f9fb;border-top:1px solid #e5e7eb">
                    <button type="button" id="toggleDone" style="background:none;border:none;cursor:pointer;font-size:.8rem;color:#6b7280;padding:0">
                        Show <?= count($doneTasks) ?> completed task<?= count($doneTasks) !== 1 ? 's' : '' ?>
                    </button>
                </div>
                <div id="doneTasks" style="display:none">
                    <?php foreach ($doneTasks as $task): ?>
                    <div style="padding:.6rem 1.25rem;border-bottom:1px solid #f3f4f6;opacity:.6">
                        <table style="width:100%;border-collapse:collapse">
                            <tr style="vertical-align:middle">
                                <td style="width:28px">
                                    <span style="display:inline-block;width:20px;height:20px;border-radius:50%;background:#16a34a;vertical-align:middle">
                                        <svg viewBox="0 0 20 20" fill="white" width="12" height="12" style="margin:4px"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414L8.414 15 3.293 9.879a1 1 0 111.414-1.414L8.414 12.172l6.879-6.879a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    </span>
                                </td>
                                <td style="padding-left:.5rem;font-size:.85rem;color:#6b7280;text-decoration:line-through"><?= e($task['title']) ?></td>
                                <td style="text-align:right;font-size:.78rem;color:#9ca3af">
                                    <?= !empty($task['completed_at']) ? date('M j', strtotime($task['completed_at'])) : '' ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <?php endforeach; ?>
                </div>
                <script>
                document.getElementById('toggleDone').addEventListener('click', function() {
                    var el = document.getElementById('doneTasks');
                    if (el.style.display === 'none') {
                        el.style.display = '';
                        this.textContent = 'Hide completed tasks';
                    } else {
                        el.style.display = 'none';
                        this.textContent = 'Show <?= count($doneTasks) ?> completed task<?= count($doneTasks) !== 1 ? 's' : '' ?>';
                    }
                });
                </script>
                <?php endif; ?>

            </div>
        </td>

    </tr>
</table>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
