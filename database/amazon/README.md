# Amazon reports

Drop Seller Central exports here. **Nothing in this directory is committed** — see
`.gitignore`, and the reason below.

| File | Where it comes from | Imported by |
|---|---|---|
| `*settlement*.txt` | Payments → All Statements → Download Flat File V2 | `database/import_amazon_settlement.php` |
| `*orders*.txt` | Reports → Order Reports | *(not built yet)* |

```bash
php database/import_amazon_settlement.php            # dry run, reconciles only
php database/import_amazon_settlement.php --commit
```

## These files hold buyer PII

The **order** report carries buyer names, shipping addresses and phone numbers. Amazon's
Data Protection Policy requires that PII be deleted within 30 days of delivery, with an
exception only for what tax or legal obligations require.

A git commit is the opposite of deletable — it stays in the history, on every clone and
in every backup, for ever. So these files are never committed, and should be deleted off
the server once imported.

The **settlement** report carries no PII: order ids, SKUs and amounts only.

## The reconciliation

The importer adds every line and compares the total to the deposit Amazon says it made.
If they disagree it refuses to import and says by how much. A fee figure that cannot be
tied back to the bank is not worth having.
