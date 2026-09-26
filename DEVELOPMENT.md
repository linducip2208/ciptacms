# Development

## Commands

Every command below exists in `app/Console/Commands` or ships with Laravel.
Verified with `php artisan list`.

```sh
php artisan serve                    # dev server
php artisan test                     # full suite
php artisan migrate --seed           # schema + seed data
php artisan migrate:fresh --seed     # start over (drops everything)
```

| Command | What it does |
|---|---|
| `php artisan lindu:install` | Seed data. Options: `--admin-email=` (default `admin@lindu.local`), `--admin-password=` (default `password123`), `--fresh` |
| `php artisan lindu:doctor` | Health check: PHP version, extensions, writable paths, DB, maintenance mode, `APP_KEY`, disks |
| `php artisan lindu:backup --type=full` | Run a backup now. Also scheduled daily |
| `php artisan lindu:check-updates` | Report versions. Also scheduled daily |
| `php artisan lindu:search-index` | Push DB content into Meilisearch. `--driver=`, `--limit=` (default 200). The `database` driver needs nothing |
| `php artisan webhooks:retry` | Retry failed webhook deliveries. Also scheduled every 5 minutes |
| `php artisan lindu:unlock` | Remove the install lock so `/install` can run again. Requires `--force`; `--token` prints the recovery token without unlocking |

`composer check` runs the blade linter, the route linter and the test suite:

```sh
composer lint:blade      # php tools/blade-lint.php
composer lint:routes     # php tools/route-lint.php
composer check           # both + php artisan test
composer test            # config:clear + php artisan test
```

`php tools/test.php` runs the same suite as `php artisan test` but prints one
line per **distinct** failure reason instead of a full PHPUnit stack trace. It
accepts a filter: `php tools/test.php Workflow`. Use it when you want to know
*why* something failed, not *where*.

### Schedule

`routes/console.php`:

```
lindu:backup --type=full          daily
lindu:check-updates               daily
webhooks:retry                    every five minutes
queue:prune-failed --hours=72     daily
```

Run it with `* * * * * cd /path && php artisan schedule:run`.

## The linters, and why they exist

### `php tools/blade-lint.php`

Compiles every `.blade.php` under `resources/views`, `modules`, `themes` and
`plugins`, then runs `token_get_all($compiled, TOKEN_PARSE)` on the output.

It exists because of a specific failure mode. Blade matches a directive only when
the `@` is preceded by whitespace or a line start:

```blade
<p>Total: @if($n){{ $n }}@endif</p>   {{-- @if is literal text, not a directive --}}
<p>Total: @if ($n) {{ $n }} @endif</p> {{-- correct --}}
```

In the first case the `@if` is passed through as text, the block it should have
opened never opens, and the `@endif` still closes nothing. The template compiles,
`php -l` passes, and the page 500s **at runtime only** — which means it ships.

