<?php

declare(strict_types=1);

return [
    'guard'    => 'session',
    'provider' => 'users',

    'session' => [
        'key'        => 'auth_user',
        'lifetime'   => (int) ($_ENV['SESSION_LIFETIME'] ?? 120),
    ],

    'password' => [
        'algo'       => PASSWORD_BCRYPT,
        'cost'       => 12,
        'min_length' => 8,
    ],

    'remember' => [
        'enabled'  => true,
        'lifetime' => 60 * 24 * 30, // 30 days in minutes
        'cookie'   => 'remember_token',
    ],

    'throttle' => [
        'max_attempts' => 5,
        'decay_minutes' => 15,
    ],
];
