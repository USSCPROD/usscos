<?php

declare(strict_types=1);

/**
 * One realistic lead, for walking the flow in front of somebody.
 *
 *   php database/seed_demo_lead.php            dry run, prints what it would create
 *   php database/seed_demo_lead.php --commit   writes it
 *
 * Deliberately a parks department buying DuraStripe line-marking aerosol: a credible
 * first order, a FedEx-sized quantity rather than a pallet, and it exercises the
 * cartonizer properly because the case weight and pack quantity are both real.
 *
 * The lead is the starting point only. Quoting it, converting it and shipping it are
 * the walkthrough; this just puts a believable thing on the board to start from.
 *
 * Safe to re-run: it refuses if a lead with this email already exists rather than
 * making a second one.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(BASE_PATH)->load();
App\Core\Config::load(BASE_PATH . '/config');

use App\Core\Database;

$commit = in_array('--commit', array_slice($argv, 1), true);

$lead = [
    'company_name' => 'Cherokee County Parks & Recreation',
    'first_name'   => 'Denise',
    'last_name'    => 'Whitaker',
    'email'        => 'dwhitaker@cherokeecountyparks.org',
    'phone'        => '770-924-7768',
    'website'      => 'www.cherokeecountyparks.org',
    'source'       => 'web',
    'status'       => 'new',
    'rep_id'       => 40,   // Chris Danis
    'created_by'   => 1,    // Chip
    'notes'        => "Subject: Field marking paint — spring season\n\n"
        . "We maintain 14 multi-use fields across four parks and currently buy marking "
        . "aerosol from a local distributor. Their white has been inconsistent this year "
        . "and we are looking at alternatives before the spring season starts.\n\n"
        . "Interested in DuraStripe. We would need white and yellow to begin with, "
        . "roughly 20 cases of white and 10 of yellow to cover the first few months, and "
        . "we would want it shipped rather than collected.\n\n"
        . "Can you quote that, and do you offer any price break for a seasonal order? "
        . "Our purchasing runs through the county so I will need something in writing "
        . "with a quote number before I can raise a PO.",
];

echo "Lead to create\n";
echo str_repeat('-', 62), "\n";
foreach ($lead as $k => $v) {
    printf("  %-14s %s\n", $k, str_replace("\n", "\n" . str_repeat(' ', 17), (string)$v));
}
echo str_repeat('-', 62), "\n";

$existing = Database::selectOne('SELECT id FROM leads WHERE email = ?', [$lead['email']]);

if ($existing !== false) {
    echo "\nAlready there as lead {$existing['id']}. Nothing to do.\n";
    exit(0);
}

if (!$commit) {
    echo "\nDry run. Add --commit to write it.\n";
    exit(0);
}

Database::statement(
    'INSERT INTO leads (company_name, first_name, last_name, email, phone, website,
                        source, status, rep_id, created_by, notes)
     VALUES (:company_name, :first_name, :last_name, :email, :phone, :website,
             :source, :status, :rep_id, :created_by, :notes)',
    array_combine(
        array_map(fn ($k) => ':' . $k, array_keys($lead)),
        array_values($lead)
    )
);

$id = (int)Database::connection()->lastInsertId();

echo "\nCreated lead {$id}. Open it at /leads/{$id}\n";
