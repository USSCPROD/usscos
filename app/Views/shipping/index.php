<?php ob_start();

$statusLabel = [
    'confirmed'         => 'Confirmed',
    'processing'        => 'Processing',
    'partially_shipped' => 'Partial',
    'paid'              => 'Paid',
];
$statusBadge = [
    'confirmed'         => 'badge--info',
    'processing'        => 'badge--info',
    'partially_shipped' => 'badge--warning',
    'paid'              => 'badge--success',
];

$today    = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// One tab per BENCH that has work. Parcel and freight are different people, and a
// tab each for FedEx and FedEx Freight put one of them inside the other's list.
$benchNames = [
    'parcel'   => 'Parcel (FedEx, UPS)',
    'freight'  => 'Freight',
    'delivery' => 'Our truck',
    'pickup'   => 'CPU (customer pick-up)',
    'other'    => 'Other',
];

// Parcel, freight and CPU are the three benches USSC actually works, so they are
// shown whether or not there is anything on them today. An empty CPU tab says "no
// collections waiting", which is information; a missing one says the system does not
// do collections, which is wrong.
$alwaysShow = ['parcel', 'freight', 'pickup'];

$tabsWithOrders = [];
foreach ($benchNames as $benchKey => $benchName) {
    if (!empty($grouped[$benchKey]) || in_array($benchKey, $alwaysShow, true)) {
        $tabsWithOrders[] = ['id' => $benchKey, 'name' => $benchName];
    }
}
$hasUnassigned = !empty($grouped['none']);

$activeTab = $_GET['tab'] ?? 'all';
?>
<?php if (\App\Services\FulfillmentPolicy::enabled()): ?><p><a class="btn btn--primary" href="<?= '/shipping/fulfillments' ?>">Shipping &amp; Fulfillment</a></p><?php endif; ?>

<!--
    The header is already a flex row with space-between. The buttons belong in its right
    hand side, not floated out of the left one - a float with a negative top margin does
    not reserve any space, so it landed on top of the heading rather than beside it.
-->
<div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1rem">
    <div>
        <h1 class="page-title">Shipping Queue</h1>
        <p class="page-subtitle"><?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?> ready to ship</p>
    </div>
    <div style="display:flex;gap:.5rem;flex-shrink:0">
        <a href="/shipping/approve" class="btn btn--secondary">Waiting for OK</a>
        <a href="/shipping/pull" class="btn btn--secondary">Pull list</a>
        <a href="/shipping/release" class="btn btn--primary">Ready to ship</a>
    </div>
</div>

<!-- Tabs -->
<div style="border-bottom:2px solid #d1d5db;margin-bottom:1.25rem;overflow-x:auto;white-space:nowrap">
    <?php
    $tabs = [['id' => 'all', 'name' => 'All', 'count' => count($orders)]];
    foreach ($tabsWithOrders as $sv) {
        $tabs[] = ['id' => $sv['id'], 'name' => $sv['name'], 'count' => count($grouped[$sv['id']] ?? [])];
    }
    if ($hasUnassigned) {
        $tabs[] = ['id' => 'none', 'name' => 'No Carrier', 'count' => count($grouped['none'] ?? [])];
    }
    foreach ($tabs as $tab):
        $isActive = $activeTab === $tab['id'];
    ?>
    <a href="?tab=<?= urlencode($tab['id']) ?>"
       style="display:inline-block;padding:.6rem 1.1rem;font-size:.9rem;font-weight:<?= $isActive ? '700' : '500' ?>;color:<?= $isActive ? '#222b59' : '#6b7280' ?>;border-bottom:<?= $isActive ? '3px solid #222b59' : '3px solid transparent' ?>;margin-bottom:-2px;text-decoration:none;white-space:nowrap">
        <?= e($tab['name']) ?>
        <span style="display:inline-block;background:<?= $isActive ? '#222b59' : '#e5e7eb' ?>;color:<?= $isActive ? '#fff' : '#374151' ?>;font-size:.72rem;font-weight:700;border-radius:999px;padding:.1rem .5rem;margin-left:.35rem"><?= $tab['count'] ?></span>
    </a>
    <?php endforeach; ?>
