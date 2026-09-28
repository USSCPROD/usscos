<?php

declare(strict_types=1);

/**
 * Import a reference ZIP rate table.
 *
 *   php database/import_tax_reference.php --dry-run
 *   php database/import_tax_reference.php --commit
 *   php database/import_tax_reference.php --commit --file=database/reference/TAXRATES_ZIP5_GA202610.csv
 *
 * A script rather than a migration, because these files are reissued every month and a
 * migration is a thing that runs once. Re-running this replaces the rows for whatever
 * months the files cover.
 *
 * Dry run by default, per the rule for anything that changes data in bulk.
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;

$args   = array_slice($argv, 1);
$commit = in_array('--commit', $args, true);
$files  = [];

foreach ($args as $arg) {
    if (str_starts_with($arg, '--file=')) {
        $files[] = substr($arg, 7);
    }
}

if ($files === []) {
    $files = glob(BASE_PATH . '/database/reference/TAXRATES_ZIP5_*.csv') ?: [];
}

if ($files === []) {
    exit("No reference files found in database/reference/.\n");
}

if (!$commit) {
    echo "DRY RUN — nothing will be written. Add --commit to apply.\n\n";
}

$total = 0;
$pdo   = Database::connection();

if ($commit) {
    $pdo->beginTransaction();
}

try {
    foreach ($files as $path) {
        if (!is_readable($path)) {
            echo "  cannot read {$path}\n";
            continue;
        }

        // TAXRATES_ZIP5_GA202609.csv -> GA, 2026-09
        $asOf = 'unknown';
        if (preg_match('/_([A-Z]{2})(\d{4})(\d{2})\.csv$/', basename($path), $m)) {
            $asOf = $m[2] . '-' . $m[3];
        }

        $fh = fopen($path, 'r');
        $header = fgetcsv($fh);
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
                        state_code = VALUES(state_code), region_name = VALUES(region_name),
                        combined_rate = VALUES(combined_rate), state_rate = VALUES(state_rate),
                        county_rate = VALUES(county_rate), city_rate = VALUES(city_rate),
                        special_rate = VALUES(special_rate), as_of = VALUES(as_of),
                        imported_at = NOW()
                ", [
                    str_pad(trim($r[1]), 5, '0', STR_PAD_LEFT),
                    trim($r[0]),
                    trim($r[2]) ?: null,
                    (float)$r[3], (float)$r[4], (float)$r[5], (float)$r[6], (float)$r[7],
                    $asOf,
                ]);
            }
        }

        fclose($fh);
        printf("  %-46s %5d rows  (%s)\n", basename($path), $rows, $asOf);
        $total += $rows;
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

printf("\n%s %d rows.\n", $commit ? 'Imported' : 'Would import', $total);

if ($commit) {
    $summary = Database::select("
        SELECT state_code, COUNT(*) AS zips, MIN(as_of) AS as_of
        FROM tax_zip_reference GROUP BY state_code ORDER BY state_code
    ");

    foreach ($summary as $s) {
        printf("  %s  %d ZIPs, %s\n", $s['state_code'], $s['zips'], $s['as_of']);
    }
}
