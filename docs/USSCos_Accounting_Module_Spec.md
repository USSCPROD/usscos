# USSCos Accounting Module — Technical Specification

**For:** USSCos (formerly BusinessOS) — internal application for USSC, a paint manufacturer
**Prepared:** August 2026
**Purpose:** Hand-off spec for engineering to implement an accounting/bookkeeping section covering sales orders/invoices, online orders, credit card payments, checks, and purchase orders/bills.

Assumptions locked in during setup (stated here so engineering and finance are working from the same page):

- **Accounting method:** Accrual basis. Revenue is recognized at invoice/shipment, not at cash receipt. This matters for a manufacturer because it's the only way COGS matches against the revenue that caused it.
- **Entity type:** C-corp. Equity section uses common stock / APIC / retained earnings, not owner's draw.
- **Inventory:** Full manufacturing inventory and COGS tracking is in scope — raw materials → work-in-process → finished goods, with COGS computed from actual production, not just a single "inventory value" pass-through.
- **Sales tax:** Multi-state. USSC sells online direct-to-customer across states, so the module needs jurisdiction-aware tax tracking, not a single flat rate.

---

## 1. Chart of Accounts

Account numbers are placeholders — keep the ranges but feel free to renumber to match any existing COA already in USSCos.

### Assets (1000–1499)

| # | Account Name | Type | Purpose |
|---|--------------|------|---------|
| 1000 | Operating Checking | Bank | Primary operating account |
| 1010 | Reserve / Savings | Bank | Tax reserve, rainy-day fund |
| 1090 | Undeposited Funds | Current Asset | Checks/cash received but not yet deposited to the bank |
| 1100 | Accounts Receivable | Current Asset | Outstanding customer invoices |
| 1150 | Allowance for Doubtful Accounts | Contra-Asset | Reserve against uncollectible AR |
| 1200 | Raw Materials Inventory | Current Asset | Resins, pigments, solvents, packaging on hand |
| 1210 | Work-in-Process (WIP) Inventory | Current Asset | Paint currently in production/mixing/batching |
| 1220 | Finished Goods Inventory | Current Asset | Completed, ready-to-ship paint products |
| 1300 | Prepaid Expenses | Current Asset | Insurance, annual software licenses paid upfront |
| 1400 | Manufacturing Equipment | Fixed Asset | Mixers, filling lines, lab equipment |
| 1410 | Vehicles | Fixed Asset | Delivery trucks |
| 1420 | Office/Warehouse Equipment | Fixed Asset | Forklifts, computers, furniture |
| 1450 | Accumulated Depreciation | Contra-Asset | Offsets fixed asset accounts |
| 1500 | Payment Processor Clearing | Current Asset | In-transit funds held by Stripe/PayPal/card processor before bank deposit |

### Liabilities (2000–2499)

| # | Account Name | Type | Purpose |
|---|--------------|------|---------|
| 2000 | Accounts Payable | Current Liability | Bills owed to vendors/suppliers |
| 2100 | Business Credit Card | Current Liability | Company card balance |
| 2200 | Sales Tax Payable | Current Liability | Tax collected, owed to states — see sub-ledger in §3.6, not per-state GL accounts |
| 2300 | Payroll Liabilities | Current Liability | Net pay owed, employee withholdings |
| 2310 | Payroll Taxes Payable | Current Liability | Employer + withheld payroll taxes owed |
| 2400 | Accrued Expenses | Current Liability | Incurred, not yet billed (utilities, accrued payroll) |
| 2500 | Customer Deposits / Unearned Revenue | Current Liability | Online orders paid before shipment (if not shipped same-day) |
| 2600 | Notes Payable | Long-Term Liability | Equipment loans, term debt |

### Equity — C-corp (3000–3499)

| # | Account Name | Type | Purpose |
|---|--------------|------|---------|
| 3000 | Common Stock | Equity | Par value of issued shares |
| 3100 | Additional Paid-In Capital | Equity | Amount paid above par value |
| 3200 | Retained Earnings | Equity | Accumulated profits/losses |
| 3300 | Dividends Declared | Equity | Distributions to shareholders (not an expense) |

### Revenue (4000–4499)

