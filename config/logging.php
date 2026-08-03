<?php

declare(strict_types=1);

return [
    'channel' => $_ENV['LOG_CHANNEL'] ?? 'file',
    'level'   => $_ENV['LOG_LEVEL']   ?? 'error',

    'channels' => [
        'file' => [
            'driver' => 'daily',
            'path'   => BASE_PATH . '/storage/logs/app.log',
            'days'   => 14,
        ],
    ],

    'levels' => [
        'debug'     => 100,
        'info'      => 200,
        'notice'    => 250,
        'warning'   => 300,
        'error'     => 400,
        'critical'  => 500,
        'alert'     => 550,
        'emergency' => 600,
    ],
];
