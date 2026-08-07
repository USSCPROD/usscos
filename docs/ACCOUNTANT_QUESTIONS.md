# Questions for the Accountant

Working agenda for the conversation before the QuickBooks/accounting phase begins.
Every item here is a decision that changes what gets built — none is a detail we can
sensibly guess at.

Add to this as more surface. Answers should be recorded inline so the file becomes the
record of what was agreed.

---

## 1. The question everything else depends on

**Does USSCOS become the book of record, or does QuickBooks stay the ledger?**

This one drives every other answer, so it's worth settling first.

| | If QuickBooks stays | If USSCOS takes over |
|---|---|---|
| USSCOS builds | A daily export | Journal entry posting, opening balances, period close |
| Month-end runs in | QuickBooks | USSCOS |
| CPA works from | QuickBooks | USSCOS |
| Effort | Weeks | Months, and needs their close involvement |

Current assumption is **QuickBooks stays**, which is why the export API exists and the
posting services don't. Worth explicitly confirming rather than inheriting.

Follow-ups if QuickBooks stays:
- Is there any appetite to leave QuickBooks eventually? If so, roughly when — it changes
  whether we build toward it now or not at all.
- What does QuickBooks do today that they'd be unwilling to lose?

**Answer:**

### 1a. Who owns inventory valuation?

This is the question that actually forces the answer above, and it needs settling
**before the inventory module is built** rather than after.

Stock *quantity* is operational and clearly belongs in USSCOS — it's where orders ship
from. Stock *value* is a balance-sheet figure that drives COGS, so whoever owns it is
doing accounting. The moment USSCOS deducts stock on invoice it becomes the system of
record for quantity, and value normally follows quantity.

- **Who owns inventory valuation — USSCOS or QuickBooks?**
- **On what method — FIFO, weighted average, or standard cost?** (See §8, which currently
  assumes simplified standard costing.)
- **Does inventory post to the GL from USSCOS, or does QuickBooks keep valuing stock from
  its own counts?**

If both systems value stock independently, they will diverge, and the divergence lands on
the balance sheet. The workable answers are one of:

| | Quantity | Valuation | Consequence |
|---|---|---|---|
| **A** | USSCOS | QuickBooks | USSCOS sends quantity movements, QB values them. Simplest. |
| **B** | USSCOS | USSCOS | USSCOS posts COGS and inventory journals. Makes it an ERP. |
| **C** | Both | QuickBooks | Two counts, guaranteed drift. Not viable — listed to rule out. |

Current assumption is **A**, matching "QuickBooks stays the ledger".

**Answer:**

---

## 2. QuickBooks setup

- **Desktop or Online?** The plan assumes **Desktop with QODBC** (the exports we've seen
  are Desktop reports). Confirm, including version and whether it's hosted.
- Who has admin access, and who would own the sync running each day?
- Is the company file on a machine that's always on? QODBC needs QuickBooks reachable.
- Multi-user mode, or does the file get locked by whoever has it open?

**Answer:**

---

## 3. What should flow to QuickBooks, and how

- **Which records?** Invoices, sales orders, payments — all three, or fewer? Some
  businesses keep sales orders out of QuickBooks entirely since they're not financial.
- **Cadence.** Nightly is planned. Would they rather it were real-time, or a batch they
  review and release?
- **Should anything flow the other way?** Payments entered directly in QuickBooks, for
  instance, would otherwise be invisible in USSCOS.
- **Who reconciles it?** Someone needs to notice when a record fails to sync.

**Answer:**

---

## 4. Payments — three specific gaps

**Payment method names.** USSCOS stores `check`, `credit_card`, `ach`, `cash`, `wire`,
`other`. QuickBooks has its own Payment Method list and QODBC rejects anything not on it.
What are the exact names in their file? ("Check", "Visa", "MasterCard", "ACH"…)

**Deposit account.** A Receive Payment in QuickBooks goes either to Undeposited Funds or
straight to a bank account. The spec recommends **Undeposited Funds** for checks so bank
rec works. Should card and ACH behave differently?

**Processor fees.** USSCOS records a $100 card payment as $100 — the ~$3 fee isn't
captured. The spec posts fees separately to 6150 and warns against netting them against
revenue. Options: capture the fee per transaction in USSCOS, or record fees in QuickBooks
monthly from the processor statement. Which do they prefer?

**Answer:**

---

## 5. Prepayments taken on a sales order

`payments.sales_order_id` exists — money can be collected before an invoice exists. Those
payments have no invoice to apply to.

Should they land in QuickBooks as an unapplied customer payment, or as **Customer Deposits
/ Unearned Revenue** (account 2500 in the spec)? The second is more correct; the first is
simpler.

**Answer:**

---

## 6. Chart of accounts

51 accounts are seeded from the spec (migration 046) — see `/accounting/accounts`.

