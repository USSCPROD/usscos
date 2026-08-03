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

$csvFile = BASE_PATH . '/database/spreadsheets/customers_email.csv';
if (!file_exists($csvFile)) {
    die("File not found: $csvFile\n");
}

$stmt = $pdo->prepare("
    UPDATE customers SET email = :email
    WHERE quickbooks_name = :quickbooks_name AND :email != ''
");

$handle = fopen($csvFile, 'r');
fgetcsv($handle); // skip header

$updated = $skipped = 0;
while (($row = fgetcsv($handle)) !== false) {
    if (count($row) < 2) continue;
    [$qbName, $email] = $row;
    $qbName = trim($qbName);
    $email  = trim($email);
    if (!$qbName || !$email) { $skipped++; continue; }

    $stmt->execute([':quickbooks_name' => $qbName, ':email' => $email]);
    $stmt->rowCount() > 0 ? $updated++ : $skipped++;
}
fclose($handle);

echo "Done. Updated: $updated | Skipped/no-match: $skipped\n";
