# Handoff — the website, leads, and stencils

Written 19 November 2026 for a **new Claude Code session** picking up website work.
Start here; it is what the previous session knew and a fresh one does not.

> A session opened in this project reads `CLAUDE.md` and the user's memory automatically.
> It does **not** see earlier conversations. This file is the bridge.

---

## The two websites, which are easy to confuse

| | What it is | Lead capture |
|---|---|---|
| **usscproducts.com** | The real public site. WordPress, Porto theme, WooCommerce, Contact Form 7. | **Connected** to USSCOS since 13 Nov 2026 |
| **usscos.com** | Not a live site — three bare PHP forms at `/var/www/usscos.com` on the same droplet as USSCOS. Nothing links to it. | Connected, and was the source of ~117 spam leads |
| **os.usscos.com** | USSCOS itself, the internal application. | n/a |

**Chip's rule, 13 Nov 2026:** a lead whose source is `web` came through usscos.com, and
since nothing links to that site, it is spam. 117 were deleted on that basis.
`database/remove_spam_leads.php --web` applies the rule, dry run by default, and refuses
to touch any lead that has been worked.

## How website leads reach USSCOS

```
form submitted
      ↓
Contact Form 7 sends its email        ← unchanged, still the system of record
      ↓
wpcf7_mail_sent fires (only after the mail has gone)
      ↓
POST https://os.usscos.com/webhook/lead   with X-USSCOS-Secret
      ↓
lead created, routed by Admin > Lead Routing, rep notified
```

**The email is deliberately first and independent.** If USSCOS is down or the secret is
wrong, the enquiry is still emailed. Nothing about this integration can cost an enquiry —
which is why it was safe to put on a live site mid-testing.

The WordPress side is a Code Snippets snippet, id 9, "USSCOS lead forwarding". The copy
of record is [wordpress/ussc-lead-webhook.php](wordpress/ussc-lead-webhook.php); see
[wordpress/README.md](wordpress/README.md) before touching it — it documents two traps
that each cost an attempt.

### Proven, as at 19 Nov 2026

| Form | CF7 id | Sent as | State |
|---|---|---|---|
| Contact Form - Basic | 74 | `contact` | **Proven**, including the Phone field added 13 Nov |
| Request a Quote | 14593 | `paint_quote` | **Proven by a real enquiry** — lead 134, Gabriel Barron at CUHSD asking about the RM-21 robot |
| Request a Stencil Quote | 18220 | `stencil_quote` | Data proven (lead 133). **Artwork upload deployed but never tested** |

The newsletter, Georgia Proud Partner and Become a Dealer forms are deliberately *not*
forwarded. A newsletter signup is not a lead, and making it one is how a lead list fills
with people nobody intends to ring.

### The one thing left unfinished

**Stencil artwork.** `quote-file` on form 18220 is the customer's artwork. The snippet now
copies it into the WordPress media library and sends the URL as `attachment`, which
USSCOS appends to the lead notes. **This was deployed and never tested** — the test
submission failed validation (State and Paint are required) and the browser disconnected
before a retry. Worth proving before anyone relies on it: a stencil quote without the
artwork is a phone call to ask for it again.

## Spam

- **usscos.com** had no protection at all until 13 Nov. It now has a honeypot, a minimum
  completion time and a per-address rate limit — `/var/www/usscos.com/spam.php`, copy of
  record at [site/spam.php](site/spam.php). Every check fails open: the failure mode is
  "some spam gets in", never "a customer cannot reach you".
- **usscproducts.com** already had **Akismet and Turnstile** active on its forms. WP
  Armour was added on top, which was belt and braces rather than a fix — the spam was
  never coming from WordPress.

## The webhook secret — rotate it

`LEAD_WEBHOOK_SECRET` in `/var/www/usscos/.env` **was committed to this public repository**
in commit `41ead2f` and must be rotated. It authorises creating a lead and nothing else —
no customer data, no money — so this is "today", not "drop everything".

Rotating needs both sides changed together, in this order, or WordPress leads 401 in
between:

1. New value into `/var/www/usscos/.env`
2. Same value into Code Snippets snippet 9 on usscproducts.com
3. usscos.com needs nothing — its `config.php` reads the `.env` rather than holding a copy

The endpoint currently **accepts** a request with no secret and logs it, and rejects a
wrong one. When the logs stop showing absences, requiring it is one line in
`WebhookController::lead()`.

## Stencils — what exists already

Facts, not plans:

- **1,224 stencil products**, all active, all flagged `publish_to_website`.
- Import and categorisation scripts exist: `database/import_stencils.php`,
  `import_stencil_calculator.php`, `categorise_stencils.php`,
  `categorise_stencils_from_calculator.php`, `group_stencil_variants.php`,
  `retire_stencil_thickness.php`.
- USSCOS has a **storefront skeleton of its own** — `SiteController`,
  `StorefrontRepository`, views under `app/Views/site/`, routes mounted at `/shop`
  because `/` is the staff dashboard. The routing comment says these become root paths
  when the site moves to its own vhost.
- **Product content is the blocker, not code.** Of 1,931 products published to the
  website, 1,901 have no `website_name` and 1,927 have no image row — four products in
  the entire system have an image. See **Product content** in
  [TESTING_LOG.md](TESTING_LOG.md). Anything built to show stencils will show text until
  somebody writes and photographs them.
- **Categories come from the live site's menu**, not from spreadsheets — scrape
  usscproducts.com. Spreadsheet trees have been wrong before.

## What the new session should ask first

The goal was given as "put the stencil portion on the website", which could mean at least
three different jobs:

1. **Stencils for sale on usscproducts.com** — WooCommerce products on the real site.
2. **The USSCOS storefront** — building out `/shop` so USSCOS serves its own site.
3. **A stencil configurator or calculator** — `import_stencil_calculator.php` suggests
   sizing logic already exists somewhere worth finding before rebuilding it.

**Ask which.** They share almost no work, and the product-content gap above affects all
three equally.
