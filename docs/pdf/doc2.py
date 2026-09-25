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
        "<b>What is left is not software.</b> The counting screens exist; somebody now has to walk the "
        "buildings. And the product file — barcodes and pack quantities — still gates scanning."
        "<br/><br/>"
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
        ("Pack quantities", "Aerosol, Fat Cans, pails and 2.5 gal jugs — 458 of 891 products now carry real figures"),
        ("Sales tax engine", "Tax calculated here, from the ship-to address. Rate, county, base and reason frozen onto each invoice"),
        ("GA and NC rate tables", "All 259 counties and 1,605 ZIPs, from the state revenue departments"),
        ("Tax reporting", "Liability by county, Amazon's collections kept separate, and a sales-by-state nexus watch"),
        ("Adjustments", "The honest correction path, with a required reason code, plus the negative-stock worklist"),
        ("Transfers", "Scanned out of one building and into the other. In between it counts at neither, which is correct"),
        ("Returns", "Only resellable goods go back on the shelf. Credit priced from the original invoice and its frozen tax rate"),
        ("Cycle counting", "Blind counts — the expected figure is never sent to the counter — reviewed, then applied as adjustments"),
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
        ("Nothing tells a credit memo from an invoice",
         "invoice_type has a credit_memo value that nothing filters on, so a credit raised today would be counted as revenue by Sales by Rep, A/R aging and customer lifetime value"),
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
        ("Products", "891 records, images, documents, ~100 fields for EDI and Amazon"),
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
        ("Scan a delivery against its PO", "Receiving and purchasing both work, but separately. The warehouse scan screen does not yet know about POs, so a scanned delivery does not update one", "Small"),
        ("Locations", "Screens and warehouses done. Needs the bays and racks in 730 entering", "Small"),
        ("Job binder", "Artwork and documents done. Production notes, QA checklist and photos follow the same pattern", "Medium"),
        ("Shipping queue", "Working. Needs pack verification and the link through to carriers", "Medium"),
        ("Dashboard", "Every figure is a placeholder showing zero. The data exists; nothing is wired to it", "Small"),
        ("Sales tax", "Engine and both states' rates are in. Needs real invoices checked against what QuickBooks charged before it is trusted", "Small"),
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
        ("Scan a delivery against its PO", "Receiving and purchasing each work, but separately — a scanned delivery does not update a PO", "Small"),
    ]:
        inv.append([C(r[0], st), C(r[1], st), C(r[2], st)])
    S.append(table(inv, [1.7*inch, 4.0*inch, 0.9*inch], st))

    S.append(P("Shipping and carriers", st, 'h2'))
    shp = [[C("Item", st, True), C("Why it matters", st, True), C("Effort", st, True)]]
    for r in [
        ("Pack verification", "Second scan at the bench catches what picking missed", "Small"),
        ("Freight section and BOL", "Carrier, cost, pallets and a generated bill of lading", "Medium"),
        ("FedEx", "Labels and tracking captured rather than typed", "Medium"),
        ("Kuebix quotes", "Removes retyping. Pending confirmation the API is enabled", "Medium"),
        ("Amazon orders direct", "Straight into USSCOS as same-day priority, instead of being keyed by hand", "Medium"),
    ]:
        shp.append([C(r[0], st), C(r[1], st), C(r[2], st)])
    S.append(table(shp, [1.7*inch, 4.0*inch, 0.9*inch], st))

    S.append(P("Data and integration", st, 'h2'))
    dat = [[C("Item", st, True), C("Why it matters", st, True), C("Effort", st, True)]]
    for r in [
        ("Product reimport", "765 SKUs with barcodes and pack quantities replacing the current 891", "Small"),
        ("Carry pack data through the reimport", "The figures entered so far must survive it or they are lost", "Small"),
        ("Quarterly tax rate refresh", "Georgia republishes its rate chart EVERY QUARTER, and ten counties change on 1 October. Somebody or something must load the new chart four times a year or the rates go quietly stale", "Small"),
        ("Credit memos in reporting", "Returns calculate what is owed but cannot raise a credit, because no report distinguishes a credit memo from an invoice — one raised today would be counted as revenue everywhere", "Medium"),
        ("Atlanta city-limit addresses", "In Fulton, DeKalb and Clayton the rate depends on the city and a ZIP cannot settle it. Those deliveries are flagged for a person today", "Medium"),
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
        ("Remaining pack quantities", "1 gal, 2-packs, 1.25 gal jugs, 55 gal drums. Aerosol, Fat Cans, pails and 2.5 gal jugs are done"),
        ("Robo jug boxing decision", "Whether to move from double boxes to singles. The pallet stays 48 jugs either way, so nothing is blocked — but the figures change when it is decided"),
        ("Final product file", "Barcodes and QuickBooks IDs, plus the pack columns"),
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
        ("4", "Shipping finished", "Pack verification · freight and BOL · scan a delivery against its PO",
         "Not started"),
        ("5", "Carriers", "FedEx labels and tracking · Kuebix quotes · Amazon direct",
         "Waiting on credentials"),
        ("6", "QuickBooks", "The bridge, both files, items and customers pulled",
         "Not started"),
        ("6b", "Tax upkeep", "Quarterly GA rate refresh · Atlanta city-limit addresses",
         "Rates loaded, upkeep not"),
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
        "it, and no amount of building substitutes for it. The pack figures supplied so far cover 458 "
        "of 891 products and must be carried through the reimport, or they are lost.", st, GREEN, '#f0fdf4'))

build(sys.argv[2],
      "USSCOS Build Status and Priorities",
      "What is ready, what is close, what remains &nbsp;·&nbsp; 24 September 2026",
      story_fn)
print("written:", sys.argv[2])
