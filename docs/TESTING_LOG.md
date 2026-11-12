# Testing log

Started 2026-10-29, during test runs against the partial QuickBooks extract.

This is the running list of what testing has turned up: what is still open, what is
waiting on a decision, and what has been fixed. Add to it as things are found — a
finding that lives only in a chat message is a finding that gets rediscovered.

**Two kinds of entry, and the difference matters.**

- **Code** — a fault in the application. It survives the re-import. Fix it now.
- **Data** — an artifact of the partial extract loaded for testing. Recheck it after
  the real import rather than chasing it. See the note in [GO_LIVE.md](GO_LIVE.md).

Counts quoted below come from the test database and are illustrative, not authoritative.

The end-to-end flow — what each person does and sees, in order — is written up in
[ORDER_WALKTHROUGH.md](ORDER_WALKTHROUGH.md).

---

## Open — waiting on Chip

### Duplicate customers — 9,665 pairs

**Data, not a code fault.** The partial extract appears to have been imported twice:
TRUMARK exists as id 31000 (267 invoices, created 1 July) and id 125701 (nothing at
all, created 7 July), with the **same `qb_balance` on both** — so any total that sums
balances double-counts them.

Across the base: **9,665 duplicate name groups**, each with exactly one extra row, and
18,656 rows inside those groups have no invoices and no orders.

**Not worth bulk-fixing in the test data**, which is deleted and re-imported at cutover
anyway. Two things that *are* worth doing:

- **The real import needs a dedupe step**, or this arrives again at full scale.
- **The duplicate check Chip asked for** — when a rep adds a customer, check whether it
  already exists and already has a rep — is the thing that stops this recurring by hand.

The empty TRUMARK (125701) was deactivated so testing is not confused by it.

### Reps: two rep columns, and only one login linked

Found 10 November 2026 while wiring distributor pricing.

- **`customers.sales_rep_id` → `sales_reps` is the live link.** QuickBooks populates it,
  314 customers carry it, and `AccessScope` uses it to decide which customers a rep
  sees. It fails closed: a rep login with no rep record sees nothing.
- **`customers.rep_id` → `users` is dead.** It came from a migration collision years
  ago and `AccessScope` explicitly ignores it — but **the dropdown on the customer edit
  page writes to it**, so assigning a rep there changes nothing. One customer in the
  database has it set. That picker should point at `sales_reps`, and `rep_id` should be
  retired.
- **Only 1 of 33 reps is linked to a login**, so rep scoping cannot be tested yet.
  Chris Danis (38 customers) and Jack Curlings (34) are in `sales_reps` and need logins.
  **William Lavelle is not in the list at all** and needs adding.

Chip's requirements, not yet built: a rep sees **only their own customers**; a rep may
**add a customer**, but the system must first check whether that customer already
exists and already has a rep assigned.

Still to settle: whether being a rep should be a **flag** on a user rather than a role,
so somebody can see sales screens without being assignable for credit — which is the
distinction Larry Fitzpatrick showed was needed.


### Emailed payment links — Square

Chip described this on 10 November 2026: customers pay over the phone, **or are sent a
link to pay online**, and only then does the order ship. **Square** is the processor —
decided, so that part needs no revisiting.

Paying over the phone works today (*Collect Payment* on the order, before invoicing).
The link does not exist: USSCOS has no online payment capability at all.

Agreed shape, in Chip's words: a **button on the sales order that emails a payment
link**, and pressing it **saves the order first**. Card data must never touch the
USSCOS server — a Square-hosted link keeps it that way, which is a compliance question
before it is a coding one.

**Deferred by Chip, not forgotten** — "we can build the email link later, just remember
that it is going to happen."


Nothing moves on these until a decision or a file arrives.

| What | Why it is blocked |
|---|---|
| Prices for ~28 unpriced colors; SprayChalk entirely unpriced | Needs the final product spreadsheet |
| Rep list | Sales dashboard cannot resolve "my leads" without it. Larry Fitzpatrick is sales, **not** a rep. Five sales staff then flip to the `rep` role |
| 213 published products sharing identical names | Stencils are missing their size in the name. Renaming reaches QuickBooks, so it needs a nod first |
| Five categories still holding products with no live-tree home | Concrete Coatings and Repair (49), Grass Colorants (31), Asphalt Coatings and Repair (12 — ambiguous between two same-named live categories), BedWorks (5 — no live equivalent), Field Markers (5) |
| Inventory counting policy | The inventory module is built; what remains is people and decisions, not code |
| Which `shipping_boxes` row a rolled stencil goes into | Stencils roll into much smaller boxes than their flat size suggests; the cartonizer has no rule for them |

