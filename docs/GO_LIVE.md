# What USSCOS needs before you can actually use it

Written 2026-09-23, from the operational problems as described: inventory that nobody
trusts, goods not recorded in or out, wrong orders shipped, returns that never reach the
system, and a second warehouse about to open.

The short answer to the biggest question: **accounting can come later. Bills can stay in
QuickBooks.** None of it blocks running the warehouse. What cannot come later is anything
that makes stock wrong on day one — because an inventory system people stop trusting is
worse than no inventory system, and you already know what that feels like.

---

## The principle to hold on to

**Every event that changes stock must be recorded, or the count drifts and trust is gone
within weeks.** That is the whole reason the current numbers are wrong: paint comes in and
goes out without being entered, and returns never land anywhere.

So the go-live test is not "does the system have inventory". It is:

> **Is there a screen for every way stock moves, and is it faster than not using it?**

Every gap in that list becomes a place where someone shrugs and the count rots.

---

## Every way stock moves at USSC

| Movement | Status |
|---|---|
| Receipt from TCC (bulk) | **Not built** |
| Receipt from the canning company (aerosol) | **Not built** |
| Transfer between warehouses | **Not built** — new, and needed for the second building |
| Shipment to customer | Picking built; **stock deduction not built** |
| **Customer return** | **Not built at all** — and named as a main cause of drift |
| Damage, spillage, write-off | **Not built** |
| Physical count / adjustment | **Not built** |

Seven ways stock moves. **One and a half are built.** That is the real gap between now and
usable.

---

## 1. Inventory — the core problem

### Multi-location is a schema change, not a setting

Today `products.qty_on_hand` is a **single number**. There are no location, warehouse or
bin tables at all. With a second warehouse you need:

- **Locations** — main warehouse, new warehouse, and later bays or racks within them
- **Stock per product per location**, with the total being the sum rather than the source
- **Transfers** — scanned out of one building, scanned in at the other

### The bit people get wrong: in transit

When a pallet leaves the main warehouse and has not yet arrived at the new one, it is in
neither. If a transfer is a single instant event, then anything lost, delayed or
miscounted between buildings silently vanishes and nobody can say where.

So a transfer is **two events, not one**:

```
scan out of Warehouse A   →   IN TRANSIT   →   scan in at Warehouse B
```

Anything sitting in transit for longer than a day is a question worth asking, and that
becomes a report. This is exactly the kind of gap that produces "we don't understand how
it can be so off".

### Returns are not optional

Named as a main cause of drift, and **nothing exists**. A return needs:

- A record of what came back, from which customer, against which invoice
- A decision per item: **back into sellable stock, or scrapped** — these are different
  movements and conflating them will reintroduce the same drift
- The stock movement that follows from that decision
- A credit memo, or a note that one is owed

The schema already anticipates this — `invoices.invoice_type` includes `credit_memo`, and
`inventory_transactions.transaction_type` includes `return_in` and `return_out`. The
enums are there; the feature is not.

### Opening the count

One full physical count, entered as the starting position, with USSCOS maintaining from
there. Greg, a couple of days, per the bookkeeper conversation. Do it **after** the
movement screens exist, not before — otherwise the count is stale before anyone uses it.

---

## 2b. Answers from shipping, 2026-09-23

| Question | Answer | What follows |
|---|---|---|
| Website channel? | **Yes** — products pushed to a website, orders come into USSCOS | Build the channel model for four routes now, not three |
| Amazon automation today? | **None. Entirely by hand**, including shipment confirmation | A missed confirmation is invisible until account health drops |
| Pick list per person or shared? | **Undecided** — several shipping people | Recommend one shared queue where picking an order claims it |
| Label printers? | **Location to be decided** | Decides whether a label prints where the work is, or someone walks |
| Wrong shipments? | **Several times a week** | ~150/year — see below |
| Customer pickup? | Packing list to a file holder, clerk pulls it to the **CPU area** | No record of who collected or when |
| Freight PRO number? | **From the carrier**, once they enter the shipment to raise the BOL | Arrives *after* shipping, not with the label — the customer email may go before it exists |

### The wrong shipments are the strongest argument for scanning

At three a week, roughly **150 a year**. Each costs freight out, freight back, freight out
again, picking and packing twice, the admin of the credit and re-order — and **the stock
count goes wrong twice**, because the wrong item left and the right one did not, and the
return may never be recorded.

Even at a conservative $75 of hard cost each, that is **over $11,000 a year** before
counting the inventory drift or the customer relationships. A scan at the packing bench
refuses the wrong item before the carton closes, which is the only point where stopping it
is cheap.

It also explains part of the inventory problem directly: ~150 unplanned returns a year
arriving with no process to record them is exactly the kind of leak that produces a count
nobody can explain.

### Customer pickup needs a release record

Today there is no record of **who collected an order or when**. That is awkward when a
customer says it never arrived, and it is a stock movement like any other — the goods left
the building. Worth a signature or at least a name and timestamp at the CPU counter.

---

