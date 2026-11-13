# usscos.com — the lead capture forms

A separate site from this application, served from `/var/www/usscos.com` on the same
droplet: `contact.php`, `paint-quote.php`, `stencil-quote.php`, posting to
`/webhook/lead` here. **This is where the website leads have been coming from** —
usscproducts.com, the WordPress storefront, is a second source still to be wired
(see [../wordpress/README.md](../wordpress/README.md)).

`spam.php` here is the copy of record for the guard that is deployed there. The live
files are not in this repository; `.backups/` beside them holds what was there before
13 November 2026.

## The guard

Three forms posted straight into USSCOS, created a lead and emailed a rep, with
**nothing on them at all** — no captcha, no honeypot, no rate limit. Bots found them.
An SEO pitch, a subscription bot and a vague "I would like more information" all became
leads. A lead list full of that is one people stop reading, which costs more than the
spam does.

| Check | What it catches | Why it will not catch a customer |
|---|---|---|
| **Honeypot** — hidden `website` and `company_url` fields | Form-fillers that complete every field | Positioned off-screen with `tabindex="-1"`; a person never sees or tabs to it |
| **Time to complete** — under 3 seconds from page load | Scripts that post without rendering | Below any real reading and typing. A *missing* timestamp is not held against anybody, so a cached page or a stripped hidden field cannot block a sale |
| **Rate limit** — one submission per address per 5 minutes | Repeat submission | Generous for a person. If the limit cannot be written it allows the submission rather than blocking it |

**A caught submission is shown the ordinary thank-you and silently dropped**, with no
validation error beside it. Saying "you look like a bot" only teaches whoever wrote it
which check to beat next.

Every drop is written to the PHP error log with its reason, so it is possible to tell a
working filter from a broken form.

## Tested against the live site, 13 Nov 2026

| | Result |
|---|---|
| Page renders, honeypot fields present | pass |
| Honeypot filled → no lead | pass |
| Submitted instantly → no lead | pass |
| **Genuine submission → lead created and routed** | **pass** |

That last row is the one that matters. A filter that stops spam and also stops customers
has made things worse.

## The webhook secret

These forms now send `X-USSCOS-Secret`, read by `config.php` out of
`/var/www/usscos/.env` rather than copied — one copy of the secret, changed in one place.

The endpoint **accepts requests without it** and logs them. It briefly required it on
13 Nov 2026, which took live lead capture down until it was noticed: these forms did not
send it yet. A change that has to land on both sides at once cannot land on one.

Once the logs stop showing requests with no secret — and once usscproducts.com sends it
too — requiring it is one line in `WebhookController::lead()`.

**The secret was never the fix for the spam.** The junk arrived *through* the forms, not
by posting at the endpoint. The two protect against different things and both are worth
having.
