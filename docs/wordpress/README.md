# Website lead forwarding

Getting Contact Form 7 submissions on **usscproducts.com** into USSCOS as leads,
**without stopping the emails** that currently get them to the right person.

## How it fits together

```
visitor submits a form
        │
        ▼
Contact Form 7 sends its email   ← unchanged, still the system of record
        │
        ▼
wpcf7_mail_sent fires            ← only after the email has gone
        │
        ▼
POST https://os.usscos.com/webhook/lead   with X-USSCOS-Secret
        │
        ▼
lead created, routed, rep notified
```

**The email is deliberately first and deliberately independent.** If USSCOS is down
or the secret is wrong, the email has already been delivered and whoever normally
receives it still does. Nothing about this change can cost you an enquiry — which is
what makes it safe to put on a live site before you have finished testing the other end.

## Setting it up

1. **Get the secret**

   ```bash
   ssh root@159.223.177.58 'grep LEAD_WEBHOOK_SECRET /var/www/usscos/.env'
   ```

2. **Install a PHP snippets plugin** — WPCode or Code Snippets.

   **Not HFCM.** That plugin injects HTML and JavaScript into pages; it cannot run
   server-side code, which is why the forms are not already connected.

3. **Paste [`ussc-lead-webhook.php`](ussc-lead-webhook.php)** as a PHP snippet, set to
   run everywhere. Put the secret in `USSCOS_LEAD_SECRET`.

4. **Check the form ids.** Contact Form 7 shows the id in its shortcode —
   `[contact-form-7 id="74"]`. Only `74` (Contact Form - Basic) is filled in; the two
   quote forms need their real ids before they will forward.

5. **Test with the live form**, then look in USSCOS under Leads. If nothing appears, the
   PHP error log on the WordPress host says why — every failure is logged there.

## Which forms, and which not

| Form | Forwarded as |
|---|---|
| Contact Form - Basic | `contact` |
| Request a Quote | `paint_quote` |
| Request a Stencil Quote | `stencil_quote` |
| Footer Newsletter Form | **no** |
| Newsletter Popup Form | **no** |
| Become a Georgia Proud Partner | **no** |
| Info on Become a Dealer | **no** |

A newsletter signup is not a lead. Forwarding it fills the lead list with people nobody
intends to ring, and the list stops being read. The partner and dealer forms have no
handler in USSCOS; they could have one, but that is a decision about what happens to
them, not a wiring job.

## The secret, and why

`/webhook/lead` sits outside the login system by necessity — WordPress cannot log in. It
used to accept anything posted to it, and bots found it: an SEO pitch, a subscription bot
and a vague "more information" all became leads and all emailed a rep.

It now requires `X-USSCOS-Secret`, compared with `hash_equals`. **An unset secret refuses
everything** rather than accepting anything, because a webhook that fails open is worse
than one that is switched off — nobody notices.

To rotate it: change `LEAD_WEBHOOK_SECRET` in `/var/www/usscos/.env`, then the snippet.
No restart needed. Leads submitted in between are lost to USSCOS but **still emailed**.

## Still open

- **120 web leads already exist**, growing month on month, and only 6 match the shapes
  this webhook produces. Something else has been feeding them — a connector, a plugin or
  an import. Worth establishing what, so it is not left running alongside this and
  creating every lead twice.
- **Contact Form 7 has no spam protection** on these forms. The secret stops bots posting
  to USSCOS directly; it does nothing about bots filling in the form itself. A honeypot
  or Turnstile on the WordPress side is the fix, and it keeps the junk out of the email
  too.
