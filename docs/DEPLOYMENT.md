# Deployment & Operations

Production runs on a **DigitalOcean droplet** (Ubuntu 24.04 LTS), deployed from Git.
Migrated off GoDaddy cPanel on 2026-08-03 — see [DIGITALOCEAN.md](DIGITALOCEAN.md) for
how the server was built and how to rebuild it.

---

## Server layout

Droplet **159.223.177.58**

| Domain | Document root | Serves |
|---|---|---|
| `os.usscos.com` | `/var/www/usscos/public` | USSCOS (internal) |
| `usscos.com`, `www.usscos.com` | `/var/www/usscos.com` | Public website + `/forms` |

The application lives in `/var/www/usscos`, but only its `public/` subdirectory is
web-reachable. `app/`, `config/`, `database/`, and `.env` sit outside the served path —
that's what keeps them private, and why the nginx `root` **must** point at
`/var/www/usscos/public`, not `/var/www/usscos`.

Owned by the `deploy` user, group `www-data`.

### TLS

Let's Encrypt via certbot, covering all three names, renewing automatically through
`certbot.timer`. HTTP 301-redirects to HTTPS.

```bash
certbot certificates          # check status
certbot renew --dry-run       # test renewal
```

### nginx

Server blocks live in `deploy/` in this repo and are copied to
`/etc/nginx/sites-available/`. After editing:

```bash
nginx -t && systemctl reload nginx
```

Uploads are blocked from executing scripts in both server blocks. The `.htaccess` files
under `public/` are Apache-only and ignored here — kept for portability.

## Deploying a change

```bash
ssh deploy@159.223.177.58
cd /var/www/usscos && bash deploy/deploy.sh
```

That pulls `main`, installs Composer dependencies, fixes permissions, and reloads
PHP-FPM. It refuses to run if someone edited files directly on the server, and warns you
when a deploy contains **new migrations** — those stay manual so a schema change is
always deliberate and always follows a backup.

Paths are resolved from the file location (`BASE_PATH = dirname(__DIR__)`), so the same
code runs unchanged locally and in production. `PUBLIC_SITE_PATH` in `.env` points at the
public website root.

## Website deployment

The public site lives in `website/` in this repo and is copied to `/var/www/usscos.com/`.
Forms go in `usscos.com/forms/`.

> This is temporary. The public website is being rebuilt as part of the application
> itself — see [ROADMAP.md](ROADMAP.md#website--publishing).

`website/config.php` points at the USSCOS webhook:

```php
define('BUSINESSOS_WEBHOOK', 'https://os.usscos.com/webhook/lead');
```

> On the old cPanel host this had to be `http://` — cPanel blocked HTTPS loopback cURL
> between folders on the same server, producing a silent cURL error 0 with leads never
> arriving. That restriction does not exist on the droplet; HTTPS loopback is verified
> working there.

## Operations runbook

### Importing the product master

```bash
cd /var/www/usscos

# 1. Dry run — writes nothing, prints the full plan
php database/import_products.php

# 2. Apply
php database/import_products.php --commit
```

Place the workbook at `database/spreadsheets/USSC EDI-ERP DATA.xlsx`, or pass
`--file=/full/path.xlsx`.

| Flag | Effect |
|---|---|
| *(none)* | Dry run — reports the plan, writes nothing |
| `--commit` | Apply inserts and updates |
| `--deactivate-missing` | Set `is_active = 0` on products absent from the sheet (reversible) |
| `--delete-unreferenced` | Delete only sheet-absent products that nothing references |
| `--overwrite-blanks` | Let blank cells NULL out existing values (**off by default**) |
| `--sheet=NAME` | Import a single tab |

Matching is by SKU. Blank cells are ignored by default, so the sheet can't erase data it
simply doesn't cover. Everything runs in one transaction.

> **The spreadsheet is not a complete master.** Around 70 real SKUs exist only in the
> database — most importantly the entire ROBO robot-paint line, plus Sharp Stripe
> machines, wands, and UMA-Tip cases. Never wipe products and reload from the sheet.

### Backups

DigitalOcean droplet backups run automatically. Before any bulk data change, take a
database dump as well:

```bash
mysqldump usscos > ~/usscos-$(date +%F).sql
```

### Running migrations

```bash
cd /var/www/usscos && php database/migrate.php
```

Back up first. `deploy.sh` warns when a deploy includes new migrations but never runs
them for you.

### Checking logs

```bash
tail -f /var/log/nginx/os.usscos.com.error.log   # nginx + PHP errors
tail -f /var/www/usscos/storage/logs/*.log       # application log
journalctl -u php8.3-fpm -f                      # PHP-FPM
```

### Rolling back

```bash
cd /var/www/usscos
git log --oneline           # find the last good commit
git reset --hard <commit>
sudo systemctl reload php8.3-fpm
```

Note that a rollback does **not** undo a migration. If the bad deploy included a schema
change, restore the database dump too.

## Known risks

**No automated tests.** Schema and type mismatches are found by clicking through the UI
or by a script failing mid-run. As the surface area grows this gets more expensive.

**Uploads have no access control.** `product_documents.is_public` only controls whether
the UI *shows* a file — the file itself is fetchable by anyone with the URL, logged in or
not. `/var/www/usscos-storage/uploads` exists outside the web root ready for the fix, but
the code still writes to `public/uploads`. **This must be resolved before any customer
portal accounts exist.**

**Security hardening still pending.** USSCOS is reachable from anywhere with only a
password. Two-factor authentication, login lockout, and an audit log are planned but not
built. An IP allowlist was ruled out because staff log in remotely.

**Single server.** Application and database share one droplet with no staging
environment. Fine at current scale; worth revisiting before the site carries real
e-commerce traffic.
