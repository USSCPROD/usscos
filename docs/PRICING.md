# Pricing Model

How a price is decided, for any product, any customer, any quantity.

Decided across several conversations on 2026-08-03. Not all of it is built yet — the
status of each piece is marked.

---

## The one rule

Every price in the system — website, cart, quote, sales order, invoice — resolves through
a **single function**. Nothing computes a price on its own.

```
customer-specific override
        ↓  (if none)
volume break for their tier
        ↓  (if none)
their tier price
        ↓  (if none)
retail
```

One place to change, and every surface follows. A rep quoting 48 cases and a customer
adding 48 to their cart get the same number because they ran the same code.

> **Status:** not built. Should land with the website module, since the public catalog is
> the first thing that needs it.

## Tiers

| Tier | Notes |
|---|---|
| **Retail / Web** | The same number at launch |
| **Distributor** | Below retail |
| **Stocking distributor** | Lowest — they hold stock and mostly buy for themselves |

Historically the website was priced at **retail + 10%**, deliberately, so USSC never
undercut its own distributors. As of the August 2026 price increase, **retail rises to
meet web** and the two become one number. Material costs drove a long-overdue increase.

Consequences:

- A logged-in direct customer and an anonymous visitor see the same price. Accounts exist
  for order history and reorder, not for a discount.
- `products.website_price` is **redundant** — read `price` (retail) everywhere. The column
  stays in case the two diverge again, but should not be populated. Two columns that must
  be kept in sync will drift apart.
- Keep the resolution function regardless. It still does real work for distributors and
  for negotiated pricing, and nothing needs rearchitecting if the tiers separate again.

Columns today: `price` (retail, canonical), `dist_price`, `dist_price_2026`,
`stocking_dist`. `retail_price` is legacy and mirrors `price` on save.

## Retail is the pallet price

Important and easy to get backwards: **retail is the best price** — what you pay buying a
full pallet. Smaller quantities pay a **markup above** it. Volume pricing erodes that
markup rather than discounting below retail.

## Volume pricing

Break quantities differ by product type, so they can't be derived from a single formula:

- DuraStripe aerosol — **108 cases** per pallet, 20-case minimum to ship palletized
- AquaStripe bulk — **24** five-gallon buckets per pallet

Not every product is tiered; many are flat-priced.

### Reusable schedules

Rather than a break table on each of 891 products, define a named **schedule** once and
attach it where it applies.

**`price_schedules`** — a named ladder, e.g. `Aerosol — Cases`, `Bulk — 5 Gallon`

**`price_schedule_breaks`** — the rungs

| Label | Min qty | Adjustment |
|---|---|---|
| Single case | 1 | retail + 10% |
| Quarter pallet | 27 | retail + 6% |
| Half pallet | 54 | retail + 3% |
| Full pallet | 108 | retail |

*(illustrative — real numbers still to be supplied)*

Attach at either level:

- `products.price_schedule_id` — one product
- `categories.price_schedule_id` — everything in the category

Resolution: **product → category → none.** A product with no schedule anywhere is flat at
retail, which covers "not everything is tiered" with no special casing.

So all aerosols share one ladder, all bulk shares another, and an unusual product gets its
own override. Change the aerosol ladder once and every aerosol SKU follows.

Percentages above retail are preferred over fixed prices per break — they survive a price
increase without re-entering the whole catalog.

### Where it shows

- **Admin** — a screen listing schedules, their rungs, and what's attached to each
- **Product page** — a Volume Pricing card showing the ladder
- **Website** — the same ladder as a "buy more, save more" table
- **Cart and quotes** — applied automatically through the resolution function

> **Status:** designed, not built. Blocked on the actual break quantities and percentages
> for aerosol and bulk.

## Open questions

1. **Does the volume ladder survive the price increase?** Today it erodes a 10% markup
   down to retail at a full pallet. If retail rises to equal web, there is no markup left
   to erode and a single case costs the same as a pallet. Either the ladder is rebuilt as
   real discounts below the new retail, or volume pricing goes away on the web. This is a
   pricing decision, not a technical one.
2. **The 20-case shipping minimum** — is ordering 1–19 cases disallowed, or does it just
   ship differently (parcel/LTL rather than palletized)? Hard validation versus a shipping
   method change.
3. **Do distributors get volume breaks too?** They already buy below retail; stacking
   breaks can erode margin quickly. Either breaks apply to retail/web only, or each tier
   gets its own ladder.
4. **Order-total discounts** — "spend $2,500, take 5% off" is a different mechanism from
   per-line breaks and affects order totals rather than line prices. Wanted or not?

## Reorder pricing

When a customer reorders a past order they are charged **today's price**, with the
historical price shown for context:

> You paid $52.95 in March — current price $58.20

Order history always shows what was actually paid and is never recalculated.

With prices rising, the reorder screen will be many customers' first encounter with the
increase. Worth having reps contact significant accounts before they find out on their
own.

## Related

- [ROADMAP.md](ROADMAP.md#website--publishing) — the website and publishing module
- Roadmap item 3, customer-specific pricing — the override layer at the top of the chain
