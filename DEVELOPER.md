# Developer guide

Architecture notes for someone picking this codebase up. It assumes you can
read PHP and Laravel but have never seen this project.

[DEVELOPMENT.md](DEVELOPMENT.md) is the day-to-day companion: commands, the
linters, the current state of the suite, and step-by-step recipes for adding a
resource, a component, a field type or a module. This file is the map.

---

## 1. What this is

Two products in one repository:

1. **A company-profile CMS** — a public website plus the admin behind it.
2. **An application core** — the engines, the generic admin CRUD layer and the
   extension points other products are built on.

Laravel 13 on PHP 8.3, MySQL 8 / MariaDB 10.6+ or SQLite. Tabler 1.4 is the
CSS foundation for both the admin and the public site; the page builder is plain
Alpine in one Blade file. **No build step is required** — `npm`/`vite` is only
for changing `resources/css`, `resources/js` or `vite.config.js`. A
`php artisan dev` helper (from `laravel/pao`) runs the dev processes, and
`composer dev` wraps it.

Measured at the current commit (`php artisan route:list` and a file walk):

| | |
|---|---|
| Routes | **391** |
| Migrations | **23** |
| Eloquent models | **112** (100 in `App\Models`, 12 in `App\Models\Cp`) |
| Classes in `app/Core/Services` | **30**, plus 6 in `Services/Payments` |
| Public page-builder components | **21** |
| Reusable public UI components | **17** under `resources/views/components/site` |
| Test files | **38** under `tests/Feature` and `tests/Unit` |

---

## 2. Layout

| Path | Responsibility |
|---|---|
| `app/Core/Services/` | 30 services, the whole domain layer |
| `app/Core/Contracts/` | `PaymentGateway`, `LicenseProvider` — the two pluggable seams |
| `app/Core/Plugins/` | `PluginInterface`, `PluginLoader`, `PluginException`, `UnknownPluginException` |
| `app/Core/Themes/` | `DesignTokens` — token normalisation, merging and CSS emission |
| `app/Core/Search/` | search drivers |
| `app/Core/Support/` | `helpers.php` (`setting()`, `tenant()`, `tenant_id()`, `lindu_version()`, `user_can()`), `SafeCache`, `Contrast` |
| `app/Core/Traits/` | `Auditable`, `BelongsToTenant`, `HasSlug` |
| `app/Http/Controllers/Admin/` | admin controllers |
| `app/Http/Controllers/Api/V1`, `V2` | REST surfaces |
| `app/Http/Controllers/Frontend/` | `SiteController` (public site), `HomeController` (sitemap/robots) |
| `app/View/Composers/` | `AdminMenuComposer` — injects `$adminMenu` into every `admin.*` view |
| `app/Http/Middleware/` | `CheckPermission` (`permission:`), `ResolveTenant` (`tenant:`), `CheckInstalled` (`installed:`), `RequirePair` (`pair:`), `SetLocale` (**not registered**) |
| `app/Providers/` | `LinduCoreProvider` (registers the singletons), `ModuleServiceProvider`, `AppServiceProvider` |
| `modules/`, `plugins/`, `themes/` | folder + manifest extensions |
| `config/lindu.php` | product version, extension paths, media, security, backup, updates, license, payments |
| `config/lindu_admin.php` | 28 generic resources driving both admin CRUD and the API |
| `routes/` | `web.php`, `api.php`, `admin.php`, `site.php`, `company.php`, `pair-routes.php`, `console.php` |

`bootstrap/app.php` registers `routes/web.php`, `routes/api.php`,
`routes/console.php` and a `/up` health endpoint, appends `ResolveTenant` to
both the `web` and `api` groups and `RequirePair` to `web`, and renders
`QuotaExceeded` as 402 and `FeatureUnavailable` as 403.

---

## 3. `app/Core/Services`

Thirty classes. They are **not** all container singletons —
`LinduCoreProvider::register()` registers 18 of them as singletons
(`SettingService`, `AuditService`, `MenuService`, `ModuleManager`,
`PluginManager`, `ThemeManager`, `SeoService`, `MediaService`, `SearchService`,
`TenantService`, `LicenseService`, `BackupService`, `UpdateService`,
`WorkflowEngine`, `WebhookDispatcher`, `ImportExportService`,
`DataBuilderService`, `HealthService`). The rest are resolved from the
container as needed, or used as static helpers.

