"""
qbXML request building and response parsing.

This is what QODBC sells. Underneath its SQL veneer it builds exactly these documents and
hands them to the same Intuit request processor, so writing them ourselves costs a few
days once instead of a licence every year.

Nothing here touches Windows or COM, so all of it is testable on any machine - see
test_qbxml.py. qb_bridge.py supplies the transport.

TWO THINGS qbXML WILL PUNISH YOU FOR

1. ELEMENT ORDER IS PART OF THE SCHEMA. qbXML is a sequence, not a mapping: <TxnDate>
   before <RefNumber> is valid and the reverse is a parse error, with a message that does
   not say so. Every builder below writes fields in schema order and the order is not
   alphabetical, not payload order, and must not be "tidied".

2. statusCode="1" IS NOT AN ERROR. On a query it means "nothing matched", which is the
   normal answer when we ask whether an invoice already exists. Treating it as a failure
   would make every first write look broken.
"""

from __future__ import annotations

from decimal import Decimal, InvalidOperation
from xml.etree import ElementTree as ET
from xml.sax.saxutils import escape

# The qbXML spec level we ask for. 16.0 is widely supported; the processor can be asked
# what the running QuickBooks actually supports, and qb_bridge negotiates down if needed.
DEFAULT_VERSION = "16.0"

# Status codes worth naming. Everything else is reported with whatever QuickBooks said.
STATUS_OK = 0
STATUS_NO_MATCH = 1          # query matched nothing - expected, not a failure
STATUS_NAME_EXISTS = 3100    # a list name we tried to add is already there
STATUS_REF_NOT_FOUND = 3140  # a CustomerRef or ItemRef that does not exist in the file


class QBXMLError(Exception):
    """A request QuickBooks refused. Carries the code so callers can branch on it."""

    def __init__(self, code: int, message: str, request_id: str = ""):
        super().__init__(f"[{code}] {message}" + (f" (request {request_id})" if request_id else ""))
        self.code = code
        self.message = message
        self.request_id = request_id


# --------------------------------------------------------------------- helpers

def _esc(value) -> str:
    return escape("" if value is None else str(value))


def _el(tag: str, value) -> str:
    """One element, or nothing at all when the value is empty.

    Omitting is deliberate: qbXML rejects <PONumber></PONumber> on fields it considers
    optional, so an absent value must be an absent element rather than an empty one.
    """
    if value is None:
        return ""
    text = str(value).strip()
    return f"<{tag}>{_esc(text)}</{tag}>" if text else ""


def _ref(tag: str, full_name) -> str:
    """A reference to something on a QuickBooks list, by the name QuickBooks uses."""
    if not full_name or not str(full_name).strip():
        return ""
    return f"<{tag}><FullName>{_esc(str(full_name).strip())}</FullName></{tag}>"


def _amount(value) -> str:
    """Two decimal places, which is what QuickBooks stores. Quantities are not money."""
    if value is None or str(value).strip() == "":
        return ""
    try:
        return str(Decimal(str(value)).quantize(Decimal("0.01")))
    except (InvalidOperation, ValueError):
        raise QBXMLError(0, f"Not a number: {value!r}")


def _qty(value) -> str:
    """
    Quantities keep their precision - QuickBooks allows five decimal places.

    Formatted with 'f' rather than str(). Decimal.normalize() alone renders a round
    number in scientific notation - '20.0000' becomes '2E+1' and '1000.0000' becomes
    '1E+3' - and our quantities arrive from MySQL with exactly those trailing zeros. A
    thousand cases sent as 1E+3 is the kind of thing that is only ever found afterwards.
    """
    if value is None or str(value).strip() == "":
        return ""
    try:
        return format(Decimal(str(value)).normalize(), "f")
    except (InvalidOperation, ValueError):
        raise QBXMLError(0, f"Not a quantity: {value!r}")


def envelope(body: str, version: str = DEFAULT_VERSION, on_error: str = "stopOnError") -> str:
    """
    Wrap one or more *Rq elements in the document QuickBooks expects.

    stopOnError rather than continueOnError: a batch that half-applied would leave us
    acknowledging records we cannot prove were written. One request per document keeps
    the failure boundary exactly where the acknowledgment boundary is.
    """
    return (
        '<?xml version="1.0" encoding="utf-8"?>\n'
        f'<?qbxml version="{version}"?>\n'
        "<QBXML>\n"
        f'  <QBXMLMsgsRq onError="{on_error}">\n'
        f"{body}\n"
        "  </QBXMLMsgsRq>\n"
        "</QBXML>"
    )


