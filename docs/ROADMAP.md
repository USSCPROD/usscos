# Roadmap

Status as of **July 2026**. Last reconciled with `BusinessOS_CRM_Vision.docx` on
the same date — see the note at the top of [Next up](#next-up).

The original blueprint (`Custom_BMS_Blueprint_InHouse.docx`, June 2026) laid out seven
phases over twelve months. Actual build order diverged — CRM and the sales document flow
came first, accounting was deliberately deferred, and several things were added that
weren't in the original plan.

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

## Next up

> Reconciled July 2026 with `BusinessOS_CRM_Vision.docx`, which proposed a fuller CRM
> layer (Customer Intelligence, Marketing, expanded Rep Portal). That document's
> sequencing is folded in below rather than tracked separately, so there's one list.

1. **Version control and a deploy script** — highest risk reduction available; see
   [DEPLOYMENT.md](DEPLOYMENT.md#known-risks)
2. **Customer Intelligence** — 360° profile (lifetime revenue, order count, avg order
   size, purchase frequency, top products, outstanding balance) on the existing customer
   page; a unified activity timeline merging notes, orders, invoices, payments, and
   quotes; smart alerts (60/90/180-day inactivity, order frequency drop, aging balance,
   quote expiration). Foundational — the activity feed and alert logic get reused by
   both Rep Portal and Marketing below. No new tables for the profile stats; likely one
   new `activities` table and one `customer_alerts` table.
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
   > **Prerequisite:** `customers` has **no rep column**. Reps are recorded on leads,
   > quotes, orders, and invoices but never on the customer itself, so "my customers"
   > has nothing to query. Add `customers.rep_id` (plus a backfill from each customer's
   > most recent order) before starting this. Also needed: a goals/quota table — nothing
   > anywhere tracks targets today.
5. **Customer portal** — order history, invoices, quotes, and a **Reorder** button that
   creates a new sales order from a past one. Accounts created internally via an invite
   sent from the customer record. Lives on the public site, not inside USSCOS.
6. **Digital Job Binder** — expand the sales order into a full job record: artwork with
   revision history, production notes, QA checklist, shop-floor photos, shipping
   documents. Everything about a job in one place, retrievable years later.
7. **Security hardening** — 2FA, login lockout, audit log.

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
- **Product relationships** — related, replacement, accessory, cross-sell.
- **Approval and publish workflows** — separate publish states for website, distributor,
  and Amazon, with revision control and document approval.
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
- **Intercompany** — US Technical Coatings as a second entity with intercompany PO flow
- **Internal messaging** — employee DMs with notification badge
- **Website port** — usscos.com → usscproducts.com, same structure

## Deferred on purpose

**Full general ledger.** The blueprint's Phase 2 called for double-entry bookkeeping and
financial statements. QuickBooks remains the ledger, with USSCOS owning invoicing and
AR. This is the highest-risk, lowest-differentiation part of the plan — nothing that makes
this system valuable (job binder, portals, production, AI) depends on it. The scaffolding
tables (`chart_of_accounts`, `journal_entries`, `journal_entry_lines`) exist but are unused.

What's worth building before a GL:
- AR aging
- Clean invoice export to QuickBooks
- **Job costing** — materials + labor + shipping against what was charged. This is what
  actually feeds the job profitability reporting, and it needs no ledger.

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
| Assigned rep on customer profile "partially built" | **Not built** — `customers` has no rep column at all (see the Rep Portal prerequisite above) |
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
