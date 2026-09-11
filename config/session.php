<?php

declare(strict_types=1);

return [
    'driver'    => 'file',
    'lifetime'  => (int) ($_ENV['SESSION_LIFETIME'] ?? 120),
    'secure'    => filter_var($_ENV['SESSION_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'http_only' => true,
    'same_site' => 'lax',
    'path'      => BASE_PATH . '/storage/sessions',
    'cookie'    => 'usscos_session',
];