# ------------------------------------------------------------------- queries

def invoice_query_by_ref(ref_number: str, request_id: str = "1") -> str:
    """
    The idempotency check. Asked before every write, so a crash between writing and
    acknowledging costs a duplicate lookup rather than a duplicate invoice.
    """
    return envelope(
        f'    <InvoiceQueryRq requestID="{_esc(request_id)}">\n'
        f"      {_el('RefNumber', ref_number)}\n"
        "      <IncludeLineItems>false</IncludeLineItems>\n"
        "    </InvoiceQueryRq>"
    )


def sales_order_query_by_ref(ref_number: str, request_id: str = "1") -> str:
    return envelope(
        f'    <SalesOrderQueryRq requestID="{_esc(request_id)}">\n'
        f"      {_el('RefNumber', ref_number)}\n"
        "      <IncludeLineItems>false</IncludeLineItems>\n"
        "    </SalesOrderQueryRq>"
    )


def payment_query_by_ref(ref_number: str, request_id: str = "1") -> str:
    """
    Payments are matched on RefNumber too, but a payment's RefNumber is the check number,
    which is NOT unique - two customers can both write check 1001. The caller must also
    compare the customer before treating a hit as the same payment.
    """
    return envelope(
        f'    <ReceivePaymentQueryRq requestID="{_esc(request_id)}">\n'
        f"      {_el('RefNumber', ref_number)}\n"
        "    </ReceivePaymentQueryRq>"
    )


def customer_query_by_name(full_name: str, request_id: str = "1") -> str:
    return envelope(
        f'    <CustomerQueryRq requestID="{_esc(request_id)}">\n'
        f"      {_el('FullName', full_name)}\n"
        "    </CustomerQueryRq>"
    )


# -------------------------------------------------------------------- writes

def _tax_code(is_taxable) -> str:
    """QuickBooks' two built-in sales tax codes. Any company file has both."""
    truthy = str(is_taxable).strip().lower() in ("1", "true", "t", "yes", "y")
    return "Tax" if truthy else "Non"


def _invoice_line(line: dict) -> str:
    """
    InvoiceLineAdd, in schema order:
        ItemRef, Desc, Quantity, UnitOfMeasure, Rate, ClassRef, Amount, ...,
        SalesTaxCodeRef, ...

    Rate is sent, not Amount. QuickBooks then computes the extension itself, and the
    caller compares its total against ours - see check_total(). Sending both invites a
    silent disagreement where QuickBooks keeps one and we believe the other.

    SalesTaxCodeRef is sent even though the invoice carries a 0% tax item and the tax
    therefore computes to zero either way. It costs nothing and it keeps the record
    honest: which lines were taxable is a fact about the sale, and a year from now
    nobody should have to infer it.
    """
    return (
        "        <InvoiceLineAdd>\n"
        f"          {_ref('ItemRef', line.get('ItemRefFullName'))}\n"
        f"          {_el('Desc', line.get('Desc'))}\n"
        f"          {_el('Quantity', _qty(line.get('Quantity')))}\n"
        f"          {_el('UnitOfMeasure', line.get('UOM'))}\n"
        f"          {_el('Rate', _amount(line.get('Rate')))}\n"
        f"          {_ref('SalesTaxCodeRef', _tax_code(line.get('is_taxable')))}\n"
        "        </InvoiceLineAdd>"
    )


def _tax_line(rec: dict) -> str:
    """
    Sales tax as an ordinary line, which is the only way QuickBooks will accept our
    figure. Sent as Amount rather than Rate because it is a flat charge, not a unit price.

    Only for tax USSCOS itself charged. A marketplace order's tax was collected and
    remitted by Amazon - it is not ours, we never owe it, and putting it on the invoice
    would inflate both revenue and the liability account. It stays on the USSCOS record
    in marketplace_tax_collected, where the tax screen reports it separately.
    """
    if str(rec.get("tax_source") or "").strip().lower() != "usscos":
        return ""

    amount = _amount(rec.get("tax_amount"))
    if not amount or Decimal(amount) == 0:
        return ""

    item = rec.get("TaxLineItemRef")
    if not item:
        raise QBXMLError(
            0,
            f"Invoice {rec.get('RefNumber')} has {amount} of sales tax but no QuickBooks "
            "item to post it to. Set QB_TAX_LINE_ITEM.",
        )

    # The reason line records WHY this rate applied - county, or the fallback that was
    # used. Reconciling a tax figure two years later without it is guesswork.
    desc = rec.get("tax_reason") or "Sales tax"
    rate = rec.get("tax_rate_applied")
    if rate:
        try:
            desc = f"{desc} ({Decimal(str(rate)) * 100:.3f}%)"
        except (InvalidOperation, ValueError):
            pass

    return (
        "        <InvoiceLineAdd>\n"
        f"          {_ref('ItemRef', item)}\n"
        f"          {_el('Desc', desc)}\n"
        f"          {_el('Amount', amount)}\n"
        f"          {_ref('SalesTaxCodeRef', 'Non')}\n"
        "        </InvoiceLineAdd>"
    )


