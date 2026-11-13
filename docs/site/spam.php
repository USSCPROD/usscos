<?php

/**
 * Spam guard for the usscos.com lead forms.
 *
 * These three forms post straight into USSCOS and create a lead, which emails a rep.
 * They had nothing on them at all, and bots found them: an SEO pitch, a subscription
 * bot and a vague "I would like more information" all became leads. A lead list full
 * of that is one people stop reading, which costs more than the spam does.
 *
 * Three checks, no third-party service and nothing for a real customer to solve:
 *
 *   1. A honeypot field. Hidden from people, filled in by bots that fill everything.
 *   2. Time to complete. A form submitted within a couple of seconds of being served
 *      was not read, let alone typed into.
 *   3. A rate limit per address. Five minutes between submissions is generous for a
 *      person and ruinous for a script.
 *
 * A caught submission is told it succeeded and silently dropped. Saying "you look like
 * a bot" only teaches whoever wrote it which check to beat next.
 */

if (!function_exists('ussc_honeypot_fields')) {

    /** The hidden fields. Put this inside every <form>. */
    function ussc_honeypot_fields(): string
    {
        $t = time();

        // Named to look worth filling in. "website" and "company_url" are what a
        // form-filler reaches for; a real visitor never sees either.
        return
            '<div aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;'
            . 'height:0;width:0;overflow:hidden" tabindex="-1">'
            . '<label>Website<input type="text" name="website" tabindex="-1" autocomplete="off" value=""></label>'
            . '<label>Company URL<input type="text" name="company_url" tabindex="-1" autocomplete="off" value=""></label>'
            . '</div>'
            . '<input type="hidden" name="form_started" value="' . $t . '">';
    }

    /**
     * True when this submission looks automated.
     *
     * Deliberately forgiving: every check has to be something a real person on a slow
     * connection, with autofill, on a phone, will still pass. A form that rejects
     * customers to stop bots has made the problem worse.
     */
    function ussc_is_spam(array $post): bool
    {
        // 1. Honeypot. Anything at all in either field.
        if (trim((string)($post['website'] ?? '')) !== ''
            || trim((string)($post['company_url'] ?? '')) !== '') {
            ussc_spam_log('honeypot');
            return true;
        }

        // 2. Too fast to have been read. Three seconds is below any real completion,
        // and a missing timestamp is not held against anybody — a cached page or a
        // browser that strips hidden fields should not block a customer.
        $started = (int)($post['form_started'] ?? 0);

        if ($started > 0 && (time() - $started) < 3) {
            ussc_spam_log('too fast');
            return true;
        }

        // 3. Rate limit. One address, five minutes.
        if (!ussc_rate_ok()) {
            ussc_spam_log('rate limit');
            return true;
        }

        return false;
    }

    /** One submission per address per five minutes. */
    function ussc_rate_ok(int $seconds = 300): bool
    {
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');

        if ($ip === '') {
            return true;
        }

        $dir = sys_get_temp_dir() . '/ussc-form-rate';

        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            return true; // Cannot rate limit; do not block the customer over it.
        }

        $file = $dir . '/' . hash('sha256', $ip);
        $last = is_file($file) ? (int)@filemtime($file) : 0;

        if ($last > 0 && (time() - $last) < $seconds) {
            return false;
        }

        @touch($file);

        // Tidy up behind us so the directory does not grow without limit.
        if (random_int(1, 50) === 1) {
            foreach ((array)glob($dir . '/*') as $old) {
                if (is_file($old) && (time() - (int)@filemtime($old)) > 86400) {
                    @unlink($old);
                }
            }
        }

        return true;
    }

    /** Why something was dropped, so it is possible to tell signal from silence. */
    function ussc_spam_log(string $reason): void
    {
        error_log(
            'ussc form spam (' . $reason . ') from ' . ($_SERVER['REMOTE_ADDR'] ?? '?')
            . ' on ' . ($_SERVER['REQUEST_URI'] ?? '?')
        );
    }
}
