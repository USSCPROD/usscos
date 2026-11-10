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
| `pack_status` stays `not_started` and nothing requires packing before release | An order can be released and invoiced without ever being verified at the pack bench. *Ready to ship* reads Complete off the picked quantity alone. Worth deciding whether packing should gate release |
| A new sales order is saved as **Draft**, and a draft is invisible to the shipping queue | Nothing on the order says so. Found on 2026-11-10 while walking the flow — it is the most likely thing to derail a demo. Worth either defaulting to Confirmed or saying plainly on the order that shipping cannot see it yet |
| The id in the URL is not the SO number | `/sales-orders/24` is SO #11. Every link passes the right one, so it only bites when a URL is typed or read aloud |

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

- **Gate 1 added** — *Shipping → Orders to approve*. The warehouse cannot see an order
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
