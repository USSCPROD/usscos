# Architecture

USSCOS is a hand-rolled MVC application. No framework — the `app/Core` directory
*is* the framework, at roughly 17 small classes.

---

## Request lifecycle

```
Browser
   │
   ▼
public/index.php          defines BASE_PATH, loads Composer autoload
   │
   ▼
bootstrap/app.php         builds the Application
   │
   ▼
Application::run()        boots config, session, database; dispatches
   │
   ▼
Router::dispatch()        matches method + URI against routes/web.php
   │
   ▼
Middleware chain          AuthMiddleware, GuestMiddleware, ThrottleMiddleware, Csrf…
   │
   ▼
Controller::action()      receives (Request, Response, ...routeParams)
   │
   ▼
Service                   business logic (where one exists)
   │
   ▼
Repository                SQL via PDO
   │
   ▼
View                      renders into $content, wrapped by layouts/app.php
   │
   ▼
Response
```

nginx rewrites everything that isn't a real file or directory to `index.php`
(`try_files $uri $uri/ /index.php?$query_string`), which is what makes clean URLs work.
The `.htaccess` in `public/` does the same job on Apache and is kept for portability —
on the current nginx droplet it is ignored.

## Layers

### Core (`app/Core/`)

| Class | Role |
|---|---|
| `Application` | Boot sequence and run loop |
| `Router` / `Route` | Route registration, pattern matching, dispatch |
| `Request` / `Response` | HTTP wrappers; `Response::redirect()`, `json()`, `html()` |
| `Controller` | Base class — provides `view()` |
| `Service` / `Repository` / `Model` | Base classes for the other layers |
| `Database` | PDO singleton — `select()`, `selectOne()`, `statement()`, `insert()`, `transaction()` |
| `Auth` | `Auth::user()`, login/logout, role checks |
| `Session` | Session handling, flash messages, CSRF tokens |
| `Validator` | Input validation |
| `Mailer` | PHPMailer wrapper |
| `Config` | Reads `config/*.php` |
| `Logger` | File logging to `storage/` |
| `View` | View resolution and rendering |

### Middleware (`app/Middleware/`)

| Middleware | Purpose | Status |
|---|---|---|
| `AuthMiddleware` | Requires a logged-in user | Active — wraps most routes |
| `GuestMiddleware` | Redirects logged-in users away from `/login` | Active |
| `ThrottleMiddleware` | Rate limiting | Available |
| `CsrfMiddleware` | Validates the `_token` field | **Defined but not wired to any route** |

CSRF is dormant. Forms should still emit `csrf_field()` so enabling it later is a
one-line change rather than an audit of every form.

### Controllers (18)

Thin by design: read input, call a service or repository, return a view or redirect.

`Admin` `Auth` `Customer` `Dashboard` `Document` `Inventory` `Invoice` `Lead`
`Opportunity` `Payment` `Product` `PurchaseOrder` `Quote` `SalesOrder` `Shipping`
`Task` `Vendor` `Webhook`

`WebhookController` is the only one reachable without authentication.

### Services (9)

Business logic where it's non-trivial: `Customer` `Dashboard` `Document` `Inventory`
`Invoice` `Payment` `Product` `Quote` `SalesOrder`.

Modules without a service (leads, tasks, opportunities, vendors, purchasing) go
controller → repository directly. That's fine while the logic stays simple.

### Repositories (15)

Every SQL statement in the application lives in one of these:

`Admin` `Customer` `Document` `Inventory` `Invoice` `Lead` `Opportunity` `Payment`
`Product` `PurchaseOrder` `Quote` `SalesOrder` `Task` `User` `Vendor`

### Models (`app/Models/`)

Only `Customer` and `User` exist, and they're barely used — repositories return plain
arrays. Data flows as associative arrays throughout, not objects. Worth knowing before
you go looking for an ORM.

## Views

```
app/Views/
  layouts/
    app.php        Main shell — sidebar, topbar, $content
    auth.php       Bare layout for login
  partials/
    sidebar.php    Left navigation
    topbar.php     Header bar
  <module>/        index.php, show.php, edit.php, form.php per module
  errors/          404 and friends
```

Views receive data as extracted variables. The pattern is always buffer → assign to
`$content` → include the layout. See [CLAUDE.md](../CLAUDE.md) for the exact form and
the function-name collision hazard.

## Routing

`routes/web.php` — 158 routes, grouped by module, most inside an auth middleware group.

```php
Router::get('/products/{id}', [ProductController::class, 'show'])->name('products.show');
```

Route parameters are extracted into an associative array and spread into the controller
method as **named arguments** — so parameter names must match placeholder names exactly.

Order matters: literal routes must be registered before parameterised ones that would
also match (`/products/autocomplete` before `/products/{id}`).

## Configuration

`config/app.php` `auth.php` `database.php` `logging.php` `session.php`, all reading from
`.env` via Dotenv. Access with `config('app.url')`.

`.env` lives at the project root — **outside** the web root, which is what keeps
credentials unreachable from the browser.

## Authentication and roles

Session-based. Roles, in the `users.role` enum:

`owner` `admin` `bookkeeper` `manager` `shipping` `employee` `rep` `distributor` `readonly`

Role checks are done ad hoc in controllers and views rather than through a central
permission layer. Something to consolidate as the portals arrive.

## Front end

No build step, no bundler, no framework. Plain HTML, CSS, and vanilla JavaScript
inline in views. Chart.js is used for dashboard graphs. This is deliberate — it keeps
deployment to a `git pull` with nothing to compile.
