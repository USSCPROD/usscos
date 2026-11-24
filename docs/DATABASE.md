# Database

MySQL 8, InnoDB, `utf8mb4_unicode_ci`. Accessed exclusively through PDO with prepared
statements, and exclusively from repository classes.

---

## Migrations

49 files in `database/migrations/`, numbered in run order. A handful of early ones
predate the numbering scheme (`add_defaults_to_companies.sql`, `create_crm_phase2.sql`,
`create_customer_notes.sql`, `rename_sent_to_pending_invoices.sql`,
`add_payment_to_sales_orders.sql`, `add_sales_order_id_to_payments.sql`).

**Rules**

- Never edit a migration that has already run on the server. Add a new numbered one.
- Run the SQL **before** uploading PHP that depends on it, or the new code queries
  tables that don't exist and the page dies.
- `ALTER TABLE … ADD COLUMN … AFTER x` can reference a column added earlier in the
  *same* statement — MySQL processes the clauses in order.

Run them with `php database/migrate.php`, or paste into phpMyAdmin → SQL.

## Table map

### Core

| Table | Notes |
|---|---|
| `companies` | Single row (id = 1) holding company-wide defaults |
| `users` | Auth + role enum |
| `departments` | Org structure |
| `activity_log` | The timeline: what happened, to what, by whom, when. Written through `App\Services\Activity` |
| `settings` | Key/value store — lead routing config lives here |

### The activity log

`activity_log` was created in migration 003 and nothing wrote to it until 24 Nov 2026.
It now carries two kinds of entry:

- **System events** — `quote.sent`, `order.shipped`, `customer.rep_changed`. Written by the
  code that does the thing, with a whole sentence in `description` including the numbers,
  because a timeline of "updated" entries answers nothing.
- **Logged interactions** — `note.call`, `note.email`, `note.meeting`, `note.note`. What a
  rep did, in their own words, through the form on a lead or opportunity.

Three things about it that are easy to get wrong:

1. **It has a party axis as well as a subject.** `subject_type`/`subject_id` is the record
   ("invoice 412"); `customer_id`/`lead_id` is who it is about. Migration 126 added the
   second because a customer timeline spans seven tables and would otherwise be a
   seven-way UNION rewritten every time a module is added. Set both.
2. **Writing to it must never be able to fail the work.** `Activity::log()` swallows every
   throwable into the error log, deliberately. Recording that an invoice was issued must
   not be able to stop an invoice being issued.
3. **Nothing edits or deletes entries**, and the audit screen says so. A logged call is a
   contemporaneous record; being able to revise it is most of what would make it
   untrustworthy. Corrections are logged as corrections.

The customer page's "All Activity" tab is the derived seven-way UNION *plus* the log as an
eighth branch, excluding the three events a derived branch already covers
(`quote.created`, `order.created`, `customer.created`). The derived branches infer events
from records that still exist, so they can say "invoice issued" but never "rep changed" —
the log is the only branch carrying things somebody *did*.

**Known seam:** customers log notes into `customer_notes`; leads and opportunities log into
`activity_log`. Two mechanisms for the same idea. Customers kept theirs because the form
and its history predate the log, and migrating it is a separate decision — both render in
the customer timeline either way.

### Customers & vendors

| Table | Notes |
|---|---|
| `customers` | Billing/shipping addresses, terms, tax, type |
| `customer_notes` | Free-text notes per customer, with a type (note/call/email/meeting). **Predates `activity_log`** — see the note below |
| `distributors`, `customer_distributor_links` | Channel structure |
| `vendors` | Suppliers |

### Products

| Table | Notes |
|---|---|
| `products` | ~100 columns. Also holds raw materials via `item_type='raw_material'` |
| `product_brands` | `name` and `slug` are both `NOT NULL UNIQUE` |
| `product_components` | Bill of materials for assemblies |
| `product_images` | Gallery; `is_primary` flags the main image |
| `product_documents` | SDS, TDS, flyers, catalogs; `is_public` gates website visibility |

### Sales flow

| Table | Notes |
|---|---|
| `leads` | `company_name` is `NOT NULL` |
| `opportunities` | Pipeline stages, expected value, probability, next step, lost reason — see below |
| `quotes` + `quote_line_items` | `customer_id` nullable (quotes can attach to a lead) |
| `sales_orders` + `sales_order_line_items` | Line item qty column is **`qty_ordered`** |
| `invoices` + `invoice_line_items` | Line item qty column is **`qty`** |
| `payments` + payment applications | Payments apply to specific invoices |
| `tasks` | Polymorphic — nullable FKs to customer, lead, opportunity, quote, sales order |

### Purchasing & inventory

`bills` + `bill_line_items`, `purchase_orders` + line items, `inventory_transactions`.

### Accounting (scaffolded, not in active use)

`chart_of_accounts`, `journal_entries`, `journal_entry_lines`. QuickBooks remains the
ledger for now — see [ROADMAP.md](ROADMAP.md).

