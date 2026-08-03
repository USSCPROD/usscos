<?php ob_start();

$q = $quote;

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
$isExpired = $q['status'] === 'sent' && !empty($q['expiry_date']) && $q['expiry_date'] < date('Y-m-d');
$canConvert = in_array($q['status'], ['accepted', 'sent']) && empty($q['sales_order_id']);
$showEmail  = in_array($q['status'], ['draft', 'sent']);
?>

<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:.75rem">
    <div>
        <a href="/quotes" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Quotes
        </a>
        <div style="display:flex;align-items:center;gap:.75rem;margin-top:.25rem;flex-wrap:wrap">
            <h1 class="page-title" style="margin:0"><?= e($q['quote_number']) ?></h1>
            <span class="badge <?= $statusBadge[$q['status']] ?? 'badge--secondary' ?>" style="font-size:.85rem;padding:.3rem .75rem">
                <?= $statusLabel[$q['status']] ?? e($q['status']) ?>
                <?php if ($isExpired): ?> <span style="font-size:.75em;opacity:.8">(Expired)</span><?php endif; ?>
            </span>
            <?php if (!empty($q['sales_order_id'])): ?>
                <a href="/sales-orders/<?= (int)$q['sales_order_id'] ?>" class="badge badge--success" style="font-size:.85rem;padding:.3rem .75rem;text-decoration:none">
                    SO #<?= e($q['so_number'] ?? $q['sales_order_id']) ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
        <?php if (!in_array($q['status'], ['accepted', 'converted'])): ?>
        <a href="/quotes/<?= (int)$q['id'] ?>/edit" class="btn btn--secondary">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Edit
        </a>
        <?php endif; ?>
        <?php if ($showEmail): ?>
        <button type="button" class="btn btn--secondary" onclick="document.getElementById('emailModal').style.display='flex'">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            Email
        </button>
        <?php endif; ?>

        <!-- Status buttons -->
        <?php if ($q['status'] === 'draft'): ?>
            <form method="post" action="/quotes/<?= (int)$q['id'] ?>/status" style="display:inline">
                <input type="hidden" name="status" value="sent">
                <button type="submit" class="btn btn--secondary">Mark Sent</button>
            </form>
        <?php endif; ?>
        <?php if (in_array($q['status'], ['draft','sent'])): ?>
            <form method="post" action="/quotes/<?= (int)$q['id'] ?>/status" style="display:inline">
                <input type="hidden" name="status" value="accepted">
                <button type="submit" class="btn btn--secondary">Mark Accepted</button>
            </form>
            <form method="post" action="/quotes/<?= (int)$q['id'] ?>/status" style="display:inline">
                <input type="hidden" name="status" value="declined">
                <button type="submit" class="btn btn--secondary">Mark Declined</button>
            </form>
        <?php endif; ?>
        <?php if ($canConvert): ?>
            <form method="post" action="/quotes/<?= (int)$q['id'] ?>/convert" style="display:inline" onsubmit="return confirm('Convert this quote to a Sales Order?')">
                <button type="submit" class="btn btn--primary">Convert to SO</button>
            </form>
        <?php endif; ?>
        <?php if (!empty($q['sales_order_id'])): ?>
            <a href="/sales-orders/<?= (int)$q['sales_order_id'] ?>" class="btn btn--primary">View Sales Order</a>
        <?php endif; ?>
    </div>
</div>

<?php if ($flash = \App\Core\Session::getFlash('success')): ?>
    <div class="alert alert--success" style="margin-bottom:1rem"><?= e($flash) ?></div>
<?php endif; ?>
<?php if ($flash = \App\Core\Session::getFlash('error')): ?>
    <div class="alert alert--danger" style="margin-bottom:1rem"><?= e($flash) ?></div>
<?php endif; ?>

