<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__, 2));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

use App\Core\Config;
use App\Core\Database;

Config::load(BASE_PATH . '/config');
Database::init();

// -------------------------------------------------------------------------
// Seed default company
// -------------------------------------------------------------------------

$companyId = Database::insert(
    'INSERT INTO companies (name, slug, email, timezone, currency) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)',
    ['Acme Manufacturing', 'acme', 'info@acme.com', 'America/New_York', 'USD']
);

echo "Company seeded. ID: {$companyId}\n";

// -------------------------------------------------------------------------
// Seed admin user
// -------------------------------------------------------------------------

$password = password_hash('password', PASSWORD_BCRYPT, ['cost' => 12]);

Database::insert(
    'INSERT INTO users (company_id, first_name, last_name, email, password, role, is_active)
     VALUES (?, ?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)',
    [$companyId, 'Mike', 'Johnson', 'admin@acme.com', $password, 'owner', 1]
);

echo "Admin user seeded. Email: admin@acme.com / Password: password\n";
echo "\nSeeding complete.\n";
