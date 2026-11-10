<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Several single-document print views, joined into one print job.
 *
 * The packing slip, the invoice and the sales order are each written as a complete
 * HTML document with their own <style> block. That is right for one order and useless
 * for twelve: a browser asked to print twelve documents prints one.
 *
 * So the first document is kept whole - it carries the stylesheet every later page
 * needs - and the rest contribute only what is inside their <body>, separated by a
 * hard page break. Nothing about the individual templates has to change, which means
 * a change to the packing slip cannot drift out of step with the batch version.
 */
class PrintBatch
{
    /**
     * @param string[] $documents complete HTML documents, in print order
     */
    public static function stitch(array $documents): string
    {
        $documents = array_values(array_filter($documents, fn ($d) => trim((string)$d) !== ''));

        if ($documents === []) {
            return '';
        }

        if (count($documents) === 1) {
            return $documents[0];
        }

        $first = array_shift($documents);
        $rest  = '';

        foreach ($documents as $doc) {
            $rest .= '<div style="page-break-before:always;break-before:page">'
                   . self::body($doc)
                   . '</div>';
        }

        // Appended inside the first document's <body>, so the whole batch sits under
        // one <html> with one stylesheet.
        $pos = strripos($first, '</body>');

        if ($pos === false) {
            return $first . $rest;
        }

        return substr($first, 0, $pos) . $rest . substr($first, $pos);
    }

    /** What is between <body> and </body>, or the whole string if there is no body. */
    private static function body(string $html): string
    {
        if (!preg_match('~<body[^>]*>(.*)</body>~is', $html, $m)) {
            return $html;
        }

        return $m[1];
    }
}
