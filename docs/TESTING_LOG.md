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
  consuming would preserve it and make the first movement behave. Not done yet — it
  changes inventory behaviour and that is Chip's call.

*The DSY count was restored to 387 by hand after the test.*

### Other

| What | Notes |
|---|---|
| Uploads post immediately and reload the page | Anything typed in the edit form and not yet saved is lost. Save first, then upload. Worth making the upload not discard the form |
| 186 non-stencil products have `gross_weight` but no `case_weight_gross` | The cartonizer reads `case_weight_gross`, so these ship without a weight |
| 44 traffic paints and 17 striping machines are misfiled under Specialty Coatings | Category assignment, not a code fault |
| `database/issue_draft_invoices.php` | Only exists for invoices stranded by the old draft behavior. Delete it once the data is re-imported |
| A new sales order is saved as **Draft**, and a draft is invisible to the shipping queue | Nothing on the order says so. Found on 2026-11-10 while walking the flow — it is the most likely thing to derail a demo. Worth either defaulting to Confirmed or saying plainly on the order that shipping cannot see it yet |
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

- **`database/import_quickbooks.php` is not ready.** Three faults: it writes
  `status = 'sent'`, which a later migration renamed to `pending`; it has **no payments
  step at all** (nothing writes to the `payments` table); and because its source is the
  open A/R aging report it sets both `total_amount` and `balance_due` to the outstanding
  balance, so a part-paid invoice imports as a smaller invoice. Fixing it needs sample
  exports of the files that will actually be used.
- **`ar_opening_date` must be set at cutover**, and there is no screen for it. It is the
  date USSCOS takes over its own receivables, and it does not exist yet. Until it is set,
  `ArPeriod` fences nothing off and imported history counts as USSCOS receivables.

---

## Fixed

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

Freight is not quoted: Keubix price it by hand and there is no API to ask, so an order
the cartonizer calls freight says so and names the markup to add.

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
