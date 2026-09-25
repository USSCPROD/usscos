# Questions for the Bookkeeper

A different conversation from [ACCOUNTANT_QUESTIONS.md](ACCOUNTANT_QUESTIONS.md). That one
asks the accountant a policy question — does USSCOS take the general ledger. This one asks
the person who actually keys the work in **what they do every day**, because that shapes
what USSCOS has to produce whichever way the policy lands.

Bring a laptop. Several of these are better answered by watching them do it for ten
minutes than by discussing it.

Write answers inline so this file becomes the record.

---

## 1. Walk me through a normal day

Ask them to narrate it rather than summarize. The goal is to find the steps nobody has
written down.

- What do you open first, and what do you do in it?
- Which parts are QuickBooks, which are spreadsheets, which are email?
- **Where do you type the same thing twice?** Double entry is the clearest sign of what
  USSCOS should take over.
- What takes longest, and what do you most dread?

**Answer:**

---

## 2. Invoices — how one actually comes to exist

> **Answered 2026-09-21 (Chip):** invoices are created in USSCOS once a sales order ships. This matches what is built. Still open: how often a sent invoice gets edited, and how credit memos/returns are handled.

USSCOS currently creates invoices when a sales order ships. We need to know whether that
matches reality.

- Who creates an invoice today, and in what system?
- Does every invoice start from a sales order, or do some appear on their own?
- What has to be on it before it can go out — PO number, terms, ship via, tracking?
- Who sends it to the customer, and how?
- **How often does an invoice get edited after it's sent?** This decides whether USSCOS
  can treat a sent invoice as final.
- What happens with credit memos and returns?

**Answer:**

---

## 3. Payments — the mechanics

> **Answered 2026-09-21 (Chip):** payments are accepted in USSCOS. Still open: Undeposited Funds practice, processor fees, partial/short payments.

**Answered 2026-09-21:**

- Payments post **straight to the bank**, not through Undeposited Funds. The spec
  recommends Undeposited Funds so bank reconciliation matches deposit-for-deposit — worth
  raising with the accountant, but current practice is direct.
- **Processor fees are captured per transaction**, which is the better option and means
  USSCOS should record the fee on the payment rather than reconciling monthly.

**Answer:**

---

## 4. The exports we've asked for

> **Answered 2026-09-21 (Chip):** the exports are coming. Still worth agreeing the exact columns and making it a memorised report so each one comes out the same shape.

USSCOS is missing invoice fields because the original import didn't carry them. These come
from them, so agree the format now.

- **Invoice-level export** with: invoice number, Terms, Ship Via, P.O. Number, and
  Tracking if QuickBooks holds it
- Can this be produced on a repeatable schedule, or is each one a manual job?
- Which QuickBooks report do they use, and can they save it as a memorised report so it
  comes out the same shape each time?

**Answer:**

---

## 5. The SKU renaming

> **Answered 2026-09-21 (Chip):** SKUs are being imported **into QuickBooks first**, so the
> exports will carry the new names.
>
> **This looks like good news for the $6.1M gap.** Renaming an item in QuickBooks Desktop
> updates that item everywhere it is referenced, including on historical transactions — so
> a fresh export should carry new names on old invoices too. Worth confirming with the
> bookkeeper rather than assuming, and *merged* items behave differently from renamed ones.
>
> Confirmed: **all data here gets cleared when the real data comes in.** Backups and a
> tested restore are in place as of 2026-09-18.

- How far along is it, and when will it be finished?
- Will every QuickBooks item name then match the product spreadsheet exactly?
- **What happens to items that no longer exist** — renamed, merged, or left alone?
- Are historical invoices updated to the new names, or do old invoices keep the old ones?
  (This one matters a lot: if history keeps old names, the 348 unmatched item names stay
  unmatched and re-importing does not fix them.)

**Answer:**

---

## 6. Sales tax

