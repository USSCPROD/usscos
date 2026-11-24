<?php
/**
 * "Log a call, email, visit or note" — the form a rep uses to record what THEY did.
 *
 * Expects $logAction — where to post, e.g. /leads/143/log.
 * Optional $logPlaceholder.
 *
 * Included inside a page that has already started its buffer: no ob_start(), no layout.
 *
 * WHY IT MATTERS. Until this existed, everything on a lead's timeline was something the
 * system did. A rep who rang a lead and got no answer had nowhere to put that, so the
 * single most common piece of sales information in the business was not recorded
 * anywhere — and the next person rang them again.
 *
 * The only writable field was `notes`, a free-text blob the website webhook also appends
 * artwork links and address lines to. Typing into a field that another process is writing
 * to is not a record of anything.
 */
$logAction      = $logAction      ?? '';
$logPlaceholder = $logPlaceholder ?? 'What happened? Who you spoke to, what they said, what you agreed.';
?>

<form method="POST" action="<?= e($logAction) ?>"
      style="padding:1rem 1.1rem;border-bottom:1px solid #e5e7eb;background:#fafafa">
    <?= csrf_field() ?>
    <div style="display:flex;gap:.6rem;margin-bottom:.6rem;flex-wrap:wrap">
        <?php
        /* Named $logKey/$logLabel, never $val. A closure accessor called $val is declared
           at the top of several views, and a foreach key of the same name overwrites it —
           PHP then treats $val('x') as a variable function call, which on the Add/Edit
           User form became "Call to undefined function readonly()" and a page that
           rendered halfway and stopped. See CLAUDE.md. */
        foreach (['call' => 'Call', 'email' => 'Email', 'meeting' => 'Meeting', 'note' => 'Note']
                 as $logKey => $logLabel): ?>
            <label style="display:inline-flex;align-items:center;gap:.3rem;font-size:.85rem;font-weight:600;
                          cursor:pointer;padding:.3rem .75rem;border:1px solid #d1d5db;border-radius:5px;
                          color:#374151;background:#fff">
                <input type="radio" name="note_type" value="<?= e($logKey) ?>"
                       <?= $logKey === 'call' ? 'checked' : '' ?> style="accent-color:#222b59">
                <?= e($logLabel) ?>
            </label>
        <?php endforeach; ?>
    </div>

    <textarea name="body" rows="2" required placeholder="<?= e($logPlaceholder) ?>"
              style="width:100%;padding:.6rem .75rem;border:1px solid #d1d5db;border-radius:6px;
                     font-family:inherit;font-size:.9rem;resize:vertical;box-sizing:border-box"></textarea>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:.5rem;gap:.75rem">
        <span class="text-muted" style="font-size:.78rem">
            Saved to the timeline against your name. Entries are not editable.
        </span>
        <button type="submit" class="btn btn--primary btn--sm">Log it</button>
    </div>
</form>
