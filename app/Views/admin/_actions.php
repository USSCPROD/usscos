<?php
/**
 * Row actions for an admin lookup list: Edit, Deactivate/Reactivate, and Delete.
 *
 * Included from inside a foreach, so every variable is prefixed `act` to avoid colliding
 * with the including view's loop variables. Expects:
 *
 *   $actEntity     URL slug, e.g. 'sales-reps'
 *   $actId         row id
 *   $actActive     bool, current is_active
 *   $actEditUrl    where Edit goes
 *   $actLabel      singular human label, e.g. 'sales rep'
 *   $actDeletable  bool, whether a hard delete is ever offered for this entity
 *   $actRefs       int, how many records reference this row
 *
 * Delete is shown only when nothing references the row. Several of these foreign keys
 * are SET NULL, so a delete on a referenced row would succeed and quietly blank the
 * field on historical records — hence deactivate being the visible default.
 */
?>
<a href="<?= e($actEditUrl) ?>" class="btn btn--xs btn--secondary">Edit</a>

<form method="POST" action="/admin/<?= e($actEntity) ?>/<?= (int)$actId ?>/toggle-active" style="display:inline"
      onsubmit="return confirm('<?= $actActive
          ? 'Deactivate this ' . e($actLabel) . '? It will be hidden from new documents. Existing records keep it.'
          : 'Reactivate this ' . e($actLabel) . '?' ?>')">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn--xs btn--secondary"
            title="<?= $actActive ? 'Hide from pickers, keep all history' : 'Make available again' ?>">
        <?= $actActive ? 'Deactivate' : 'Reactivate' ?>
    </button>
</form>

<?php if ($actDeletable): ?>
    <?php if ((int)$actRefs === 0): ?>
        <form method="POST" action="/admin/<?= e($actEntity) ?>/<?= (int)$actId ?>/delete" style="display:inline"
              onsubmit="return confirm('Permanently delete this <?= e($actLabel) ?>? Nothing references it, so this is safe. This cannot be undone.')">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn--xs"
                    style="color:#dc2626;border-color:#fca5a5;background:#fff"
                    title="Not used by any record — safe to remove">Delete</button>
        </form>
    <?php else: ?>
        <span style="font-size:.68rem;color:#9ca3af;padding-left:.3rem"
              title="Used by <?= (int)$actRefs ?> record(s), so deleting would alter them. Deactivate instead.">
            in use (<?= number_format((int)$actRefs) ?>)
        </span>
    <?php endif; ?>
<?php endif; ?>
