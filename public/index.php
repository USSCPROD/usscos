<?php

declare(strict_types=1);

// Resolved from this file's own location, so the same code runs unchanged on a
// laptop, on cPanel, and on a droplet. Do not hardcode a path here.
// PUBLIC_PATH is defined in bootstrap/app.php once the environment has loaded.
define('BASE_PATH', dirname(__DIR__));

define('START_TIME', microtime(true));
define('START_MEMORY', memory_get_usage());

require BASE_PATH . '/vendor/autoload.php';

$app = require BASE_PATH . '/bootstrap/app.php';

$app->run();
