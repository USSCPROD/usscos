import sys; sys.path.insert(0, sys.argv[1])
from pdfstyle import *
from reportlab.platypus import Paragraph, Spacer, PageBreak
from reportlab.lib.units import inch

def P(s, st, k='body'): return Paragraph(s, st[k])
def B(s, st): return Paragraph('<bullet>&bull;</bullet> ' + s, st['bullet'])
def C(s, st, b=False): return Paragraph(s, st['cellb' if b else 'cell'])

def step(S, st, n, title, who, does, system, note=None):
    """One numbered step: who does what, and what the system does about it."""
    S.append(P(f'{n}. {title} <font color="#6b7280" size="9">&nbsp;— {who}</font>', st, 'h2'))
    rows = [[C('The person', st, True), C('The system', st, True)]]
    rows.append([C(does, st), C(system, st)])
    S.append(table(rows, [3.3*inch, 3.3*inch], st))
    if note:
        S.append(P(note, st, 'note'))

def story_fn(S, st):
    S.append(callout(
        "<b>What this is.</b> Every step of an order, from the moment it arrives to the money "
        "landing, as USSCOS will run it. Please read the part that is yours and mark anything "
        "that is wrong, missing, or would not survive a busy Friday.<br/><br/>"
        "<b>How to read it.</b> Steps marked <b>BUILT</b> work today and can be tried. Steps "
        "marked <b>TO COME</b> are agreed but not written yet — they are included so the whole "
        "shape is visible. Nothing here changes until everyone has had a look.", st))
    S.append(Spacer(1, 8))

    S.append(P("The short version", st, 'h1'))
    flow = [[C("", st, True), C("Stage", st, True), C("Who", st, True), C("Ends when", st, True)]]
    for r in [
        ("1", "Order entered",      "Sales",            "The order is confirmed and appears at shipping"),
        ("2", "Picked",             "Warehouse",        "Everything is on the bench, scanned"),
        ("3", "Packed and checked", "A second person",  "The box matches the pick"),
        ("4", "Shipped",            "Shipping",         "Stock comes out, the invoice is created"),
        ("5", "Label made",         "Shipping",         "Tracking number captured automatically"),
        ("6", "Invoice reviewed",   "Bookkeeping",      "Handling added and the invoice approved"),
        ("7", "Customer told",      "Nobody — automatic", "Approval sends the tracking email"),
        ("8", "Paid",               "Bookkeeping",      "Payment recorded against the invoice"),
    ]:
        flow.append([C(r[0], st, True), C(r[1], st, True), C(r[2], st), C(r[3], st)])
    S.append(table(flow, [0.3*inch, 1.5*inch, 1.5*inch, 3.3*inch], st))

    S.append(P("The biggest change is step 3 and step 7. Today nothing checks the box before it "
               "is sealed, and a person has to remember to email the customer. Both of those are "
               "where orders go wrong.", st, 'note'))

    S.append(PageBreak())

    # ---------------------------------------------------------------- 1
    S.append(P("Step by step", st, 'h1'))

    step(S, st, 1, "The order is entered", "Sales — BUILT",
         "Enter the customer, what they want, and where it is going. "
         "Put the customer's PO number on the order. "
         "If they sent a PO document, upload it to the job binder.",
         "Prices the order, works out the sales tax from the <b>delivery address</b>, "
         "and keeps the PO document with the job for good — it is still one click away "
         "years later, after the order is invoiced and closed.",
         "Note the ship-to address is what decides the tax, not the billing address. "
         "Getting it right at this step is what stops a correction later.")

    step(S, st, 2, "The order is confirmed", "Sales — BUILT",
         "Confirm the order when it is ready to be worked. Nothing is picked from a draft.",
         "It appears in the shipping queue. Until it is confirmed, shipping cannot see it.",
         "<b>Question for the room:</b> is the draft-to-confirmed step happening on every "
         "order today, or do some reach shipping another way?")

    step(S, st, 3, "Picking", "Warehouse — BUILT",
         "Open the order on the tablet at the bench and scan each item as it is picked. "
         "Scanning a case barcode counts the whole case. "
         "If something is not there, report it short and say what is missing.",
         "Refuses anything that is not on the order. Counts up as you scan. "
         "Short stock flags the order and tells whoever owns it, rather than quietly "
         "shipping less than was asked for.",
         "No paper pick list is required. The order stays on the screen and survives "
         "somebody walking away, a re-login, or the tablet going to sleep.")

    step(S, st, 4, "Packing and checking — THE NEW STEP", "A second person — BUILT",
         "As the box is filled, scan every item into it again. "
         "When it matches, seal it. "
         "If the box is right and the pick was wrong, say what happened and carry on.",
         "Checks the box against <b>what was picked</b>, not what was ordered — so a "
         "deliberate short pick is not treated as an error. "
         "Anything not on the order is refused loudly. "
         "Records who packed it, separately from who picked it.",
         "This is the step that catches the error picking cannot: scanning the label on "
         "the shelf and then grabbing from the next bay. Wrong shipments happen several "
         "times a week, and this is aimed squarely at them.")

    S.append(callout(
        "<b>Question for the room.</b> Should the person who packs be forbidden from being "
        "the person who picked? The system records both names and says so on screen when "
        "they match, but does not currently stop it — because on a thin shift that might "
        "stop work altogether. <b>Your call.</b>", st, AMBER, '#fffbeb'))

    S.append(PageBreak())

    step(S, st, 5, "Ship and invoice", "Shipping — BUILT",
         "Press Ship &amp; Invoice. Enter the ship date and any tracking or PRO numbers "
         "you already have.",
         "Bills <b>what was picked</b>, never what was ordered — a short pick is not "
         "invoiced in full. "
         "Takes the stock out of the location it came from. "
         "Creates the invoice and closes the order, or leaves it part-shipped if something "
         "is still owed.",
         "If an order ships in two goes, the second invoice bills only the remainder. "
         "That used to bill everything twice.")

    step(S, st, 6, "The label", "Shipping — PARTLY BUILT",
         "Enter the weight and how many boxes, and print the label. "
         "For freight, enter the PRO number by hand for now.",
         "Creates the FedEx label and <b>captures the tracking number itself</b> — nobody "
         "reads it off a screen and retypes it. "
         "Knows the boxes: one case of aerosol is a single box, two go in a double, three "
         "go as a double plus a single. "
         "One 5 gallon pail is a parcel; more than one is freight.",
         "TO COME: weights come with the product file, freight rating through Kuebix, and "
         "FedEx must certify us before real labels can print.")

    step(S, st, 7, "The invoice is reviewed", "Bookkeeping — BUILT",
         "Open Awaiting Review. Add handling, freight, or anything else the invoice needs. "
         "Then press Approve &amp; Send.",
         "Holds every shipped invoice until it is approved. "
         "The customer has been told <b>nothing</b> up to this point. "
         "Approving marks it reviewed and sends the tracking in one action.",
         "TO COME: handling worked out automatically, so this becomes a check rather than "
         "typing. The queue stays either way — one click to approve is very different from "
         "no one looking.")

    step(S, st, 8, "The customer is told", "Nobody — BUILT",
         "Nothing. This is the point.",
         "Emails the customer their tracking numbers as clickable links, with the invoice "
         "number and their own PO on it.",
         "Currently switched off while we are testing, so that nothing reaches a real "
         "customer by accident. It is turned on deliberately, on the server.")

    step(S, st, 9, "Payment", "Bookkeeping — BUILT",
         "Record the payment against the invoice when it arrives.",
         "Updates the balance and the A/R aging. Everything pushes to QuickBooks, which "
         "stays the book of record.",
         None)

    S.append(PageBreak())

    # ---------------------------------------------------------------- side flows
    S.append(P("When it does not go straight through", st, 'h1'))

    S.append(P("Short stock", st, 'h2'))
    for b in ["The picker reports it short and says what is missing.",
              "The order is flagged and handed back rather than shipped quietly incomplete.",
              "If part of it goes, only that part is invoiced and the rest stays owed."]:
        S.append(B(b, st))

    S.append(P("The box does not match the pick", st, 'h2'))
    for b in ["Packing will not verify it.",
              "You can still ship — the truck will not wait — but you must say why, and it is "
              "recorded as a mismatch rather than a clean check.",
              "That is how we find out where the errors actually come from."]:
        S.append(B(b, st))

    S.append(P("A customer returns something", st, 'h2'))
    for b in ["Book the return in against the original invoice.",
              "Say what condition each item came back in. <b>Only resellable goods go back on a "
              "shelf</b> — that judgment belongs to whoever is holding the pail.",
              "The credit is worked out from what they actually paid, including tax at the rate "
              "on the original invoice, and goes on a list for the bookkeeper.",
              "TO COME: raising the credit in USSCOS rather than QuickBooks."]:
        S.append(B(b, st))

    S.append(P("Amazon orders", st, 'h2'))
    for b in ["Today: printed, keyed in by hand, walked to shipping.",
              "TO COME: they arrive in USSCOS by themselves, marked Amazon, with same-day "
              "priority since they have to go out that day.",
              "Amazon collects its own sales tax, so USSCOS charges none on those orders and "
              "records what Amazon collected as a memo figure."]:
        S.append(B(b, st))

    S.append(PageBreak())
    S.append(P("Stock, in the background", st, 'h2'))
    S.append(P("None of this works if the count is wrong, so every way stock moves now has a "
               "screen. All of these are built and can be tried:", st))
    inv = [[C("What happened", st, True), C("Where it is recorded", st, True)]]
    for r in [
        ("Paint arrives from TCC or a supplier", "Receiving — scan it in, choose the location"),
        ("A delivery does not match the PO",     "Recorded as it actually arrived, and the difference goes on a queue"),
        ("An order ships",                       "Comes out of the location it was picked from"),
        ("Stock moves between buildings",        "A transfer — scanned out of one, scanned in at the other"),
        ("Something is damaged, spilled or found","An adjustment, with a reason that is chosen from a list"),
        ("A customer returns something",         "A return — only resellable stock goes back on"),
        ("Somebody counts a shelf",              "A cycle count — counted blind, reviewed, then applied"),
    ]:
        inv.append([C(r[0], st), C(r[1], st)])
    S.append(table(inv, [2.9*inch, 3.7*inch], st))

    S.append(callout(
        "<b>Counting is done blind.</b> Whoever counts is <i>not</i> shown what the system "
        "expects to be there. That is deliberate: shown the number, a tired person at the end "
        "of a shift confirms it instead of counting it, and the count becomes a copy of the "
        "figure it was meant to check.", st, GREEN, '#f0fdf4'))

    # ---------------------------------------------------------------- questions
    S.append(P("What we need you to tell us", st, 'h1'))
    S.append(P("These are decisions the system cannot make. Please answer the ones that are "
               "yours — a wrong guess here is more expensive than asking.", st))

    q = [[C("Question", st, True), C("Who should answer", st, True)]]
    for r in [
        ("Can the person who picked an order also be the one who verifies it?", "Shipping manager"),
        ("Should an order be allowed to ship if the box was never verified?", "Shipping manager"),
        ("Who chases a transfer between buildings that arrives short?", "Shipping manager"),
        ("When is the first full stock count, and who does it?", "Operations"),
        ("After that, how often should we count — and the expensive stock, the fast-moving stock, or everything in turn?", "Operations"),
        ("Who looks at a count that disagrees with the system and decides what it means?", "Operations"),
        ("Who watches the queue of deliveries that did not match their PO?", "Purchasing"),
        ("Who is allowed to correct a stock figure by hand?", "Management"),
        ("Is there a rule about returned paint going back on the shelf, or is it a judgment each time?", "Shipping manager"),
        ("What bays and racks are in 730, and how are they labeled?", "Operations"),
        ("Do our sales tax rates match what QuickBooks charges today?", "Bookkeeping"),
        ("Is the draft-to-confirmed step happening on every order?", "Sales"),
    ]:
        q.append([C(r[0], st), C(r[1], st)])
    S.append(table(q, [4.7*inch, 1.9*inch], st))

    S.append(P("Sign-off", st, 'h1'))
    S.append(P("If the part that is yours is right, sign it. If it is not, write on it — that is "
               "far more useful than agreement.", st))

    sign = [[C("Area", st, True), C("Name", st, True), C("Right / needs changing", st, True), C("Date", st, True)]]
    for area in ["Sales", "Shipping floor", "Shipping manager", "Bookkeeping", "Operations", "Management"]:
        sign.append([C(area, st, True), C("", st), C("", st), C("", st)])
    S.append(table(sign, [1.4*inch, 1.7*inch, 2.4*inch, 1.1*inch], st))

    S.append(Spacer(1, 10))
    S.append(callout(
        "<b>Nothing here is fixed.</b> It is easier to change a step now than after everyone "
        "has learned it. The two things most worth arguing about are whether packing has to be "
        "a second person, and whether an unverified order should be allowed to ship at all.",
        st, GREEN, '#f0fdf4'))

build(sys.argv[2],
      "How an Order Runs in USSCOS",
      "Step by step, from order entry to payment &nbsp;·&nbsp; for review &nbsp;·&nbsp; 25 September 2026",
      story_fn)
print("written:", sys.argv[2])