| Service | What it owns |
|---|---|
| `SettingService` | the `settings` table and the `setting()` helper. 300-second cache, dropped on every `set()`. `type: secret` values are `Crypt`-encrypted. |
| `SafeCache` | wraps every cache read: discards an entry that fails to decode and rebuilds it. `normalise()` flattens anything array-like before storing, so a `Collection` can never be written to the store again. |
| `MenuService` | `tree($location, $user)` and `forget()`. Tenant-scoped, permission-filtered, nested, 120-second cache. **`forget()` is `Cache::flush()`** — the whole cache, not a menu key. |
| `BlockLibrary` | the page builder: `catalog()` (21 components), `render()`, `sections()`, plus the `lines()` / `pairs()` helpers the inspector textarea fields use. |
| `FormRenderer` | renders a form to HTML and processes a submission through the anti-spam layers. The same code path backs the admin "as saved" preview. |
| `DataBuilderService` | the `cb_*` physical mirror: `syncPhysicalTable()`, `syncRecord()`, `removeColumn()`, `dropPhysicalTable()`, `safeTableName()`, `COLUMN_MAP`. |
| `RelationRegistry` | the content-type relation definitions. |
| `ImportExportService` | CSV/JSON for content-type records, plus the generic `export()` / `toCsv()` / `downloadModel()` used by the resource controller. |
| `ModuleManager` | module discovery, `syncRegistry()`, `checkDeps()`, `registerMenus()`, `registerPermissions()`, lifecycle. `registerPermissions()` splits `"company.manage"` into action + module. |
| `PluginManager` | plugin discovery, `syncRegistry()`, `activeInstances()`, `ensureBooted()` / `registerHooks()`, `applyFilter()`, `dispatchHook()`, `callAction()`, lifecycle, `ACTIONS`, `FORBIDDEN_METHODS`. |
| `ThemeManager` | theme discovery, `activate()` / `deactivate()`, `tokens()`, `tokenCss()`, `hasViewOverrides()` / `applyViewOverrides()`. |
| `DesignTokens` (in `Core/Themes`) | `normalise()`, `merge()`, `toCss()`, `VARIABLES` (14 named token keys → CSS custom properties). Values are length-capped and stripped of `;`, `{`, `}`, `<`, `>`, `\`, newlines and `@import`, so a stored value cannot smuggle a CSS declaration. |
| `WorkflowEngine` | `TRIGGERS` (17), `CONDITIONS` (13), `ACTIONS` (9), `WHITELISTED_MODELS`, `trigger()`, `run()`, `retry()`. |
| `WebhookDispatcher` | outbound delivery, `sign()` / `verify()` HMAC, `dispatchEvent()`, `processRetries()`. |
| `CommentService` | the public comment write paths and their moderation state. |
| `MediaService` | upload, metadata, thumbnails, WebP/AVIF conversion, trash/restore. |
| `SearchService` (+ `Core/Search`) | the `database` driver (default, needs nothing) and Meilisearch. |
| `SeoService` | per-row `SeoMeta`, `titleFor()`, the sitemap and robots output. |
| `LicenseService` | the built-in license manager: issue / activate / deactivate / `status()` / `hasFeature()`, plus the `LicenseProvider` seam. |
| `BackupService` | `run($type)`, `restore($backup, $confirmed)`, `prune()`, `envExample()`, `dumpDatabase()`. |
| `UpdateService` | `check()`, `checkAll()`, `apply()` — backup → migrate → clear cache → record version. Never fetches code. |
| `TenantService` | `resolve($host)`, `checkQuota()`. |
| `FeatureFlag` | static helper over `plan_features` and `tenant_usages`: `enabled()`, `limit()`, `used()`, `exhausted()`, `track()`. |
| `Quota` | the enforcement layer over `FeatureFlag`: `allows()`, `consume()`, `guard()`, `assertFeature()`, `snapshot()`, `recountAll()`, plus `METRICS` (7) and `FEATURES` (8). Throws `QuotaExceeded` / `FeatureUnavailable`. |
| `HealthService` | `checks()` — the same list `lindu:doctor` and `/admin/health` report. |
| `NotificationService` | `notifyAdmins()`, `send()`, template rendering. |
| `AuditService` | the `audit_logs` writer every admin controller calls. |
| `TwoFactorService` | TOTP enrolment, verification and backup codes. |
| `CompanyProfileService` | the `about.*` and `contact.*` settings, `socialLinks()`. |
| `InstallLock` | the install state, `lock()`, `unlock()`, `recoveryToken()`, `verifyToken()`. |
| `QrService` | server-side QR generation. |
| `PaymentManager` (+ `Services/Payments/`) | resolves a gateway by key from `config('lindu.payments.adapters')`: `HostedGateway` (abstract) + `Xendit`, `Ipaymu`, `Tripay`, `Stripe`, `Generic`. |

### Conventions

- **Thin controllers, logic in services.** A controller validates, calls a
  service, and returns a view or a redirect.
- **Wrap external I/O in `try/catch` + `report($e)`.** The public site must
  survive a missing marketplace, a missing search index or a `cp_*` table that
  does not exist yet. `SiteController::safe()` is the reference.
- **Never build a class name from a request value.** The whitelist pattern is
  used at every boundary: `config/lindu_admin.php`,
  `CompanyProfileController::RESOURCES`, `WorkflowEngine::WHITELISTED_MODELS`,
  `DataBuilderController::FIELD_TYPES` / `RELATION_TYPES`, `FormField::TYPES`,
  `ModuleController::ACTIONS`, `PluginManager::FORBIDDEN_METHODS`.
- **Do not put per-request state on a singleton.** `PluginManager` and
  `ThemeManager` do keep per-request caches and clear them in `flush()` /
  `reset()`.
- **Prefer `Cache::forget('specific.key')` in new code.** `MenuService::forget()`
  is `Cache::flush()` and that is a known wart, not a pattern to copy.
- **Events fan out from the service**, not the controller: `event()`, then
  `WebhookDispatcher::dispatchEvent()`, then `WorkflowEngine::trigger()` — each
  in its own `try/catch`.

---

## 4. The engines

### Generic admin CRUD

`config/lindu_admin.php` maps 28 slugs to `{model, label, search}`. That one
map powers, with no per-resource code:

- `Admin\ResourceController` → `/admin/r/{resource}` (index, create, store,
  edit, update, destroy, bulk, CSV export)
- `Api\V1\ResourceApiController` and `Api\V2\ResourceApiController`

`{resource}` is resolved through the config map and
`abort_unless($m && class_exists($m), 404)`. v1 intersects writes with the
model's `$fillable`; **v2 does not** — keep `$fillable` on every model.

### Menu engine

Rows in `menu_items`, rendered by `MenuService::tree()` into the admin sidebar
(through `AdminMenuComposer`) and the public navbar and footer (both read
`location: primary`). A module's `module.json` `menus` array becomes rows on
install/activate; `deactivate`/`uninstall` delete them. See
[MENU_ENGINE.md](MENU_ENGINE.md).

Three things to know before you touch it: `forget()` flushes the entire cache;
the permission filter runs on the flat collection **before** nesting, so a child
whose parent was filtered out is not rendered; and `MenuController::LOCATIONS`
accepts a `footer` location that **nothing renders**.

### Theme engine

A `theme.json` may declare `tokens` (grouped or flat), legacy `settings`, and
`"views": true`. Activating a theme emits its tokens as CSS custom properties
(via `DesignTokens::toCss()`, using a doubled `:root:root` selector so it wins
the cascade) and, if it ships `views/`, prepends that directory to the view
finder — which is how a theme replaces `site/layout.blade.php` without editing
`resources/views`. `SiteController::__construct` calls
`applyViewOverrides()` once per request, inside a `try/catch`.

`ThemeManager::TOKEN_FILTER` (`theme.tokens`) is a plugin filter, so a plugin
can rewrite the token set.

**`dark-commerce` is not a real second theme.** Its manifest is equivalent to
`default`'s (both `settings.primary = #4f46e5`), it has no `tokens` block, and
its `layout.blade.php` sits at the theme root rather than in `views/`, so it is
never loaded. Activating it changes nothing visible.