## 2a. Order channels, priority, and orders that cannot ship

Recorded 2026-09-23.

### Amazon orders come straight in, and jump the queue

Today Amazon orders are printed and **typed into QuickBooks only** — they never reach
USSCOS. When the integration is built they should arrive here **directly as sales orders,
labelled Amazon**.

**They carry a same-day ship obligation**, so they outrank everything else in the queue.
That is a harder deadline than a phone order and it cannot depend on someone remembering
which printout came from Amazon.

Practically this means:

- A **channel** on every order — direct, Amazon, website, pickup, EDI
- A **priority**, defaulting to urgent for Amazon
- A **ship-by time, not just a date** — "today" stops being useful at about 3pm, and the
  queue needs to show what is at risk while there is still time to act
- Shipment confirmed back to Amazon with tracking inside their window, since late
  confirmation affects account standing

### Orders that cannot be fulfilled need to be a list

Wanted: a way to see, from stock, **which sales orders cannot be filled** — rather than
finding out one at a time at the shelf, which is what happens now.

What that needs:

- Availability per line: stock on hand, less what is already committed to other orders
- A verdict per order: fully fillable, partly, or not at all
- **Which product is blocking it** — the question actually asked is "what are we waiting
  on", not "is this order blocked"
- Re-checked when stock arrives, so a receipt **releases** the orders it unblocks instead
  of Hannah checking the pile

This is the same list production needs in order to know what to make, viewed from the
other end. One calculation, two audiences.

### A correction worth recording

An earlier note inferred from "1 open sales order" that backorders must be tracked outside
USSCOS. That was wrong. **USSCOS is still in testing** — there are no real orders in it
yet, so counts from it describe the test data and nothing else. Worth remembering before
drawing conclusions from any live figure in this system.

---

## 2. Sales orders and the shipping queue

### Does a new order reach shipping automatically? No.

A new sales order is created as **`draft`**. The shipping queue only shows `confirmed`,
`processing`, `partially_shipped` and `paid`. So an order sits invisible to shipping until
someone confirms it.

**Worth checking whether that confirm step is actually happening.** If sales people expect
orders to flow straight through, some are sitting in draft that nobody is working.

### Verifying the ship-to address against the customer's PO

The current process: print the PO, hand it to the clerk, she compares the packing slip
address against the paperwork by eye.

**Attach the customer's PO to the sales order.** The Job Binder already does exactly this
for artwork — file upload, stored against the order, visible on the page. The same pattern
puts the PO on screen beside the address, so no paper is needed and nothing is compared
from memory.

Then two additions worth having:

- **An explicit "address verified" tick**, recording who checked it and when. That turns an
  informal habit into something you can see was done — and see when it was skipped.
- **Prefer saved customer addresses.** If a ship-to is typed freehand rather than chosen
  from the customer's known addresses, flag it for a second look. Most wrong addresses are
  typed, not chosen.

Full address validation against USPS or FedEx is possible later, but note what it does and
doesn't do: it confirms an address is *deliverable*, not that it matches the customer's PO.
It would not have caught the errors she is checking for.

---

## 3. Connecting to QuickBooks

### You almost certainly do not need to open a port

Worth correcting, because "get the port open" suggests exposing QuickBooks to the internet.
The design does the opposite: a small bridge program runs on the Windows machine beside
QuickBooks and **calls out** to `os.usscos.com` over HTTPS. Outbound only. Nothing inbound,
no port forwarding, no QuickBooks exposed to the world.

That matters — an exposed accounting system is precisely the thing worth not doing.

### What is actually needed

1. **A Windows machine that stays on**, with QuickBooks Desktop installed and the company
   file reachable
2. **QODBC** installed and licensed on that machine (it is paid software)
3. **A DSN per company file** — one for USSC, one for TCC, since you are keeping two
4. **Outbound HTTPS** from that machine to `os.usscos.com` (almost certainly already
   allowed)
5. **The bridge program**, which does not exist yet — it is the piece that reads QuickBooks
   and talks to the API already built here
6. **An API key per entity**, so a USSC record can never land in TCC's file

### The ListID point, since you asked

Every QuickBooks item has a **`ListID`** that never changes, even when you rename the item.
USSCOS currently stores only the item **name** — all 891 products, zero ListIDs.

**You cannot detect a rename when the only key you hold is the thing that changed.** Rename
a SKU in QuickBooks today and USSCOS sees an item it has never heard of.

Fixing it is cheap **at the reimport and expensive afterwards**, because immediately after
the rename every name still matches, which is the easiest moment this project will ever
have to establish the ID mapping.

So:

- Ask for **ListID as a column** in the export, alongside the item name
- Store it in `products.quickbooks_id` (the column exists, empty)
- Add **items and customers to what the bridge pulls** — the plan currently pulls only POs
  and invoices, so nothing would ever look at the item list

Do that and renames stop being an event. Skip it and you will be back here.

---

## 4. Freight and carriers

### Today

Tracking numbers and ship-via are typed in by hand at Ship & Invoice. That works. It is not
blocking.

