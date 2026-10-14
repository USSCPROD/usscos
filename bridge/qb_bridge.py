#!/usr/bin/env python3
"""
The QuickBooks bridge. Runs on the Windows machine that hosts QuickBooks Desktop.

    python qb_bridge.py --simulate     no QuickBooks needed, writes nothing to it
    python qb_bridge.py --dry-run      fetch and show, acknowledge nothing
    python qb_bridge.py                the real thing

Needs pywin32 and Intuit's free QuickBooks Desktop SDK for the real mode. NOT QODBC -
see the note on the QuickBooks class.

It PULLS from USSCOS over outbound HTTPS and writes through Intuit's request processor. Nothing reaches into
this machine, so there is no inbound firewall rule to open. See docs/QUICKBOOKS_SYNC.md.

THE ONE RULE THAT MATTERS. Acknowledge only what QuickBooks has confirmed. If this script
dies between writing an invoice and sending the ack, the record stays pending and comes
back next run - so every write is preceded by a RefNumber lookup, and a record that is
already there is acknowledged with the TxnID QuickBooks already has rather than written
again. That is what makes a crash boring instead of a duplicate invoice.

ORDER MATTERS. Sales orders and invoices before payments: a payment references its
invoices by RefNumber, so they must exist first. USSCOS also holds payments back until
every invoice they pay carries a qb_txn_id, so the two halves agree.
"""

import argparse
import json
import os
import sys
import time
from datetime import datetime

import requests

import qbxml

BASE = os.environ.get("USSCOS_URL", "https://os.usscproducts.com")
KEY = os.environ.get("USSCOS_API_KEY", "")
TIMEOUT = 120

# (endpoint type, singular name used by /ack, qbXML transaction type)
KINDS = [
    ("sales_orders", "sales_order", "SalesOrder"),
    ("invoices", "invoice", "Invoice"),
    ("payments", "payment", "ReceivePayment"),
]


def log(msg):
    print(f"{datetime.now():%Y-%m-%d %H:%M:%S}  {msg}", flush=True)


# --------------------------------------------------------------------- USSCOS

def session():
    if not KEY:
        sys.exit("USSCOS_API_KEY is not set. Refusing to run without it.")
    s = requests.Session()
    s.headers.update({"X-API-Key": KEY, "Accept": "application/json"})
    return s


def fetch(s, kind, limit):
    r = s.get(f"{BASE}/api/qb/pending", params={"type": kind, "limit": limit}, timeout=TIMEOUT)
    r.raise_for_status()
    return r.json()


def acknowledge(s, batch_id, results):
    if not results:
        return
    r = s.post(f"{BASE}/api/qb/ack",
               json={"batch_id": batch_id, "results": results}, timeout=TIMEOUT)
    r.raise_for_status()
    out = r.json()
    log(f"  acked {out.get('acknowledged', 0)}, failed {out.get('failed', 0)}")
    if out.get("rejected"):
        log(f"  REJECTED by USSCOS: {out['rejected']}")


# ----------------------------------------------------------------- QuickBooks

class QuickBooks:
    """
    Talks to QuickBooks Desktop through Intuit's own request processor.

    WHY NOT QODBC. QODBC is a commercial SQL veneer over exactly this interface - it
    builds qbXML and hands it to the same COM object. Paying yearly for the convenience
    of writing SELECT instead of <InvoiceQueryRq> is a poor trade when the queries are
    four shapes that never change. See bridge/qbxml.py.

    The connection is local: COM reaches QuickBooks on this machine only, which is why
    this script runs on the QuickBooks box rather than on the server.
    """

    # (type, singular) -> (query builder, add builder, qbXML Ret total field)
    BUILDERS = {
        "Invoice":        (qbxml.invoice_query_by_ref,     qbxml.invoice_add,         "TotalAmount"),
        "SalesOrder":     (qbxml.sales_order_query_by_ref, qbxml.sales_order_add,     "TotalAmount"),
        "ReceivePayment": (qbxml.payment_query_by_ref,     qbxml.receive_payment_add, "TotalAmount"),
    }

    def __init__(self, app_name="USSCOS Bridge"):
        import win32com.client  # imported here so --simulate runs on any machine

        self.rp = win32com.client.Dispatch("QBXMLRP2.RequestProcessor")
        self.rp.OpenConnection2("", app_name, 1)   # 1 = localQBD
        self.ticket = self.rp.BeginSession("", 2)  # 2 = qbFileOpenDoNotCare
        self.version = self._negotiate_version()
        log(f"  QuickBooks session open, using qbXML {self.version}")

    def _negotiate_version(self):
        """
        Ask QuickBooks what it speaks rather than assuming.

        A request sent at a spec level the installed QuickBooks does not support fails
        with a message about the document, not about the version, which is a miserable
        thing to debug on someone else's machine at go-live.
        """
        try:
            supported = list(self.rp.QBXMLVersionsForSession(self.ticket))
            for want in (qbxml.DEFAULT_VERSION, "15.0", "14.0", "13.0"):
                if want in supported:
                    return want
            return max(supported, key=lambda v: float(v))
        except Exception:                           # noqa: BLE001 - fall back, do not fail
            return qbxml.DEFAULT_VERSION

    def _send(self, xml):
        return qbxml.first(self.rp.ProcessRequest(self.ticket, xml))

    def txn_id(self, table, ref_number):
        """The idempotency check. Returns an existing TxnID, or None."""
        query, _, _ = self.BUILDERS[table]
        r = self._send(query(ref_number))

        if r.empty:
            return None
        if not r.ok:
            raise qbxml.QBXMLError(r.code, r.message)
        return r.txn_id

    def insert(self, table, rec):
        _, add, total_field = self.BUILDERS[table]
        r = self._send(add(rec, version=self.version))

        if not r.ok:
            raise qbxml.QBXMLError(r.code, r.message)

        # Lines were sent as Quantity and Rate, so QuickBooks did the arithmetic. If it
        # reached a different total than we billed, something on its side changed the
        # price - a price level on the customer, or a sales tax item. Refusing here
        # leaves the record pending with the reason attached, rather than creating an
        # invoice the customer is charged differently for than our records say.
        qbxml.check_total(rec.get("total_amount") or rec.get("TotalAmount"),
                          r.ret.get(total_field))
        return r.txn_id

    def close(self):
        try:
            self.rp.EndSession(self.ticket)
            self.rp.CloseConnection()
        except Exception:                           # noqa: BLE001 - closing is best effort
            pass


