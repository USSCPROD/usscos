# Product spreadsheet — changes needed before import

Reviewed **`USSC EDI-ERP DATA.xlsx`** on 2026-09-23 (832 rows, 765 with a SKU, 45 columns
across five sheets). This is a good file — real pricing tiers, accounting mappings, vendor
and GS1 data. What follows is what it still needs before it can drive inventory and
scanning in USSCOS.

Four additions and one decision. All are cheap now and expensive once data is live.

---

## 1. Add `Units Per Case` — a number

**Why:** scanning a case has to know what a case contains, or the system cannot convert a
scan into stock. Scan a case of DuraStripe White and we know *which* product but not
whether that is 12 cans or 1.

The information exists in the file, but as **text inside other fields** — `UOM` reads
"Case (12)", the product name reads "Case (12 × 18 oz)". Parsing English out of a name is
guesswork that breaks the first time someone writes "Case of 12" or "12pk".

| SKU | Units Per Case |
|---|---|
| DSW | 12 |
| DSW24 | 24 |
| DSWFC | 12 |
| A 5 Gal Pail sold singly | 1 |

Use **1** where the item is already the base unit — not blank. Blank means "nobody has said
yet", and that distinction is worth keeping.

## 2. Add `Pallet Qty` — a number, plus `Pallet Qty Varies`

**Why:** receiving happens by the pallet. Without a pallet quantity, every receipt is
counted by hand.

Already known from the pack rules:

| | Per pallet |
|---|---|
| Aerosol cases | **108 cases** (1,296 cans) |
| 2-pack cases | **54** (also 1,296 cans) |
| 2.5 gal jugs | **24 boxes** (48 jugs) |
| 1 gallon | **~120 — varies by batch** |

That last row is why a second column is needed. **`Pallet Qty Varies`** — yes/no. For
1-gallon the figure is a starting suggestion the receiver corrects; for aerosol it is
fixed. Without the flag the system presents a wrong number with exactly the same
confidence as a right one, which is how people stop trusting it.

## 3. Add `QuickBooks ListID`

**Why:** so renaming a SKU in QuickBooks never breaks the link again.

Every QuickBooks item carries a `ListID` that **never changes, even when the item is
renamed**. USSCOS currently stores only the item *name*, for all 891 products, with zero
ListIDs.

**You cannot detect a rename when the only key you hold is the thing that changed.** Rename
a SKU in QuickBooks today and USSCOS sees an item it has never heard of — which is exactly
how 348 item names ended up unmatched against $6.1M of invoice history.

This is **cheapest right now and expensive later**, because immediately after the rename
every name still matches. That is the easiest moment this project will ever have to
establish the mapping. Six months of drift later, it is a manual reconciliation.

QODBC exposes `ListID` directly, and most QuickBooks item reports can be configured to
show it.

## 4. Add `Contains` — for pack-level SKUs

**Why:** so a pallet knows it is made of cases.

The **Single Cans-Case-Pallets** sheet models pack levels as **separate SKUs**:

```
DSW1       single can
DSW-CASE   12 shipper case
DSW108     108-case pallet
```

None of those appear in the EDI Master. Separate SKUs are the right call for Amazon and EDI
— each sellable unit needs its own GTIN — but as things stand **nothing connects them**.

So the system would treat a pallet of white and a case of white as unrelated products. Sell
a case and the pallet count does not move. You can be "out of cases" with a full pallet in
the rack. That is the drift you already have, rebuilt.

Two columns fix it:

| Column | Example on DSW108 |
|---|---|
| `Contains SKU` | `DSW` |
| `Contains Qty` | `108` |

Leave both blank for a base item. Then scanning a pallet can decrement cases, and stock is
one number viewed at different resolutions rather than several numbers that disagree.

---

## The decision behind #4

**Is a pallet of white a quantity of cases, or its own stock item?**

- **Quantity of cases** — one pool of stock, counted in cases, with pallet and can as ways
  of moving it. Cleanest for inventory, and the `Contains` columns are what make it work.
- **Its own item** — separate stock per pack level. Fine for selling, but somebody has to
  remember to break a pallet down into cases in the system when it happens physically, and
  that is precisely the sort of step that gets skipped.

**Recommended: quantity of cases**, with `Contains` defining the relationship. But it is a
real business decision about how the warehouse thinks, not just a data-modeling one — so
worth agreeing rather than assuming.

---

## Two notes, no action needed

**Barcode coverage is about half.** GTIN-12 on 296 of 765 (38%), GTIN-14 on 379 (49%). MPN
is 93%. So barcode scanning covers roughly half the catalog and **typing the SKU is the
primary path for the rest**, not a fallback. USSCOS already supports both. Worth setting
expectations before the scanners arrive — and worth prioritising barcodes on the
fastest-moving items rather than trying to fill all 765.

**Do not import `Qty On Hand`.** The column is populated (651 rows) but these are
QuickBooks' figures, which are known to be unreliable. The opening stock position should
come from Greg's physical count, entered once the receiving and adjustment screens exist.
Importing numbers nobody trusts as the starting point defeats the purpose of the count.

---

## Summary for whoever is finishing the file

| Add | Type | Notes |
|---|---|---|
| `Units Per Case` | number | 1 for base items, never blank where known |
| `Pallet Qty` | number | 108 aerosol cases, 54 2-packs, 24 jug boxes |
| `Pallet Qty Varies` | yes/no | Yes for 1-gallon |
| `QuickBooks ListID` | text | From QODBC or a QuickBooks item report |
| `Contains SKU` | text | Pack SKUs only — e.g. DSW108 contains DSW |
| `Contains Qty` | number | Pack SKUs only — e.g. 108 |

And decide whether the pack-level SKUs from the **Single Cans-Case-Pallets** sheet are
joining the main catalog. If they are, they need the full column set like any other item.

---

## Related

- [GO_LIVE.md](GO_LIVE.md) — what else is needed before USSCOS can be used
- [OPERATIONS_DISCOVERY.md](OPERATIONS_DISCOVERY.md) — the physical flow these quantities describe