- Does it match their actual QuickBooks chart? Any accounts to add, merge or drop?
- Their account numbering, or ours? Ours are placeholders.
- All 768 products currently map to just `Sales of Product Income` and
  `Cost of Goods Sold`. Do they want revenue split further — by product line, or the
  spec's wholesale vs online split (4000 / 4100)?

**Answer:**

---

## 7. Sales tax

Multi-state, so this needs a real decision.

- How is nexus handled today, and in which states are they registered?
- The spec recommends **Avalara or TaxJar** rather than maintaining rates in-house. Do
  they have a preference, or an existing subscription?
- Who files and remits, and on what cadence?
- How are resale certificates and tax-exempt customers tracked today? (`customers.tax_exempt`
  and `resale_certificate_number` exist but aren't populated.)

**Answer:**

---

## 8. Inventory and cost accounting

- The spec's §4.8 is deliberately a **simplified standard costing v1** — a standard cost
  per SKU, reviewed quarterly, with drift trued up rather than tracked as formal variance
  accounts. Is that acceptable, or do they want proper absorption costing with variances?
- Who sets and reviews standard costs, and how often?
- **Overhead allocation basis** — labour hours, machine hours, or something else? Pick one
  and stay consistent.
- Is a physical count done annually? The spec's reconciliation schedule assumes so.
- How is WIP valued today, if at all?
- **Ownership of valuation is asked in §1a** — settle that first, because it decides
  whether any of the above is USSCOS's problem or QuickBooks'.

**Answer:**

---

## 9. Revenue recognition on web orders

The spec notes that if online orders don't ship same-day, the sale should sit in
**Customer Deposits (2500)** and reclass to revenue at ship date, rather than being
recognised at checkout.

- What's the typical order-to-ship lag?
- Is it material enough to bother with, or is recognising at checkout fine?

**Answer:**

---

## 10. Reporting

- **Which reports do they actually run monthly?** We can build them from data we hold —
  A/R aging exists already.
- What do they need at year end for the CPA and the tax return?
- Do our A/R aging buckets (current / 1–30 / 31–60 / 61–90 / 90+, measured from the **due
  date**) match their convention? Some firms age from the invoice date instead.
- Any reports they currently build by hand in Excel that could be generated?

**Answer:**

---

## 11. How far back should the real import go?

Everything currently in USSCOS is **partial test data**. The plan is to delete all
invoices, sales orders and related sales records once testing is finished, then import
properly from QuickBooks.

**The accountant decides the scope.** Options:

- **From the beginning of QuickBooks** — full history. Best for long-term trend analysis,
  reorder prediction and per-product margin over time. Largest import, and any historical
  data quality problems come with it.
- **Current fiscal year only** — clean and fast. Loses multi-year comparison and weakens
  the AI features that learn from buying cycles.
- **A middle option** — e.g. three years, enough for year-over-year without the full tail.

Worth knowing before deciding:

- Rep attribution only exists for **2026** so far (9,339 invoices). 2024–25 is $24.6M
  across 25,386 invoices with no rep. If history is imported, a matching **Sales by Rep
  Detail** export is needed for those years or the older data has no attribution.
- Product-level invoice lines are what drive Customer Intelligence, reorder prediction and
  margin reporting. More history means better predictions.
- Whatever the range, it should be **one authoritative export**, not the current mix of
  partial reports.

**Answer:**

---

## 12. Data quality issues we've found

Worth raising, since they may know the cause:

- **Negative inventory** — `ROBOCON-W-2.5` shows **−34 on hand** from the product master.
  Usually means shipments recorded against stock never received.
- **9 invoices with no line items** (of 34,729) — probably import artefacts, but worth
  confirming they're not real.
- **Rep was never exported.** 0 of 34,729 invoices carry a sales rep, and no user has the
  `rep` role, because the field wasn't in the reports that were imported. A
  **Sales by Rep Detail** export has been requested.
- **A partial QuickBooks import.** What's in USSCOS is not the complete history — worth
  agreeing what the authoritative range is.

**Answer:**

---

## 13. Cutover — only if USSCOS becomes the ledger

Ignore this section if QuickBooks stays.

- **Opening balances.** Requires a trial balance at a cutover date: every account's
  opening figure, open AR by invoice, open AP by bill, inventory on hand at cost.
  Typically the hardest part of an accounting migration. Who prepares it?
- **Cutover date.** Start of a fiscal year is cleanest.
- **Parallel run.** The spec suggests 60 days running both. Realistic?
- **Period close.** Who closes a month, and should USSCOS hard-block posting into a closed
  period? (Not built — cheap to add, but only meaningful if we're the ledger.)
- What would their auditor or CPA expect to see before trusting the system?

**Answer:**

---

## Related

- [USSCos_Accounting_Module_Spec.md](USSCos_Accounting_Module_Spec.md) — the full spec
- [QUICKBOOKS_SYNC.md](QUICKBOOKS_SYNC.md) — the export API as built, and the Windows bridge
- [ROADMAP.md](ROADMAP.md#accounting) — where this sits in the plan
