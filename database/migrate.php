<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

$host     = $_ENV['DB_HOST']     ?? 'localhost';
$port     = $_ENV['DB_PORT']     ?? '3306';
$database = $_ENV['DB_DATABASE'] ?? 'businessos';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$database}`");

    // Create migrations tracking table
    $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
        id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        migration   VARCHAR(255) NOT NULL,
        ran_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_migration (migration)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $migrationPath = __DIR__ . '/migrations';
    $files         = glob($migrationPath . '/*.sql');
    sort($files);

    $ran = $pdo->query("SELECT migration FROM migrations")->fetchAll(PDO::FETCH_COLUMN);

    $count = 0;
    foreach ($files as $file) {
        $name = basename($file);
        if (in_array($name, $ran)) {
            echo "  [skip] {$name}\n";
            continue;
        }

        $sql = file_get_contents($file);
        // Split on semicolons so multi-statement files (e.g. CREATE + INSERT) all run
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            if ($statement !== '') {
                $pdo->exec($statement);
            }
        }
        $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)")->execute([$name]);

        echo "  [ran]  {$name}\n";
        $count++;
    }

    echo "\nDone. {$count} migration(s) ran.\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