### Plugin engine

`PluginInterface` (in `app/Core/Plugins/`) is:

```php
slug(): string
manifest(): array
filters(): array          // a LIST of names, not a name => callable map
filter(string $name, mixed $value, array $context = []): mixed   // null = "unchanged"
hooks(): array            // ['event.name' => 'methodName'] or a bare list
onInstall(): void
onUninstall(): void
```

`PluginLoader` matches a slug against directories on disk and instantiates
`src/Plugins/{Studly}/Plugin.php`. `PluginManager` walks the active set in
manifest-priority order so a filter chain is deterministic.

Two execution paths:

- **Filters** — `applyFilter($name, $value, $ctx)` feeds the value through
  every active plugin that declares the name; the last non-null return wins.
  `PluginManager::filters()` is a backwards-compatible static wrapper. The
  mechanism works. **The only filter name core ever passes is
  `ThemeManager::TOKEN_FILTER` (`theme.tokens`)** — nothing calls
  `applyFilter('seo.meta', …)` or `applyFilter('whatsapp.link', …)`, so those two
  bundled filters never run in a real request. Wiring a new filter means adding
  the `applyFilter()` call at the point you want it to run; declaring the name
  in a plugin is not enough.
- **Hooks** — `ensureBooted()` registers each active plugin's hooks as Laravel
  listeners once per request (`LinduCoreProvider::boot()` calls it, because the
  framework's `event()` does not reach the manager). `dispatchHook($hook,
  $payload)` invokes the handlers directly and returns the payload with the
  slugs that ran under `plugins`. The direct path works; the `event()` path
  does not — see §7.

`normaliseHooks()` reads the **class's** `hooks()` method, not the manifest's
`hooks` array, so a manifest that lists event names is documentation only. A
bare list `['page.rendered']` derives the method as `on` + studly(event).

A URL may only name an *action key* that the plugin's own manifest `actions`
map resolves to a public, non-forbidden method of a loaded plugin. The URL never
names a method. **None of the three bundled manifests declares an `actions`
map**, so a custom URL action on them is a 404.

### Module engine

Folder + `module.json` with a `slug`. `ModuleManager::discover()` scans
`config('lindu.modules_path')`; `ModuleServiceProvider::boot()` loads
`routes.php`, `routes-api.php`, `views/` (namespace `mod-{slug}`), `lang/`
(namespace `mod-{slug}`) and `database/migrations/` for every module that is
**both installed and active** in the database. A missing `modules` table means
nothing loads. The lifecycle verbs are `install`, `activate`, `deactivate`,
`uninstall` — allowlisted in `ModuleController::ACTIONS`, with no `update()`.

Eight ship: `api`, `blog`, `company-profile`, `forms`, `media`, `pages`,
`seo`, `tenants`. `company-profile` is the one that matters and it is a
manifest plus core code — its models are `app/Models/Cp/`, its controller is
`app/Http/Controllers/Admin/CompanyProfileController.php`, its admin routes
come from `routes/company.php`. See [MODULES.md](MODULES.md) and
[COMPANY_PROFILE.md](COMPANY_PROFILE.md).

### Workflow engine

Data, not code. `trigger($event, $payload)` runs every active workflow whose
`trigger_event` matches. 17 triggers, 13 condition operators, 9 action types.
Two deliberate safety rules: **an unknown operator evaluates to false** (a typo
blocks the workflow rather than firing it), and **a write action may only
touch `ContentRecord` or `ContentType`**. Every run writes a `workflow_runs`
row.

Actions run **synchronously, in the request that fired them.** See
[WORKFLOW.md](WORKFLOW.md) for the four known gaps, including that
`sendWebhook()` discards its own status expression so the run log shows `ok`
for a 500, and that the `schedule` trigger has no runner.

### Media pipeline

`MediaService` stores on the `public` disk (or `s3` via `MEDIA_DISK`), records
size/mime/dimensions, and queues thumbnail generation at 150×150, 300×300 and
800×600 plus WebP/AVIF variants. Two checks are load-bearing and both are
documented gaps: **the size limit is enforced twice** (controller validation
and `MediaService::store()`), but **`allowed_mimes` is not enforced at all** —
the only reader is `MediaController::storage()`, which displays it. Add a
`mimes:` rule before you care about file types.

### SEO

`SeoService` per row (`SeoMeta` is a `morphOne` from every content model),
global defaults from settings, `sitemap.xml` and `robots.txt` served live, plus
JSON-LD. Managed redirects live in the admin. See [API.md](API.md) and the
SEO section of [ADMIN_GUIDE.md](ADMIN_GUIDE.md).

### Licence

Two independent mechanisms. `LicenseService` is the built-in manager over the
`licenses` and `license_activations` tables plus a local lock file, with
`App\Core\Contracts\LicenseProvider` as the optional remote seam (no provider
ships). `RequirePair` middleware plus `App\Services\LicenseClient` is the
License v3 marketplace gate: AES-256-GCM lock file keyed on
`APP_KEY . ':' . domain`, RSA signature verified against
`public/marketplace.public.pem`, heartbeat with a 7-day grace window.

`routes/pair-routes.php` **is** required from `routes/web.php`, so `/__pair`
resolves and the wizard is reachable. Several docs still say it is not routed;
they are out of date. See [LICENSE.md](LICENSE.md).

### Backup

`BackupService::run($type)` writes `backups/backup-{ts}-{type}.zip` to the disk
named by `setting('storage.backup_disk', 'local')`: a `database.sql` dump, the
uploads, a `manifest.json` and a **secret-stripped** `.env.example`. No
`ext-zip` means a single concatenated `.sql` file. `restore()` requires an
explicit confirmation flag, so it can never be triggered by a stray GET.

### Update engine

`UpdateService::apply()` is backup → `migrate --force` → `MenuService::forget()`
+ `SettingService::forgetCache()` → `bumpVersion()`. It aborts if the
pre-update backup did not complete. It does **not** download code, run composer,
git pull, replace files, `optimize:clear`, or restart the queue. With no
`LINDU_UPDATE_URL` set, `check()` contacts nothing and reports
`source: local` — which means *nothing was compared*, not *you are current*.
`checkAll()` for modules/plugins/themes returns each row's own version as both
`current` and `latest`: a listing, not a check. See [UPDATES.md](UPDATES.md).

### Data builder

`content_records.data` (JSON keyed by field slug) is the source of truth; a
flat `cb_*` table mirrors it for reporting and every mirror write is wrapped in
`try/catch`. `dropPhysicalTable()` refuses any name not starting with `cb_`;
`columnName()` enforces `^[a][a-z0-9_]*$`. CSV/JSON import is row-at-a-time
and never aborts halfway, and reports every rejected row — but it **does not**
run the admin's record validation and **does not** mirror. The six relation
kinds are a UI seam with no model trait behind them. See
[DATA_BUILDER.md](DATA_BUILDER.md).

---

## 5. Tools

Five standalone PHP scripts under `tools/`, all runnable directly. They are
excluded from the release archive by `tools/release.php`.

### `php tools/blade-lint.php [path …]`

Compiles every `.blade.php` under `resources/views`, `modules`, `themes` and
`plugins` (or the paths you pass) and runs
`token_get_all($compiled, TOKEN_PARSE)` on the result.

It exists for one specific failure mode. Blade only recognises a directive when
the `@` is preceded by whitespace or a line start, so

```blade
<p>Total: @if($n){{ $n }}@endif</p>       {{-- @if is literal text, not a directive --}}
<p>Total: @if ($n) {{ $n }} @endif</p>    {{-- correct --}}
```

emits the `@if` as text, never opens the block, and still closes nothing with
`@endif`. The template compiles, `php -l` passes, and the page 500s **at
runtime only** — so it ships. `token_get_all` is used in-process rather than
shelling out to `php -l` because the sub-process stack-overflows on deeply
nested templates.

### `php tools/route-lint.php`

Boots the router, collects every named route, then scans Blade files and every
`.php` under `app/`, `routes/`, `database/` and `modules/` for `route('name')`
calls that do not exist. `route('typo')` is the same class of bug: a 500 on one
page only.

It skips `$request->route('param')` (reading a parameter, not generating a URL)
and `api.*` names. **Blind spot:** the pattern needs the closing `)` immediately
after the quoted name, so `route('api.v1.forms.submit', $slug)` is not checked.
Review dynamic names by hand.

### `php tools/screenshot.php [baseUrl] [outDir]`

Headless-Chrome renders of all 13 public pages at four widths (1920, 1366, 768,
390), writing a PNG per page per breakpoint plus a machine-readable report of
horizontal overflow and console errors. Defaults to
`http://127.0.0.1:8123` and `storage/screenshots`. Set `LINDU_CHROME` to point
at a specific browser; it auto-detects Chrome and Edge on Windows and
Chrome/Chromium on Linux.

