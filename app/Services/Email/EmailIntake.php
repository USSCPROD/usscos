<?php

declare(strict_types=1);

namespace App\Services\Email;

use App\Repositories\EmailIntakeRepository;
use App\Services\Activity;

/**
 * Take a received email and put it on the right timeline.
 *
 * HOW MAIL GETS HERE. A shared mailbox - crm@usscproducts.com - that reps BCC. Something
 * watching that mailbox POSTs each message to /api/email/inbound. On Microsoft 365 the
 * simplest watcher is a Power Automate flow ("when a new email arrives" → HTTP), because
 * Microsoft disabled IMAP basic auth, so a password-based poller cannot work and anything
 * else needs an Azure app registration. See docs/EMAIL_INTAKE.md.
 *
 * BCC rather than reading the reps' mailboxes wholesale. It is opt-in per message, so no
 * personal mail is ever swept in, and nobody has to be told their inbox is being read. The
 * cost is that it only captures what somebody remembers to BCC - which is the trade, and
 * it is the one worth making first.
 *
 * A matched message becomes a note.email entry, the same kind a rep writes by hand, so it
 * lands in the timeline already built rather than in a parallel feed.
 */
final class EmailIntake
{
    public function __construct(
        private readonly EmailIntakeRepository $repo = new EmailIntakeRepository(),
        private readonly EmailMatcher $matcher = new EmailMatcher(),
    ) {
    }

    /**
     * @param array $m Parsed message: message_id, from_email, from_name, to[], cc[],
     *                 subject, body, sent_at.
     * @return array{status:string, id:?int, matched:bool}
     */
    public function receive(array $m): array
    {
        $messageId = trim((string)($m['message_id'] ?? ''));
        if ($messageId === '') {
            // Synthesised rather than refused: a message with no Message-ID is unusual but
            // real, and losing it would be worse than storing it under a hash. The hash is
            // of the parts that identify it, so a genuine repeat still collides.
            $messageId = 'sha256:' . hash('sha256', implode("\n", [
                (string)($m['from_email'] ?? ''),
                implode(',', (array)($m['to'] ?? [])),
                (string)($m['subject'] ?? ''),
                (string)($m['sent_at'] ?? ''),
            ]));
        }

        $from = strtolower(trim((string)($m['from_email'] ?? '')));
        $to   = array_map('strval', (array)($m['to'] ?? []));
        $cc   = array_map('strval', (array)($m['cc'] ?? []));

        $match = $this->matcher->match(array_merge([$from], $to, $cc));
        $matched = $match['customer_id'] !== null || $match['lead_id'] !== null;

        $body    = $this->plainText((string)($m['body'] ?? ''));
        $subject = mb_substr(trim((string)($m['subject'] ?? '')), 0, 500);

        $id = $this->repo->insert([
            'message_id'          => mb_substr($messageId, 0, 255),
            'direction'           => $this->direction($from),
            'from_email'          => mb_substr($from, 0, 255),
            'from_name'           => mb_substr(trim((string)($m['from_name'] ?? '')), 0, 255) ?: null,
            'to_emails'           => mb_substr(implode(', ', array_merge($to, $cc)), 0, 2000) ?: null,
            'subject'             => $subject ?: null,
            'body'                => $body ?: null,
            'sent_at'             => $this->dateOrNull((string)($m['sent_at'] ?? '')),
            'matched_customer_id' => $match['customer_id'],
            'matched_lead_id'     => $match['lead_id'],
            'matched_on'          => $match['matched_on'],
            'match_type'          => $match['match_type'],
            'activity_id'         => null,
            'status'              => $matched ? 'matched' : 'unmatched',
        ]);

        if ($id === null) {
            return ['status' => 'duplicate', 'id' => null, 'matched' => false];
        }

        if ($matched) {
            $activityId = Activity::logInteraction('email', $this->summary($subject, $body, $match), [
                'subject_type' => $match['customer_id'] !== null ? 'Customer' : 'Lead',
                'subject_id'   => $match['customer_id'] ?? $match['lead_id'],
                'customer_id'  => $match['customer_id'],
                'lead_id'      => $match['lead_id'],
                // No signed-in user: a mailbox delivered this, not a person at a screen.
                // The sender's address is in the text instead.
                'user_id'      => null,
            ]);

            // Written after the fact rather than before, because the activity row has to
            // exist to be pointed at. Null if the log write failed - which it can, since
            // Activity never throws - and the intake row is still correct without it.
            if ($activityId !== null) {
                $this->repo->linkActivity($id, $activityId);
            }
        }

        return ['status' => $matched ? 'matched' : 'unmatched', 'id' => $id, 'matched' => $matched];
    }

    /**
     * The timeline sentence.
     *
     * Subject first, because that is what a person scanning a timeline needs, then enough
     * of the body to recognise the conversation. The full text stays in email_intake - a
     * timeline entry is a summary, not a mail client.
     *
     * A domain match says so. It is a weaker claim than an address match, and whoever reads
     * it later should be able to see which kind it was without going looking.
     */
    private function summary(string $subject, string $body, array $match): string
    {
        $line = 'Email: ' . ($subject !== '' ? $subject : '(no subject)');

        if ($body !== '') {
            $line .= ' — ' . str_limit(preg_replace('/\s+/', ' ', $body) ?? '', 300);
        }
        if (($match['match_type'] ?? '') === 'domain') {
            $line .= ' [matched on the domain ' . $match['matched_on'] . ', not an address on record]';
        }

        return $line;
    }

    /** Ours going out, or theirs coming in. Decided by the sender's domain. */
    private function direction(string $from): string
    {
        foreach (['usscproducts.com', 'usscos.com', 'stencilsplus.com'] as $ours) {
            if (str_ends_with($from, '@' . $ours)) {
                return 'outbound';
            }
        }
        return 'inbound';
    }

    /**
     * HTML reduced to something readable, with scripts and styles removed first.
     *
     * strip_tags alone keeps the CONTENT of <style> and <script> blocks, so a marketing
     * signature would arrive as a page of CSS. They go before the tags do.
     */
    private function plainText(string $body): string
    {
        if (!str_contains($body, '<')) {
            return trim($body);
        }

        $body = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $body) ?? $body;
        $body = preg_replace('#<br\s*/?>|</p>|</div>|</tr>#i', "\n", $body) ?? $body;
        $body = strip_tags($body);
        $body = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $body = preg_replace("/\n{3,}/", "\n\n", $body) ?? $body;

        return trim($body);
    }

    private function dateOrNull(string $value): ?string
    {
        if (trim($value) === '') {
            return null;
        }
        $ts = strtotime($value);
        return $ts === false ? null : date('Y-m-d H:i:s', $ts);
    }
}
