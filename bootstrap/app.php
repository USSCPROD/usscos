<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\Config;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Session;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

// Document root of the public marketing site, which lives outside this application
// on servers where the two are deployed separately (currently usscos.com). Used for
// shared assets such as the company logo. Set PUBLIC_SITE_PATH in .env to override.
define('PUBLIC_PATH', $_ENV['PUBLIC_SITE_PATH'] ?? BASE_PATH . '/public');

// Boot configuration
Config::load(BASE_PATH . '/config');

// Initialize logger
Logger::init();

// Set error handling based on environment
if (Config::get('app.debug')) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    Logger::error("PHP Error [{$severity}]: {$message} in {$file}:{$line}");
    if (Config::get('app.debug')) {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    }
    return true;
});

set_exception_handler(function (\Throwable $e): void {
    Logger::error("Uncaught exception: " . $e->getMessage(), [
        'file'  => $e->getFile(),
        'line'  => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);

    if (Config::get('app.debug')) {
        http_response_code(500);
        echo "<pre><b>Uncaught Exception:</b> " . htmlspecialchars($e->getMessage()) . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "Trace:\n" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    } else {
        http_response_code(500);
        require BASE_PATH . '/app/Views/errors/500.php';
    }
    exit(1);
});

// Boot database connection
Database::init();

// Start session
Session::start();

// Create and return the application
$app = new Application(BASE_PATH);

// Load routes
require BASE_PATH . '/routes/web.php';

return $app;