```sh
php artisan serve --port=8123 &
php tools/screenshot.php
```

It closes the gap the structural responsive tests cannot: they assert markup,
this asserts what a browser actually lays out. **There is no visual-regression
baseline** — the images are not compared against a golden set, so a change
could alter appearance without failing anything.

### `php tools/release.php [version] [--out=dir]`

Builds `dist/CiptaCMS-v{version}.zip` from an explicit allow-list of what
ships, minus a denylist (`.git`, `vendor`, `node_modules`, `tests`, `tools`,
`deploy`, `dist`, the real `.env`, `storage/logs`, compiled views, caches,
`public/build`, an activated licence lock). It then re-opens the finished zip
and checks the actual entries for `.git`, `node_modules`, `vendor`, a real
`.env`, logs, tests, build output and `.kilo/`.

It refuses to build — non-zero exit — if a live secret is found in a file that
is about to be archived (`APP_KEY=base64:…`, `LICENSE_KEY=…`, a private key
header) or if `storage/app/lindu/license.json` exists, because shipping an
activated licence grants every customer the same one.

Version comes from the first argument or, failing that, `.release-version`.

### `php tools/test.php [filter]`

The same suite as `php artisan test`, printed as counts plus **one line per
distinct failure reason** instead of a PHPUnit stack trace. Use it to find out
*why* something failed rather than *where*:

```sh
php tools/test.php
php tools/test.php Workflow
```

### Composer scripts

```sh
composer lint:blade      # php tools/blade-lint.php
composer lint:routes     # php tools/route-lint.php
composer check           # both, then php artisan test
composer test            # config:clear + php artisan test
```

---

## 6. Tests

```
tests/
  Feature/   AuthTest  TwoFactorTest  RbacTest  AuthHardeningTest
             AdminRenderTest  AllResourcesTest  AdminEmptyStateTest
             ModuleLifecycleTest  ModuleEngineTest  ExtensionEnginesTest
             BundledPluginsTest  HookProbe2Test
             PageBuilderTest  FormBuilderTest  E2eBuilderFlowTest
             BlockLibraryIntegrityTest
             DataBuilderRelationsTest
             MediaPipelineTest  CmsApiTest  ApiV2Test  TenantIsolationTest
             PublicSiteTest  PublicSiteDesignTest  ResponsiveAccessibilityTest
             ContrastTest  TailwindTablerCollisionTest
             InstallerTest  DoctorTest  TemplateIntegrityTest
             WorkflowTest  CommentModerationTest  LicenseTest
             RepositoryAuditTest  SecurityHardeningTest
             CacheRoundTripTest  CorruptCacheTest  QuotaEnforcementTest
  Unit/      ExampleTest
```