</div>

<?php
/*
 * A legend, on the page.
 *
 * These badges were explained in title attributes, which need a mouse and a second of
 * patience — and the bench runs on a tablet, where hover does not happen at all. A
 * line of text costs nothing and everybody can read it.
 */
?>
<div style="font-size:.78rem;color:#6b7280;margin:-.4rem 0 1rem;line-height:1.8">
    <strong style="color:#374151">What the tags mean:</strong>
    <span style="white-space:nowrap"><strong>Picking</strong> partly pulled</span> ·
    <span style="white-space:nowrap"><strong>Picked</strong> all pulled</span> ·
    <span style="white-space:nowrap"><strong>Short</strong> cannot be filled in full</span> ·
    <span style="white-space:nowrap"><strong>Packing</strong> box partly scanned</span> ·
    <span style="white-space:nowrap"><strong>Confirmed</strong> a person checked the box by eye</span> ·
    <span style="white-space:nowrap"><strong>Verified</strong> every item scanned and matched</span> ·
    <span style="white-space:nowrap"><strong>Mismatch</strong> box did not match, reason recorded</span>
</div>

<?php
// Decide which orders to show for active tab
if ($activeTab === 'all') {
    $displayOrders = $orders;
} elseif ($activeTab === 'none') {
    $displayOrders = $grouped['none'] ?? [];
} else {
    $displayOrders = $grouped[$activeTab] ?? [];
}
?>

<?php if (empty($displayOrders)): ?>
    <div style="background:#fff;border:1px solid #d1d5db;border-radius:8px;padding:3rem;text-align:center;color:#6b7280">
        <svg width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2" style="color:#d1d5db;margin:0 auto 1rem;display:block">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
        </svg>
        <div style="font-size:1.1rem;font-weight:600;color:#374151;margin-bottom:.4rem">No orders in this queue</div>
        <div style="font-size:.9rem">Confirmed and processing orders will appear here.</div>
    </div>