<!-- Main document card -->
<div style="background:#fff;border:1px solid #d1d5db;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.08);overflow:hidden;font-family:inherit;margin-bottom:1.5rem">

    <!-- Document header -->
    <table style="width:100%;border-collapse:collapse;border-bottom:2px solid #d1d5db;table-layout:fixed">
        <colgroup><col style="width:220px"><col style="width:180px"><col><col></colgroup>
        <tr>
            <td style="padding:1.25rem 1.5rem;vertical-align:top;border-right:1px solid #d1d5db">
                <div style="font-size:1.75rem;font-weight:700;color:#111;letter-spacing:-.02em;margin-bottom:1.25rem">Quote</div>
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.35rem">Quote No.</div>
                <div style="font-weight:700;font-size:1.05rem;color:#222b59"><?= e($q['quote_number']) ?></div>
            </td>
            <td style="padding:1.25rem;vertical-align:top;border-right:1px solid #d1d5db">
                <div style="margin-bottom:.85rem">
                    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.2rem">Quote Date</div>
                    <div><?= date('M j, Y', strtotime($q['quote_date'])) ?></div>
                </div>
                <div>
                    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.2rem">Expiry Date</div>
                    <div style="color:<?= $isExpired ? '#dc2626' : 'inherit' ?>;font-weight:<?= $isExpired ? '700' : 'normal' ?>">
                        <?= !empty($q['expiry_date']) ? date('M j, Y', strtotime($q['expiry_date'])) : '—' ?>
                        <?php if ($isExpired): ?><span style="font-size:.8rem"> (Past Due)</span><?php endif; ?>
                    </div>
                </div>
            </td>
            <td style="padding:1.25rem;vertical-align:top;border-right:1px solid #d1d5db">
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.5rem">Bill To</div>
                <div style="font-size:.925rem;line-height:1.8">
                    <a href="/customers/<?= (int)$q['customer_id'] ?>" class="link" style="font-weight:700"><?= e($q['company_name']) ?></a>
                    <?php if (!empty($q['bill_address_1'])): ?>
                        <br><?= e($q['bill_address_1']) ?>
                        <?php if (!empty($q['bill_address_2'])): ?><br><?= e($q['bill_address_2']) ?><?php endif; ?>
                        <?php if (!empty($q['bill_city'])): ?>
                            <br><?= e($q['bill_city']) ?><?= !empty($q['bill_state']) ? ', ' . e($q['bill_state']) : '' ?> <?= e($q['bill_zip'] ?? '') ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </td>
            <td style="padding:1.25rem;vertical-align:top">
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.5rem">Ship To</div>
                <?php if (!empty($q['ship_address_1'])): ?>
                <div style="font-size:.925rem;line-height:1.8">
                    <?php if (!empty($q['ship_name'])): ?><strong><?= e($q['ship_name']) ?></strong><br><?php endif; ?>
                    <?= e($q['ship_address_1']) ?>
                    <?php if (!empty($q['ship_address_2'])): ?><br><?= e($q['ship_address_2']) ?><?php endif; ?>
                    <?php if (!empty($q['ship_city'])): ?>
                        <br><?= e($q['ship_city']) ?><?= !empty($q['ship_state']) ? ', ' . e($q['ship_state']) : '' ?> <?= e($q['ship_zip'] ?? '') ?>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                    <div style="font-size:.9rem;color:#6b7280;font-style:italic">Same as billing</div>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <!-- Meta row -->
    <table style="width:100%;border-collapse:collapse;border-bottom:1px solid #d1d5db;background:#f8f9fb;table-layout:fixed">
        <tr>
            <td style="padding:.85rem 1.25rem;border-right:1px solid #d1d5db;vertical-align:top;width:20%">
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.3rem">P.O. No.</div>
                <div style="font-size:.925rem"><?= !empty($q['po_number']) ? e($q['po_number']) : '—' ?></div>
            </td>
            <td style="padding:.85rem 1.25rem;border-right:1px solid #d1d5db;vertical-align:top;width:20%">
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.3rem">Terms</div>
                <div style="font-size:.925rem"><?= !empty($q['payment_term_name']) ? e($q['payment_term_name']) : '—' ?></div>
            </td>
            <td style="padding:.85rem 1.25rem;border-right:1px solid #d1d5db;vertical-align:top;width:20%">
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.3rem">Rep</div>
                <div style="font-size:.925rem">
                    <?= !empty($q['rep_first']) ? e($q['rep_first'] . ' ' . $q['rep_last']) : '—' ?>
                </div>
            </td>
            <td style="padding:.85rem 1.25rem;border-right:1px solid #d1d5db;vertical-align:top;width:20%">
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.3rem">Prepared By</div>
                <div style="font-size:.925rem">
                    <?= !empty($q['created_first']) ? e($q['created_first'] . ' ' . $q['created_last']) : '—' ?>
                </div>
            </td>
            <td style="padding:.85rem 1.25rem;vertical-align:top;width:20%">
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.3rem">Ship Via</div>
                <div style="font-size:.925rem"><?= !empty($q['ship_via_name']) ? e($q['ship_via_name']) : '—' ?></div>
            </td>
        </tr>
    </table>

    <!-- Opportunity / Lead row -->
    <table style="width:100%;border-collapse:collapse;border-bottom:2px solid #d1d5db;background:#f8f9fb;table-layout:fixed">
        <tr>
            <!-- Opportunity cell -->
            <td style="padding:.75rem 1.25rem;border-right:1px solid #d1d5db;vertical-align:middle;width:40%">
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.35rem">Opportunity</div>
                <?php if (!empty($q['opportunity_name'])): ?>
                    <div style="font-size:.925rem;font-weight:600"><?= e($q['opportunity_name']) ?></div>
                    <?php if (!empty($q['opportunity_stage'])): ?>
                        <div style="margin-top:.2rem"><span class="badge badge--info" style="font-size:.75rem"><?= ucfirst(str_replace('_',' ',$q['opportunity_stage'])) ?></span></div>
                    <?php endif; ?>
                    <div style="margin-top:.4rem">
                        <a href="#" onclick="document.getElementById('opp-link-form').style.display='block';this.style.display='none';return false" style="font-size:.8rem;color:#6b7280;text-decoration:underline">Change</a>
                    </div>
                <?php else: ?>
                    <div style="color:#9ca3af;font-size:.875rem;font-style:italic;margin-bottom:.4rem">No opportunity linked</div>
                <?php endif; ?>
                <!-- Inline link form -->
                <div id="opp-link-form" style="display:<?= empty($q['opportunity_name']) ? 'block' : 'none' ?>;margin-top:.35rem">
                    <form method="POST" action="/quotes/<?= (int)$q['id'] ?>/link-opportunity" style="display:flex;gap:.5rem;align-items:center">
                        <select name="opportunity_id" id="oppLinkSelect" style="flex:1;padding:.35rem .5rem;border:1px solid #d1d5db;border-radius:6px;font-size:.875rem">
                            <option value="">— None —</option>
                        </select>
                        <button type="submit" class="btn btn--xs btn--primary">Save</button>
                        <?php if (!empty($q['opportunity_name'])): ?>
                        <a href="#" onclick="document.getElementById('opp-link-form').style.display='none';this.closest('td').querySelector('a[onclick]').style.display='';return false" style="font-size:.8rem;color:#6b7280">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </td>
            <!-- Lead cell -->
            <td style="padding:.75rem 1.25rem;vertical-align:middle;width:35%">
                <?php if (!empty($q['lead_id'])): ?>
                    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.35rem">Lead</div>
                    <div style="font-size:.925rem">
                        <a href="/leads/<?= (int)$q['lead_id'] ?>" class="link"><?= e($q['company_name']) ?></a>
                        <span style="font-size:.8rem;color:#d97706;background:#fffbeb;border:1px solid #fde68a;border-radius:4px;padding:.1rem .4rem;margin-left:.4rem">Lead</span>
                    </div>
                <?php elseif (!empty($q['customer_id'])): ?>
                    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.35rem">Customer</div>
                    <div style="font-size:.925rem">
                        <a href="/customers/<?= (int)$q['customer_id'] ?>" class="link"><?= e($q['company_name']) ?></a>
                    </div>
                <?php endif; ?>
            </td>
            <td></td>
        </tr>
    </table>
    <script>
    (function(){
        var sel = document.getElementById('oppLinkSelect');
        if (!sel) return;
        <?php if (!empty($q['lead_id'])): ?>
        fetch('/opportunities?lead_id=<?= (int)$q['lead_id'] ?>&json=1')
            .then(function(r){return r.json();}).then(function(opps){
                (opps||[]).forEach(function(o){
                    var opt = document.createElement('option');
                    opt.value = o.id;
                    opt.textContent = o.name;
                    <?php if (!empty($q['opportunity_id'])): ?>
                    if (o.id == <?= (int)$q['opportunity_id'] ?>) opt.selected = true;
                    <?php endif; ?>
                    sel.appendChild(opt);
                });
            });
        <?php elseif (!empty($q['customer_id'])): ?>
        fetch('/customers/<?= (int)$q['customer_id'] ?>/opportunities')
            .then(function(r){return r.json();}).then(function(opps){
                (opps||[]).forEach(function(o){
                    var opt = document.createElement('option');
                    opt.value = o.id;
                    opt.textContent = o.name;
                    <?php if (!empty($q['opportunity_id'])): ?>
                    if (o.id == <?= (int)$q['opportunity_id'] ?>) opt.selected = true;
                    <?php endif; ?>
                    sel.appendChild(opt);
                });
            });
        <?php endif; ?>
    })();
    </script>

    <!-- Line items -->
    <div style="overflow-x:auto;border-bottom:2px solid #d1d5db">
        <table style="width:100%;border-collapse:collapse;font-size:.925rem">
            <colgroup>
                <col style="width:130px"><col><col style="width:90px"><col style="width:60px">
                <col style="width:120px"><col style="width:70px"><col style="width:48px"><col style="width:120px">
            </colgroup>
            <thead>
                <tr style="background:#f8f9fb;border-bottom:1px solid #d1d5db">
                    <th style="padding:.6rem .75rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;text-align:left">Item</th>
                    <th style="padding:.6rem .75rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;text-align:left">Description</th>
                    <th style="padding:.6rem .75rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;text-align:right">Quantity</th>
                    <th style="padding:.6rem .75rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;text-align:left">U/M</th>
                    <th style="padding:.6rem .75rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;text-align:right">Price Each</th>
                    <th style="padding:.6rem .75rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;text-align:right">Disc %</th>
                    <th style="padding:.6rem .75rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;text-align:center">Tax</th>
                    <th style="padding:.6rem .75rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;text-align:right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($line_items as $li): ?>
                <tr style="border-bottom:1px solid #f3f4f6">
                    <td style="padding:.65rem .75rem;font-family:monospace;font-size:.875rem;color:#0A3D91;font-weight:600"><?= e($li['quickbooks_item'] ?? $li['sku'] ?? '') ?></td>
                    <td style="padding:.65rem .75rem;color:#374151"><?= e($li['product_name'] ?? $li['description'] ?? '') ?></td>
                    <td style="padding:.65rem .75rem;text-align:right;font-family:monospace"><?= number_format((float)$li['qty'], 2) ?></td>
                    <td style="padding:.65rem .75rem;color:#6b7280;font-size:.875rem"><?= e($li['uom_name'] ?? $li['uom'] ?? '') ?></td>
                    <td style="padding:.65rem .75rem;text-align:right;font-family:monospace"><?= money((float)$li['unit_price']) ?></td>
                    <td style="padding:.65rem .75rem;text-align:right;color:#6b7280;font-size:.875rem">
                        <?= (float)($li['discount_pct'] ?? 0) > 0 ? number_format((float)$li['discount_pct'], 1) . '%' : '—' ?>
                    </td>
                    <td style="padding:.65rem .75rem;text-align:center">
                        <?php if ($li['is_taxable']): ?>
                            <svg width="14" height="14" viewBox="0 0 20 20" fill="#16a34a"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        <?php else: ?>
                            <span style="color:#9ca3af">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:.65rem .75rem;text-align:right;font-family:monospace;font-weight:600"><?= money((float)$li['line_total']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($line_items)): ?>
                <tr><td colspan="8" style="padding:1.5rem;text-align:center;color:#9ca3af">No line items</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="border-top:1px solid #e5e7eb">
                    <td colspan="7" style="padding:.6rem .75rem;text-align:right;color:#6b7280;font-size:.9rem">Subtotal</td>
                    <td style="padding:.6rem .75rem;text-align:right;font-family:monospace;font-weight:600"><?= money((float)$q['subtotal']) ?></td>
                </tr>
                <?php if ((float)($q['discount_amount'] ?? 0) > 0): ?>
                <tr>
                    <td colspan="7" style="padding:.4rem .75rem;text-align:right;color:#6b7280;font-size:.9rem">Discount</td>
                    <td style="padding:.4rem .75rem;text-align:right;font-family:monospace;color:#ef4444">−<?= money((float)$q['discount_amount']) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ((float)($q['tax_amount'] ?? 0) > 0): ?>
                <tr>
                    <td colspan="7" style="padding:.6rem .75rem;text-align:right;color:#6b7280;font-size:.9rem">
                        Tax<?= !empty($q['tax_rate_name']) ? ' (' . e($q['tax_rate_name']) . ')' : '' ?>
                    </td>
                    <td style="padding:.6rem .75rem;text-align:right;font-family:monospace;font-weight:600"><?= money((float)$q['tax_amount']) ?></td>
                </tr>
                <?php endif; ?>
                <tr style="border-top:2px solid #d1d5db">
                    <td colspan="7" style="padding:.85rem .75rem;text-align:right;font-weight:700;font-size:1.05rem">Total</td>
                    <td style="padding:.85rem .75rem;text-align:right;font-family:monospace;font-weight:700;font-size:1.15rem;color:#222b59"><?= money((float)$q['total_amount']) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Memo / Notes -->
    <?php if (!empty($q['memo']) || !empty($q['internal_notes'])): ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;padding:1.25rem 1.5rem">
        <?php if (!empty($q['memo'])): ?>
        <div>
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.5rem">Memo</div>
            <div style="font-size:.9rem;color:#374151;white-space:pre-line"><?= e($q['memo']) ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($q['internal_notes'])): ?>
        <div>
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.5rem">Internal Notes</div>
            <div style="font-size:.9rem;color:#374151;background:#fffbeb;border:1px solid #fde68a;border-radius:6px;padding:.75rem;white-space:pre-line"><?= e($q['internal_notes']) ?></div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>

