<?php ob_start();
$isEdit = $opp !== null;
$action = $isEdit ? '/opportunities/' . (int)$opp['id'] . '/edit' : '/opportunities';
$val    = fn(string $k) => e($opp[$k] ?? '');

$defaultProb = ['prospecting' => 20, 'proposal' => 50, 'negotiation' => 75, 'closed_won' => 100, 'closed_lost' => 0];
?>

<div class="page-header">
    <div class="page-header__left">
        <a href="/pipeline" class="back-link">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Pipeline
        </a>
        <h1 class="page-title"><?= e($title) ?></h1>
    </div>
</div>

<form method="POST" action="<?= $action ?>">
    <?php if ($lead): ?>
        <input type="hidden" name="lead_id" value="<?= (int)$lead['id'] ?>">
    <?php endif; ?>
    <?php if ($customer): ?>
        <input type="hidden" name="customer_id" value="<?= (int)$customer['id'] ?>">
    <?php endif; ?>

<div style="max-width:900px">
    <div class="card" style="padding:1.5rem">
            <h3 class="card__section-title">Opportunity Details</h3>
            <table style="width:100%;border-collapse:collapse">
                <tr>
                    <td colspan="2" style="padding:.5rem 0 .5rem 0;vertical-align:top">
                        <label class="form-label">Opportunity Name <span style="color:#dc2626">*</span></label>
                        <input type="text" name="name" required value="<?= $val('name') ?>" class="input" style="width:100%;box-sizing:border-box" placeholder="e.g. Acme Corp — Epoxy Flooring Project">
                    </td>
                </tr>
                <tr>
                    <td style="padding:.5rem .75rem .5rem 0;width:50%;vertical-align:top">
                        <label class="form-label">Expected Value ($)</label>
                        <input type="number" name="expected_value" step="0.01" min="0" value="<?= $val('expected_value') ?: '0' ?>" class="input" style="width:100%;box-sizing:border-box">
                    </td>
                    <td style="padding:.5rem 0 .5rem .75rem;width:50%;vertical-align:top">
                        <label class="form-label">Expected Close Date</label>
                        <input type="date" name="expected_close" value="<?= $val('expected_close') ?>" class="input" style="width:100%;box-sizing:border-box">
                    </td>
                </tr>
                <tr>
                    <td style="padding:.5rem .75rem .5rem 0;vertical-align:top">
                        <label class="form-label">Stage</label>
                        <select name="stage" id="stageSelect" class="input" style="width:100%;box-sizing:border-box" onchange="updateProb(this.value)">
                            <?php foreach (['prospecting' => 'Prospecting', 'proposal' => 'Proposal', 'negotiation' => 'Negotiation', 'closed_won' => 'Closed Won', 'closed_lost' => 'Closed Lost'] as $s => $sl): ?>
                                <option value="<?= $s ?>" <?= ($opp['stage'] ?? 'prospecting') === $s ? 'selected' : '' ?>><?= $sl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td style="padding:.5rem 0 .5rem .75rem;vertical-align:top">
                        <label class="form-label">Probability (%)</label>
                        <input type="number" name="probability" id="probInput" min="0" max="100" value="<?= $val('probability') ?: '20' ?>" class="input" style="width:100%;box-sizing:border-box">
                    </td>
                </tr>
                <tr>
                    <td style="padding:.5rem .75rem .5rem 0;vertical-align:top">
                        <label class="form-label">Assigned Rep</label>
                        <select name="rep_id" class="input" style="width:100%;box-sizing:border-box">
                            <option value="">— Unassigned —</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= (int)$u['id'] ?>" <?= (string)($opp['rep_id'] ?? '') === (string)$u['id'] ? 'selected' : '' ?>>
                                    <?= e($u['first_name'] . ' ' . $u['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td style="padding:.5rem 0 .5rem .75rem;vertical-align:top">
                        <?php if ($lead): ?>
                            <label class="form-label">Lead</label>
                            <div style="padding:.62rem .75rem;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:6px;font-size:.95rem"><?= e($lead['company_name']) ?></div>
                        <?php elseif ($customer): ?>
                            <label class="form-label">Customer</label>
                            <div style="padding:.62rem .75rem;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:6px;font-size:.95rem"><?= e($customer['company_name']) ?></div>
                        <?php elseif ($isEdit && $opp['customer_name']): ?>
                            <label class="form-label">Customer</label>
                            <div style="padding:.62rem .75rem;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:6px;font-size:.95rem"><?= e($opp['customer_name']) ?></div>
                        <?php elseif ($isEdit && $opp['lead_name']): ?>
                            <label class="form-label">Lead</label>
                            <div style="padding:.62rem .75rem;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:6px;font-size:.95rem"><?= e($opp['lead_name']) ?></div>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <?php if ($isEdit && in_array($opp['stage'], ['closed_lost'])): ?>
            <div style="margin-top:1rem">
                <label class="form-label">Lost Reason</label>
                <input type="text" name="lost_reason" value="<?= $val('lost_reason') ?>" class="input" style="width:100%;box-sizing:border-box" placeholder="Price, timing, competition, etc.">
            </div>
            <?php endif; ?>

            <div style="margin-top:1rem">
                <label class="form-label">Notes</label>
                <textarea name="notes" rows="4" class="input" style="width:100%;box-sizing:border-box;resize:vertical"><?= e($opp['notes'] ?? '') ?></textarea>
            </div>
    </div>
</div>

<!-- Save bar -->
<div class="save-bar">
    <a href="/pipeline" class="btn btn--secondary">Cancel</a>
    <button type="submit" class="btn btn--primary"><?= $isEdit ? 'Save Opportunity' : 'Create Opportunity' ?></button>
</div>
</form>

<script>
var defaultProb = <?= json_encode($defaultProb) ?>;
function updateProb(stage) {
    if (defaultProb[stage] !== undefined) {
        document.getElementById('probInput').value = defaultProb[stage];
    }
}
</script>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
