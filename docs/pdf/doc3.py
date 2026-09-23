import sys; sys.path.insert(0, sys.argv[1])
from pdfstyle import *
from reportlab.platypus import Paragraph, Spacer, PageBreak
from reportlab.lib.units import inch

def P(s, st, k='body'): return Paragraph(s, st[k])
def B(s, st): return Paragraph('<bullet>&bull;</bullet> ' + s, st['bullet'])
def C(s, st, b=False): return Paragraph(s, st['cellb' if b else 'cell'])

def story_fn(S, st):
    S.append(callout(
        "<b>The short version.</b> An order is handled by three people and re-typed into three "
        "systems before the customer hears anything. The order already exists digitally the moment "
        "sales enters it — everything after that is moving paper around a building and copying "
        "numbers between screens.<br/><br/>"
        "At <b>45 to 95 orders a day</b>, small per-order savings compound into most of a full-time "
        "role.", st))
    S.append(Spacer(1, 10))

    # ---------- VOLUME
    S.append(P("The numbers we are working with", st, 'h1'))
    v = [[C("Month (2026)", st, True), C("Orders", st, True), C("Working days", st, True), C("Orders per day", st, True)]]
    for r in [("January","900","20","45.0"),("February","1,423","20","71.2"),("March","2,064","23","89.7"),
              ("April","1,990","21","94.8"),("May","1,403","20","70.2"),("June","1,506","22","68.5")]:
        v.append([C(r[0], st), C(r[1], st), C(r[2], st), C(r[3], st)])
    S.append(table(v, [1.7*inch, 1.6*inch, 1.6*inch, 1.7*inch], st))
    S.append(Spacer(1, 6))
    S.append(P("Average <b>2.2 lines per order</b>, with the largest at 25. Busy months run close to "
               "<b>90 orders a day</b>. Every minute saved per order is roughly <b>1.5 hours a day</b> "
               "at that volume.", st))

    # ---------- CURRENT
    S.append(P("How it works today", st, 'h1'))
    S.append(P("Orders arrive three different ways, and two of them start on paper.", st))

    S.append(P("Route 1 — phone or customer PO", st, 'h2'))
    cur = [[C("#", st, True), C("Step", st, True), C("Who", st, True), C("Est. min", st, True)]]
    for r in [("1","Takes the call or PO, enters a sales order","Sales","—"),
              ("2","Prints the sales order","Sales","0.5"),
              ("3","Walks it to shipping","Sales","0.3"),
              ("4","Sorted into FedEx, Freight or Customer Pickup","Hannah","0.2"),
              ("5","Re-types the order into FedEx or the freight portal","Hannah","3.0"),
              ("6","Verifies the address against the PO, when one is attached","Hannah","1.5"),
              ("7","Prints labels and shipping documents","Hannah","0.5"),
              ("8","Peels the top off the label, sticks it to the paperwork","Hannah","0.5"),
              ("9","Puts it in the pile for the bookkeeper","Hannah","0.2"),
              ("10","Types the tracking number into the system","Sharon","1.5"),
              ("11","Sends the invoice to the customer","Sharon","1.0")]:
        cur.append([C(r[0], st), C(r[1], st), C(r[2], st), C(r[3], st)])
    S.append(table(cur, [0.35*inch, 3.85*inch, 1.1*inch, 0.85*inch], st))
    S.append(Spacer(1, 6))
    S.append(P("Roughly <b>9 minutes per order</b> of handling, <i>before</i> anyone picks or packs "
               "anything. These are estimates — see the note on measuring them.", st, 'note'))

    S.append(P("Route 2 — Amazon", st, 'h2'))
    S.append(P("Orders arrive from Amazon, are <b>printed</b>, then <b>typed by hand into "
               "QuickBooks</b>, then handed to Hannah. So Amazon orders never exist in USSCOS at "
               "all, and the same details are keyed twice before anyone picks them.", st))
    S.append(P("These also carry a <b>same-day ship obligation</b>, and nothing in the current "
               "process marks them as more urgent than anything else in the pile. They are urgent "
               "because someone recognises the printout.", st))

    S.append(P("Route 3 — customer pickup (CPU)", st, 'h2'))
    S.append(P("The packing list goes into a file holder, the clerk collects it and pulls the order "
               "into the CPU area. No carrier, but the same paper trail — and <b>no record of who "
               "collected it or when</b>, which matters when a customer says it never arrived.", st))

    S.append(P("Coming soon — the website", st, 'h2'))
    S.append(P("Products will be pushed to a website and those orders will come into USSCOS "
               "directly. Worth building the channel model to expect it now rather than retrofitting "
               "a fourth route later.", st))

    S.append(P("And separately — picking and packing", st, 'h2'))
    for b in ["Orders are gathered, <b>counted by hand across all of them</b>, then someone walks the warehouse to pick.",
              "There is no pick list, so the counting is repeated mentally every time.",
              "Packing is unverified. <b>Wrong items get packed and shipped</b>, which comes back as a return and puts the stock count out.",
              "Orders that cannot ship yet are <b>a physical pile Hannah manages</b>, and she <b>repeatedly checks whether product has arrived</b> to release them."]:
        S.append(B(b, st))

    S.append(PageBreak())

    # ---------- BOTTLENECKS
    S.append(P("Where the time actually goes", st, 'h1'))
    bn = [[C("Bottleneck", st, True), C("What it costs", st, True), C("Fixed by the ERP?", st, True)]]
    for r in [
        ("The same data typed three times", "Order into USSCOS or QuickBooks, again into FedEx, tracking back again", "<b>Yes</b>"),
        ("Paper walks the building", "Sales to Hannah to Sharon, three handoffs per order", "<b>Yes</b>"),
        ("The bookkeeper is a queue", "Nothing reaches the customer until Sharon works through her pile", "<b>Yes</b>"),
        ("Address checked only sometimes", "Only when a PO happens to be attached, and by eye", "<b>Yes</b>"),
        ("Backorders live in a pile", "Hannah polls for arrivals rather than being told", "<b>Yes</b>"),
        ("No pick list", "Quantities counted by hand across orders, repeatedly", "<b>Yes</b>"),
        ("One trip per order", "The same rack visited many times a day", "<b>Yes</b>"),
        ("Packing is unverified", "Wrong shipments, returns, and stock that no longer matches", "<b>Yes</b>"),
        ("Amazon keyed by hand", "Printed, re-typed, and invisible to USSCOS", "<b>Yes</b>, with the Amazon link"),
        ("Deciding the carrier", "Still a judgement about cost and service", "No — stays human"),
        ("Loading the truck", "Physical work", "No"),
    ]:
        bn.append([C(r[0], st), C(r[1], st), C(r[2], st)])
    S.append(table(bn, [1.8*inch, 3.4*inch, 1.4*inch], st))

    # ---------- FUTURE
    S.append(P("How it would work", st, 'h1'))
    S.append(P("Same people, same building. The order stops being paper and the copying stops.", st))
    fut = [[C("#", st, True), C("Step", st, True), C("Who", st, True), C("Change", st, True)]]
    for r in [("1","Order entered, or pulled in automatically from Amazon","Sales / system","<b>Amazon keying gone</b>"),
              ("1b","Amazon orders land labelled and flagged urgent, at the top of the queue","System","<b>Same-day protected</b>"),
              ("2","Order appears in the shipping queue the moment it is confirmed","—","<b>No printing, no walking</b>"),
              ("3","Shipping builds a pick list across several orders at once","Hannah","<b>One trip, not many</b>"),
              ("4","Picker scans each item; wrong or excess is refused at the shelf","Picker","<b>Wrong picks stopped</b>"),
              ("5","Packer rescans at the bench to confirm the carton","Packer","<b>Wrong packs stopped</b>"),
              ("6","Customer PO shown on screen beside the address","Hannah","No paperwork to open"),
              ("7","Carrier rated and label printed from the order","Hannah","<b>No re-typing</b>"),
              ("8","Tracking captured automatically. Freight PRO arrives later, when the carrier raises the BOL","—","<b>Step removed</b>"),
              ("9","Invoice raised and emailed with tracking on it","—","<b>Step removed</b>"),
              ("10","Stock deducted as it ships","—","Count stays true"),
              ("11","Backorders release themselves when stock arrives","—","<b>No more checking</b>")]:
        fut.append([C(r[0], st), C(r[1], st), C(r[2], st), C(r[3], st)])
    S.append(table(fut, [0.35*inch, 3.2*inch, 1.05*inch, 1.55*inch], st))

    S.append(P("Several pickers, one queue", st, 'h2'))
    S.append(P("There are several shipping people, and whether each gets their own pick list or "
               "everyone works one general list is still open. The usual answer avoids choosing:", st))
    for b in ["<b>One shared queue, and picking an order claims it.</b> Whoever starts an order holds it, and it disappears from everyone else's list.",
              "No one has to assign work in advance, and nobody picks the same order twice.",
              "A claimed order that goes quiet can be released back, so a person going home does not strand it.",
              "Progress is already stored per order rather than per person, so one picker can finish what another started."]:
        S.append(B(b, st))
    S.append(Spacer(1, 4))
    S.append(P("Worth deciding after the pilot rather than before it — the right answer depends on "
               "whether pickers naturally work whole orders or split a trip between several.", st, 'note'))

    S.append(P("And the orders that cannot ship", st, 'h2'))
    S.append(P("Today these are a physical pile that Hannah manages, checking repeatedly whether "
               "product has arrived. In USSCOS they become a list, worked out from stock rather "
               "than memory:", st))
    for b in ["Each order is checked against available stock — on hand, less what is already committed elsewhere.",
              "The order is marked fillable, partly fillable, or blocked.",
              "<b>The screen names the product holding it up</b>, because the question actually asked is \"what are we waiting on\".",
              "<b>A receipt releases the orders it unblocks.</b> Stock arriving is what tells Hannah, instead of Hannah going to look."]:
        S.append(B(b, st))
    S.append(Spacer(1, 6))
    S.append(callout("<b>The same arithmetic answers production.</b> Stock, less what is committed, "
                     "against what is ordered. Read one way it says which orders cannot ship. Read "
                     "the other, it says which paint to make — the problem of making stock we do not "
                     "need while customers wait on stock we do.", st, GREEN, '#f0fdf4'))

    S.append(PageBreak())

    # ---------- SAVINGS
    S.append(P("What it saves", st, 'h1'))
    sv = [[C("Step removed or shortened", st, True), C("Min saved", st, True), C("Why", st, True)]]
    for r in [("Printing and walking the order","0.8","The order is already digital"),
              ("Re-typing into FedEx or freight","2.5","Address, weight and service come from the order"),
              ("Checking the address","1.0","PO shown beside it instead of opened"),
              ("Label peeled and stapled to paper","0.7","Nothing to attach it to"),
              ("Tracking typed into the system","1.5","Captured from the carrier"),
              ("Invoice sent by hand","1.0","Sent on shipment"),
              ("Counting quantities across orders","1.0","The pick list does the arithmetic")]:
        sv.append([C(r[0], st), C(r[1], st), C(r[2], st)])
    sv.append([C("<b>Total per order</b>", st, True), C("<b>~8.5</b>", st, True), C("Conservative — excludes walking saved by batch picking", st)])
    S.append(table(sv, [2.6*inch, 0.9*inch, 3.1*inch], st))
    S.append(Spacer(1, 10))

    hrs = [[C("At this volume", st, True), C("Orders/day", st, True), C("If 8.5 min saved", st, True), C("If only 4 min saved", st, True)]]
    for r in [("Quiet month","45","6.4 hrs/day","3.0 hrs/day"),
              ("Typical","70","9.9 hrs/day","4.7 hrs/day"),
              ("Busy month","90","12.8 hrs/day","6.0 hrs/day")]:
        hrs.append([C(r[0], st), C(r[1], st), C(r[2], st), C(r[3], st)])
    S.append(table(hrs, [1.6*inch, 1.3*inch, 1.9*inch, 1.8*inch], st))
    S.append(Spacer(1, 8))
    S.append(callout(
        "<b>Treat these as estimates, not a promise.</b> They are built from the steps described, not "
        "from a stopwatch. <b>Time one normal day</b> — how long Hannah spends per order in FedEx, how "
        "long Sharon spends on tracking and invoices — and the real figure will be close to one of "
        "these columns. Even the pessimistic column is half a person a day.", st, AMBER, '#fffbeb'))
    S.append(P("The savings not in the table", st, 'h2'))
    for b in ["<b>Fewer wrong shipments.</b> Every one costs a return, a replacement, the freight both ways, and a stock count that no longer matches.",
              "<b>Customers told sooner.</b> Tracking goes out on shipment rather than when the pile reaches Sharon.",
              "<b>Backorders stop being watched.</b> The system releases them when stock lands.",
              "<b>Sharon stops being the bottleneck</b> and reviews exceptions instead of typing tracking numbers."]:
        S.append(B(b, st))

    S.append(PageBreak())

    # ---------- WON'T FIX
    S.append(P("The wrong shipments", st, 'h1'))
    S.append(P("Wrong shipments happen <b>several times a week</b>. Taking that as three, it is "
               "roughly <b>150 a year</b>, and each one costs more than it first appears.", st))
    w = [[C("What a wrong shipment costs", st, True), C("Note", st, True)]]
    for r in [("Freight out on the wrong goods", "Already spent"),
              ("Freight back", "Usually ours"),
              ("Freight out again on the right goods", "Paid twice for one sale"),
              ("Picking and packing a second time", "Labour, twice"),
              ("Admin — the call, the credit, the re-order", "Sales and bookkeeping"),
              ("<b>The stock count goes wrong twice</b>", "Wrong item left, right item did not — and the return may never be recorded"),
              ("The customer relationship", "Not costed here, and not nothing")]:
        w.append([C(r[0], st), C(r[1], st)])
    S.append(table(w, [3.4*inch, 3.2*inch], st))
    S.append(Spacer(1, 8))
    S.append(callout(
        "<b>This is the strongest single argument for scanning.</b> At 150 a year, even a "
        "conservative $75 of hard cost each is over <b>$11,000 a year</b> — before counting the "
        "inventory drift they cause or the customers they annoy. A scan at the bench refuses the "
        "wrong item before the carton closes, which is the only point where stopping it is cheap.",
        st, GREEN, '#f0fdf4'))
    S.append(P("It also explains part of the inventory problem. Roughly 150 unplanned returns a "
               "year, arriving with no process to record them, is precisely the kind of leak that "
               "produces a count nobody can explain.", st))

    S.append(P("What it will not fix, and what to watch", st, 'h1'))
    S.append(P("Worth being straight about these — they decide whether the rollout succeeds.", st))
    for b in ["<b>Scanning only helps where barcodes exist.</b> Today: 38% of items have a retail barcode, 49% a case barcode. For the rest the SKU is typed. That works, but it is not instant — and prioritising barcodes on fast movers matters more than reaching every item.",
              "<b>A pick list is only as good as the stock figure.</b> If the count is wrong the list sends someone to an empty rack. This is why receiving, counting and returns come <i>before</i> the clever picking.",
              "<b>Wi-Fi in the racks.</b> If coverage drops where the paint is, scanning stalls. Worth walking the building with a device before buying several.",
              "<b>Someone still confirms the order.</b> Orders start as drafts and only reach shipping once confirmed. If that step is skipped they stay invisible — the digital equivalent of the paper never being walked over.",
              "<b>Carrier choice stays a judgement.</b> The system can rate and compare; deciding freight versus parcel is still a person.",
              "<b>Adoption is the real risk.</b> If scanning is slower than what people do now, they will work around it, and the count will rot exactly as it does today. Every screen has to be quicker than the paper it replaces."]:
        S.append(B(b, st))

    # ---------- RECOMMENDATIONS
    S.append(P("What I would do, in order", st, 'h1'))
    rec = [[C("", st, True), C("Change", st, True), C("Why here", st, True)]]
    for r in [
        ("1","Stock movements first — receiving, shipping deduction, returns, adjustments",
         "Everything else depends on the count being real"),
        ("2","Opening physical count",
         "Once there are screens to keep it true"),
        ("3","Consolidated pick list, picked by scan",
         "Biggest single time saving in the warehouse, and it stops wrong picks"),
        ("4","Pack verification at the bench",
         "Catches what picking missed, before it ships"),
        ("5","Customer PO on the order, with an address-verified tick",
         "Replaces opening paperwork, and makes the check consistent"),
        ("6","FedEx labels and tracking",
         "Removes the re-typing and the tracking hand-off entirely"),
        ("7","Invoice and tracking emailed on shipment",
         "Takes the bookkeeper off the critical path"),
        ("8","Automatic backorder release",
         "Ends the checking"),
        ("9","Amazon orders pulled in automatically",
         "Ends printing and double keying; also confirms shipment back to Amazon on time"),
        ("10","Freight quotes from Kuebix",
         "Last of the re-typing, and dependent on their API being enabled"),
    ]:
        rec.append([C(r[0], st, True), C(r[1], st), C(r[2], st)])
    S.append(table(rec, [0.3*inch, 3.4*inch, 2.9*inch], st))
    S.append(Spacer(1, 8))
    S.append(callout(
        "<b>Why this order and not the obvious one.</b> The tempting start is FedEx and the pick list, "
        "because that is where the visible pain is. But a pick list built on a stock figure nobody "
        "trusts sends pickers to empty racks, and the crew stops using it within a fortnight. Get the "
        "count honest first and the rest lands on something solid.", st, GREEN, '#f0fdf4'))

    # ---------- QUESTIONS
    S.append(P("Questions this raised", st, 'h1'))
    for b in ["<b>Where will the label printers go?</b> One at the pack bench per picker, or a shared printer — decides whether a label prints where the work happens or someone walks for it.",
              "<b>Own pick list per person, or one shared queue?</b> Recommendation above is a shared queue with claiming, but worth settling after the pilot.",
              "<b>Should a customer pickup be signed for?</b> There is no record today of who collected or when, which is awkward when an order is disputed.",
              "<b>Does Amazon need confirming back the same day?</b> It is fully manual now, so a missed confirmation is invisible until account health drops.",
              "<b>Who is the pilot group</b>, and when can they give it a full day with real orders?"]:
        S.append(B(b, st))
    S.append(Spacer(1, 6))
    S.append(P("Answered since the first draft: the website channel is coming and will feed USSCOS "
               "directly; Amazon is entirely manual today; wrong shipments run several times a week; "
               "customer pickup runs on a file holder and the CPU area; and the freight PRO number "
               "comes back from the carrier once they have entered the shipment to raise the BOL — "
               "so it arrives <b>after</b> the order ships, not with the label.", st, 'note'))
    S.append(Spacer(1, 8))
    S.append(callout("<b>A note on figures from USSCOS.</b> The system is still in testing, so order "
                     "and stock counts inside it describe test data rather than the business. Live "
                     "figures in this document come from imported QuickBooks history, which is real.",
                     st, GREY, '#f9fafb'))

build(sys.argv[2],
      "Order to Shipment",
      "How it works today, how it would work, and what it saves &nbsp;·&nbsp; 23 September 2026",
      story_fn)
print("written:", sys.argv[2])