### Lookups

`payment_terms`, `units_of_measure`, `tax_rates`, `ship_via`, `customer_messages`.

## Sales document flow

```
Lead ──► Opportunity ──► Quote ──► Sales Order ──► Invoice ──► Payment
             │              │           │              │
             └──────────────┴───────────┴──────────────┘
                    all can carry Tasks and Documents
```

Status changes cascade: emailing a quote moves it to `sent`, which advances the lead to
`contacted` and the opportunity to `proposal`. Accepting advances to `closed_won`.
Shipping a sales order creates the invoice and closes the order.

## Constraints that bite

### Products are referenced from seven tables

| Table | On delete |
|---|---|
| `quote_line_items` | **RESTRICT** |
| `sales_order_line_items` | **RESTRICT** |
| `inventory_transactions` | **RESTRICT** |
| `product_components` | **RESTRICT** |
| `invoice_line_items` | **SET NULL** |
| `bill_line_items` | **SET NULL** |
| `purchase_order_line_items` | **SET NULL** |

So `DELETE FROM products` fails outright on anything quoted or ordered — and if forced
with FK checks disabled, it *succeeds* while silently erasing the product link on every
historical invoice, bill, and PO. Deactivate (`is_active = 0`) instead of deleting.

### NOT NULL columns without a sensible null

`products.qty_on_hand`, `qty_on_sales_order`, `qty_on_po`, `track_inventory`,
`is_active`, `is_taxable`, `is_hazmat`, `publish_to_website`, `item_type` — all
`NOT NULL` with defaults. Writing `null` errors; **omit the column** instead so the
existing value or the default applies.

Also `NOT NULL`: `products.quickbooks_item` (and `UNIQUE`), `product_brands.name` and
`.slug` (both `UNIQUE`), `leads.company_name`.

### Column naming inconsistencies

- Sales order lines use `qty_ordered`; invoice and quote lines use `qty`.
- Sales order lines use `taxable`; invoice and quote lines use `is_taxable`.
- `products` has both `price` and `retail_price`. **`price` is canonical** — it's what
  the UI and all quote/order code read. `retail_price` is legacy and is kept mirrored
  to `price` on save.

Copying line items between document types requires mapping these — see
`InvoiceService::shipAndInvoice()`.

### PDO named parameters

Each named parameter may appear only once per statement. Repeat the value under a second
name (`:total`, `:total2`) rather than reusing one.

## Data import

| Script | Purpose |
|---|---|
| `database/import_products.php` | Product master from `USSC EDI-ERP DATA.xlsx` — upsert by SKU |
| `database/import_quickbooks.php` | Initial QuickBooks item/customer import |
| `database/import_sales_history.php` | Historical sales |
| `database/reimport_line_items.php` | Line item repair |

`import_products.php` is the pattern to copy: dry run by default, `--commit` to write,
single transaction, never deletes without an explicit flag. See
[DEPLOYMENT.md](DEPLOYMENT.md#importing-the-product-master) for usage.

## Backups

Before any bulk operation: phpMyAdmin → select database → check the affected tables →
**Export** → Go. Ten seconds, and it's the difference between a mistake and an incident.

---

## Opportunities: next step and lost reason

Two columns on `opportunities` exist to be reported on, and each has a rule that is not
obvious from the schema.

**`next_step` / `next_step_date`** (migration 128) drive the "needs attention" panel on the
dashboard and the pipeline. An opportunity with no next step is flagged, because nothing
ever creates a task from an opportunity — so one with nothing planned against it generates
no task, raises no flag, and is invisible until somebody recognises the name. Free text
deliberately: "ring the AD about field dimensions" beats any dropdown.

**`lost_reason_code`** (migration 129) is what the win/loss report groups by;
`lost_reason` is the free-text detail a person reads. Both are **cleared whenever the stage
moves to anything other than `closed_lost`** — a deal reopened and then won would otherwise
still carry the reason it was lost for, and the report would count it twice.

Three things to keep:

1. **A loss must state why.** `updateStage()` is refused without a valid code, and it is not
   defaulted to `other` — a default fills the report with the one value that tells nobody
   anything, and is indistinguishable from reasons somebody chose. `lost_reason` sat empty
   from the initial commit to 24 Nov 2026 precisely because the board's "Lost" button posted
   the stage and nothing else.
2. **The codes live in `App\Services\LostReasons`,** not an enum. Changing a label is safe;
   changing a code orphans deals already recorded against it, which is why `label()` renders
   an unknown code as "Retired reason: x" rather than dropping the row.
3. **Deals with no code are reported as their own row,** not filtered out. "We do not know
   why we lost 40% of these" is the most important line on the page.

The stage panel stays visible on closed deals so a loss can be given a reason after the
fact and a deal closed by mistake can be reopened. While it was hidden, every pre-existing
loss was stuck under "No reason recorded" for good.
