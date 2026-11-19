<?php

declare(strict_types=1);

/**
 * The old stencil shop's customer accounts, into USSCOS website accounts.
 *
 *   php database/import_shop_accounts.php storage/stencil-data/shop_accounts.json            dry run
 *   php database/import_shop_accounts.php storage/stencil-data/shop_accounts.json --commit   write
 *
 * The JSON comes from database/tools/export_shop_accounts.py, run against the shop's
 * designer.db. It is private (password hashes): keep it in storage/stencil-data/ and delete
 * it afterwards.
 *
 * What comes across:
 *  - Customers and distributors, keeping their passwords (the shop's PBKDF2 hash, which
 *    CustomerAccounts checks and replaces with a modern hash on first sign-in). Nobody has
 *    to reset. None is linked to a customer: staff do that, as for any sign-up. Shop
 *    distributors are flagged 'says distributor' so they are at the top of that list.
 *  - Saved designs, except midfields whose logo upload is not on this server.
 *  - Saved (not yet approved) quotes. Approved ones were orders the shop already handled.
 *
 * What does not: shop staff ('admin') logins, which belong in USSCOS as staff users;
 * activity logs; sessions. An email already in customer_logins is skipped, so a second run
 * changes nothing.
 *
 * Dry run by default; one transaction; nothing deleted.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;
use App\Repositories\CustomerLoginRepository;
use App\Repositories\SavedWorkRepository;
use App\Services\Stencils\MidfieldDesigner;

$args   = array_slice($argv, 1);
$commit = in_array('--commit', $args, true);
$file   = array_values(array_filter($args, static fn($a) => $a !== '--commit'))[0] ?? '';

if ($file === '' || !is_readable($file)) {
    fwrite(STDERR, "Usage: php database/import_shop_accounts.php <shop_accounts.json> [--commit]\n");
    exit(1);
}

$data = json_decode((string)file_get_contents($file), true);

if (!is_array($data) || !isset($data['users'])) {
    fwrite(STDERR, "That file is not an export from export_shop_accounts.py.\n");
    exit(1);
}

$logins = new CustomerLoginRepository();
$work   = new SavedWorkRepository();
$when   = static fn($t) => $t ? date('Y-m-d H:i:s', (int)$t) : null;
$types  = array_keys(App\Services\Web\CustomerAccounts::BUYER_TYPES);

$n = ['users' => 0, 'distributors' => 0, 'blocked' => 0, 'skip_exists' => 0, 'skip_staff' => 0, 'skip_bad' => 0,
      'designs' => 0, 'skip_logo' => 0, 'quotes' => 0, 'skip_approved' => 0, 'quote_items_dropped' => 0];
$newId = [];   // shop user id → customer_logins id

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    foreach ($data['users'] as $u) {
        $email = strtolower(trim((string)($u['email'] ?? '')));

        if (($u['role'] ?? '') === 'admin') {
            $n['skip_staff']++;
            continue;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($u['hash']) || empty($u['salt'])) {
            $n['skip_bad']++;
            continue;
        }
        if ($logins->findByEmail($email)) {
            $n['skip_exists']++;
            continue;
        }

        $dist = ($u['role'] ?? '') === 'distributor';
        $id = $logins->create([
            'email' => $email, 'legacy_salt' => (string)$u['salt'], 'legacy_hash' => (string)$u['hash'],
            'legacy_iterations' => (int)$u['iterations'], 'name' => mb_substr((string)($u['name'] ?: $email), 0, 100),
            'phone' => mb_substr((string)$u['phone'], 0, 40), 'organization' => mb_substr((string)$u['org'], 0, 150),
            'buyer_type' => in_array($u['buyer_type'], $types, true) ? $u['buyer_type'] : 'other',
            'asked_trade' => $dist ? 1 : 0, 'source' => 'stencil_shop', 'shop_role' => (string)($u['role'] ?? 'customer'),
            'signup_ip' => $u['signup_ip'] ?? null, 'created_at' => $when($u['created'] ?? null),
        ]);

        if (!empty($u['blocked'])) {
            $logins->setStatus($id, 'blocked', mb_substr('From the stencil shop: ' . (string)$u['blocked_note'], 0, 255));
            $n['blocked']++;
        }

        $newId[(int)$u['id']] = $id;
        $n['users']++;
        $n['distributors'] += $dist ? 1 : 0;
    }

    foreach ($data['designs'] ?? [] as $d) {
        $owner = $newId[(int)$d['user_id']] ?? null;

        if ($owner === null) {
            continue;
        }

        $kind = (string)$d['tool'];
        $design = is_array($d['data']) ? $d['data'] : [];

        if ($kind === 'midfield' && MidfieldDesigner::path((string)($design['logo'] ?? ''), 'logo.png') === null) {
            $n['skip_logo']++;
            continue;
        }
        if (!in_array($kind, ['endzone', 'midfield', 'text'], true)) {
            continue;
        }

        $work->createDesign($owner, $kind, mb_substr((string)($d['name'] ?: 'Design'), 0, 120), ['kind' => $kind] + $design,
                            mb_substr((string)$d['summary'], 0, 255), $d['price'] !== null ? (float)$d['price'] : null);
        $n['designs']++;
    }

    foreach ($data['quotes'] ?? [] as $q) {
        $owner = $newId[(int)$q['user_id']] ?? null;

        if ($owner === null) {
            continue;
        }
        if (($q['status'] ?? '') !== 'saved') {
            $n['skip_approved']++;
            continue;
        }

        $items = array_values(array_filter($q['items'] ?? [], static fn($i) => ($i['kind'] ?? '') !== 'unknown'));
        $n['quote_items_dropped'] += count($q['items'] ?? []) - count($items);

        if ($items === []) {
            continue;
        }

        $work->createQuote($owner, mb_substr((string)($q['name'] ?: 'Quote'), 0, 120), $items, (float)($q['wholesale'] ?? 0));
        $n['quotes']++;
    }

    if ($commit) {
        $pdo->commit();
    } else {
        $pdo->rollBack();
    }
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Stopped, nothing written: " . $e->getMessage() . "\n");
    exit(1);
}

printf("%s\n", $commit ? 'Imported.' : 'Dry run: nothing written. Add --commit to import.');
printf("  accounts:            %d (%d distributors, %d paused)\n", $n['users'], $n['distributors'], $n['blocked']);
printf("  skipped:             %d already here, %d shop staff, %d without email or password\n", $n['skip_exists'], $n['skip_staff'], $n['skip_bad']);
printf("  saved designs:       %d (%d midfields skipped: logo not on this server)\n", $n['designs'], $n['skip_logo']);
printf("  saved quotes:        %d (%d approved ones left behind; %d items no longer in the catalog dropped)\n",
       $n['quotes'], $n['skip_approved'], $n['quote_items_dropped']);
echo "\nNone is linked to a customer yet: link them in USSCOS under Website accounts.\n";