### Product content — names, descriptions and photographs

**Not code. This is somebody writing and somebody photographing, and it gates how useful
the customer portal is to a retail customer.** Measured 12 November 2026:

| | Published products lacking it |
|---|---|
| `website_name` | **1,901** of 1,931 |
| `website_short_description` / `website_description` | effectively all |
| `website_image_url` | **1,909** of 1,931 |
| any row in `product_images` | **1,927** of 1,931 |

**Four products in the whole system have an image.**

1,931 products are flagged `publish_to_website` out of 2,095, so the *range* is decided —
what is missing is what each one says and looks like.

Consequences, in the order they will be felt:

- **Portal search returns text.** It has to fall back to `products.name`, because
  `website_name` is empty for 98% of them. Workable for a distributor typing a SKU, poor
  for the retail customer who came to look at paint — and retail ordering through the
  portal is a decision already taken. See [CUSTOMER_PORTAL.md](CUSTOMER_PORTAL.md).
- **The website has the same gap**, since USSCOS is its product source. Whatever is
  written here serves both, which is the argument for doing it once and properly.
- **`products.name` is an internal name.** "T-Tip — White, Case (12 × 18 oz)" is right for
  a pull list and wrong for a customer deciding what to buy.

Worth sizing before committing to it: 1,931 products is a lot of writing, but the variant
grouping already built means colours of one product share copy — the real number of
*distinct things to describe* is far smaller than the row count, and the shared-description
work done on listings is the model. Photography is the longer pole: a case of aerosols
photographs once, but the colour variants each need their own, which is exactly the
listing-photo-versus-colour-photo distinction already handled on the product page.

No code is blocked by this. The portal can be built and will work; it will simply look
like a spreadsheet until this is done.

## Open — needs testing on real hardware

| What | Notes |
|---|---|
| Tablet and scanner at the shipping station | SO #10, #17, #22 and #25 are fully scannable and set up for this |

## Open — code

### The first shipment of a product destroys its stock count

**Found 10 November 2026, walking a test order end to end. This is the most serious
thing in this file.**

`products.qty_on_hand` is not stored — it is **recomputed** on every stock movement as
the sum of that product's rows in `product_stock`. A product that was imported with a
count but was never placed at a location has no rows at all, so the first time one
ships, the recompute replaces the real number with the sum of nothing.

Watched it happen: **DSY read 391 on hand, shipped 4, and came out at −4.** Not 387.
The 391 was gone, not reduced.

**284 products are in that state** — a count on the product, no row in `product_stock`
(29 location rows exist in the whole database). Every one of them loses its count the
first time it goes out of the door.

Two things follow, and the second matters more than the first:

- **The real import must land stock at a location**, not only on the product. If it
  writes `products.qty_on_hand` the way the current data was written, day one looks
  correct and then rots one product at a time as orders ship.
- **The code should refuse to silently destroy a count.** When a product has a non-zero
  count and no location rows, seeding the default location with that quantity before
  consuming would preserve it and make the first movement behave.

*The DSY count was restored to 387 by hand after the test.*

**Decided 2026-11-11 (Chip):** at cutover there will be **real counted inventory
numbers**, not the spreadsheet figures sitting in the test data. That settles it — there
is no need to preserve the current counts, and no need for the "assumed, not counted"
flag that was being considered. The current 284 products are disposable along with the
rest of the test data.

What remains is one hard requirement and one cheap safety net:

1. **The real import must write `product_stock` rows, not just `products.qty_on_hand`.**
   This is the whole fix. A count written only onto the product is not a count the system
   can keep — it is a number with nothing behind it, and the first movement replaces it
   with the sum of the locations, which is zero. Every imported product needs a row at a
   location, even if that location is just the default warehouse on day one.
