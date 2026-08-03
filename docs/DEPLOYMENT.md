# Deployment & Operations

Production runs on GoDaddy cPanel shared hosting. Deployment is currently a manual file
copy — see [Known risks](#known-risks) at the end.

---

## Server layout

Hosting account root: `/home/t2a2ymc1f3z4/public_html/`

| Domain | Document root | Serves |
|---|---|---|
| `os.usscos.com` | `public_html/businessos/public` | USSCOS (internal) |
| `usscos.com` | `public_html/usscos.com` | Public website + `/forms` |
| `usscproducts.net` | `public_html` | Hosting primary domain |

The application code sits in `public_html/businessos/`, but only its `public/`
subdirectory is web-reachable. `app/`, `config/`, `database/`, and `.env` are all
outside the served path — that's what keeps them private, and it's why the subdomain
document root **must** point at `businessos/public`, not `businessos`.

### DNS

`usscos.com` DNS is managed at **GoDaddy**, not cPanel — so cPanel cannot create
subdomain DNS records itself. Adding a subdomain takes two steps:

1. cPanel → **Domains** → Create A New Domain, document root `public_html/businessos/public`
2. GoDaddy → **DNS** → add an **A** record (`os` → the same IP as the `@` record,
   currently `160.153.189.158`)

Then let AutoSSL issue the certificate (cPanel → SSL/TLS Status → Run AutoSSL).

### Required .htaccess files

**`businessos/public/.htaccess`** — the rewrite that makes routing work. Without it,
every URL except `/` returns 404:

```apache
Options -Indexes
Options +FollowSymLinks
RewriteEngine On
RewriteRule ^\.env - [F,L]
RewriteRule ^composer\.(json|lock)$ - [F,L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options SAMEORIGIN
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
```

**`businessos/public/uploads/.htaccess`** — disables script execution for uploaded files.
Never remove it.

> File Manager hides dotfiles by default. Settings → **Show Hidden Files** or you'll
> think these are missing when they aren't (and vice versa).

## Deploying a change

1. **Run any new SQL first.** phpMyAdmin → select database → **SQL** → paste the
   migration → Go. Uploading PHP that queries tables which don't exist yet produces
   confusing failures.
2. Upload changed files via File Manager or FTP, preserving paths.
3. Load the affected page and confirm.

`BASE_PATH` is **hardcoded** in `public/index.php`:

```php
define('BASE_PATH', '/home/t2a2ymc1f3z4/public_html/businessos');
define('PUBLIC_PATH', '/home/t2a2ymc1f3z4/public_html/usscos.com');
```

That means `public/index.php` differs between local and server — don't overwrite the
server copy with a local one.

## Website deployment

The public site lives in `website/` in this repo and deploys to
`public_html/usscos.com/`. Forms go in `usscos.com/forms/`.

`website/config.php` must point at the USSCOS webhook over **plain HTTP**:

```php
define('BUSINESSOS_WEBHOOK', 'http://os.usscos.com/webhook/lead');
```

cPanel blocks HTTPS loopback cURL between folders on the same server. Using `https://`
here produces a silent cURL error 0 and leads never arrive.

## Operations runbook

### Importing the product master

```bash
cd ~/public_html/businessos

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

Before any bulk change: phpMyAdmin → database → select tables → **Export** → Go.

### Running migrations

phpMyAdmin SQL tab, or from Terminal:

```bash
cd ~/public_html/businessos && php database/migrate.php
```

### Checking logs

Application logs write to `storage/`. PHP errors surface in cPanel → **Errors**.

## Known risks

**Manual deployment with no version control.** The project is not in Git. There is no
change history, no diff, no rollback, and no known-good state to return to if an upload
lands half-finished or overwrites the wrong file. This is the largest operational risk
in the project and is worth fixing ahead of any further feature work — a repository plus
a small deploy script would remove most of it.

**No automated tests.** Schema and type mismatches are currently found by clicking
through the UI or by a script failing mid-run.

**Security hardening still pending.** USSCOS is reachable from anywhere with only a
password. Two-factor authentication, login lockout, and an audit log are planned but not
built. An IP allowlist was ruled out because staff log in remotely.