def invoice_add(rec: dict, request_id: str = "1", version: str = DEFAULT_VERSION) -> str:
    """
    One invoice.

    Field order below is the InvoiceAdd sequence from the qbXML spec. It is not
    alphabetical and not the order of our payload. Do not sort it.
    """
    parts = [_invoice_line(l) for l in (rec.get("lines") or [])]

    if not parts:
        raise QBXMLError(0, f"Invoice {rec.get('RefNumber')} has no lines")

    tax = _tax_line(rec)
    if tax:
        parts.append(tax)

    lines = "\n".join(parts)

    body = (
        f'    <InvoiceAddRq requestID="{_esc(request_id)}">\n'
        "      <InvoiceAdd>\n"
        f"        {_ref('CustomerRef', rec.get('CustomerRefFullName'))}\n"
        f"        {_el('TxnDate', rec.get('TxnDate'))}\n"
        f"        {_el('RefNumber', rec.get('RefNumber'))}\n"
        f"        {_el('PONumber', rec.get('PONumber'))}\n"
        f"        {_ref('TermsRef', rec.get('TermsRefFullName'))}\n"
        f"        {_el('DueDate', rec.get('DueDate'))}\n"
        f"        {_el('ShipDate', rec.get('ShipDate'))}\n"
        f"        {_ref('ShipMethodRef', rec.get('ShipMethodRefFullName'))}\n"
        f"        {_ref('ItemSalesTaxRef', rec.get('ItemSalesTaxRef'))}\n"
        f"        {_el('Memo', rec.get('Memo'))}\n"
        f"{lines}\n"
        "      </InvoiceAdd>\n"
        "    </InvoiceAddRq>"
    )
    return envelope(body, version)


def _sales_order_line(line: dict) -> str:
    return (
        "        <SalesOrderLineAdd>\n"
        f"          {_ref('ItemRef', line.get('ItemRefFullName'))}\n"
        f"          {_el('Desc', line.get('Desc'))}\n"
        f"          {_el('Quantity', _qty(line.get('Quantity')))}\n"
        f"          {_el('Rate', _amount(line.get('Rate')))}\n"
        "        </SalesOrderLineAdd>"
    )


def sales_order_add(rec: dict, request_id: str = "1", version: str = DEFAULT_VERSION) -> str:
    lines = "\n".join(_sales_order_line(l) for l in (rec.get("lines") or []))

    if not lines:
        raise QBXMLError(0, f"Sales order {rec.get('RefNumber')} has no lines")

    body = (
        f'    <SalesOrderAddRq requestID="{_esc(request_id)}">\n'
        "      <SalesOrderAdd>\n"
        f"        {_ref('CustomerRef', rec.get('CustomerRefFullName'))}\n"
        f"        {_el('TxnDate', rec.get('TxnDate'))}\n"
        f"        {_el('RefNumber', rec.get('RefNumber'))}\n"
        f"        {_el('PONumber', rec.get('PONumber'))}\n"
        f"        {_ref('ShipMethodRef', rec.get('ShipMethodRefFullName'))}\n"
        f"        {_el('Memo', rec.get('Memo'))}\n"
        f"{lines}\n"
        "      </SalesOrderAdd>\n"
        "    </SalesOrderAddRq>"
    )
    return envelope(body, version)


