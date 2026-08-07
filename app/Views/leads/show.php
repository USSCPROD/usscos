<?php ob_start();
$statusBadge = ['new' => 'badge--info', 'contacted' => 'badge--warning', 'qualified' => 'badge--success', 'converted' => 'badge--success', 'dead' => 'badge--neutral'];
$sourceLabel = ['web' => 'Web', 'referral' => 'Referral', 'trade_show' => 'Trade Show', 'cold_call' => 'Cold Call', 'social' => 'Social Media', 'email_campaign' => 'Email Campaign', 'other' => 'Other'];
$stageBadge  = ['prospecting' => 'badge--neutral', 'proposal' => 'badge--info', 'negotiation' => 'badge--warning', 'closed_won' => 'badge--success', 'closed_lost' => 'badge--neutral'];
$stageLabel  = ['prospecting' => 'Prospecting', 'proposal' => 'Proposal', 'negotiation' => 'Negotiation', 'closed_won' => 'Closed Won', 'closed_lost' => 'Closed Lost'];
?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/leads" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Leads
        </a>
        <div style="display:flex;align-items:center;gap:.75rem;margin-top:.25rem">
            <h1 class="page-title"><?= e($lead['company_name']) ?></h1>
            <span class="badge <?= $statusBadge[$lead['status']] ?? 'badge--neutral' ?>"><?= ucfirst($lead['status']) ?></span>
        </div>
        <?php if ($lead['first_name'] || $lead['last_name']): ?>
            <p class="page-subtitle"><?= e(trim(($lead['first_name'] ?? '') . ' ' . ($lead['last_name'] ?? ''))) ?></p>
        <?php endif; ?>
    </div>
    <div class="page-header__right">
        <?php if ($lead['status'] !== 'converted' && $lead['status'] !== 'dead'): ?>
            <a href="/opportunities/create?lead_id=<?= (int)$lead['id'] ?>" class="btn btn--secondary">+ Opportunity</a>
        <?php endif; ?>
        <?php if ($lead['status'] !== 'converted'): ?>
            <form method="POST" action="/leads/<?= (int)$lead['id'] ?>/convert" style="display:inline" onsubmit="return confirm('Convert this lead to a customer?')">
            <?= csrf_field() ?>
                <button type="submit" class="btn btn--success">Convert to Customer</button>
            </form>
        <?php else: ?>
            <a href="/customers/<?= (int)$lead['customer_id'] ?>" class="btn btn--secondary">View Customer</a>
        <?php endif; ?>
        <a href="/leads/<?= (int)$lead['id'] ?>/edit" class="btn btn--secondary">Edit</a>
    </div>
</div>

