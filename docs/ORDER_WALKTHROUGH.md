# From a lead to shipped and paid

Written 2026-11-10 and extended 2026-11-20, by clicking through every screen on the
live site rather than reading the code. Every label and button name below is what is
actually on screen.

Use this as a demo script. It is in two halves, and they are genuinely different
businesses:

- **Part One — winning the work.** A lead, an opportunity, tasks, a quote. Sales only.
  Nothing here moves any goods and nothing here is visible to the warehouse.
- **Part Two — delivering it.** The sales order, two approval gates, the pull, the
  bench, the invoice and the label. Three different people touch it.

Most of the confusion in a demo comes from not knowing which half, and which person,
you are standing in at any moment.

---

# Part One — Winning the work

## The four things, and why there are four

They look like duplicates until you ask what each one answers.

| | Answers | Lives on |
|---|---|---|
| **Lead** | "Who is this, and are they worth our time?" | Someone who is not a customer yet |
| **Opportunity** | "How much, and how likely?" | A deal, inside the pipeline total |
| **Task** | "Who does what, by when?" | A person's to-do list |
| **Quote** | "Here is the price, in writing" | A document the customer sees |

A lead can carry several opportunities — a parks department buying stencils this spring
and traffic paint next autumn is **two** deals, with two values and two close dates, not
one lead with a muddled total. That is the whole reason opportunities exist separately.

> **The pipeline is the forecast.** Only opportunities have an expected value and a
> probability, so only opportunities can be added up into "what might we close this
> quarter". A lead with no opportunity on it contributes **nothing** to that number,
> however promising it is.

---

## Where the opportunities went

First question everybody asks, so get it in early: **the sidebar calls it Pipeline, not
Opportunities.** Same thing. `/opportunities` and `/pipeline` both land there and both
light the same sidebar entry.

---

## Step 1 — The lead

**Who:** sales. **Screen:** Leads.

A lead is a company that is not a customer yet. They arrive two ways:

- **By hand** — Leads → **+ New Lead**.
- **By themselves** — the forms on usscproducts.com post straight in. A contact form, a
  paint quote request or a stencil quote request becomes a lead within seconds, with
  the form it came from recorded so you can tell them apart.

The lead moves through **New → Contacted → Qualified**, and ends at **Converted** or
**Dead**. The status is just a dropdown on Edit; nothing is enforced.

The lead page is the hub for everything else in Part One — Opportunities, Quotes and
Tasks each have their own panel on it, with an **+ Add** button. Work from here rather
than from the sidebar and nothing ends up orphaned.

---

## Step 2 — The opportunity

**Who:** sales. **Screen:** lead page → Opportunities → **+ Add**, or **+ Opportunity**
in the page header.

Give it an expected value, a close date, and a stage. The stages are
**Prospecting → Proposal → Negotiation → Closed Won / Closed Lost**, and each carries a
default probability — 20%, 50%, 75%, 100%, 0% — which is what weights the forecast.

The opportunity page shows the stages as a progress bar, so where a deal stands is
readable across the room. That makes it the right screen to leave on display.

---

## Step 3 — Tasks

**Who:** sales. **Screen:** lead page → Tasks → **+ Add Task**, or Tasks in the sidebar.

A task is the follow-up: call them Tuesday, send the sample, chase the PO. It has an
owner, a due date and a priority, and it shows up on that person's dashboard.

Created from the lead, it stays attached to the lead — so the history of who chased
what sits with the company rather than evaporating out of somebody's inbox.

> **Tasks do not drive anything.** Nothing in the system waits on a task and no stage
> moves when one is ticked off. They are a to-do list attached to the right record,
> which is all they are meant to be. Say that plainly in a demo before someone assumes
> there is a workflow engine underneath.

---

## Step 4 — The quote

**Who:** sales. **Screen:** lead page → Quotes → **+ New Quote**.

Start it from the opportunity's **+ Quote** button rather than the generic one — that
links the two, and the link is what makes the next part work.

Add lines, set an expiry date. **Shipping is a line you type**: get a freight price the
way you get one today, and key it in. The quote total includes it, so the customer sees
one number rather than "plus freight".