> **Answered 2026-09-21 (Chip):** USSCOS needs to handle sales tax. QuickBooks calculates
> it today. **Only two states: Georgia and North Carolina.** Unsure whether the bookkeeper
> or the accountant files — ask. Avalara/TaxJar not in use and not familiar.
>
> **This changes the recommendation.** The spec assumes genuinely multi-state selling and
> tells us to buy a tax API. At two states that is the wrong trade — Avalara and TaxJar
> price for dozens of jurisdictions and constant nexus change. Two states can be a rate
> table we maintain. The catch is that **GA and NC both have county/local rates on top of
> the state rate**, so it is not two numbers. Find out how QuickBooks is set up before
> assuming anything.

**Answered 2026-09-21:**

- **Separate county tax codes**, not one rate per state
- Tax charged on **where the customer is** — destination-based sourcing, which is correct
  for both GA and NC
- **The accountant files**, not the bookkeeper
- **Two filing entities: Technical Coatings files yearly, USSC files monthly** — see the
  multi-entity note below
- Tax-exempt: certificates are filed and no tax is charged

So USSCOS needs county-level rates for GA and NC, destination-based, with an exemption
flag per customer and somewhere to record the certificate. `customers.tax_exempt` and
`resale_certificate_number` already exist and are unpopulated.

---

## 7. Inventory, as it stands today

> **Answered 2026-09-21 (Chip):** inventory will be kept in USSCOS. QuickBooks tracks it
> today but **the numbers are not reliable**; counting is manual and occasional.
>
> **Agreed plan:** do a full physical count at cutover, seed USSCOS with accurate figures,
> and USSCOS maintains them from there. That is the right sequence — starting from known
> numbers rather than importing figures nobody trusts.
>
> Paint production currently goes into **a separate program**, which we would eventually
> want to fold into USSCOS. That program is the WIP/batch piece of the spec (§4.8).
>
> Still open: **valuation**, not quantity — `ACCOUNTANT_QUESTIONS.md` §1a. And whether any
> per-product cost exists today.

Related to `ACCOUNTANT_QUESTIONS.md` §1a, but this is the practical half.

**Answered 2026-09-21:**

- **No cost per product exists today.** Costing is expected to follow **actual cost**, not
  a standard cost. **This contradicts spec §4.8**, which assumes simplified standard
  costing with quarterly true-up. Actual costing is materially more work — it needs a real
  cost captured per production run and a costing method (FIFO or weighted average) agreed
  with the accountant.
- Physical count is done by **Greg** (warehouse) and takes **a couple of days**
- Production is recorded in **Markov**, a separate program

**Answer:**

---

## 8. What reports actually get used

Not what exists — what somebody reads.

- Which reports do you run monthly, and who reads them?
- Which does the owner ask for?
- What does the CPA ask for at year end?
- **Is there a report you'd like that you can't get today?**

**Answer:**

---

## 9. Bills and purchasing

> **Answered 2026-09-21 (Chip):** bills and purchasing will run through USSCOS. Currently 269 vendors, 1 purchase order and 0 bills exist, so this is a build, not a migration.

USSCOS has 269 vendors and one purchase order — so this is clearly happening elsewhere.

**Answered 2026-09-21:** POs **are** raised. **Sharon** enters bills. Paid by **ACH,
weekly**. All of this moves into USSCOS.

**Answer:**

---

## 10. The handover question

- If USSCOS could do **one thing** to save you time, what would it be?
- What would you be nervous about USSCOS taking over?
- Is there anything you do that nobody else knows how to do?

**Answer:**

---

## What we already believe, for them to correct

State these plainly and let them disagree — assumptions are easier to challenge than
questions are to answer.

| We think | Correct? |
|---|---|
| QuickBooks Desktop, synced via QODBC | |
| QuickBooks stays the ledger; USSCOS feeds it | |
| Invoices originate in USSCOS when an order ships | |
| Sales tax is handled in QuickBooks today | |
| Inventory is not reliably tracked anywhere yet | |
| "Processed by" is who keyed the order, not who earns it | |
| Only 2–3 years of history needs importing | |

---

## Related

- [ACCOUNTANT_QUESTIONS.md](ACCOUNTANT_QUESTIONS.md) — the ledger-of-record decision
- [USSCos_Accounting_Module_Spec.md](USSCos_Accounting_Module_Spec.md) — what a full
  in-house ledger would involve, if it comes to that
- [QUICKBOOKS_SYNC.md](QUICKBOOKS_SYNC.md) — the export bridge as designed
