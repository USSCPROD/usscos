# What to make next

Recorded 2026-09-23.

## The problem, as described

> "Production goes off historical data and makes paint. Right now they are making some paint
> that we only sell every now and then, but we have orders on backorder for paint that we
> are out of now, and they want to make paint that we don't need."

Production is planning from memory and past patterns because **nothing tells them what is
actually needed**. That is not a discipline problem. It is a missing number.

## The number that answers it

```
Available  =  on hand  +  already on order from TCC  −  committed to customers
```

Anything where that goes negative is a **backorder with someone waiting**. Anything
approaching its reorder point is about to become one. That list, sorted by who has waited
longest, **is the production order**.

Historical demand only enters as a tiebreaker, for items nobody is currently waiting on.
Today it is the primary input, which is the inversion causing the problem.

## Why it cannot be built yet

The three inputs are all empty, checked 2026-09-23:

| Input | Products with a value |
|---|---|
| Committed to open orders (`qty_on_sales_order`) | **0** |
| Already on order from TCC (`qty_on_po`) | **0** |
| Reorder point | **0** |
| On hand | 360 — and not trusted |

The columns exist. Nothing maintains them, because nothing yet records receipts,
shipments or commitments. **This is not a reporting gap — it is the same root cause as the
inventory problem.** Once movements are captured these numbers maintain themselves and the
production list falls out of them.

## What we do have

**43,508 invoice lines linked to products.** That is real demand history per SKU — how
much white actually moves in a month, what is seasonal, what sells twice a year. So "what
do we normally need" is answerable from data rather than recollection, and it is better
data than production is working from now.

## Where this belongs — and where it does not

USSCOS knows **demand**: orders, backorders, sales history. Mar-Kov knows **production**:
batches, formulations, yield. They meet at the intercompany purchase order.

**USSCOS should not tell TCC how to make paint.** It should say *"you need 40 cases of
white and 12 of yellow, and there are customers waiting on the white"* — and that becomes
the PO to TCC, which drives their production.

The PO is already the instruction. It just needs to be generated from demand instead of
guesswork. That fits the structure already in place rather than adding a parallel one.

## What it looks like when built

A **"What to make"** screen listing every product short of demand:

- On hand, committed, on order, and the shortfall
- **Backordered items flagged and sorted first** — customers waiting outrank restocking
- Normal demand rate alongside, from invoice history, for context
- A button that raises the TCC purchase order straight from the list

## Sequence

It cannot come first, and building it early would produce a confident list built on numbers
nobody believes.

1. Receiving and shipment deduction — so stock is real
2. Returns and adjustments — so it stays real
3. Opening physical count
4. Commitments tracked from open sales orders
5. **Then this screen** — comparatively small once the data beneath it is honest

## Related

- [GO_LIVE.md](GO_LIVE.md) §2a — orders that cannot be fulfilled, which is the same
  calculation read from the other end: one direction says which orders are stuck, the other
  says which paint to make
- [INTERCOMPANY.md](INTERCOMPANY.md) — the TCC purchase order this produces
