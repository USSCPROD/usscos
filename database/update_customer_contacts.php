<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

define('BASE_PATH', dirname(__DIR__));

$env = [];
foreach (file(BASE_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v);
}
$dsn = "mysql:host={$env['DB_HOST']};dbname={$env['DB_DATABASE']};charset=utf8mb4";
$pdo = new PDO($dsn, trim($env['DB_USERNAME']), trim($env['DB_PASSWORD']), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$csvFile = BASE_PATH . '/database/spreadsheets/customers_full.csv';
if (!file_exists($csvFile)) {
    die("customers_full.csv not found at: $csvFile\n");
}

$stmt = $pdo->prepare("
    UPDATE customers SET
        first_name      = :first_name,
        last_name       = :last_name,
        bill_address_1  = :bill_address_1,
        bill_city       = :bill_city,
        bill_state      = :bill_state,
        bill_zip        = :bill_zip,
        phone           = CASE WHEN phone IS NULL OR phone = '' THEN :phone ELSE phone END,
        fax             = CASE WHEN fax IS NULL OR fax = '' THEN :fax ELSE fax END
    WHERE quickbooks_name = :quickbooks_name
");

$handle = fopen($csvFile, 'r');
$headers = fgetcsv($handle); // skip header

$updated = 0;
$skipped = 0;
$line = 1;

while (($row = fgetcsv($handle)) !== false) {
    $line++;
    if (count($row) < 10) {
        continue;
    }

    [$qbName, $firstName, $lastName, $addr1, $city, $state, $zip, $phone, $fax, $balance] = $row;

    if (empty(trim($qbName))) {
        continue;
    }

    $stmt->execute([
        ':quickbooks_name' => trim($qbName),
        ':first_name'      => trim($firstName) ?: null,
        ':last_name'       => trim($lastName) ?: null,
        ':bill_address_1'  => trim($addr1) ?: null,
        ':bill_city'       => trim($city) ?: null,
        ':bill_state'      => trim($state) ?: null,
        ':bill_zip'        => trim($zip) ?: null,
        ':phone'           => trim($phone) ?: null,
        ':fax'             => trim($fax) ?: null,
    ]);

    $count = $stmt->rowCount();
    if ($count > 0) {
        $updated++;
    } else {
        $skipped++;
    }

    if ($line % 1000 === 0) {
        echo "Processed $line rows... (updated: $updated, skipped/no-match: $skipped)\n";
    }
}

fclose($handle);

echo "Done. Updated: $updated | No match: $skipped | Total rows: " . ($line - 1) . "\n";
