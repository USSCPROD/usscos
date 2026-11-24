<?php ob_start(); ?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/admin" class="back-link">Admin</a>
        <h1 class="page-title">Email intake</h1>
    </div>
</div>

<p class="text-muted" style="margin:-.5rem 0 1rem;max-width:720px;font-size:.9rem">
    Mail BCC'd to the CRM mailbox. Anything matched to a customer or lead is already on that
    record's timeline. Anything below that could not be placed is waiting for somebody to say
    where it belongs — an email nobody places is an email the CRM has quietly lost.
</p>

<div class="card" style="padding:.75rem 1.1rem;margin-bottom:1rem;display:flex;gap:.4rem;flex-wrap:wrap">
    <?php foreach (['unmatched' => 'Needs placing', 'matched' => 'Placed', 'ignored' => 'Ignored', 'all' => 'Everything'] as $tabKey => $tabLabel): ?>
        <a href="/admin/email-intake?show=<?= e($tabKey) ?>"
           class="btn btn--sm <?= $show === $tabKey ? 'btn--primary' : 'btn--secondary' ?>">
            <?= e($tabLabel) ?><?= $tabKey === 'unmatched' && $unmatchedCount > 0 ? ' (' . (int)$unmatchedCount . ')' : '' ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($emails === []): ?>
    <div class="card" style="padding:1.5rem 1.1rem;color:#6b7280;font-size:.9rem">
        <?= $show === 'unmatched'
            ? 'Nothing waiting — every email received has been placed on a record.'
            : 'Nothing here.' ?>
    </div>
<?php else: ?>
    <?php foreach ($emails as $em): ?>
        <div class="card" style="padding:0;overflow:hidden;margin-bottom:.85rem">
            <div style="padding:.75rem 1.1rem;border-bottom:1px solid #f1f2f5;background:#fafafa">
                <div style="font-weight:700;color:#111;font-size:.92rem">
                    <?= e($em['subject'] ?: '(no subject)') ?>
                </div>
                <div class="text-muted" style="font-size:.8rem;margin-top:.15rem">
                    <?= e($em['direction'] === 'outbound' ? 'Sent to' : 'From') ?>
                    <strong><?= e($em['direction'] === 'outbound' ? ($em['to_emails'] ?: '—') : $em['from_email']) ?></strong>
                    <?php if (!empty($em['sent_at'])): ?>
                        &middot; <?= date('M j, Y g:i a', strtotime((string)$em['sent_at'])) ?>
                    <?php endif; ?>
                    <?php if (!empty($em['match_type'])): ?>
                        &middot; matched on <?= e($em['matched_on'] ?: '?') ?>
                        <?php if ($em['match_type'] === 'domain'): ?>
                            <span style="color:#b45309;font-weight:600">(domain only)</span>
                        <?php elseif ($em['match_type'] === 'manual'): ?>
                            <span style="color:#6b7280">(by hand)</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php if (!empty($em['customer_name']) || !empty($em['lead_name'])): ?>
                    <div style="font-size:.82rem;margin-top:.2rem">
                        <?php if (!empty($em['customer_name'])): ?>
                            <a href="/customers/<?= (int)$em['matched_customer_id'] ?>" style="color:#0A3D91"><?= e($em['customer_name']) ?></a>
                        <?php endif; ?>
                        <?php if (!empty($em['lead_name'])): ?>
                            <?= !empty($em['customer_name']) ? ' &middot; ' : '' ?>
                            <a href="/leads/<?= (int)$em['matched_lead_id'] ?>" style="color:#0A3D91"><?= e($em['lead_name']) ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($em['body'])): ?>
                <div style="padding:.75rem 1.1rem;font-size:.86rem;color:#374151;line-height:1.55;white-space:pre-wrap;max-height:11rem;overflow:auto"><?= e(str_limit((string)$em['body'], 1200)) ?></div>
            <?php endif; ?>

            <?php if ($em['status'] === 'unmatched'): ?>
                <div style="padding:.75rem 1.1rem;border-top:1px solid #f1f2f5;background:#fffbeb">
                    <?php /* Ids rather than a name search: what gets attached here writes to a
                             timeline, and a near-match on a name would put somebody's email on the
                             wrong account with nothing saying so. Open the record, take its id. */ ?>
                    <form method="POST" action="/admin/email-intake/<?= (int)$em['id'] ?>/attach"
                          style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
                        <?= csrf_field() ?>
                        <label class="text-muted" style="font-size:.8rem">Customer ID</label>
                        <input type="number" name="customer_id" min="1" style="width:7.5rem;padding:.35rem .5rem;border:1px solid #d1d5db;border-radius:5px">
                        <label class="text-muted" style="font-size:.8rem">or Lead ID</label>
                        <input type="number" name="lead_id" min="1" style="width:7.5rem;padding:.35rem .5rem;border:1px solid #d1d5db;border-radius:5px">
                        <button type="submit" class="btn btn--primary btn--sm">Attach</button>
                    </form>
                    <form method="POST" action="/admin/email-intake/<?= (int)$em['id'] ?>/ignore" style="margin-top:.5rem">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn--secondary btn--sm">Not business mail — ignore</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
