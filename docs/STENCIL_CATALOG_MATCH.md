# Stencil catalog match — shop vs USSCOS

Run 19 November 2026. Read-only on both sides; nothing in USSCOS was changed.

**Rule from Chip, 19 Nov 2026:** the stencil shop's catalog (stencilsplus.com and
stencils.usscproducts.com) is the most current and is the **default**. Where it and
USSCOS disagree, the shop wins.

## Sources

| Side | Where it came from | Count |
|---|---|---|
| Shop | `https://www.stencilsplus.com/catalog.json` and `https://stencils.usscproducts.com/catalog.json` — what the live pages load | 1,246 SKUs in 241 products |
| USSCOS | `products` in category *Stencils* (or a child of it), or named like `%stencil%`, on production | 1,224 SKUs |

The two shops are **identical**: same SKUs, prices and weights. The USSC shop adds only
Rooftop and Yard & Lawn, which are call-for-quote with no SKU. Endzone, Midfield and
Custom Field are priced live by the designers and have no SKU either.

## Result

| | SKUs |
|---|---|
| In both | **1,214** |
| Same retail price (`website_price`) | 1,212 |
| Same weight (`net_weight`) | 1,212 |
| Shop only — USSCOS needs them | **32** |
| USSCOS only — not sold on the shop | **10** |
| Names in USSCOS with a `website_name` | 0 |

SKUs were matched case-insensitively (`ST-STOPBAR-18x8-8` is written with a lower-case x
on both sides).

### The one price disagreement — `STBS-48` (BUS, 48" letters)

| SKU | Shop price / weight | USSCOS price / weight |
|---|---|---|
| STBS-48-8 | 164.34 / 16.55 lb | 207.21 / 20.87 lb |
| STBS-48-32 | 146.81 / 12.42 lb | 185.11 / 15.65 lb |

USSCOS is exactly 1.261× the shop on both price and weight, so the two were calculated
from different sheet sizes. Shop wins → USSCOS takes the shop's numbers.

### Wholesale (`dist_price`)

The shop computes wholesale as retail × 2 / 2.75. USSCOS's `dist_price` agrees for every
matched SKU but six: `STBS-48-8/-32` (follows from the price above), `STCHR-72X40-8/-32`
and `STN6L-8/-32`, where USSCOS is 3% higher. The shop rule would set them to
85.05 / 72.36 and 595.32 / 506.50.

### Shop only (32) — to add to USSCOS

- **Retail (20):** McDonald's Curbside 75", Mobile Background 94", Mobile Interior 86";
  Target Number 5–8 (63"); Walmart Pickup with Background 84"; Zaxby's Stop Bar 68" and
  71.5". Each in 1/8 and 3/32.
- **Sports (6):** Football Block Letters 10-mil, 15'/16'/18'/20'/25' (`STBL-*`), and the
  aluminum Hashmark Stencil `AF-USS011ASSY` (1,241.39 — aluminum is call-to-order on the
  shop).
- **Ribbons (4):** Awareness Ribbons 10-mil, `STEN-BC6/10/12/20`.
- **Kits (2):** Number Kit 0–9, 14" (`STNUM-14-8/-32`).

### USSCOS only (10) — decide, don't delete

| SKUs | What | Likely reading |
|---|---|---|
| `ST-BBALL-HS-8/-32`, `ST-BBALL-NCAA-8/-32` | Basketball court with 3-point line, ~$1,300–1,470 | Not on the shop at all — dropped, or never listed? |
| `ST-STOPBAR-18x8-32`, `-24x6-32`, `-24x8-32`, `ST-ST-LINE-108-32` | 3/32" versions of stop bars and the straight line | Shop sells these in 1/8" only |
| `STF-C04-8`, `STNP-C04-8` | Old curb SKUs for FIRE and NO PARKING | Superseded by `STF-04-8-C` / `STNP-04-8-C`, which both sides have. No price; FIRE's weight is 0.07 lb. Deactivate |

Per CLAUDE.md: deactivate rather than delete — products are referenced from seven tables.

## Decisions, 19 Nov 2026 (Chip)

- **Categories:** the shop's categories are "the new updated and approved listings" and
  replace USSCOS's stencil categories. Eleven, in shop order: Word Stencils, Symbols &
  Arrows, Letter & Number Kits, Retail Chain Stencils, Sports & Athletics (with Endzone,
  Midfield Logo and Custom Field under it), Ribbon Stencils, Rooftop, Yard & Lawn. The
  old children of Stencils are deactivated and hidden, not deleted.
- **Ribbons** go under **Ribbon Stencils**, including the older `STEN-BC1`–`3` that sat
  under Retail stores. (The shop calls the section Awareness Ribbons.)
- **Aluminum hashmark** goes in with no weight. Its cost is left empty too: the 2.75
  ratio is the plastic sheet's.

Applied by [database/sync_stencils_from_shop.php](../database/sync_stencils_from_shop.php).

## What the shop has that USSCOS does not

Not SKU data, but the larger gap: the shop has a name, description and photos for every
product, and USSCOS has none (0 of 1,224 stencils have a `website_name`). The shop's
`variants` structure also maps directly onto USSCOS's `variant_group` / `variant_axis`.
