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
     * DO NOT CLEAR THIS. As at 24 Nov 2026 the live production site usscproducts.com
     * (WordPress, 160.153.189.158) posts real customer leads to this endpoint using the
     * PREVIOUS secret, and that site is deliberately not being touched - os.usscos.com is
     * still a test system. Clearing this, or setting LEAD_WEBHOOK_REQUIRE_SECRET, stops
     * production lead capture dead.
     *
     * It fails silently from this end: Contact Form 7 has already sent its own email and
     * shows the visitor a success message, the snippet writes the 401 to the WordPress
     * error log where nobody reads it, and the lead simply never arrives here. Nothing in
     * USSCOS says anything is wrong. That is the worst shape a failure comes in.
     *
     * So this is not a loose end to tidy. It stays until somebody has deliberately put the
     * current secret into Code Snippets snippet 9 on usscproducts.com, or that site has
     * been retired. Every use is logged with the sender, so you can tell which it is.
     */
    'lead_webhook_secret_previous' => trim((string)($_ENV['LEAD_WEBHOOK_SECRET_PREVIOUS'] ?? '')),

    /*
     * Whether a secret is REQUIRED, as opposed to merely checked when sent.
     *
     * This is the setting that actually protects the endpoint. Until it is true a request
     * with no secret at all is accepted, which means a leaked secret costs nothing to work
     * around: omit it. Rotating without this is theatre.
     *
     * Default false, and as at 24 Nov 2026 it must STAY false. Turning it on while any
     * sender is silent takes live lead capture down - that is exactly what happened on
     * 13 Nov 2026 - and usscproducts.com, which is the live production site and is not
     * being changed, authenticates with the retired secret rather than this one.
     *
     * The precondition is not "the log went quiet". It is: every sender has been given the
     * current secret, verified by the log showing neither "no secret sent" nor "PREVIOUS
     * secret used". Until then this flag is the difference between a working business and
     * a silent one.
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
