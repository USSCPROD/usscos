<?php

declare(strict_types=1);

return [
    'name'     => $_ENV['APP_NAME']  ?? 'USSCOS',
    'env'      => $_ENV['APP_ENV']   ?? 'production',
    'debug'    => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url'      => $_ENV['APP_URL']   ?? 'http://localhost',
    'key'      => $_ENV['APP_KEY']   ?? '',
    'timezone' => 'America/New_York',
    'locale'   => 'en_US',
    'currency' => 'USD',

    'version'  => '1.0.0',

    'providers' => [],
];
