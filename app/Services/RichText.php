<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Sanitise HTML typed into the website description editors.
 *
 * Whatever is stored here is printed into a public page unescaped — that is the
 * point of a rich text field — so it cannot be trusted on the way out. It is
 * cleaned on the way IN, once, rather than on every render: a value that reaches
 * the database already safe cannot be rendered unsafely by some future view that
 * forgets to call the right helper.
 *
 * An ALLOW-LIST, never a block-list. Blocking <script> leaves <img onerror>,
 * <svg onload>, <iframe>, javascript: URLs, data: URLs and a long tail of things
 * nobody thinks of; permitting eight tags leaves nothing.
 */
class RichText
{
    /** Tags a product description may use, and the attributes each may carry. */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [],
        'u' => [], 'ul' => [], 'ol' => [], 'li' => [],
        'h3' => [], 'h4' => [], 'blockquote' => [],
        'a' => ['href', 'title'],
    ];

    /** Clean a fragment of HTML for storage. */
    public static function clean(?string $html): ?string
    {
        $html = trim((string)$html);

        if ($html === '') {
            return null;
        }

        $doc = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);

        // Wrapped and declared UTF-8: without the meta charset DOMDocument reads the
        // bytes as Latin-1 and turns every em dash in a paint name into mojibake.
        $doc->loadHTML(
            '<?xml encoding="UTF-8"><div id="rt-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $root = $doc->getElementById('rt-root');

        if ($root === null) {
            return null;
        }

        self::scrub($root);

        $out = '';

        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        $out = trim($out);

        return $out === '' ? null : $out;
    }

    /** Strip anything not on the allow-list, keeping the text inside it. */
    private static function scrub(\DOMNode $node): void
    {
        // Snapshot the children: unwrapping a node mutates the live NodeList, and
        // iterating it directly silently skips siblings.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMText) {
                continue;
            }

            if (!$child instanceof \DOMElement) {
                $node->removeChild($child);        // comments, CDATA, processing instructions
                continue;
            }

            $tag = strtolower($child->nodeName);

            if (!isset(self::ALLOWED[$tag])) {
                self::scrub($child);
                self::unwrap($child);              // keep the words, drop the tag
                continue;
            }

            foreach (iterator_to_array($child->attributes ?? []) as $attr) {
                if (!in_array(strtolower($attr->nodeName), self::ALLOWED[$tag], true)) {
                    $child->removeAttribute($attr->nodeName);
                }
            }

            if ($tag === 'a') {
                self::fixLink($child);
            }

            self::scrub($child);
        }
    }

    /** Replace an element with its own children. */
    private static function unwrap(\DOMElement $el): void
    {
        $parent = $el->parentNode;

        if ($parent === null) {
            return;
        }

        while ($el->firstChild !== null) {
            $parent->insertBefore($el->firstChild, $el);
        }

        $parent->removeChild($el);
    }

    /**
     * Make a link safe, or remove the href.
     *
     * Only http, https and mailto. 'javascript:' is the obvious one, but 'data:' can
     * carry a whole HTML document and relative URLs can be made to point anywhere, so
     * the scheme is checked rather than the string searched for bad words.
     */
    private static function fixLink(\DOMElement $a): void
    {
        $href = trim($a->getAttribute('href'));
        $scheme = strtolower((string)parse_url($href, PHP_URL_SCHEME));

        $ok = $href !== ''
            && (in_array($scheme, ['http', 'https', 'mailto'], true)
                || str_starts_with($href, '/'));

        if (!$ok) {
            $a->removeAttribute('href');
            return;
        }

        // Anything off-site opens in a new tab, and noopener stops the opened page
        // reaching back into ours through window.opener.
        if ($scheme === 'http' || $scheme === 'https') {
            $a->setAttribute('target', '_blank');
            $a->setAttribute('rel', 'noopener nofollow');
        }
    }

    /** Plain text of a fragment, for meta descriptions and card blurbs. */
    public static function toText(?string $html, int $limit = 0): string
    {
        $text = trim(html_entity_decode(strip_tags((string)$html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        if ($limit > 0 && mb_strlen($text) > $limit) {
            $text = rtrim(mb_substr($text, 0, $limit - 1)) . '…';
        }

        return $text;
    }
}