class Simulated:
    """
    Stands in for QuickBooks so the USSCOS half can be proven on its own.

    It deliberately does NOT pretend records already exist - every fetch is treated as a
    first write - because the interesting failure is a duplicate, and a stub that silently
    found everything already present would hide exactly that.
    """

    def __init__(self):
        self.written = []

    def txn_id(self, table, ref_number):
        return None

    def insert(self, table, rec):
        self.written.append((table, rec.get("RefNumber")))
        return f"SIM-{table[:3].upper()}-{rec.get('RefNumber') or len(self.written)}"


# ----------------------------------------------------------------------- run

def run_kind(s, qb, kind, singular, table, limit, dry_run):
    batch = fetch(s, kind, limit)
    records = batch.get("records", [])

    if not records:
        log(f"{kind}: nothing pending")
        return 0, 0

    log(f"{kind}: {len(records)} pending (batch {batch['batch_id']})")

    if dry_run:
        for rec in records:
            log(f"  would write {rec.get('RefNumber')} "
                f"{rec.get('CustomerRefFullName')} {rec.get('total_amount', rec.get('TotalAmount', ''))}")
        return 0, 0

    results, ok_count, fail_count = [], 0, 0

    for rec in records:
        ref = rec.get("RefNumber")
        try:
            existing = qb.txn_id(table, ref) if ref else None
            if existing:
                log(f"  {ref} already in QuickBooks as {existing} - acknowledging, not rewriting")
                txn = existing
            else:
                txn = qb.insert(table, rec)
                log(f"  {ref} written as {txn}")
            results.append({"type": singular, "id": rec["id"], "ok": True, "qb_txn_id": txn})
            ok_count += 1
        except Exception as e:                      # noqa: BLE001 - the message goes to USSCOS
            log(f"  {ref} FAILED: {e}")
            results.append({"type": singular, "id": rec["id"], "ok": False, "message": str(e)})
            fail_count += 1

    acknowledge(s, batch["batch_id"], results)
    return ok_count, fail_count


def main():
    ap = argparse.ArgumentParser(description="USSCOS to QuickBooks bridge")
    ap.add_argument("--simulate", action="store_true",
                    help="run the full cycle against a stub instead of QuickBooks")
    ap.add_argument("--dry-run", action="store_true",
                    help="fetch and report only - acknowledges nothing")
    ap.add_argument("--limit", type=int, default=100)
    ap.add_argument("--only", help="one of sales_orders, invoices, payments")
    args = ap.parse_args()

    s = session()

    status = s.get(f"{BASE}/api/qb/status", timeout=TIMEOUT).json()
    log(f"USSCOS says: {json.dumps(status.get('pending', {}))}")

    if args.simulate:
        log("SIMULATE - QuickBooks is not being written to")
        qb = Simulated()
    elif args.dry_run:
        qb = None
    else:
        qb = QuickBooks()

    total_ok = total_fail = 0
    started = time.time()

    for kind, singular, table in KINDS:
        if args.only and args.only != kind:
            continue
        ok, fail = run_kind(s, qb, kind, singular, table, args.limit, args.dry_run)
        total_ok += ok
        total_fail += fail

    if hasattr(qb, "close"):
        qb.close()

    log(f"done in {time.time() - started:.1f}s - {total_ok} written, {total_fail} failed")
    return 1 if total_fail else 0


if __name__ == "__main__":
    sys.exit(main())
