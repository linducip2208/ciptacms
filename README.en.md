# Lindu CMS — Reusable Application Platform (English)

> Also available in [Indonesia](README.id.md) · [العربية](README.ar.md) ·
> [Bahasa Indonesia ringkas](README.md)

Lindu CMS is a **commercial, white-label company CMS** on **Laravel 13 +
PHP 8.3 + MySQL/SQLite + Tabler**. Core never contains app-specific logic —
business features live as folder + manifest extensions in `modules/`, `plugins/`
and `themes/`.

This documentation is written from the source and carries explicit
**"Known gaps"** sections. Read those before quoting a feature to a customer.

## What actually exists

| Area | Contents | Doc |
|---|---|---|
| Core | 28 singleton services, 3 providers, middleware, audit log, health checks, backup, import/export | [ARCHITECTURE.md](ARCHITECTURE.md) |
| Auth | Login / register / logout / reset, remember-me, TOTP 2FA + backup codes, session manager, Socialite OAuth, Sanctum | [SECURITY.md](SECURITY.md) |
| RBAC | Users, roles, permissions, groups, **127** seeded permissions in 5 groups, 4 roles, `permission:` middleware | [ARCHITECTURE.md](ARCHITECTURE.md) |
| Menus | `menu_items`, unlimited nesting, server-side permission + role filtering, modules auto-register their menus | [MENU_ENGINE.md](MENU_ENGINE.md) |
| Extension engine | Module / plugin / theme: discover → registry → activate / deactivate / uninstall | [MODULES.md](MODULES.md) · [PLUGINS.md](PLUGINS.md) · [THEMES.md](THEMES.md) |
| CMS | Pages (draft / published / scheduled / trash / revisions / templates), blog + comment moderation, queued media (150/300/800 thumbs + WebP/AVIF), SEO meta / OG / sitemap / robots / schema | [PAGE_BUILDER.md](PAGE_BUILDER.md) |
| Page builder | Real HTML5 drag-and-drop, **21 components**, 60-step undo/redo, copy/paste/duplicate, save-as-block, templates, per-breakpoint visibility | [PAGE_BUILDER.md](PAGE_BUILDER.md) |
| Form builder | **18 field types**, per-type validation, 5 anti-spam layers, `POST /api/v1/forms/{slug}` | [FORM_BUILDER.md](FORM_BUILDER.md) |
| Data builder | Runtime content types, JSON record + `cb_*` physical mirror, CSV/JSON import with per-row error reporting | [DATA_BUILDER.md](DATA_BUILDER.md) |
| Workflow | 17 triggers, 13 condition operators, 9 actions, model whitelist, run log | [WORKFLOW.md](WORKFLOW.md) |
| Company profile | 12 `cp_*` tables, 8 whitelisted CRUD resources, 18 public routes | [COMPANY_PROFILE.md](COMPANY_PROFILE.md) |
| API | REST `v1` + `v2` (Bearer, pagination, filter, sort, search, `?fields=` / `?include=`), 28 generic resources, OpenAPI document | [API.md](API.md) |
| Webhooks | Outbound (HMAC + queue + retry + log) and inbound | [API.md](API.md) |
| Multi-tenant | Tenants, domains, plans, feature flags, usage quotas, read-only subscriptions | [SAAS.md](SAAS.md) |
| White label | 6 branding sections, custom domains, theme customizer → CSS custom properties | [WHITE_LABEL.md](WHITE_LABEL.md) |
| Licensing | Built-in license manager **and** the License v3 pairing gate (RSA signature + AES-256-GCM lock) | [LICENSE.md](LICENSE.md) |
| Updates | Backup → migrate → clear cache → record version. **Never executes remote code** | [UPDATES.md](UPDATES.md) |
| Installer | `/install` (3 steps, locked once used) + `php artisan lindu:install` | [INSTALL.md](INSTALL.md) |
| Ops | `lindu:doctor`, `lindu:backup`, `lindu:search-index`, `lindu:check-updates`, `webhooks:retry`, queue monitor, S3 presigned upload | [DEVELOPMENT.md](DEVELOPMENT.md) |

## What is not there — read before you sell it