2. **A guard in `StockRepository::consume()`**, so this can never happen silently again:
   if a product has a non-zero `qty_on_hand` and no `product_stock` rows, seed the
   default warehouse with that quantity before consuming. Cheap, and it means a future
   import that gets this wrong degrades into a wrong location rather than a destroyed
   count. Not a substitute for (1).

### Other

| What | Notes |
|---|---|
| Uploads post immediately and reload the page | Anything typed in the edit form and not yet saved is lost. Save first, then upload. Worth making the upload not discard the form |
| 186 non-stencil products have `gross_weight` but no `case_weight_gross` | The cartonizer reads `case_weight_gross`, so these ship without a weight |
| 44 traffic paints and 17 striping machines are misfiled under Specialty Coatings | Category assignment, not a code fault |
| `database/issue_draft_invoices.php` | Only exists for invoices stranded by the old draft behavior. Delete it once the data is re-imported |
| A new sales order is saved as **Draft**, and a draft is invisible to the shipping queue | Nothing on the order says so. Found on 2026-11-10 while walking the flow — it is the most likely thing to derail a demo. Worth either defaulting to Confirmed or saying plainly on the order that shipping cannot see it yet |
| Open Orders counts drafts | The list header reads 27 open / $31,724.46, and both include drafts. "Open" is defined as anything not invoiced or cancelled, so a draft falls in — yet the same screen now says a draft is not visible to shipping. An order nobody can act on is not open. Found 2026-11-11 |
| A draft still has a Status dropdown on the order | It is kept for the corrections it is genuinely for (cancelling, putting a shipped order back), but on a draft it sits beside **Submit for shipping** and does the same job badly — somebody can set Confirmed by hand and skip the submit path. Found 2026-11-11 |
| The id in the URL is not the SO number | `/sales-orders/24` is SO #11. Every link passes the right one, so it only bites when a URL is typed or read aloud |

## Open — before anyone quotes a customer

**Switch `FEDEX_ENVIRONMENT` to production and check one quote.** Sandbox returns list
price — a test account has no negotiated rates, which is why ACCOUNT and LIST come back
identical there. The quote screen says so on screen, but nobody should price a real
order off it until the live key is in and the figure has been compared against a known
FedEx quote once.

The quote picks the **ACCOUNT** rate explicitly. It used to take whichever rate FedEx
listed first, which was invisible in sandbox (both identical) and would have quoted
list price in production.

## Open — deferred to the import

Deferred by Chip on 2026-10-29: *"let's worry about the import when we get closer."*
Nothing here is urgent, and all of it is needed before the real cutover.

### The importer rework

**`database/import_quickbooks.php` is a prototype that got test data in. It is not what
you cut over with.** Logged in full 2026-11-12.

First, the thing it is easy to get wrong: *"connecting to QuickBooks"* means two different
jobs.

| | What it is | State |
|---|---|---|
| **Ongoing sync** | QODBC bridge on the Windows box pulls from `/api/qb/pending`, writes to QuickBooks, acks. Pushes **out**; brings nothing in. For daily operation after go-live. | Built — `routes/web.php:425-427`, documented in [QUICKBOOKS_SYNC.md](QUICKBOOKS_SYNC.md) |
| **Cutover import** | One-time load of customers, products, open invoices, balances and stock. Currently reads **Excel exports**, not the API or QODBC. | Prototype, not ready |

**The source has not been decided, and it should be decided deliberately rather than by
default.** Spreadsheet exports need no Windows setup but are a snapshot somebody has to
produce correctly. The QODBC bridge already exists and could pull master data directly —
more moving parts, no manual export, no stale snapshot. Chip has said *"IT will bring
everything if possible"*, which may settle it. **Confirm what IT can actually extract
before rebuilding against the wrong source.**

#### Faults to fix, whatever the source

1. **It does not place stock.** `importProducts()` writes `products.qty_on_hand` from the
   *Qty On Hand* column (line ~430) and never writes a `product_stock` row. Since
   `qty_on_hand` is recomputed as the sum of the location rows on every movement, an
   imported count with no row behind it is replaced by the sum of nothing on the first
   shipment — DSY read 391, shipped 4, came out at −4. **284 products are in that state.**
   This is the one fault that corrupts real inventory, and the importer is its cause.
   `StockRepository::consume()` now seeds the default warehouse as a safety net, but the
   import must do it properly: every imported count gets a location row.