The full explanation is in [ARCHITECTURE.md](ARCHITECTURE.md#the-linters).

### `php tools/route-lint.php`

Boots the router, collects every named route, then reports any `route('name')`
in a Blade file or a `.php` file under `app/`, `routes/`, `database/` or
`modules/` that does not exist. `route('typo')` is also a runtime-only 500 on one
page, so it is worth the same treatment.

**Known blind spot:** the pattern requires the closing `)` right after the quoted
name, so `route('api.v1.forms.submit', $slug)` is *not* checked. Dynamic names
have to be reviewed by hand.

## Current state of the suite

The suite is **not green**. `php artisan test` reports failures in
`LicenseTest` (pairing/signature cases and the state assertions) and
`RepositoryAuditTest` (admin routes with no controller method, and schema probes
that need a migrated database). Treat it as a diagnostic:

1. run it,
2. read the failing test names,
3. confirm your change is not among them,
4. re-run the area you touched.

`RepositoryAuditTest` in particular asserts two useful invariants — every
`admin.*` route resolves to a real controller method, and every seeded admin menu
URL resolves — so it catches renames that would otherwise ship broken navigation.
It is currently failing on `MenuController@create` / `@edit` and
`UserController@syncRoles` / `RoleController@syncPermissions`, which are routes
with no method behind them. Do not link to those four.

## Conventions

- **Thin controllers, logic in `app/Core/Services`.** A controller validates,
  calls a service and returns a view or a redirect.
- **Services are singletons**, registered in `LinduCoreProvider`. Do not put
  per-request state on a service.
- **Resolve classes through a whitelist, never from a request value.** The
  pattern in `CompanyProfileController::RESOURCES`,
  `WorkflowEngine::WHITELISTED_MODELS`, `DataBuilderController::FIELD_TYPES` and
  `config/lindu_admin.php` is deliberate — a `{resource}` route parameter must
  never be concatenated into a class name.
- **Wrap external calls in `try/catch` and `report($e)`.** The public site must
  not 500 because a marketplace, a search index or a `cp_*` table is
  unavailable. `SiteController::safe()` is the reference.
- **Never trust `Cache::flush()`.** `MenuService::forget()` is `Cache::flush()`,
  which is why every menu edit clears the whole cache. Prefer
  `Cache::forget('specific.key')` in new code.
- **FormRequests for complex validation**; inline `$r->validate()` for simple
  CRUD shapes.
- **Events fan out from the service**, not the controller:
  `event()`, then `WebhookDispatcher::dispatchEvent()`, then
  `WorkflowEngine::trigger()` — each in its own `try/catch`. See
  [WORKFLOW.md](WORKFLOW.md).

## Adding things

### A resource (model + admin CRUD + API)

1. `php artisan make:model Foo -m` and write the migration.
2. Add an explicit `$fillable` to the model. `Api\V1\ResourceApiController`
   intersects writes with it; a model with `$guarded = []` would let
   `Api\V2\ResourceApiController` write any column.
3. Add one entry to `config/lindu_admin.php`:

   ```php
   'invoices' => ['model' => App\Models\Invoice::class, 'label' => 'Invoices', 'search' => ['number']],
   ```

That single entry gives you `/admin/r/invoices` (list, create, edit, bulk, CSV
export) and all five `/api/v1/invoices` routes, plus the v2 variants. The slug
must match `[a-z-]+`.

### A page-builder component

Two edits, both in `app/Core/Services/BlockLibrary.php`: an entry in
`catalog()` and a `match` arm in `renderBlock()`. See
[PAGE_BUILDER.md](PAGE_BUILDER.md#adding-a-component).

### A form field type

One entry in `FormField::TYPES`, one `match` arm in `FormRenderer::field()` and
one in the submit rule table. See [FORM_BUILDER.md](FORM_BUILDER.md).

### A workflow trigger, condition or action

Constants at the top of `WorkflowEngine`. **Adding a model to
`WHITELISTED_MODELS` is a security decision** — anyone who can edit a workflow
can then write to that model. See [WORKFLOW.md](WORKFLOW.md#the-security-boundary).

### A module

Copy an existing folder, edit `module.json`. `ModuleManager::discover()` finds
anything in `modules/` with a `module.json` carrying a `slug`; the provider loads
`routes.php`, `routes-api.php`, `views/`, `lang/` and `database/migrations/` when
they exist. See [MODULES.md](MODULES.md).

## Before you commit

```sh
php tools/blade-lint.php
php tools/route-lint.php
php artisan test
```

Then read the failures. Do not paper over one with a skipped assertion — three of
the four tests in `tests/Feature/FormBuilderTest.php` are `probe` tests that catch
an exception and `assertTrue(true)`, so they report success whether or not the
code under test works. They were written to diagnose a specific bug and should be
replaced with real assertions once it is fixed.

See also: [INSTALL.md](INSTALL.md), [ARCHITECTURE.md](ARCHITECTURE.md),
[TROUBLESHOOTING.md](TROUBLESHOOTING.md).
