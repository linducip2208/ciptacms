# Lindu CMS — Reusable Application Platform (English)

> Also available in [Indonesia](README.id.md) · [العربية](README.ar.md)

Lindu CMS is a **Laravel 13 + PHP 8.3** CMS and application foundation for Jodohku, POS, ERP, Hotel, LMS, Marketplace, SaaS and Company Profiles. **Core never contains app-specific logic** — everything business lives in `modules/`.

## Features
### 1. Core Engine
Bootstrap, config (`config/lindu.php`, `tenancy.php`), providers, events/listeners, jobs/queue, scheduler, cache, audit log, exception handling, storage, localization (id/en), health checks, backup-ready, import/export.

### 2. Auth & Security
Login, register, logout, password reset, remember-me, rate-limit, login history, account activate/deactivate, **TOTP 2FA** (`/admin/security/2fa` + `/2fa/challenge` + backup codes), device/session manager (`/admin/security/sessions`), social OAuth-ready (`/oauth/{provider}`), Sanctum tokens, RBAC.

### 3. RBAC
Users, roles, permissions, groups, policies, 127 seeded permissions (view/create/read/update/delete/publish/approve/export/import/manage/configure), `permission` middleware.

### 4. Menu Engine
DB-driven, unlimited nesting, icon/route/URL/permission/roles/module/badge/sort/target/visibility/meta, drag-order admin, modules auto-register.

### 5-7. Module / Plugin / Theme Engines
Lifecycle discover→install→activate→deactivate→uninstall, registry tables, 15 modules, 3 plugins (hooks/filters), 2 themes, activation, settings, custom CSS/JS, child-ready.

### 8-9. CMS + SEO
Pages (draft/published/scheduled/trash/revisions/builder/templates), blog (posts/categories/tags/comments moderation), media **queue** (`ProcessMediaJob`: 150/300/800 thumbs + WebP + AVIF via GD), SEO meta/OG/Twitter/sitemap/robots/schema.

### 10-14. Builders + Workflow
**Page Builder drag-drop** (21 blocks, desktop/tablet/mobile visibility, undo/redo, live preview, reusable blocks, `BlockLibrary::render()`), Form builder (19 fields, validation, conditional, submissions, webhook), Data builder (`cb_*` physical tables + instant CRUD/API), Workflow trigger→condition→action + runs.

### 15-17. Notifications / API / Webhooks
DB+mail notifications, queue; **REST v1+v2** (`/api/v1|v2/`, Bearer, pagination/filter/sort/search, v2 `?include=&fields=`), OpenAPI at `/api/docs/openapi.json`; webhooks in/out, HMAC, retry job.

### 18-20. Dashboard / Settings / Media
Permission-aware widgets, stats, activity; settings groups+encrypted secrets; media manager with variants/srcset.

### 21-27. Tenancy / SaaS / License / Update / Backup
Tenants+domains+quotas, plans/subscriptions/trial, white-label, licenses (active/inactive/expired/suspended/banned), update check+pre-backup, backups, audit log.

### 28-35. Search / Import-Export / Tasks / Admin UX
Global search abstraction, CSV export/import, tasks, responsive admin (dark/light, sidebar, breacrumb, palette, toasts, empty/loading states).

### 36. Installer + SDK + Apps
Web installer `/install`, console `lindu:install`; 15 modules: ecommerce, POS (sales/purchases), hotel, LMS, CRM, ERP, marketplace, **Jodohku** (profiles/preferences/likes/favorites/blocks/reports/memberships).

### Payments / PWA / Tenant-DB
Adapters Xendit/iPaymu/Tripay/Stripe/Manual (`/admin/gateways` + test), PWA (`manifest.webmanifest` + `sw.js`), tenant separate-DB ready (`config/tenancy.php`).

## Install / Develop / Test
```sh
composer install; cp .env.example .env; php artisan key:generate
php artisan migrate --seed; php artisan storage:link; php artisan serve
php artisan test; php artisan route:list
php artisan queue:work; php artisan schedule:run
```
Default login `admin@lindu.local / password123`.
