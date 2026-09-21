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

### Answered 2026-09-21

| Question | Answer |
|---|---|
| Does TCC invoice USSC, or is the PO the whole transaction? | **Both** — PO *and* invoice. A full purchase cycle. |
| Shared customer list? | **No. TCC sells only to USSC** — TCC has exactly one customer. |
| Shared products? | **No. TCC holds raw materials, USSC holds finished goods.** |
| Does TCC sell to anyone else? | **No, only USSC.** |
| Can anyone see both entities? | **Yes — upper management.** |
| One QuickBooks file or two? | **Two currently — the intent is to move to one.** |

### What follows from those answers

**The inventory split maps cleanly onto the accounting spec, and it makes USSC's costing
much simpler than the spec assumes.**

```
TCC                                  │ USSC
raw materials → WIP → finished batch │ finished goods → COGS on shipment
(actual batch cost, from Markov)     │ (cost = what TCC invoiced, plus freight)
                                     │
        └────── intercompany PO + invoice ──────┘
```

The spec's hard part — three-stage inventory with actual costing, WIP capitalisation and
overhead allocation — lives **entirely inside TCC**. USSC's cost of a finished good is
simply the intercompany invoice price. So "actual cost, not standard cost" is a TCC
problem, not a USSC one, and it depends on Markov rather than on anything USSCOS invents.

**Two QuickBooks files means two sync pipelines.** `QUICKBOOKS_SYNC.md` and the export
queue currently assume one company, one API key, one queue. Each needs an entity.

**Access needs three states, not two:** TCC-only, USSC-only, and both (upper management).
The existing `AccessScope` pattern is right — enforce in repositories — but the scope
becomes a set of permitted entities rather than a single value.

**TCC's customer list is one row.** Worth knowing before building anything general: TCC
does not need customer management, quoting, or a CRM. Its side of USSCOS is purchasing,
production and one recurring sale.

### A third party in the chain — the canning company

The flow is not just TCC → USSC. For aerosol:

```
TCC makes the paint
  → USSC semi takes totes to a CANNING COMPANY
    → cans are filled
      → USSC collects the finished aerosol later
```

Bulk is simpler: TCC makes it and the semi brings it the mile to USSC. Sometimes a
shipping company collects instead of our own semi.

This matters because the paint spends time in a facility **neither entity owns**, and a
tote of paint does not become an exact number of cases — so quantity changes there as well
as at batch yield. Who owns the stock while it sits at the canner is the question that
decides whose inventory it is. Captured in
[OPERATIONS_DISCOVERY.md](OPERATIONS_DISCOVERY.md) for the operations manager.

### Still open

- **Transfer pricing** — at what price does TCC invoice USSC? Cost-plus, or a set
  schedule? This has tax consequences and is a question for the accountant, not a build
  decision.
- **Consolidated reporting** — if upper management wants combined figures, intercompany
  sales and purchases have to be **eliminated** or the combined revenue double-counts.
  Does anyone need a consolidated view, or only each entity separately?
- **Markov** — what it is, whether it exports, integrate vs rebuild.
- **Freight** — is inbound freight from TCC to USSC part of USSC's cost of goods?
- **One QuickBooks file for two legal entities** — the stated intent. Possible, but the
  two companies file separately (TCC yearly, USSC monthly), so the file needs to keep them
  cleanly apart — usually via classes. Worth the accountant's view before it is done,
  since getting it wrong is painful to unpick. Good news for USSCOS: it would mean **one**
  sync pipeline rather than two.

## Related

- [ACCOUNTANT_QUESTIONS.md](ACCOUNTANT_QUESTIONS.md) — ledger of record, inventory valuation
- [BOOKKEEPER_QUESTIONS.md](BOOKKEEPER_QUESTIONS.md) — day-to-day practice
- [USSCos_Accounting_Module_Spec.md](USSCos_Accounting_Module_Spec.md) — assumes a single
  entity throughout, so its chart of accounts and posting logic need revisiting against this