2. **Stock can also diverge the other way.** 14 products have `product_stock` rows that
   disagree with `products.qty_on_hand` — SOTDG says 166 with 4 at locations;
   TTP115FI-WT-5G says 0 with 160. The product page shows a number the system overwrites
   the moment anything moves. Same root cause, opposite direction: there are **two** ways
   the import can produce a count that will not survive.
3. **It writes `status = 'sent'`**, which a later migration renamed to `pending`.
4. **It has no payments step at all** — nothing writes to the `payments` table.
5. **Part-paid invoices import as smaller invoices.** Its source is the open A/R aging
   report, so it sets `total_amount` and `balance_due` to the same outstanding figure.
6. **Tracking has two homes, and the import feeds the wrong one.** Found 2026-11-12.

   | | What it holds | Who writes it |
   |---|---|---|
   | `shipment_tracking` rows | The real record — carrier, tracking type, label path, and as many numbers as a shipment needs | The bench, the FedEx label run, the freight PRO form |
   | `invoices.tracking_number` | One string, nothing else | The Tracking # field on the invoice form, and the import |

   Invoice 292157 read **None yet** on Awaiting Review while its own page showed
   `794905225741`: the list judged emptiness from the row count and then printed the
   column. Both screens were internally consistent and disagreed with each other. Patched
   so a value in either counts, but **the duplication is the defect** and it will keep
   producing this shape of bug.

   A single column cannot hold a shipment that went out in five boxes, which is the
   normal case here, so `shipment_tracking` is the one to keep. Collapsing them means:
   the import writes rows rather than the column; the invoice form's Tracking # field
   goes, leaving the Shipment Tracking panel as the only way in; the shipment email
   (`InvoiceController.php:338`) reads rows; then the column is dropped by migration.
   **38 references across `app/` mention it**, with 5 writers, so this is a deliberate
   job rather than a tidy-up — and it is far cheaper during the reimport than after.

7. **Buying groups import half-parented.** Found 2026-11-12, looking at what a customer
   portal would stand on.

   A buying group is a real structure, not a data mess. **Sports Inc** is a group whose
   members are their own companies — Johnson-Lambe, Academy Sports, Albert Sporting Goods
   — and Sports Inc pays their bills, which is why one AP address
   (`importinvoice@hq.sportsinc.com`) sits on 117 customer records.

   QuickBooks models this with job-style names and USSCOS carries it as `parent_id`:

   ```
   SPORTS INC                 id 28622   parent NULL
   SPORTS INC:JOHNSON-LAMBE   id 28656   parent 28622   correct
   SPORTS INC:JOHNSON-LAMBE   id 123357  parent NULL    duplicate, orphaned
   ```

   **Exactly half the hierarchy is missing.** 138 customers are named like children
   (`PARENT:CHILD`) and only 69 have a `parent_id`; the other 69 are orphans from a
   second import pass, 62 of them in Sports Inc alone. So every group member exists twice
   — once correctly parented, once loose.

   This is the most concrete cost of the duplicate-customer problem. A customer portal
   answers *who may this login see?* with the parent/child link, so a Sports Inc login
   built on today's data would show about half its members and silently miss the rest.
   See [CUSTOMER_PORTAL.md](CUSTOMER_PORTAL.md).

   The import must therefore produce **one correctly parented record per member**:
   recognise `PARENT:CHILD` naming, create or find the parent, and set `parent_id` — on
   every pass, not just the first. Re-running it must not create a second orphaned copy,
   which is what idempotency was supposed to mean.

   **Settled 2026-11-12, verified by Chip in QuickBooks: Sports Inc pays every member's
   invoices.** Members never settle their own. So a login belongs on the parent, and the
   earlier worry about a member paying directly does not arise.

   The data agrees about which records are real:

   | Record | Invoices | Payments |
   |---|---|---|
   | `SPORTS INC:JOHNSON-LAMBE` id 28656, parented | **120, $133,417.77** | 0 |
   | `SPORTS INC:JOHNSON-LAMBE` id 123357, orphan | 0 | 0 |
   | `JOHNSON - LAMBE` id 16730, independent | 1, $1,227.85 | 1 |

   The orphan is **empty** — import noise, and merging it loses nothing. The independent
   record holds exactly one invoice, 292161, which is a test invoice created during this
   testing; it is not a second trading relationship.

   **The group has 397 invoices and zero payments recorded against any of them**, parent
   or child, while Chip has confirmed Sports Inc pays them all. Two explanations, and the
   evidence here does not separate them: the importer has no payments step at all (fault 4
   above), and **what is loaded is only a small slice of the real history** — payments may
   simply not have been in the extract, so an absence here is not evidence of absence in
   QuickBooks. Either way the conclusion for testing is the same: **A/R on this data means nothing**, and every one of
   those invoices reads unpaid because nothing has ever written a payment.

   It also means **there is no example in the data of how a buying group actually pays** —
   one payment across many invoices is exactly the distributor-portal case. When a real
   extract arrives, the payment records are what will show whether they settle per invoice,
   per statement or per month, and that shapes the portal screen. Worth asking for
   deliberately rather than hoping it turns up.

