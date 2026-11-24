<?php ob_start(); ?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/leads/<?= (int)$lead['id'] ?>" class="back-link">Back to the lead</a>
        <h1 class="page-title">This company may already exist</h1>
    </div>
</div>

<div style="max-width:760px">

<div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:1rem 1.15rem;margin-bottom:1.25rem">
    <div style="font-weight:600;color:#92400e;margin-bottom:.35rem">
        <?= count($duplicates) ?> similar customer<?= count($duplicates) === 1 ? '' : 's' ?> found
    </div>
    <div style="font-size:.86rem;color:#92400e">
        Converting <strong><?= e($lead['company_name']) ?></strong> would create a new customer.
        If one of these is the same company, attach the lead to it instead — its quotes and
        opportunities come across, and the orders stay on one account.
    </div>
</div>

<div class="card" style="padding:0;overflow:hidden;margin-bottom:1.25rem">
    <table style="width:100%;border-collapse:collapse">
        <tbody>
        <?php foreach ($duplicates as $dup): ?>
            <tr style="border-bottom:1px solid #f1f2f5">
                <td style="padding:.75rem 1.1rem">
                    <?php if ($dup['visible'] && $dup['id'] !== null): ?>
                        <a href="/customers/<?= (int)$dup['id'] ?>" target="_blank" rel="noopener"
                           style="color:#0A3D91;font-weight:600;text-decoration:none"><?= e($dup['company_name']) ?></a>
                    <?php else: ?>
                        <span style="font-weight:600"><?= e($dup['company_name']) ?></span>
                    <?php endif; ?>
                    <?php if (!$dup['is_active']): ?>
                        <span class="badge badge--neutral" style="margin-left:.35rem">Inactive</span>
                    <?php endif; ?>
                    <div style="font-size:.82rem;color:#6b7280;margin-top:.15rem">
                        <?php if ($dup['visible']): ?>
                            <?= e($dup['email'] ?: '') ?><?= $dup['email'] && $dup['phone'] ? ' · ' : '' ?><?= e($dup['phone'] ?: '') ?>
                            <?php if (!empty($dup['sales_rep_name'])): ?>
                                <?= ($dup['email'] || $dup['phone']) ? ' · ' : '' ?>rep <?= e($dup['sales_rep_name']) ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php /* Redacted by CustomerService::possibleDuplicates(). A rep learns the
                                     company exists and who to ask, but gets no contact details, no link,
                                     and no button — attaching their lead to another rep's account is not
                                     theirs to do. */ ?>
                            <em>Assigned to <?= e($dup['sales_rep_name'] ?: 'another rep') ?> — ask the office before adding it again</em>
                        <?php endif; ?>
                    </div>
                </td>
                <td style="padding:.75rem 1.1rem;text-align:right;white-space:nowrap">
                    <?php if ($dup['visible'] && $dup['id'] !== null): ?>
                        <form method="POST" action="/leads/<?= (int)$lead['id'] ?>/convert" style="display:inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="link_customer_id" value="<?= (int)$dup['id'] ?>">
                            <button type="submit" class="btn btn--primary btn--sm">This is the one</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card" style="padding:1rem 1.15rem">
    <div style="font-weight:600;color:#111;margin-bottom:.3rem">None of these is the same company</div>
    <div style="font-size:.86rem;color:#6b7280;margin-bottom:.75rem">
        A new customer will be created from this lead. Products are referenced from seven tables
        and merging two customer records later is not something the application can do for you,
        so it is worth being sure now.
    </div>
    <form method="POST" action="/leads/<?= (int)$lead['id'] ?>/convert">
        <?= csrf_field() ?>
        <?php // Present only after the warning, so creating a duplicate is always deliberate. ?>
        <input type="hidden" name="confirm_duplicate" value="1">
        <button type="submit" class="btn btn--secondary btn--sm">Create a new customer anyway</button>
        <a href="/leads/<?= (int)$lead['id'] ?>" class="btn btn--secondary btn--sm" style="margin-left:.4rem">Cancel</a>
    </form>
</div>

</div>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
