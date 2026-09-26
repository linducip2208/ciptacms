# Architecture

```
LINDU = Core + CMS + Auth + RBAC + Menu + Module + Plugin + Theme
      + Builders (Page/Form/Data/Workflow) + API + Webhooks + Media + SEO
      + Tenancy + SaaS + License + Update + Backup + Apps
```

- **Stack** — Laravel 13, PHP 8.3, MySQL 8 / SQLite, Tabler (admin), vanilla JS
  + Alpine (the page builder). No build step is required for the admin or the
  public site; `npm`/`vite` are optional.
- **Entry points** — `bootstrap/app.php` registers `routes/web.php`,
  `routes/api.php`, `routes/console.php` and a `/up` health endpoint.

## Layers

| Path | Responsibility |
|---|---|
| `app/Core/Services/` | 28 services, all registered as singletons by `LinduCoreProvider` |
| `app/Core/Contracts/` | `PaymentGateway`, `LicenseProvider` — the two pluggable seams |
| `app/Core/Traits/` | `Auditable`, `BelongsToTenant`, `HasSlug` |
| `app/Core/Support/helpers.php` | `setting()`, `tenant()`, `tenant_id()`, `lindu_version()`, `user_can()` |
| `app/Http/Controllers/Admin/` | 25 admin controllers |
| `app/Http/Controllers/Api/V1`, `V2` | REST surfaces |
| `app/Http/Controllers/Frontend/` | `SiteController` (public company site), `HomeController` (sitemap/robots) |
| `app/Models/` | 112 Eloquent models (100 core + 12 `App\Models\Cp`) |
| `app/View/Composers/` | `AdminMenuComposer` — injects `$adminMenu` into every `admin.*` view |
| `app/Http/Middleware/` | `CheckPermission` (`permission:`), `ResolveTenant` (`tenant:`), `CheckInstalled` (`installed:`), `RequirePair` |
| `modules/`, `plugins/`, `themes/` | folder + manifest extensions — see [MODULES.md](MODULES.md), [PLUGINS.md](PLUGINS.md), [THEMES.md](THEMES.md) |
| `config/lindu.php` | product version, extension paths, media, security, backup, updates, license, payments |
| `config/lindu_admin.php` | the generic-resource map that drives both admin CRUD and the API |
| `config/license.php` | License v3 pairing — see [LICENSE.md](LICENSE.md) |

## Services

`app/Core/Services/`:

```
AuditService        BackupService        BlockLibrary        CompanyProfileService
DataBuilderService  FeatureFlag          FormRenderer        HealthService
ImportExportService LicenseService       MediaService        MenuService
ModuleManager       NotificationService  PaymentManager      PluginManager
QrService           SearchService        SeoService          SettingService
TenantService       ThemeManager         TwoFactorService    UpdateService
WebhookDispatcher   WorkflowEngine
Payments/           GenericGateway  HostedGateway  IpaymuGateway
                    StripeGateway   TripayGateway   XenditGateway
```

All are `singleton`s in `LinduCoreProvider::register()`.

## Generic admin CRUD

`config/lindu_admin.php` maps 28 slugs to `{model, label, search}`. That single
map powers, with no per-resource code:

- `Admin\ResourceController` → `/admin/r/{resource}` (index, create, store,
  edit, update, destroy, bulk, CSV export)
- `Api\V1\ResourceApiController` and `Api\V2\ResourceApiController`

`{resource}` is resolved through the config map and `abort_unless($m &&
class_exists($m), 404)` — never turned into a class name from user input. The
same whitelist pattern is used by `CompanyProfileController::RESOURCES` and
`WorkflowEngine::WHITELISTED_MODELS`.

## Permissions

- `User::hasPermission($slug)` and `User::hasRole([…])`; the helper
  `user_can('slug')` wraps the first.
- Route guard: middleware alias `permission:` → `CheckPermission`.
- `AppServiceProvider` installs `Gate::before()` granting everything to
  `super-admin` and `admin` — so a permission check is *not* enough on its own
  if you want strict separation between the two.
- 127 permissions are seeded by `RolesPermissionsSeeder` across 5 groups
  (`cms`, `commerce`, `apps`, `system`, `saas`) and 4 roles (`super-admin` 100,
  `admin` 90, `manager` 50, `member` 10).

Module and plugin manifests contribute more permissions through
`ModuleManager::registerPermissions()`, which splits a manifest entry like
`"company.manage"` into `action = manage`, `module = company`, grouped under a
`PermissionGroup` named after the module slug.

## Tenancy

`ResolveTenant` is appended to both the `web` and `api` groups and calls
`TenantService::resolve($host)`, which matches `tenants.domain` or the first
host label against `tenants.subdomain` for an `active` tenant, then binds it as
the `tenant` container instance. `tenant()` / `tenant_id()` read that binding.
`BelongsToTenant` fills `tenant_id` on the models that need it.

`config('lindu.tenant_mode')` is read from `LINDU_TENANT_MODE` (default
`single`), but **no code branches on it** — the middleware resolves a tenant on
every request regardless. See [SAAS.md](SAAS.md).

## Multi-tenancy caveat

`TenantService::resolve()` is:

```php
Tenant::where('domain', $host)
      ->orWhere('subdomain', explode('.', $host)[0])
      ->where('status', 'active')
      ->first();
```

Two problems worth knowing before a multi-tenant sale: the `orWhere` is not
grouped with the `status` filter, so a **suspended** tenant still resolves if the
host matches; and the first label of the host is compared against
`tenants.subdomain` for *any* TLD, so `anything.test` matches a tenant whose
subdomain is `anything`. `MenuService` and `BlockLibrary` do scope by
`tenant_id`, but most core queries do not add a tenant filter at all.

## The linters