```sh
php artisan test          # full suite
php tools/test.php        # same suite, compact failure reasons
php tools/test.php Workflow
```

**The suite is not a release gate.** Treat `php artisan test` as a diagnostic:
run it, read the failing test names, confirm your change is not among them,
re-run the area you touched. The last recorded run (commit `425080c`,
2026-09-28) was **579/580 passing, 2148 assertions**. I did not re-run it while
writing this file, so treat that number as a commit-message claim, not a fresh
measurement. `DEVELOPMENT.md` records the specific known failures.

Two tests are worth knowing about because they assert invariants rather than
features:

- **`RepositoryAuditTest`** — every `admin.*` route resolves to a real
  controller method, and every seeded admin menu URL resolves. It catches a
  rename that would otherwise ship broken navigation.
- **`TailwindTablerCollisionTest`** — fails if a Tabler component is shadowed
  by a Tailwind utility of the same name. Tailwind v4 ships a `collapse`
  utility that is identical to Tabler's `.collapse`; the utility layer won, and
  every collapse element on the site was laid out with a real bounding box and
  painted nothing. Two thousand assertions had passed before a browser found it.

Also note that three of the four tests in `tests/Feature/FormBuilderTest.php`
are `probe` tests that catch an exception and `assertTrue(true)` — they report
success whether or not the code works.

---

## 7. Known limitations

These are real, current and worth reading before you promise anything. They are
the same four the README states, plus the ones most likely to bite you.

