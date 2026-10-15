"""
Tests for the qbXML layer.

    python3 bridge/test_qbxml.py

No QuickBooks, no Windows, no network. Everything here is the part that can be got wrong
silently - element order, empty elements, the "no match" status, and a total that does not
agree with ours.
"""

import sys
from xml.etree import ElementTree as ET

import qbxml

PASS, FAIL = [], []


def check(name, condition, detail=""):
    (PASS if condition else FAIL).append(name)
    print(f"  {'ok  ' if condition else 'FAIL'}  {name}" + (f"\n          {detail}" if detail and not condition else ""))


INVOICE = {
    "id": 34815,
    "RefNumber": "292140",
    "TxnDate": "2026-08-05",
    "DueDate": "2026-09-04",
    "PONumber": "PO-8871",
    "CustomerRefFullName": "THAYER HIGH SCHOOL",
    "TermsRefFullName": "Net 30",
    "ShipMethodRefFullName": "FedEx Ground",
    "Memo": None,
    "total_amount": "1164.00",
    "lines": [{
        "ItemRefFullName": "DSWFC",
        "Desc": "Fat Can - White, Case (12 x 26 oz)",
        "Quantity": "20.0000",
        "Rate": "58.2000",
        "UOM": "CS",
    }],
}


def tag_order(xml, parent_tag):
    root = ET.fromstring(xml)
    for el in root.iter(parent_tag):
        return [c.tag for c in el]
    return []


print("\n-- envelope and structure --")
x = qbxml.invoice_add(INVOICE)
check("well-formed XML", ET.fromstring(x) is not None)
check("declares a qbxml version", '<?qbxml version="16.0"?>' in x)
check("stopOnError, not continueOnError", 'onError="stopOnError"' in x)

print("\n-- element order is schema order, not ours --")
order = tag_order(x, "InvoiceAdd")
expected = ["CustomerRef", "TxnDate", "RefNumber", "PONumber",
            "TermsRef", "DueDate", "ShipMethodRef", "InvoiceLineAdd"]
check("InvoiceAdd children in spec order", order == expected, f"got {order}")

line_order = tag_order(x, "InvoiceLineAdd")
check("InvoiceLineAdd in spec order",
      line_order == ["ItemRef", "Desc", "Quantity", "UnitOfMeasure", "Rate", "SalesTaxCodeRef"],
      f"got {line_order}")

print("\n-- empty values are omitted, not sent empty --")
check("Memo=None produced no element", "<Memo>" not in x)
check("ShipDate absent produced no element", "<ShipDate>" not in x)
no_po = dict(INVOICE, PONumber="")
check("empty PONumber omitted", "<PONumber>" not in qbxml.invoice_add(no_po))

print("\n-- references use FullName --")
check("CustomerRef wraps FullName",
      "<CustomerRef><FullName>THAYER HIGH SCHOOL</FullName></CustomerRef>" in x)

print("\n-- escaping --")
nasty = dict(INVOICE, CustomerRefFullName='BOB & SONS <"BIG">')
xe = qbxml.invoice_add(nasty)
check("ampersand and angle brackets escaped", "&amp;" in xe and "&lt;" in xe)
check("still parses after escaping", ET.fromstring(xe) is not None)

print("\n-- numbers --")
check("Rate rounded to 2dp", "<Rate>58.20</Rate>" in x, x[x.find("<Rate>"):][:30])
check("Quantity keeps its own precision", "<Quantity>20</Quantity>" in x)
check("round quantities never render as scientific notation",
      "<Quantity>1000</Quantity>" in qbxml.invoice_add(
          dict(INVOICE, lines=[dict(INVOICE["lines"][0], Quantity="1000.0000")])))
check("fractional quantities survive",
      "<Quantity>1.5</Quantity>" in qbxml.invoice_add(
          dict(INVOICE, lines=[dict(INVOICE["lines"][0], Quantity="1.5000")])))

print("\n-- refusals that protect us --")
try:
    qbxml.invoice_add(dict(INVOICE, lines=[]))
    check("invoice with no lines refused", False)
