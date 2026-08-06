<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

/**
 * Split a migration file into executable statements.
 *
 * This used to be explode(';', $sql), which broke on a semicolon inside a comment —
 * the text after it became a bogus statement. Several existing migrations contain
 * exactly that and only survived because they were originally applied through the
 * `mysql` client rather than this script.
 *
 * So walk the file instead, tracking quotes so that neither a comment nor a semicolon
 * inside a string literal is mistaken for structure. Comments are dropped and only
 * top-level semicolons end a statement.
 *
 * @return string[] Non-empty statements, comments stripped.
 */
function splitStatements(string $sql): array
{
    $statements = [];
    $current    = '';
    $quote      = null;      // active quote character, or null outside a string
    $length     = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';

        if ($quote !== null) {
            $current .= $char;

            // Backslash escape, and the '' / "" doubling form
            if ($char === '\\' && $quote !== '`') {
                if ($next !== '') {
                    $current .= $next;
                    $i++;
                }
            } elseif ($char === $quote) {
                if ($next === $quote) {
                    $current .= $next;
                    $i++;
                } else {
                    $quote = null;
                }
            }

            continue;
        }

        // "-- " (or -- at end of line) and "#" start a line comment
        if (($char === '-' && $next === '-' && ($i + 2 >= $length || preg_match('/\s/', $sql[$i + 2]) === 1)) || $char === '#') {
            $newline = strpos($sql, "\n", $i);
            $i       = $newline === false ? $length : $newline;
            $current .= "\n";
            continue;
        }

        if ($char === '/' && $next === '*') {
            $end = strpos($sql, '*/', $i + 2);
            $i   = $end === false ? $length : $end + 1;
            $current .= ' ';
            continue;
        }

        if ($char === "'" || $char === '"' || $char === '`') {
            $quote    = $char;
            $current .= $char;
            continue;
        }

        if ($char === ';') {
            if (trim($current) !== '') {
                $statements[] = trim($current);
            }
            $current = '';
            continue;
        }

        $current .= $char;
    }

    // Trailing statement with no closing semicolon
    if (trim($current) !== '') {
        $statements[] = trim($current);
    }

    return $statements;
}

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

    // --dry-run parses every file and reports what would run, touching nothing.
    // --all pairs with it to include already-applied files, which is how you check that
    // the parser still handles the whole history.
    $dryRun  = in_array('--dry-run', $argv, true);
    $showAll = in_array('--all', $argv, true);

    if ($dryRun) {
        echo "DRY RUN — nothing will be executed or recorded.\n\n";
    }

    $count = 0;
    foreach ($files as $file) {
        $name = basename($file);
        if (in_array($name, $ran) && !($dryRun && $showAll)) {
            echo "  [skip] {$name}\n";
            continue;
        }

        $sql        = file_get_contents($file);
        $statements = splitStatements($sql);

        if ($dryRun) {
            printf("  [parse] %-52s %d statement(s)\n", $name, count($statements));
            foreach ($statements as $s) {
                $firstLine = trim(explode("\n", $s)[0]);
                $verb      = strtoupper(strtok($firstLine, " \t("));
                $ok        = in_array($verb, [
                    'CREATE', 'ALTER', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'SET',
                    'REPLACE', 'RENAME', 'TRUNCATE', 'START', 'COMMIT', 'PREPARE', 'EXECUTE',
                ], true);
                printf("        %s %s\n", $ok ? ' ' : '<< NOT SQL:', substr($firstLine, 0, 88));
            }
            $count++;
            continue;
        }

        foreach ($statements as $statement) {
            $pdo->exec($statement);
        }
        $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)")->execute([$name]);

        echo "  [ran]  {$name}\n";
        $count++;
    }

    echo $dryRun
        ? "\nDone. {$count} file(s) parsed, nothing executed.\n"
        : "\nDone. {$count} migration(s) ran.\n";

} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
