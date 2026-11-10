<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Every label for one shipment, as one PDF.
 *
 * Twelve boxes means twelve labels, and twelve trips to a Print button with a pause in
 * between is how a label gets missed and a box goes out unlabelled. One file, one
 * print job, one walk to the printer.
 *
 * Merged with pdfunite (poppler-utils), which is on the server already. A PHP PDF
 * library would be a dependency to carry and a parser to trust; this is one process
 * that either works or says it did not.
 */
class LabelSheet
{
    /** Where a label is allowed to live. Anything else is not ours to read. */
    private const PREFIX = '/uploads/labels/';

    /**
     * @param list<string> $webPaths label_path values, as stored
     * @return string|null the merged PDF, or null when there is nothing to merge
     */
    public static function merge(array $webPaths): ?string
    {
        $files = [];

        foreach ($webPaths as $path) {
            $real = self::resolve((string)$path);

            if ($real !== null) {
                $files[] = $real;
            }
        }

        if ($files === []) {
            return null;
        }

        if (count($files) === 1) {
            $bytes = @file_get_contents($files[0]);

            return $bytes === false ? null : $bytes;
        }

        if (!is_executable('/usr/bin/pdfunite')) {
            return null;   // the caller falls back to one link per label
        }

        $out = tempnam(sys_get_temp_dir(), 'labels_') . '.pdf';

        $cmd = '/usr/bin/pdfunite '
             . implode(' ', array_map('escapeshellarg', $files)) . ' '
             . escapeshellarg($out) . ' 2>/dev/null';

        exec($cmd, $ignored, $code);

        if ($code !== 0 || !is_file($out)) {
            @unlink($out);

            return null;
        }

        $bytes = @file_get_contents($out);
        @unlink($out);

        return $bytes === false || $bytes === '' ? null : $bytes;
    }

    /**
     * A stored path turned into a file on disk, or null if it is not one of ours.
     *
     * The path comes out of the database, which is not the same as trustworthy: these
     * are read off disk and streamed to a browser, so the only ones allowed are the
     * ones under the labels directory, with no way to climb out of it.
     */
    private static function resolve(string $webPath): ?string
    {
        if (!str_starts_with($webPath, self::PREFIX) || str_contains($webPath, '..')) {
            return null;
        }

        $full = realpath(BASE_PATH . '/public' . $webPath);
        $root = realpath(BASE_PATH . '/public' . self::PREFIX);

        if ($full === false || $root === false || !str_starts_with($full, $root)) {
            return null;
        }

        return is_file($full) ? $full : null;
    }
}
