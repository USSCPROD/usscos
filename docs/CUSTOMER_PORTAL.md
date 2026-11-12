# Customer portal

**Status: decided in principle, not designed, not built. Written 12 November 2026.**

Chip's intent, in his words: *"allow customers to have a login so they can view their past
orders, have the ability to pay online and also order again from a past order."*

Three things behind one login:

| | What it does | Why it exists |
|---|---|---|
| **See past orders and invoices** | What was bought, what shipped, what is paid and what is not | People lose emails and then ring up asking how to pay |
| **Pay online** | Square. For a distributor, several invoices in one payment | The only online payment route — USSC's customers are not QuickBooks users, so QuickBooks Payments cannot serve them. See [FULFILLMENT_AND_PAYMENT_DECISIONS.md](FULFILLMENT_AND_PAYMENT_DECISIONS.md) |
| **Order again from a past order** | Repeat a previous order without re-keying it | Most of this trade is the same colours in the same pack sizes, over and over |

---

## The thing to settle before anything else

**An email address does not identify a customer here.**

Of 41,233 customers, 28,646 have an email address — and **3,737 addresses are shared by
more than one customer record**. The worst case is not marginal:

| Address | Customers sharing it |
|---|---|
| `importinvoice@hq.sportsinc.com` | **95** |
| `jpweigel1@gmail.com` | 15 |
| `apinvoicenet@fastenal.com` | 12 |

Those are buying groups and chains whose branches each exist as their own customer, all
billing to one AP inbox. So "log in with your email and see your orders" has no answer to
*which* of 95 customers you are.

That is a design question, not a data cleanup — the records are legitimately separate.
Some possible shapes, none chosen:

- A login is its own record, linked to **one or many** customers, with a chooser when it
  has several. Fits the buying-group case honestly.
- A login belongs to one customer, and shared addresses simply cannot self-register —
  those accounts are created by hand.
- Logins are invited per customer rather than claimed by email, so the link is always
  deliberate.

Whichever it is, it has to be decided before the sign-in screen is written, because it
determines what a session even means.

## What already exists

- **`users.role`** already has `distributor` as a value, and `AccessScope` treats it as
  restricted — the machinery for "this login sees only its own records" is built and in
  use for reps. It scopes by `customers.sales_rep_id`; a customer login would scope by
  customer, which is the same shape of change.
- **`customer_type`** exists, so distributor-or-not is answerable in principle. It is
  blank on 41,229 of 41,233 rows today, which is an import problem rather than a schema
  one — logged with the importer rework in [TESTING_LOG.md](TESTING_LOG.md).
- **Nothing else.** No portal tables, no customer authentication, no public session
  handling.

## Traps worth naming now

**Reorder must re-price, not repeat the price.** A past order carries what was charged
then. Copying those figures into a new order quietly sells at last year's price —
and for a distributor whose tier has changed since, at the wrong tier. A reorder should
copy the *items and quantities* and price them fresh through `CustomerPricing`, then show
what changed.

**Reorder must check the product is still sold.** Discontinued SKUs, retired pack sizes
and products no longer published to the website are all live in old orders.

**This is the first publicly reachable authenticated surface.** Everything today is
employees behind one login screen on an internal tool. A customer portal is open to the
internet, which changes what a weak password, an enumerable id or a missing scope check
costs. `AccessScope` fails closed on purpose; a customer scope must do the same, in the
route and the repository, not in the view.

**One payment against many invoices** is genuinely different machinery from a
single-invoice pay link: applying an amount across invoices, part-paying some, staying
consistent when a webhook arrives twice or late. Expect it to be the larger half.

**Sub-customers exist** — 69 of them. If a parent logs in, do they see their children's
orders? Probably yes, and it needs saying rather than assuming.

## Not started

Nothing in this document is built. It records a decision in principle and the questions
that have to be answered before the first line is written.
