import sys; sys.path.insert(0, sys.argv[1])
from pdfstyle import *
from reportlab.platypus import Paragraph, Spacer, PageBreak
from reportlab.lib.units import inch

def P(s, st, k='body'): return Paragraph(s, st[k])
def B(s, st): return Paragraph('<bullet>&bull;</bullet> ' + s, st['bullet'])
def C(s, st, b=False): return Paragraph(s, st['cellb' if b else 'cell'])

def story_fn(S, st):
    S.append(callout(
        "<b>Summary.</b> The sales side of USSCOS is built and working — quoting, orders, invoicing, "
        "customers, products, reps, artwork. The warehouse side was not: of the seven ways stock moves "
        "at USSC, one and a half were built.<br/><br/>"
        "<b>All seven are now built.</b> Receiving, shipment, purchase-order receipts, transfers, "
        "returns, adjustments and counting — every one through a single path that writes the movement "
        "and the per-location balance together, inside one database transaction. The product total is "
        "recomputed from its locations rather than incremented, so it cannot drift from the detail."
        "<br/><br/>"
        "<b>Shipping is built too</b> — pack verification, FedEx labels with the tracking number "
        "captured automatically, and an invoice review queue so the customer is told only once the "
        "invoice is finished.<br/><br/>"
        "<b>What is left is mostly not software.</b> The screens exist; what they now need is people "
        "to walk the buildings and count, a product file with weights and barcodes, and the process "
        "signed off by those who work it.<br/><br/>"
        "<b>Accounting is not on the critical path.</b> QuickBooks keeps the books, bills stay in "
        "QuickBooks, and none of it blocks going live.", st))
    S.append(Spacer(1, 10))

    # ------------- DONE SINCE LAST VERSION
    S.append(P("Completed since the last version of this document", st, 'h1'))
    S.append(P("All deployed and tested against the live database.", st))
    new = [[C("Item", st, True), C("What it does now", st, True)]]
    for r in [
        ("Receiving", "Scan or type a SKU, confirm the quantity, choose the location. Suggests a quantity from the pack size but never insists on it"),
        ("Deduct stock on shipment", "Shipping takes stock out of the location holding it, biggest holding first, splitting across locations when needed"),
        ("Stock on every invoice", "A counter sale or a correction moves stock too, not only a shipment. Editing an invoice moves the difference"),
        ("Receive against a PO", "Records what actually arrived, never trimmed to match the PO, into a location"),
        ("Variance queue", "Receipts that disagree with their PO, resolved by updating the PO or explaining the difference"),
        ("Locations", "Admin screen, warehouses named 1000, 900, 3085 and 730"),
        ("Customer PO on the job", "The PO document itself is filed on the job binder and stays with the job after invoicing"),
        ("Pack quantities", "Aerosol, Fat Cans, pails and 2.5 gal jugs — 508 of 885 products packed, 458 weighed"),
        ("Sales tax engine", "Tax calculated here, from the ship-to address. Rate, county, base and reason frozen onto each invoice"),
        ("GA and NC rate tables", "All 259 counties and 1,605 ZIPs, from the state revenue departments"),
        ("Tax reporting", "Liability by county, Amazon's collections kept separate, and a sales-by-state nexus watch"),
        ("Adjustments", "The honest correction path, with a required reason code, plus the negative-stock worklist"),
        ("Transfers", "Scanned out of one building and into the other. In between it counts at neither, which is correct"),
        ("Returns", "Only resellable goods go back on the shelf. Credit priced from the original invoice and its frozen tax rate"),
        ("Cycle counting", "Blind counts — the expected figure is never sent to the counter — reviewed, then applied as adjustments"),
        ("Pack verification", "A second scan at the bench, checked against the pick rather than the order. Refuses anything not on the order"),
        ("Shipment tracking", "Several numbers per shipment, each a carrier link, searchable, and kept on the invoice"),
        ("Invoice review queue", "A shipped invoice waits for handling to be added. Approving it is what emails the customer"),
        ("FedEx", "Labels created and the tracking number captured with nobody retyping it. Proven against sandbox"),
        ("Box rules", "One aerosol case is a single box, two go in a double, three go as a double plus a single. One pail is a parcel, two are freight"),
        ("Dashboard", "Real figures, and only the ones USSCOS can actually know. The queues from every module surface here"),
        ("Credit memos", "Raised from a return. Negative totals, so every existing report nets them correctly"),
        ("Job binder", "Artwork, the customer PO, append-only job notes, and a quality checklist USSC defines itself"),
        ("Box dimensions", "All four boxes measured — aerosol single and double, Fat Can, Robo jug — with a 2 lb empty weight each"),
        ("Rate cross-check", "Every GA and NC ZIP compared against an independent published table. Ambiguity is now found rather than assumed"),
        ("Rate refresh", "One command loads whatever chart is dropped in, dates it from inside the chart, and the dashboard warns before rates go stale"),
    ]:
        new.append([C(r[0], st), C(r[1], st)])
    S.append(table(new, [1.8*inch, 4.8*inch], st))

    S.append(callout(
        "<b>The design point underneath all of it.</b> Every stock movement goes through one function "
        "that writes the transaction record and the per-location balance inside a single database "
        "transaction. The product total is <i>recomputed</i> from the sum of its locations rather than "
        "incremented, so it cannot drift away from the detail. That is the difference between a count "
        "that can be trusted and the one being replaced.", st, GREEN, '#f0fdf4'))

    S.append(PageBreak())

    # ------------- PROBLEMS FOUND
    S.append(P("Problems found and fixed on the way", st, 'h1'))
    S.append(P("Each of these was live and silent. They are listed because they may have already cost "
               "money, and because they say something about where else to look.", st))
    bugs = [[C("What was wrong", st, True), C("What it cost", st, True)]]
    for r in [
        ("Shipping the rest of a part-shipped order re-invoiced everything picked",
         "The customer was billed a second time for the first shipment. Worth checking against real partial shipments in QuickBooks"),
        ("Receiving against a PO capped the quantity at what was ordered",
         "620 cases arriving against a PO for 600 silently became 600 — twenty cases gone, no error, no note"),
        ("PO receipts never touched locations",
         "Stock received against a PO existed as a total in no building, invisible to the location screens"),
        ("Invoices could be edited without stock following",
         "Correcting an invoice from ten to eight billed eight and left ten deducted"),
        ("A database error on an invoice showed as “Not Found”",
         "Real faults looked like missing records, so nobody would have reported them as faults"),
        ("North Carolina was taxed at 8.25 percent statewide",
         "That is Mecklenburg's rate. Most NC counties are 6.75 or 7 percent, so customers would have been overcharged — caught by an Amazon packing slip"),
        ("Every Georgia ZIP pointed at a rate not yet in effect",
         "Caught in my own work before anyone used it. It would have quietly started working on 1 October, which is the worst way for a fault to behave"),
        ("One county stood in for a whole state",
         "With no default marked, the lowest-numbered county — Appling, 8 percent — was returned as the rate for the whole of Georgia"),
        ("ZIPs were assigned to counties by land area",
         "A weak proxy: a ZIP can hold most of its acreage in a rural county and nearly all its mail in the urban one next door, which priced Johns Creek as Gwinnett. Checking against a published table found 281 such ZIPs"),
        ("Georgia's October rates had no end date",
         "They would have gone on applying silently long after the quarter ended. Found while automating the refresh, which now judges rates by the age of the newest chart rather than trusting an end date"),
        ("Fat Cans were sharing the aerosol box",
         "Every 12-to-a-case product had been given the same box, so 26 oz cases would have been rated as the smaller 18 oz carton. They have their own now"),
        ("A Robo jug pack was recorded as the box, not the jug",
         "An order for three jugs would have been weighed as three pairs — a FedEx label reading 105 lb for something that weighs 35"),
        ("Nothing told a credit memo from an invoice",
         "invoice_type has a credit_memo value that nothing filtered on, so a credit would have counted as revenue in Sales by Rep, A/R aging and customer lifetime value. Fixed by giving credits negative totals, so all fifteen money queries net correctly untouched"),
        ("Shipping queued invoices from the wrong place",
         "The rule lived in one controller, so anything else that shipped an order would have skipped the review queue and never told the customer"),
        ("FedEx sandbox answers every address check with a fake",
         "It placed 1000 McFarland 400 Blvd in Chile. Address validation now reports that it proved nothing, rather than a verdict nobody should believe"),
    ]:
        bugs.append([C(r[0], st), C(r[1], st)])
    S.append(table(bugs, [3.0*inch, 3.6*inch], st))

    S.append(PageBreak())

    # ------------- READY
    S.append(P("Ready to use now", st, 'h1'))
    ready = [[C("Area", st, True), C("What it does", st, True)]]
    for r in [
        ("Leads and pipeline", "Web forms feed leads, routed by type, converted to customers"),
        ("Quotes", "Built, emailed, converted to orders; status flows back to the lead"),
        ("Sales orders", "Created, edited, packing slips, payment collection"),
        ("Invoicing", "Ship and invoice, receipt or invoice email, closes the order, moves stock"),
        ("Customers", "41,233 records, 360° profile, revenue history, activity timeline, alerts"),
        ("Duplicate prevention", "Adding a customer warns on likely duplicates, including ones the user cannot see"),
        ("Products", "885 active records, images, documents, ~100 fields for EDI and Amazon"),
        ("Sales reps", "33 reps, credited revenue, commission rates, logins"),
        ("Reporting", "Sales by rep, sales by employee, A/R aging, all with date ranges"),
        ("Pick and verify", "Scan against the order, refuse wrong items, report short stock"),
        ("Receiving", "Scan in, suggested pack quantities, into a named location"),
        ("Stock movement", "Receipts, shipments, invoice corrections and PO receipts, all logged by location"),
        ("Purchasing", "POs raised and received against, with a variance queue when they disagree"),
        ("Locations", "Warehouses 1000, 900, 3085, 730, with bays and racks addable"),
        ("Job binder", "Artwork with revision history and approvals, plus the customer's PO filed on the job"),
        ("Access control", "Reps and distributors restricted to their own customers, enforced in the data layer"),
        ("Admin", "Users, reps, terms, carriers, tax rates, locations, with safe deactivate and delete"),
        ("Backups", "Nightly, verified by restoring, copied off-site to DigitalOcean"),
    ]:
        ready.append([C(r[0], st), C(r[1], st)])
    S.append(table(ready, [1.6*inch, 5.0*inch], st))

    # ------------- CLOSE
    S.append(P("Close to finished", st, 'h1'))
    close = [[C("Item", st, True), C("What remains", st, True), C("Effort", st, True)]]
    for r in [
        ("Pack and weight data", "508 products packed and 458 weighed. The rest wait on figures, and all of it must survive the reimport", "Small"),
        ("Locations", "Screens and warehouses done. Needs the bays and racks in 730 entering", "Small"),
        ("FedEx production", "Works against sandbox. FedEx must certify the Ship API before real labels print, and package weights are still missing", "Small"),
        ("Sales tax", "Engine and both states' rates are in. Needs real invoices checked against what QuickBooks charged before it is trusted", "Small"),
        ("Quality checklist", "The screen is built and deliberately empty — USSC adds the checks it actually performs", "Operations"),
    ]:
        close.append([C(r[0], st), C(r[1], st), C(r[2], st)])
    S.append(table(close, [1.6*inch, 4.1*inch, 0.9*inch], st))

    # ------------- TO BUILD
    S.append(P("Left to build", st, 'h1'))
    S.append(P("Grouped by what they belong to. Effort is relative, not a quote.", st))
    S.append(P("Inventory — still the critical path", st, 'h2'))
    inv = [[C("Item", st, True), C("Why it matters", st, True), C("Effort", st, True)]]
    for r in [
        ("Opening physical count", "One full count. The screens exist and a full count is the same machinery as a cycle count — this is people walking buildings, not code", "Operations"),
        ("Bays and racks in 730", "Locations work, but 730 has no internal detail yet, so everything there counts as one place", "Operations"),
        ("Scanners", "None bought yet, so picking, packing and counting are keyboard-only. 738 products carry no barcode either — printing Code 128 SKU labels would settle both", "Operations"),
    ]:
        inv.append([C(r[0], st), C(r[1], st), C(r[2], st)])
    S.append(table(inv, [1.7*inch, 4.0*inch, 0.9*inch], st))

    S.append(P("Shipping and carriers", st, 'h2'))
    shp = [[C("Item", st, True), C("Why it matters", st, True), C("Effort", st, True)]]
    for r in [
        ("Freight section and BOL", "Carrier, cost, pallets and a generated bill of lading. Freight PRO numbers are hand-entered today", "Medium"),
        ("Automatic handling fee", "Calculated rather than typed, so the review queue becomes a one-click check instead of data entry", "Small"),
        ("Tracking status", "Delivered or in transit on the invoice. The second FedEx project is created and its keys are in", "Small"),
        ("Kuebix quotes", "Removes retyping. Pending confirmation the API is enabled", "Medium"),
        ("Amazon orders direct", "Straight into USSCOS as same-day priority, instead of being keyed by hand", "Medium"),
    ]:
        shp.append([C(r[0], st), C(r[1], st), C(r[2], st)])
    S.append(table(shp, [1.7*inch, 4.0*inch, 0.9*inch], st))

    S.append(P("Data and integration", st, 'h2'))
    dat = [[C("Item", st, True), C("Why it matters", st, True), C("Effort", st, True)]]
    for r in [
        ("Product reimport", "765 SKUs with barcodes and pack quantities replacing the current 885", "Small"),
        ("Carry pack data through the reimport", "The figures entered so far must survive it or they are lost", "Small"),
        ("Quarterly tax rate refresh", "Loading is now one command and the dashboard warns when rates go stale. What cannot be automated is the download — Georgia's site refuses our server and Avalara's tables sit behind a form. Two minutes, four times a year", "Operations"),
        ("Historical A/R", "Imported invoices carry no payments, so $1.6m reads as overdue that is not. Either import the payments or close the period", "Small"),
        ("281 disputed ZIPs", "Where our county assignment and the published table disagree — 174 in GA, 107 in NC. Listed on the tax screen and flagged for a person on each order. Settling them properly means address-level lookup", "Medium"),
        ("QuickBooks bridge", "Reads QuickBooks and talks to USSCOS. Outbound only — no port to open", "Large"),
        ("Capture QuickBooks IDs", "So a rename in QuickBooks never breaks the link again", "Small"),
        ("Two-entity support", "TCC and USSC, with per-area access rather than one flag", "Large"),
    ]:
        dat.append([C(r[0], st), C(r[1], st), C(r[2], st)])
    S.append(table(dat, [1.7*inch, 4.0*inch, 0.9*inch], st))

    S.append(P("Later — genuinely optional for go-live", st, 'h2'))
    S.append(P("Customer and distributor portals with reordering · tiered and volume pricing · "
               "rep dashboard and commission statements · public website · two-factor authentication "
               "and audit log · Mar-Kov integration · accounting ledger, if it is ever wanted.", st, 'note'))

    S.append(PageBreak())

    # ------------- BLOCKED
    S.append(P("Waiting on other people", st, 'h1'))
    S.append(P("Not stalled for want of effort — listed so they are not mistaken for work in progress.", st))
    blk = [[C("Item", st, True), C("Waiting on", st, True)]]
    for r in [
        ("Remaining pack quantities and weights", "Roughly 380 products still without a pack size or a weight. Aerosol, Fat Cans, pails and 2.5 gal jugs are complete"),
        ("Robo jug boxing decision", "Settled for parcels — one jug per box, since a pair is 58 to 70 lb. The box still holds two on a pallet or a truck, and the pallet is 48 jugs either way"),
        ("Process sign-off", "The step-by-step handed to sales, shipping, bookkeeping and operations to approve or mark up"),
        ("Pail dimensions", "A pail ships unboxed, so the pail is the package and FedEx needs its size. The four cardboard boxes are all measured"),
        ("Scanners", "None bought yet. Two to trial would do — one at receiving, one at the packing bench"),
        ("Final product file", "Barcodes, QuickBooks IDs, case weights, and the pack columns"),
        ("Hazmat class", "Aerosol is regulated and FedEx will refuse production labels without it declared"),
        ("FedEx certification", "FedEx must certify the Ship API before production labels can print"),
        ("SKU renaming", "Being finalised in QuickBooks"),
        ("Kuebix automation", "Confirmation the API is enabled on the subscription, and its cost"),
        ("FedEx", "Account number and API credentials"),
        ("Operations walkthrough", "The interview, which settles who owns stock at the canning company"),
        ("Inventory valuation", "The accountant. Blocks costing stock, not counting it"),
        ("Invoice fields (terms, ship via, PO)", "A QuickBooks export carrying those columns"),
        ("Distributor list", "Turf Tank, BSN and the others"),
    ]:
        blk.append([C(r[0], st), C(r[1], st)])
    S.append(table(blk, [2.3*inch, 4.3*inch], st))

    # ------------- PRIORITY
    S.append(P("The order I would build it in", st, 'h1'))
    S.append(callout("<b>The principle.</b> Every way stock moves needs a screen, and each screen must "
                     "be quicker than not using it. Any movement without one becomes the place where "
                     "someone shrugs and the count starts rotting again — which is exactly how the "
                     "current numbers went wrong.", st))
    S.append(Spacer(1, 8))

    ph = [[C("", st, True), C("Phase", st, True), C("Contains", st, True), C("State", st, True)]]
    for r in [
        ("1", "Stock can move", "Receiving · deduct on shipment · PO receipts · transfers · adjustments",
         "Done"),
        ("2", "Stock is right", "Returns · cycle counting · negative stock list · opening physical count",
         "Built — the count itself is operations"),
        ("3", "Product data", "Reimport 765 SKUs with barcodes, pack quantities, QuickBooks IDs",
         "Waiting on the file"),
        ("4", "Shipping finished", "Pack verification · PO receiving · freight and BOL · handling fee",
         "Built except freight — paused for sign-off"),
        ("5", "Carriers", "FedEx labels and tracking · Kuebix quotes · Amazon direct",
         "FedEx proven in sandbox"),
        ("6", "QuickBooks", "The bridge, both files, items and customers pulled",
         "Not started"),
        ("6b", "Tax upkeep", "Quarterly GA rate refresh · the 281 disputed ZIPs",
         "Loading automated, downloading is manual"),
        ("7", "Two entities", "TCC and USSC with per-area access",
         "Not started"),
        ("8", "The rest", "Dashboard · job binder remainder · portals · tiered pricing",
         "Not started"),
    ]:
        ph.append([C(r[0], st, True), C(r[1], st, True), C(r[2], st), C(r[3], st)])
    S.append(table(ph, [0.3*inch, 1.3*inch, 3.0*inch, 2.0*inch], st))
    S.append(Spacer(1, 8))
    S.append(P("Phases 1 to 4 are what \"functional\" means. Five onwards make it faster and wider, but "
               "the warehouse can run without them.", st, 'note'))
    S.append(callout("<b>One sequencing point worth protecting.</b> The physical count belongs in phase 2, "
                     "<i>after</i> the movement screens exist. Counting first means the count is out of "
                     "date before anyone can use it, and the second count is much harder to ask for than "
                     "the first.", st, AMBER, '#fffbeb'))

    S.append(PageBreak())

    # ------------- QUESTIONS
    S.append(P("Questions that need answers", st, 'h1'))
    S.append(P("Grouped by who can answer them. The starred ones change what gets built.", st))

    S.append(P("Answered since the last version", st, 'h2'))
    for b in ["<b>How does a pallet count?</b> As a quantity of base units, not as its own item — 108 cases × 12 = 1,296 cans for 18 oz aerosol, 75 × 12 = 900 for Fat Cans, 24 pails, 48 jugs. Built that way.",
              "<b>Is the PO to TCC tied to a job?</b> No. It is replenishment, billed to USSC, and never belongs to a particular sales order.",
              "<b>Which PO belongs on the job binder?</b> The customer's, as a document. Built.",
              "<b>Should we use Stripe for tax lookups?</b> No — priced per calculation and built to sit under Stripe payments. Two states is a rate table we own, and it is built.",
              "<b>Do we charge tax outside GA and NC on web sales?</b> No, only where we have nexus. Sales by state are now tracked so a threshold is seen coming.",
              "<b>Should Amazon's tax be stored?</b> Yes, as a memo figure that never touches what we owe. Built."]:
        S.append(B(b, st))

    S.append(P("Operations", st, 'h2'))
    for b in ["<b>★ While paint sits at the canning company, is it ours or TCC's?</b> Decides whose inventory it is.",
              "<b>★ Who watches the variance queue?</b> It is built, but a queue nobody opens is the same as no queue.",
              "<b>★ When is the opening count, and who does it?</b> Everything else is ready for it. Until it happens the figures are a starting point, not a count.",
              "How often should a cycle count run, and over what — highest value, fastest moving, or simply everything in turn?",
              "How much paint is lost turning totes into cans? Nobody tracks it, but someone knows.",
              "What bays and racks exist in 730, and how are they labeled?",
              "Is the draft-to-confirmed step happening on every order, or are some invisible to shipping?",
              "When is stock considered gone — at pick, at load, or at invoice? It currently comes out at invoice.",
              "Who prints labels, and on what printer?"]:
        S.append(B(b, st))

    S.append(P("Bookkeeper and accountant", st, 'h2'))
    for b in ["<b>★ Who owns inventory valuation — USSCOS or QuickBooks?</b> Blocks costing stock, though not counting it.",
              "<b>★ Do our rates match QuickBooks?</b> Check a handful of real GA and NC invoices against what QuickBooks charged. The tables come from the states, but that is the comparison that proves it.",
              "<b>Have any part-shipped orders been billed twice?</b> The fault is fixed, but any that already happened are in QuickBooks, not here.",
              "<b>Where did the 8.25 percent NC rate come from?</b> It is Mecklenburg's. Worth knowing whether it was ever charged outside USSCOS.",
              "After the rename, do historical invoices show the new item names in an export?",
              "Can the export include the QuickBooks ListID column?",
              "How is sales tax set up — one rate per state, or county codes?",
              "Are processor fees captured per transaction or reconciled monthly?"]:
        S.append(B(b, st))

    S.append(P("Suppliers and vendors", st, 'h2'))
    for b in ["<b>★ Kuebix: is API access enabled on our subscription, and does it cost extra?</b>",
              "FedEx: account number and API credentials.",
              "Mar-Kov: is there an API, and is its QuickBooks link switched on today?"]:
        S.append(B(b, st))

    S.append(P("Management", st, 'h2'))
    for b in ["<b>★ Will TCC staff use USSCOS at go-live?</b> Two-entity support is far cheaper to add during the data reimport than afterwards.",
              "Does anyone need a combined TCC and USSC view? Neither QuickBooks file sees the other, so only USSCOS could produce one.",
              "Who is the pilot group for the scanners, and when can they spend a day on it?"]:
        S.append(B(b, st))

    S.append(Spacer(1, 8))
    S.append(callout(
        "<b>The single most useful thing right now</b> is still the finished product file — barcodes, "
        "QuickBooks IDs and the remaining pack quantities. Scanning, transfers and counting all wait on "
        "it, and no amount of building substitutes for it. The pack figures supplied so far cover 508 "
        "of 885 products and must be carried through the reimport, or they are lost.", st, GREEN, '#f0fdf4'))

build(sys.argv[2],
      "USSCOS Build Status and Priorities",
      "What is ready, what is close, what remains &nbsp;·&nbsp; 28 September 2026",
      story_fn)
print("written:", sys.argv[2])
