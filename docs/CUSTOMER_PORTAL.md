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

**Search, not browsing.** Confirmed by Chip 12 Nov 2026: *"from portal, no they just
search for the product."* A search box over product name and SKU, showing the website
name, image and that customer's own price. **No category trees, no related products, no
landing pages** — that is what the website is for, and duplicating it here would be the
expensive half of a storefront built twice.

Only products with `publish_to_website` set should be findable, so the portal and the
site offer the same range rather than exposing items nobody meant to sell online. That is
**1,931 of 2,095** products today.

**The website fields those results would show are almost entirely empty**, checked
12 Nov 2026:

| | Published products lacking it |
|---|---|
| `website_name` | 1,901 of 1,931 |
| `website_image_url` | 1,909 of 1,931 |
| any row in `product_images` | 1,927 of 1,931 |

So a search built on the website fields would return a page of blanks. It has to fall
back to `products.name` — and there is effectively **no product imagery at all**: four
products have an image. Results will be text for the foreseeable future, which is
workable for a distributor typing a SKU and poor for a retail customer who came to look
at paint. Worth knowing before anyone judges the search by how it feels.

**Everything lands in the same sales order queue and goes through both gates.** A portal
order is a sales order like any other: it waits for approval before the warehouse sees it.
`sales_orders.channel` already distinguishes where an order came from (`direct`, `amazon`,
`website`, `pickup`, `edi`, `other`), which is what will later answer what the portal is
actually selling.

## The third audience: people who buy on the website and never log in

Plenty of customers will order from usscproducts.com and never open a portal account.
They still have to become something in USSCOS.

**They become real customers, one record each. Decided by Chip 12 Nov 2026** — *"we don't
want website to be the customer. We want the actual customer's info for future things
like marketing."*

That overturns how it works today. Website orders currently land on a **single catch-all
customer** — `WEBSITE`, id 32561, carrying an internal address — the way QuickBooks
handles anonymous sales. A buyer lumped in there has no history, nothing to reorder, and
no account anyone could later give them.

### Which makes duplicate prevention the central problem

Every guest checkout is a chance to create a second record for somebody who already
exists. Unlike the 9,665 duplicate pairs already in the data, **these would keep arriving
after cutover** — a defect that generates itself.

`CustomerService::possibleDuplicates()` already does this matching for reps adding
customers, and should be reused. But a checkout is not a rep, and the difference matters:

- **A rep can be shown the matches and asked.** A customer cannot. Showing a guest
  "did you mean one of these three companies?" discloses other customers' names to a
  stranger. The method already redacts per match for restricted users; at checkout the
  right answer is to **show nothing at all**.
- So the matching has to be **silent**: nothing about other customers is ever shown at
  checkout. Guessing wrong in public is worse than a duplicate somebody resolves
  internally.
- Merging two customers is not yet a thing USSCOS can do. If near-matches are to be
  queued, something has to be able to act on the queue.

### The matching order — Chip, 12 Nov 2026

**Email first, then company name**, *"as a school or company may have more than 1 person
ordering."*

That is the right order for this trade: the customer is the institution, not the
individual. Two coaches buying for the same school are one customer, not two.

1. **Exact email match** → attach to that customer.
2. **No email match, company name matches** → attach to that company.
3. **Neither** → create a new customer.

Two things make step 2 harder than it reads:

- **Company names are not unique.** 9,665 company names are currently held by more than
  one record. That is the duplicate problem again, so a clean import fixes most of it —
  but the rule still needs an answer for *matched more than one*, and the answer cannot
  be to ask the customer. Attach to none, create the record, and queue it.
- **Fuzzy matching cuts both ways.** "Thacher School" against "The Thacher School" should
  match; "Carolina Sports Inc." against "Carolina Sports" probably should; two different
  Midfield High Schools in different states should not. Exact-match-after-normalising is
  the safe starting point, with anything looser queued rather than acted on.

### The gap this exposes: a customer holds only one person

**There is no contacts table.** `customers` carries one set of person fields —
`first_name`, `last_name`, `email`, `cc_email` and phones. So the rule above correctly
attaches Coach Jones's order to Thacher School, and then **has nowhere to put Coach
Jones.** He either overwrites Coach Smith or is lost.

For the stated reason behind all of this — *"we want the actual customer's info for future
things like marketing"* — that is the wrong way round: it keeps the institution and
discards the people, and the people are who marketing is addressed to.

A `customer_contacts` table (customer_id, name, email, phone, role, marketing consent,
last ordered) would close it, and would also serve:

- **Who to email** a shipment notification or invoice, when the person who ordered is not
  the address on the account.
- **Portal logins**, which are per-person, not per-company — a school with three coaches
  needs three logins against one customer. That question is already open above under
  identity; this is the same answer approached from a different direction.
- **Sports Inc**, where the AP inbox is a contact of the parent while each member has its
  own buyer.

Not needed to run the warehouse. Needed before the portal or any marketing use of this
data.

### Other consequences of a guest order

- **It arrives already paid.** Square takes the money at checkout, so the order lands with
  payment attached rather than waiting for it. Both gates still apply — paid is not
  picked.
- **It is retail priced by definition.** Nobody gets a tier without being identified,
  which is its own argument for a distributor logging in rather than checking out as a
  guest.
- **Marketing consent belongs at checkout.** If the reason for keeping the record is
  future marketing, whether they agreed to be marketed to is part of the record, captured
  then — not assumed afterwards from the fact that they bought something.

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
