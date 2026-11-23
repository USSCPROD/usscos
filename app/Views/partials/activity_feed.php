<?php
/**
 * The activity timeline, as a list.
 *
 * Expects:
 *   $events - rows from App\Services\Activity (forLead / forCustomer / forSubject / recent)
 *   $total  - optional; the count before the limit, so the view can say what it is hiding
 *
 * No ob_start() and no layout include: this is included INSIDE a page that has already
 * started its own buffer. A second ob_start() here would swallow the parent's output.
 *
 * The helper is guarded and uniquely named. Two views defining one function name in a
 * single request is a fatal error, and because the page's buffer is discarded the symptom
 * is a page that renders halfway and stops - which reads as a broken layout, not a crash.
 */

if (!function_exists('activity_tone')) {
    /**
     * Colour by what the event means, not by which module it came from. Red for money
     * stopped or an account closed, green for money in and goods out, amber for a decision
     * pending - so a timeline can be read at a glance before any of it is read properly.
     */
    function activity_tone(string $event): string
    {
        return match (true) {
            str_contains($event, 'deactivated'),
            str_contains($event, 'declined'),
            str_contains($event, 'cancelled'),
            str_contains($event, 'failed')      => '#b91c1c',
            str_contains($event, 'payment'),
            str_contains($event, 'shipped'),
            str_contains($event, 'accepted'),
            str_contains($event, 'converted'),
            str_contains($event, 'reactivated') => '#15803d',
            str_contains($event, 'sent'),
            str_contains($event, 'approved')    => '#0A3D91',
            str_contains($event, 'created')     => '#6d28d9',
            default                             => '#475569',
        };
    }
}

$events = $events ?? [];
$total  = $total  ?? count($events);
?>

<?php if ($events === []): ?>
    <div style="padding:2rem;text-align:center;color:#9ca3af;font-size:.9rem">
        Nothing recorded yet. Activity appears here as work is done.
    </div>
<?php else: ?>
    <table style="width:100%;border-collapse:collapse">
        <tbody>
        <?php foreach ($events as $ev):
            $tone  = activity_tone((string)$ev['event']);
            // The last segment of the event name, for people: quote.sent -> sent.
            $parts = explode('.', (string)$ev['event']);
            $label = str_replace('_', ' ', end($parts));
            // user_name is empty for anything the website or a script did. Say so, rather
            // than leaving a blank where a person's name goes.
            $who   = trim((string)($ev['user_name'] ?? '')) ?: 'Website';
        ?>
            <tr style="border-bottom:1px solid #f1f2f5">
                <td style="padding:.7rem 1.25rem;vertical-align:top;width:1%;white-space:nowrap">
                    <span style="display:inline-block;font-size:.7rem;font-weight:700;text-transform:uppercase;
                                 letter-spacing:.03em;padding:.2rem .55rem;border-radius:4px;
                                 color:#fff;background:<?= $tone ?>"><?= e($label) ?></span>
                </td>
                <td style="padding:.7rem .75rem;vertical-align:top;font-size:.9rem;color:#374151;line-height:1.5">
                    <?= e((string)$ev['description']) ?>
                    <div style="font-size:.78rem;color:#9ca3af;margin-top:.15rem">
                        <?= e($who) ?> &middot; <?= e((string)$ev['subject_type']) ?>
                        <?php if (!empty($ev['subject_id'])): ?>#<?= (int)$ev['subject_id'] ?><?php endif; ?>
                    </div>
                </td>
                <td style="padding:.7rem 1.25rem;vertical-align:top;width:1%;white-space:nowrap;
                           font-size:.78rem;color:#9ca3af;text-align:right">
                    <?= date('M j, Y g:i a', strtotime((string)$ev['created_at'])) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($total > count($events)): ?>
        <div style="padding:.6rem 1.25rem;font-size:.78rem;color:#9ca3af;border-top:1px solid #f1f2f5">
            Showing the most recent <?= count($events) ?> of <?= $total ?>.
        </div>
    <?php endif; ?>
<?php endif; ?>