1. **Two of the three bundled plugins are only partially wired.** `seo-booster`,
   `webhook-logger` and `whatsapp-bridge` all have real `Plugin` classes
   implementing `PluginInterface`, and both the filter chain and direct hook
   dispatch work as mechanisms. Three separate things stop them doing anything
   in a real request:
   - **Plugin event hooks are not proven end to end.** A listener registered by
     `PluginManager::ensureBooted()` does not reach the plugin through the
     framework dispatcher under the test harness, even though a listener
     registered directly in the same test does. Everything *around* the
     dispatch is verified — the listener is registered, the delivery succeeds, a
     second listener on the same event fires, and calling the handler directly
     records the row. The `TypeError` that used to make every hook throw (the
     registration closure spread the dispatcher's arguments into the plugin
     method; the dispatcher passes the payload only) was fixed in `e39797b`.
     The residual difference is still open.
   - **Core never calls `applyFilter('seo.meta', …)` or
     `applyFilter('whatsapp.link', …)`.** The only filter name core passes is
     `theme.tokens`, which none of the three declares. So `seo-booster` appends
     nothing to any page, and no `wa.me` link is produced anywhere.
   - **`whatsapp-bridge`'s `contact.message` hook can never fire.**
     `SiteController::notifyNew()` dispatches `event('cms.'.$event, …)`, i.e.
     `cms.contact.message`, and the plugin registers `contact.message`. The
     names do not match.

   `webhook-logger` is the closest to working — `WebhookDispatcher` does fire
   `webhook.delivered` / `webhook.failed`, which is exactly what it registers —
   but it still depends on the broken `event()` path. Do not advertise any of
   the three as working features.

2. **The admin interface is not translated.** `lang/en.json` and `lang/id.json`
   exist, `app/Http/Middleware/SetLocale.php` exists and reads
   `general.locale` — but **no view calls `__()`, `trans()` or `@lang`, and no
   route or group registers the middleware.** It is dead code. Only the public
   site structure is set up for translation.

3. **There is no visual-regression baseline.** `tools/screenshot.php` renders
   and reports overflow and console errors, and `ResponsiveAccessibilityTest`
   asserts markup across seven widths — but nothing compares the renders to a
   golden set.

4. **`dark-commerce` has no tokens of its own beyond the colour-mode
   setting.** See §4.

Carried over from the subsystem docs, because they will save you a day:

- **Page builder:** section `layout` is stored but never rendered (every
  section is one `.wrap` column), and the generated per-breakpoint CSS targets
  a class the `<section>` element does not carry, so section visibility works in
  the editor and not on the published page. `html` is an unsanitised passthrough
  by design.
- **Form builder:** `is_unique`, `validation`, `conditional`, `captcha` and
  `block_disposable_email` are stored and never read. `repeater` renders as a
  single text input. A `password` field stores its value in cleartext. The
  `forms` table has no `title` column — the real columns are `name`,
  `submit_text`, `submit_label`, `success_message`, `status`, `is_active`,
  `deleted_at`.
- **Workflow:** `schedule` has no runner; the inbound webhook fires
  `webhook.received` but `TRIGGERS` contains `webhook`; `sendWebhook()` throws
  away its status expression so the run log cannot show a failed HTTP action.
- **Menu:** `MenuService::forget()` is `Cache::flush()`. `MenuController`
  accepts a `footer` location that nothing renders.
- **Media:** `allowed_mimes` is not enforced.
- **Tenancy:** `TenantService::resolve()` applies `status = 'active'` outside
  the `orWhere`, so a suspended tenant still resolves on an exact domain match;
  and the first host label is compared to `tenants.subdomain` for any TLD. Only
  `MenuService` and the `cb_*` mirror filter by `tenant_id` at all.
- **Licence:** `LicenseService::install()` writes the licence key in cleartext
  despite a comment claiming encryption.
- **Payments:** five adapters and a resolver, with no checkout, no callback
  route, no reconciliation, and no signature verification on the Xendit
  callback. `XenditGateway::mode()` has no effect — both branches return the
  same host.
- **Security:** `throttle:api` is defined but no API route uses it, so the REST
  API is unthrottled by the framework; `POST /api/v1/webhooks/in/{key}` looks
  the hook up by its secret and `WebhookDispatcher::verify()` is never called on
  that path; `Api\V2\ResourceApiController` does not intersect writes with
  `$fillable`; `Settings → Advanced → Allow raw SQL` is a row nothing reads.