A quote runs **Draft → Sent → Accepted**, or **Declined / Expired**.

### The bit that surprises people

**Creating a quote does not move the pipeline. Sending it does.**

The opportunity's stage follows the quote's *status*, not its existence:

| Quote status | Opportunity moves to |
|---|---|
| **Sent** | Proposal |
| **Accepted** | Closed Won |
| **Declined** | Closed Lost |

It only ever moves **forward** — marking a quote Sent will not drag a deal back out of
Negotiation. So if you build a quote in a demo and the pipeline does not budge, nothing
is broken: press **Mark as Sent**, and watch it step across.

The quote total also keeps the opportunity's value in sync as you edit lines, so the
forecast tracks the real number rather than the first guess.

---

## Step 5 — Convert

Two conversions, in this order, and the order is not optional.

**First, the lead becomes a customer.** On the lead page, **Convert to Customer**. This
is the moment the company stops being a prospect and gets a customer record, which is
what carries pricing, terms and an address.

**Then the quote becomes a sales order.** On the quote, **Convert to SO** — available
once the quote is **Sent** or **Accepted** and not already converted.

> **Doing it the other way round stops you**, deliberately. A quote attached to a lead
> has no customer to hang a sales order on, so pressing Convert to SO first refuses,
> and says to convert the lead first. The quote stays attached throughout and converts
> straight afterwards — nothing is retyped.

Everything on the quote carries across: quantities, prices, taxability and the shipping
line. The sales order lands as a **Draft**, which is where Part Two picks up.

---

# Part Two — Delivering it


## Two different "approvals", at opposite ends of the job

Worth settling before anything else, because the words look alike and the screens are
nowhere near each other.

| | **Shipping › Waiting for your OK** | **Invoices › Awaiting Review** |
|---|---|---|
| When | Before any work starts | After the goods have gone |
| About | A sales order | A finished invoice |
| Who | Shipping clerk or admin | Bookkeeper |
| Doing it | Puts the order in front of the warehouse | Signs the invoice off — and is what would send the customer their tracking |

Nothing in the first one emails anybody.

---

## The one thing that derails a demo

**A new order is saved as a Draft, and a draft is invisible to shipping.**

The shipping queue only shows orders that are *Confirmed*, *Processing*,
*Partially Shipped* or *Paid*. So you enter an order, switch to the shipping screen to
show what happens next, and it is not there. Nothing is broken — the order is still a
draft.

> **Press "Submit for shipping" on the order.** It is the green button in the header of
> the sales order. That is the handoff, and it is a single press — there is no status
> dropdown to hunt for.

The system now says this out loud rather than leaving you to discover it. Saving a draft
flashes a message naming the SO number and telling you the warehouse cannot see it yet,
and the draft order itself carries a panel saying the same thing with the button right
beside it. **Save & New** — the one that used to drop orders silently into the void —
flashes it too.

Second gotcha, worth knowing before someone spots it: **the number in the address bar
is not the SO number.** `/sales-orders/24` opens SO #11. Every link in the application
passes the right one, so this only bites if you type or read out a URL. Click through;
don't type.

---

## Phase 1 — The salesperson enters the order

**Who:** sales. **Screen:** Sales Orders.

1. **Sales Orders** in the sidebar → **+ New Order**.
2. Pick the customer, add lines, set a **Ship By** date.
3. Save. The order is now a **Draft** — and the page says so, with the reason.
4. **Submit for shipping.** This is the handoff — it is what puts the order in front of
   the shipping clerk.

If the order came from a quote (Part One), steps 1 and 2 already happened: it arrives
as a draft with every line on it, and you start at step 4.

**What the salesperson sees afterwards:** the order page, with Print / PDF, Packing
Slip, Email, and — while the order is Confirmed, Processing or Partially Shipped — a
green **Collect Payment** button for payment taken up front.

**What just changed for everyone else:** the order appears on the **Shipping Queue**
and on the **Pull list**.