### FedEx

A real integration gets you: rates at order time, labels printed from USSCOS, tracking
numbers captured automatically rather than typed, and delivery status back on the order.

Needs a FedEx developer account, API credentials, and the account number. The work is
moderate and self-contained. **It is an efficiency gain, not a blocker** — manual entry
works on day one.

### Kuebix — it does have an API

Confirmed 2026-09-23. Two endpoints matter:

| Purpose | Endpoint |
|---|---|
| Retrieve quotes already created for a shipment | `GET /shipments/{shipmentId}/rates/view` |
| Request a fresh quote | `POST /action/quickRate` |

Quotes return carrier name, total price, fuel surcharge, additional charges, transit days
and a quote reference. Authentication is an **API username, API key and client ID**.

**Before building anything, ask Kuebix support two questions:** is API access enabled on
our subscription, and does it cost extra? Neither is answerable from the documentation and
both decide whether this is worth starting.

Assuming it is enabled, the useful shape is: request or retrieve quotes from the sales
order, show the options with carrier, price and transit days, let the clerk pick one, and
store the chosen quote against the order — carrier, cost, reference. That removes the
retyping and, more usefully, keeps the freight cost attached to the order instead of
living on paper.

A freight section is worth building **either way** — quote reference, carrier, cost, pallet
count and BOL. The API makes it quicker to fill in; it is not what makes it valuable.

---

## 5. What can wait

| | Why it can wait |
|---|---|
| **Accounting / general ledger** | QuickBooks keeps the books. Nothing operational depends on it. |
| **Bills and AP** | Keep entering bills in QuickBooks. Pull the history later if ever needed. |
| **FedEx API** | Manual tracking entry works today. |
| **Kuebix automation** | Manual entry works today. |
| **Job Binder** (production, QA, photos) | Artwork is live; the rest is additive. |
| **Dashboard** | Cosmetic. Currently shows zeros, which is worse than nothing, but breaks nothing. |
| **Portals, tiered pricing** | Neither is needed to run the warehouse. |
| **Entity scoping (TCC/USSC)** | Only if TCC people use USSCOS on day one. If go-live is USSC-only, this can follow — **but it is far cheaper during the reimport**, so decide deliberately rather than by default. |

**Yes — enter bills in QuickBooks and get that information later.** That is exactly what
the operating-layer position means, and it takes the single largest piece of work off the
critical path.

---

## The go-live list, in order

### Must exist before anyone relies on it

1. **Locations and per-location stock** — schema change; everything else builds on it
2. **Receiving** — scan or key what arrives, from TCC and from the canner
3. **Stock deduction on shipment** — quantity only; cost stays null until valuation is
   settled
4. **Transfers between warehouses** — scan out, in transit, scan in
5. **Returns** — what came back, restock or scrap, stock movement, credit memo
6. **Adjustments and physical count** — the honest correction path, plus a count screen
7. **Barcodes and `units_per_case` on products** — from the final spreadsheet; without them
   scanning cannot convert a case to units
8. **The opening physical count**, entered once the above exists

### Needed at the same time, smaller

9. **Customer PO attached to the sales order**, with an address-verified tick
10. **Confirm the draft → confirmed step** is understood, so orders actually reach shipping
11. **A freight section** — carrier, cost, pallet count, BOL — even if entered by hand
12. **Roles for warehouse staff**, so the shipping station shows only what it needs

### Straight after go-live

13. QuickBooks bridge, with ListIDs and item sync
14. FedEx API
15. Kuebix, if it has an API
16. Entity scoping, if TCC comes on

---

## What I need from you to start

- **The product spreadsheet with `units_per_case`, `pallet_qty` and barcodes** — this is
  the one thing that blocks scanning entirely, and nothing else substitutes for it
- ~~A name for the new warehouse~~ — **"730"** (the address), and **yes to bays/racks**,
  since that is how stock is physically stored
- ~~Does Kuebix have an API?~~ — **yes**. Still needed: confirmation from Kuebix support
  that it is enabled on our subscription, and whether it costs extra
- ~~Will TCC people use USSCOS at go-live?~~ — **yes, partially.** Some users see parts of
  both companies; upper management sees everything in both. That makes access a
  **(entity x area) matrix**, not a single entity flag — see
  [INTERCOMPANY.md](INTERCOMPANY.md). Worth designing as a matrix from the start.

---

## Related

- [OPERATIONS_DISCOVERY.md](OPERATIONS_DISCOVERY.md) / [OPERATIONS_INTERVIEW.md](OPERATIONS_INTERVIEW.md) — the process walkthrough
- [INTERCOMPANY.md](INTERCOMPANY.md) — TCC/USSC structure
- [QUICKBOOKS_SYNC.md](QUICKBOOKS_SYNC.md) — the bridge design
- [ACCOUNTANT_QUESTIONS.md](ACCOUNTANT_QUESTIONS.md) — inventory valuation, still open and
  still only blocking the *costing* of stock, not the counting of it
