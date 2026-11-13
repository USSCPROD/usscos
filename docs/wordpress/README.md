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
   run everywhere. Put the secret in `USSCOS_LEAD_SECRET`, keeping the quotes.

   **Start the paste below the `<?php` line.** Code Snippets supplies the opening tag
   itself — it is greyed out above line 1 of its editor. Paste `<?php` as well and PHP
   receives two opening tags, which is a parse error: the snippet refuses to save and
   never appears in the list, with nothing obvious to say why. This cost an attempt on
   13 Nov 2026.

   **Give it a title**, or saving pops a *Snippet incomplete* dialog and stops.

4. **The form ids and field names are already filled in**, read off the live forms on
   13 Nov 2026:

   | Form | id | Sent as | Its fields |
   |---|---|---|---|
   | Contact Form - Basic | 74 | `contact` | `your-name`, `your-email`, `your-phone`, `your-subject`, `your-message` |
   | Request a Quote | 14593 | `paint_quote` | `quote-name`, `quote-email`, `quote-phone`, `quote-company`, `quote-address/city/state/zip`, `quote-paintuser`, `quote-paintbrand`, `quote-paintcolor`, `quote-products` |
   | Request a Stencil Quote | 18220 | `stencil_quote` | as above plus `quote-paint`, `quote-details`, `quote-file` |

   If a form is edited and a field renamed, the mapping in the snippet has to follow —
   otherwise the lead arrives with a name and an empty body, which is worse than nothing
   because somebody then rings to ask what the customer already typed.

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

## Do not put the secret in this file and commit it

It happened on 13 November 2026, into a **public** repository, and the exposed value had
to be rotated. The file is edited in place to add the secret before pasting, and a
`git add -A` swept the edited copy into a commit.

Work on a copy named `*.local.php` — `.gitignore` covers that pattern — or paste the
secret straight into the Code Snippets editor and leave this file alone.

The blast radius of this particular secret is narrow: it authorises creating a lead and
nothing else. There is no customer data behind it and no money. It still gets rotated,
because a secret that is public is not a secret.

## The secret, and why

`/webhook/lead` sits outside the login system by necessity — WordPress cannot log in. It
used to accept anything posted to it, and bots found it: an SEO pitch, a subscription bot
and a vague "more information" all became leads and all emailed a rep.

It now requires `X-USSCOS-Secret`, compared with `hash_equals`. **An unset secret refuses
everything** rather than accepting anything, because a webhook that fails open is worse
than one that is switched off — nobody notices.

To rotate it: change `LEAD_WEBHOOK_SECRET` in `/var/www/usscos/.env`, then the snippet.
No restart needed. Leads submitted in between are lost to USSCOS but **still emailed**.

## Where the existing leads come from

**Answered 13 Nov 2026: usscos.com's own forms**, a separate site on the same droplet —
see [../site/README.md](../site/README.md). Those 120 leads are not a mystery feeder, and
there is nothing to switch off: usscproducts.com is a **second** source being added
alongside, not a replacement.

## Phone on the contact form

Added 13 Nov 2026, optional, paired with Subject in a two-column row. Also added to the
form's Mail tab, so the email staff receive carries it too — the email is still the path
people read, and a lead with a number is one somebody can ring.

Optional on purpose: a required phone number loses the enquiries from people who only
want an email back, and a lead you can email is worth more than no lead.

**Adding the field was not enough on its own.** The snippet decides what arrives, and it
only looked for `quote-phone`. The first test submitted a number and the lead came
through with the phone blank. Both names are in the mapping now. A field nothing reads is
a field that does not exist.

## Still open

- **Contact Form 7 has no spam protection** on these forms. usscos.com's forms now have a
  honeypot, a minimum completion time and a rate limit; these do not. The CF7 Honeypot
  plugin does the same job here without code, and it keeps the junk out of the **email**
  as well as out of USSCOS — which matters more, because the email is the path people
  actually read today.
- **`quote-file`** on the stencil form is an upload. The USSCOS handler takes an
  `attachment`, and this snippet does not send one — artwork submitted through
  usscproducts.com will not travel with the lead. Worth fixing before that form is
  anybody's main route.
