#!/usr/bin/env python3
"""
Export the old stencil shop's customer accounts for USSCOS (database/import_shop_accounts.php).

    python3 export_shop_accounts.py /opt/usscstencils/var/designer.db > shop_accounts.json

Reads the shop's SQLite account file (read-only) and writes JSON: users with their password
hashes (PBKDF2, which USSCOS can check, so nobody has to reset), saved designs, and saved
quotes with their items turned into USSCOS cart items ({kind: 'stock', sku, qty} ...),
using the shop's catalog to map productId/variantKey/thickness to a SKU.

The output holds password hashes and customers' details: it is private. Keep it out of git
(storage/stencil-data/ is ignored) and delete it once imported.
"""
import json
import sqlite3
import sys
import urllib.request

CATALOG = "https://stencils.usscproducts.com/catalog.json"


def main():
    if len(sys.argv) < 2:
        sys.exit(__doc__)
    db = sqlite3.connect("file:" + sys.argv[1] + "?mode=ro", uri=True)
    db.row_factory = sqlite3.Row
    catalog = sys.argv[2] if len(sys.argv) > 2 else CATALOG

    if catalog.startswith("http"):
        req = urllib.request.Request(catalog, headers={"User-Agent": "usscos-export"})
        with urllib.request.urlopen(req, timeout=60) as r:
            cat = json.load(r)
    else:
        with open(catalog) as f:
            cat = json.load(f)

    sku_of = {}
    for p in cat.get("products", []):
        for v in p.get("variants", []):
            for t, o in (v.get("options") or {}).items():
                sku_of[(p["id"], v["key"], t)] = o["sku"]

    def item(i):
        pid, qty = i.get("productId"), int(i.get("qty") or 1)
        if pid == "custom-text":
            return {"kind": "text", "text": i.get("text", ""), "size": i.get("size", ""), "thickness": i.get("thickness", ""), "qty": qty}
        if pid == "custom-endzone":
            return {"kind": "endzone", "qty": qty, **{k: i.get(k, "") for k in ("text", "size", "style", "format", "fill", "line")}}
        if pid == "custom-midfield":
            return {"kind": "midfield", "logo": i.get("logo", ""), "field": i.get("field", ""), "width": i.get("width", ""), "qty": qty}
        sku = sku_of.get((pid, i.get("variantKey"), i.get("thickness")))
        return {"kind": "stock", "sku": sku, "qty": qty} if sku else {"kind": "unknown", "shop": i}

    cols = {r[1] for r in db.execute("PRAGMA table_info(users)")}
    out = {"users": [], "designs": [], "quotes": []}
    for u in db.execute("SELECT * FROM users ORDER BY id"):
        out["users"].append({k: u[k] for k in ("id", "email", "name", "phone", "org", "buyer_type", "salt", "hash", "iterations",
                                               "created", "last_seen", "signup_ip", "blocked", "blocked_note")}
                            | {"role": u["role"] if "role" in cols else "customer"})
    for d in db.execute("SELECT * FROM designs ORDER BY id"):
        out["designs"].append({"user_id": d["user_id"], "tool": d["tool"], "name": d["name"], "data": json.loads(d["data"] or "{}"),
                               "summary": d["summary"], "price": d["price"], "created": d["created"], "updated": d["updated"]})
    for q in db.execute("SELECT * FROM quotes ORDER BY id"):
        out["quotes"].append({"user_id": q["user_id"], "name": q["name"], "status": q["status"], "retail": q["retail"],
                              "wholesale": q["wholesale"], "created": q["created"], "updated": q["updated"],
                              "items": [item(i) for i in json.loads(q["items"] or "[]")]})
    json.dump(out, sys.stdout)


if __name__ == "__main__":
    main()
