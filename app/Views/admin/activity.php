<?php ob_start(); ?>

<div class="page-header">
    <h1 class="page-title">Activity Log</h1>
    <p class="text-muted" style="margin:.25rem 0 0">
        Everything the system recorded, newest first. Entries cannot be edited or removed —
        that is what makes them worth having.
    </p>
</div>

<div class="card" style="padding:1rem 1.25rem;margin-bottom:1rem">
    <form method="GET" action="/admin/activity" style="display:flex;gap:.6rem;align-items:flex-end;flex-wrap:wrap">
        <div>
            <label class="text-muted" style="display:block;font-size:.78rem;font-weight:600;margin-bottom:.2rem">Event</label>
            <select name="event" style="padding:.45rem .6rem;border:1px solid #d1d5db;border-radius:6px;font:inherit;font-size:.875rem">
                <option value="">All events</option>
                <?php foreach ($eventOpts as $opt): ?>
                    <option value="<?= e($opt) ?>" <?= $filters['event'] === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="text-muted" style="display:block;font-size:.78rem;font-weight:600;margin-bottom:.2rem">Who</label>
            <select name="user" style="padding:.45rem .6rem;border:1px solid #d1d5db;border-radius:6px;font:inherit;font-size:.875rem">
                <option value="">Anyone</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= (int)$u['id'] ?>" <?= (string)$filters['user_id'] === (string)$u['id'] ? 'selected' : '' ?>>
                        <?= e(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn--primary btn--sm">Filter</button>
        <?php if ($filters['event'] !== '' || $filters['user_id'] !== '' || $filters['subject_type'] !== ''): ?>
            <a class="btn btn--secondary btn--sm" href="/admin/activity">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="card" style="padding:0;overflow:hidden">
    <?= view('partials.activity_feed', ['events' => $events, 'total' => count($events)]) ?>
</div>

<?php if ($page > 1 || $hasMore): ?>
    <div style="display:flex;gap:.6rem;justify-content:center;margin-top:1rem">
        <?php
        // The filters have to be carried on the paging links or page 2 silently drops them.
        $qs = static function (int $p) use ($filters): string {
            return '/admin/activity?' . http_build_query(array_filter([
                'page'  => $p > 1 ? $p : null,
                'event' => $filters['event'] ?: null,
                'user'  => $filters['user_id'] ?: null,
                'type'  => $filters['subject_type'] ?: null,
            ], static fn ($v) => $v !== null));
        };
        ?>
        <?php if ($page > 1): ?>
            <a class="btn btn--secondary btn--sm" href="<?= e($qs($page - 1)) ?>">&larr; Newer</a>
        <?php endif; ?>
        <?php if ($hasMore): ?>
            <a class="btn btn--secondary btn--sm" href="<?= e($qs($page + 1)) ?>">Older &rarr;</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
