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

    /*
     * The secret being retired, accepted alongside the current one while a rotation is
     * in flight.
     *
     * Rotating needs the server and every sender to change together or leads 401 in the
     * gap. One of the senders is a WordPress snippet somebody has to paste by hand, so
     * "together" is not something that can be arranged - hence an overlap window: set the
     * new secret here as current, leave the old one as previous, update the senders, then
     * clear this. Every use of the previous secret is logged, so the window can be closed
     * the moment the log goes quiet rather than on a guess.
     */
    'lead_webhook_secret_previous' => trim((string)($_ENV['LEAD_WEBHOOK_SECRET_PREVIOUS'] ?? '')),

    /*
     * Whether a secret is REQUIRED, as opposed to merely checked when sent.
     *
     * This is the setting that actually protects the endpoint. Until it is true a request
     * with no secret at all is accepted, which means a leaked secret costs nothing to work
     * around: omit it. Rotating without this is theatre.
     *
     * Default false because turning it on while any sender is still silent takes live lead
     * capture down - which is exactly what happened on 13 Nov 2026. Turn it on once the
     * "no secret sent" lines stop appearing in the log.
     */
    'lead_webhook_require_secret' => filter_var(
        $_ENV['LEAD_WEBHOOK_REQUIRE_SECRET'] ?? false,
        FILTER_VALIDATE_BOOL
    ),
    'locale'   => 'en_US',
    'currency' => 'USD',

    'version'  => '1.0.0',

    'providers' => [],
];
