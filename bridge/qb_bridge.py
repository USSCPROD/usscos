#!/usr/bin/env python3
"""
The QuickBooks bridge. Runs on the Windows machine that hosts QuickBooks Desktop.

    python qb_bridge.py --simulate     no QuickBooks needed, writes nothing to it
    python qb_bridge.py --dry-run      fetch and show, acknowledge nothing
    python qb_bridge.py                the real thing

It PULLS from USSCOS over outbound HTTPS and writes through QODBC. Nothing reaches into
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

BASE = os.environ.get("USSCOS_URL", "https://os.usscproducts.com")
KEY = os.environ.get("USSCOS_API_KEY", "")
DSN = os.environ.get("QODBC_DSN", "QuickBooks Data")
TIMEOUT = 120

# (endpoint type, singular name used by /ack, QODBC table)
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


# --------------------------------------------------------------------- QODBC

class QuickBooks:
    """Thin QODBC wrapper. Field names already match its columns, so this stays small."""

    def __init__(self):
        import pyodbc  # imported here so --simulate runs on any machine
        self.cx = pyodbc.connect(f"DSN={DSN}", autocommit=True)

    def txn_id(self, table, ref_number):
        """The idempotency check. Returns an existing TxnID, or None."""
        cur = self.cx.cursor()
        cur.execute(f"SELECT TxnID FROM {table} WHERE RefNumber = ?", ref_number)
        row = cur.fetchone()
        return row[0] if row else None

    def insert(self, table, rec):
        raise NotImplementedError(
            f"Writing {table} through QODBC is not implemented yet. "
            "Run with --simulate until it is, so nothing is acknowledged as written "
            "when it was not."
        )


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

    log(f"done in {time.time() - started:.1f}s - {total_ok} written, {total_fail} failed")
    return 1 if total_fail else 0


if __name__ == "__main__":
    sys.exit(main())
