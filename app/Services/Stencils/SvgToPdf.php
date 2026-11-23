<?php

declare(strict_types=1);

namespace App\Services\Stencils;

/**
 * Turns a proof SVG into a PDF, on the server.
 *
 * WHY THIS EXISTS. The proof page used to say "print, and choose Save as PDF". That
 * leaves the most important document a customer sees to whichever browser they happen to
 * use, and they do not agree: Safari drew a blank page through Save as PDF while printing
 * the same sheet correctly on paper, because the two use different backends. Chrome
 * needed the sheet pinned to exactly one page or it added an empty second one. Every one
 * of those was a separate fix for the same symptom, reported by a customer who had
 * already given up.
 *
 * A PDF made here is the same bytes for everybody, needs no print dialog, and nobody has
 * to remember to pick Landscape. It is also what the stencil shop did - with this same
 * tool, on this same SVG.
 *
 * rsvg-convert, from librsvg2-bin. The SVG is 792 x 612 points, which is letter
 * landscape, so no scaling or page size needs stating.
 */
final class SvgToPdf
{
    private const BIN = '/usr/bin/rsvg-convert';

    public static function available(): bool
    {
        return is_executable(self::BIN);
    }

    /**
     * @throws DesignerError when the converter is missing or fails. The caller should
     *                       fall back to the printable page rather than show an error:
     *                       a proof the customer can still print beats no proof.
     */
    public static function convert(string $svg): string
    {
        if (!self::available()) {
            throw new DesignerError('PDF conversion is not available on this server.');
        }

        // A temporary file, not stdin: a proof carries a base64 logo and runs to tens of
        // kilobytes, which is past the point where writing to a pipe can block.
        $in = tempnam(sys_get_temp_dir(), 'proof_') ?: throw new DesignerError('Cannot write a temporary file.');

        try {
            if (file_put_contents($in, $svg) === false) {
                throw new DesignerError('Cannot write the proof for conversion.');
            }

            $out = $in . '.pdf';

            $cmd = escapeshellarg(self::BIN) . ' -f pdf -o ' . escapeshellarg($out)
                 . ' ' . escapeshellarg($in) . ' 2>&1';

            exec($cmd, $lines, $code);

            if ($code !== 0 || !is_file($out)) {
                error_log('Proof PDF: rsvg-convert exited ' . $code . ' - ' . implode(' ', $lines));
                throw new DesignerError('The proof could not be turned into a PDF.');
            }

            $pdf = (string) file_get_contents($out);
            @unlink($out);

            if (!str_starts_with($pdf, '%PDF')) {
                throw new DesignerError('The proof could not be turned into a PDF.');
            }

            return $pdf;
        } finally {
            @unlink($in);
        }
    }
}
