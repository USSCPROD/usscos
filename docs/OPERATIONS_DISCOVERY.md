# What I need from the Operations Manager

The goal is to model the **real** flow from purchase order to customer delivery, including
the physical moves, so USSCOS reflects what actually happens rather than a tidy version of
it. Written 2026-09-21.

Best format: walk it once out loud, start to finish, for **one bulk order and one aerosol
order**. Those are different routes and the aerosol one involves a third party.

> **For the conversation itself, use [OPERATIONS_INTERVIEW.md](OPERATIONS_INTERVIEW.md)** —
> the same ground, in the order the paint moves, written to be read aloud. This document is
> the reference behind it, explaining why each answer is needed.

What is already known, so it does not need re-explaining:

- USSC raises a PO to TCC. TCC produces. TCC invoices USSC.
- The PO is adjusted to the **actual** quantity a batch yields.
- **Bulk:** TCC makes it, our semi collects it (about a mile), it comes to USSC.
- **Aerosol:** TCC makes the paint, our semi takes totes to a **canning company**, and we
  collect the finished aerosol later.
- Sometimes a **shipping company** collects instead of our semi.
- Batch details are recorded in **Markov**.
- TCC holds raw materials, USSC holds finished goods.

---

## The one question behind all of this

**At every point in the chain, who owns the paint, and how much of it is there?**

Those two facts are what an inventory system has to know. Everything below is asking that
question at each step.

---

## Stage 1 — Raising the PO

- Who decides paint is needed, and what triggers it? A low stock level, a customer order,
  a production schedule, or someone's judgement?
- Who raises the PO, and what is on it — product, quantity, price, wanted-by date?
- Does TCC confirm or acknowledge it before producing?
- Can one PO cover several products or batches, or is it one PO per batch?
- **How long from PO to paint being ready?**

## Stage 2 — TCC produces

- What gets recorded in Markov, and by whom?
- **Is a batch or lot number assigned, and does it stay with the product afterwards?**
- How is the finished quantity measured — gallons, totes, weight?
- **How different is actual yield from the ordered quantity, typically?** A few percent, or
  more?
- Does anything get rejected or reworked, and what happens to it?
- Who tells USSC it is ready, and how?

## Stage 3 — Bulk route: TCC → USSC

- Who drives, and is there any paperwork for the move?
- **Is anything signed or counted on arrival at USSC?**
- Who records that it arrived, and where does that get recorded today?
- Does the PO get adjusted at this point, or earlier, or later?
- Does TCC's invoice arrive with the paint or separately?

## Stage 4 — Aerosol route: TCC → canning company → USSC

This is the part I understand least, and it is where the model is most likely to be wrong.

- **Who is the canning company, and where are they?**
- **Who owns the paint while it is at the canner — TCC or USSC?** This decides whose
  inventory it sits in, and it is the single most important answer in this document.
- Is there a **PO to the canning company** for the canning service? Do they invoice us?
- What goes to them — totes of paint only, or do we also supply cans, caps, labels?
- **What comes back, and how is it counted?** Cases, cans, pallets?
- **How much paint is lost in canning?** A tote of paint does not become an exact number of
  cans, so there is a yield figure here, and someone must know roughly what it is.
- How long are they typically holding it?
- Do they ever hold stock for us between runs?
- Is the batch or lot number carried through onto the cans?

## Stage 5 — Arrival at USSC

- Who receives it, and what is checked?
- **Is it counted on arrival, or trusted from the paperwork?**
- Where is it put, and does location need recording? (One warehouse, or bays and racks?)
- What happens when the count is wrong — who is told, and what is corrected?
- Is anything labelled or relabelled at this point?

## Stage 6 — When the shipping company is used instead

- What decides that — distance, volume, the semi being busy?
- Who books it and who pays — USSC or TCC?
- Is the freight cost part of the paint's cost, or a separate expense?
- Does the paperwork differ from our own semi doing it?

## Stage 7 — Out to the customer

Partly built already (shipping queue, pick-and-verify, Ship & Invoice), so this is about
confirming rather than discovering.

- Who picks an order, and against what — a packing slip, a screen, memory?
- Is anything checked before it goes out today?
- Who books the outbound carrier, and when is the tracking number known?
- Is a bill of lading needed for freight, and who produces it?
- **When is stock considered gone — at pick, at load, or at invoice?**

---

## What I specifically need to write down at each step

For every stage above, three things:

| | Why it matters |
|---|---|
| **Who does it** | Decides which role sees which screen |
| **What document is created or signed** | Decides what USSCOS has to produce or store |
| **What quantity is known, and how accurately** | Decides where a count is trusted and where it must be confirmed |

And two things about the whole chain:

- **Where does the quantity change?** Batch yield and canning loss are two places it
  clearly does. Anywhere else?
- **Where does custody change?** TCC → semi → canner → semi → USSC → customer. Each
  handover is a point where stock can be wrong and nobody notices.

---

## Mar-Kov

Mar-Kov turns out to be a full batch manufacturing ERP/MES for process industries, with
paint and coatings as a named market — lot traceability, electronic batch records,
formulation control, batch costing, QC, and an existing QuickBooks integration. See
[INTERCOMPANY.md](INTERCOMPANY.md) for why the recommendation is to **integrate rather
than rebuild**.

The operations manager will know how it is actually used, which matters more than what the
brochure claims.

### How it is used day to day

- **Which parts of Mar-Kov do we actually use, and which are switched off or ignored?**
  Most companies use a fraction of a system like this.
- Who enters batches, and at what point — during the run, or after?
- Is formulation/recipe control used, or are recipes kept elsewhere?
- Is lot or batch numbering used, and does it reach the finished product?
- **Is inventory in Mar-Kov trusted?** (QuickBooks inventory is not — is this different?)
- Does it produce batch costs today, and does anyone look at them?
- Is quality control recorded in it?

### Where it stops

- **Does Mar-Kov cover the canning company step, or does that happen outside it?**
- Does it know about the totes going out and the cases coming back?
- Does it track anything once the paint leaves TCC?
- Are purchase orders raised in Mar-Kov, or elsewhere?

### The boundary question

The proposal is that Mar-Kov keeps TCC — raw materials, WIP, formulation, batch cost — and
USSCOS takes USSC: finished goods, customers, sales, shipping. The intercompany PO and
invoice are the handover.

- **Does that match how people actually think about the split?**
- Is anything today being done in Mar-Kov that ought to be on the USSC side, or the
  reverse?
- What does Mar-Kov do badly, or what do people work around?
- Is there any appetite to replace it, or is it doing its job?

### Practical

- Who administers it, and is there a support contract?
- Is it hosted by Mar-Kov or running on a machine here?
- **Is the QuickBooks integration switched on today?** If so, what does it push, and to
  which company file?
- Who at Mar-Kov do we talk to about an integration?

---

## Also worth asking, since they will know

- What goes wrong most often in this process?
- What does everyone work around, or keep in their head?
- Is there a spreadsheet or whiteboard doing a job the system should do?
- **If one thing in this chain were tracked properly, what would help most?**

---

## Related

- [INTERCOMPANY.md](INTERCOMPANY.md) — the TCC/USSC structure this sits inside
- [BOOKKEEPER_QUESTIONS.md](BOOKKEEPER_QUESTIONS.md) — the financial side of the same flow
- [ROADMAP.md](ROADMAP.md) — shipping and inventory are the next build