- **The business modules are stubs.** `pos`, `hotel`, `lms`, `crm`, `erp`,
  `ecommerce`, `marketplace`, `jodohku` and `api` contain only a `module.json`
  and one placeholder route. What exists is **schema plus generic CRUD** — no POS
  transaction, no room-availability check, no quiz runner, no marketplace
  checkout. See [MODULES.md](MODULES.md).
- **Plugins are stubs.** `seo-booster`, `webhook-logger` and `whatsapp-bridge`
  are manifest-only; there is no plugin code loader, and
  `PluginManager::filters()` returns its input unchanged.
- **Theme layouts are not used.** `themes/*/layout.blade.php` is never rendered;
  the public site uses `site/layout.blade.php` plus `branding.*` settings.
  `ThemeManager::deactivate()` does not exist, so the deactivate button always
  reports an error.
- **Payments are a seam, not billing.** Five adapters (Xendit, iPaymu, Tripay,
  Stripe, Generic) over `PaymentGateway`, with no checkout, reconciliation,
  invoicing, proration or dunning. Do not call this billing.
- **Per-breakpoint visibility has no effect** on the rendered page, and a
  section's `layout` value is not rendered. See
  [PAGE_BUILDER.md](PAGE_BUILDER.md#known-gaps).
- **Multi-tenancy is not scoped.** Only the menu tree and the `cb_*` mirror
  filter by `tenant_id`. See
  [SAAS.md](SAAS.md#scoping-caveats--read-before-a-multi-tenant-sale).
- **The `api`, `webhook-in` and `login` rate limiters are registered but unused.**
  Only `throttle:form-submit` is applied. See
  [SECURITY.md](SECURITY.md#not-enforced--read-this-before-you-sell-it).
- **Update checking without an endpoint always reports "local".**
  `LINDU_UPDATE_URL` is empty by default, so `check()` returns the installed
  version as latest with `source: "local"` — that means *nothing was checked*,
  not "up to date".
- **PWA is minimal.** A `manifest.webmanifest` exists; `sw.js` is
  `skipWaiting()` plus an empty `fetch` handler.
- **The test suite is not green.** Run `php artisan test` and read the failures.

## Quickstart

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

`.env.example` uses SQLite, so no database server is needed. Sign in with
`admin@lindu.local` / `password123` — **change it before anyone else can reach
the site.**

```sh
composer check          # blade-lint + route-lint + the test suite
php artisan lindu:doctor
```

For production: [INSTALL.md](INSTALL.md) then [DEPLOYMENT.md](DEPLOYMENT.md).

## Layout

```
app/Core/Services/        28 singleton services + Payments/
app/Core/Contracts/       PaymentGateway, LicenseProvider
app/Models/               112 models (100 core + 12 App\Models\Cp)
config/lindu_admin.php    28 generic resources → admin CRUD + REST API
modules/ plugins/ themes/ folder + manifest extensions
database/migrations/      21 files, 126 tables
tools/                    blade-lint.php, route-lint.php, test.php
```

## Documentation

Start at **[ARCHITECTURE.md](ARCHITECTURE.md)**, then:

[INSTALL](INSTALL.md) · [DEVELOPMENT](DEVELOPMENT.md) · [DEPLOYMENT](DEPLOYMENT.md) ·
[PAGE_BUILDER](PAGE_BUILDER.md) · [FORM_BUILDER](FORM_BUILDER.md) ·
[DATA_BUILDER](DATA_BUILDER.md) · [MENU_ENGINE](MENU_ENGINE.md) ·
[WORKFLOW](WORKFLOW.md) · [COMPANY_PROFILE](COMPANY_PROFILE.md) ·
[WHITE_LABEL](WHITE_LABEL.md) · [UPDATES](UPDATES.md) · [LICENSE](LICENSE.md) ·
[API](API.md) · [DATABASE](DATABASE.md) · [MODULES](MODULES.md) ·
[PLUGINS](PLUGINS.md) · [THEMES](THEMES.md) · [SAAS](SAAS.md) ·
[SECURITY](SECURITY.md) · [TROUBLESHOOTING](TROUBLESHOOTING.md)

Source: https://github.com/linducip2208/ciptacms