> **Also on the order page:** the job binder — Documents, Notes, Quality, Artwork. The
> customer's PO belongs under Documents. It stays with the job permanently and is one
> click from the invoice years later.

---

## Phase 2 — The clerk says it may be worked ("OK to ship")

**Who:** shipping clerk or admin. **Screen:** Shipping → **Waiting for your OK**.

A confirmed order is not yet work. It waits here until somebody says it may be worked,
and **the warehouse cannot see it at all until then** — not on the pull list, not in the
queue, not on a pick screen.

The screen is split by bench — **Parcel**, **Freight**, **Our own truck**, **Customer
collects** — because parcel and freight are worked by different people and neither wants
the other's orders in their list. Orders with no carrier yet are listed first on their
own, with a dropdown: **set how it ships, then approve**, or the warehouse cannot tell
whose work it is.

Each row shows lines, quantity, ship-by and value. *"May be tight"* appears when the
stock figures suggest it will not fill — **advisory only**. The quantity decision happens
at Phase 5, after somebody has walked the floor, because that is where it is based on
what is physically in hand rather than on counts nobody trusts yet.

**Print packing slips for ticked orders** prints the batch as one job, one slip per page.
That replaces printing labels and stapling them to slips: the slips still go to the
bench, the labels do not, because a label needs a packed box before it means anything.

---

## Phase 3 — The warehouse pulls the goods

**Who:** warehouse staff (DaQuan, Chris, Greg, Josh). **Screen:** Shipping → Pull list.

A warehouse login sees **only** Shipping and Receiving. They land on the Shipping Queue
when they sign in, and any other address bounces them back with "That section isn't
available for your account." No customers, no pricing, no costs.

The pull list is split by the same benches, so the parcel pullers and the freight
pullers each get their own walk.

**The Pull list is not per-order.** It totals everything owed across every open order,
by product — *"9 of ASTE, 5 of SOTDG"* — so the building is walked once instead of once
per order. Items that cannot be filled are pushed to the top with a count of how short
they are.

Each line has a **Picked** button to record what actually came off the shelf.

> **The warehouse does not decide who waits.** When stock is short the list says so and
> lists the orders wanting it, but the choice between a full order and a partial belongs
> to the shipping clerk in Phase 4.

---

## Phase 4 — Pick, then verify the box

**Who:** warehouse, at the bench. **Screen:** Shipping Queue → **Pick**, then
**Pack**.

### Pick

Scan each item. The screen counts up — *"26 of 26 picked, 100%"* — and only shows what
is still to pick, so the list shrinks as the work gets done. A case barcode counts the
whole case.

If it cannot be finished: **Report Short Stock**, with a note. That flags the order
short and the reason travels with it.

> Picking does **not** mark anything shipped. Nothing has left the building yet.

### Pack & Verify

A second scan, as each item goes into the box. This is the error-catcher: **anything
not on the order is refused.** The screen says *"The box matches the pick. Ready to seal
and ship."* when it agrees.

There is a **Confirm without scanning** fallback for when a label will not read. It
records the order as *confirmed by a person* rather than *scan-verified* — so afterwards
you can still tell a box a human checked from a box a scanner read.

**On the queue, the chips tell you where each order stands.** The first is the pick, the
second is the pack. Hover any of them for the sentence that explains it.

| Badge | Means |
|---|---|
| Picking / Picked / Short | how far the pulling got |
| Packing | some of the box scanned in, not all |
| **Confirmed** | a person checked the box by eye — pressed *Confirm without scanning* |
| **Verified** | every item was scanned and matched the pick |
| Mismatch | the box did not match, and somebody wrote down why |

Confirmed and Verified both mean packing is finished. They are kept apart because the
difference is the evidence, and that is the whole reason the bench exists.

---

## Phase 5 — The clerk releases it, and the invoice is created

**An order cannot be released until it has been packed and verified.** *Ready to ship*
shows **Pack & verify first** instead of a Ship button until the box has been checked.
The same rule guards the Ship & Invoice button on the sales order, so there is no way
round it.


**Who:** shipping clerk / manager (Hannah). **Screen:** Shipping → **Ready to ship**.