Several of these are out of date in the other direction. The subsystem docs
still describe `/__pair` as unrouted, `ThemeManager::deactivate()` as missing,
the module engine as fail-open, the plugin interface as a map, and
`MenuController@create`/`@edit` as dead routes. All of those have been fixed.
**When the docs and the code disagree, the code is right — read the source.**

---

## 8. Adding things

Full recipes are in [DEVELOPMENT.md](DEVELOPMENT.md). The short version:

| You are adding | Touch |
|---|---|
| A model + admin CRUD + API | `$fillable` on the model, one entry in `config/lindu_admin.php` |
| A page-builder component | one entry in `BlockLibrary::catalog()` **and** one `match` arm in `renderBlock()` |
| A form field type | `FormField::TYPES`, a `match` arm in `FormRenderer::field()`, and a submit rule |
| A workflow trigger / condition / action | the constants at the top of `WorkflowEngine` |
| A module | a folder with `module.json`; the provider loads the rest |
| A plugin | `src/Plugins/{Studly}/Plugin.php` implementing `PluginInterface` |
| A theme | a folder with `theme.json`, plus `views/` and `"views": true` if it replaces layouts |

**Adding a model to `WorkflowEngine::WHITELISTED_MODELS` is a security
decision** — anyone who can edit a workflow can then write to that model.

### Before you commit

```sh
php tools/blade-lint.php
php tools/route-lint.php
php artisan test
```

Then read the failures. Do not paper over one with a skipped assertion.

---

## 9. Artisan commands

Every one of these exists; verified with `php artisan list`.

| Command | What it does |
|---|---|
| `lindu:doctor` | PHP version, ten extensions, writable paths, DB, maintenance mode, `APP_KEY`, free disk, storage link. Exits non-zero on failure. |
| `lindu:install` | Seed data. `--admin-email=`, `--admin-password=`, `--fresh` |
| `lindu:backup` | Run a backup now. `--type=full\|database\|files`. Also scheduled daily |
| `lindu:restore` | **does not exist** — restore is admin-only (`/admin/backups/restore`) and requires an explicit confirmation |
| `lindu:check-updates` | Report versions. Also scheduled daily |
| `lindu:quota-recount` | Recalculate plan usage counters from the source tables. `--tenant=`. Also scheduled daily |
| `lindu:search-index` | Push DB content into Meilisearch. `--driver=`, `--limit=` (default 200) |
| `lindu:unlock` | Remove the install lock. `--force`; `--token` prints the recovery token without unlocking |
| `webhooks:retry` | Retry failed webhook deliveries. Scheduled every five minutes |

`routes/console.php` schedules `lindu:backup --type=full` daily,
`lindu:check-updates` daily, `webhooks:retry` every five minutes,
`queue:prune-failed --hours=72` daily and `lindu:quota-recount` daily. Run it
with `* * * * * cd /path && php artisan schedule:run`.

---

## See also

[DEVELOPMENT.md](DEVELOPMENT.md) · [ARCHITECTURE.md](ARCHITECTURE.md) ·
[ADMIN_GUIDE.md](ADMIN_GUIDE.md) · [DATABASE.md](DATABASE.md) ·
[API.md](API.md) · [PAGE_BUILDER.md](PAGE_BUILDER.md) ·
[FORM_BUILDER.md](FORM_BUILDER.md) · [DATA_BUILDER.md](DATA_BUILDER.md) ·
[MENU_ENGINE.md](MENU_ENGINE.md) · [WORKFLOW.md](WORKFLOW.md) ·
[THEMES.md](THEMES.md) · [MODULES.md](MODULES.md) · [PLUGINS.md](PLUGINS.md) ·
[COMPANY_PROFILE.md](COMPANY_PROFILE.md) · [UPDATES.md](UPDATES.md) ·
[LICENSE.md](LICENSE.md) · [SAAS.md](SAAS.md) · [SECURITY.md](SECURITY.md) ·
[TROUBLESHOOTING.md](TROUBLESHOOTING.md) · [UPGRADE.md](UPGRADE.md) ·
[CHANGELOG.md](CHANGELOG.md)