#### What it needs to become

The rules in [CLAUDE.md](../CLAUDE.md) for scripts that change data in bulk apply and
currently are not met: dry run by default, explicit `--commit`, one transaction, roll back
on any error. `database/import_products.php` is the reference implementation.

Add to that a **reconciliation report** at the end — counts in versus rows written, totals
in versus totals stored, and anything skipped with the reason. A silent import that
finishes is indistinguishable from one that dropped half the file, and this one runs once
against real data with the old system being deleted.

Fixing any of this needs **sample exports of the files that will actually be used**.

- **`ar_opening_date` must be set at cutover**, and there is no screen for it. It is the
  date USSCOS takes over its own receivables, and it does not exist yet. Until it is set,
  `ArPeriod` fences nothing off and imported history counts as USSCOS receivables.

---

## Fixed

### 2026-11-12 — the shipping queue on the bench tablet

The queue is how shipping picks the next order and it is worked on a Fire tablet. As a
table it needed **1521px**. Every action button — Pick, Pack, Label, Packing Slip — sat
off the right edge, reachable only by dragging the table sideways with nothing on screen
suggesting that was possible.

Below 1399px each row is now a card: headings travel with their values, empty fields drop
out, carrier/status/total share a line, and the buttons are 44px tall and sit in a
wrapping row. Done in CSS against the same markup, so the PHP deciding which button leads
stays the single source of truth.

**The breakpoint was wrong first time.** It was set at 1024px because the first test was
at 768. A Fire HD reports **1280 CSS pixels** in landscape, so the bench still got the
table. *A tablet is not 768 wide — match what the device reports.* At 1400–1600 the
buttons wrap instead, because the table overflowed a 1440 laptop too.

Also fixed on that screen: the pick/pack tags carried their meaning in `title=`
attributes, and there is no hover on a tablet, so on the one screen those sentences were
written for they could not be read. Card mode prints them under the badge.

### 2026-11-12 — a finished order looked stuck

SO #11 was fully shipped, invoiced as 292166 and carrying PRO 45566677, and every button
had correctly disappeared — which from the bench is indistinguishable from being broken.
Seven parcel orders were in the same state one branch over, including SO #36 with five
labels printed and nothing saying so.

Both the freight and parcel cards now lead with a green *"This one is done — nothing left
to do here"* box naming the invoice and the tracking, and offering the invoice, the
packing slip and the way back to the queue. The record/label forms fold shut.

Picked-minus-shipped was being calculated in three places on that page and is now one
value. Those three disagreeing is what produced the earlier bug where a freight order was
told "creating the label will invoice it" directly above a card saying there was no label
to create.

**This is the third instance of one pattern**, after the draft trap and the approval gate:
correct logic that says nothing reads as a fault. Every blocked or finished state needs to
name itself where the buttons would be.

### 2026-11-12 — the tablet slept mid-pick

Fire OS caps the screen timeout at 30 minutes and hides "stay awake while charging" behind
Developer Options, which Amazon has been removing — tapping the serial number seven times
did nothing on this device.