| # | Account Name | Type | Purpose |
|---|--------------|------|---------|
| 4000 | Paint Sales — Wholesale/Distributor | Income | Revenue from sales-order/invoice customers |
| 4100 | Paint Sales — Online/Direct | Income | Revenue from the online store |
| 4200 | Sales Returns & Allowances | Contra-Revenue | Returned/damaged goods, price adjustments |
| 4300 | Sales Discounts | Contra-Revenue | Early-pay or volume discounts taken |
| 4900 | Other Income | Income | Interest, misc. non-operating income |

### Cost of Goods Sold (5000–5599)

Recognized when finished goods ship (matched to the revenue in §4000/4100), not when raw materials are purchased.

| # | Account Name | Type | Purpose |
|---|--------------|------|---------|
| 5000 | COGS — Raw Materials | COGS | Material cost component of units shipped |
| 5100 | COGS — Direct Labor | COGS | Production labor cost component of units shipped |
| 5200 | COGS — Manufacturing Overhead | COGS | Applied overhead (utilities, equipment depreciation on production lines, etc.) |
| 5300 | Freight In | COGS | Inbound shipping on raw material purchases |
| 5500 | Inventory Shrinkage / Write-offs | COGS | Spoilage, damaged batches, obsolete inventory |

### Operating Expenses (6000–6999)

| # | Account Name | Type | Purpose |
|---|--------------|------|---------|
| 6000 | Advertising & Marketing | Expense | Ad spend, marketing tools |
| 6100 | Software & Subscriptions | Expense | SaaS tools, including USSCos hosting/infra if applicable |
| 6150 | Merchant/Payment Processing Fees | Expense | Stripe/PayPal/card-processor fees on online orders and card payments |
| 6200 | Office Supplies | Expense | General supplies |
| 6300 | Professional Services | Expense | Legal, accounting, audit |
| 6400 | Insurance | Expense | General liability, product liability (important for a paint manufacturer) |
| 6500 | Salaries & Wages — Admin/Sales | Expense | Non-production payroll |
| 6505 | Direct Labor — Production | Expense | Production-floor payroll, before any portion is capitalized into WIP (see §4.8) |
| 6510 | Payroll Taxes — Employer | Expense | Employer-side payroll tax |
| 6600 | Rent — Facility/Warehouse | Expense | Warehouse/plant lease |
| 6700 | Utilities | Expense | Electric, gas, water (non-production-allocated portion) |
| 6800 | Repairs & Maintenance | Expense | Non-capitalized equipment upkeep |
| 6900 | Bank Fees | Expense | Monthly account fees, wire fees |
| 6950 | Depreciation Expense | Expense | Non-production asset depreciation |
| 6960 | Bad Debt Expense | Expense | Written-off receivables |

---

## 2. Why manufacturing changes the standard template

