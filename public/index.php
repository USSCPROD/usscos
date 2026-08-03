<?php

declare(strict_types=1);

define('BASE_PATH', '/home/t2a2ymc1f3z4/public_html/businessos');define('PUBLIC_PATH', '/home/t2a2ymc1f3z4/public_html/usscos.com');
define('START_TIME', microtime(true));
define('START_MEMORY', memory_get_usage());

require BASE_PATH . '/vendor/autoload.php';

$app = require BASE_PATH . '/bootstrap/app.php';

$app->run();