`public/assets/js/wakelock.js` takes a **screen wake lock** on the queue, pull list, pick,
pack, label and receiving pages. Nothing to configure on the device and a factory update
cannot undo it. It re-takes the lock when the tab becomes visible, since browsers drop it
whenever the page is hidden.

A badge bottom-right says whether the lock is actually held, because a refused wake lock
looks exactly like a working one until the screen goes dark with someone's hands full.
**Confirmed green on the real Fire tablet.**


### 2026-11-11 — a draft could be created in silence

A new sales order saves as a draft, and a draft never reaches the shipping queue.
Nothing said so. The order has a number, a customer and lines, so the natural reading of
"it is not in shipping" was that shipping is broken.

Worse than the known half: **Save & New** sent no `so_action` and the controller returned
to a blank form *before* reaching the flash. No message at all. Key five orders, press it
five times, and nothing anywhere says the warehouse never heard about any of them.

- The button says **Save draft & New**, and now sets a message naming the SO before
  handing back the empty form.
- The order page carries a notice next to Submit for shipping: *this is a draft, the
  warehouse cannot see it, nothing can be picked or packed against it.*
- Both draft messages name the SO number — the id in the URL is not it, so there was
  previously nothing to search for.
- The Drafts card on the list is amber, clickable through to `?status=draft`, and reads
  "Not visible to shipping" when the count is above zero.

Verified on SO #33 and #34, created back to back through Save draft & New.

### 2026-11-11 — sales_orders.invoice_id was dead

Declared in migration 021 with a foreign key, never written and never read: zero non-null
rows. The real link is `invoices.sales_order_id`, which is the direction that survives an
order being invoiced more than once — SO #15 shipped 9 on one invoice and 21 on another.
Dropped in migration 119. The shipping queue already derived the current invoice with a
subquery rather than trusting the column, so nothing changed behaviorally.

### 2026-11-11 — rep password files were readable by the web server

`create_rep_user.php` writes the password to a file and chmods it `0600`. `deploy.sh`
then runs `find storage -type f -exec chmod 0664` across the whole directory, so every
deploy re-widened them to group-readable by `www-data`. The three rep files have been
shredded. **The deploy behaviour is unchanged** — the next password file written there
will have the same problem. Setting a password in Admin → Users works and needs no file,
which is the simpler path.


### 2026-11-10 — every FedEx error was unreadable

*"FedEx refused the label: FedEx returned HTTP 400 with no message."*

**FedEx gzips its responses, and curl was not asked to decompress them.** So
`json_decode` was handed compressed bytes, failed, and the error parser — which only
ever looked in `body.errors` — had nothing to report. This was not specific to one
label: **every error FedEx has ever returned arrived as "no message"**, with the reason
sitting in the body unread.

Two fixes: `CURLOPT_ENCODING => ''` so curl undoes the compression, and an error parser
that looks everywhere FedEx puts a reason (`errors`, `alerts`, nested under `output`),
names the offending field when there is one, and falls back to the raw response rather
than to a sentence that says nothing.

The same client does tracking, so those errors become readable too.

The failure underneath turned out to be test data: TRUMARK's ship-to reads
**Chattanooga, TN 30534** — and 30534 is Dawsonville, Georgia. FedEx now says so in
plain words: *"Recipient state and postal code mismatch."*

### 2026-11-10 — unticking "Active" on a customer did nothing

**Two checkboxes named `is_active` on one form** — *"Active"* on the Company tab and
*"Active customer"* under Status on Additional Info. An unticked checkbox submits
nothing, so whichever one you could see, the other was still ticked on a hidden tab and
still sent `is_active=1`. Unticking either had no effect, and the page came back saying
Active — which reads as a save that silently failed.

One field, one control. Additional Info now points at the Company tab instead of
offering a second switch for the same thing.

### 2026-11-10 — Larry Fitzpatrick was in the rep list

Not something anyone did in USSCOS: the rep list comes from QuickBooks, which had him
as the sales rep on 36 accounts. He is sales staff, not a rep.

`database/retire_sales_reps.php` holds the names that must never be in the rep list and
takes them out — deactivating the rep record rather than deleting it, so historical
orders still read correctly, and unassigning their customers so a real rep can be given
them. 36 accounts are now unassigned.

**Re-runnable on purpose.** The real import will bring him back; run it again
afterwards.