A generic small-business chart of accounts (single "Inventory" line, one revenue account, owner's draw) doesn't hold up for USSC. Two things are specific to being a paint manufacturer:

1. **Three-stage inventory, not one.** Raw materials, WIP, and finished goods need to be tracked separately so the business can see how much cash is tied up at each stage and can generate accurate COGS per batch/SKU. Collapsing these into one "Inventory" account will look fine on a balance sheet and be useless for production/pricing decisions.
2. **Product liability exposure.** Insurance (6400) deserves its own clean line — paint manufacturing carries more product-liability risk than a typical small business, and that expense tends to grow with revenue in a way worth tracking distinctly.

---

## 3. Data Model / Database Schema

This section is written as entity definitions engineering can translate directly into tables/models. Field lists are the essentials — add audit columns (`created_at`, `updated_at`, `created_by`) and soft-delete flags per your app's existing conventions.

### 3.1 Core ledger

**`accounts`** (the chart of accounts itself)
- `id`, `account_number`, `name`, `account_type` (asset/liability/equity/revenue/cogs/expense), `subtype`, `parent_account_id` (nullable, for sub-accounts), `is_active`, `normal_balance` (debit/credit)

**`journal_entries`**
- `id`, `entry_date`, `source_type` (invoice/online_order/payment/bill/bill_payment/production_run/manual), `source_id`, `memo`, `posted_at`, `posted_by`, `is_reversal_of` (nullable, self-reference)

**`journal_entry_lines`**
- `id`, `journal_entry_id`, `account_id`, `debit_amount`, `credit_amount`, `memo`
- **Invariant:** for a given `journal_entry_id`, `SUM(debit_amount) = SUM(credit_amount)`. Enforce this at the service layer before allowing a post (see §6).

### 3.2 Sales side

**`customers`**
- `id`, `name`, `customer_type` (distributor/retailer/direct), `billing_address`, `shipping_address`, `payment_terms`, `tax_exempt` (bool), `resale_certificate_number` (nullable), `default_tax_jurisdiction_id`

**`sales_orders`**
- `id`, `customer_id`, `order_date`, `customer_po_number`, `status` (open/fulfilled/cancelled), `ship_date`, `subtotal`, `tax_amount`, `total_amount`

**`sales_order_line_items`**
- `id`, `sales_order_id`, `inventory_item_id`, `quantity`, `unit_price`, `line_total`

**`invoices`**
- `id`, `sales_order_id` (nullable — some invoices may not originate from a formal SO), `customer_id`, `invoice_number`, `invoice_date`, `due_date`, `status` (draft/sent/partially_paid/paid/overdue/void), `subtotal`, `tax_amount`, `total_amount`, `amount_paid`, `balance_due`

**`invoice_line_items`**
- `id`, `invoice_id`, `inventory_item_id`, `quantity`, `unit_price`, `line_total`, `tax_jurisdiction_id`

**`online_orders`**
- `id`, `order_number`, `channel` (website/marketplace), `customer_id` (nullable for guest checkout), `order_date`, `status` (paid/shipped/refunded/cancelled), `subtotal`, `tax_amount`, `shipping_amount`, `total_amount`, `payment_processor` (e.g. Stripe), `processor_transaction_id`, `processor_fee_amount`, `tax_jurisdiction_id`

**`online_order_line_items`**
- `id`, `online_order_id`, `inventory_item_id`, `quantity`, `unit_price`, `line_total`

### 3.3 Payments received (credit card, check, ACH, cash)

**`payments`**
- `id`, `customer_id`, `payment_date`, `payment_method` (credit_card/check/ach/cash), `amount`, `reference_number` (check number or processor transaction ID), `status` (received/deposited/bounced), `deposited_to_account_id` (nullable until deposited), `undeposited` (bool)

**`payment_applications`** (supports partial payments and paying multiple invoices with one check)
- `id`, `payment_id`, `invoice_id`, `amount_applied`

### 3.4 Purchasing (POs and bills)

**`vendors`**
- `id`, `name`, `contact_info`, `payment_terms`, `default_expense_account_id`

**`purchase_orders`**
- `id`, `vendor_id`, `po_number`, `order_date`, `expected_date`, `status` (open/received/closed/cancelled), `total_amount`

**`purchase_order_line_items`**
- `id`, `purchase_order_id`, `inventory_item_id` (nullable — null for non-inventory purchases like a service), `description`, `quantity`, `unit_cost`, `line_total`, `gl_account_id` (used when the line isn't inventory, e.g. a supply purchase hitting an expense account directly)

**`bills`**
- `id`, `vendor_id`, `purchase_order_id` (nullable — bills can exist without a formal PO), `bill_number`, `bill_date`, `due_date`, `status` (open/partially_paid/paid/overdue), `subtotal`, `tax_amount`, `total_amount`, `amount_paid`, `balance_due`

**`bill_line_items`**
- `id`, `bill_id`, `purchase_order_line_item_id` (nullable), `description`, `amount`, `gl_account_id`

**`bill_payments`**
- `id`, `bill_id`, `vendor_id`, `payment_date`, `payment_method` (check/credit_card/ach), `amount`, `reference_number` (check number), `paid_from_account_id`

### 3.5 Inventory & manufacturing

**`inventory_items`**
- `id`, `sku`, `name`, `item_type` (raw_material/wip/finished_good), `unit_of_measure`, `standard_cost`, `quantity_on_hand`, `reorder_point`

**`bill_of_materials`**
- `id`, `finished_good_item_id`, `component_item_id`, `quantity_required` — defines the recipe (e.g., how many gallons of resin + pigment + solvent make one batch of a given paint SKU)

**`production_runs`**
- `id`, `finished_good_item_id`, `batch_number`, `quantity_produced`, `run_date`, `status` (in_progress/complete)

**`production_run_consumption`**
- `id`, `production_run_id`, `raw_material_item_id`, `quantity_consumed`, `cost_amount`

**`inventory_transactions`** (the audit trail for every quantity/value movement)
- `id`, `inventory_item_id`, `transaction_type` (purchase_receipt/production_consumption/production_output/sale_shipment/adjustment/shrinkage), `quantity`, `unit_cost`, `total_cost`, `reference_type`, `reference_id`, `transaction_date`

### 3.6 Multi-state sales tax

Don't create a GL account per state — it doesn't scale and clutters the COA. Keep `2200 Sales Tax Payable` as a single GL account and track jurisdiction detail in a sub-ledger:

**`tax_jurisdictions`**
- `id`, `state`, `county` (nullable), `city` (nullable), `tax_rate`, `effective_date`

**`sales_tax_collected`**
- `id`, `source_type` (invoice/online_order), `source_id`, `tax_jurisdiction_id`, `taxable_amount`, `tax_amount`, `filing_period`, `remitted` (bool), `remitted_date`

**Recommendation:** don't build your own nexus/rate-determination logic in-house. Multi-state sales tax rules (economic nexus thresholds, product taxability, rate changes) change constantly and get it wrong easily. Integrate a tax calculation API (Avalara or TaxJar are the standard choices) to compute `tax_amount` at checkout/invoice time and to help with filing. The schema above is designed to store whatever that API returns per line item.

---

## 4. Transaction Workflows & Posting Logic

Every event below produces one balanced `journal_entry` (debits = credits). Account numbers reference §1.

### 4.1 Sales Order → Invoice (wholesale/distributor channel)

At invoice issue (accrual — revenue recognized here, not at payment):

```
Dr  1100 Accounts Receivable          [total]
    Cr  4000 Paint Sales - Wholesale      [subtotal]
    Cr  2200 Sales Tax Payable            [tax]
```

At the same time, if the order ships concurrently (COGS matched to the sale):

```
Dr  5000 COGS - Raw Materials          [material cost portion of units shipped]
Dr  5100 COGS - Direct Labor           [labor cost portion]
Dr  5200 COGS - Manufacturing Overhead [overhead cost portion]
    Cr  1220 Finished Goods Inventory      [total unit cost of units shipped]
```

If invoice and shipment happen on different dates, post the COGS entry at ship date, not invoice date.

### 4.2 Online Order

At order/payment time:

```
Dr  1500 Payment Processor Clearing    [gross amount]
    Cr  4100 Paint Sales - Online/Direct   [subtotal]
    Cr  2200 Sales Tax Payable             [tax]
Dr  6150 Merchant Processing Fees      [fee amount]
    Cr  1500 Payment Processor Clearing    [fee amount]
```

When the processor batches and deposits net funds to the bank (typically 1-2 days later):

```
Dr  1000 Operating Checking            [net deposit]
    Cr  1500 Payment Processor Clearing    [net deposit]
```

COGS entry at shipment — same structure as §4.1, hitting Finished Goods Inventory.

**Note on timing:** if online orders don't ship same-day, consider recording the initial sale to `2500 Customer Deposits / Unearned Revenue` instead of `4100 Revenue`, then reclassing to revenue at actual ship date. This keeps revenue recognition strictly tied to fulfillment. If orders typically ship within a day, the simpler version above is fine — don't over-engineer this if the lag is immaterial.

### 4.3 Credit Card Payment Received (against a wholesale invoice)

```
Dr  1500 Payment Processor Clearing    [gross payment]
    Cr  1100 Accounts Receivable           [gross payment]
Dr  6150 Merchant Processing Fees      [fee]
    Cr  1500 Payment Processor Clearing    [fee]
Dr  1000 Operating Checking            [net]
    Cr  1500 Payment Processor Clearing    [net]
```

### 4.4 Check Payment Received (against a wholesale invoice)

At receipt (before deposit):

```
Dr  1090 Undeposited Funds             [amount]
    Cr  1100 Accounts Receivable           [amount]
```

At bank deposit (may batch multiple checks into one deposit):

```
Dr  1000 Operating Checking            [deposit total]
    Cr  1090 Undeposited Funds             [deposit total]
```

### 4.5 Purchase Order → Bill Received

PO creation itself is a commitment, not a liability — no journal entry until goods/services are received and billed.

At bill receipt (inventory purchase):

```
Dr  1200 Raw Materials Inventory       [amount]
    Cr  2000 Accounts Payable              [amount]
```

At bill receipt (non-inventory expense, e.g., a supply purchase against a PO):

```
Dr  6xxx [relevant expense account]    [amount]
    Cr  2000 Accounts Payable              [amount]
```

### 4.6 Bill Payment — Check

```
Dr  2000 Accounts Payable              [amount]
    Cr  1000 Operating Checking            [amount]
```

### 4.7 Bill Payment — Credit Card

```
Dr  2000 Accounts Payable              [amount]
    Cr  2100 Business Credit Card          [amount]
```

Then, when the credit card statement itself is paid:

```
Dr  2100 Business Credit Card          [statement amount]
    Cr  1000 Operating Checking            [statement amount]
```

### 4.8 Manufacturing Cost Flow (raw materials → WIP → finished goods)

Raw materials issued to a production run:

```
Dr  1210 WIP Inventory                 [material cost consumed]
    Cr  1200 Raw Materials Inventory       [material cost consumed]
```

Direct labor applied to the run (production payroll was already recorded as an expense when payroll ran — `Dr 6505 / Cr 2300 Payroll Liabilities` — this entry reclassifies the portion tied to this batch out of expense and into inventory):

```
Dr  1210 WIP Inventory                 [labor cost applied]
    Cr  6505 Direct Labor - Production     [labor cost applied]
```

Overhead applied (allocated by labor hours, machine hours, or another standard basis — pick one and stay consistent). Actual overhead costs — the production-allocated share of utilities (6700), equipment depreciation (6950), etc. — are already sitting in their normal expense accounts when incurred. This entry capitalizes an estimated share of them into WIP using the standard overhead rate:

```
Dr  1210 WIP Inventory                 [overhead applied, at standard rate]
    Cr  6700 Utilities / 6950 Depreciation Expense (production-allocated share)
```

Production run complete, batch moved to finished goods:

```
Dr  1220 Finished Goods Inventory      [total batch cost: materials + labor + overhead]
    Cr  1210 WIP Inventory                 [total batch cost]
```

COGS recognition happens later, at the point of sale/shipment (§4.1/§4.2), which debits `5000/5100/5200` and credits `1220` — a completely separate step from the entries above. Until shipped, the full batch cost sits capitalized as an asset in Finished Goods; nothing above hits a COGS account.

**Implementation note — this is intentionally a simplified v1.** True absorption costing tracks *applied* overhead/labor against *actual* overhead/labor and posts a period-end variance to close the gap when the standard rate doesn't perfectly predict actual cost. That's real cost-accounting work and is out of scope for a first build. Start with standard costing (a pre-set cost per unit per SKU, reviewed and updated quarterly per §7) and accept that small drift between standard and actual cost will show up as a discrepancy between 6505/6700/6950 balances and what actually got capitalized — reviewed and trued up in the quarterly cost review, not tracked as formal variance accounts. Revisit with a cost accountant if margin analysis by batch becomes business-critical enough to justify the added complexity.

---

## 5. Transaction Categorization Quick Reference

| Transaction Type | Category | Primary Account(s) | Notes |
|---|---|---|---|
| Wholesale invoice issued | Revenue | 4000 | Recognized at invoice/ship date, not payment |
| Online order placed | Revenue | 4100 | Net of processor fee, which posts separately |
| Sales tax collected (any channel) | Liability | 2200 | Tracked by jurisdiction in `sales_tax_collected` sub-ledger |
| Credit card payment received | Asset transfer | 1500 → 1100 → 1000 | Fee hits 6150, not netted against revenue |
| Check received | Asset transfer | 1090 → 1100, then → 1000 at deposit | Never skip the Undeposited Funds step — it's what makes bank rec work |
| Raw material purchase (PO/bill) | Asset | 1200 | Not an expense until consumed and eventually sold |
| Non-inventory purchase (PO/bill) | Expense | Relevant 6xxx | Office supplies, software, services |
| Bill paid by check | Liability reduction | 2000 → 1000 | Not an expense at payment time — expense was recorded at bill receipt |
| Bill paid by credit card | Liability transfer | 2000 → 2100 | |
| Credit card statement paid | Liability reduction | 2100 → 1000 | |
| Raw materials consumed in production | Asset transfer | 1200 → 1210 | |
| Batch completed | Asset transfer | 1210 → 1220 | |
| Finished goods shipped (any channel) | COGS | 1220 → 5000/5100/5200 | Matched to the same period as the revenue |
| Dividend declared | Equity | 3300 | Never categorize as an expense |
| Merchant/processor fees | Expense | 6150 | |

---

## 6. Implementation Notes (for engineering)

- **Double-entry enforcement.** Every write to `journal_entry_lines` should go through a single service-layer function that validates `SUM(debit) == SUM(credit)` for the parent `journal_entry` before allowing a commit. Don't let any code path insert journal lines directly.
- **Immutable ledger.** Once a `journal_entry` is posted, don't allow updates or deletes. Corrections go in as a new reversing entry that references the original (`is_reversal_of`). This is what makes an audit trail actually trustworthy and what an accountant/auditor will expect.
- **One posting service per source event**, not scattered logic: `InvoicePostingService`, `OnlineOrderPostingService`, `PaymentPostingService`, `BillPostingService`, `ProductionRunPostingService`. Each takes a source record and returns a balanced journal entry. This keeps the §4 logic in one place per event type instead of spread across controllers.
- **Idempotency.** Posting services should be safe to call twice without double-posting (e.g., check for an existing journal entry tied to the source record/id before creating a new one). Webhooks from a payment processor in particular can fire more than once for the same event.
- **Tax calculation as an external call.** Don't hardcode state tax rates in the app. Call a tax API (Avalara/TaxJar) at invoice/checkout time, store the returned rate and jurisdiction on the line item, and treat `tax_jurisdictions` as a cache of what the API has told you, not a source of truth you maintain by hand.
- **Standard cost updates.** Whatever process updates `inventory_items.standard_cost` (e.g., a quarterly cost review) should itself post a journal entry for any resulting inventory revaluation, rather than silently changing the number.

---

## 7. Reconciliation Schedule

### Weekly (15–30 min)
- [ ] Categorize/review any transactions the posting services couldn't auto-match
- [ ] Review Undeposited Funds — confirm checks received have been deposited
- [ ] Flag anything unusual (unexpected fee amounts, failed payments, orders stuck unshipped)

### Monthly (by the 10th)
- [ ] Reconcile Operating Checking and Reserve against bank statements
- [ ] Reconcile Business Credit Card against the statement
- [ ] Reconcile Payment Processor Clearing to zero (or to a known in-transit balance)
- [ ] Review Accounts Receivable aging — follow up on anything overdue
- [ ] Review Accounts Payable — confirm bills are scheduled for payment on terms
- [ ] Spot-check inventory: does `quantity_on_hand` per `inventory_items` match a physical count for a sample of SKUs?
- [ ] Run P&L and Balance Sheet, review COGS as a % of revenue for anomalies

### Quarterly
- [ ] File and remit sales tax by jurisdiction (pull from `sales_tax_collected`)
- [ ] Review standard costs against actual production costs — true up if there's meaningful drift
- [ ] Review the chart of accounts — add/merge accounts only if reporting actually needs it
- [ ] Review any 1099 contractor payments against the $600 threshold
- [ ] Estimated corporate tax payment (C-corp)

### Annual
- [ ] Full physical inventory count (raw materials, WIP, finished goods) and reconcile against system quantities
- [ ] Full-year reconciliation across all accounts
- [ ] Prepare 1099s
- [ ] Generate annual P&L and Balance Sheet for the CPA/tax filing
- [ ] Board review of dividend declarations, if any

---

## 8. Anti-Patterns to Avoid

- **Recognizing revenue at payment instead of invoice/shipment.** Breaks accrual matching and makes COGS/margin reporting meaningless.
- **One flat "Inventory" account.** Loses the raw material / WIP / finished goods distinction that a manufacturer actually needs for cost control.
- **Skipping Undeposited Funds for checks.** Makes bank reconciliation painful — deposits on the bank statement won't match individual AR payments.
- **Netting merchant fees against revenue.** Post gross revenue and the fee separately (6150) so you can actually see what payment processing is costing you.
- **Per-state GL accounts for sales tax.** Use one liability account plus the jurisdiction sub-ledger — a GL account per state doesn't scale past a handful of states.
- **Building your own multi-state tax rate/nexus logic.** Use a tax API instead; the rules change too often to maintain in-house.
- **Categorizing dividends as an expense.** They're equity distributions, not operating expenses — same mistake as owner's draw in a smaller entity.

---

## 9. Next Steps

- [ ] Confirm/renumber the chart of accounts against anything already partially built in USSCos
- [ ] Implement schema from §3 (or map to existing tables if some already exist)
- [ ] Build the five posting services described in §6
- [ ] Select and integrate a tax API (Avalara or TaxJar) for multi-state sales tax
- [ ] Decide standard costing methodology and seed `standard_cost` for existing SKUs
- [ ] Wire up the reconciliation checklist (§7) as recurring tasks/reminders inside USSCos, if the app supports task scheduling
