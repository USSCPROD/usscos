<?php

declare(strict_types=1);

namespace App\Services\Web;

/**
 * Which site a web lead came from.
 *
 * Two public sites post to the same webhook. Until now both wrote source='web' and nothing
 * recorded which, so "how many enquiries does usscproducts.com actually bring in" had no
 * answer, and when a spam rule deleted 117 web leads on 13 Nov 2026 there was no way to
 * tell whether genuine enquiries went with them.
 *
 * Three signals, in order of how much they can be trusted:
 *
 *  1. The payload says so. usscos.com's forms send `site`. This is the only signal the
 *     sender controls deliberately, so it wins.
 *  2. The User-Agent. wp_remote_post() sends "WordPress/6.x; https://usscproducts.com"
 *     by default, which identifies the WordPress site accurately with nothing to install
 *     there - worth reading precisely because the alternative is waiting on a paste into
 *     Code Snippets before any WordPress lead is labelled.
 *  3. Nothing. Returns null, meaning "not recorded".
 *
 * Deliberately NOT used: the remote address. usscos.com is on this droplet and
 * usscproducts.com is on cPanel elsewhere, so the two are distinguishable by IP today -
 * but that is a coincidence of hosting, not a fact about the lead, and it would survive a
 * server move by silently labelling every lead wrong. A blank is recoverable; a confident
 * wrong answer is not.
 */
final class LeadOrigin
{
    /** Hosts we will record. Anything else is somebody else's claim about themselves. */
    private const KNOWN = ['usscos.com', 'usscproducts.com', 'os.usscos.com'];

    public static function detect(array $payload, array $server): ?string
    {
        $declared = self::normalise((string) ($payload['site'] ?? ''));
        if ($declared !== null) {
            return $declared;
        }

        // "WordPress/6.8.1; https://usscproducts.com" - take the URL at the end.
        $agent = (string) ($server['HTTP_USER_AGENT'] ?? '');
        if (preg_match('#https?://([^\s;/]+)#i', $agent, $m)) {
            $fromAgent = self::normalise($m[1]);
            if ($fromAgent !== null) {
                return $fromAgent;
            }
        }

        return null;
    }

    /**
     * Reduce a host to one of the sites we know, or null.
     *
     * An allowlist rather than "store whatever arrived", because this value is written by
     * whoever posts to a public endpoint and is then shown on a staff page. Storing an
     * arbitrary string would put an attacker's text in front of a rep.
     */
    private static function normalise(string $value): ?string
    {
        $host = strtolower(trim($value));
        if ($host === '') {
            return null;
        }

        // Accept a full URL as readily as a bare host - senders differ.
        if (str_contains($host, '//')) {
            $host = (string) parse_url($host, PHP_URL_HOST);
        }

        $host = preg_replace('/:\d+$/', '', $host);           // strip any port
        $host = preg_replace('/^www\./', '', (string) $host); // www is the same site

        return in_array($host, self::KNOWN, true) ? $host : null;
    }
}
