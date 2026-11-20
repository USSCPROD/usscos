<?php
// Point this at your BusinessOS install
define('USSCOS_WEBHOOK', 'https://os.usscos.com/webhook/lead');
define('SITE_NAME', 'US Specialty Coatings');

// The shared secret the webhook will accept. Read out of the USSCOS .env rather than
// copied, so there is one copy of it: change it there and this follows. The webhook
// accepts requests without it today and logs them; once these forms send it, requiring
// it is a one-line change at the other end.
$ussc_env = @file_get_contents('/var/www/usscos/.env');
if (preg_match('/^LEAD_WEBHOOK_SECRET=(.*)$/m', (string) $ussc_env, $ussc_m)) {
    define('USSCOS_WEBHOOK_SECRET', trim($ussc_m[1]));
} else {
    define('USSCOS_WEBHOOK_SECRET', '');
}
