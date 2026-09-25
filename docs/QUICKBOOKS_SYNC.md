# QuickBooks Sync

USSCOS is **not** the ledger of record — QuickBooks is. Invoices, sales orders and
payments are created in USSCOS and pushed to QuickBooks daily.

Reporting inside USSCOS (A/R aging, P&L, sales by customer or product) is computed
directly from invoices and payments, so it needs no general ledger and works whether or
not a given batch has synced yet.

---

## Architecture

QODBC only runs on Windows, alongside QuickBooks Desktop. The droplet runs Ubuntu, so it
cannot speak ODBC to QuickBooks directly. A small bridge on the Windows machine closes
the gap:

```
  USSCOS (Ubuntu droplet)                    Windows machine
  ───────────────────────                    ────────────────
  invoices / sales_orders / payments
        │  qb_exported_at IS NULL
        ▼
  GET  /api/qb/pending      ──────────────►  bridge script
                                                  │
                                                  ▼
                                             QODBC ──► QuickBooks
                                                  │
  POST /api/qb/ack          ◄──────────────  results (per record)
        │
        ▼
  qb_exported_at stamped, qb_txn_id stored
```

The bridge **pulls**. Nothing needs to reach into the Windows machine, so no inbound
firewall rules or port forwarding — it only makes outbound HTTPS calls.

## Authentication

All three endpoints sit outside the session auth group and behind an API key.

Send it as a header on every request:

```
X-API-Key: <the key>
```

The key lives in `/var/www/usscos/.env` as `API_KEY`. Retrieve it with:

```bash
ssh root@159.223.177.58 'grep API_KEY /var/www/usscos/.env'
```

If `API_KEY` is unset the API refuses every request with a 503 rather than allowing them —
a missing secret fails closed. Comparison uses `hash_equals`, so it is not timing-attackable.

To rotate: change the value in `.env` and update the bridge. No restart needed.

## Endpoints

### `GET /api/qb/status`

Health check and pending counts.

```json
{ "ok": true, "time": "2026-08-06T14:02:11+00:00",
  "pending": { "invoices": 3, "sales_orders": 1, "payments": 2, "failed": 0 } }
```

### `GET /api/qb/pending?type=invoices&limit=100`

`type` is `invoices`, `sales_orders` or `payments`. `limit` defaults to 100, caps at 500.

Field names mirror QODBC's own columns, so the bridge is a thin mapping rather than a
translation layer:

```json
{
  "batch_id": "b20260806140211-9f3a2c",
  "type": "invoices",
  "count": 1,
  "records": [{
    "id": 34815,
    "RefNumber": "292140",
    "TxnDate": "2026-08-05",
    "DueDate": "2026-09-04",
    "CustomerRefFullName": "THAYER HIGH SCHOOL",
    "TermsRefFullName": "Net 30",
    "PONumber": "PO-8871",
    "ShipMethodRefFullName": "FedEx Ground",
    "tracking_number": "1Z999AA10123456784",
    "subtotal": "1450.00", "tax_amount": "0.00", "total_amount": "1450.00",
    "lines": [{
      "ItemRefFullName": "DSWFC",
      "Desc": "Fat Can — White, Case (12 × 26 oz)",
      "Quantity": "20.0000", "Rate": "58.2000", "Amount": "1164.00",
      "UOM": "CS"
    }]
  }]
}
```

`CustomerRefFullName` is `customers.quickbooks_name` and `ItemRefFullName` is
`products.quickbooks_item` — the names QuickBooks itself uses, so they resolve without
a lookup table. Both are fully populated (41,233 customers, 891 products).

Calling this repeatedly is safe. A record keeps appearing until it is acknowledged, so an
interrupted run simply picks it up next time.

### `POST /api/qb/ack`

```json
{
  "batch_id": "b20260806140211-9f3a2c",
  "results": [
    { "type": "invoice", "id": 34815, "ok": true,  "qb_txn_id": "8A2F-1754500000" },
    { "type": "invoice", "id": 34816, "ok": false, "message": "Customer not found in QuickBooks" }
  ]
}
```

`type` is `invoice`, `sales_order` or `payment` (singular).

- **`ok: true`** stamps `qb_exported_at` and stores `qb_txn_id`. The record is never
  offered again.
- **`ok: false`** stores the message in `qb_export_error` and leaves the record pending,
  so a broken record stays visible rather than being retried silently forever.

Every call is written to `qb_export_log`, so "did last night's sync run?" is answerable.

## Avoiding duplicates

This is the part worth getting right. A crash between writing to QuickBooks and sending
the ack would otherwise create the invoice twice.

**The bridge must check QuickBooks for the RefNumber before inserting.**

```sql
-- via QODBC, before creating anything
SELECT TxnID FROM Invoice WHERE RefNumber = '292140'
```

- Row found → the write already happened. Ack with `ok: true` and that `TxnID`.
- No row → insert, then ack with the returned `TxnID`.

That makes the whole cycle idempotent. Acking only on confirmed success, never
optimistically, is what keeps it safe.

## The bridge script (Windows side)

Runs on the machine with QuickBooks. Scheduled nightly via Task Scheduler. Pseudocode:

```python
BASE    = "https://os.usscos.com"
HEADERS = {"X-API-Key": API_KEY}

for kind, singular in (("invoices","invoice"), ("sales_orders","sales_order"), ("payments","payment")):
    batch = requests.get(f"{BASE}/api/qb/pending",
                         params={"type": kind, "limit": 100},
                         headers=HEADERS, timeout=60).json()

    results = []
    for rec in batch["records"]:
        try:
            existing = qodbc_query(
                "SELECT TxnID FROM Invoice WHERE RefNumber = ?", rec["RefNumber"])
            txn_id = existing[0]["TxnID"] if existing else qodbc_insert(rec)
            results.append({"type": singular, "id": rec["id"],
                            "ok": True, "qb_txn_id": txn_id})
        except Exception as e:
            results.append({"type": singular, "id": rec["id"],
                            "ok": False, "message": str(e)})

    if results:
        requests.post(f"{BASE}/api/qb/ack",
                      json={"batch_id": batch["batch_id"], "results": results},
                      headers=HEADERS, timeout=60)
```

Requirements on the Windows machine:

- **QODBC** installed and licensed, with the QuickBooks company file open (or QODBC
  configured for unattended mode)
- Python 3 with `requests` and `pyodbc`
- Outbound HTTPS to `os.usscos.com` — nothing inbound
- The API key stored somewhere sensible (Windows Credential Manager or an env var), not
  hardcoded in the script

**Order matters:** sales orders and invoices before payments. A payment references its
invoices by `RefNumber`, so those invoices must exist in QuickBooks first.

## Monitoring

```bash
# pending counts
curl -s -H "X-API-Key: $KEY" https://os.usscos.com/api/qb/status | python3 -m json.tool

# anything stuck
ssh root@159.223.177.58 'mysql -N -e "
  SELECT invoice_number, qb_export_error FROM usscos.invoices
  WHERE qb_exported_at IS NULL AND qb_export_error IS NOT NULL;"'

# recent activity
ssh root@159.223.177.58 'mysql -N -e "
  SELECT created_at, record_type, reference, action, message
  FROM usscos.qb_export_log ORDER BY id DESC LIMIT 20;"'
```

A record with a `qb_export_error` and no `qb_exported_at` needs a human. The usual causes
are a customer or item that exists in USSCOS but not in QuickBooks, or a `RefNumber`
collision with something entered manually.

## Historical backfill

Migration 047 marked all 34,729 existing invoices as already exported — they came *from*
QuickBooks in the first place, and without that the first run would have tried to push two
years of history back in. Sales orders and payments were marked the same way.

Only records created after that migration will queue.

## Not covered here

The [accounting spec](USSCos_Accounting_Module_Spec.md) also describes journal entry
posting, opening balances and period close. **None of that is needed** while QuickBooks
remains the ledger — it only applies if USSCOS ever takes over as the book of record.
The chart of accounts is seeded (migration 046) and used for reporting and mapping, not
for posting.

---

## Decided 2026-09-21: the sync becomes bidirectional

Mar-Kov's own QuickBooks integration **stays as it is**. USSCOS does not talk to Mar-Kov.
Instead **QuickBooks becomes the hub**, and USSCOS both pushes to it and pulls from it:

```
        Mar-Kov ──────────┐
       (TCC batches)      │
                          ▼
                    QuickBooks  ── two company files, TCC and USSC
                      ▲     │
          push ───────┘     └─────── pull
        (USSC invoices,       (POs and invoices that
         payments, SOs)        originated elsewhere)
```

This is a real change of shape. Everything built so far is **push-only** — `GET /pending`
hands records out, `POST /ack` confirms them. There is no inbound path.

### What pulling needs that pushing did not

**A key that survives both directions.** QuickBooks' `TxnID` is the only stable identifier
for a transaction. `invoices.qb_txn_id` already exists and is stamped on acknowledgment,
so the same column serves both — pull matches on it.

**Not re-importing our own records.** USSCOS pushes invoices to QuickBooks and would then
pull invoices back. Any record already carrying a `qb_txn_id` we set ourselves is ours and
must be skipped, or every pushed invoice returns as a duplicate on the next pull.

**Picking up edits, not just creates.** This matters here specifically: the intercompany PO
**is edited after creation** to the actual batch yield. A pull that only looks for new
records would never see the correction. QuickBooks gives each transaction an
**`EditSequence`** that changes on every edit — store it and compare, and an amended PO is
detectable without diffing every field.

**An entity on every record.** Two company files, so each pulled record has to say which
one it came from, and each pushed record which one it is destined for.

**Deciding who wins.** If a record exists on both sides and they disagree, which is
authoritative? Simplest defensible rule: **whoever created it owns it.** USSC invoices
originate in USSCOS, so USSCOS wins. POs and TCC-side records originate in
QuickBooks/Mar-Kov, so QuickBooks wins. Anything else needs a human.

### Endpoints to add

| Method | Path | Purpose |
|---|---|---|
| `POST` | `/api/qb/inbound` | Bridge posts records read from QuickBooks — POs, invoices |
| `GET` | `/api/qb/cursor` | Last successful pull point per entity and record type, so the bridge knows where to resume |

Both need the entity, and `inbound` must be idempotent on `TxnID` + `EditSequence`.

### Open

- Does USSCOS hold TCC-side purchasing and push to **TCC's** file, or is TCC's bookkeeping
  outside USSCOS entirely?
- Which records are pulled — POs and invoices were named. Bills? Payments entered directly
  in QuickBooks?
- `qb_export_log.record_type` is an enum of `invoice`, `sales_order`, `payment`. It needs
  `purchase_order` and `bill` adding, and a direction column.
