<?php ob_start(); ?>
<?php
// Variables, not view-local functions. Two views declaring the same function name in one
// request is a fatal that renders half a page — see CLAUDE.md.
$lblCard = 'background:#fff;border:1px solid #d1d5db;border-radius:8px;padding:1.25rem;margin-bottom:1.25rem';
$lblHead = 'font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6b7280;'
         . 'border-bottom:2px solid #d1d5db;padding-bottom:.5rem;margin-bottom:1rem';
$lblInp  = 'width:100%;padding:.55rem .7rem;font-size:.95rem;font-family:inherit;border:1px solid #d1d5db;'
         . 'border-radius:6px;box-sizing:border-box;background:#fff;color:#111';
$lblLbl  = 'font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;margin-bottom:.35rem';

$plan     = $plan     ?? ['packages' => [], 'freight_required' => false, 'reasons' => [], 'missing' => [], 'total_weight' => null];
$packages = $plan['packages'] ?? [];
$suggestedWeight   = $plan['total_weight'];
$suggestedPackages = max(1, count($packages));
?>

<div class="page-header">
    <div class="page-title">Label SO #<?= e($order['so_number']) ?></div>
    <div class="text-muted" style="font-size:.9rem">
        <?= e($order['company_name'] ?? '') ?>
        <?php if (!empty($invoice)): ?>
            &nbsp;·&nbsp; Invoice <?= e($invoice['invoice_number']) ?>
        <?php endif; ?>
    </div>
</div>

<a href="/shipping/<?= (int)$order['id'] ?>/pack" style="font-size:.85rem;color:#0A3D91;text-decoration:none">&larr; Back to packing</a>

<?php if ($sandbox): ?>
    <div class="alert" style="background:#fffbeb;border:1px solid #fcd34d;color:#92400e;margin-top:1rem">
        <strong>Sandbox.</strong> Labels created here are not valid for shipping and the tracking
        numbers are not real. Useful for proving the flow, not for putting on a box.
    </div>
<?php endif; ?>

<?php if (!$configured): ?>
    <div class="alert alert--danger" style="margin-top:1rem">
        FedEx is not configured on this server. The account number and API keys go in
        <code>.env</code> — nothing can be printed until they are there.
    </div>
<?php endif; ?>

<?php if (empty($invoice)): ?>
    <div class="alert" style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;margin-top:1rem">
        <strong>No invoice yet.</strong> FedEx ships against the invoice — it carries the delivery
        address and it is where the tracking number belongs. Ship and invoice the order first,
        or a parcel goes on the road with nothing recording it.
    </div>
<?php endif; ?>

