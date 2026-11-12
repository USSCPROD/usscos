<?php ob_start();

$payment_terms     = $payment_terms     ?? [];
$users             = $users             ?? [];
$reps              = $reps              ?? [];
$ship_via_options  = $ship_via_options  ?? [];
$tax_rates         = $tax_rates         ?? [];
$customer_messages = $customer_messages ?? [];
$line_items        = $line_items        ?? [];

$defaultTaxId  = 0;
$defaultTaxPct = 0;
foreach ($tax_rates as $tr) {
    if ((int)$tr['id'] === (int)($so['tax_rate_id'] ?? 0)) {
        $defaultTaxId  = (int)$tr['id'];
        $defaultTaxPct = (float)$tr['rate'] * 100;
        break;
    }
}
if (!$defaultTaxId) {
    foreach ($tax_rates as $tr) {
        if (stripos($tr['name'], 'out of state') !== false) {
            $defaultTaxId  = (int)$tr['id'];
            $defaultTaxPct = (float)$tr['rate'] * 100;
            break;
        }
    }
}

$hasShipAddr = !empty($so['ship_address_1']);
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
    <div>
        <a href="/sales-orders/<?= (int)$so['id'] ?>" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            SO #<?= e($so['so_number']) ?>
        </a>
        <h1 class="page-title" style="margin-top:.25rem">Edit Sales Order</h1>
    </div>
</div>

<?php
// What the warehouse has already done to this order. Shown before the form, because the
// moment to say "somebody has pulled this" is before the edit, not after it.
$soPicked = 0.0;
foreach (($line_items ?? []) as $li) { $soPicked += (float)($li['qty_picked'] ?? 0); }

/*
 * The shipping line is edited in its own field, not as a row.
 *
 * Shown as both it would be billed twice on save — once from the row and once from
 * the field — so it is lifted out here and handed to the field instead.
 */
$soShipAmount = null;
$soGoodsLines = [];

foreach ($line_items as $li) {
    $isCharge = ($li['product_id'] ?? null) === null
             && preg_match('~^\s*(shipping|handling|freight)~i', (string)($li['description'] ?? ''));

    if ($isCharge && $soShipAmount === null) {
        $soShipAmount = (float)($li['line_total'] ?? $li['unit_price'] ?? 0);
        continue;
    }

    $soGoodsLines[] = $li;
}
?>
<?php if ($soPicked > 0): ?>
    <div class="alert" style="background:#fffbeb;border:1px solid #fcd34d;color:#92400e;margin-bottom:1.25rem">
        <strong>The warehouse has already picked this order.</strong>
        <?= e(rtrim(rtrim(number_format($soPicked, 2), '0'), '.')) ?> already off the shelf.
        Cutting a quantity or removing a line means somebody has to carry paint back — the
        order will tidy itself up, the shelf will not. Anything you change here is flagged
        for the warehouse so they know to return it.
    </div>
<?php endif; ?>

<form method="post" action="/sales-orders/<?= (int)$so['id'] ?>/edit" id="soForm">
<?= csrf_field() ?>

