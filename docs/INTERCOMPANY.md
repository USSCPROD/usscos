# Intercompany — TCC and USSC

**Two legal entities**, not one business with two names. This is structural and affects
the data model, access control, purchasing and reporting. Recorded 2026-09-21 after it
became clear only a single roadmap bullet had survived from an earlier discussion.

---

## The two companies

| | Role |
|---|---|
| **TCC** — Technical Coatings | Manufactures the paint. **Pays the bills.** Files sales tax **yearly**. |
| **USSC** — US Specialty Coatings | Sells to customers. **Takes the money.** Files sales tax **monthly**. |

So the money flows in to USSC and the costs sit with TCC, joined by an intercompany
purchase.

## The paint flow

```
USSC needs paint
      │
      ├─ USSC raises a PO to TCC
      │
      ├─ TCC produces the batch          (details entered in Markov today)
      │
      ├─ TCC ships the paint to USSC
      │
      └─ PO is adjusted to the ACTUAL quantity the batch yielded
```

**The PO must stay editable after it is raised.** This is the unusual part and it is
deliberate: a batch does not yield exactly what was ordered, so the PO is corrected to
what actually arrived. Normal purchasing assumes the ordered quantity is the commitment
and the receipt is matched against it. Here the received quantity *is* the truth, and the
PO follows it.

Any purchasing build has to allow this rather than locking a PO on approval.

## Markov

The program TCC uses today to record the details of **every batch**. We want either to
integrate with it or to rebuild that capability inside USSCOS.

This is the same data the accounting spec calls WIP and production runs (§3.5, §4.8), and
it is where actual batch cost would come from — which matters because costing here follows
**actual cost, not standard cost**.

Open: what Markov is exactly, whether it exports, and whether integrating beats replacing.

## Access — users see only their own entity

**TCC users see only TCC information. USSC users see only USSC information.**

This is entity-level scoping and it is broader than the rep/distributor scoping already
built in `App\Services\AccessScope`, which limits a rep to their own customers. That
pattern is the right precedent — enforce in repositories, not views — but the scope key is
the entity rather than the sales rep.

## What this means for the build

USSCOS is currently **single-entity**. There is one row in `companies` ("US Specialty
Coatings"), and only `users` and `activity_log` carry a `company_id` at all. Invoices,
customers, products, sales orders, purchase orders, bills — none are scoped to an entity.

Retrofitting an entity column across the financial tables is far cheaper **before** the
real data import than after. The import is the natural moment to do it, since everything
is being cleared and reloaded anyway.

### Open questions

- Does **TCC** invoice USSC, or is the intercompany PO the whole transaction?
- Do TCC and USSC share the **customer list**, or does TCC only ever sell to USSC?
- Do they share **products**, or does TCC hold raw materials and USSC finished goods?
- Is there a **third** relationship — does TCC ever sell to anyone but USSC?
- Should a user ever see **both** entities (an owner, or the bookkeeper)?
- Do the two entities share a QuickBooks file, or is there one per company?

## Related

- [ACCOUNTANT_QUESTIONS.md](ACCOUNTANT_QUESTIONS.md) — ledger of record, inventory valuation
- [BOOKKEEPER_QUESTIONS.md](BOOKKEEPER_QUESTIONS.md) — day-to-day practice
- [USSCos_Accounting_Module_Spec.md](USSCos_Accounting_Module_Spec.md) — assumes a single
  entity throughout, so its chart of accounts and posting logic need revisiting against this