<!-- Email Modal -->
<div id="emailModal" style="display:none;position:fixed;inset:0;z-index:10000;background:rgba(0,0,0,.5);align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:10px;box-shadow:0 20px 60px rgba(0,0,0,.3);width:100%;max-width:540px;overflow:hidden">
        <div style="padding:1.25rem 1.5rem;background:#222b59;display:flex;align-items:center;justify-content:space-between">
            <h3 style="margin:0;color:#fff;font-size:1rem">Email Quote <?= e($q['quote_number']) ?></h3>
            <button type="button" onclick="document.getElementById('emailModal').style.display='none'" style="background:none;border:none;color:#fff;font-size:1.5rem;cursor:pointer;line-height:1;padding:0">&times;</button>
        </div>
        <form method="post" action="/quotes/<?= (int)$q['id'] ?>/email">
            <div style="padding:1.5rem;display:flex;flex-direction:column;gap:1rem">
                <div>
                    <label style="display:block;font-size:.8rem;font-weight:700;color:#374151;margin-bottom:.4rem">To (Customer Email)</label>
                    <input type="email" name="email_to" required value="<?= e($q['customer_email'] ?? '') ?>"
                        style="width:100%;padding:.65rem .85rem;border:1px solid #d1d5db;border-radius:6px;font-size:.95rem;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:.8rem;font-weight:700;color:#374151;margin-bottom:.4rem">From (Reply-To)</label>
                    <input type="email" name="email_from" required value="<?= e(\App\Core\Auth::user()['email'] ?? '') ?>"
                        style="width:100%;padding:.65rem .85rem;border:1px solid #d1d5db;border-radius:6px;font-size:.95rem;box-sizing:border-box">
                </div>
                <div>
                    <label style="display:block;font-size:.8rem;font-weight:700;color:#374151;margin-bottom:.4rem">Note to Customer (optional)</label>
                    <textarea name="email_note" rows="4"
                        placeholder="Add a personal message or instructions…"
                        style="width:100%;padding:.65rem .85rem;border:1px solid #d1d5db;border-radius:6px;font-size:.95rem;font-family:inherit;resize:vertical;box-sizing:border-box"></textarea>
                </div>
            </div>
            <div style="padding:1rem 1.5rem;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:.75rem;background:#f8f9fb">
                <button type="button" onclick="document.getElementById('emailModal').style.display='none'" class="btn btn--secondary">Cancel</button>
                <button type="submit" class="btn btn--primary">Send Email</button>
            </div>
        </form>
    </div>
</div>

<!-- Status change modal backdrop close -->
<script>
document.getElementById('emailModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
