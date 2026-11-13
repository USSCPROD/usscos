<?php

declare(strict_types=1);

/**
 * Remove the website leads that are spam.
 *
 *   php database/remove_spam_leads.php            dry run — lists them, writes nothing
 *   php database/remove_spam_leads.php --commit   deletes them
 *   php database/remove_spam_leads.php --dead     marks them dead instead of deleting
 *
 * usscos.com's forms had no spam protection until 13 Nov 2026, so bots posting through
 * them became leads and emailed a rep. This clears what they left behind. The forms now
 * carry a honeypot, a minimum completion time and a rate limit, so this should be a
 * one-off rather than a chore.
 *
 * It refuses to touch a lead that anybody has done anything with — converted, or with a
 * quote, opportunity or task hanging off it. A lead that has been worked is not spam
 * whatever its wording, and this must not be the thing that loses one.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;

$args   = array_slice($argv, 1);
$commit = in_array('--commit', $args, true);
$dead   = in_array('--dead', $args, true);

/*
 * --web takes every lead whose source is 'web' rather than matching on wording.
 *
 * Chip, 13 Nov 2026: usscos.com is not a live site — nothing links to it and no
 * customer has reason to find it — so a lead that came through its forms is spam by
 * definition. That is a far better rule than guessing from the text, which let
 * "I would like more information. Please contact me by email — us specialty coatings"
 * through because the phrase was not at the end of the note.
 *
 * Leads that anybody has worked are still protected, whichever mode is used.
 */
$webMode = in_array('--web', $args, true);

/*
 * Signals, not one rule. Each is weak alone; a lead matching any of them, having never
 * been worked, is not worth a person's attention.
 */
const PATTERNS = [
    'SEO or web-design pitch' =>
        '(search index|seo|rank(ing)? (higher|on google)|backlink|web ?design|'
        . 'domain (is )?(available|expiring)|indexhelper|submit your (site|website)|'
        . 'boost your (sales|traffic)|digital marketing|guest post|link building|'
        . 'optimization could boost)',
    'Crypto or finance'       =>
        '(crypto|bitcoin|forex|invest(ment)? opportunit|loan offer)',
    'Subscription bot'        =>
        '(i would like more information\\.?$|please send me news and updates|'
        . 'interested in new content|please let me know when i am subscribed|'
        . "i'?d like to subscribe)",
];

$found = [];

if ($webMode) {
    $rows = Database::select(
        "SELECT l.id, l.company_name, l.email, l.created_at, l.status,
                LEFT(REPLACE(REPLACE(l.notes, '\n', ' '), '\r', ''), 64) AS snippet
           FROM leads l
          WHERE l.source = 'web'
            AND l.customer_id IS NULL
            AND l.status <> 'converted'
            AND NOT EXISTS (SELECT 1 FROM quotes q        WHERE q.lead_id = l.id)
            AND NOT EXISTS (SELECT 1 FROM opportunities o WHERE o.lead_id = l.id)
            AND NOT EXISTS (SELECT 1 FROM tasks t         WHERE t.lead_id = l.id)
          ORDER BY l.id"
    );

    foreach ($rows as $r) {
        $found[(int)$r['id']] = $r + ['why' => 'came through usscos.com'];
    }
}

foreach ($webMode ? [] : PATTERNS as $label => $rx) {
    $rows = Database::select(
        "SELECT l.id, l.company_name, l.email, l.created_at, l.status,
                LEFT(REPLACE(REPLACE(l.notes, '\\n', ' '), '\\r', ''), 64) AS snippet
           FROM leads l
          WHERE l.customer_id IS NULL
            AND l.status <> 'converted'
            AND NOT EXISTS (SELECT 1 FROM quotes q        WHERE q.lead_id = l.id)
            AND NOT EXISTS (SELECT 1 FROM opportunities o WHERE o.lead_id = l.id)
            AND NOT EXISTS (SELECT 1 FROM tasks t         WHERE t.lead_id = l.id)
            AND (l.notes REGEXP :rx OR l.company_name REGEXP :rx2 OR l.email REGEXP :rx3)
          ORDER BY l.id",
        [':rx' => $rx, ':rx2' => $rx, ':rx3' => $rx]
    );

    foreach ($rows as $r) {
        $found[(int)$r['id']] = $r + ['why' => $label];
    }
}

if ($found === []) {
    echo "Nothing matched. Inbox is clean.\n";
    exit(0);
}

printf("%-6s %-26s %-32s %-12s %s\n", 'ID', 'NAME', 'EMAIL', 'DATE', 'WHY');
echo str_repeat('-', 108), "\n";

foreach ($found as $r) {
    printf("%-6s %-26s %-32s %-12s %s\n", $r['id'],
        substr(trim((string)$r['company_name']), 0, 24),
        substr((string)$r['email'], 0, 30),
        substr((string)$r['created_at'], 0, 10),
        $r['why']);
}

echo str_repeat('-', 108), "\n";
printf("%d lead(s).\n", count($found));

if (!$commit && !$dead) {
    echo "\nDry run. --commit deletes them, --dead marks them dead and keeps them.\n";
    exit(0);
}

$ids = implode(',', array_keys($found));
$pdo = Database::connection();
$pdo->beginTransaction();

try {
    if ($dead) {
        Database::statement("UPDATE leads SET status = 'dead' WHERE id IN ({$ids})");
        $verb = 'marked dead';
    } else {
        Database::statement("DELETE FROM leads WHERE id IN ({$ids})");
        $verb = 'deleted';
    }

    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Rolled back: " . $e->getMessage() . "\n");
    exit(1);
}

printf("\n%d lead(s) %s.\n", count($found), $verb);

$left = Database::selectOne("SELECT COUNT(*) n FROM leads WHERE status <> 'dead'");
printf("Leads remaining: %s\n", $left['n']);
