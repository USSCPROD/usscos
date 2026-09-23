import sys; sys.path.insert(0, sys.argv[1])
from pdfstyle import *
from reportlab.platypus import Paragraph, Spacer, PageBreak
from reportlab.lib.units import inch

def P(s, st, k='body'): return Paragraph(s, st[k])
def B(s, st): return Paragraph('<bullet>&bull;</bullet> ' + s, st['bullet'])
def C(s, st, b=False): return Paragraph(s, st['cellb' if b else 'cell'])

def story_fn(S, st):
    S.append(callout(
        "<b>The aim.</b> Every movement of stock and every step of an order is recorded as it happens, "
        "by the person doing it, on a device in their hand — so the count is right because the work "
        "made it right, not because someone reconciled it afterwards.", st))
    S.append(Spacer(1, 12))

    # ---------------- 1
    S.append(P("1. Where shipping stands today", st, 'h1'))
    S.append(P("Some of this is built and working. The gaps are specific rather than general.", st))
    data = [[C("Step", st, True), C("Status", st, True), C("Notes", st, True)]]
    for r in [
        ("Sales order created", "Built", "Created as <b>draft</b> — see the warning below"),
        ("Reaches the shipping queue", "Built", "Queue groups orders by carrier"),
        ("Pick and verify by scan", "Built", "Scan validates against the order, warns on wrong or over-picked item"),
        ("Report short stock", "Built", "Note goes back against the order"),
        ("Invoice the picked quantity", "Built", "Short picks invoice what shipped and leave the order open"),
        ("Packing slip", "Built", "Prints today"),
        ("Pack verification", "<b>Missing</b>", "No second check at the packing bench"),
        ("Deduct stock on shipment", "<b>Missing</b>", "Shipping does not move inventory at all"),
        ("Carrier label and tracking", "Manual", "Tracking typed in by hand"),
        ("Freight quote and BOL", "Manual", "Kuebix quote retyped, BOL produced outside the system"),
        ("Returns", "<b>Missing</b>", "Nothing exists — a main cause of the current drift"),
    ]:
        data.append([C(r[0], st), C(r[1], st), C(r[2], st)])
    S.append(table(data, [1.9*inch, 0.95*inch, 3.75*inch], st))
    S.append(Spacer(1, 10))
    S.append(callout(
        "<b>Worth checking first.</b> A new sales order is created as <b>draft</b>, and the shipping "
        "queue only shows orders that are confirmed or later. If sales staff expect orders to flow "
        "straight to shipping, some may be sitting where the clerk cannot see them. This costs nothing "
        "to check and may explain orders that seem to go missing.", st, AMBER, '#fffbeb'))

    # ---------------- 2
    S.append(P("2. Scanning — and one decision that saves months", st, 'h1'))
    S.append(P("The device recommendation is the Zebra TC22: an Android mobile computer with an "
               "integrated scan engine, a readable screen, and a replaceable battery. That is a sound "
               "choice and worth following.", st))
    S.append(P("Where the plan can be simplified", st, 'h2'))
    S.append(P("The scanning document describes a dedicated warehouse application. That is the right "
               "end state, but it is not required to begin, and the difference in cost is large.", st))
    S.append(P("The TC22 runs Android with a full browser, and Zebra's DataWedge delivers a scan into "
               "whatever field has focus, exactly as a keyboard would. <b>The screens already built for "
               "USSCOS therefore run on it as they are.</b> The pick-and-verify screen was written for "
               "touch, holds focus for the scanner, and stores progress on the server so an interrupted "
               "job can be picked up by someone else.", st))
    tbl = [[C("", st, True), C("Web screens on the TC22", st, True), C("Dedicated Android app", st, True)]]
    for r in [
        ("Time to first use", "Now — already built", "Months"),
        ("Works offline", "No", "Yes"),
        ("Device management", "Browser only", "Full enterprise control"),
        ("Cost to change", "Deploy and it is live", "App release each time"),
        ("Hardware lock-in", "None — any device with a browser", "Per-platform build"),
    ]:
        tbl.append([C(r[0], st), C(r[1], st), C(r[2], st)])
    S.append(table(tbl, [1.7*inch, 2.5*inch, 2.4*inch], st))
    S.append(Spacer(1, 8))
    S.append(callout(
        "<b>Recommendation.</b> Buy the TC22s and run the existing web screens on them. Prove the "
        "workflow with real staff and real stock first. Build a native application later only if "
        "offline working turns out to matter — and the honest test of that is whether Wi-Fi actually "
        "drops in the racks, which the pilot will answer.", st, GREEN, '#f0fdf4')) 
    S.append(P("Barcode coverage is the real constraint", st, 'h2'))
    S.append(P("From the current product file: <b>UPC on 38% of items, case barcodes on 49%</b>. So "
               "scanning covers about half the catalogue and typing the SKU is the normal path for the "
               "rest, not an exception. Both are supported. Worth setting that expectation before the "
               "devices arrive, and worth adding barcodes to the fastest-moving items first rather than "
               "trying to reach all 765.", st))

    S.append(PageBreak())

    # ---------------- 3
    S.append(P("3. The shipping flow we are building toward", st, 'h1'))
    flow = [[C("Stage", st, True), C("What happens", st, True), C("Built?", st, True)]]
    for r in [
        ("Confirm", "Order moves from draft into the shipping queue", "Yes"),
        ("Pick", "Scan each item against the order. Wrong or excess items are refused at the shelf", "Yes"),
        ("Short stock", "Picker records what is missing, order is flagged and routed back", "Yes"),
        ("Pack", "Second scan at the bench, package count, weight and dimensions captured", "No"),
        ("Rate", "Freight quote pulled from Kuebix, or a FedEx rate for parcel", "No"),
        ("Label", "Carrier label printed, tracking captured automatically", "No"),
        ("Ship", "Stock deducted, order closed, invoice raised for what shipped", "Part"),
        ("Documents", "Packing slip, and a bill of lading for freight", "Part"),
        ("Return", "Goods back in, restock or scrap, credit raised", "No"),
    ]:
        flow.append([C(r[0], st), C(r[1], st), C(r[2], st)])
    S.append(table(flow, [1.0*inch, 4.6*inch, 0.95*inch], st))

    # ---------------- 4
    S.append(P("4. FedEx", st, 'h1'))
    S.append(P("What an integration gives you, in order of value:", st))
    for b in ["<b>Tracking captured automatically</b> instead of typed, which removes a transcription error from every parcel.",
              "<b>Labels printed from USSCOS</b>, so the address on the label is the address on the order by construction.",
              "<b>Rates at order time</b>, so shipping is quoted rather than estimated.",
              "<b>Delivery status back on the order</b>, which answers \"where is it\" without leaving the system."]:
        S.append(B(b, st))
    S.append(Spacer(1, 6))
    S.append(P("<b>Needed:</b> a FedEx developer account, API credentials, and the account number. "
               "The work is self-contained and does not depend on anything else on this list.", st))
    S.append(callout("<b>This is an efficiency gain, not a blocker.</b> Manual tracking entry works on "
                     "day one. It should be scheduled after the inventory work, not before it.", st))

    # ---------------- 5
    S.append(P("5. Kuebix", st, 'h1'))
    S.append(P("Kuebix does publish an API, which makes automation realistic rather than hypothetical. "
               "Two endpoints matter:", st))
    k = [[C("Purpose", st, True), C("Endpoint", st, True)]]
    k.append([C("Request a fresh quote", st), C("POST /action/quickRate", st)])
    k.append([C("Retrieve quotes already on a shipment", st), C("GET /shipments/{id}/rates/view", st)])
    S.append(table(k, [3.3*inch, 3.3*inch], st))
    S.append(Spacer(1, 8))
    S.append(P("Quotes return carrier, total price, fuel surcharge, additional charges, transit days and "
               "a reference number. Authentication is an API username, API key and client ID.", st))
    S.append(callout(
        "<b>Two questions for Kuebix before any work starts:</b> is API access enabled on our "
        "subscription, and does it cost extra? Neither is answerable from their documentation, and "
        "both decide whether this is worth beginning.", st, AMBER, '#fffbeb'))
    S.append(P("How it would work", st, 'h2'))
    S.append(P("From the sales order, request or retrieve quotes, show the options with carrier, price "
               "and transit days, let the clerk choose, and store the chosen quote against the order. "
               "That removes the retyping and — more usefully — keeps the freight cost attached to the "
               "order instead of living on paper.", st))

    # ---------------- 6
    S.append(P("6. Freight and bills of lading", st, 'h1'))
    S.append(P("Worth building whether or not the Kuebix API is available. The API makes it quicker to "
               "fill in; it is not what makes it valuable.", st))
    for b in ["Carrier, service, quote reference and cost recorded against the order.",
              "Pallet count, weight and dimensions.",
              "<b>Bill of lading generated</b> rather than produced by hand.",
              "Freight cost visible against the order, so margin reflects what delivery actually cost."]:
        S.append(B(b, st))

    # ---------------- 7
    S.append(P("7. Returns", st, 'h1'))
    S.append(P("Listed here because returns are a shipping activity and because they were named as a "
               "main cause of stock being wrong. Nothing exists for them today.", st))
    for b in ["What came back, from which customer, against which invoice.",
              "<b>A decision for each item: back into sellable stock, or scrapped.</b> These are different movements, and treating them the same rebuilds the drift.",
              "The stock movement that follows from that decision.",
              "A credit memo, or a record that one is owed."]:
        S.append(B(b, st))
    S.append(Spacer(1, 6))
    S.append(P("The database already anticipates this — the invoice types include credit memo, and the "
               "stock movement types include return in and return out. The structure is there; the "
               "screens are not.", st))

    # ---------------- 8
    S.append(P("8. What is needed from USSC", st, 'h1'))
    q = [[C("Item", st, True), C("Why it matters", st, True), C("Who", st, True)]]
    for r in [
        ("Is Kuebix API access enabled on our subscription, and does it cost extra?",
         "Decides whether freight is an integration or a data-entry screen", "Kuebix support"),
        ("FedEx account number and API credentials",
         "Nothing can start without them", "FedEx account admin"),
        ("Product file with units per case, pallet quantity and barcodes",
         "Scanning cannot convert a case into units without them", "Whoever owns the spreadsheet"),
        ("Does a pallet count as a quantity of cases, or its own item?",
         "Decides how counting works across pack levels", "Operations"),
        ("Bays and racks in the 730 warehouse",
         "Needed to record where stock actually sits", "Operations"),
        ("Is the draft-to-confirmed step happening on every order?",
         "Orders may be invisible to shipping", "Sales and shipping"),
        ("Who prints labels, and on what printer?",
         "Decides how label printing is wired", "Shipping"),
    ]:
        q.append([C(r[0], st), C(r[1], st), C(r[2], st)])
    S.append(table(q, [2.7*inch, 2.5*inch, 1.4*inch], st))
    S.append(Spacer(1, 12))
    S.append(callout(
        "<b>The order that matters.</b> Stock movements first — receiving, transfers, deduction on "
        "shipment, returns. Carrier and freight automation second. A perfectly automated shipping label "
        "on top of an inventory count nobody believes solves the smaller half of the problem.", st))

build(sys.argv[2],
      "Shipping and Fulfillment",
      "What USSCOS needs to deliver — scanning, carriers, freight and returns &nbsp;·&nbsp; 23 September 2026",
      story_fn)
print("written:", sys.argv[2])
