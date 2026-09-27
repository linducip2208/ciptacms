<div align="center">

# CiptaCMS

**A white-label company-profile CMS and application core, built on Laravel 13.**

[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777bb4?logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-13-ff2d20?logo=laravel&logoColor=white)](https://laravel.com)
[![Tabler](https://img.shields.io/badge/Tabler-1.4-0d6efd?logo=tabler&logoColor=white)](https://tabler.com)
[![Tests](https://img.shields.io/badge/tests-558%20passing-success)](#testing)

[English](README.en.md) · [Bahasa Indonesia](README.id.md) · [العربية](README.ar.md)

</div>

---

## What this is

CiptaCMS is two products in one repository:

1. **A company-profile CMS** — the public website plus everything behind it: page builder, form builder, media, menu engine, blog, FAQ, testimonials, portfolio, careers, SEO.
2. **An application core** — the engines, admin and extension points other products are built on.

It is sold to companies that want their own website without a developer, and to developers
who want a CMS core to build on. It is licensed per product and domain.

**This is a commercial product, not a demo.** The numbers below are measured from the repository
at the current commit, and every claim is backed by a test. Where something is *not* finished,
it is listed in [Known gaps](#known-gaps) rather than glossed over.

---

## Measured facts

| | |
|---|---|
| Tests passing | **558 / 559** (1 skipped: file mode `0600` is a no-op on Windows) |
| Assertions | **2080** |
| HTTP routes | **391** |
| Public page-builder components | **21** |
| Migrations | **23** |
| Eloquent models | **112** |
| Core services | **30** |
| Reusable public UI components | **17** |
| Frontend build | Vite + Tabler 1.4, bundled locally, **no runtime CDN** |

---

## Features

### Public website (company profile)

Home · About · Services · Products · Portfolio · Team · Testimonials · Clients · FAQ ·
Gallery · Careers · Blog · Contact — plus detail pages for service, product, portfolio item,
career, blog post, and any CMS page.

- **Content is database-driven.** Nothing on a public page is hard-coded.
- **Navigation comes from the Menu Engine.** Parent items render as dropdowns, an item flagged
  as a CTA renders as a button, and the same tree feeds the desktop navbar, the mobile
  offcanvas and the footer.
- **Demo content ships with the installer** and is fully editable and deletable from the admin.
- **Contact form and job applications** persist to the database and notify the administrator.
- **Maps, social links, business hours, phone, WhatsApp** all read from company-profile settings.

### Design system

- **Tabler 1.4 is the foundation** for both the public site and the admin — the same bundle.
- **CMS branding is bridged onto Tabler's own CSS properties** (`--tblr-primary`, `--tblr-border-radius`,
  `--tblr-font-sans-serif`, …), so a white-label install recolours the product without forking Tabler.
- **18 design tokens** read from the database: colours, fonts, weights, radius, container width,
  section spacing, header height, footer colours.
- **Light and dark mode**, following the theme setting or the visitor's OS preference.
- **One stylesheet.** Tabler + Tailwind utilities + design tokens, compiled to a single file.
- **No runtime CDN.** A network outage cannot break the site, the admin, or the login page.

### Page builder

21 components: heading, text, image, video, button, icon, card, grid, gallery, slider, tabs,
accordion, testimonials, pricing, team, contact, map, form, html, code, dynamic content.

- Real HTML5 drag-and-drop; inspector fields generated from the component catalog.
- Sections with per-breakpoint visibility (desktop / tablet / mobile).
- Undo / redo, copy, paste, duplicate, delete, reorder.
- Save any component as a reusable block; reuse saved blocks across pages.
- Page templates; applying one snapshots the previous layout.
- Every component's output is escaped, responsive, and rendered through the same design system.

### Form builder

- Drag-and-drop builder; 19 field types.
- Validation, required flags, unique constraints, options.
- **Layered anti-spam:** honeypot field, minimum fill time, blocked-word list, per-IP rate limit.
- Submissions stored in the database, browsable and exportable in the admin.
- Public forms render with the same Tabler form classes as every other form.
- Forms can be embedded in any page through the page builder's `form` component.

### Menu engine

Unlimited nesting, per-item URL or named route, icon, permission, badge, target, sort order,
visibility, and free-form metadata. Two locations ship: `admin` (sidebar) and `primary` (public).
Modules declare their own menu entries from their manifest.

### Theme, plugin and module engines

- **Modules** — folder plus manifest. Discovered, installed, activated, deactivated, with
  dependency checking. **Only installed *and* active modules wire up routes, views, translations
  and migrations**; nothing on disk is reachable until an operator enables it.
- **Plugins** — same discovery, plus a versioned contract (`PluginInterface`) with a working
  filter chain and event hooks.
- **Themes** — design tokens, customizer, and activation that changes what visitors actually see.

### Media library

Upload with MIME allow-list enforcement, folders, search, filter, thumbnails, WebP/AVIF variants,
metadata, alt text and captions, trash and restore. Disk is configurable; S3-compatible ready.

### SEO

Per-page and per-post metadata, global defaults, OpenGraph and Twitter/X cards, canonical URLs,
JSON-LD organisation and breadcrumb schema, `sitemap.xml` generated live, `robots.txt` from settings,
and managed redirects.

### Content relations

Content types can define relations to each other — `hasOne`, `hasMany`, `belongsTo`,
`belongsToMany`, `morphOne`, `morphMany` — resolved on demand and exposed through `?include=`
on the API. To-many relations use a real pivot table; nothing is resolved eagerly.

### Roles, permissions and API

- Roles, permission groups, granular permissions, enforced in routes, controllers and the API.
- REST API at `/api/v1` with Sanctum tokens, pagination, filtering, sorting, search and relations.
- Inbound and outbound webhooks with HMAC verification that **fails closed**.

### Administration

Users, roles, permissions, groups, login history, sessions, 2FA, activity log, audit log,
system health, logs, queue, scheduled tasks, cache, storage, database, backup and restore,
maintenance mode, installer and update centre.

### Plans, limits and licensing

Plan limits and feature flags that are **enforced**, not displayed: a plan limit stops the action
and returns HTTP 402, and a gated feature refuses with HTTP 403. Counters are recounted nightly.

Marketplace licence kit with domain binding, activation limits, expiry, suspension and
version entitlement.

### Safety net

- **Install wizard** with a requirements gate that refuses to continue on an unsupported host,
  and an install lock that can only be lifted from the server console.
- **Backups** that produce a real SQL dump plus application files, with a restore that requires
  explicit confirmation.
- **Update engine** that takes a backup, migrates and clears caches — and never executes
  remote code. Without a configured update endpoint it reports "nothing was checked" rather
  than claiming to be up to date.

---

## Requirements

PHP 8.3+ with `pdo`, `mbstring`, `openssl`, `fileinfo`, `gd`, `zip` and `curl` ·
MySQL 8 / MariaDB 10.6+ or SQLite 3 · Composer 2 · Node 18+

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Then open `/install` to finish configuration, or sign in at `/login` with the seeded
administrator. Full instructions in [INSTALL.md](INSTALL.md).

## Documentation

| | |
|---|---|
| [ARCHITECTURE.md](ARCHITECTURE.md) | How the codebase is arranged |
| [DATABASE.md](DATABASE.md) | Schema and relationships |
| [INSTALL.md](INSTALL.md) | Installation |
| [DEVELOPMENT.md](DEVELOPMENT.md) | Working on the codebase |
| [DEPLOYMENT.md](DEPLOYMENT.md) | Production deployment |
| [SECURITY.md](SECURITY.md) | Security model |
| [API.md](API.md) | REST API |
| [PAGE_BUILDER.md](PAGE_BUILDER.md) | Page builder |
| [FORM_BUILDER.md](FORM_BUILDER.md) | Form builder |
| [DATA_BUILDER.md](DATA_BUILDER.md) | Content types and relations |
| [MENU_ENGINE.md](MENU_ENGINE.md) | Navigation |
| [WORKFLOW.md](WORKFLOW.md) | Automation |
| [WHITE_LABEL.md](WHITE_LABEL.md) | Rebranding |
| [THEMES.md](THEMES.md) | Theme engine |
| [MODULES.md](MODULES.md) · [PLUGINS.md](PLUGINS.md) | Extension engines |
| [LICENSE.md](LICENSE.md) | Licensing |
| [UPDATES.md](UPDATES.md) | Update engine |
| [COMPANY_PROFILE.md](COMPANY_PROFILE.md) | Company-profile module |
| [SAAS.md](SAAS.md) · [TROUBLESHOOTING.md](TROUBLESHOOTING.md) | Operations |

## Testing

```bash
php tools/blade-lint.php     # every template compiles to valid PHP
php tools/route-lint.php     # every route() name in a template exists
php tools/screenshot.php     # headless-browser renders + screenshots
composer check               # all of the above plus the test suite
```

## Known gaps

Stated plainly, because a product that hides its gaps cannot be sold honestly:

- **Two of the three bundled plugins are stubs.** `webhook-logger` and `whatsapp-bridge` have
  manifests but no implementation. `seo-booster` is real and working. The plugin engine itself
  is proven; the other two folders are not.
- **No marketplace licence has been activated against the real server.** The kit's code path
  is tested with a stand-in key pair; a genuine `whitelabel.co.id` activation has not been
  performed.
- **Accessibility is tested structurally, not by a full audit.** Alt text, labels, ARIA, heading
  order and colour contrast are asserted automatically. Screen-reader passes and keyboard-only
  walks have not been run by a person.
- **Responsive coverage is structural plus screenshots.** Seven widths are captured by a headless
  browser, and tests assert no fixed-width element causes overflow — but there is no visual
  regression baseline, so a future change could alter appearance without failing a test.
- **i18n of the admin interface is not implemented.** The `lang/` directory exists and the
  translation pipeline is wired, but the admin strings are English only.
- **Two themes ship**, one of which (`dark-commerce`) has no tokens of its own beyond the
  colour-mode setting.

## Licence

Commercial. See [LICENSE.md](LICENSE.md) for terms, activation and redistribution.