This is the decision screen, and the most important one to show your boss. Every open
order, side by side:

| Column | What it tells you |
|---|---|
| Ordered / Picked | 27 ordered, 22 picked |
| State | Complete · Part-picked · Short (*"1 line cannot be filled"*) |
| Decision | **Ship all** · **Ship 22** · **Hold** |

**Hold requires a reason.** An order on hold with no reason is indistinguishable from
one nobody got to.

Pressing **Ship all** or **Ship 22**:

- creates the **invoice** — for what was *picked*, never for what was ordered
- leaves anything still owed on the order, which stays open as **Partially Shipped**
- moves stock
- takes you straight to the label screen

> **This is the single most valuable thing in the flow.** A picker who finds 4 of 10
> cases cannot produce an invoice for 10. The old way round — invoice the order, then
> discover the shortfall — is how a customer gets billed for paint that never shipped.

---

## Phase 6 — The label

**Who:** shipping clerk **or** the warehouse — decided 2026-11-10, either can do it.
**Screen:** Label.

A warehouse login already reaches this screen: the **Label** button is on every row of
the shipping queue, and reprints live at the bottom of the label screen under *Labels on
this shipment*. Nothing had to be opened up for them.

The system proposes the packing — *"12 boxes, 438 lb"* — from the pack sizes, and will
tell you when the answer is wrong: *"This looks like freight, not parcel."*

**Type each box's weight.** Dimensions are optional but worth adding: FedEx re-rates
anything light and bulky on the bill if you leave them off. Then choose the service and
**Create FedEx label**.

Limited-quantity aerosol is ground only — it cannot go by air, and the screen says so.

> The order leaves the shipping queue when a **tracking number exists**, not when it is
> invoiced. The parcel having a label on it is the real finish line.

> **Currently in sandbox.** Labels are not valid for shipping and the tracking numbers
> are not real. Fine for proving the flow; say so during a demo before someone asks.

---

## CPU — when the customer collects

Same flow, two steps shorter. Approve it at gate one with **Customer Pickup** as the
carrier, pick it, pack and verify it, then **Invoice**.

There is no label to make and no tracking number to wait for, so **the invoice is the
finish line** — the order leaves the shipping queue as soon as it is invoiced. There is
no separate "mark complete" to remember.

The money is the only thing left, and it works the same as any other order:

- **Paying at the counter** — take it on the sales order with **Collect Payment** before
  invoicing, and the invoice is created already paid.
- **On terms** — they take the goods, the invoice stands open, and it is settled later
  from the customer's page with **Receive Payment**. Nothing else is needed; the order is
  finished the moment it is invoiced.

---

## Payment

Two doors, depending on when the money arrives.

- **Before shipping** — **Collect Payment** on the sales order. The invoice then shows
  as paid when it is created.
- **After shipping** — the customer page → **Receive Payment**, which lists the open
  invoices and applies the money across them.

An invoice can only take a payment once it has been issued. If Receive Payment says
there is nothing to apply it to, it now names the draft invoices that are blocking.

---

## The short version, for the whiteboard

```
SALES              Lead ─► Opportunity ─► Task(s)
                     │          │
                     │          └─► Quote ─► MARK AS SENT ─► pipeline moves
                     │                          │
                     └─► Convert to Customer ───┤   (lead first, always)
                                                │
                                         Convert to SO
                                                │
                                      ══════════▼══════════
SALESPERSON        Enter order ─► SUBMIT FOR SHIPPING
                                      │
                              ╔═══════▼═══════╗
SHIPPING CLERK    GATE 1      ║  OK to ship   ║  ─► batch packing slips
                              ╚═══════╤═══════╝
                                      │   (until now the warehouse cannot see it)
WAREHOUSE          Pull list ─► Pick ─► Pack & Verify
                                      │
                              ╔═══════▼═══════╗
SHIPPING CLERK    GATE 2      ║ Ship all / N  ║
                              ╚═══════╤═══════╝
                                   INVOICE
                                      │
CLERK or WAREHOUSE Label ─► tracking number ─► off the queue
                                      │
ACCOUNTS           Receive Payment
```

