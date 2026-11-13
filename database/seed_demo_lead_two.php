<?php

declare(strict_types=1);

/**
 * A second walkthrough lead, with an opportunity and the tasks around it.
 *
 *   php database/seed_demo_lead_two.php            dry run
 *   php database/seed_demo_lead_two.php --commit   writes it
 *
 * The first one (Cherokee County Parks) shows the straight line: enquiry, quote,
 * order, ship, invoice. This one shows the part a salesperson actually lives in —
 * a deal being worked, with things to do and dates they are due.
 *
 * A school district rather than a parks department, so the two demos do not look
 * like the same customer twice, and deliberately mid-flight: one task already done,
 * one due, one overdue. A board of tasks that is all green shows nothing.
 *
 * Safe to re-run: refuses if the lead already exists.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;

const REP_USER  = 40;   // Chris Danis
const OWNER     = 1;    // Chip
const LEAD_MAIL = 'rmontoya@fultonschools.org';

$commit = in_array('--commit', array_slice($argv, 1), true);

$existing = Database::selectOne('SELECT id FROM leads WHERE email = ?', [LEAD_MAIL]);

if ($existing !== false) {
    echo "Already there as lead {$existing['id']}. Nothing to do.\n";
    exit(0);
}

$lead = [
    'company_name' => 'Fulton County School District — Athletics',
    'first_name'   => 'Ray',
    'last_name'    => 'Montoya',
    'email'        => LEAD_MAIL,
    'phone'        => '470-254-3600',
    'website'      => 'www.fultonschools.org',
    'source'       => 'trade_show',
    'status'       => 'contacted',
    'rep_id'       => REP_USER,
    'created_by'   => OWNER,
    'notes'        => "Met at the GAHPERD show in Macon. Athletics director for the district — "
        . "19 high schools and middle schools, football and soccer fields at most of them.\n\n"
        . "They buy through a district bid every year and the current supplier is up for "
        . "renewal in the spring. Ray wants a price on DuraStripe in white and yellow, by "
        . "the pallet rather than the case, and asked whether we can hold a price through "
        . "the season.\n\n"
        . "Wants a sample case of white before he puts us on the bid list. Said the "
        . "district will not look at anything that cannot ship inside a week in season.",
];

$opportunity = [
    'name'           => 'Fulton County Schools — 2027 season bid',
    'stage'          => 'prospecting',
    'expected_value' => 24000.00,
    'probability'    => 25,
    'expected_close' => '2027-03-31',
    'rep_id'         => REP_USER,
    'created_by'     => OWNER,
    'notes'          => "District bid, renews in the spring. 19 schools, mostly football and "
        . "soccer. Figure is a season across the district at pallet pricing, not the sample "
        . "or a single school.\n\n"
        . "Two things to beat: the incumbent's price, and a week's lead time in season.",
];

$tasks = [
    [
        'title'       => 'Send sample case of DuraStripe white',
        'description' => "Ray will not put us on the bid list without seeing it. One case of "
                       . "DSW, no charge, to the athletics office.",
        'status'      => 'completed',
        'priority'    => 'high',
        'due_date'    => date('Y-m-d', strtotime('-6 days')),
        'completed_at'=> date('Y-m-d H:i:s', strtotime('-5 days')),
    ],
    [
        'title'       => 'Call Ray about the sample',
        'description' => "A week since the sample went. Find out whether it held up and "
                       . "whether we are on the bid list.",
        'status'      => 'open',
        'priority'    => 'high',
        'due_date'    => date('Y-m-d', strtotime('-2 days')),   // overdue on purpose
    ],
    [
        'title'       => 'Price a pallet — white and yellow',
        'description' => "Pallet pricing rather than case, and whether we can hold it through "
                       . "the season. Check the freight on a pallet to Atlanta first: at this "
                       . "size the delivered price is what gets compared, not ours.",
        'status'      => 'open',
        'priority'    => 'medium',
        'due_date'    => date('Y-m-d', strtotime('+3 days')),
    ],
    [
        'title'       => 'Confirm bid closing date with the district',
        'description' => "Renewal is 'in the spring'. Get the actual date before the "
                       . "expected close on the opportunity is a guess.",
        'status'      => 'open',
        'priority'    => 'low',
        'due_date'    => date('Y-m-d', strtotime('+10 days')),
    ],
];

echo "Lead\n", str_repeat('-', 64), "\n";
foreach ($lead as $k => $v) {
    printf("  %-14s %s\n", $k, str_replace("\n", "\n" . str_repeat(' ', 17), (string)$v));
}

echo "\nOpportunity\n", str_repeat('-', 64), "\n";
foreach ($opportunity as $k => $v) {
    printf("  %-16s %s\n", $k, str_replace("\n", "\n" . str_repeat(' ', 19), (string)$v));
}

echo "\nTasks\n", str_repeat('-', 64), "\n";
foreach ($tasks as $t) {
    printf("  [%-9s] %-9s due %-12s %s\n", $t['status'], $t['priority'], $t['due_date'], $t['title']);
}

if (!$commit) {
    echo "\nDry run. Add --commit to write it.\n";
    exit(0);
}

$pdo = Database::connection();
$pdo->beginTransaction();

try {
    Database::statement(
        'INSERT INTO leads (company_name, first_name, last_name, email, phone, website,
                            source, status, rep_id, created_by, notes)
         VALUES (:company_name, :first_name, :last_name, :email, :phone, :website,
                 :source, :status, :rep_id, :created_by, :notes)',
        array_combine(array_map(fn ($k) => ':' . $k, array_keys($lead)), array_values($lead))
    );

    $leadId = (int)$pdo->lastInsertId();

    Database::statement(
        'INSERT INTO opportunities (name, lead_id, rep_id, stage, expected_value, probability,
                                    expected_close, notes, created_by)
         VALUES (:name, :lead_id, :rep_id, :stage, :expected_value, :probability,
                 :expected_close, :notes, :created_by)',
        [
            ':name'           => $opportunity['name'],
            ':lead_id'        => $leadId,
            ':rep_id'         => $opportunity['rep_id'],
            ':stage'          => $opportunity['stage'],
            ':expected_value' => $opportunity['expected_value'],
            ':probability'    => $opportunity['probability'],
            ':expected_close' => $opportunity['expected_close'],
            ':notes'          => $opportunity['notes'],
            ':created_by'     => $opportunity['created_by'],
        ]
    );

    $oppId = (int)$pdo->lastInsertId();

    foreach ($tasks as $t) {
        Database::statement(
            'INSERT INTO tasks (title, description, status, priority, due_date, completed_at,
                                assigned_to, created_by, lead_id, opportunity_id)
             VALUES (:title, :description, :status, :priority, :due_date, :completed_at,
                     :assigned_to, :created_by, :lead_id, :opportunity_id)',
            [
                ':title'        => $t['title'],
                ':description'  => $t['description'],
                ':status'       => $t['status'],
                ':priority'     => $t['priority'],
                ':due_date'     => $t['due_date'],
                ':completed_at' => $t['completed_at'] ?? null,
                ':assigned_to'  => REP_USER,
                ':created_by'   => OWNER,
                ':lead_id'      => $leadId,
                ':opportunity_id' => $oppId,
            ]
        );
    }

    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "\nRolled back: " . $e->getMessage() . "\n");
    exit(1);
}

echo "\nLead {$leadId}, opportunity {$oppId}, " . count($tasks) . " tasks.\n";
echo "Open it at /leads/{$leadId}\n";
