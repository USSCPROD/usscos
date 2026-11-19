# Moving the stencil shop into USSCOS

Started 19 November 2026. **Goal, from Chip:** USSCOS shows and sells stencils *just like*
stencils.usscproducts.com does: the same categories, pages, calculators and designers.

**One site.** Chip, 19 Nov 2026: the usscproducts.com WordPress site will be deleted and
the domain pointed at USSCOS. There is no separate stencil site and no stencil subdomain.
Stencils are one section of the same navigation, and nothing on the site links to
another site. The stencil shop is only a data source until cut-over.

The shop is a Python app (`USSCPROD/stencilsplus-website`, local clone
`~/Documents/StencilsPlus Website/site`) running as the `usscstencils` service on
137.184.104.208. It stays live until USSCOS matches it. Then its URLs are 301-redirected
here, one hop.

## Done

| | |
|---|---|
| SKUs, prices, weights | `database/sync_stencils_from_shop.php`: 1,246 shop SKUs in USSCOS, the shop wins |
| Categories | The shop's 11 categories replaced the old children of Stencils |
| Listings | One listing per shop product, with a Size & thickness picker (238 listings) |
| Copy and pictures | `database/import_stencil_content.php`: titles, descriptions, 18 photos, 611 stencil pictures, 637 measurement drawings |

## What the shop has, and where USSCOS stands

| Shop page or tool | Shop route | USSCOS today |
|---|---|---|
| Home: hero, features, category tiles, custom block, FAQ | `/` | General USSCOS home, no stencil block |
| Category tabs, Sports with Endzone, Midfield and Custom Field under it | `/shop/{cat}` | 6 of 11 categories. Rooftop and Yard are hidden. The 3 Sports children are missing |
| Category copy | `categoryCopy` | Description stored, not shown |
| Chain pages, retail by chain | `/brand/{chain}` | None. Retail is one long list |
| Search that understands 96", 96 in and 8 ft | `/search/{q}` | Plain name search |
| Product: a size picker *and* a thickness picker, stencil picture, measurements, price | `/stencils/{slug}/{size}` | One combined picker. Pictures in the gallery |
| Quote page for call-for-pricing items, with artwork upload | `/stencils/{slug}` (`callForPricing`) | None |
| Custom text stencil calculator: type the words, choose size and thickness, get a price | `/custom` | None |
| Endzone lettering designer: live preview, instant price, proof PDF | `/custom/endzone` | None |
| Midfield logo designer: logo upload, colors, instant price, proof PDF | `/custom/midfield` | None |
| Cart | `/cart` | None |
| Checkout: Square payment, live FedEx rates and box packing | `/api/shipping`, Square | Square chosen, not built. FedEx and cartonizer exist on sales orders |
| Accounts: customer and distributor, wholesale prices, saved designs and quotes, orders on terms | `/account` | Staff logins and rep logins only. The distributor portal is not built |
| About (materials and sizing), Contact, Shipping & Returns, Privacy | `/about` and others | `/about`, `/contact`, `/privacy` exist with general copy |
| SEO prerender, sitemap, Google Tag Manager purchase event | server-side | Partial |

The designers sit behind sign-in on the shop (`gateDesigner`), so **accounts come before
the designers**.

## Plan

Each phase ships on its own and is usable when it lands.

1. **Browse like the shop.** ✅ Done 19 Nov 2026. Quote listings link `/custom` and
   `/custom/endzone|midfield` to the quote forms until phases 2 and 5 build the tools.
   - All 11 categories, with Sports' three children.
   - Category copy.
   - Chain pages.
   - The shop's search rules.
   - Size and thickness as two pickers.
   - The stencil picture and measurement drawing in their right places.
   - Quote pages for the 5 call-for-pricing listings. Their artwork upload becomes a
     USSCOS lead, the way the website forms already do.
   - About, Returns and Contact copy.
   - The shop's look for the stencil pages.
