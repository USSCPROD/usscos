<?php ob_start(); ?>
<?php
// Variables, not view-local functions — see CLAUDE.md on duplicate declarations.
$rqNum  = fn($n) => rtrim(rtrim(number_format((float)$n, 2), '0'), '.');
$rqTh   = 'padding:.6rem .75rem;font-size:.7rem;font-weight:700;text-transform:uppercase;'
        . 'letter-spacing:.05em;color:#6b7280;text-align:left;white-space:nowrap';
$rqTd   = 'padding:.7rem .75rem;border-top:1px solid #eef0f3;vertical-align:middle';
?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start">
    <div>
        <h1 class="page-title" style="margin:0">Ready to ship</h1>
        <p class="page-subtitle" style="margin:.3rem 0 0">
            What the warehouse has actually pulled, against what was ordered. Releasing an
            order invoices only what is picked — anything still owed stays on the order.
        </p>
    </div>
    <div>
        <a href="/shipping/pull" class="btn btn--secondary">Pull list</a>
        <a href="/shipping" class="btn btn--secondary">Queue</a>
    </div>
</div>

<?php if (empty($orders)): ?>
    <div class="card" style="padding:2rem;text-align:center;color:#6b7280">
        No open orders.
    </div>
<?php else: ?>
    <div class="card" style="padding:0;overflow:hidden">
        <table style="width:100%;border-collapse:collapse">
            <thead style="background:#f8fafc">
                <tr>
                    <th style="<?= $rqTh ?>">Order</th>
                    <th style="<?= $rqTh ?>">Customer</th>
                    <th style="<?= $rqTh ?>;text-align:right">Ordered</th>
                    <th style="<?= $rqTh ?>;text-align:right">Picked</th>
                    <th style="<?= $rqTh ?>">State</th>
                    <th style="<?= $rqTh ?>">Ship by</th>
                    <th style="<?= $rqTh ?>;text-align:right">Value</th>
                    <th style="<?= $rqTh ?>;text-align:right">Decision</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
                <?php
                    $ordered   = (float)$o['qty_ordered'];
                    $picked    = (float)$o['qty_picked'];
                    $shipped   = (float)$o['qty_shipped'];
                    $toShip    = $picked - $shipped;
                    $complete  = $picked >= $ordered && $ordered > 0;
                    $nothing   = $toShip <= 0;
                    $short     = (int)$o['lines_short'] > 0;
                    $held      = str_starts_with((string)($o['pick_note'] ?? ''), 'HOLD:');

                    // Three honest states, and they are not the same thing. A partial that
                    // CAN be completed is just unfinished work; one that cannot is a
                    // decision somebody has to make.
                    if ($complete)      { $state = ['Complete',  '#dcfce7', '#86efac', '#166534']; }
                    elseif ($short)     { $state = ['Short',     '#fef2f2', '#fecaca', '#991b1b']; }
                    elseif ($picked > 0){ $state = ['Part-picked','#fffbeb','#fcd34d', '#92400e']; }
                    else                { $state = ['Not picked','#f3f4f6', '#e5e7eb', '#4b5563']; }
                ?>
                <tr>
                    <td style="<?= $rqTd ?>">
                        <a href="/shipping/<?= (int)$o['id'] ?>/pick" style="color:#0A3D91;font-weight:700;text-decoration:none">
                            #<?= e($o['so_number']) ?>
                        </a>
                        <?php if (($o['priority'] ?? 'normal') !== 'normal'): ?>
                            <span class="badge badge--warning" style="margin-left:.35rem;font-size:.65rem"><?= e(strtoupper($o['priority'])) ?></span>
                        <?php endif; ?>
                        <?php if (($o['channel'] ?? '') === 'amazon'): ?>
                            <span class="badge badge--info" style="margin-left:.35rem;font-size:.65rem">AMZ</span>
                        <?php endif; ?>
                    </td>
                    <td style="<?= $rqTd ?>;font-size:.9rem"><?= e($o['company_name'] ?? '') ?></td>
                    <td style="<?= $rqTd ?>;text-align:right;font-family:monospace"><?= $rqNum($ordered) ?></td>
                    <td style="<?= $rqTd ?>;text-align:right;font-family:monospace;font-weight:700"><?= $rqNum($picked) ?></td>
                    <td style="<?= $rqTd ?>">
                        <span style="display:inline-block;padding:.2rem .55rem;border-radius:999px;font-size:.7rem;font-weight:700;
                                     background:<?= $state[1] ?>;border:1px solid <?= $state[2] ?>;color:<?= $state[3] ?>">
                            <?= $state[0] ?>
                        </span>
                        <?php if ($short): ?>
                            <div style="font-size:.7rem;color:#991b1b;margin-top:.25rem">
                                <?= (int)$o['lines_short'] ?> line<?= (int)$o['lines_short'] === 1 ? '' : 's' ?> cannot be filled
                            </div>
                        <?php endif; ?>
                        <?php if ((int)$o['invoice_count'] > 0): ?>
                            <div style="font-size:.7rem;color:#6b7280;margin-top:.25rem">
                                <?= (int)$o['invoice_count'] ?> invoice<?= (int)$o['invoice_count'] === 1 ? '' : 's' ?> already
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="<?= $rqTd ?>;font-size:.85rem;color:#6b7280;white-space:nowrap">
                        <?= !empty($o['ship_by']) ? e(date('j M', strtotime((string)$o['ship_by']))) : '—' ?>
                    </td>
                    <td style="<?= $rqTd ?>;text-align:right;font-family:monospace;font-size:.9rem">
                        $<?= number_format((float)$o['total_amount'], 2) ?>
                    </td>
                    <td style="<?= $rqTd ?>;text-align:right;white-space:nowrap">
                        <?php if ($nothing): ?>
                            <span class="text-muted" style="font-size:.8rem">nothing picked yet</span>
                        <?php else: ?>
                            <form method="POST" action="/shipping/<?= (int)$o['id'] ?>/release" style="display:inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn--primary btn--sm"
                                        onclick="return confirm('<?= $complete
                                            ? 'Invoice and ship the whole order?'
                                            : 'Ship the ' . $rqNum($toShip) . ' picked and leave the rest owed?' ?>')">
                                    <?= $complete ? 'Ship all' : 'Ship ' . $rqNum($toShip) ?>
                                </button>
                            </form>
                            <button type="button" class="btn btn--secondary btn--sm"
                                    onclick="document.getElementById('hold-<?= (int)$o['id'] ?>').style.display='table-row'">
                                Hold
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr id="hold-<?= (int)$o['id'] ?>" style="display:none;background:#fffbeb">
                    <td colspan="8" style="padding:.7rem 1rem;border-top:1px solid #fcd34d">
                        <form method="POST" action="/shipping/<?= (int)$o['id'] ?>/hold"
                              style="display:flex;gap:.5rem;align-items:center">
                            <?= csrf_field() ?>
                            <span style="font-size:.8rem;color:#92400e;font-weight:600;white-space:nowrap">Hold #<?= e($o['so_number']) ?> because</span>
                            <input type="text" name="reason" maxlength="200" required
                                   placeholder="waiting on the rest of the DSW, customer asked us to hold…"
                                   style="flex:1;padding:.45rem .6rem;border:1px solid #fcd34d;border-radius:5px;font-size:.9rem">
                            <button type="submit" class="btn btn--secondary btn--sm">Hold it</button>
                        </form>
                    </td>
                </tr>
                <?php if ($held): ?>
                    <tr style="background:#fffbeb">
                        <td colspan="8" style="padding:.5rem 1rem;border-top:1px solid #fcd34d;font-size:.82rem;color:#92400e">
                            <?= e($o['pick_note']) ?>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