<div style="margin-top:1.25rem">

    <!-- ── What the cartonizer worked out ───────────────────────────────── -->
    <div style="<?= $lblCard ?>">
        <div style="<?= $lblHead ?>">Suggested packing</div>

        <?php if ($plan['freight_required']): ?>
            <div class="alert" style="background:#fffbeb;border:1px solid #fcd34d;color:#92400e;margin-bottom:1rem">
                <strong>This looks like freight, not parcel.</strong>
                <?= e(implode(' ', $plan['reasons'] ?? [])) ?>
                A FedEx parcel label is probably the wrong answer here.
            </div>
        <?php endif; ?>

        <?php if ($packages === []): ?>
            <div class="text-muted" style="font-size:.9rem">
                Nothing to pack, or no box rules for these products.
            </div>
        <?php else: ?>
            <table style="width:100%;border-collapse:collapse;font-size:.9rem">
                <tr style="border-bottom:1px solid #e5e7eb">
                    <th style="text-align:left;padding:.4rem 0;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">Item</th>
                    <th style="text-align:left;padding:.4rem;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">Box</th>
                    <th style="text-align:right;padding:.4rem;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">Qty</th>
                    <th style="text-align:right;padding:.4rem;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">Weight</th>
                    <th style="text-align:right;padding:.4rem 0;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#6b7280">L &times; W &times; H</th>
                </tr>
                <?php foreach ($packages as $pkg): ?>
                    <tr style="border-bottom:1px solid #f3f4f6">
                        <td style="padding:.5rem 0"><?= e($pkg['sku'] ?? '') ?></td>
                        <td style="padding:.5rem"><?= e($pkg['box'] ?? '—') ?></td>
                        <td style="padding:.5rem;text-align:right;font-family:monospace"><?= e((string)($pkg['qty'] ?? '')) ?></td>
                        <td style="padding:.5rem;text-align:right;font-family:monospace">
                            <?= $pkg['weight'] !== null ? number_format((float)$pkg['weight'], 2) . ' lb' : '—' ?>
                        </td>
                        <td style="padding:.5rem 0;text-align:right;font-family:monospace;color:#6b7280">
                            <?= ($pkg['length'] ?? null) !== null
                                ? e($pkg['length'] . ' × ' . $pkg['width'] . ' × ' . $pkg['height'])
                                : '—' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>

        <?php if (!empty($plan['missing'])): ?>
            <div class="alert" style="background:#fffbeb;border:1px solid #fcd34d;color:#92400e;margin-top:1rem;font-size:.88rem">
                <strong>No weight or box on file for:</strong> <?= e(implode(', ', $plan['missing'])) ?>.
                The total below is short by whatever those weigh, so check it on the scale
                before printing — an understated weight is a bill from FedEx later.
            </div>
        <?php endif; ?>
    </div>

    <!-- ── Limited quantity ─────────────────────────────────────────────── -->
    <div class="alert" style="background:#eff6ff;border:1px solid #bfdbfe;color:#1e3a8a;font-size:.88rem">
        <strong>Aerosol goes as Limited Quantity.</strong> The box must carry the limited
        quantity mark before it leaves. USSCOS does not print that mark and does not check
        for it — the person packing does.
    </div>

    <!-- ── Make the label ───────────────────────────────────────────────── -->
    <div style="<?= $lblCard ?>">
        <div style="<?= $lblHead ?>">Create the label</div>

        <form method="POST" action="/shipping/<?= (int)$order['id'] ?>/label">
            <?= csrf_field() ?>
            <input type="hidden" name="invoice_id" value="<?= (int)($invoice['id'] ?? 0) ?>">

            <table style="width:100%;border-collapse:collapse">
                <tr>
                    <td style="padding:0 .75rem .75rem 0;width:33%;vertical-align:top">
                        <div style="<?= $lblLbl ?>">Total weight (lb)</div>
                        <input type="number" step="0.01" min="0.01" name="weight" required
                               value="<?= $suggestedWeight !== null ? e(number_format((float)$suggestedWeight, 2, '.', '')) : '' ?>"
                               style="<?= $lblInp ?>">
                        <div style="font-size:.72rem;color:#9ca3af;margin-top:.3rem">
                            <?= $suggestedWeight !== null
                                ? 'Worked out from the pack sizes. Weigh it if anything is unusual.'
                                : 'No weights on file — put the parcel on the scale.' ?>
                        </div>
                    </td>
                    <td style="padding:0 .75rem .75rem;width:17%;vertical-align:top">
                        <div style="<?= $lblLbl ?>">Packages</div>
                        <input type="number" min="1" max="50" name="packages" required
                               value="<?= (int)$suggestedPackages ?>" style="<?= $lblInp ?>">
                        <div style="font-size:.72rem;color:#9ca3af;margin-top:.3rem">
                            One tracking number each.
                        </div>
                    </td>
                    <td style="padding:0 0 .75rem;width:50%;vertical-align:top">
                        <div style="<?= $lblLbl ?>">Service</div>
                        <select name="service" style="<?= $lblInp ?>">
                            <?php foreach ($services as $svcKey => $svcLabel): ?>
                                <option value="<?= e($svcKey) ?>"><?= e($svcLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div style="font-size:.72rem;color:#9ca3af;margin-top:.3rem">
                            Limited quantity aerosol is ground only — it cannot go by air.
                        </div>
                    </td>
                </tr>
            </table>

            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:.5rem">
                <div class="text-muted" style="font-size:.85rem">
                    <?php if (!empty($invoice)): ?>
                        Shipping to
                        <?= e(trim(($invoice['ship_address_1'] ?? '') . ', ' . ($invoice['ship_city'] ?? '')
                            . ' ' . ($invoice['ship_state'] ?? '') . ' ' . ($invoice['ship_zip'] ?? ''), ' ,')) ?: 'the billing address' ?>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn--primary"
                        <?= (!$configured || empty($invoice)) ? 'disabled' : '' ?>>
                    Create FedEx label
                </button>
            </div>
        </form>
    </div>

    <!-- ── Already labelled ─────────────────────────────────────────────── -->
    <?php if (!empty($existing)): ?>
        <div style="<?= $lblCard ?>">
            <div style="<?= $lblHead ?>">Labels on this shipment</div>
            <table style="width:100%;border-collapse:collapse;font-size:.9rem">
                <?php foreach ($existing as $trk): ?>
                    <tr style="border-bottom:1px solid #f3f4f6">
                        <td style="padding:.55rem 0;font-family:monospace"><?= e($trk['tracking_number'] ?? '') ?></td>
                        <td style="padding:.55rem;color:#6b7280"><?= e($trk['tracking_type'] ?? '') ?></td>
                        <td style="padding:.55rem;color:#6b7280"><?= e((string)($trk['created_at'] ?? '')) ?></td>
                        <td style="padding:.55rem 0;text-align:right">
                            <?php if (!empty($trk['label_path'])): ?>
                                <a href="<?= e($trk['label_path']) ?>" target="_blank" rel="noopener"
                                   class="btn btn--secondary btn--sm">Print</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>

</div>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