2. **Custom text calculator.** ✅ Built 19 Nov 2026 at `/custom`. Prices, sheet sizes,
   pieces and the drawn outlines match the live shop byte for byte (7 designs compared,
   road letters included). Ends in "Request this stencil" until the cart exists. Font
   outlines: `storage/stencil-data/stencil_font.json` and `road_letters.json`, server only,
   made by `database/tools/export_stencil_font.py`. Original plan: Port the sizing and pricing from `drawings.py` and
   `fontsvg.py`. The stencil font stays private on the server. Only outlines leave it,
   exactly as the shop does today.
3. **Cart and checkout.** 🟡 Built 19 Nov 2026; tested end to end in Square's sandbox the
   same day (a $26.63 test order became SO 39, paid, website channel, SH line, card
   payment, new retail customer; reloading the return page made no second order). The
   sandbox webhook subscription "USSCOS website orders" (payment.updated →
   `/webhook/square`) is set up with its signature key in `.env`; Square's test event got
   200, unsigned and wrongly signed requests get 401. A website order shipping to a ZIP
   other than the one shipping was quoted for is flagged on the order and in the email.
   The deploy user's crontab runs `database/reconcile_web_checkouts.php --commit` every
   15 minutes (under flock), logging to `storage/logs/reconcile-web-checkouts.log`.
   Cart in the browser, priced on the server (subtotals match the shop to the cent on
   three test carts); the shop's rolled-stencil packing (same boxes as the shop) rated
   through USSCOS's FedEx at the shop's terms (no markup, Home Delivery offered); Square's
   hosted checkout; a paid checkout becomes a sales order (customer matched by email or
   created, website channel, SH shipping line, card payment recorded, no tax). Before
   launch: production Square credentials in `.env` (SQUARE_ENVIRONMENT, SQUARE_ACCESS_TOKEN,
   SQUARE_LOCATION_ID, SQUARE_WEBHOOK_SIGNATURE_KEY), the same webhook subscription
   made again on the Production side (its own signature key), and USSCOS's FedEx switched from sandbox to production (shipping prices only
   match the shop's on production rates). Use the shop's existing developer app
   ("StencilsPlus Website"; customers never see its name) and the shop's location,
   **L656RANWFXGRC "US Specialty Coatings"**: its brand already has the USSC Products logo
   and navy #222b59, and it pays into the Synovus account. No new location is needed. (The
   StencilsPlus brand is the other location, STNCLPLS, which the website does not use.)
   Sandbox uses location L0ZFJ4P9AEXHD; the test order, customer and payment it made are
   test data. Original plan: The cart, live FedEx rates through the existing cartonizer,
   Square payment, and the order arriving as a USSCOS sales order.
4. **Customer and distributor accounts.** Sign-in, wholesale pricing, saved quotes and
   orders on terms. Includes migrating the shop's existing accounts from
   `/opt/usscstencils/var`.
5. **Endzone and midfield designers.** ✅ Built 19 Nov 2026, ahead of phases 2–4 at
   Chip's request, at `/custom/endzone` and `/custom/midfield`. Prices match the shop to
   the cent (checked against endzone.py and midfield.py). Until cart and accounts exist
   they are open to everyone and end in "Request this stencil", a stencil quote lead
   with the proof's address and, for midfield, the original logo. When phases 3 and 4
   land they get Add to Cart and Save Design. Differences from the shop: proofs are
   print-ready pages (save as PDF) because the server has no rsvg-convert; raster logos
   are not traced to vector on upload (the art team works from the original); SVG logos
   are rasterised in the browser. The letter outlines are in
   `storage/stencil-data/endzone_letters.json` on the server only.
   Original plan for this phase: Port `endzone.py`, `midfield.py`, `proof.py` and
   `pdfgen.py`, with their instant prices and proof PDFs.
6. **Cut-over.** 301s from every stencils.usscproducts.com URL and the 38 old WordPress
   stencil URLs. Then Google Tag Manager (GTM-T9C6GNWV), Search Console, and retiring
   the `usscstencils` service.

## Decided

- **Port, not proxy.** Everything is rebuilt in USSCOS's PHP. The alternative was
  keeping the Python designers as a service USSCOS calls on another droplet, which
  would mean two codebases and two databases indefinitely. That is the opposite of the
  goal.
- **The shop's catalog is the source** until cut-over. The two import scripts can be
  re-run whenever the shop changes, so USSCOS keeps up with it.