except qbxml.QBXMLError:
    check("invoice with no lines refused", True)

try:
    qbxml.receive_payment_add({
        "RefNumber": "1001", "CustomerRefFullName": "X", "TotalAmount": "10",
        "applied_to": [{"RefNumber": "292140", "qb_txn_id": None, "Amount": "10"}],
    })
    check("payment applied to an invoice with no TxnID refused", False)
except qbxml.QBXMLError as e:
    check("payment applied to an invoice with no TxnID refused", "TxnID" in str(e))

try:
    qbxml.receive_payment_add({"RefNumber": "1001", "CustomerRefFullName": "X",
                               "TotalAmount": "10", "applied_to": []})
    check("unapplied payment refused", False)
except qbxml.QBXMLError:
    check("unapplied payment refused", True)

print("\n-- payment with real TxnIDs --")
pay = qbxml.receive_payment_add({
    "RefNumber": "1001", "TxnDate": "2026-08-10",
    "CustomerRefFullName": "THAYER HIGH SCHOOL",
    "TotalAmount": "1164.00", "PaymentMethodRefFullName": "Check",
    "applied_to": [{"RefNumber": "292140", "qb_txn_id": "8A2F-1754500000", "Amount": "1164.00"}],
})
check("applies by TxnID, not RefNumber",
      "<TxnID>8A2F-1754500000</TxnID>" in pay)
check("ReceivePaymentAdd in spec order",
      tag_order(pay, "ReceivePaymentAdd") ==
      ["CustomerRef", "TxnDate", "RefNumber", "TotalAmount", "PaymentMethodRef", "AppliedToTxnAdd"],
      f"got {tag_order(pay, 'ReceivePaymentAdd')}")

print("\n-- sales tax: QuickBooks will not take our figure, so we send it as a line --")
TAXED = dict(INVOICE,
             tax_source="usscos", tax_amount="90.21", tax_rate_applied="0.07750",
             tax_reason="GA - Chatham", taxable_subtotal="1164.00",
             total_amount="1254.21",
             ItemSalesTaxRef="Tax Calculated in USSCOS", TaxLineItemRef="Sales Tax")
xt = qbxml.invoice_add(TAXED)
check("invoice points at the zero-rate tax item",
      "<ItemSalesTaxRef><FullName>Tax Calculated in USSCOS</FullName></ItemSalesTaxRef>" in xt)
check("ItemSalesTaxRef sits after ShipMethodRef, before Memo",
      tag_order(xt, "InvoiceAdd") == ["CustomerRef", "TxnDate", "RefNumber", "PONumber",
                                      "TermsRef", "DueDate", "ShipMethodRef",
                                      "ItemSalesTaxRef", "InvoiceLineAdd", "InvoiceLineAdd"],
      f"got {tag_order(xt, 'InvoiceAdd')}")
check("tax went on as its own line", xt.count("<InvoiceLineAdd>") == 2)
check("tax line carries our amount", "<Amount>90.21</Amount>" in xt)
check("tax line says why that rate applied", "GA - Chatham (7.750%)" in xt)
check("tax line is itself non-taxable",
      xt.rsplit("<InvoiceLineAdd>", 1)[1].count("<FullName>Non</FullName>") == 1)

print("\n-- per-line tax codes --")
mixed = dict(TAXED, lines=[
    dict(INVOICE["lines"][0], is_taxable=1),
    dict(INVOICE["lines"][0], ItemRefFullName="FREIGHT", is_taxable=0),
])
xm = qbxml.invoice_add(mixed)
check("taxable line marked Tax", "<FullName>Tax</FullName>" in xm)
check("non-taxable line marked Non", "<FullName>Non</FullName>" in xm)
check("SalesTaxCodeRef is last in the line",
      tag_order(xm, "InvoiceLineAdd")[-1] == "SalesTaxCodeRef",
      f"got {tag_order(xm, 'InvoiceLineAdd')}")

