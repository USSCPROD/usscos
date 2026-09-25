# Modules

What each part of the system does, and how the main flows run.

---

## Lead capture (public)

Three forms on the public site (`website/` → deployed to `usscos.com/forms/`):

| Form | Fields of note |
|---|---|
| Contact Us | Name, email, phone, subject, message |
| Paint Quote Request | Address, current brand, products needed (aerosol / bulk / robot / other) |
| Stencil Quote Request | Address, paint needed + gallons, project description, **file attachment** |

Each posts JSON to `POST /webhook/lead` — the only route outside the auth middleware.
`WebhookController` branches on `form_type`, creates the lead, assigns a rep, and emails
a notification.

**Gotchas**
- `website/config.php` must use `http://os.usscos.com/webhook/lead` — cPanel blocks
  HTTPS loopback calls between folders on the same server.
- `leads.company_name` is `NOT NULL`; the contact form has no company field, so it falls
  back to the person's name.
- Stencil uploads are stored under the website's own `uploads/stencil/`.

## Lead routing

**Admin → Lead Routing.** Per form type, configure who is notified and who the lead is
assigned to. Paint leads use a **round-robin pool**: pick the reps, and each new paint
lead goes to the next one in rotation.

Stored in `settings` as `lead_routing_*` keys, including `paint_pool` (comma-separated
user IDs) and `paint_next_index`, which advances on each assignment.

## CRM

**Leads** — statuses `new → contacted → qualified → converted` (plus `dead`), with
source, rep, and notes. Converting creates a customer.

**Opportunities** — pipeline board plus a detail page per deal showing a stage progress
bar, linked quotes, and a task widget. Stage can be moved from the detail page; closing
as lost captures a reason.

The opportunity's value **auto-syncs to the highest active linked quote** when the detail
page loads, so it can't drift from reality.

**Tasks** — title, description, status, priority, due date, assignee. Nullable foreign
keys to customer, lead, opportunity, quote, and sales order mean a task can hang off
anything. Creating one from a record redirects back to that record.

## Quoting

Build with live product pricing and multi-tier price lookup, then email a branded quote.
Quotes attach to either a customer or a lead (`customer_id` is nullable).

Status changes cascade outward:

| Quote becomes | Lead moves to | Opportunity moves to |
|---|---|---|
| `sent` | `contacted` | `proposal` |
| `accepted` | `qualified` | `closed_won` |
| `declined` | — | `closed_lost` |

Accepted and converted quotes can't be edited.

## Sales orders

Created from an accepted quote, carrying line items, addresses, terms, and rep.
Statuses: `draft → confirmed → processing → partially_shipped → shipped → invoiced`
(plus `paid` and `canceled`).

Payment can be collected against an order before shipping. Packing slips and print views
are available.

The list **defaults to the "Open" filter**, which hides `invoiced` and `canceled` — so
completed work drops off the working list automatically.

## Ship & Invoice

The single action that closes an order:

1. Enter ship date, tracking number, and carrier.
2. The invoice is built server-side from the order — line items, totals, addresses.
3. The due date is calculated from the customer's payment terms (`days_due`).
4. Any payment already collected is applied automatically.
5. The sales order flips to `invoiced` and leaves the open list.

Implemented in `InvoiceService::shipAndInvoice()`. Note it maps `qty_ordered` → `qty`
and `taxable` → `is_taxable` when copying lines, because the two tables disagree on
column names.

## Invoices

Full line items, AR fields, aging, and payment application. The emailed document adapts
to the balance:

| Balance | Emails as | Shows |
|---|---|---|
| Zero | **Receipt** | "Paid in Full — Thank You", no due date |
| Outstanding | **Invoice** | Due date, balance due |

Both include a shipped/tracking banner when a tracking number is present.

## Payments

Recorded against a customer and applied to specific invoices, updating `amount_paid` and
`balance_due`. Prepayments taken on a sales order carry through to the invoice.

## Products

The deepest module in the system — roughly 100 fields per product.

| Section | Contents |
|---|---|
| Identity | SKU, name, brand, product line, color, category, sport tags |
| Descriptions | Short (sales), long (website), purchase, EDI, GS1, Amazon title + bullets |
| Pricing | Retail, distributor, distributor 2026, stocking distributor, website, Amazon, cost, computed margin |
| Accounting | Income / COGS / asset accounts, tax agency, taxable |
| Inventory | On hand, on sales order, on PO, reorder point, min order qty, lead time |
| Vendor & barcodes | Preferred vendor, vendor part #, MPN, GTIN-12, GTIN-14, ASIN, GS1 status |
| Dimensions | Shipping unit **and** individual unit dimensions and weights |
| Packaging | Units per case, case dimensions and weights, pallet TI/HI, pallet quantity |
| Compliance | Country of origin, HTS code, hazmat flag/class, UN number |
| Specs | Paint type, VOC, flash point, propellant, odor, dry time, field ready, coverage, dilution, surface use, application, recommended use, clean up, shelf life, storage temp range, warranty |

**Images** — gallery with upload, set-primary, and delete. The first upload becomes
primary automatically; deleting the primary promotes the next.

**Documents** — SDS, TDS, sales flyer, catalog, color chart, spec sheet, certificate.
Each has an `is_public` flag controlling website and customer-portal visibility.

Files are stored under `public/uploads/products/{id}/` with server-generated filenames.

> Documents are currently per-product, which means a shared catalog or color chart has
> to be uploaded per SKU. A shared library with per-product links is the intended
> improvement and is an additive change.

## Purchasing & inventory

Vendors, purchase orders with line items, raw materials (products with
`item_type='raw_material'`, managed on their own screens), and `inventory_transactions`
for stock movement. Inventory is tracked but not yet automatically deducted on invoice —
that arrives with the scanner work.

## Documents

A general document store separate from product documents, with an audience field
controlling who can see each file.

## Admin

Users and roles, departments, company defaults (tax rate, payment terms, ship via),
lookup tables, and lead routing.

## Dashboard

Role-aware landing page with KPI cards and Chart.js graphs.