### 2026-11-10 — shipping could not be charged for at all

There was nowhere on a sales order to put shipping and handling. It is now a field in
the totals, saved as its own line — a line rather than a column, because it has to read
correctly on the invoice and every total in the application already works by summing
lines. Editing an order replaces that line rather than adding to it.

**Get FedEx quote** prices what is on the form, before the order is saved, using the
same cartonizer the label screen uses so the quote and the label describe the same
parcels. Each service shows the carrier price and the charge; the customer sees only
the charge. Markups are in `config/shipping.php` — $10 parcel, $20 freight.

Freight is not quoted in the system: Kuebix price it by hand, so an order the cartonizer
calls freight says so and names the markup to add. (Noted 2026-11-11: Kuebix **does**
have an API that returns both the PRO number and the freight cost — the endpoints are in
[GO_LIVE.md](GO_LIVE.md). It is not wired up, and needs credentials from Kuebix first.
The earlier note here saying there was no API to ask was wrong.)

The line table also keeps one empty row at the bottom instead of making you press
*Add Line* after three.

### 2026-11-10 — every press of Create bought another shipment

The label form stayed open and identical after a successful run, so a slow page, a
double click, or a retry after an error that had actually worked each produced another
set of labels — **and FedEx can bill for every one**. One test order reached eighteen
tracking numbers that way.

The form is not removed, because a genuine extra carton happens. It folds into *"Another
label for this shipment"* and says what pressing it costs; reprints come from the labels
already listed. **Print all** merges every label for a shipment into one PDF using
`pdfunite`, which is already on the server — twelve boxes is twelve labels, and twelve
trips to a Print button is how one gets missed and a box goes out unlabelled.

### 2026-11-10 — labels were created and then orphaned

Found while retrying the TRUMARK label. FedEx returned the PDFs, they were written to
`public/uploads/labels/`, and then nothing pointed at them: the UPDATE that records
where a label lives looked for a `shipment_tracking` row whose `tracking_number` was
every number joined with spaces — a string that is never stored, because tracking is
written one row per parcel. So it matched nothing, `label_path` stayed NULL, and the
**Print** link never appeared. A label could be made once and never reprinted, which is
one jammed printer away from a parcel nobody can ship.

Only the first path was kept anyway, so on a two-parcel shipment the second PDF existed
on disk with nothing pointing at it even if the match had worked. Each PDF is now kept
against its own number. The five labels already orphaned on disk were relinked.

### 2026-11-10 — packing is now required before release

Decided by Chip. An order could go from Picked straight to invoiced with the box never
checked, which is the error the scan-to-verify bench exists to catch.

The gate tests the **quantities**, not the status word: `pack_status` is not reset when
an order part-ships, so an order packed once and then picked again would still read
*verified* while carrying goods nobody had looked at. `mismatch` passes deliberately —
somebody looked, wrote down what was wrong and chose to ship it.

It guards both doors: *Ready to ship* and the **Ship & Invoice** button on the sales
order. *Ready to ship* shows **Pack & verify first** rather than a button that refuses.

**CPU** is what USSC calls customer pick-up, so that is what the screens call it.
Parcel, Freight and CPU are shown whether or not they have work today. A CPU order
offers no carrier label.

### 2026-11-10 — the pack bench was counting the wrong things

Found by Chip walking SO #2 (Monarch Country Club) at the bench.

- **"Shipping and Handling" was a line to pick and pack.** It is money on an invoice,
  not an object in the building. Charge lines are now kept off the pull list, the pick
  screen and the pack screen — matched on the product's item type, or on the wording
  where there is no product. A free-text line that is *not* a charge still appears,
  because that is a real thing somebody has to go and find.
- **The unit was wrong by a factor of twelve.** `uom_code` is the smallest unit the
  catalogue knows, so 9 cases printed as "9 · 18 oz Can". Both screens now say what the
  number counts — *"9 cases of 12 × 18 oz Can"* — and the pack screen adds the box
  breakdown: *"4 × Aerosol double box (2 each) + 1 × Aerosol single box"*.
- **"Release" said nothing about what it does.** It invoices what has been picked, so
  the button now says **Invoice**.
- **Confirming an order was hidden** at the foot of the pack screen under a heading
  about not scanning. It is now stated at the top with the button on it.

