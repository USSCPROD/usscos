# Roadmap

Status as of **August 2026**. Last reconciled with `USSCOS_CRM_Vision.docx` in
July — see the note at the top of [Next up](#next-up).

The original blueprint (`Custom_BMS_Blueprint_InHouse.docx`, June 2026) laid out seven
phases over twelve months. Actual build order diverged — CRM and the sales document flow
came first, accounting was deliberately deferred, and several things were added that
weren't in the original plan.

---

## What USSCOS is, and is not

A stated decision rather than an emergent one, because without it the inventory module
will settle the question by accident.

There are three things USSCOS could become:

1. **The ERP itself** — it owns the general ledger
2. **An operating layer above the ERP** — QuickBooks keeps the books
3. **A business platform replacing portions of the ERP** — deliberate division of ownership

**The intent is (3).** Today it is (2) in practice, and it should move to (3) on purpose,
not drift into (1).

The dividing line is not features, it is **which system owns which record**:

| Record | Owner | Notes |
|---|---|---|
| Leads, opportunities, tasks | **USSCOS** | Never existed in QuickBooks |
| Quotes | **USSCOS** | |
| Sales orders, fulfilment, artwork | **USSCOS** | Including the Digital Job Binder |
| Products, categories, media, specs | **USSCOS** | Spreadsheet is the source of truth for content |
| Customers | **USSCOS** | Synced to QuickBooks for invoicing |
| Invoices and payments | **USSCOS** originates | Exported to QuickBooks, which records them financially |
| Inventory **quantity** | **USSCOS** | Once the inventory module lands |
| Inventory **valuation** | **Undecided** | See `ACCOUNTANT_QUESTIONS.md` §1a — blocks the inventory module |
| General ledger, trial balance, financial statements | **QuickBooks** | Unless the accountant says otherwise |
| AP, payroll, tax filing | **QuickBooks** | Not planned for USSCOS |

**Why not own the ledger.** It is the most regulated, least differentiated and highest-risk
part to rebuild, and it wins nothing competitively. The differentiation is entirely in the
operating layer — the job binder, distributor and customer portals, stencil artwork
workflow, tiered pricing. No off-the-shelf ERP does those for a paint and stencil
manufacturer, and that is the whole argument for building rather than buying. It does not
require the general ledger.

**The one open item is inventory valuation**, and it is genuinely load-bearing. Quantity is
operational and clearly ours. Value is a balance-sheet figure that drives COGS, so whoever
owns it is doing accounting. If both systems value stock independently they will diverge,
and the divergence lands on the balance sheet. This is why `ACCOUNTANT_QUESTIONS.md` §1a
must be answered **before** the inventory module is built, not after.

---

## Built

### Sales & CRM
- [x] Public lead capture forms (contact, paint quote, stencil quote with file upload)
- [x] Webhook lead intake with per-form routing and round-robin paint assignment
- [x] Leads with status workflow and conversion to customers
- [x] Opportunity pipeline board and detail page with stage control
- [x] Tasks linked polymorphically to any record
- [x] Quote builder, editor, and branded email; status cascades to lead and opportunity
- [x] Sales orders with status workflow, packing slips, payment collection
- [x] **Ship & Invoice** — tracking capture, invoice generation, order closeout
- [x] Invoices with receipt-vs-invoice email logic
- [x] Payments applied to specific invoices

### Products & data
- [x] Product page with image gallery and document library (SDS/TDS/flyers)
- [x] ~100 product fields covering EDI, Amazon, GS1, packaging, compliance, specs
- [x] Product master importer (idempotent upsert, dry-run first)
- [x] Vendors, purchase orders, raw materials
- [x] Inventory transactions

### Platform
- [x] Custom MVC framework, session auth, nine roles
- [x] Admin area — users, departments, company defaults, lookups, lead routing
- [x] Role-aware dashboard
- [x] USSCOS moved to its own subdomain, separated from the public website

### Reps, access and reporting — August 2026
- [x] `sales_reps` as its own entity, imported and corrected against the QuickBooks Rep list
- [x] Rep credentials entered directly on the rep form (no linked-login dropdown)
- [x] Access scoping — reps and distributors see only their own customers' invoices and
      sales orders, enforced in repositories so a guessed URL can't bypass it
- [x] `invoices.processed_by` imported from QuickBooks, and **Sales by Employee** built on it
- [x] Date-range periods on the sales reports, defaulting to year to date
      (`App\Services\ReportPeriod`, reusable by the remaining accounting reports)
- [x] Customer creation with duplicate flagging — unscoped search, redacted results, so a
      rep is stopped from re-adding a company they cannot see
- [x] Deactivate everywhere, and delete only where nothing references the row, across all
      eight admin lists
- [x] My Profile with self-service password change
- [x] CSRF enforced

## Waiting on someone else

Not blocked on build effort — listed so they aren't mistaken for work in progress.

| Item | Waiting on |
|---|---|
| Shipping & inventory | Accountant: who owns inventory valuation (`ACCOUNTANT_QUESTIONS.md` §1a) |
| Teardown and clean reimport | QuickBooks SKU rename, then a dry-run match **before** anything is deleted |
| Blank invoice fields (Terms, Ship Via, P.O.) | A QuickBooks export carrying those columns — never in the original 10-column import |
| Sales by Distributor | The distributor list (Turf Tank, BSN, others) |

Agreed 2026-08: only **two to three years** of history needs importing, since QuickBooks
keeps the ledger. The current data is already ~25 months, so this is a confirmation of
present practice rather than a change. Worth pruning customers at the same time — 41,164
records exist but only 4,156 have ever ordered.

## Next up

> Reconciled July 2026 with `USSCOS_CRM_Vision.docx`, which proposed a fuller CRM
> layer (Customer Intelligence, Marketing, expanded Rep Portal). That document's
> sequencing is folded in below rather than tracked separately, so there's one list.

1. ~~**Version control and a deploy script**~~ — done 2026-08-03. Repo at
   `github.com/USSCPROD/usscos`; deploys via `deploy/deploy.sh`. The platform also moved
   to a DigitalOcean droplet with HTTPS — see [DIGITALOCEAN.md](DIGITALOCEAN.md).
2. ~~**Customer Intelligence**~~ — done 2026-08. 360° profile (lifetime revenue, order
   count, avg order size, purchase frequency, top products, outstanding balance), a
   unified activity timeline, and inactivity alerts judged against each customer's own
   rhythm rather than a fixed threshold. Built as queries on existing tables — no
   `activities` or `customer_alerts` table was needed after all.
3. **Quoting: customer-specific pricing** — account-level price overrides per customer
   per product, layered under the existing retail/distributor/stocking-distributor
   tiers, plus volume discount rules. Self-contained, doesn't depend on anything else
   in this list. New `customer_product_prices` and volume-discount-rules tables.
4. **Rep portal (expanded scope)** — supersedes the original "own orders and
   commissions, no leads or opportunities" scope. Now includes: a rep-scoped dashboard
   (my customers, my pipeline, my tasks, MTD/YTD vs. goal, at-risk customers from #2's
   alerts), a daily task dashboard with overdue alerts (absorbs the small remaining
   Pipeline & Leads polish — disqualify-reason tracking, overdue notifications), and
   commission tracking (rate per rep or product category, calculated on invoice paid
   vs. sent, monthly statement, adjustments for returns/credits). Depends on #2 and #3.
   > **Prerequisite — mostly cleared as of 2026-08.** The earlier note here said there was
   > no rep data anywhere. That is no longer true, and the approach it described has been
   > superseded.
   >
   > Reps are their own entity in `sales_reps` (migration 048), not users — most are
   > outside or former reps with no login, and their QuickBooks history has to survive
   > either way. The join key is `sales_reps.quickbooks_name`; a rep's login, where they
   > have one, hangs off `sales_reps.user_id`. **`customers.rep_id` and `invoices.rep_id`
   > are legacy and are not the columns to use** — attribution lives in
   > `customers.sales_rep_id` and `invoices.sales_rep_id`.
   >
   > Current state: 33 reps, **9,339 of 34,729 invoices** credited, 314 customers assigned.
   > The gap is because the QuickBooks Sales by Rep export covered 2026 only — it fills in
   > when the fuller history is imported.
   >
   > Also done: rep and distributor logins are created directly on the rep form; access
   > scoping limits them to their own customers' invoices and sales orders
   > (`App\Services\AccessScope`); `invoices.processed_by` records who keyed each order,
   > which is **not** attribution — see `Sales by Employee`.
   >
   > Still outstanding for this item: the rep-scoped dashboard, commission calculation and
   > statements, and `sales_goals` (migration 045) is still empty, so MTD/YTD vs. goal has
   > no targets. No user carries `role='rep'` yet either — the capability exists, nobody
   > has been given it.
5. **Customer portal** — order history, invoices, quotes, and a **Reorder** button that
   creates a new sales order from a past one. Accounts created internally via an invite
   sent from the customer record.
   > Now the authenticated half of the public site rather than a separate build — see
   > [Website & Publishing](#website--publishing). Best done alongside that work, since
   > both need the same public-facing layout and login.
6. **Digital Job Binder** — expand the sales order into a full job record: artwork with
   revision history, production notes, QA checklist, shop-floor photos, shipping
   documents. Everything about a job in one place, retrievable years later.
7. **Security hardening** — 2FA, login lockout, audit log.
   > CSRF is now enforced (2026-08). The middleware existed but was applied to no route;
   > 36 POST forms were missing tokens and have been fixed. `/api/*` and `/webhook/*`
   > stay exempt by design.

## Website & Publishing

Decided 2026-08-03, after usscproducts.com (WordPress) was compromised. **The public
website becomes part of USSCOS rather than a separate CMS.**

The reasoning is as much practical as security: the catalog already lives here. 891
products with descriptions, images, documents and pricing, plus columns built for exactly
this — `publish_to_website`, `website_description`, `website_price`, `website_image_url`,
and `is_public` on every document. A separate CMS means maintaining the catalog twice or
building a sync layer. One system means "publish" is a flag the public routes respect, so
a published product is live the moment it's saved. Nothing to export, rebuild, or push.

It also removes the attack surface that caused the incident: no plugins, no third-party
code executing, one auth system.

Target structure — one application, three audiences separated by authentication:

```
usscproducts.com            Public site — catalog, product pages with SDS/TDS
                            downloads, content pages, lead forms
usscproducts.com/account    Customer portal (authenticated)
os.usscproducts.com         USSCOS internal
```

### Structure — confirmed August 2026

The live site is the model: *"usscproducts.com is what we want to copy. Get as close to
that as possible. Mobile matters a lot."* Its category structure governs — see
**[WEBSITE_TAXONOMY.md](WEBSITE_TAXONOMY.md)** for the full tree.

```
Category (L1)     Field Marking Paints
  Category (L2)   Aerosol Field Marking Paints        ← leaf
    Group         DuraStripe Fat Cans                 ← one page, one URL
      SKU         DSWFC / DSRFC / DSYFC …             ← colour × pack picker
```

Categories run **2–3 levels** (three under Striping Machines), with **product groups**
below them and SKUs as variants. Roughly 8 top-level and 85 subcategories — no pruning;
Specialty Coatings keeps all 26 children.

**Variants are display-only grouping, not a schema refactor.** A group is
`(brand, product_line)`; options come from `color` and `pack_level`. The site renders one
page with pickers and add-to-cart resolves to the real SKU. Products stay flat internally,
which is correct — each variant is genuinely made, stocked and shipped as its own item with
its own barcode and weight. The data already supports it: 42 product lines over 627
products, 726 carrying a colour (Fat Can = 7 SKUs/7 colours; T-Tip = 84/41, second axis
being pack).

**The spreadsheet is the source of truth for which products exist.** The hand-written
taxonomy contains discontinued lines, so validate every group against the current workbook
(now in OneDrive, not on the Desktop) before building category pages.

**Correction to migration 044:** `Resale` was flagged `show_on_website = 0`. That's wrong —
striping machines, robots, janitorial and field maintenance are all resold and are a major
part of the online catalog. Fix before publishing.

What the current site uses, for reference: Porto theme, Elementor Pro (page builder),
RevSlider (banners), WooCommerce, **woo-discount-rules-pro** (volume pricing today),
flexible-shipping, and google-listings-and-ads — so the product model must keep supporting
a Google Shopping feed. Brand colour is **#730b12**, not the admin navy; fonts are
Montserrat, Oswald, Roboto and Roboto Slab.

### Build order

**1. Categories** — foundational, and already required by EPIM Chapter 3.
`products.category` is a flat string today. Needs a real `categories` table (nested
parent/child, slug, description, image, sort order) plus a `product_categories` join so a
product can belong to several. Drives site navigation, category landing pages, filtering,
and fixes the variant/brand confusion that produced the junk brands.

**2. Product publishing + public catalog** — the highest-value step, and it lands early.
- Publish toggle on the product page; **bulk publish** from the product list; publish an
  entire category in one action
- A **Website** tab per product: web title, marketing copy, hero image, gallery,
  which documents are public, SEO title and meta description
- Public catalog and product detail pages reading straight from the same database

Once this ships, 891 products are live with real categories and downloadable SDS sheets —
before the page builder is even started. Marketing pages can stay simple HTML meanwhile.

**3. Rotating banners** — `banners` table: image, headline, subhead, button text, link,
sort order, active flag, optional start/end dates so promos expire on their own. Admin
screen to reorder and preview. Small, self-contained, very visible.

**4. Page builder — section-based** (confirmed 2026-08-03, not drag-and-drop).

A page is an ordered list of typed sections; you pick a type, fill its fields, drag to
reorder. Roughly a fifth the effort of a visual builder and it produces better pages,
because every section is designed rather than freely positioned — the same model Shopify
and Squarespace use underneath.

| Section type | Fields |
|---|---|
| Hero | Image, headline, subhead, button |
| Banner carousel | Pulls from the banners table |
| Text | Rich text |
| Text + image | Copy, image, side |
| Product grid | By category or hand-picked products |
| Feature columns | 2–4 icon/title/text blocks |
| Document list | SDS, TDS, catalogs |
| Contact form | Which form to embed |
| Video / Gallery / CTA | — |

Tables: `pages` and `page_sections` (type, sort order, JSON payload of that type's fields).

**5. Media library, navigation menus, per-page SEO.**

### Notes

- Absorbs the "Website Publish" workflow from the EPIM outline.
- Reshapes the **customer portal** below: it stops being a bolt-on and becomes the
  authenticated half of a site being built anyway.
- Needs a small content editor for marketing pages so copy changes don't require a
  developer — that was WordPress's only real advantage here.

## Planned

### Operations
- **Inventory** — barcode scanning in and out; invoice deducts stock; low-stock alerts
- **Shipping** — FedEx API for labels and automatic tracking; freight carriers with
  Bill of Lading printing
- **Production module** — batch records, versioned paint formulas, raw material
  consumption with variance, QC sign-off, labor and waste feeding true cost per batch

### Intelligence
- **Email integration** — Microsoft 365 OAuth per user; customer emails on the record
  timeline; send from inside the system. *Blocked on Azure AD credentials.*
- **AI layer** — reorder prediction, quiet-account alerts, AI-drafted quotes, plain-English
  Q&A over job history, daily morning brief, margin anomaly alerts
- **Reporting** — Business Health Score, job profitability, role dashboards,
  natural-language search, custom report builder
- **Marketing** — email campaigns (template editor, scheduled sends, open/click
  tracking, unsubscribe handling), dynamic customer segments built from live purchase
  data (top customers, at-risk, new, by product line, by region, by rep), and
  behavior-triggered automations (welcome, re-engagement, quote follow-up, thank-you,
  low-inventory alert). Largest lift of anything in this document — needs a segment
  engine, a send/tracking pipeline, and a trigger runner built from scratch. Placed
  last because it depends directly on the activity/alert data from Customer
  Intelligence, above.

### Enterprise Product Information Management (EPIM)

Outlined in `EnterPrise Product Information Management Outline.docx` — a business-first
spec written *before* any schema, in nine chapters (Vision → Philosophy → Information
Architecture → Digital Product Model → Functional Requirements → UI → Database → API →
Workflows), with every chapter closing on Business Rules, AI Opportunities, and Future
Expansion.

The product module already covers a good part of Chapter 4's information model
(~100 fields across identity, marketing, pricing, inventory, accounting, shipping,
website, Amazon, EDI, media, documents). What EPIM adds beyond what exists:

- **Category → Brand → Product → Variant hierarchy.** Today there are brands and a flat
  `category` string. No category table, and no variant concept — colors and sizes are
  separate SKUs, which is why `DURASTRIPE WHITE` ended up misfiled as a brand.
  *(The category half is now step 1 of [Website & Publishing](#website--publishing);
  variants remain open.)*
- **Product relationships** — related, replacement, accessory, cross-sell.
- **Approval and publish workflows** — separate publish states for website, distributor,
  and Amazon, with revision control and document approval. *(The website half is now
  scheduled under [Website & Publishing](#website--publishing).)*
- **Bulk edit and a media manager** across the catalog rather than per product.
- **Versioning** — product history, not just `updated_at`.
- **AI panel** — auto-generate SEO copy and Amazon listings, detect missing SDS, suggest
  related products, flag missing images.

Sequencing note: this is a large design effort and the catalog is functional today.
Worth writing the spec in parallel with building the Next-up list rather than pausing
delivery for it — with one exception, the **category table and variant model**, which
get harder to retrofit the longer the catalog grows.

### Business structure
- **Distributor portal** — tier pricing (retail / distributor / stocking distributor)
  with commission on the spread. *Awaiting detail on how the two distributor types differ.*
- **Intercompany** — TCC (Technical Coatings) and USSC as two legal entities, with an
    intercompany PO flow and entity-level access scoping. **This is structural, not a
    feature** — see [INTERCOMPANY.md](INTERCOMPANY.md). Cheapest to retrofit during the
    clean reimport, since everything is being reloaded anyway.
- **Internal messaging** — employee DMs with notification badge
- **Website port** — usscos.com → usscproducts.com, same structure

## Accounting

A full technical spec now exists:
**[USSCos_Accounting_Module_Spec.md](USSCos_Accounting_Module_Spec.md)** (August 2026).
Accrual basis, C-corp equity, three-stage manufacturing inventory, multi-state sales tax.
Contains a paint-manufacturer chart of accounts, entity definitions, journal entry logic
per workflow, a reconciliation schedule, and an anti-patterns list.

The accounting judgement is sound — immutable ledger with reversing entries, undeposited
funds for checks, gross revenue with processor fees posted separately, one Sales Tax
Payable account plus a jurisdiction sub-ledger, and a recommendation to use Avalara or
TaxJar rather than building nexus logic in-house.

**Sequenced behind [Website & Publishing](#website--publishing)**, which has a hard driver
(usscproducts.com is a compromised WordPress install) where accounting has none —
QuickBooks works today. Building the website also advances this work, since the cart
produces the `online_orders` data the spec's §4.2 depends on.

### Before writing code from the spec

It was written without access to the codebase, so it reads greenfield when most of it
already exists.

**Already built** — `sales_orders`, `invoices`, `payments`, `payment_applications`,
`vendors`, `purchase_orders`, `bills`, `bill_line_items`, `inventory_transactions`.
Sections 3.2–3.4 are a mapping exercise, not new construction.

| Spec name | Actual table |
|---|---|
| `accounts` | `chart_of_accounts` |
| `inventory_items` | `products` (`item_type` already includes `raw_material`) |
| `bill_of_materials` | `product_components` |
| line item `quantity` | `qty_ordered` (SO lines) / `qty` (invoice lines) |

**Genuinely new** — `online_orders`, `production_runs`, `production_run_consumption`,
`tax_jurisdictions`, `sales_tax_collected`, the five posting services, and seeding the
chart of accounts.

### The decision this rests on

`chart_of_accounts`, `journal_entries` and `journal_entry_lines` have existed since
migrations 015–017 and are **empty** — but they were built to *mirror* QuickBooks. They
carry `quickbooks_id` and `last_synced_at`, and use QuickBooks' 15-value account-type enum
rather than the spec's six-way split.

The spec assumes USSCOS **replaces** QuickBooks. That fork drives everything downstream —
opening balances, who runs month-end, what the CPA touches. **Settle it before
implementation starts.**

The stated position is in [What USSCOS is, and is not](#what-usscos-is-and-is-not):
QuickBooks keeps the ledger, USSCOS owns the operating layer. The live sub-question is
inventory valuation — `ACCOUNTANT_QUESTIONS.md` §1a — which blocks the inventory module.

### Gaps in the spec

- **Opening balances.** Not covered in §9. Becoming the ledger of record requires a cutover
  trial balance: every account's opening figure, open AR by invoice, open AP by bill,
  inventory on hand at cost. Usually the hardest part of an accounting migration, and it
  needs the CPA involved.
- **Period close / locking.** Entries are immutable, but nothing prevents posting into a
  month already reconciled and filed. A `closed_through_date` hard block is cheap insurance.
- **§4.8 side effect.** Capitalising overhead by crediting 6700/6950 leaves those expense
  accounts with odd — occasionally negative — balances between quarterly true-ups. Inherent
  to the simplified v1 and the spec is honest about it, but the bookkeeper should expect it.

### Dependencies

COGS recognition (§4.1) needs `production_runs` and per-unit cost — the **production
module**, not built. `online_orders` (§4.2) is the **e-commerce cart**, part of the website
module. Accounting cannot complete before both exist.

### Worth pulling forward regardless

Neither needs a general ledger, and both would be used weekly:

- **AR aging** — who owes what, how overdue
- **Job costing** — materials + labour + shipping against what was charged; this is what
  actually feeds job profitability reporting

## Corrections to `features.docx`

That document flagged several items as "unclear" or guessed at their status. Checked
against the schema and views in July 2026 — these are the ones it got wrong:

| It said | Actually |
|---|---|
| Quote expiration date is "Future, not a current field" | **Exists** — `quotes.expiry_date`, on the quote form and document |
| Quote status `expired` is "Future" | **Exists** in the enum. What's missing is anything that flips it automatically |
| Close date per deal "unclear" | **Exists** — `opportunities.expected_close`, shown on the detail page |
| Drag to move between pipeline stages "Built" | **Not built** — no drag handlers; stage changes go through a dropdown |
| Disqualify-with-reason "unclear" | **Split** — `opportunities.lost_reason` is captured; `leads` has no reason field, only `dead` status |
| Assigned rep on customer profile "partially built" | **Column exists, data doesn't** — `customers.rep_id` came in with migration 026 but is populated on 1 of 41,233 rows; rep was never exported from QuickBooks (see the Rep Portal prerequisite above) |
| Call/email/meeting task types "unclear" | **Not built** — no `type` column on `tasks` |
| Stages "New → Qualified → Quoted → Negotiating → Closed" | Actual: `prospecting`, `proposal`, `negotiation`, `closed_won`, `closed_lost` — three open stages, no separate "Quoted" |

Small items worth folding into the Next-up work rather than tracking separately:
auto-expiring quotes past `expiry_date`, a `lead.lost_reason` field, task types, and
drag-and-drop on the pipeline board (JavaScript only — unaffected by the table-layout
convention).

## Known cleanup items

- **Junk brands from the import** — `DURASTRIPE WHITE/BLUE/YELLOW/RED` (colors treated as
  brands) and six `PleeFix …` variants (product names treated as brands). Deferred until
  the master spreadsheet is final, since re-importing would recreate them.
- **Spreadsheet gaps** — ~70 SKUs missing (the ROBO line especially); the Brand column on
  the secondary tabs holds product names; the `Amazon Deals` tab has no header row.
- **`price` vs `retail_price`** — duplicate columns on `products`. `price` is canonical
  and `retail_price` is kept mirrored; the dead column could be dropped.
- **Product documents are per-product** — a shared library with per-product links would
  stop the same catalog being uploaded dozens of times.
- **Inconsistent field naming** — the same product description is labelled "Sales
  Description", "Short Description", and "Sales" in three different places.
- **The flex/grid convention** is probably based on a misdiagnosis and is worth
  retesting; see [CLAUDE.md](../CLAUDE.md#layout-use-tables-not-flexgrid).
