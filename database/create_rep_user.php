<?php

declare(strict_types=1);

/**
 * Give a sales rep a login, and tie it to their rep record.
 *
 *   php database/create_rep_user.php --email=cdanis@usspecialtycoatings.com \
 *       --first=Chris --last=Danis --rep="Chris Danis"
 *   ...add --commit to write.
 *
 * Two things have to line up for rep scoping to work, and only one of them is a user
 * account:
 *
 *   users.role = 'rep'      AccessScope restricts this role to its own customers
 *   sales_reps.user_id      how it finds WHICH rep the login belongs to
 *
 * With the role and no link the rep sees nothing at all — AccessScope fails closed on
 * purpose, because the safe reading of "no rep record" is "no customers" rather than
 * "all of them". So this script refuses to create a login it cannot link, and names
 * the rep records it can see when the name does not match.
 *
 * --create-rep makes the sales_reps row too, for a rep QuickBooks has never heard of.
 *
 * THE PASSWORD IS NEVER PRINTED, the same as create_warehouse_user.php. It is written
 * to a file outside the web root and the script reports the path. A password echoed to
 * a terminal lives on in scrollback and in whatever copied it.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;

$args   = array_slice($argv, 1);
$commit = in_array('--commit', $args, true);
$create = in_array('--create-rep', $args, true);

$opt = ['email' => null, 'first' => null, 'last' => null, 'rep' => null, 'password-file' => null];

foreach ($args as $a) {
    foreach ($opt as $k => $_) {
        if (str_starts_with($a, "--{$k}=")) {
            $opt[$k] = substr($a, strlen($k) + 3);
        }
    }
}

foreach (['email', 'first', 'last', 'rep'] as $required) {
    if (($opt[$required] ?? '') === '') {
        fwrite(STDERR, "Missing --{$required}=\n");
        exit(1);
    }
}

if (!filter_var($opt['email'], FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "That email address does not look valid.\n");
    exit(1);
}

if (!$commit) {
    echo "DRY RUN — nothing will be written. Add --commit to apply.\n\n";
}

$existingUser = Database::selectOne('SELECT id, role FROM users WHERE email = ?', [$opt['email']]);

if ($existingUser !== false) {
    printf("A login already exists for %s (user #%d, role %s).\n", $opt['email'], $existingUser['id'], $existingUser['role']);
    printf("Nothing created. Change their role and link the rep by hand if that is the right account.\n");
    exit(1);
}

$rep = Database::selectOne('SELECT id, name, user_id FROM sales_reps WHERE name = ?', [$opt['rep']]);

if ($rep === false && !$create) {
    printf("No rep called \"%s\".\n", $opt['rep']);
    printf("Either the spelling differs from QuickBooks, or they are new — add --create-rep.\n\n");
    printf("Reps whose name looks close:\n");

    foreach (Database::select(
        'SELECT name FROM sales_reps WHERE is_active = 1 AND SOUNDEX(name) = SOUNDEX(?) ORDER BY name',
        [$opt['rep']]
    ) as $r) {
        printf("    %s\n", $r['name']);
    }

    exit(1);
}

if ($rep !== false && $rep['user_id'] !== null) {
    printf("Rep \"%s\" is already linked to user #%d. Nothing to do.\n", $rep['name'], $rep['user_id']);
    exit(1);
}

$name = trim($opt['first'] . ' ' . $opt['last']);

printf("%-18s %s\n", 'Login', $opt['email']);
printf("%-18s %s\n", 'Name', $name);
printf("%-18s %s\n", 'Role', 'rep — sees only their own customers');
printf("%-18s %s\n", 'Rep record', $rep === false ? $opt['rep'] . ' (will be created)' : $opt['rep'] . ' (#' . $rep['id'] . ')');

if ($rep !== false) {
    $n = Database::selectOne('SELECT COUNT(*) n FROM customers WHERE sales_rep_id = ?', [(int)$rep['id']]);
    printf("%-18s %d customer(s) become visible to this login\n", 'Scope', (int)$n['n']);
}

if (!$commit) {
    echo "\nNothing written. Re-run with --commit to apply.\n";
    exit(0);
}

$passwordFile = $opt['password-file'] ?: BASE_PATH . '/storage/rep-password-' . preg_replace('/\W/', '-', $opt['email']) . '.txt';
$password     = bin2hex(random_bytes(9));

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    // Columns copied from create_warehouse_user.php rather than guessed: the password
    // column is `password`, not `password_hash`, and company_id is NOT NULL.
    $companyId = (int)(Database::selectOne('SELECT company_id FROM users ORDER BY id LIMIT 1')['company_id'] ?? 1);

    $pdo->prepare("
        INSERT INTO users (company_id, first_name, last_name, email, username, password, role, is_active)
        VALUES (?, ?, ?, ?, ?, ?, 'rep', 1)
    ")->execute([
        $companyId,
        $opt['first'],
        $opt['last'],
        $opt['email'],
        // Reps sign in with their email; username is NOT NULL and unique, so it is set
        // to the same thing rather than left for somebody to wonder about.
        $opt['email'],
        password_hash($password, PASSWORD_DEFAULT),
    ]);

    $userId = (int)$pdo->lastInsertId();

    if ($rep === false) {
        $pdo->prepare("INSERT INTO sales_reps (name, user_id, is_active) VALUES (?, ?, 1)")
            ->execute([$opt['rep'], $userId]);
    } else {
        $pdo->prepare('UPDATE sales_reps SET user_id = ? WHERE id = ?')
            ->execute([$userId, (int)$rep['id']]);
    }

    if (@file_put_contents($passwordFile, $password . "\n") === false) {
        throw new \RuntimeException('Could not write the password file to ' . $passwordFile);
    }

    @chmod($passwordFile, 0600);

    $pdo->commit();

    printf("\nDone. User #%d created and linked.\n", $userId);
    printf("Password written to %s — read it, give it to them, delete the file.\n", $passwordFile);
} catch (\Throwable $e) {
    $pdo->rollBack();
    @unlink($passwordFile);
    fwrite(STDERR, "\nRolled back: " . $e->getMessage() . "\n");
    exit(1);
}
