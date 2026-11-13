<?php

declare(strict_types=1);

return [
    'name'     => $_ENV['APP_NAME']  ?? 'USSCOS',
    'env'      => $_ENV['APP_ENV']   ?? 'production',
    'debug'    => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url'      => $_ENV['APP_URL']   ?? 'http://localhost',
    'key'      => $_ENV['APP_KEY']   ?? '',
    'timezone' => 'America/New_York',

    // Shared secret the WordPress site sends on /webhook/lead. Unset means the endpoint
    // refuses everything rather than accepting anything — see WebhookController::lead().
    'lead_webhook_secret' => trim((string)($_ENV['LEAD_WEBHOOK_SECRET'] ?? '')),
    'locale'   => 'en_US',
    'currency' => 'USD',

    'version'  => '1.0.0',

    'providers' => [],
];