def receive_payment_add(rec: dict, request_id: str = "1", version: str = DEFAULT_VERSION) -> str:
    """
    A customer payment, applied to named invoices.

    AppliedToTxnAdd needs the invoice's TxnID, NOT its RefNumber - QuickBooks will not
    resolve a reference number here. USSCOS holds a payment back until every invoice it
    pays carries a qb_txn_id, so by the time one reaches this function the ids exist.
    An application without one is a bug upstream and is refused loudly rather than
    written as an unapplied credit nobody notices.
    """
    applications = []
    for app in (rec.get("applied_to") or []):
        txn_id = (app.get("qb_txn_id") or "").strip()
        if not txn_id:
            raise QBXMLError(
                0,
                f"Payment {rec.get('RefNumber')} applies to invoice "
                f"{app.get('RefNumber')} which has no QuickBooks TxnID yet",
            )
        applications.append(
            "        <AppliedToTxnAdd>\n"
            f"          <TxnID>{_esc(txn_id)}</TxnID>\n"
            f"          {_el('PaymentAmount', _amount(app.get('Amount')))}\n"
            "        </AppliedToTxnAdd>"
        )

    if not applications:
        raise QBXMLError(0, f"Payment {rec.get('RefNumber')} is not applied to any invoice")

    body = (
        f'    <ReceivePaymentAddRq requestID="{_esc(request_id)}">\n'
        "      <ReceivePaymentAdd>\n"
        f"        {_ref('CustomerRef', rec.get('CustomerRefFullName'))}\n"
        f"        {_el('TxnDate', rec.get('TxnDate'))}\n"
        f"        {_el('RefNumber', rec.get('RefNumber'))}\n"
        f"        {_el('TotalAmount', _amount(rec.get('TotalAmount')))}\n"
        f"        {_ref('PaymentMethodRef', rec.get('PaymentMethodRefFullName'))}\n"
        f"        {_el('Memo', rec.get('Memo'))}\n"
        + "\n".join(applications)
        + "\n      </ReceivePaymentAdd>\n"
        "    </ReceivePaymentAddRq>"
    )
    return envelope(body, version)


# ------------------------------------------------------------------ response

class Response:
    """One *Rs element: its status, and whatever record came back."""

    def __init__(self, request_id: str, code: int, severity: str, message: str, ret: dict | None):
        self.request_id = request_id
        self.code = code
        self.severity = severity
        self.message = message
        self.ret = ret or {}

    @property
    def ok(self) -> bool:
        return self.code == STATUS_OK

    @property
    def empty(self) -> bool:
        """A query that matched nothing. Normal, and not a failure."""
        return self.code == STATUS_NO_MATCH

    @property
    def txn_id(self) -> str | None:
        return self.ret.get("TxnID")

    @property
    def edit_sequence(self) -> str | None:
        """Changes on every edit. The pull direction uses it to spot an amended PO."""
        return self.ret.get("EditSequence")

    def __repr__(self) -> str:
        return f"<Response {self.code} {self.severity} {self.message!r} txn={self.txn_id}>"


def parse_response(xml: str) -> list[Response]:
    """
    Turn a qbXML response document into Response objects.

    Does NOT raise on a QuickBooks-level error - a refused request is information the
    bridge must report back to USSCOS and store against the record, not an exception
    that loses which record it belonged to. Only malformed XML raises.
    """
    try:
        root = ET.fromstring(xml)
    except ET.ParseError as e:
        raise QBXMLError(0, f"QuickBooks returned XML we could not parse: {e}")

    msgs = root.find("QBXMLMsgsRs")
    if msgs is None:
        raise QBXMLError(0, "No QBXMLMsgsRs in the response")

    out = []
    for rs in msgs:
        code = int(rs.get("statusCode", "0"))
        ret = None

        # The payload element is the one child whose tag ends in 'Ret'. Picking it by
        # shape rather than by name keeps this working for every request type.
        for child in rs:
            if child.tag.endswith("Ret"):
                ret = {g.tag: (g.text or "") for g in child}
                break

        out.append(Response(
            request_id=rs.get("requestID", ""),
            code=code,
            severity=rs.get("statusSeverity", ""),
            message=rs.get("statusMessage", ""),
            ret=ret,
        ))

    return out


def first(xml: str) -> Response:
    """The single response from a single-request document."""
    responses = parse_response(xml)
    if not responses:
        raise QBXMLError(0, "QuickBooks returned no response elements")
    return responses[0]


def check_total(expected, returned) -> None:
    """
    Compare what we billed against what QuickBooks computed.

    We send Quantity and Rate and let QuickBooks extend the lines, so a disagreement
    means it reached a different number - rounding, a price level on the customer
    record, or sales tax applied from the item. Any of those produce an invoice the
    customer is charged differently for than our records say, which is worth stopping
    for rather than discovering at reconciliation.
    """
    if expected is None or returned is None:
        return
    try:
        want = Decimal(str(expected)).quantize(Decimal("0.01"))
        got = Decimal(str(returned)).quantize(Decimal("0.01"))
    except (InvalidOperation, ValueError):
        return
    if want != got:
        raise QBXMLError(
            0,
            f"Total mismatch: USSCOS says {want}, QuickBooks computed {got}. "
            "Not acknowledged - check for a price level or a sales tax item on this customer.",
        )
