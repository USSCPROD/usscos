<?php

declare(strict_types=1);

/**
 * Create a login for a warehouse worker who has no email address.
 *
 *   php database/create_warehouse_user.php --username=jose --first=Jose --last=Ramirez
 *   php database/create_warehouse_user.php --username=jose --first=Jose --last=Ramirez --commit
 *
 * The account gets the 'warehouse' role, which WarehouseScope limits to the pull
 * list, shipping and receiving. Not 'shipping' - that is the department role, held by
 * the shipping manager, who also enters orders and needs far more than this.
 *
 * THE PASSWORD IS NEVER PRINTED. It is generated here and written to a file that
 * only the person running this can read, and the script reports the path rather than
 * the value. A password echoed to a terminal ends up in scrollback, in a screen
 * recording, and in whatever copied the output — the one place it must not be is
 * anywhere it can be read later by someone who was not meant to have it.
 *
 * Pass --password-file= to choose where. The default sits outside the web root, so a
 * mistyped URL cannot serve it.
 *
 * users.email is NOT NULL and read all over the application, so a warehouse account
 * is given a placeholder address at an invalid domain. Nothing is ever sent to it and
 * nobody types it — the username is what they sign in with. The domain is .invalid,
 * reserved by RFC 2606 precisely so it can never resolve or receive mail.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;

$args   = array_slice($argv, 1);
$commit = in_array('--commit', $args, true);
$opt    = ['username' => null, 'first' => null, 'last' => null, 'password-file' => null];

foreach ($args as $a) {
    foreach ($opt as $k => $_) {
        if (str_starts_with($a, "--{$k}=")) {
            $opt[$k] = substr($a, strlen($k) + 3);
        }
    }
}

foreach (['username', 'first', 'last'] as $required) {
    if (($opt[$required] ?? '') === '' || $opt[$required] === null) {
        fwrite(STDERR, "Usage: php database/create_warehouse_user.php --username=jose --first=Jose --last=Ramirez [--commit]\n");
        exit(1);
    }
}

$username = strtolower(trim($opt['username']));

if (!preg_match('/^[a-z0-9._-]{2,50}$/', $username)) {
    fwrite(STDERR, "Username must be 2-50 characters: letters, digits, dot, dash or underscore.\n");
    exit(1);
}

$existing = Database::selectOne(
    "SELECT id, first_name, last_name FROM users WHERE LOWER(username) = ? OR email = ? LIMIT 1",
    [$username, $username . '@warehouse.invalid']
);

if ($existing) {
    fwrite(STDERR, sprintf(
        "Username '%s' is already taken by %s %s (id %d).\n",
        $username, $existing['first_name'], $existing['last_name'], $existing['id']
    ));
    exit(1);
}

$company = Database::selectOne("SELECT id FROM users WHERE company_id IS NOT NULL ORDER BY id LIMIT 1");
$companyId = (int)(Database::selectOne("SELECT company_id FROM users ORDER BY id LIMIT 1")['company_id'] ?? 1);

printf("%sWould create:\n", $commit ? '' : "DRY RUN — nothing will be written. Add --commit to apply.\n\n");
printf("  name     : %s %s\n", $opt['first'], $opt['last']);
printf("  username : %s\n", $username);
printf("  role     : warehouse  (pull list, shipping, receiving — nothing else)\n");
printf("  email    : %s@warehouse.invalid  (placeholder, never used)\n", $username);

if (!$commit) {
    echo "\nNothing written. Add --commit to create the account.\n";
    exit(0);
}

// Four words beat a short random string: it can be read aloud across a warehouse and
// typed on a tablet without a password manager, which is the actual situation here.
$words = ['anchor', 'basin', 'cobalt', 'draft', 'ember', 'forge', 'gravel', 'harbor',
          'indigo', 'jasper', 'kettle', 'lantern', 'marble', 'nickel', 'orchard',
          'pewter', 'quarry', 'ribbon', 'saddle', 'timber', 'umber', 'velvet',
          'walnut', 'yarrow', 'zinc'];

$password = '';

for ($i = 0; $i < 3; $i++) {
    $password .= ($i ? '-' : '') . $words[random_int(0, count($words) - 1)];
}

$password .= '-' . random_int(10, 99);

$file = $opt['password-file'] ?? (BASE_PATH . '/storage/warehouse-logins.txt');

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    $pdo->prepare(
        "INSERT INTO users (company_id, first_name, last_name, email, username, password, role, is_active)
         VALUES (?,?,?,?,?,?,'warehouse',1)"
    )->execute([
        $companyId,
        trim($opt['first']),
        trim($opt['last']),
        $username . '@warehouse.invalid',
        $username,
        password_hash($password, PASSWORD_DEFAULT),
    ]);

    $id = (int)$pdo->lastInsertId();

    $line = sprintf(
        "%s  %-14s %-24s %s\n",
        date('Y-m-d H:i'),
        $username,
        trim($opt['first'] . ' ' . $opt['last']),
        $password
    );

    if (@file_put_contents($file, $line, FILE_APPEND | LOCK_EX) === false) {
        throw new \RuntimeException("Could not write the password file at {$file}");
    }

    @chmod($file, 0600);

    $pdo->commit();

    printf("\nCreated user %d.\n", $id);
    printf("The password is in %s — it is NOT shown here on purpose.\n", $file);
    echo "Read it, give it to them, and delete the file when every account is handed out.\n";
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    throw $e;
}
