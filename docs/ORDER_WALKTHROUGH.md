# From sales order to shipped and paid

Written 2026-11-10, by clicking through every screen on the live site rather than
reading the code. Every label and button name below is what is actually on screen.

Use this as a demo script. The flow is six phases, and **three different people touch it** — the salesperson, the warehouse, and the shipping clerk. Most of the confusion
comes from not knowing which of the three you are standing in at any moment.

---

## The one thing that derails a demo

**A new order is saved as a Draft, and a draft is invisible to shipping.**

The shipping queue only shows orders that are *Confirmed*, *Processing*,
*Partially Shipped* or *Paid*. So you enter an order, switch to the shipping screen to
show what happens next, and it is not there. Nothing is broken — the order is still a
draft.

> **Set the status to Confirmed before you leave the order.** It is the Status dropdown
> in the top-left box of the sales order, next to the S.O. number.

Second gotcha, worth knowing before someone spots it: **the number in the address bar
is not the SO number.** `/sales-orders/24` opens SO #11. Every link in the application
passes the right one, so this only bites if you type or read out a URL. Click through;
don't type.

---

## Phase 1 — The salesperson enters the order

**Who:** sales. **Screen:** Sales Orders.

1. **Sales Orders** in the sidebar → **+ New Order**.
2. Pick the customer, add lines, set a **Ship By** date.
3. Save. The order is now a **Draft**.
4. **Change Status to Confirmed.** This is the handoff — it is what puts the order in
   front of the warehouse.

**What the salesperson sees afterwards:** the order page, with Print / PDF, Packing
Slip, Email, and — while the order is Confirmed, Processing or Partially Shipped — a
green **Collect Payment** button for payment taken up front.

**What just changed for everyone else:** the order appears on the **Shipping Queue**
and on the **Pull list**.

> **Also on the order page:** the job binder — Documents, Notes, Quality, Artwork. The
> customer's PO belongs under Documents. It stays with the job permanently and is one
> click from the invoice years later.

---

## Phase 2 — The clerk approves it ("OK to ship")

**Who:** shipping clerk or admin. **Screen:** Shipping → **Orders to approve**.

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

**On the queue, the chips tell you where each order stands:** Picking → Picked →
Verified (scanned) or Confirmed (by hand).

---

## Phase 5 — The clerk releases it, and the invoice is created

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
SALESPERSON        Enter order ─► set to CONFIRMED
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

## A ten-minute demo

Orders #1–30 are seeded test data, marked *"SEED — test order, safe to delete"*.

1. **Sales Orders → + New Order.** Enter one for a real customer. Save, **set
   Confirmed**, and say out loud that this is the handoff to the clerk.
2. **Shipping → Orders to approve.** Your order is here, and *only* here — the
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
