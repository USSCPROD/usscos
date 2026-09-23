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
        "customers, products, reps, artwork. The warehouse side is not: of the seven ways stock moves "
        "at USSC, one and a half are built. That gap is the whole distance between where this is now "
        "and something the business can run on.<br/><br/>"
        "<b>Accounting is not on the critical path.</b> QuickBooks keeps the books, bills stay in "
        "QuickBooks, and none of it blocks going live.", st))
    S.append(Spacer(1, 10))

    # ------------- READY
    S.append(P("Ready to use now", st, 'h1'))
    S.append(P("Built, deployed and tested against real data.", st))
    ready = [[C("Area", st, True), C("What it does", st, True)]]
    for r in [
        ("Leads and pipeline", "Web forms feed leads, routed by type, converted to customers"),
        ("Quotes", "Built, emailed, converted to orders; status flows back to the lead"),
        ("Sales orders", "Created, edited, packing slips, payment collection"),
        ("Invoicing", "Ship and invoice, receipt or invoice email, closes the order"),
        ("Customers", "41,233 records, 360° profile, revenue history, activity timeline, alerts"),
        ("Duplicate prevention", "Adding a customer warns on likely duplicates, including ones the user cannot see"),
        ("Products", "891 records, images, documents, ~100 fields for EDI and Amazon"),
        ("Sales reps", "33 reps, credited revenue, commission rates, logins"),
        ("Reporting", "Sales by rep, sales by employee, A/R aging, all with date ranges"),
        ("Pick and verify", "Scan against the order, refuse wrong items, report short stock"),
        ("Artwork binder", "Proofs with full revision history and who approved which version"),
        ("Access control", "Reps and distributors restricted to their own customers, enforced in the data layer"),
        ("Admin", "Users, reps, terms, carriers, tax rates, with safe deactivate and delete"),
        ("Backups", "Nightly, verified by restoring, copied off-site to DigitalOcean"),
    ]:
        ready.append([C(r[0], st), C(r[1], st)])
    S.append(table(ready, [1.6*inch, 5.0*inch], st))

    # ------------- CLOSE
    S.append(P("Close to finished", st, 'h1'))
    close = [[C("Item", st, True), C("What remains", st, True), C("Effort", st, True)]]
    for r in [
        ("Stock locations", "Tables built and both warehouses created. Needs the screens, plus bays and racks for 730", "Small"),
        ("Job binder", "Artwork done. Production notes, QA checklist and photos follow the same pattern", "Medium"),
        ("Shipping queue", "Working. Needs pack verification and the link through to carriers", "Medium"),
        ("Dashboard", "Every figure is a placeholder showing zero. The data exists; nothing is wired to it", "Small"),
    ]:
        close.append([C(r[0], st), C(r[1], st), C(r[2], st)])
    S.append(table(close, [1.5*inch, 4.2*inch, 0.9*inch], st))

    S.append(PageBreak())

    # ------------- TO BUILD
    S.append(P("Left to build", st, 'h1'))
    S.append(P("Grouped by what they belong to. Effort is relative, not a quote.", st))
    S.append(P("Inventory — the critical path", st, 'h2'))
    inv = [[C("Item", st, True), C("Why it matters", st, True), C("Effort", st, True)]]
    for r in [
        ("Receiving", "How stock first enters the system. Nothing works without it", "Medium"),
        ("Deduct stock on shipment", "Shipping currently does not move inventory at all", "Small"),
        ("Transfers between warehouses", "Scan out, in transit, scan in — needed for 730", "Medium"),
        ("Returns", "Named as a main cause of drift. Nothing exists", "Medium"),
        ("Adjustments and write-offs", "The honest correction path. Without it people work around the system", "Small"),
        ("Cycle counting", "Rolling counts so errors surface in days, not at year end", "Medium"),
        ("Opening physical count", "One full count, entered once the screens above exist", "Operations"),
    ]:
        inv.append([C(r[0], st), C(r[1], st), C(r[2], st)])
    S.append(table(inv, [1.7*inch, 4.0*inch, 0.9*inch], st))

    S.append(P("Shipping and carriers", st, 'h2'))
    shp = [[C("Item", st, True), C("Why it matters", st, True), C("Effort", st, True)]]
    for r in [
        ("Pack verification", "Second scan at the bench catches what picking missed", "Small"),
        ("Customer PO on the order", "Replaces comparing addresses against printed paperwork", "Small"),
        ("Freight section and BOL", "Carrier, cost, pallets and a generated bill of lading", "Medium"),
        ("FedEx", "Labels and tracking captured rather than typed", "Medium"),
        ("Kuebix quotes", "Removes retyping. Pending confirmation the API is enabled", "Medium"),
    ]:
        shp.append([C(r[0], st), C(r[1], st), C(r[2], st)])
    S.append(table(shp, [1.7*inch, 4.0*inch, 0.9*inch], st))

    S.append(P("Data and integration", st, 'h2'))
    dat = [[C("Item", st, True), C("Why it matters", st, True), C("Effort", st, True)]]
    for r in [
        ("Product reimport", "765 SKUs with barcodes and pack quantities replacing the current 891", "Small"),
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
        ("Final product file", "Units per case, pallet quantity, barcodes, QuickBooks IDs"),
        ("SKU renaming", "Being finalised in QuickBooks"),
        ("Kuebix automation", "Confirmation the API is enabled on the subscription, and its cost"),
        ("FedEx", "Account number and API credentials"),
        ("Operations walkthrough", "The interview, which settles who owns stock at the canning company"),
        ("Inventory valuation", "The accountant. Blocks costing stock, not counting it"),
        ("Invoice fields (terms, ship via, PO)", "A QuickBooks export carrying those columns"),
        ("Distributor list", "Turf Tank, BSN and the others"),
    ]:
        blk.append([C(r[0], st), C(r[1], st)])
    S.append(table(blk, [2.9*inch, 3.7*inch], st))

    # ------------- PRIORITY
    S.append(P("The order I would build it in", st, 'h1'))
    S.append(callout("<b>The principle.</b> Every way stock moves needs a screen, and each screen must "
                     "be quicker than not using it. Any movement without one becomes the place where "
                     "someone shrugs and the count starts rotting again — which is exactly how the "
                     "current numbers went wrong.", st))
    S.append(Spacer(1, 8))

    ph = [[C("", st, True), C("Phase", st, True), C("Contains", st, True), C("Ends with", st, True)]]
    for r in [
        ("1", "Stock can move", "Receiving · deduct on shipment · transfers · adjustments",
         "Every movement has a screen"),
        ("2", "Stock is right", "Returns · cycle counting · opening physical count",
         "A count people believe"),
        ("3", "Product data", "Reimport 765 SKUs with barcodes, pack quantities, QuickBooks IDs",
         "Scanning works on real products"),
        ("4", "Shipping finished", "Pack verification · customer PO on the order · freight and BOL",
         "Orders leave verified"),
        ("5", "Carriers", "FedEx labels and tracking · Kuebix quotes",
         "No retyping"),
        ("6", "QuickBooks", "The bridge, both files, items and customers pulled",
         "Renames stop breaking things"),
        ("7", "Two entities", "TCC and USSC with per-area access",
         "TCC staff can use it"),
        ("8", "The rest", "Dashboard · job binder · portals · tiered pricing",
         "—"),
    ]:
        ph.append([C(r[0], st, True), C(r[1], st, True), C(r[2], st), C(r[3], st)])
    S.append(table(ph, [0.3*inch, 1.35*inch, 3.1*inch, 1.85*inch], st))
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

    S.append(P("Operations", st, 'h2'))
    for b in ["<b>★ Does a pallet count as a quantity of cases, or as its own item?</b> This decides how counting works across pack levels, and the product file cannot be finished without it.",
              "<b>★ While paint sits at the canning company, is it ours or TCC's?</b> Decides whose inventory it is.",
              "How much paint is lost turning totes into cans? Nobody tracks it, but someone knows.",
              "What bays and racks exist in 730, and how are they labelled?",
              "Is the draft-to-confirmed step happening on every order, or are some invisible to shipping?",
              "When is stock considered gone — at pick, at load, or at invoice?",
              "Who prints labels, and on what printer?"]:
        S.append(B(b, st))

    S.append(P("Bookkeeper and accountant", st, 'h2'))
    for b in ["<b>★ Who owns inventory valuation — USSCOS or QuickBooks?</b> Blocks costing stock, though not counting it.",
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

    S.append(Spacer(1, 12))
    S.append(callout(
        "<b>The single most useful thing right now</b> is the finished product file — units per case, "
        "pallet quantity, barcodes and QuickBooks IDs. Receiving, transfers, counting and scanning all "
        "wait on it, and no amount of building substitutes for it.", st, GREEN, '#f0fdf4'))

build(sys.argv[2],
      "USSCOS Build Status and Priorities",
      "What is ready, what is close, what remains &nbsp;·&nbsp; 23 September 2026",
      story_fn)
print("written:", sys.argv[2])