<?php else: ?>
    <div style="background:#fff;border:1px solid #d1d5db;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.07);overflow:hidden">
        <div style="overflow-x:auto">
            <table class="queue-table" style="width:100%;border-collapse:collapse;font-size:.925rem">
                <thead>
                    <tr style="background:#f8f9fb;border-bottom:2px solid #d1d5db">
                        <th style="padding:.7rem 1rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;white-space:nowrap">SO #</th>
                        <th style="padding:.7rem 1rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">Customer</th>
                        <th style="padding:.7rem 1rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;white-space:nowrap">PO #</th>
                        <th style="padding:.7rem 1rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;white-space:nowrap">Ship By</th>
                        <th style="padding:.7rem 1rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">Ship To</th>
                        <?php if ($activeTab === 'all' || $activeTab === 'none'): ?>
                        <th style="padding:.7rem 1rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;white-space:nowrap">Carrier</th>
                        <?php endif; ?>
                        <th style="padding:.7rem 1rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">Status</th>
                        <th style="padding:.7rem 1rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;white-space:nowrap">Priority</th>
                        <th style="padding:.7rem 1rem;text-align:right;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">Total</th>
                        <th style="padding:.7rem 1rem"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($displayOrders as $o):
                        $shipDate   = $o['requested_ship_date'] ?? null;
                        $isOverdue  = $shipDate && $shipDate < $today;
                        $isDueToday = $shipDate && $shipDate === $today;
                        $isDueSoon  = $shipDate && $shipDate === $tomorrow;

                        $shipAddr = trim(implode(', ', array_filter([
                            $o['ship_address_1'] ?: null,
                            $o['ship_city']      ?: null,
                            $o['ship_state']     ?: null,
                        ])));
                    ?>
                    <tr style="border-bottom:1px solid #f3f4f6;cursor:pointer" onclick="window.location='/sales-orders/<?= (int)$o['id'] ?>'" onmouseover="this.style.background='#eff6ff'" onmouseout="this.style.background=''">
                        <td data-label="SO #" style="padding:.75rem 1rem;white-space:nowrap">
                            <span style="font-weight:700;color:#0A3D91;font-family:monospace"><?= e($o['so_number']) ?></span>
                            <?php
                            /*
                             * Where it came from, when that changes how it is treated.
                             *
                             * A marketplace order carries a shipping deadline that is
                             * not ours to negotiate — missing it costs the account
                             * rather than annoying one customer — so the bench has to
                             * see which ones those are without opening them. Direct
                             * orders say nothing, because most orders are direct and a
                             * badge on everything is a badge on nothing.
                             */
                            $chan = $o['channel'] ?? 'direct';
                            $chanStyle = [
                                'amazon'  => ['#fff7ed', '#fdba74', '#9a3412', 'AMAZON'],
                                'website' => ['#eff6ff', '#bfdbfe', '#1d4ed8', 'WEBSITE'],
                                'edi'     => ['#f5f3ff', '#ddd6fe', '#5b21b6', 'EDI'],
                            ][$chan] ?? null;
                            ?>
                            <?php if ($chanStyle): ?>
                                <div style="margin-top:.3rem">
                                    <span style="font-size:.68rem;font-weight:700;letter-spacing:.04em;
                                                 padding:.15rem .4rem;border-radius:4px;
                                                 background:<?= $chanStyle[0] ?>;border:1px solid <?= $chanStyle[1] ?>;
                                                 color:<?= $chanStyle[2] ?>"><?= $chanStyle[3] ?></span>
                                </div>
                                <?php if (!empty($o['channel_reference'])): ?>
                                    <div style="font-size:.68rem;color:#9ca3af;font-family:monospace;margin-top:.15rem">
                                        <?= e($o['channel_reference']) ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td data-label="Customer" style="padding:.75rem 1rem">
                            <div style="font-weight:600;color:#111"><?= e($o['company_name']) ?></div>
                            <?php if ($o['phone']): ?><div style="font-size:.8rem;color:#6b7280"><?= e($o['phone']) ?></div><?php endif; ?>
                        </td>
                        <td data-label="PO #" style="padding:.75rem 1rem;color:#374151;white-space:nowrap"><?= $o['po_number'] ? e($o['po_number']) : '<span style="color:#d1d5db">—</span>' ?></td>
                        <td data-label="Ship by" style="padding:.75rem 1rem;white-space:nowrap">
                            <?php if ($shipDate): ?>
                                <?php if ($isOverdue): ?>
                                    <span style="color:#dc2626;font-weight:700">⚠ <?= date('M j', strtotime($shipDate)) ?><span style="font-size:.75rem;font-weight:400;display:block">Overdue</span></span>
                                <?php elseif ($isDueToday): ?>
                                    <span style="color:#d97706;font-weight:700"><?= date('M j', strtotime($shipDate)) ?><span style="font-size:.75rem;font-weight:400;display:block">Today</span></span>
                                <?php elseif ($isDueSoon): ?>
                                    <span style="color:#d97706"><?= date('M j', strtotime($shipDate)) ?><span style="font-size:.75rem;color:#9ca3af;display:block">Tomorrow</span></span>
                                <?php else: ?>
                                    <span style="color:#374151"><?= date('M j, Y', strtotime($shipDate)) ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color:#d1d5db">—</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Ship to" style="padding:.75rem 1rem;font-size:.875rem;color:#374151"><?= $shipAddr ? e($shipAddr) : '<span style="color:#d1d5db">—</span>' ?></td>
                        <?php if ($activeTab === 'all' || $activeTab === 'none'): ?>
                        <td data-label="Carrier" style="padding:.75rem 1rem;white-space:nowrap">
                            <?php if ($o['ship_via_name']): ?>
                                <span style="background:#f0f4ff;color:#0A3D91;padding:.2rem .6rem;border-radius:4px;font-size:.8rem;font-weight:600"><?= e($o['ship_via_name']) ?></span>
                            <?php else: ?>
                                <span style="color:#d1d5db">—</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <td data-label="Status" style="padding:.75rem 1rem">
                            <span class="badge <?= $statusBadge[$o['status']] ?? 'badge--neutral' ?>"><?= $statusLabel[$o['status']] ?? e($o['status']) ?></span>
                        </td>
                        <?php /*
                         * Set it here, because here is where somebody finds out.
                         *
                         * The column was read by the pull list, the approval board and
                         * the release queue and written by nothing — there was no field
                         * on any form and no route to post to, so an order could not be
                         * made urgent at all. The clerk learns a job has to go today;
                         * the salesperson who keyed it last week does not.
                         *
                         * Submits on change: one tap on the bench tablet, not pick-then-
                         * press.
                         */ ?>
                        <td data-label="Priority" style="padding:.75rem 1rem;white-space:nowrap" onclick="event.stopPropagation()">
                            <?php $pri = $o['priority'] ?? 'normal'; ?>
                            <form method="POST" action="/shipping/<?= (int)$o['id'] ?>/priority" style="margin:0">
                                <?= csrf_field() ?>
                                <input type="hidden" name="return" value="/shipping<?= $activeTab !== 'all' ? '?tab=' . e($activeTab) : '' ?>">
                                <select name="priority" onchange="this.form.submit()"
                                        style="padding:.3rem .5rem;font-size:.8rem;border-radius:5px;cursor:pointer;
                                               border:1px solid <?= $pri === 'urgent' ? '#fca5a5' : ($pri === 'high' ? '#fcd34d' : '#d1d5db') ?>;
                                               background:<?= $pri === 'urgent' ? '#fef2f2' : ($pri === 'high' ? '#fffbeb' : '#fff') ?>;
                                               color:<?= $pri === 'urgent' ? '#b91c1c' : ($pri === 'high' ? '#92400e' : '#6b7280') ?>;
                                               font-weight:<?= $pri === 'normal' ? '400' : '700' ?>">
                                    <option value="normal" <?= $pri === 'normal' ? 'selected' : '' ?>>Normal</option>
                                    <option value="high"   <?= $pri === 'high'   ? 'selected' : '' ?>>High</option>
                                    <option value="urgent" <?= $pri === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                                </select>
                            </form>
                        </td>
                        <td data-label="Total" style="padding:.75rem 1rem;text-align:right;font-family:monospace;font-weight:600;color:#111">$<?= number_format((float)$o['total_amount'], 2) ?></td>
                        <td class="queue-actions" style="padding:.75rem 1rem;text-align:right;white-space:nowrap" onclick="event.stopPropagation()">
                            <?php
                            // Pick state, so the floor can see at a glance what's underway
                            // and what someone has already flagged as short.
                            $pickState = $o['pick_status'] ?? 'not_started';
                            // The fourth entry is the label, the fifth says what it means.
                            // A two-word badge nobody can interpret is decoration.
                            $pickBadge = [
                                'in_progress' => ['#eff6ff', '#bfdbfe', '#1d4ed8', 'Picking',
                                    'Some of this order has been pulled, not all of it.'],
                                'ready'       => ['#f0fdf4', '#bbf7d0', '#166534', 'Picked',
                                    'Everything ordered has been pulled off the shelf.'],
                                'short'       => ['#fffbeb', '#fcd34d', '#92400e', 'Short',
                                    'Flagged short — it cannot be filled in full.'],
                            ][$pickState] ?? null;
                            ?>
                            <?php
                            // Pack state matters as much as pick state: an order that is
                            // picked but unverified is the one about to go out wrong.
                            $packState = $o['pack_status'] ?? 'not_started';
                            $packBadge = [
                                'in_progress' => ['#eff6ff', '#bfdbfe', '#1d4ed8', 'Packing',
                                    'Some of the box has been scanned in, not all of it.'],
                                // Confirmed by a person, not read by a scanner. Shown in a
                                // quieter colour than Verified on purpose: both are finished,
                                // one carries weaker evidence, and the badge should say so.
                                'confirmed'   => ['#f8fafc', '#cbd5e1', '#475569', 'Confirmed',
                                    'A person checked the box by eye and said it matches the pick — pressed "Confirm without scanning" on the Pack screen. Finished, but nothing was scanned.'],
                                'verified'    => ['#f0fdf4', '#bbf7d0', '#166534', 'Verified',
                                    'Every item was scanned into the box and matched the pick. The strongest check there is.'],
                                'mismatch'    => ['#fef2f2', '#fca5a5', '#b91c1c', 'Mismatch',
                                    'The box did not match the pick and somebody recorded why.'],
                            ][$packState] ?? null;
                            ?>
                            <?php if ($pickBadge): ?>
                                <span class="queue-tag" title="<?= e($pickBadge[4]) ?>"
                                      style="font-size:.75rem;padding:.25rem .55rem;border-radius:4px;margin-right:.4rem;cursor:help;
                                             background:<?= $pickBadge[0] ?>;border:1px solid <?= $pickBadge[1] ?>;color:<?= $pickBadge[2] ?>">
                                    <?= $pickBadge[3] ?>
                                </span>
                                <?php /* The same sentence the title carries. There is no hover on the
                                         bench tablet, so on a touch screen the title is simply not
                                         there; CSS shows this copy only in card mode. */ ?>
                                <span class="queue-tag-why"><?= e($pickBadge[4]) ?></span>
                            <?php endif; ?>
                            <?php if ($packBadge): ?>
                                <span class="queue-tag" title="<?= e($packBadge[4]) ?>"
                                      style="font-size:.75rem;padding:.25rem .55rem;border-radius:4px;margin-right:.4rem;cursor:help;
                                             background:<?= $packBadge[0] ?>;border:1px solid <?= $packBadge[1] ?>;color:<?= $packBadge[2] ?>">
                                    <?= $packBadge[3] ?>
                                </span>
                                <?php /* The same sentence the title carries. There is no hover on the
                                         bench tablet, so on a touch screen the title is simply not
                                         there; CSS shows this copy only in card mode. */ ?>
                                <span class="queue-tag-why"><?= e($packBadge[4]) ?></span>
                            <?php endif; ?>
                            <?php
                            /*
                             * One step forward, the rest out of the way.
                             *
                             * Every action was offered on every row at the same weight,
                             * so a fully picked order still led with a filled-in Pick
                             * button and the thing actually left to do sat beside it
                             * looking the same. Pick and Pack stay reachable once done
                             * — picks get corrected — but as quiet links, not as the
                             * instruction.
                             */
                            $unpackedRow = (float)($o['qty_unpacked'] ?? 0);
                            $packedOk    = $unpackedRow <= 0.0001 || ($o['pack_status'] ?? '') === 'mismatch';

                            /*
                             * Picking is finished when the pick says so, not when
                             * something has been picked.
                             *
                             * This read "nothing picked yet" as the only reason to lead
                             * with Pick, so a half-picked order went quiet on the one
                             * button that finishes it. 'short' counts as finished: it is
                             * as far as that order can go, and the next move is to pack
                             * what there is.
                             */
                            $pickState = $o['pick_status'] ?? 'not_started';
                            $needsPick = in_array($pickState, ['not_started', 'in_progress'], true);
                            $needsPack = !$needsPick && !$packedOk;

                            $lead  = 'font-size:.8rem;padding:.3rem .7rem;border-radius:5px;text-decoration:none;'
                                   . 'margin-right:.4rem;display:inline-block;border:1px solid #0A3D91;'
                                   . 'color:#fff;background:#0A3D91';
                            $quiet = 'font-size:.8rem;padding:.3rem .7rem;border-radius:5px;text-decoration:none;'
                                   . 'margin-right:.4rem;display:inline-block;border:1px solid #d1d5db;color:#6b7280';

                            /*
                             * A freight order has no label to lead with.
                             *
                             * Taking the FedEx button off those rows left them with no
                             * action at all once picked and packed — the row went quiet
                             * exactly when the shipment was ready to go. For anything not
                             * going by parcel, invoicing IS the last step the system
                             * takes; the carrier is booked outside it.
                             */
                            $parcelRow  = (($o['ship_mode'] ?? '') === 'parcel') || ($o['ship_mode'] ?? '') === '';
                            $readyToGo  = !$needsPick && !$needsPack;
                            $leadInvoice = $readyToGo && !$parcelRow;
                            ?>
                            <a href="/shipping/<?= (int)$o['id'] ?>/pick"
                               style="<?= $needsPick ? $lead : $quiet ?>">Pick</a>
                            <a href="/shipping/<?= (int)$o['id'] ?>/pack"
                               style="<?= $needsPack ? $lead : $quiet ?>">Pack</a>
                            <?php
                            /*
                             * The row's own next step, not a link to a list.
                             *
                             * This used to send everybody to Ready to ship whatever
                             * state their order was in — including orders already
                             * invoiced, which are not on that page at all, so the
                             * button led to a screen without the order on it.
                             */
                            $toShip   = (float)($o['qty_to_ship'] ?? 0);
                            $unpacked = (float)($o['qty_unpacked'] ?? 0);
                            $invId    = (int)($o['invoice_id'] ?? 0);
                            $btn      = 'font-size:.8rem;padding:.3rem .7rem;border-radius:5px;'
                                      . 'text-decoration:none;margin-right:.4rem;display:inline-block';
                            ?>
                            <?php if ($toShip <= 0 && $invId > 0): ?>
                                <a href="/invoices/<?= $invId ?>"
                                   title="Already invoiced — nothing left to bill on this order"
                                   style="<?= $btn ?>;border:1px solid #cbd5e1;color:#475569">
                                    Invoice <?= e($o['invoice_number'] ?? '') ?>
                                </a>
                            <?php elseif ($toShip <= 0): ?>
                                <span title="Nothing picked yet, so there is nothing to invoice"
                                      style="<?= $btn ?>;border:1px dashed #cbd5e1;color:#94a3b8">Invoice</span>
                            <?php elseif ($unpacked > 0.0001 && ($o['pack_status'] ?? '') !== 'mismatch'): ?>
                                <?php /* Nothing here: Pack is already the lead button on
                                         this row, and a second button to the same screen
                                         under a different name is not a second step. */ ?>
                            <?php else: ?>
                                <form method="POST" action="/shipping/<?= (int)$o['id'] ?>/release"
                                      style="display:inline;margin-right:.4rem">
                                    <?= csrf_field() ?>
                                    <button type="submit"
                                            title="Invoice what has been picked and packed on this order"
                                            onclick="return confirm('Invoice the <?= rtrim(rtrim(number_format($toShip, 2), '0'), '.') ?> picked on SO #<?= e($o['so_number']) ?>?')"
                                            style="font-size:.8rem;padding:.3rem .7rem;border-radius:5px;cursor:pointer;<?= $leadInvoice
                                                ? 'border:1px solid #16a34a;color:#fff;background:#16a34a'
                                                : 'border:1px solid #16a34a;color:#16a34a;background:#fff' ?>">
                                        Invoice
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php if (($o['ship_mode'] ?? '') === 'freight' || ($o['ship_mode'] ?? '') === 'pickup'): ?>
                                <?php /*
                                 * Nothing here, deliberately.
                                 *
                                 * FedEx is not the carrier on an LTL order and nobody
                                 * labels a collection, so the button is correctly absent
                                 * - but these rows used to carry a dashed "No FedEx
                                 * label" pill saying so. That existed to stop a freight
                                 * row going quiet once picked and packed, back when
                                 * removing the button left it with no action at all.
                                 * Invoice leads these rows now, so the hole it filled is
                                 * gone and all it does is explain an absence on every
                                 * freight order for ever. The Carrier column already
                                 * says Freight, which is the actual explanation, and the
                                 * label screen says it properly to anyone who goes there
                                 * looking for a label.
                                 */ ?>
                            <?php else: ?>
                                <?php /* The lead once the box is checked: the label
                                         screen invoices the order in the same press. */ ?>
                                <a href="/shipping/<?= (int)$o['id'] ?>/label"
                                   style="<?= $readyToGo ? $lead : $quiet ?>">Label</a>
                            <?php endif; ?>
                            <a href="/sales-orders/<?= (int)$o['id'] ?>/packing-slip" target="_blank"
                               style="font-size:.8rem;padding:.3rem .7rem;border:1px solid #d1d5db;border-radius:5px;color:#374151;text-decoration:none"
                               onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background=''">Packing Slip</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
