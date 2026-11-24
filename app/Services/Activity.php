<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Logger;
use App\Repositories\ActivityRepository;

/**
 * The activity log: what happened, to what, by whom, when.
 *
 * WHY IT IS STATIC. A call site is one line in the middle of a method that is already
 * doing the real work - issuing an invoice, converting a quote, changing a rep. Anything
 * requiring a constructor and an injected dependency would not get written at the twenty
 * places that matter, and a log with twenty gaps in it is worse than none, because the
 * gaps look like nothing happened.
 *
 * IT NEVER THROWS. Every failure is swallowed and sent to the error log. Recording that
 * an invoice was issued must not be able to stop an invoice being issued - and a log that
 * can take the application down with it gets removed by whoever is on the phone when it
 * does.
 *
 * EVENT NAMES are 'noun.verb', past tense, lower case: quote.sent, order.shipped,
 * customer.rep_changed. They are read by people in the audit screen, so they are written
 * for people; they are also the filter, so they are stable once used.
 *
 * DESCRIPTION is a whole sentence with the numbers in it, written to be read on its own
 * six months later. "Quote Q-1042 sent to purchasing@west.k12.ga.us" - not "sent", and
 * not "status changed". A timeline of "updated" entries answers nothing.
 */
final class Activity
{
    private static ?ActivityRepository $repo = null;

    /**
     * @param array{
     *     subject_type?: string, subject_id?: int|string|null,
     *     customer_id?: int|string|null, lead_id?: int|string|null,
     *     user_id?: int|null, old?: array|null, new?: array|null
     * } $on
     */
    /** @return int|null The new row's id, or null if it could not be written. */
    public static function log(string $event, string $description, array $on = []): ?int
    {
        try {
            $user = Auth::user();

            return (int) self::repo()->insert([
                'company_id'   => (int) ($user['company_id'] ?? 1),
                // user_id may be given explicitly for work done on someone's behalf by a
                // script or a webhook, where Auth has nobody signed in.
                'user_id'      => self::nullableInt($on['user_id'] ?? ($user['id'] ?? null)),
                'subject_type' => (string) ($on['subject_type'] ?? 'System'),
                'subject_id'   => self::nullableInt($on['subject_id'] ?? null),
                'customer_id'  => self::nullableInt($on['customer_id'] ?? null),
                'lead_id'      => self::nullableInt($on['lead_id'] ?? null),
                'event'        => $event,
                'description'  => $description,
                'old_values'   => self::json($on['old'] ?? null),
                'new_values'   => self::json($on['new'] ?? null),
                'ip_address'   => self::clip($_SERVER['REMOTE_ADDR'] ?? null, 45),
                'user_agent'   => self::clip($_SERVER['HTTP_USER_AGENT'] ?? null, 500),
            ]);
        } catch (\Throwable $e) {
            Logger::error('Activity log failed: ' . $e->getMessage(), [
                'event' => $event,
                'on'    => $on,
            ]);

            // Null, never a throw. The caller may want the id - email intake stores it -
            // but not being able to record the entry must not fail the work that caused it.
            return null;
        }
    }

    /**
     * Record something a PERSON did: a call, an email, a visit, a note.
     *
     * Separate from log() by name because it is a different kind of entry. Everything else
     * in this table is the system reporting itself; this is somebody's own account of what
     * happened, in their words, and it is the only entry type whose text is not predictable
     * from the event name.
     *
     * Stored here rather than in a notes table of its own so that one timeline answers
     * "what has happened with this account", whoever or whatever caused it. A rep's call
     * and the invoice it led to belong in one list, in order.
     *
     * Not editable, deliberately and consistently with the rest of the log. A logged call
     * is a contemporaneous record; being able to revise it later is most of what would
     * make it untrustworthy. Getting it wrong means logging a correction, which is also
     * what you would do on paper.
     *
     * @param string $type  call | email | meeting | note - validated by the caller.
     * @param string $body  What happened, in the rep's own words.
     */
    public static function logInteraction(string $type, string $body, array $on = []): ?int
    {
        return self::log('note.' . $type, $body, $on);
    }

    /** The interaction types a person may log. Anything else is a system event. */
    public static function interactionTypes(): array
    {
        return ['call', 'email', 'meeting', 'note'];
    }

    // ------------------------------------------------------------------ reading

    public static function forCustomer(int $customerId, int $limit = 50, int $offset = 0): array
    {
        return self::repo()->forCustomer($customerId, $limit, $offset);
    }

    public static function countForCustomer(int $customerId): int
    {
        return self::repo()->countForCustomer($customerId);
    }

    public static function forLead(int $leadId, int $limit = 50, int $offset = 0): array
    {
        return self::repo()->forLead($leadId, $limit, $offset);
    }

    public static function countForLead(int $leadId): int
    {
        return self::repo()->countForLead($leadId);
    }

    public static function forSubject(string $type, int $id, int $limit = 50): array
    {
        return self::repo()->forSubject($type, $id, $limit);
    }

    public static function recent(int $limit = 100, int $offset = 0, array $filters = []): array
    {
        return self::repo()->recent($limit, $offset, $filters);
    }

    public static function eventNames(): array
    {
        return self::repo()->eventNames();
    }

    // ------------------------------------------------------------------ helpers

    /**
     * The subset of $new that differs from $old, as ['old' => [...], 'new' => [...]].
     *
     * Values are compared loosely on their string form, because what comes back from PDO
     * is strings and what comes in from a form is strings, while what the code set may be
     * an int or a float. A strict compare marks '1' against 1 as a change and fills the
     * log with edits nobody made.
     *
     * @param string[] $watch Columns worth recording. Everything else is ignored - a
     *                        diff of every column records updated_at on every save.
     */
    public static function changes(array $old, array $new, array $watch): array
    {
        $before = [];
        $after  = [];

        foreach ($watch as $key) {
            if (!array_key_exists($key, $new)) {
                continue;
            }
            $was = $old[$key] ?? null;
            $is  = $new[$key];

            if ((string) $was !== (string) $is) {
                $before[$key] = $was;
                $after[$key]  = $is;
            }
        }

        return ['old' => $before, 'new' => $after];
    }

    private static function repo(): ActivityRepository
    {
        return self::$repo ??= new ActivityRepository();
    }

    private static function nullableInt(mixed $v): ?int
    {
        return ($v === null || $v === '' ) ? null : (int) $v;
    }

    /** Empty arrays are stored as NULL, not '[]' - "no change recorded" reads cleaner. */
    private static function json(?array $v): ?string
    {
        if ($v === null || $v === []) {
            return null;
        }
        $json = json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // The column has a json_valid CHECK on it. A value that will not encode - a
        // resource, invalid UTF-8 from a legacy import - would take the whole insert
        // down, and with it the entry that encodes fine.
        return $json === false ? null : $json;
    }

    private static function clip(?string $v, int $max): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        return mb_substr($v, 0, $max);
    }
}
