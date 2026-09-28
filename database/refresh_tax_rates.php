<?php

declare(strict_types=1);

/**
 * Load whatever tax rate files are sitting in database/reference/.
 *
 *   php database/refresh_tax_rates.php              dry run, the default
 *   php database/refresh_tax_rates.php --commit
 *
 * Handles both shapes without being told which is which:
 *
 *   *.csv   Avalara ZIP reference tables      TAXRATES_ZIP5_GA202609.csv
 *   *.pdf   Georgia DOR general rate chart    general-rate-chart-oct-2026.pdf
 *
 * WHY THE DOWNLOAD IS NOT AUTOMATED. It cannot be. dor.georgia.gov returns 403 to this
 * server for both the index page and the PDF itself - it is behind bot protection - and
 * Avalara's tables are behind a form. Pretending otherwise would mean a scheduled job that
 * silently fetches a challenge page and parses nothing, which is worse than a person
 * spending two minutes once a quarter.
 *
 * So the download is manual and everything after it is not: parsing, effective dating,
 * loading and verification all happen here, and the dashboard says when the rates are
 * going stale rather than relying on somebody remembering.
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;

$commit = in_array('--commit', array_slice($argv, 1), true);
$dir    = BASE_PATH . '/database/reference';

if (!$commit) {
    echo "DRY RUN — nothing will be written. Add --commit to apply.\n\n";
}

$files = array_merge(glob($dir . '/*.csv') ?: [], glob($dir . '/*.pdf') ?: []);

if ($files === []) {
    exit("Nothing in database/reference/.\n\n"
       . "  Georgia:  dor.georgia.gov/sales-tax-rates-general — save the current and next\n"
       . "            quarter's general rate charts as PDFs\n"
       . "  Avalara:  avalara.com/taxrates/en/download-tax-tables.html — GA and NC\n");
}

$pdo = Database::connection();

if ($commit) {
    $pdo->beginTransaction();
}

try {
    foreach ($files as $path) {
        str_ends_with(strtolower($path), '.csv')
            ? importAvalara($path, $commit)
            : importGeorgiaChart($path, $commit);
    }

    if ($commit) {
        $pdo->commit();
    }
} catch (\Throwable $e) {
    if ($commit && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $e;
}

report();


/** Avalara's ZIP reference table — a second opinion, never the rate we charge. */
function importAvalara(string $path, bool $commit): void
{
    $asOf = preg_match('/_([A-Z]{2})(\d{4})(\d{2})\.csv$/', basename($path), $m)
        ? $m[2] . '-' . $m[3]
        : 'unknown';

    $fh = fopen($path, 'r');
    fgetcsv($fh);
    $rows = 0;

    while (($r = fgetcsv($fh)) !== false) {
        if (count($r) < 9 || trim($r[1]) === '') {
            continue;
        }

        $rows++;

        if ($commit) {
            Database::statement("
                INSERT INTO tax_zip_reference
                    (zip, state_code, source, region_name, combined_rate,
                     state_rate, county_rate, city_rate, special_rate, as_of)
                VALUES (?, ?, 'avalara', ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    state_code=VALUES(state_code), region_name=VALUES(region_name),
                    combined_rate=VALUES(combined_rate), state_rate=VALUES(state_rate),
                    county_rate=VALUES(county_rate), city_rate=VALUES(city_rate),
                    special_rate=VALUES(special_rate), as_of=VALUES(as_of), imported_at=NOW()
            ", [
                str_pad(trim($r[1]), 5, '0', STR_PAD_LEFT), trim($r[0]), trim($r[2]) ?: null,
                (float)$r[3], (float)$r[4], (float)$r[5], (float)$r[6], (float)$r[7], $asOf,
            ]);
        }
    }

    fclose($fh);
    printf("  %-44s %5d ZIP rates   (%s)\n", basename($path), $rows, $asOf);
}

/**
 * The Georgia DOR general rate chart.
 *
 * Effective dates come from the chart itself rather than from the filename, because the
 * filename is whatever the person saving it typed and the date is the one thing that must
 * be right — a chart loaded against the wrong quarter is worse than one not loaded at all.
 */
function importGeorgiaChart(string $path, bool $commit): void
{
    $text = shell_exec('pdftotext -layout ' . escapeshellarg($path) . ' - 2>/dev/null');

    if (!is_string($text) || $text === '') {
        printf("  %-44s could not read — is pdftotext installed?\n", basename($path));
        return;
    }

    if (!preg_match('/Effective\s+([A-Z][a-z]+)\s*(\d{1,2}),?\s*(\d{4})/', $text, $m)) {
        printf("  %-44s no effective date in the chart — skipped\n", basename($path));
        return;
    }

    $from = date('Y-m-d', strtotime("{$m[1]} {$m[2]} {$m[3]}"));
    $to   = date('Y-m-d', strtotime($from . ' +3 months -1 day'));

    // "001 Appling 8 L E S T" and "060 Fulton* 7.75 M L E Tf"
    $pat = '/\b(\d{3}[A-Z]?)\s+([A-Za-z][A-Za-z .\'\-]*(?:\([^)]*\))?\*?)\s*(\d+(?:\.\d+)?)\s/';
    preg_match_all($pat, $text . ' ', $all, PREG_SET_ORDER);

    $loaded = 0;

    foreach ($all as $row) {
        [$code, $name, $rate] = [$row[1], trim(rtrim(trim($row[2]), '*')), (float)$row[3]];

        if ($code === '000') {
            continue;
        }

        $county = trim(explode('(', $name)[0]);

        if ($commit) {
            Database::statement("
                INSERT INTO tax_rates
                    (name, state_code, county, is_state_default, rate,
                     effective_from, effective_to, needs_review, source_note, is_active)
                SELECT ?, 'GA', ?, 0, ?, ?, ?, 0, ?, 1
                FROM DUAL
                WHERE NOT EXISTS (
                    SELECT 1 FROM tax_rates t WHERE t.name = ? AND t.effective_from = ?
                )
            ", [
                'GA - ' . $name, $county, $rate / 100, $from, $to,
                'GA DOR general rate chart, effective ' . $from,
                'GA - ' . $name, $from,
            ]);
        }

        $loaded++;
    }

    printf("  %-44s %5d jurisdictions (%s to %s)\n", basename($path), $loaded, $from, $to);
}

function report(): void
{
    echo "\nRates now on file:\n";

    foreach (Database::select("
        SELECT state_code, effective_from, effective_to, COUNT(*) AS n
        FROM tax_rates WHERE is_active = 1 AND county IS NOT NULL
        GROUP BY state_code, effective_from, effective_to
        ORDER BY state_code, effective_from
    ") as $r) {
        printf("  %s  %s to %s   %d jurisdictions\n",
            $r['state_code'], $r['effective_from'], $r['effective_to'] ?: 'open', $r['n']);
    }

    foreach (\App\Services\TaxService::coverageWarnings() as $w) {
        echo "\n  ⚠ " . $w . "\n";
    }
}