<div class="detail-layout">
    <!-- Left: Lead Info -->
    <div class="detail-layout__side">
        <div class="card" style="padding:1.25rem">
            <h3 class="card__section-title">Contact Info</h3>
            <dl class="detail-list">
                <?php if ($lead['email']): ?>
                    <dt>Email</dt><dd><a href="mailto:<?= e($lead['email']) ?>"><?= e($lead['email']) ?></a></dd>
                <?php endif; ?>
                <?php if ($lead['phone']): ?>
                    <dt>Phone</dt><dd><a href="tel:<?= e($lead['phone']) ?>"><?= e($lead['phone']) ?></a></dd>
                <?php endif; ?>
                <?php if ($lead['website']): ?>
                    <dt>Website</dt><dd><a href="<?= e($lead['website']) ?>" target="_blank"><?= e($lead['website']) ?></a></dd>
                <?php endif; ?>
                <dt>Source</dt><dd><?= $sourceLabel[$lead['source']] ?? e($lead['source']) ?></dd>
                <dt>Rep</dt><dd><?= $lead['rep_first'] ? e($lead['rep_first'] . ' ' . $lead['rep_last']) : '<span class="text-muted">Unassigned</span>' ?></dd>
                <dt>Created</dt><dd><?= date('M j, Y', strtotime($lead['created_at'])) ?></dd>
            </dl>

            <?php if ($lead['notes']): ?>
                <h3 class="card__section-title" style="margin-top:1.25rem">Notes</h3>
                <p style="font-size:.9rem;color:#374151;line-height:1.6;white-space:pre-wrap"><?= e($lead['notes']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right: Opportunities + Quotes -->
    <div class="detail-layout__main" style="display:flex;flex-direction:column;gap:1rem">
        <div class="card" style="padding:0;overflow:hidden">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;border-bottom:1px solid #e5e7eb">
                <h3 style="margin:0;font-size:.95rem;font-weight:700;color:#111">Opportunities</h3>
                <a href="/opportunities/create?lead_id=<?= (int)$lead['id'] ?>" class="btn btn--xs btn--primary">+ Add</a>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th class="text-center">Stage</th>
                            <th class="text-right">Value</th>
                            <th class="text-center">Probability</th>
                            <th>Close Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($opps)): ?>
                            <tr><td colspan="6" class="table__empty">No opportunities yet.</td></tr>
                        <?php else: ?>
                            <?php
                            $qBadgeOpp = ['draft'=>'badge--secondary','sent'=>'badge--info','accepted'=>'badge--success','declined'=>'badge--danger','expired'=>'badge--warning'];
                            foreach ($opps as $opp): ?>
                                <tr style="border-bottom:<?= empty($opp['quotes']) ? '1px solid #f3f4f6' : 'none' ?>">
                                    <td style="font-weight:600"><a href="/opportunities/<?= (int)$opp['id'] ?>" style="color:#222b59"><?= e($opp['name']) ?></a></td>
                                    <td class="text-center">
                                        <span class="badge <?= $stageBadge[$opp['stage']] ?? 'badge--neutral' ?>"><?= $stageLabel[$opp['stage']] ?? e($opp['stage']) ?></span>
                                    </td>
                                    <td class="text-right font-mono">$<?= number_format((float)$opp['expected_value'], 0) ?></td>
                                    <td class="text-center text-sm text-muted"><?= (int)$opp['probability'] ?>%</td>
                                    <td class="text-sm text-muted"><?= $opp['expected_close'] ? date('M j, Y', strtotime($opp['expected_close'])) : '—' ?></td>
                                    <td class="text-right">
                                        <?php if ($lead['customer_id']): ?>
                                            <a href="/quotes/create?customer_id=<?= (int)$lead['customer_id'] ?>&opportunity_id=<?= (int)$opp['id'] ?>" class="btn btn--xs btn--secondary" style="margin-right:.25rem">+ Quote</a>
                                        <?php else: ?>
                                            <a href="/quotes/create?lead_id=<?= (int)$lead['id'] ?>&opportunity_id=<?= (int)$opp['id'] ?>" class="btn btn--xs btn--secondary" style="margin-right:.25rem">+ Quote</a>
                                        <?php endif; ?>
                                        <a href="/opportunities/<?= (int)$opp['id'] ?>" class="btn btn--xs btn--secondary">View</a>
                                    </td>
                                </tr>
                                <?php foreach ($opp['quotes'] ?? [] as $qt): ?>
                                <tr style="background:#f8f9fb;border-bottom:1px solid #f3f4f6">
                                    <td style="padding:.4rem .75rem .4rem 2rem;font-size:.85rem;color:#374151" colspan="2">
                                        <a href="/quotes/<?= (int)$qt['id'] ?>" class="link" style="font-family:monospace;font-weight:600"><?= e($qt['quote_number']) ?></a>
                                        — <?= date('M j, Y', strtotime($qt['quote_date'])) ?>
                                    </td>
                                    <td class="text-right font-mono" style="font-size:.85rem;padding:.4rem .75rem">$<?= number_format((float)$qt['total_amount'], 0) ?></td>
                                    <td colspan="3" style="padding:.4rem .75rem">
                                        <span class="badge <?= $qBadgeOpp[$qt['status']] ?? 'badge--secondary' ?>" style="font-size:.75rem"><?= ucfirst($qt['status']) ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quotes -->
        <div class="card" style="padding:0;overflow:hidden">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;border-bottom:1px solid #e5e7eb">
                <h3 style="margin:0;font-size:.95rem;font-weight:700;color:#111">Quotes</h3>
                <?php if ($lead['customer_id']): ?>
                    <a href="/quotes/create?customer_id=<?= (int)$lead['customer_id'] ?>" class="btn btn--xs btn--primary">+ New Quote</a>
                <?php else: ?>
                    <a href="/quotes/create?lead_id=<?= (int)$lead['id'] ?>" class="btn btn--xs btn--primary">+ New Quote</a>
                <?php endif; ?>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Quote #</th><th>Date</th><th>Expires</th>
                            <th class="text-right">Total</th><th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($quotes)): ?>
                            <tr><td colspan="5" class="table__empty">No quotes yet.</td></tr>
                        <?php else: ?>
                            <?php
                            $qBadge = ['draft'=>'badge--secondary','sent'=>'badge--info','accepted'=>'badge--success','declined'=>'badge--danger','expired'=>'badge--warning'];
                            $qLabel = ['draft'=>'Draft','sent'=>'Sent','accepted'=>'Accepted','declined'=>'Declined','expired'=>'Expired'];
                            foreach ($quotes as $qt): ?>
                                <tr class="table__row--clickable" onclick="window.location='/quotes/<?= (int)$qt['id'] ?>'" style="cursor:pointer">
                                    <td class="font-mono"><a href="/quotes/<?= (int)$qt['id'] ?>" class="link" style="font-weight:600"><?= e($qt['quote_number']) ?></a></td>
                                    <td><?= date('M j, Y', strtotime($qt['quote_date'])) ?></td>
                                    <td class="text-sm text-muted"><?= !empty($qt['expiry_date']) ? date('M j, Y', strtotime($qt['expiry_date'])) : '—' ?></td>
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

        <!-- Tasks -->
        <div class="card" style="padding:0;overflow:hidden">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;border-bottom:1px solid #e5e7eb">
                <h3 style="margin:0;font-size:.95rem;font-weight:700;color:#111">
                    Tasks
                    <?php $openTasks = array_filter($tasks ?? [], fn($t) => !in_array($t['status'],['completed','cancelled'])); ?>
                    <?php if (count($openTasks)): ?>
                        <span style="background:#ef4444;color:white;border-radius:10px;padding:.1rem .45rem;font-size:.7rem;margin-left:.4rem"><?= count($openTasks) ?></span>
                    <?php endif; ?>
                </h3>
                <a href="/tasks/create?lead_id=<?= (int)$lead['id'] ?>" class="btn btn--xs btn--primary">+ Add Task</a>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width:36px"></th>
                            <th>Task</th><th>Assigned</th><th>Due</th><th>Priority</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($tasks)): ?>
                        <tr><td colspan="6" class="table__empty">No tasks yet. <a href="/tasks/create?lead_id=<?= (int)$lead['id'] ?>" class="link">Add one</a></td></tr>
                    <?php else:
                        $tPriBadge = ['low'=>'badge--secondary','medium'=>'badge--info','high'=>'badge--warning','urgent'=>'badge--danger'];
                        $tStsBadge = ['open'=>'badge--secondary','in_progress'=>'badge--info','completed'=>'badge--success','cancelled'=>'badge--secondary'];
                        foreach ($tasks as $t):
                            $isOverdue = !empty($t['due_date']) && $t['due_date'] < date('Y-m-d') && !in_array($t['status'],['completed','cancelled']);
                    ?>
                        <tr class="table__row--clickable" onclick="window.location='/tasks/<?= (int)$t['id'] ?>/edit'" style="cursor:pointer">
                            <td onclick="event.stopPropagation()">
                                <form method="POST" action="/tasks/<?= (int)$t['id'] ?>/status">
                                <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="<?= $t['status']==='completed' ? 'open' : 'completed' ?>">
                                    <input type="hidden" name="redirect" value="/leads/<?= (int)$lead['id'] ?>">
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

    </div>
</div>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