Two standalone scripts, plus the test suite:

```sh
php tools/blade-lint.php     # composer lint:blade
php tools/route-lint.php     # composer lint:routes
composer check               # both, then php artisan test
```

**Why `blade-lint.php` exists.** Blade only recognises a directive when the `@`
is preceded by whitespace or a line start. A directive written immediately after
a word character is not matched, so it is emitted into the compiled output as
literal text — and the block it was supposed to open is never opened, while its
`@endif` still is. The template compiles, `php -l` passes, and the failure is a
runtime-only 500 on exactly the page that uses the template:

```blade
{{-- broken: @endif stays, the @if never opened --}}
<p>@if($x)Hello@endif</p>          {{-- fine --}}
<p>Total: @if($n){{ $n }}@endif</p>  {{-- @if is not a directive here --}}
```

`blade-lint.php` compiles every `.blade.php` under `resources/views`, `modules`,
`themes` and `plugins` and then runs `token_get_all($compiled, TOKEN_PARSE)` on
the result, so a template that compiles to invalid PHP fails the check. It uses
`token_get_all` in-process rather than shelling out to `php -l` because the
sub-process stack-overflows on deeply nested templates. It accepts explicit
paths: `php tools/blade-lint.php resources/views/site`.

**Why `route-lint.php` exists.** `route('typo')` is not a compile error; it is a
500 on the one page that calls it, and only that page. `route-lint.php` boots the
router, collects every named route, then scans Blade files for
`route('name')` and `.php` files under `app/`, `routes/`, `database/` and
`modules/` for the same. It skips `$request->route('param')` (reading a route
*parameter*, not generating a URL) and `api.*` names.

It will **not** catch a dynamic name with arguments after it — the pattern
requires the closing `)` immediately after the quoted name, so
`route('api.v1.forms.submit', $slug)` is not checked. That is a real gap, not an
oversight in the docs.

## The test suite

```sh
php artisan test          # full suite
php tools/test.php        # same suite, compact output: counts + distinct failure reasons
php tools/test.php Workflow   # filtered
```

`tests/Feature/` covers, by area: auth (`AuthTest`), 2FA (`TwoFactorTest`), RBAC
(`RbacTest`), menus and generic admin rendering (`AdminRenderTest`,
`AllResourcesTest`), module/plugin/theme lifecycle (`ModuleLifecycleTest`), the
CMS and its builders (`PageBuilderTest`, `FormBuilderTest`, `E2eBuilderFlowTest`),
media (`MediaPipelineTest`), the API (`CmsApiTest`, `ApiV2Test`), tenancy
(`TenantIsolationTest`), the public site (`PublicSiteTest`), the installer
(`InstallerTest`), the doctor command (`DoctorTest`), template integrity
(`TemplateIntegrityTest`), workflows (`WorkflowTest`) and licensing
(`LicenseTest`, `RepositoryAuditTest`).

**The suite is not green at the moment.** Treat `php artisan test` as a
diagnostic, not a gate, and read the failure list before assuming a change is
safe — see [DEVELOPMENT.md](DEVELOPMENT.md) for the current state and the
ad-hoc static audits you can run yourself.

## Money

`App\Core\Contracts\PaymentGateway` declares `name()`, `charge()`,
`verifyCallback()` and `parseCallback()`. `HostedGateway` implements the shared
request/response handling; `XenditGateway`, `IpaymuGateway`, `TripayGateway`,
`StripeGateway` and `GenericGateway` supply an endpoint, headers, a body builder
and a response interpreter. `PaymentManager` resolves one by key from
`config('lindu.payments.adapters')`.

**This is an interface and a set of adapters, not a billing integration.** There
is no subscription lifecycle, no invoice reconciliation, no dunning, and no
per-tenant plan enforcement. The admin screen at `/admin/gateways` only stores
`billing.gateways` in `settings`. Do not describe it as billing in a customer
proposal — see [SAAS.md](SAAS.md).

## Docs

| File | Covers |
|---|---|
| [INSTALL.md](INSTALL.md) | requirements and install |
| [DEVELOPMENT.md](DEVELOPMENT.md) | day-to-day work, commands, the linters, adding a resource |
| [DEPLOYMENT.md](DEPLOYMENT.md) | VPS deploy |
| [API.md](API.md) | v1 / v2 REST, auth, webhooks |
| [DATABASE.md](DATABASE.md) | schema conventions |
| [PAGE_BUILDER.md](PAGE_BUILDER.md) | the page builder and its 21 components |
| [FORM_BUILDER.md](FORM_BUILDER.md) | forms, fields, anti-spam, submissions |
| [DATA_BUILDER.md](DATA_BUILDER.md) | content types, records, the `cb_*` mirror, import/export |
| [MENU_ENGINE.md](MENU_ENGINE.md) | `menu_items`, visibility, module-declared menus |
| [WORKFLOW.md](WORKFLOW.md) | triggers, conditions, actions, the model whitelist |
| [WHITE_LABEL.md](WHITE_LABEL.md) | branding settings, domains, theme customizer |
| [COMPANY_PROFILE.md](COMPANY_PROFILE.md) | the `cp_*` module and its public routes |
| [UPDATES.md](UPDATES.md) | the update centre and why it never runs remote code |
| [LICENSE.md](LICENSE.md) | the license manager and the License v3 pairing gate |
| [MODULES.md](MODULES.md) · [PLUGINS.md](PLUGINS.md) · [THEMES.md](THEMES.md) | extension engines |
| [SAAS.md](SAAS.md) | tenants, plans, quotas, the payment seam |
| [SECURITY.md](SECURITY.md) | what is enforced, and what is not |
| [TROUBLESHOOTING.md](TROUBLESHOOTING.md) | the 503 walkthrough |
