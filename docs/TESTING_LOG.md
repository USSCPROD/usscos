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

| What | Notes |
|---|---|
| Uploads post immediately and reload the page | Anything typed in the edit form and not yet saved is lost. Save first, then upload. Worth making the upload not discard the form |
| 186 non-stencil products have `gross_weight` but no `case_weight_gross` | The cartonizer reads `case_weight_gross`, so these ship without a weight |
| 44 traffic paints and 17 striping machines are misfiled under Specialty Coatings | Category assignment, not a code fault |
| `database/issue_draft_invoices.php` | Only exists for invoices stranded by the old draft behavior. Delete it once the data is re-imported |

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