Gate 1 says **"work this order."** Gate 2 says **"ship this much."** Keeping them apart
is the point: the first is a scheduling decision, the second is a decision about goods
somebody has in their hands.

Everything above the double line is a forecast. Everything below it is goods. The
conversion is the only door between them, and it is one-way.

---

## Who sees what

| Role | Lands on | Sees |
|---|---|---|
| **Warehouse** (DaQuan, Chris, Greg, Josh) | Shipping Queue | Shipping and Receiving only — including picking, packing and **printing labels**. No customers, pricing, costs or accounting. A typed URL is refused, not just hidden |
| **Shipping manager** (Hannah) | Dashboard — shipping work, tasks, activity | The full application. She enters orders too, so she is deliberately *not* on a warehouse login |
| **Sales** | Dashboard — their own numbers | Their pipeline, leads, quotes, overdue accounts and quiet accounts. **No company revenue, A/R or stock value** |
| **Bookkeeper** | Dashboard — receivables | Finance, without the sales targets |
| **Owner / admin / manager** | Dashboard — company KPIs | Everything |

The dashboard is an allow-list per role: a panel added later appears for nobody until
someone names the audiences it belongs to. The failure mode is a missing panel somebody
asks about, rather than company financials quietly turning up on a rep's screen.

---

## A ten-minute demo — Part Two only

Orders #1–30 are seeded test data, marked *"SEED — test order, safe to delete"*.

1. **Sales Orders → + New Order.** Enter one for a real customer. Save, press
   **Submit for shipping**, and say out loud that this is the handoff to the clerk.
2. **Shipping → Waiting for your OK.** Your order is here, and *only* here — the
   warehouse cannot see it yet. Set the carrier, tick it, **print the packing slip**,
   then **OK to ship**. Point out the parcel and freight sections.
3. **Shipping → Pull list.** The order has appeared. Point out it is totalled by
   product, not by order, that short items sort to the top, and that parcel and freight
   have their own tabs.
4. **Shipping Queue → Pick** on your new order. Scan or use **Set**. Watch the counter.
5. **Pack & Verify.** Scan something that is *not* on the order and let it be refused —
   this is the moment that lands.
6. **Ready to ship.** Show a Complete order next to a Short one, and the Ship all /
   Ship N / Hold choice. Release yours.
7. **The invoice appears.** Note it bills what was picked.
8. **Label.** Type a weight, create the label. Mention the sandbox. Either the clerk
   or the warehouse does this step.
9. **Customer → Receive Payment.** Apply the payment, and the loop is closed.

If an order is missing from the shipping screens, check two things in this order: is it
still a **Draft**, and has it been **approved** at gate one? It is almost always one of
those two.

---

## The twenty-minute demo — the whole thing

Run Part One first and let it flow into the script above. This is the version to show
your boss, because it is the only one that shows the forecast and the goods as one
business.

1. **Leads.** Open a lead that came in from the website, and point out the form it came
   from. The point to land: *nobody typed this in.*
2. **+ Opportunity.** Give it a value and a close date. Note the probability, and that
   this — not the lead — is what the pipeline adds up.
3. **+ Add Task.** "Call Tuesday." Show it land on the dashboard. Say that it drives
   nothing; it is a to-do in the right place.
4. **+ Quote** from the opportunity. Add lines, type a freight price, save.
5. **Pipeline.** The deal has **not** moved. Let that sit for a second — then go back,
   **Mark as Sent**, and show it step to Proposal. This is the moment that explains how
   the forecast stays honest: it follows what was actually sent, not what was drafted.
6. **Convert to Customer** on the lead. Then **Convert to SO** on the quote. Show the
   lines, prices and freight arriving untouched.
7. **Submit for shipping** — and you are at step 2 of the ten-minute demo above.

> **If you only have five minutes**, run steps 5 and 6, then jump to *Pack & Verify* and
> *Ready to ship* in Part Two. Those three screens are the ones that are hard to argue
> with: the forecast moves on evidence, the box is checked against the order, and the
> invoice bills what was picked.
