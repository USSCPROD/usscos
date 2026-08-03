# BusinessOS

A custom business management platform built for **US Specialty Coatings** — a paint and
stencil manufacturer operating a distributor network. It replaces a stack of QuickBooks,
spreadsheets, and network-drive folders with one system the company owns outright.

Built in-house on PHP + MySQL. No framework, no licensing cost, no vendor lock-in.

---

## What it does today

| Area | Capability |
|---|---|
| **Lead capture** | Public web forms post to a webhook that creates leads automatically |
| **Lead routing** | Per-form-type notification and assignment, with round-robin for paint leads |
| **CRM** | Leads → opportunities → pipeline board, with tasks linked to any record |
| **Quoting** | Build, edit, and email branded quotes; status changes advance the lead and opportunity |
| **Orders** | Quote → sales order, packing slips, payment collection |
| **Fulfilment** | Ship & Invoice — records tracking, generates the invoice, closes the order |
| **Invoicing** | Emails as a receipt when paid or an invoice with due date when on terms |
| **Products** | ~100 fields per product, image gallery, document library (SDS/TDS/flyers) |
| **Purchasing** | Vendors, purchase orders, raw materials |
| **Inventory** | Stock levels and inventory transactions |
| **Admin** | Users, roles, departments, company defaults, lookup tables |

## Documentation

| Document | What's in it |
|---|---|
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | Request lifecycle, application layers, directory map |
| [docs/DATABASE.md](docs/DATABASE.md) | Schema, key relationships, and the constraints that bite |
| [docs/MODULES.md](docs/MODULES.md) | What each module does and how the main flows run |
| [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) | Server layout, deploy process, operations runbook |
| [docs/ROADMAP.md](docs/ROADMAP.md) | What's built, what's next, what's deferred |
| [CLAUDE.md](CLAUDE.md) | Coding conventions — read before changing code |
| [SETUP.md](SETUP.md) | Local development setup |

## Stack

- **PHP 8.3** (`ea-php83` on the server) — no framework, custom MVC
- **MySQL 8** — PDO with prepared statements throughout
- **Apache** on cPanel shared hosting (GoDaddy)
- **Composer** for PhpSpreadsheet, PHPMailer, Dotenv
- Plain HTML/CSS/JS on the front end — no build step, no bundler

## Live environment

| URL | Serves | Document root |
|---|---|---|
| `os.usscos.com` | BusinessOS (internal) | `public_html/businessos/public` |
| `usscos.com` | Public website + lead forms | `public_html/usscos.com` |

Both eventually move to `usscproducts.com` with the same structure.

## Layout at a glance

```
app/
  Controllers/     18 controllers — HTTP handling, no business logic
  Services/         9 services    — business logic and orchestration
  Repositories/    15 repositories — all SQL lives here
  Core/            Framework: Router, Request, Response, Database, Auth, Session…
  Middleware/      Auth, Guest, CSRF, Throttle
  Models/          Thin; repositories do most of the work
  Views/           21 module folders + layouts and partials
config/            app, auth, database, logging, session
database/
  migrations/      49 numbered SQL migrations
  import_*.php     Data importers (QuickBooks, sales history, products)
public/            Web root — index.php, assets, uploads
routes/web.php     158 routes
Helpers/           Global helper functions
website/           Public site source (deployed separately to usscos.com)
```

## Quick start

See [SETUP.md](SETUP.md) for local development, or
[docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) for the server.