### 2026-11-10 — blank form rows were being saved as line items

The order form draws three empty rows, each with a default quantity of 1, and the
parser skipped a row only when its quantity **and** price were both zero — so every
empty row it drew became a line. A one-product order saved three: the pick screen
listed two items nobody could find, *Ready to ship* read 6 ordered instead of 4, and a
packing slip would have printed two blank rows. A line is now real when it **names**
something. The same test was wrong in the invoice parser. 3 saved lines across 2 orders
were cleared by `database/remove_blank_order_lines.php`.

### 2026-11-10 — two gates, two benches

The flow did not match what had been agreed: the clerk's approval sat *after* picking
and was fused to invoicing, so the warehouse started work the moment sales confirmed an
order and nobody had said it was OK.

- **Gate 1 added** — *Shipping → Waiting for your OK*. The warehouse cannot see an order
  at all until the clerk approves it. The approval is its own timestamped column, not a
  status value: collecting a payment sets status to `paid` and releasing sets
  `partially_shipped`, either of which would have erased an approval kept in status.
- **The quantity decision stays at gate 2**, after picking — the stock counts are not
  trusted yet, so a partial decided before anyone walks the floor is decided on numbers
  nobody believes.
- **Parcel and freight are separate benches** throughout: approval screen, shipping
  queue tabs and pull list. `ship_via.ship_mode` says which. FedEx and FedEx Freight
  used to sit side by side as two carrier tabs, which put one person's work inside the
  other's list.
- **USSC's own truck is its own bench.** The live carrier rows are *Customer Pickup* and
  *USSC Delivery*, not the names the seed migration created, so both fell through to
  `other` — and `other` was drawn as *"no carrier yet"*.
- **Batch packing slips** — tick orders at gate 1, print the lot as one job. This
  replaces printing labels and stapling them to slips; the labels leave that habit,
  because a label needs a packed box before it means anything.
- **Every open test order now has a carrier**, so the No Carrier tab is empty and gone.
  18 orders had none: they were approved by the migration because picking had already
  started, so they never passed gate one where the carrier gets settled.
  `database/set_test_carriers.php` splits them the way a real order would split —
  pails, jugs and drums to freight, aerosol cases to parcel — and only touches orders
  that are still work. Re-runnable, dry run by default.

### 2026-10-29 — invoices

An order was invoiced and then could not be paid. One test run, three faults.

- **Invoices were born `draft`, and only *emailing* one moved it to `pending`.** Shipping
  an order raised a draft nobody could pay, because Receive Payment offers pending,
  partial and overdue invoices only. Invoices are now created `pending` — the goods have
  left the building, so the bill is issued.
- **"No open invoices… All invoices are paid in full"** was hardcoded text. The page never
  checked whether anything was paid. It now says nothing is payable and lists the draft
  invoices that are blocking, with links.
- **Total Orders added the invoice count to the sales order count**, so every order that
  reached an invoice counted twice — one order read as two. It now counts sales orders
  plus invoices raised without one.
- **`status = 'overdue'` was read by the invoice KPI but written only by the importer**, so
  the figure sat at zero while the aging report, both dashboards and the customer alerts —
  all of which derive lateness from `due_date` — showed the real number. The KPI, the
  Overdue filter and the per-row badge now derive it too.
- **Drafts were counting as overdue**, which made overdue exceed open. Caught by running
  the new query against live data: 1,055 overdue against 1,052 open is impossible.

### 2026-10-29 — product edit page and the storefront

- **Photos and documents were below the Save button and collapsed by default.** Moved
  above the edit form, open, under a "Photos & documents" heading.
- **The Save button vanished when Compliance was collapsed.** That section's `<details>`
  was never closed, so Save and Cancel were rendered inside it.
- **A color could not have its own photo.** The group shot of every can had to be uploaded
  against one product — White, which leads the group — so White's only photo slot was
  spent. `product_images.is_listing` now marks an image as belonging to the whole listing;
  a card shows it, a product page leads with the color's own photo.
- **The description written on White did not appear on the other colors.** Sharing works
  on save, but that copy was typed before the sharing code existed.
  `database/share_listing_copy.php` backfilled it to 34 siblings.