print("\n-- marketplace tax is Amazon's, not ours --")
amz = dict(INVOICE, tax_source="marketplace", tax_amount="0.00",
           marketplace_tax_collected="74.18",
           ItemSalesTaxRef="Tax Calculated in USSCOS", TaxLineItemRef="Sales Tax")
xa = qbxml.invoice_add(amz)
check("no tax line for a marketplace order", xa.count("<InvoiceLineAdd>") == 1)
check("Amazon's collected tax never reaches QuickBooks", "74.18" not in xa)

print("\n-- no tax at all --")
xn = qbxml.invoice_add(dict(INVOICE, tax_source="none", tax_amount="0.00",
                            ItemSalesTaxRef="Tax Calculated in USSCOS"))
check("exempt invoice gets no tax line", xn.count("<InvoiceLineAdd>") == 1)

print("\n-- tax with nowhere to post it is refused --")
try:
    qbxml.invoice_add(dict(TAXED, TaxLineItemRef=""))
    check("tax with no QuickBooks item refused", False)
except qbxml.QBXMLError as e:
    check("tax with no QuickBooks item refused", "QB_TAX_LINE_ITEM" in str(e))

print("\n-- response parsing --")
OK_RESP = """<?xml version="1.0" ?><QBXML><QBXMLMsgsRs>
<InvoiceAddRs requestID="1" statusCode="0" statusSeverity="Info" statusMessage="Status OK">
<InvoiceRet><TxnID>8A2F-1754500000</TxnID><EditSequence>1754500000</EditSequence>
<RefNumber>292140</RefNumber><TotalAmount>1164.00</TotalAmount></InvoiceRet>
</InvoiceAddRs></QBXMLMsgsRs></QBXML>"""
r = qbxml.first(OK_RESP)
check("ok parsed", r.ok)
check("TxnID extracted", r.txn_id == "8A2F-1754500000")
check("EditSequence extracted", r.edit_sequence == "1754500000")

EMPTY_RESP = """<?xml version="1.0" ?><QBXML><QBXMLMsgsRs>
<InvoiceQueryRs requestID="1" statusCode="1" statusSeverity="Info"
 statusMessage="A query request did not find a matching object."/>
</QBXMLMsgsRs></QBXML>"""
e = qbxml.first(EMPTY_RESP)
check("statusCode 1 is 'empty', not an error", e.empty and not e.ok)
check("empty response has no TxnID", e.txn_id is None)

ERR_RESP = """<?xml version="1.0" ?><QBXML><QBXMLMsgsRs>
<InvoiceAddRs requestID="1" statusCode="3140" statusSeverity="Error"
 statusMessage="There is an invalid reference to QuickBooks Customer &quot;NOPE&quot;."/>
</QBXMLMsgsRs></QBXML>"""
er = qbxml.first(ERR_RESP)
check("error reported, not raised", (not er.ok) and er.code == 3140)
check("error message preserved for USSCOS", "invalid reference" in er.message)

try:
    qbxml.parse_response("<QBXML><not closed>")
    check("malformed XML raises", False)
except qbxml.QBXMLError:
    check("malformed XML raises", True)

print("\n-- total cross-check --")
try:
    qbxml.check_total("1164.00", "1164.00")
    check("matching totals pass", True)
except qbxml.QBXMLError:
    check("matching totals pass", False)

try:
    qbxml.check_total("1164.00", "1231.43")
    check("mismatched totals refused", False)
except qbxml.QBXMLError as e:
    check("mismatched totals refused", "1231.43" in str(e))

try:
    qbxml.check_total("1164.00", "1164.004")
    check("sub-cent noise tolerated", True)
except qbxml.QBXMLError:
    check("sub-cent noise tolerated", False)

print("\n-- queries --")
q = qbxml.invoice_query_by_ref("292140")
check("query asks by RefNumber", "<RefNumber>292140</RefNumber>" in q)
check("query skips line items", "<IncludeLineItems>false</IncludeLineItems>" in q)

print(f"\n{len(PASS)} passed, {len(FAIL)} failed")
if FAIL:
    print("FAILED: " + ", ".join(FAIL))
sys.exit(1 if FAIL else 0)
