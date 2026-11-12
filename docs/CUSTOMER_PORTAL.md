# Customer portal

**Status: decided in principle, not designed, not built. Written 12 November 2026.**

Chip's intent, in his words: *"allow customers to have a login so they can view their past
orders, have the ability to pay online and also order again from a past order."*

## Two audiences, and only one of them is new

These get confused because both end in "they log in and see less than we do". They are
different people with different relationships to the money.

| | Who | Relationship | State |
|---|---|---|---|
| **Sales reps** | Chris Danis, Jack Curlings, William Leavell | **We bill their customers directly; the rep earns commission.** They are employees with an internal login | **Built.** `users.role = 'rep'`, scoped by `AccessScope` through `sales_reps.user_id` to `customers.sales_rep_id`. They see their own customers and are kept out of shipping, the pull list and receiving |
| **Distributors and stocking distributors** | Customers who buy to resell | **They pay us.** They see their own invoices and settle them | **Not built.** This is the portal |

A rep sees *a set of customers*. A distributor sees *their own account*. Nothing about
the rep scope carries over except the shape of the idea — scope in the route and the
repository, fail closed.

---

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

**Most of that is already modelled, and I was wrong to call it purely a design question.**
Chip explained the structure on 12 Nov 2026: **Sports Inc is a buying group.** Its members
— Johnson-Lambe, Academy Sports and the rest — are their own companies, and Sports Inc
pays their bills. One AP inbox, many real customers.

QuickBooks models it with job-style names, and USSCOS already carries it as `parent_id`:

```
SPORTS INC                 id 28622   parent NULL
SPORTS INC:JOHNSON-LAMBE   id 28656   parent 28622
SPORTS INC:ACADEMY SPORTS  id 28623   parent 28622
```

So *who may this login see?* has an answer in the data: **a login on the parent sees its
children.** What remains is narrower:

- **The hierarchy is half built.** 138 customers are named `PARENT:CHILD` and only 69 have
  a `parent_id` — the rest are orphaned duplicates from a second import pass, 62 of them
  in Sports Inc alone. A portal on today's data would show Sports Inc about half its
  members and miss the rest silently. Fixing this is importer work, logged in
  [TESTING_LOG.md](TESTING_LOG.md).
- **Who pays is settled.** Chip verified in QuickBooks on 12 Nov 2026 that **Sports Inc
  pays every member's invoices**; members never settle their own. The paying login belongs
  on the parent. The independent `JOHNSON - LAMBE` record (id 16730) turned out to hold a
  single test invoice from this testing, not a second trading relationship.
- **Does a child get its own login?** A member seeing its own orders without seeing the
  group's, while the parent pays, is plausible and unconfirmed. It is a view question
  only — payment stays with the parent.

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

## Who can do what — decided 12 Nov 2026

| | Sees | Pays | Orders |
|---|---|---|---|
| **Parent of a group** (Sports Inc) | Its own orders and **every sub-company's** | **All of them**, one payment applied across invoices | Yes |
| **Sub-company** (Johnson-Lambe) | **Only its own** orders | No — the parent settles | Yes |
| **Distributor / stocking distributor** | Own account | Yes | Yes |
| **Retail** | Own account | Yes, unless opted out | Yes |

**Every customer can place a new order, not only repeat one.** Chip's decision. I had
argued for sending retail to the website and keeping portal ordering for distributors,
on the grounds that the website is already the storefront and the portal would duplicate
it; he chose one route for everyone instead, which also removes the oddity of a customer
who can repeat an order but not place one.

Scope assumed unless corrected: **search over product name and SKU**, showing the website
name, image and that customer's price — not category browsing, related products or
landing pages. Enough for somebody who knows roughly what they want, without rebuilding
the storefront inside the portal.

**Everything lands in the same sales order queue and goes through both gates.** A portal
order is a sales order like any other: it waits for approval before the warehouse sees it.
`sales_orders.channel` already distinguishes where an order came from (`direct`, `amazon`,
`website`, `pickup`, `edi`, `other`), which is what will later answer what the portal is
actually selling.

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

**One payment against many invoices is mostly already built**, internally. Receive
Payment takes an amount, offers **Apply Oldest First**, and lets a person set *Amount to
Apply* per invoice; the allocation is stored as `payment_applications` rows, which is
exactly the model a distributor paying a batch needs. Chip's description of what the
portal should do — *"enter an amount and click a button as to how much to apply to each
invoice just like we can do already"* — is that screen, facing outwards.

So the remaining work is not the allocation arithmetic. It is the customer-facing wrapper,
Square itself, and making a payment that arrives by webhook land on those same
`payment_applications` rows exactly once when the event is delivered twice or late.

**Do customers see stock on hand?** I would show nothing until there has been a real
count. A quantity shown from a number nobody trusts is worse than no quantity, and it is
the sort of thing that gets believed by a customer long after we have stopped believing
it. See the stock findings in [TESTING_LOG.md](TESTING_LOG.md).

**A portal order needs a price the customer has already seen.** Ordering through the
portal means the price is quoted at the moment they add the line, from `CustomerPricing`.
If anything re-prices between adding and submitting — a tier change, a price list update —
the customer has agreed to one number and the order carries another. Decide whether the
quoted price is held on the order or recalculated at approval.

## Not started

Nothing in this document is built. It records a decision in principle and the questions
that have to be answered before the first line is written.
