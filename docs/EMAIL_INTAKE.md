# Email into the CRM

Mail BCC'd to a shared mailbox arrives on the right customer or lead timeline.

## The shape of it

```
rep BCCs crm@usscproducts.com
        ↓
shared mailbox (Microsoft 365)
        ↓
a watcher POSTs each message as JSON
        ↓
POST /api/email/inbound        ← X-API-Key, same key as the QuickBooks bridge
        ↓
EmailMatcher → customer or lead
        ↓
matched   → note.email in activity_log, on that record's timeline
unmatched → queued at /admin/email-intake for somebody to place
```

## Why BCC and not "read the mailboxes"

HubSpot and Salesforce both offer two modes: a BCC dropbox, and a connected inbox that
reads everything via OAuth. The second is the one that feels magic — nobody has to remember
anything — and it is also the one that reads the rep's personal mail, because it has the
whole mailbox.

BCC is opt-in per message. It captures less, and what it captures is unambiguous, and
nobody has to be told their inbox is being read. Start here; the matching and storage code
is the same either way, so moving to Graph later is a change of transport, not a rewrite.

## Setting up the watcher

**IMAP with a password will not work.** Microsoft disabled basic authentication for IMAP,
so a polling script would need OAuth2 (XOAUTH2), which needs an Azure app registration
anyway. Three routes, cheapest first:

1. **Power Automate flow** — "When a new email arrives (V3)" on the shared mailbox → HTTP
   action POSTing to `https://os.usscos.com/api/email/inbound` with `X-API-Key`. Built in
   the Microsoft 365 UI, no Azure registration, no admin consent, no secrets in our code
   beyond the key. **This is the one to use.**
2. **Microsoft Graph** — Azure app registration, application permission `Mail.Read`, scoped
   by an Application Access Policy to just that mailbox, delta queries for incremental
   sync. More control, more setup. The step to take if the flow proves unreliable or the
   volume grows.
3. **Inbound parse at a provider** — an MX record on a subdomain pointing at a service that
   POSTs. Avoids Microsoft entirely but adds a third party and a DNS change.

### The JSON

```json
{
  "message_id": "<CAE1234@mail.gmail.com>",
  "from_email": "denise@cherokeecountyparks.org",
  "from_name":  "Denise Whitaker",
  "to":         ["chip@usscproducts.com", "crm@usscproducts.com"],
  "cc":         [],
  "subject":    "Re: spring season paint",
  "body":       "Plain text or HTML; HTML is reduced before storing",
  "sent_at":    "2026-11-24T14:02:11Z"
}
```

Only `from_email` is required. `to` and `cc` accept either a list or a comma-separated
string, because no two senders agree. `body` falls back to `html`, `sent_at` to `date`.

## How matching works

Two passes, in `App\Services\Email\EmailMatcher`:

1. **Exact address** on a customer or lead record. Certain.
2. **Domain**, where the domain belongs to a company rather than a mail provider. You
   emailed `bob@school.edu` and the record has `jane@school.edu`. This is where most of the
   value is, because the person who enquires and the person who buys are often not the same
   person.

A domain match is a weaker claim and says so — on the timeline entry and in the intake
screen. **Free-mail domains are excluded from pass 2**, and that exclusion is the whole
safety of it: without it a single `gmail.com` address would match every private customer in
the table and attach a stranger's email to them.

Our own domains are never matched on. Every BCC'd message has a USSC address on it — that
is what makes it ours.

## Things that were deliberate

- **`message_id` is UNIQUE.** A repeat delivery is rejected by the database, not by
  application logic somebody could bypass. A message with no Message-ID gets a hash of its
  identifying parts, so a genuine repeat still collides.
- **An unmatched email is stored and returns 200.** A non-2xx sends most senders into
  retry, and retrying will not make an address appear on a customer record. It goes in the
  queue instead — an email nobody places is an email the CRM has quietly lost, and that is
  the same failure shape as the website artwork that was dropped silently for two months.
- **The full body lives in `email_intake`, not in the timeline.** A timeline entry is a
  sentence; the original has to survive so an unmatched one can be read and placed later.
- **Attaching by hand takes an id, not a name search.** What gets attached writes to a
  timeline, and a near-match on a company name would put somebody's email on the wrong
  account with nothing saying so.

## Not done

- **Attachments** are not stored. The body is.
- **Threading.** Each message is its own entry; `References` is not followed, so a long
  thread appears as separate entries rather than one conversation.
- **Open tracking.** Needs a pixel and sending from the CRM, which is a different feature.