<div style="background:#fff;border:1px solid #d1d5db;border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.08);overflow:visible;font-family:inherit">

    <!-- ── Customer/Job bar ─────────────────────────────────────────────── -->
    <div style="padding:1rem 1.5rem;background:#f8f9fb;border-bottom:1px solid #d1d5db">
        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
            <label style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;white-space:nowrap">Customer / Job</label>
            <input type="hidden" name="customer_id" value="<?= (int)$so['customer_id'] ?>">
            <div style="padding:.55rem .85rem;background:#fff;border:1px solid #d1d5db;border-radius:6px;font-size:1rem;font-weight:600;display:inline-flex;align-items:center;gap:.75rem">
                <?= e($so['company_name']) ?>
                <a href="/customers/<?= (int)$so['customer_id'] ?>" style="font-weight:400;font-size:.875rem;color:#6b7280">View</a>
            </div>
        </div>
    </div>

    <!-- ── Header: SO title | date+SO# | Bill To | Ship To ──────────────── -->
    <table style="width:100%;border-collapse:collapse;border-bottom:2px solid #d1d5db;table-layout:fixed">
        <colgroup>
            <col style="width:200px">
            <col style="width:175px">
            <col>
            <col>
        </colgroup>
        <tr>
            <!-- Sales Order title + SO# + Status -->
            <td style="padding:1.25rem 1.5rem;vertical-align:top;border-right:1px solid #d1d5db">
                <div style="font-size:1.75rem;font-weight:700;color:#111;letter-spacing:-.02em;margin-bottom:1.25rem">Sales Order</div>
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.4rem">S.O. No.</div>
                <div style="padding:.6rem .85rem;background:#f3f4f6;border:1px solid #d1d5db;border-radius:6px;font-size:1rem;font-weight:700;color:#374151;margin-bottom:.85rem"><?= e($so['so_number']) ?></div>
                <div style="<?= soInpStyle2('lbl') ?>">Status</div>
                <select name="status" style="<?= soInpStyle2('inp') ?>">
                    <?php foreach ([
                        'draft'             => 'Draft',
                        'confirmed'         => 'Confirmed',
                        'processing'        => 'Processing',
                        'partially_shipped' => 'Partially Shipped',
                        'shipped'           => 'Shipped',
                        'invoiced'          => 'Invoiced',
                        'cancelled'         => 'Cancelled',
                    ] as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= ($so['status'] ?? '') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <!-- Date + Ship Date -->
            <td style="padding:1rem 1.25rem;vertical-align:top;border-right:1px solid #d1d5db">
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.4rem">Date</div>
                <input type="date" name="order_date" value="<?= e($so['order_date'] ?? date('Y-m-d')) ?>" required
                    style="width:100%;padding:.65rem .75rem;font-size:.95rem;border:1px solid #d1d5db;border-radius:6px;background:#fff;box-sizing:border-box;margin-bottom:.85rem">
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.4rem">Ship Date</div>
                <input type="date" name="requested_ship_date" value="<?= e($so['requested_ship_date'] ?? '') ?>"
                    style="width:100%;padding:.65rem .75rem;font-size:.95rem;border:1px solid #d1d5db;border-radius:6px;background:#fff;box-sizing:border-box">
            </td>
            <!-- Bill To -->
            <td style="padding:1rem 1.25rem;vertical-align:top;border-right:1px solid #d1d5db">
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.4rem">Name / Address</div>
                <div style="padding:.75rem 1rem;min-height:110px;background:#f8f9fb;border:1px solid #d1d5db;border-radius:6px;font-size:.9rem;line-height:1.8;color:#374151">
                    <strong><?= e($so['company_name']) ?></strong>
                    <?php if ($so['bill_address_1'] ?? ''): ?><br><?= e($so['bill_address_1']) ?><?php endif; ?>
                    <?php if ($so['bill_city'] ?? ''): ?>
                        <br><?= e($so['bill_city']) ?><?= ($so['bill_state'] ?? '') ? ', ' . e($so['bill_state']) : '' ?> <?= e($so['bill_zip'] ?? '') ?>
                    <?php endif; ?>
                </div>
            </td>
            <!-- Ship To -->
            <td style="padding:1rem 1.25rem;vertical-align:top">
                <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.4rem;display:flex;align-items:center;gap:.5rem">
                    Ship To
                    <label style="font-size:.8rem;font-weight:600;text-transform:none;letter-spacing:0;display:inline-flex;align-items:center;gap:.3rem;cursor:pointer;color:#6b7280">
                        <input type="checkbox" id="sameAsBilling" <?= $hasShipAddr ? '' : 'checked' ?> style="accent-color:#0A3D91"> Same as billing
                    </label>
                </div>
                <div id="shipSameBox" style="padding:.75rem 1rem;min-height:110px;background:#f8f9fb;border:1px solid #d1d5db;border-radius:6px;font-size:.9rem;color:#9ca3af;<?= $hasShipAddr ? 'display:none;' : '' ?>">
                    Same as billing address
                </div>
                <div id="shipEditBox" style="<?= $hasShipAddr ? '' : 'display:none;' ?>">
                    <div style="display:flex;flex-direction:column;gap:.4rem">
                        <input type="text" name="ship_name"      value="<?= e($so['ship_name'] ?? '') ?>"      placeholder="Name / Attn"  style="<?= soInpStyle2('inp') ?>">
                        <input type="text" name="ship_address_1" value="<?= e($so['ship_address_1'] ?? '') ?>" placeholder="Address"      style="<?= soInpStyle2('inp') ?>">
                        <input type="text" name="ship_address_2" value="<?= e($so['ship_address_2'] ?? '') ?>" placeholder="Address 2"    style="<?= soInpStyle2('inp') ?>">
                        <div style="display:grid;grid-template-columns:1fr 52px 85px;gap:.35rem">
                            <input type="text" name="ship_city"  value="<?= e($so['ship_city'] ?? '') ?>"  placeholder="City"         style="<?= soInpStyle2('inp') ?>">
                            <input type="text" name="ship_state" value="<?= e($so['ship_state'] ?? '') ?>" placeholder="ST" maxlength="2" style="<?= soInpStyle2('inp') ?>;text-transform:uppercase">
                            <input type="text" name="ship_zip"   value="<?= e($so['ship_zip'] ?? '') ?>"   placeholder="Zip"          style="<?= soInpStyle2('inp') ?>">
                        </div>
                        <input type="text" name="ship_phone" value="<?= e($so['ship_phone'] ?? '') ?>" placeholder="Phone" style="<?= soInpStyle2('inp') ?>">
                    </div>
                </div>
            </td>
        </tr>
    </table>


    <!-- ── Channel ───────────────────────────────────────────────────────
         A marketplace order must NOT be charged sales tax - Amazon collected and
         remits it - and the channel is what tells the tax engine so. Changing it here
         re-resolves the tax on the next save.
    -->
    <table style="width:100%;border-collapse:collapse;border-bottom:2px solid #d1d5db;background:#f8f9fb;table-layout:fixed">
        <tr>
            <td style="padding:.85rem 1.25rem;border-right:1px solid #d1d5db;vertical-align:top;width:20%">
                <div style="<?= soInpStyle2('lbl') ?>">Channel</div>
                <select name="channel" id="soChannel" style="<?= soInpStyle2('inp') ?>">
                    <?php foreach ([
                        'direct'  => 'Direct',
                        'amazon'  => 'Amazon',
                        'website' => 'Website',
                        'pickup'  => 'Pickup',
                        'edi'     => 'EDI',
                        'other'   => 'Other',
                    ] as $chKey => $chLabel): ?>
                        <option value="<?= e($chKey) ?>"<?= (string)($so['channel'] ?? 'direct') === $chKey ? ' selected' : '' ?>><?= e($chLabel) ?></option>
                    <?php endforeach; ?>
                </select>
                <div id="chHint" style="font-size:.72rem;color:#6b7280;margin-top:.4rem;line-height:1.4"></div>
            </td>
            <td style="padding:.85rem 1.25rem;border-right:1px solid #d1d5db;vertical-align:top;width:30%">
                <div style="<?= soInpStyle2('lbl') ?>">Marketplace Order ID</div>
                <input type="text" name="channel_reference" id="soChannelRef" maxlength="100"
                       value="<?= e($so['channel_reference'] ?? '') ?>" style="<?= soInpStyle2('inp') ?>">
            </td>
            <td style="padding:.85rem 1.25rem;border-right:1px solid #d1d5db;vertical-align:top;width:25%" id="mktTaxCell">
                <div style="<?= soInpStyle2('lbl') ?>">Tax Amazon Collected</div>
                <input type="number" step="0.01" min="0" name="marketplace_tax_collected"
                       value="<?= e((string)($so['marketplace_tax_collected'] ?? '')) ?>" style="<?= soInpStyle2('inp') ?>">
            </td>
            <td style="padding:.85rem 1.25rem;vertical-align:top;width:25%" id="mktFeeCell">
                <div style="<?= soInpStyle2('lbl') ?>">Amazon Fee</div>
                <input type="number" step="0.01" min="0" name="marketplace_fee"
                       value="<?= e((string)($so['marketplace_fee'] ?? '')) ?>"
                       placeholder="0.00" style="<?= soInpStyle2('inp') ?>">
                <div style="font-size:.72rem;color:#9ca3af;margin-top:.3rem;line-height:1.35">
                    What Amazon keeps. A cost to USSC &mdash; never billed to the buyer.
                </div>
            </td>
        </tr>
    </table>

    <!-- ── Meta row: PO · Terms · Rep · Processed By · Ship Via ──────────── -->
    <table style="width:100%;border-collapse:collapse;border-bottom:2px solid #d1d5db;background:#f8f9fb;table-layout:fixed">
        <tr>
            <td style="padding:.85rem 1.25rem;border-right:1px solid #d1d5db;vertical-align:top;width:20%">
                <div style="<?= soInpStyle2('lbl') ?>">P.O. No.</div>
                <input type="text" name="po_number" value="<?= e($so['po_number'] ?? '') ?>" maxlength="100" style="<?= soInpStyle2('inp') ?>">
            </td>
            <td style="padding:.85rem 1.25rem;border-right:1px solid #d1d5db;vertical-align:top;width:20%">
                <div style="<?= soInpStyle2('lbl') ?>">Terms</div>
                <select name="payment_term_id" style="<?= soInpStyle2('inp') ?>">
                    <option value="">— Select —</option>
                    <?php foreach ($payment_terms as $pt): ?>
                        <option value="<?= (int)$pt['id'] ?>" <?= (int)($so['payment_term_id'] ?? 0) === (int)$pt['id'] ? 'selected' : '' ?>><?= e($pt['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td style="padding:.85rem 1.25rem;border-right:1px solid #d1d5db;vertical-align:top;width:20%">
                <div style="<?= soInpStyle2('lbl') ?>">Rep</div>
                <select name="rep_id" style="<?= soInpStyle2('inp') ?>">
                    <option value="">— None —</option>
                    <?php foreach ($reps as $rep): ?>
                        <option value="<?= (int)$rep['id'] ?>" <?= (int)($so['rep_id'] ?? 0) === (int)$rep['id'] ? 'selected' : '' ?>><?= e($rep['last_name'] . ', ' . $rep['first_name']) ?><?= $rep['rep_code'] ? ' (' . e($rep['rep_code']) . ')' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td style="padding:.85rem 1.25rem;border-right:1px solid #d1d5db;vertical-align:top;width:20%">
                <div style="<?= soInpStyle2('lbl') ?>">Processed By</div>
                <select name="processed_by_id" style="<?= soInpStyle2('inp') ?>">
                    <?php foreach ($users as $u): ?>
                        <option value="<?= (int)$u['id'] ?>" <?= (int)($so['created_by'] ?? 0) === (int)$u['id'] ? 'selected' : '' ?>><?= e($u['first_name'] . ' ' . $u['last_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td style="padding:.85rem 1.25rem;vertical-align:top;width:20%">
                <div style="<?= soInpStyle2('lbl') ?>">Ship Via</div>
                <select name="ship_via_id" id="shipViaSelect" style="<?= soInpStyle2('inp') ?>">
                    <option value="" data-mode="">— Select —</option>
                    <?php foreach ($ship_via_options as $sv): ?>
                        <option value="<?= (int)$sv['id'] ?>" data-mode="<?= e($sv['ship_mode'] ?? 'other') ?>" data-carrier="<?= e($sv['carrier_code'] ?? '') ?>"
                            <?= (int)($so['ship_via_id'] ?? 0) === (int)$sv['id'] ? 'selected' : '' ?>><?= e($sv['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php /*
                 * Who is taking the pallet.
                 *
                 * Ship Via above answers how it goes and stops at "Freight" - a method,
                 * not a carrier. When somebody rings round for a price and books by
                 * phone, this is where that name is recorded, at the point the decision
                 * is made rather than when a PRO turns up at the bench afterwards.
                 *
                 * Shown only for freight, driven by the data-mode already on each Ship
                 * Via option.
                 */ ?>
                <div id="freightCarrierWrap" style="display:none;margin-top:.5rem">
                    <div style="<?= soInpStyle2('lbl') ?>">Freight carrier</div>
                    <?php if (!empty($freight_carriers)): ?>
                        <select name="freight_carrier_id" style="<?= soInpStyle2('inp') ?>">
                            <option value="">— Not chosen yet —</option>
                            <?php foreach ($freight_carriers as $fc): ?>
                                <option value="<?= (int)$fc['id'] ?>"
                                    <?= (int)($so['freight_carrier_id'] ?? 0) === (int)$fc['id'] ? 'selected' : '' ?>><?= e($fc['name']) ?><?php
                                    if (!empty($fc['scac'])): ?> (<?= e($fc['scac']) ?>)<?php endif; ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <div style="font-size:.78rem;color:#b45309">
                            No carriers yet — <a href="/admin/freight-carriers" style="color:#0A3D91">add them under Admin</a>.
                        </div>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    </table>

    <!-- ── Line Items ───────────────────────────────────────────────────── -->
    <div style="border-bottom:2px solid #d1d5db">
        <div style="display:flex;justify-content:flex-end;padding:.6rem 1rem;background:#f8f9fb;border-bottom:1px solid #d1d5db">
            <button type="button" class="btn btn--sm btn--secondary" id="addLineBtn">+ Add Line</button>
        </div>
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:.925rem">
                <colgroup>
                    <col style="width:155px">
                    <col>
                    <col style="width:88px">
                    <col style="width:65px">
                    <col style="width:120px">
                    <col style="width:72px">
                    <col style="width:50px">
                    <col style="width:120px">
                    <col style="width:34px">
                </colgroup>
                <thead>
                    <tr style="background:#f8f9fb;border-bottom:1px solid #d1d5db">
                        <th style="<?= soInpStyle2('th') ?>">Item</th>
                        <th style="<?= soInpStyle2('th') ?>">Description</th>
                        <th style="<?= soInpStyle2('th') ?>;text-align:right">Quantity</th>
                        <th style="<?= soInpStyle2('th') ?>">U/M</th>
                        <th style="<?= soInpStyle2('th') ?>;text-align:right">Price Each</th>
                        <th style="<?= soInpStyle2('th') ?>;text-align:right">Disc %</th>
                        <th style="<?= soInpStyle2('th') ?>;text-align:center">Tax</th>
                        <th style="<?= soInpStyle2('th') ?>;text-align:right">Amount</th>
                        <th style="<?= soInpStyle2('th') ?>"></th>
                    </tr>
                </thead>
                <tbody id="lineBody"></tbody>
                <tfoot>
                    <tr style="border-top:1px solid #e5e7eb">
                        <td colspan="7" style="padding:.6rem .75rem;text-align:right;color:#6b7280;font-size:.9rem">Subtotal</td>
                        <td style="padding:.6rem .75rem;text-align:right;font-family:monospace;font-weight:600" id="fSubtotal">$0.00</td>
                        <td></td>
                    </tr>
                    <tr id="fDiscRow" style="display:none">
                        <td colspan="7" style="padding:.4rem .75rem;text-align:right;color:#6b7280;font-size:.9rem">Discount</td>
                        <td style="padding:.4rem .75rem;text-align:right;font-family:monospace;color:#ef4444" id="fDiscount"></td>
                        <td></td>
                    </tr>
                    <?php /* Shipping is charged, not picked. It becomes its own line on
                             the order so it reads correctly on the invoice, and the
                             shipping screens already know to keep charge lines off the
                             bench — nobody goes and finds a box of handling. */ ?>
                    <tr>
                        <td colspan="7" style="padding:.5rem .75rem;text-align:right;color:#6b7280;font-size:.9rem">
                            Shipping &amp; handling
                        </td>
                        <td style="padding:.5rem .75rem;text-align:right">
                            <input type="number" name="shipping_amount" id="fShipAmount"
                                   step="0.01" min="0" placeholder="0.00"
                                   value="<?= $soShipAmount !== null ? number_format($soShipAmount, 2, '.', '') : '' ?>"
                                   style="width:100%;max-width:7.5rem;padding:.35rem .5rem;border:1px solid #d1d5db;
                                          border-radius:6px;font-family:monospace;text-align:right;font-size:.9rem">
                            <div style="margin-top:.35rem;text-align:right">
                                <button type="button" id="fQuoteBtn" class="btn btn--sm btn--secondary"
                                        style="font-size:.78rem">Get FedEx quote</button>
                            </div>
                            <div id="fQuoteOut" style="margin-top:.4rem;font-size:.8rem;text-align:left"></div>
                        </td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="7" style="padding:.7rem .75rem;text-align:right">
                            <label style="display:inline-flex;align-items:center;gap:.6rem;font-weight:600;color:#374151">
                                Tax
                                <select name="tax_rate_id" id="taxRateSelect"
                                    style="padding:.4rem .6rem;font-size:.875rem;border:1px solid #d1d5db;border-radius:6px;background:#fff">
                                    <option value="" data-rate="0">— None —</option>
                                    <?php foreach ($tax_rates as $tr): ?>
                                        <option value="<?= (int)$tr['id'] ?>" data-rate="<?= (float)$tr['rate'] * 100 ?>"
                                            <?= (int)$tr['id'] === $defaultTaxId ? 'selected' : '' ?>><?= e($tr['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <input type="hidden" name="tax_rate_pct" id="taxRatePct" value="<?= $defaultTaxPct ?>">
                        </td>
                        <td style="padding:.7rem .75rem;text-align:right;font-family:monospace;font-weight:600" id="fTax">$0.00</td>
                        <td></td>
                    </tr>
                    <tr style="border-top:2px solid #d1d5db">
                        <td colspan="7" style="padding:.85rem .75rem;text-align:right;font-weight:700;font-size:1.05rem">Total</td>
                        <td style="padding:.85rem .75rem;text-align:right;font-family:monospace;font-weight:700;font-size:1.1rem" id="fTotal">$0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- ── Footer: Customer Message · Internal Notes · Memo ─────────────── -->
    <div style="display:grid;grid-template-columns:300px 1fr 1fr;gap:1.5rem;padding:1.25rem 1.5rem;border-bottom:1px solid #d1d5db;align-items:start">
        <div>
            <div style="<?= soInpStyle2('lbl') ?>">Customer Message</div>
            <select name="customer_message_id" style="<?= soInpStyle2('inp') ?>;margin-bottom:.85rem">
                <option value="">— None —</option>
                <?php foreach ($customer_messages as $cm): ?>
                    <option value="<?= (int)$cm['id'] ?>" <?= (int)($so['customer_message_id'] ?? 0) === (int)$cm['id'] ? 'selected' : '' ?>><?= e($cm['message']) ?></option>
                <?php endforeach; ?>
            </select>
            <div style="<?= soInpStyle2('lbl') ?>">Internal Notes</div>
            <textarea name="internal_notes" rows="3" style="<?= soInpStyle2('inp') ?>;resize:vertical"><?= e($so['internal_notes'] ?? '') ?></textarea>
        </div>
        <div>
            <div style="<?= soInpStyle2('lbl') ?>">Memo</div>
            <textarea name="memo" rows="5" style="<?= soInpStyle2('inp') ?>;resize:vertical"><?= e($so['memo'] ?? '') ?></textarea>
        </div>
        <div></div>
    </div>

    <!-- ── Actions ──────────────────────────────────────────────────────── -->
    <div style="display:flex;justify-content:flex-end;gap:.75rem;padding:1rem 1.5rem;background:#f8f9fb">
        <a href="/sales-orders/<?= (int)$so['id'] ?>" class="btn btn--secondary">Cancel</a>
        <?php if (($so['status'] ?? '') === 'draft'): ?>
            <?php /* Only while it is a draft. Once it has been submitted the question
                     is no longer draft-or-send, and a Submit button on a shipped order
                     would be an invitation to break something. */ ?>
            <button type="submit" name="so_action" value="draft" class="btn btn--secondary">Save as draft</button>
            <button type="submit" name="so_action" value="submit" class="btn btn--primary">Submit for shipping</button>
        <?php else: ?>
            <button type="submit" class="btn btn--primary">Save Changes</button>
        <?php endif; ?>
    </div>

</div>
</form>

<!--
    The job binder, OUTSIDE the form above - HTML forms cannot nest, and this one needs
    its own multipart post. It writes to the same endpoint the saved order page uses, so
    there is one upload path rather than two that have to be kept in step.
-->
<?php
// A variable, not a view-local function. Two views declaring the same function name in
// one request is a fatal error that renders half a page and reads as a broken layout -
// see CLAUDE.md. show.php uses $jbInp for the same thing.
$soEditDocInp = 'width:100%;padding:.5rem .65rem;font-size:.9rem;font-family:inherit;border:1px solid #d1d5db;'
              . 'border-radius:5px;box-sizing:border-box;background:#fff;color:#111';
?>

<div id="documents" style="margin-top:1.5rem">
    <div style="border-bottom:2px solid #d1d5db;padding-bottom:.5rem;margin-bottom:1rem">
        <span style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280">
            Documents
        </span>
        <?php if (!empty($jobDocuments)): ?>
            <span style="font-size:.78rem;color:#9ca3af">&nbsp;— <?= count($jobDocuments) ?> on file</span>
        <?php endif; ?>
    </div>

    <?php if (empty($jobDocuments)): ?>
        <div class="text-muted" style="font-size:.88rem;margin-bottom:1rem">
            Nothing filed yet. The customer's PO belongs here — it stays with this job
            after it is invoiced.
        </div>
    <?php else: ?>
        <table style="width:100%;border-collapse:collapse;margin-bottom:1rem">
            <?php foreach ($jobDocuments as $jd): ?>
                <tr style="border-bottom:1px solid #eef0f3">
                    <td style="padding:.5rem .6rem .5rem 0;font-size:.85rem;width:9rem;color:#6b7280">
                        <?= e(\App\Services\JobDocumentService::TYPES[$jd['doc_type']] ?? $jd['doc_type']) ?>
                    </td>
                    <td style="padding:.5rem 0;font-size:.9rem">
                        <a href="<?= e($jd['file_path']) ?>" target="_blank" rel="noopener"
                           style="color:#0A3D91;font-weight:600;text-decoration:none">
                            <?= e($jd['title'] ?: $jd['file_name']) ?>
                        </a>
                        <?php if (!empty($jd['reference_num'])): ?>
                            <span style="color:#9ca3af;font-size:.82rem">&nbsp;· <?= e($jd['reference_num']) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <div class="card" style="padding:1.25rem;background:#f8fafc">
        <div style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.8rem">
            File a document
        </div>
        <form method="POST" action="/sales-orders/<?= (int)$so['id'] ?>/documents" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <table style="width:100%;border-collapse:collapse">
                <tr>
                    <td style="padding:.3rem .6rem .3rem 0;font-size:.85rem;color:#6b7280;width:7rem">Kind</td>
                    <td style="padding:.3rem 0;width:40%">
                        <select name="doc_type" style="<?= $soEditDocInp ?>">
                            <?php foreach (\App\Services\JobDocumentService::TYPES as $typeKey => $typeLabel): ?>
                                <option value="<?= e($typeKey) ?>"><?= e($typeLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td style="padding:.3rem .6rem;font-size:.85rem;color:#6b7280;width:7rem">Reference</td>
                    <td style="padding:.3rem 0">
                        <input type="text" name="reference_num" maxlength="100"
                               value="<?= e($so['po_number'] ?? '') ?>" style="<?= $soEditDocInp ?>">
                    </td>
                </tr>
                <tr>
                    <td style="padding:.3rem .6rem .3rem 0;font-size:.85rem;color:#6b7280">File</td>
                    <td colspan="3" style="padding:.3rem 0">
                        <input type="file" name="document_file[]" multiple required style="<?= $soEditDocInp ?>;padding:.4rem">
                    </td>
                </tr>
            </table>
            <div style="margin-top:.85rem;text-align:right">
                <button type="submit" class="btn btn--secondary btn--sm">File it</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var sel  = document.getElementById('soChannel');
    var cell = document.getElementById('mktTaxCell');
    var ref  = document.getElementById('soChannelRef');
    var hint = document.getElementById('chHint');
    if (!sel) return;
    function sync() {
        var amazon = sel.value === 'amazon';
        if (cell) cell.style.display = amazon ? '' : 'none';
        var feeCell = document.getElementById('mktFeeCell');
        if (feeCell) feeCell.style.display = amazon ? '' : 'none';
        if (ref)  ref.closest('td').style.display = (amazon || sel.value === 'website') ? '' : 'none';
        if (hint) hint.textContent = amazon
            ? 'No sales tax will be charged — Amazon collected and remits it.'
            : (sel.value === 'website' ? 'Tax is calculated from the ship-to address.' : '');
    }
    sel.addEventListener('change', sync);
    sync();
})();

var EXISTING_LINES = <?= json_encode(array_values($soGoodsLines), JSON_HEX_TAG) ?>;
</script>

<?php
function soInpStyle2(string $type): string {
    return match($type) {
        'inp' => 'width:100%;padding:.62rem .75rem;font-size:.95rem;font-family:inherit;line-height:1.45;background:#fff;color:#111;border:1px solid #d1d5db;border-radius:6px;box-sizing:border-box',
        'lbl' => 'font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.4rem',
        'th'  => 'padding:.6rem .75rem;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;white-space:nowrap;text-align:left',
        default => '',
    };
}
?>

<script>
(function(){
    function fmt(n){return '$'+(parseFloat(n)||0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g,',');}
    function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');}

    const INP  = 'width:100%;padding:.35rem .5rem;font-size:.9rem;font-family:inherit;background:#fff;border:1px solid #d1d5db;border-radius:5px;box-sizing:border-box';
    const INP_R= INP+';text-align:right';

    function addLine(d){
        d=d||{};
        const tr=document.createElement('tr');
        tr.className='so-line';
        tr.style.borderBottom='1px solid #e5e7eb';
        tr.innerHTML=`
            <td style="padding:.3rem .4rem;vertical-align:middle;position:relative">
                <input type="hidden" name="line_product_id[]" class="f-pid" value="${esc(d.product_id||'')}">
                <input type="text" name="line_item[]" value="${esc(d.sku||d.quickbooks_item||'')}" class="f-code" placeholder="Item" autocomplete="off" style="${INP}">
                <div class="f-drop" style="display:none;position:absolute;top:100%;left:0;min-width:440px;z-index:9999;margin-top:1px;background:#fff;border:1px solid #c8c8c8;border-radius:6px;box-shadow:0 8px 28px rgba(0,0,0,.18);max-height:300px;overflow-y:auto"></div>
            </td>
            <td style="padding:.3rem .4rem;vertical-align:middle"><input type="text" name="line_desc[]" value="${esc(d.description||d.product_name||'')}" class="f-desc" placeholder="Description" style="${INP}"></td>
            <td style="padding:.3rem .4rem;vertical-align:middle"><input type="number" name="line_qty[]"      value="${d.qty_ordered!=null?parseInt(d.qty_ordered):1}" class="f-qty f-calc" step="1" min="0" style="${INP_R}"></td>
            <td style="padding:.3rem .4rem;vertical-align:middle"><input type="text"   name="line_uom[]"      value="${esc(d.uom_code||'')}" class="f-uom" maxlength="10" style="${INP}"></td>
            <td style="padding:.3rem .4rem;vertical-align:middle"><input type="number" name="line_price[]"    value="${d.unit_price!=null?parseFloat(d.unit_price).toFixed(2):''}" class="f-price f-calc" step="0.01" min="0" placeholder="0.00" style="${INP_R}"></td>
            <td style="padding:.3rem .4rem;vertical-align:middle"><input type="number" name="line_discount[]" value="${d.discount_pct!=null?d.discount_pct:0}" class="f-disc f-calc" step="any" min="0" max="100" style="${INP_R}"></td>
            <td style="padding:.3rem .4rem;vertical-align:middle;text-align:center"><input type="checkbox" name="line_taxable[]" value="1" class="f-tax f-calc" ${d.taxable==1?'checked':''}></td>
            <td style="padding:.3rem .75rem;vertical-align:middle;text-align:right;font-family:monospace;font-weight:600" class="f-total">$0.00</td>
            <td style="padding:.3rem .4rem;vertical-align:middle"><button type="button" class="so-del-btn" title="Remove" style="background:none;border:none;font-size:1.3rem;cursor:pointer;color:#9ca3af;line-height:1;padding:0 .3rem;border-radius:4px">&times;</button></td>`;
        document.getElementById('lineBody').appendChild(tr);
        recalcRow(tr); bindRow(tr);
    }

    function recalcRow(tr){
        const q=parseFloat(tr.querySelector('.f-qty').value)||0;
        const p=parseFloat(tr.querySelector('.f-price').value)||0;
        const d=parseFloat(tr.querySelector('.f-disc').value)||0;
        tr.querySelector('.f-total').textContent=fmt(q*p*(1-d/100));
    }
    function recalcAll(){
        let sub=0,disc=0,taxable=0;
        document.querySelectorAll('.so-line').forEach(tr=>{
            const q=parseFloat(tr.querySelector('.f-qty').value)||0;
            const p=parseFloat(tr.querySelector('.f-price').value)||0;
            const d=parseFloat(tr.querySelector('.f-disc').value)||0;
            const gross=q*p,da=gross*d/100,net=gross-da;
            sub+=gross;disc+=da;
            if(tr.querySelector('.f-tax').checked) taxable+=net;
        });
        const rate=parseFloat(document.getElementById('taxRatePct').value)||0;
        const taxAmt=taxable*rate/100;

        /*
         * Shipping is part of the total.
         *
         * It saves as a line, so the stored total has always included it — but the
         * figure on the screen was adding up the line ROWS only, and the shipping
         * field is not one of them. The number you read and the number that saved
         * disagreed, which is the worst way for a total to be wrong.
         *
         * Subtotal stays goods-only, which is what a subtotal means.
         */
        const ship=parseFloat(document.getElementById('fShipAmount').value)||0;

        document.getElementById('fSubtotal').textContent=fmt(sub);
        const dr=document.getElementById('fDiscRow');
        dr.style.display=disc>0?'':'none';
        if(disc>0) document.getElementById('fDiscount').textContent='−'+fmt(disc);
        document.getElementById('fTax').textContent=fmt(taxAmt);
        document.getElementById('fTotal').textContent=fmt(sub-disc+taxAmt+ship);
    }
    function bindRow(tr){
        tr.querySelectorAll('.f-calc').forEach(el=>{
            el.addEventListener('input',()=>{recalcRow(tr);recalcAll();});
            el.addEventListener('change',()=>{recalcRow(tr);recalcAll();});
        });
        tr.querySelector('.so-del-btn').addEventListener('click',()=>{tr.remove();recalcAll();});
        const codeEl=tr.querySelector('.f-code'),dropEl=tr.querySelector('.f-drop');
        let timer;
        codeEl.addEventListener('input',function(){
            clearTimeout(timer);
            const q=this.value.trim();
            if(!q){dropEl.style.display='none';return;}
            timer=setTimeout(()=>{
                // Whoever is buying decides the price, so the lookup is told.
                const cust=document.getElementById('customerId');
                const who=cust && cust.value ? '&customer_id='+encodeURIComponent(cust.value) : '';
                fetch('/products/autocomplete?q='+encodeURIComponent(q)+who)
                    .then(r=>r.json()).then(items=>showDrop(items,dropEl,tr)).catch(()=>{});
            },220);
        });
        codeEl.addEventListener('blur',()=>setTimeout(()=>{dropEl.style.display='none';},180));
    }
    function showDrop(items,dropEl,tr){
        dropEl.innerHTML='';
        if(!items||!items.length){dropEl.style.display='none';return;}
        items.forEach(p=>{
            const d=document.createElement('div');
            d.style.cssText='display:flex;align-items:baseline;gap:.75rem;padding:.6rem 1rem;cursor:pointer;border-bottom:1px solid #eee;font-size:.9rem;color:#111';
            d.innerHTML=`<span style="font-weight:700;font-family:monospace;color:#0A3D91;min-width:110px;flex-shrink:0">${esc(p.sku||p.quickbooks_item)}</span>`+
                `<span style="flex:1;color:#222">${esc(p.name||'')}</span>`+
                (p.price?`<span style="margin-left:auto;color:#6b7280;font-size:.85rem">${fmt(p.price)}`
                    + (p.price_label && p.price_label!=='retail'
                        ? ` <span style="color:${p.price_fell_back?'#b45309':'#0A3D91'}">${esc(p.price_label)}</span>` : '')
                    + `</span>`:'');
            d.addEventListener('mouseover',()=>d.style.background='#f0f4ff');
            d.addEventListener('mouseout', ()=>d.style.background='');
            d.addEventListener('mousedown',e=>{
                e.preventDefault();
                tr.querySelector('.f-code').value=p.sku||p.quickbooks_item;
                tr.querySelector('.f-pid').value=p.id;
                tr.querySelector('.f-desc').value=p.name||'';
                if(p.price) tr.querySelector('.f-price').value=p.price;
                dropEl.style.display='none';
                recalcRow(tr);recalcAll();
                tr.querySelector('.f-qty').focus();
            });
            dropEl.appendChild(d);
        });
        dropEl.style.display='block';
    }

    document.getElementById('taxRateSelect').addEventListener('change',function(){
        document.getElementById('taxRatePct').value=this.options[this.selectedIndex].dataset.rate||0;
        recalcAll();
    });
    document.getElementById('addLineBtn').addEventListener('click',()=>addLine());

    /*
     * Keep one empty row at the bottom.
     *
     * The form drew three and then made you reach for a button, which breaks the
     * rhythm of typing an order — the next line should just be there. Only ever one
     * spare, so a saved order does not collect blanks.
     */
    function ensureSpareRow(){
        const rows=[...document.querySelectorAll('#lineBody tr')];
        if(!rows.length){addLine();return;}
        const last=rows[rows.length-1];
        const used=['.f-code','.f-desc'].some(sel=>(last.querySelector(sel)?.value||'').trim()!=='')
                 || parseFloat(last.querySelector('.f-price')?.value||0)>0;
        if(used) addLine();
    }
    var FREIGHT_MARKUP = <?= json_encode((float)(\App\Core\Config::get('shipping.freight_markup') ?? 20.0)) ?>;
    document.getElementById('lineBody').addEventListener('input',ensureSpareRow);
    document.getElementById('fShipAmount').addEventListener('input',recalcAll);

    /*
     * Ask FedEx what it would cost.
     *
     * Quoted from what is on the form, because the order is not saved yet. The boxes
     * come from the same cartonizer the label screen uses, so the quote and the label
     * are describing the same parcels rather than two guesses.
     */
    (function(){
        const btn=document.getElementById('fQuoteBtn'), out=document.getElementById('fQuoteOut');
        if(!btn) return;

        /*
         * Only offer the quote when a parcel carrier is selected.
         *
         * Nobody can ask FedEx what an LTL pallet costs, and a button that is always
         * going to answer "this is freight" is just a thing to press and be told off
         * by. Freight gets the Keubix reminder instead, which is the actual next step.
         */
        const via=document.getElementById('shipViaSelect');
        const wrap=btn.parentNode;
        function syncQuoteBtn(){
            const opt = via && via.selectedIndex>=0 ? via.options[via.selectedIndex] : null;
            const mode = opt ? (opt.dataset.mode||'') : '';
            const carrier = opt ? (opt.dataset.carrier||'') : '';

            // FedEx only, and only once something is actually chosen. Nothing selected
            // is not a parcel shipment, it is an unanswered question.
            const canQuote = carrier==='fedex';
            wrap.style.display = canQuote ? '' : 'none';

            if(canQuote){
                if(out.textContent.indexOf('Keubix')>-1 || out.textContent.indexOf('Choose')>-1) out.innerHTML='';
                return;
            }
            if(mode==='freight'){
                out.innerHTML='<div style="color:#6b7280">Freight — get the price from Keubix and add $'
                             + FREIGHT_MARKUP.toFixed(2) + '.</div>';
            } else if(!opt || opt.value===''){
                out.innerHTML='<div style="color:#9ca3af">Choose how it ships to price it.</div>';
            } else {
                out.innerHTML='<div style="color:#9ca3af">No quote for this carrier — type the amount.</div>';
            }
        }
        if(via) via.addEventListener('change',syncQuoteBtn);
        syncQuoteBtn();
        btn.addEventListener('click',function(){
            // The order form by id, not the first form on the page. The topbar has a
            // company-switch form above it, so querySelector('form') posted that one —
            // no customer, no lines, no address, and a quote that could only ever
            // answer "add the delivery ZIP code first".
            const fd=new FormData(document.getElementById('soForm'));
            btn.disabled=true; btn.textContent='Asking FedEx…';
            out.innerHTML='';
            fetch('/sales-orders/quote-shipping',{method:'POST',body:fd})
                .then(r=>r.json())
                .then(d=>{
                    btn.disabled=false; btn.textContent='Get FedEx quote';
                    if(!d.ok){ out.innerHTML='<div style="color:#b45309">'+esc(d.message||'No quote.')+'</div>'; return; }
                    let h='<div style="color:#6b7280;margin-bottom:.3rem">'+d.boxes+' box(es), '+d.weight+' lb — '
                        +'FedEx price plus $'+Number(d.markup).toFixed(2)+'. Click one to use it.</div>';
                    if(d.sandbox){
                        h+='<div style="color:#b45309;margin-bottom:.3rem">Sandbox rates — list price, not '
                          +'USSC\u2019s negotiated account rates. Expect these to be high.</div>';
                    }
                    d.rates.forEach(function(r){
                        h+='<div class="q-rate" data-charge="'+r.charge+'" style="display:flex;justify-content:space-between;'
                          +'gap:1rem;padding:.3rem .45rem;cursor:pointer;border-radius:5px">'
                          +'<span>'+esc(r.label)+'</span>'
                          +'<span style="font-family:monospace"><span style="color:#9ca3af">'+fmt(r.cost)+'</span> → <strong>'+fmt(r.charge)+'</strong></span>'
                          +'</div>';
                    });
                    out.innerHTML=h;
                    out.querySelectorAll('.q-rate').forEach(function(el){
                        el.addEventListener('mouseover',()=>el.style.background='#eff6ff');
                        el.addEventListener('mouseout', ()=>el.style.background='');
                        el.addEventListener('click',function(){
                            document.getElementById('fShipAmount').value=Number(this.dataset.charge).toFixed(2);
                            out.innerHTML='<div style="color:#166534">Shipping set to '+fmt(this.dataset.charge)+'.</div>';
                            recalcAll();
                        });
                    });
                })
                .catch(()=>{ btn.disabled=false; btn.textContent='Get FedEx quote';
                             out.innerHTML='<div style="color:#b45309">Could not reach the quote service.</div>'; });
        });
    }());


    /* Same-as-billing */
    const sameCb=document.getElementById('sameAsBilling');
    function toggleShip(){
        const s=sameCb.checked;
        document.getElementById('shipEditBox').style.display=s?'none':'block';
        document.getElementById('shipSameBox').style.display=s?'block':'none';
    }
    sameCb.addEventListener('change',toggleShip);

    /* Load existing lines */
    if(EXISTING_LINES && EXISTING_LINES.length){
        EXISTING_LINES.forEach(function(l){ addLine(l); });
    } else {
        addLine();addLine();addLine();
    }
    recalcAll();
})();
</script>

<script>
/* Show the freight carrier only when the order is going freight. The ship mode is
   already on each Ship Via option as data-mode, so nothing new has to be looked up. */
(function () {
    var via  = document.querySelector('select[name="ship_via_id"]');
    var wrap = document.getElementById('freightCarrierWrap');
    if (!via || !wrap) { return; }
    function sync() {
        var opt = via.options[via.selectedIndex];
        wrap.style.display = (opt && opt.getAttribute('data-mode') === 'freight') ? '' : 'none';
    }
    via.addEventListener('change', sync);
    sync();
}());
</script>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
