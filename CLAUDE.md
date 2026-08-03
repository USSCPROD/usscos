# Conventions

Rules for anyone — human or AI — changing code in this project. Read before editing.

---

## Non-negotiables

1. **PDO with prepared statements. Always.** Never interpolate user input into SQL.
2. **All SQL lives in repositories.** Controllers and views must never issue queries.
3. **PSR-12.** `declare(strict_types=1);` at the top of every PHP file.
4. **Never redeclare global helpers** (`e()`, `money()`, `csrf_field()` …) inside a view.
5. **Use `Auth::user()`**, never `Session::get('user')`.
6. **No framework.** Don't introduce Laravel, Symfony components, Bootstrap, or Tailwind.

## Layering

```
Request → Router → Middleware → Controller → Service → Repository → Database
                                     ↓
                                   View
```

| Layer | Responsibility | Must not |
|---|---|---|
| **Controller** | Read input, call a service, return a view or redirect | Contain business logic or SQL |
| **Service** | Business rules, orchestration, multi-repository work | Touch `$_POST` directly (take an array) |
| **Repository** | All SQL for one table/aggregate | Contain business rules |
| **View** | Presentation only | Query the database |

Not every module has a service — simple ones go controller → repository. Add a service
as soon as there's real logic or more than one repository involved.

## Views

Every view follows this shape:

```php
<?php ob_start(); ?>

<!-- markup -->

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/Views/layouts/app.php';
```

**Helper functions defined in a view must be uniquely named and guarded:**

```php
if (!function_exists('pfield')) {
    function pfield(string $label, $value): void { /* … */ }
}
```

Two views defining the same function name in one request is a fatal error. This has
bitten this project before — the page renders halfway and then dies, which looks
exactly like a broken layout. When a page mysteriously truncates, check for this first.

## Layout: use tables, not flex/grid

Page layout uses HTML `<table>` elements with inline `style=""`, not `display:flex`
or `display:grid`.

> **Note on why:** this convention came from a debugging session where a page rendered
> only partially. Both a flex layout *and* a duplicate-function fatal error were fixed
> in the same pass, and the fatal error was the more likely culprit — cPanel is server
> software and has no bearing on CSS rendering. **The rule stands until someone tests it
> properly** (one page, a plain `display:flex` block, nothing else unusual). If flex
> works, this convention can be dropped and the markup simplified considerably.

## Styling

- Brand navy: `#222b59`. Link/accent blue: `#0A3D91`.
- Inline `style=""` is normal here; there's no CSS build step.
- Existing classes worth reusing: `.btn`, `.btn--primary`, `.btn--secondary`, `.btn--sm`,
  `.badge`, `.badge--success|info|warning|danger|neutral`, `.card`, `.page-header`,
  `.page-title`, `.kpi-card`, `.alert`, `.text-muted`.

## Global helpers

Defined in `Helpers/functions.php` — available everywhere, never redeclare:

`e()` `money()` `csrf_field()` `csrf_token()` `auth()` `config()` `session()`
`old()` `flash()` `url()` `route()` `asset()` `redirect()` `abort()` `now()`
`percentage()` `str_limit()` `time_ago()` `env()` `base_path()` `uuid()`

There is **no** global `fileSize()` or `fileIcon()` — define your own, guarded.

## Database

- Migrations are numbered sequentially in `database/migrations/`. Never edit one that
  has already run on the server; add a new one.
- **Run the SQL before uploading PHP that depends on it.** Otherwise the new code
  queries tables that don't exist yet.
- Named PDO parameters may appear only **once** per query. Repeat the value under a
  second name instead (`:total`, `:total2`).
- `products.qty_on_hand`, `is_active`, `is_taxable` and friends are `NOT NULL DEFAULT`.
  Writing `null` to them fails — omit the column instead.
- `leads.company_name` is `NOT NULL` — always supply a fallback.

## Routing

Routes live in `routes/web.php`. Multi-parameter routes work, but **the controller
method's parameter names must exactly match the route placeholders** — params are
spread as named arguments:

```php
Router::post('/products/{id}/images/{imageId}/delete', [ProductController::class, 'deleteImage']);

public function deleteImage(Request $r, Response $res, string $id, string $imageId): Response
```

Public endpoints (webhooks) must sit **outside** the auth middleware group.

## Error handling gotcha

`PDOException extends RuntimeException`. A `catch (\RuntimeException)` intended to
render a 404 will silently swallow real database errors and show "Not Found" instead.
Catch `\PDOException` first and rethrow it:

```php
try {
    $data = $this->service->show($id);
} catch (\PDOException $e) {
    throw $e;                 // let real DB errors surface
} catch (\RuntimeException) {
    return $this->view('errors.404', ['title' => 'Not Found'], 404);
}
```

## File uploads

Uploads land inside the web root, so:

- Generate the stored filename server-side; never trust the client's name.
- Whitelist extensions explicitly.
- `public/uploads/.htaccess` disables script execution — keep it there.
- Store the web path (`/uploads/…`) in the database, not the filesystem path.
- On delete, verify the path starts with the expected prefix and contains no `..`.

## Destructive operations

Products are referenced from seven tables. Quote/SO line items, `inventory_transactions`
and `product_components` are `ON DELETE RESTRICT`; invoice/bill/PO line items are
`ON DELETE SET NULL` — meaning a forced delete *succeeds* while silently erasing product
links on historical invoices.

Scripts that change data in bulk must:
1. Default to a **dry run** that reports the plan and writes nothing.
2. Require an explicit `--commit` flag.
3. Run inside a **transaction** and roll back on any error.
4. Prefer deactivating over deleting.

`database/import_products.php` is the reference implementation.

## Environment

- `BASE_PATH` is **hardcoded** in `public/index.php` to the server path. It differs
  locally — see [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).
- Same-server HTTP calls must use `http://`, not `https://` — cPanel blocks HTTPS loopback.
