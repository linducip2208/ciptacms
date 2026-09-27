# Changelog

All notable changes to CiptaCMS.

**Everything in this file is derived from `git log`.** Entries are grouped under
the version label the commit subject carries; where a commit carries no label,
it sits under an explicit "unreleased" heading rather than being given an
invented version number. No feature is listed here that is not in the tree at
the referenced commit, and no date is shown that is not the commit's own author
date.

## Version numbering is inconsistent — read this first

| | |
|---|---|
| `config/lindu.php` → `version` | **`1.0.0`** |
| `config/lindu.php` → `core_version` | `1.0.0` |
| `.release-version` (added by `425080c`) | `1.0.0` |
| Commit subjects | go up to **`v1.8`** |

**The code does not carry the version labels its commit history does.** What
the application reports as its version is `1.0.0`; the last version-named
commit is `fa6726d` (v1.8), and **six** substantive commits landed after it
without a label. The labels below are reproduced from the commit subjects so
the history is legible — they are **not** what `config('lindu.version')`
returns. Fixing this is outstanding work.

There are no git tags in this repository, so there is nothing authoritative to
cut a release from.

---

## Unreleased — 2026-09-28

### Release packaging, empty state, plugin hook investigation — `425080c`

*This is the tip of the history as of this file being written. It is the
twenty-first phase and it is unlabelled.*

**Release packaging.** `tools/release.php` builds
`dist/CiptaCMS-vX.Y.Z.zip` from an **explicit include list** rather than a
directory scan, so a stray folder cannot reach a customer. It refuses to build
when anything on the include list carries a live credential or an activated
licence lock, then re-opens the finished archive to confirm no `.git`,
`node_modules`, `vendor`, real `.env`, logs, tests or build output slipped in.
The env templates are explicitly allowed through, because install needs them.
First package built: **594 files, 0.73 MB, verified clean.** `dist/` is now
git-ignored, and `.release-version` is the default build version.

**Admin UX.** `admin/partials/empty-row.blade.php`, a shared empty state for
list screens — a table with no rows tells an operator nothing, because they
cannot distinguish "nothing matches your filter" from "you have not created
anything yet". `AdminEmptyStateTest` records the screens still needing one into
`storage/admin-empty-state.txt` as an inventory to work through, not a gate that
always fails. Applied to pages, posts, comments, workflows, users, audits and
the updates screen in this commit.

**Plugin hooks: one real bug fixed, one gap still open.** The registration
closure spread the dispatcher's arguments into the plugin method. The dispatcher
passes the payload only, so this mostly worked by accident and broke on any
other arity; it now takes the last argument, which covers both shapes. Still
not proven end to end, and recorded as such: everything *around* the dispatch is
verified — the listener is registered, the delivery succeeds, a second listener
on the same event fires, and calling the plugin's handler directly records the
row — but **the registered listener does not run when the real dispatcher
fires.** The test now asserts the verified parts and names the gap, instead of
the previous placeholder that only checked registration. Do not advertise plugin
event hooks as working.

**One correction to `e39797b`:** its message said the dispatcher passes
`(name, payload)`. It passes the payload only. The spread bug was real; the
explanation of it was not.

**Tests: 579/580 passing, 2148 assertions, no failures.**

Two gaps that this commit did not address, found while verifying the above:
core never calls `applyFilter('seo.meta', …)` or `applyFilter('whatsapp.link',
…)` — the only filter name core passes is `theme.tokens`, which none of the three
bundled plugins declares — and `whatsapp-bridge`'s `contact.message` hook can
never fire because `SiteController::notifyNew()` dispatches
`cms.contact.message`. See [Open items](#open-items-at-the-tip-of-this-history).

---

## Unreleased — 2026-09-27

Five commits after `fa6726d` (v1.8), in order. These are the largest changes in
the project's history and none of them carries a version label. (A sixth
unlabelled commit, `425080c`, landed the next day — see
[Unreleased — 2026-09-28](#unreleased--2026-09-28).)

### Public site design system — `26ddd5c`

The public frontend claimed to use Tabler but did not: it loaded a bespoke
stylesheet with hand-rolled grid, spacing and form CSS, and Tabler was only
present in the admin. The menu was thirteen links in a row.

**Framework.** One stylesheet for the whole product — Tabler (foundation) +
Tailwind (utility layer) + the CMS design tokens — loaded by both layouts.
Previously 1.5 MB was spread over three files and `app.css` was loaded by
nobody, which left ~138 Tailwind classes in the admin doing nothing. One bundle
now, 821 KB, Tabler selectors verified present in the output. **No runtime CDN
anywhere**, including the login and docs pages.

**Design system.** 16 reusable components added under
`resources/views/components/site/` in this commit, replacing inline markup and
per-page CSS across 20 templates (17 are present today — `alert` arrived with
`f6bb6dd`). `ThemeController::tokenCss()` emits tokens from the
database and bridges them onto Tabler's own properties, so white-label
recolours the site without forking Tabler; colours and lengths are validated so
a stored value cannot smuggle in a CSS declaration.

**Navigation.** Sticky Tabler navbar with dropdowns for parents, a CTA for
`meta.cta`, active state with `aria-current`, and an offcanvas for small
screens with nested groups and a close control. Still driven entirely by the
Menu Engine — `FrontendMenuSeeder` now seeds the grouping (Company / Business /
Resources + Contact) as data, and the template names no page. The footer reads
the same tree and links Privacy and Terms only when an operator has published
them.

**Page builder.** `BlockLibrary` emitted `class="wrap"` and `class="grid"`,
which no longer existed, so builder pages had no width constraint. The `grid`
component opened a `div` and never closed it, shipping broken markup on any
page that used it — now a self-contained component. Repeated inline grid
declarations replaced with `.lindu-auto-grid`.

**Form builder.** `FormRenderer` had 12 hand-rolled inline styles on its
inputs; it now uses Tabler form classes like every other form on the site.

**Accessibility.** Added a `<header>` landmark (the site had `<nav>` but no
banner). Focus outlines are never removed, images carry `alt`, form controls
have labels, the honeypot is hidden from assistive technology, heading levels
do not skip.

**Tests.** `BlockLibraryIntegrityTest`, `ResponsiveAccessibilityTest`, plus
additions to `PublicSiteDesignTest`. 67 tests cover all 21 components; 44 cover
7 breakpoints against 13 pages. **531/532 pass, 1967 assertions.**

**Known gaps, recorded in the commit itself:** contrast ratios are not measured
automatically, breakpoints are asserted structurally rather than through a
headless browser, and six of the requested components are covered by a composite
component rather than a separate one.

### Remove the vertical business stubs — `f6bb6dd`

Placeholder folders shipped for eight industries the product does not serve:
`pos`, `erp`, `hotel`, `jodohku`, `crm`, `lms`, `ecommerce`, `marketplace`.
Each was ~800 bytes — a manifest, a README and two route files returning
`{"module":"x","ok":true}`. No implementation existed, and the folders
registered admin menu entries pointing at routes that did not exist. **Removed.**
The Module Engine is still proven by the eight real CMS modules.

**Module engine fail-open fixed.** `ModuleServiceProvider` skipped a module
only when a database row existed and was inactive:

```php
$rec = Module::where('slug', $slug)->first();
if ($rec && !$rec->is_active) continue;
```

With no row the condition was false, so every folder on disk registered its
routes — and on a fresh install the `modules` table is empty, which meant
modules an operator had never installed were reachable. It now requires the
module to be **both installed and active**, and a missing table means "load
nothing".

**Tailwind/Tabler collision fixed.** Tailwind v4 ships a `collapse` utility
whose name is identical to Tabler's `.collapse` component. The utility layer
beat Tabler, so every collapse element — admin sidebar, public navbar, FAQ and
careers accordions — was laid out with a real bounding box and **painted
nothing**. Found only by rendering the site in a browser; 2000 assertions had
passed. Restored with a documented override, and
`TailwindTablerCollisionTest` now fails if a Tabler component is shadowed again.

**Tooling.** `tools/screenshot.php` added: headless-Chrome renders of all 13
public pages at four widths, with a machine-readable overflow and console-error
report.

**Accessibility maths.** WCAG 2.1 contrast maths in `app/Core/Support`, with 13
tests confirming the shipped palette meets AA rather than assuming it.

**Refactors.** Navigation split into `nav-state` / `nav-desktop` / `nav-mobile`
so the tree is walked once and the active-state rule exists once; previously 155
lines rendered the same data twice. Alert component added; flash partial
rewritten to use it. README rewritten in English, Indonesian and Arabic with
measured counts and an explicit known-gaps section.

**Correction recorded in the commit.** An earlier commit message claimed
`package-lock.json` was not committed and that `npm ci` would be unpinned. That
was wrong — it had been tracked since `1304c62`. `npm ci` is safe.

**Tests: 558/559 passing, 2080 assertions.**

### Make the bundled plugins real — `c3a3e82`

Two of the three bundled plugins were manifest-only stubs, so a product selling
a plugin engine shipped empty examples next to it.

- **`webhook-logger`** — writes one activity-log entry per outgoing webhook
  delivery, calling out failures with the HTTP status and attempt count so a
  broken endpoint is visible in the admin rather than buried in `webhook_logs`.
- **`whatsapp-bridge`** — sends text through the WhatsApp Business Cloud API,
  configured from settings rather than `.env`. Refuses to send when
  unconfigured instead of failing silently, rejects numbers that cannot be
  international, surfaces API errors, and can notify a number when a contact
  form arrives. Exposes a `whatsapp.link` filter for the contact pages.

**Engine bugs this exposed, all fixed:**

- `PluginManager::flush()` cleared the loader and the active list but not the
  `booted` flag or the registered-hook map, so once `ensureBooted()` had run no
  plugin could register hooks again for the life of the process. A plugin
  activated at runtime never hooked up.
- Nothing called `ensureBooted()` during boot. Only `applyFilter()` and
  `dispatchHook()` did, and the framework's `event()` does not, so plugin event
  hooks never fired in an ordinary request at all. `LinduCoreProvider::boot()`
  now calls it.
- `WebhookLog` was missing `response_status`, `error` and `delivered_at` from
  its `$fillable`, so every one of those was silently dropped on write. The
  delivery log showed neither the HTTP status nor the error it was recording.
- The plugin interface contract is `filters()` returning a **list of names**,
  not a `name => callable` map: the core does `in_array($name, $filters)` and
  then calls `filter()` itself. A map silently never matches.

**Known gap, recorded in the commit:** with a listener registered, firing
`webhook.delivered` still does not reach the plugin under the test harness. The
handler is therefore tested by invoking it directly, and
`test_hook_listener_is_registered` pins the registration half.

**Tests: 577/578 passing, 2143 assertions.**

### Fix the plugin hook payload — `e39797b` — 2026-09-27

The registration closure spread the dispatcher's arguments into the plugin
method, so every hook threw a `TypeError` — which the surrounding `catch` then
swallowed. The net effect was that **no plugin hook ran, in any request**, while
the plugin manager still reported them registered. The closure now takes the
last argument as the payload, which covers both the payload-only and the
`(name, payload)` shapes.

> **Corrected by `425080c`.** This commit's own message explained the cause as
> *"the dispatcher calls a listener with (event name, payload)"*. That is
> wrong — **the dispatcher passes the payload only.** The spread bug was real;
> the explanation of it was not. It is repeated here because it is what that
> commit recorded, and it was corrected a commit later.

The root-cause hunt is recorded in the commit: `Event::hasListeners()` returned
true, a direct invocation of the registered closure produced the expected row,
but `event()` produced none — while a plain listener registered in the same
test fired correctly, proving the dispatcher itself was healthy. The residual
difference between `event()` and a manual call was **still open** after this
commit.

**Tests: 579/580 passing, 2146 assertions, no failures.**

### Fix the settings cache — `9231bd8`

`SettingService::all()` cached a `Collection`. A `Collection` written to the
database or file cache store comes back as `__PHP_Incomplete_Class`, so the
first method call on it fataled. Because `setting()` reads that value from
every view and controller, one cache entry took the whole site down — and
clearing the cache only delayed it, since the next write poisoned it again.

- `SettingService::all()` stores a plain array and re-wraps it on read.
- `SafeCache` wraps every cache read, discards an entry that fails to decode
  and rebuilds it. The validity check sits inside the `try`: fetching an
  incomplete object does not throw, calling a method on it does.
- `SafeCache::normalise()` flattens anything array-like before storing.
- `MenuService` uses `SafeCache` too; it already cached arrays but was equally
  exposed to a poisoned entry.
- `SafeCache::isReadable()` lets the health screen report a bad entry.

`CacheRoundTripTest` proves arrays survive the store and Collections do not.
`CorruptCacheTest` proves settings and the admin recover, and that a page still
renders with a poisoned settings cache.

---

## v1.8 — 2026-09-27 · `fa6726d`

*Plugin/theme engines, quota enforcement, comments, relations, auth hardening.*

- **Plugin engine:** `PluginInterface` + `PluginLoader` + `PluginException`.
  `seo-booster` moved to `src/Plugins/SeoBooster/Plugin.php`.
- **Theme engine:** `DesignTokens` + a reworked `ThemeManager`; `tabler.css` /
  `tabler.js` built assets.
- **Quota:** `Quota` service, with `QuotaExceeded` (rendered **HTTP 402**) and
  `FeatureUnavailable` (**HTTP 403**), plus the `lindu:quota-recount` command,
  scheduled daily.
- **Comments:** `CommentService`, public post/report endpoints
  (`throttle:comment`) wired to the existing moderation screens.
- **Relations:** `RelationRegistry` and content-type relation CRUD, migration
  `add_content_type_relations`.
- **Cache:** `SafeCache` guards corrupt payloads across the
  FeatureFlag / Menu / Setting / Media services.
- **Auth:** `throttle:login`, `throttle:2fa-challenge`, `throttle:register`,
  `throttle:password-reset`; hardened `AuthController` and `AuthApiController`.
- **Assets:** `qr.js` and a `swagger.js` fallback renderer added to the Vite
  inputs; `welcome.blade.php` removed.

---

## Unversioned, major rewrite — 2026-09-27 · `1304c62`

*commercial-grade Lindu CMS — company profile, builders, engines, security.*

The commit that turned a working skeleton into a product. Every claim below is
backed by a test at that commit.

**Company profile (new).** 12 `cp_*` tables, 12 models under `App\Models\Cp`, a
whitelisted `RESOURCES` map so the `{resource}` route param is never used to
build a class name. A full public site — `/`, `/about`, `/services`,
`/products`, `/portfolio`, `/team`, `/testimonials`, `/clients`, `/faq`,
`/gallery`, `/careers` (with CV upload), `/contact`, `/blog`, `/p/{slug}` — all
data-driven, navigation from the Menu Engine. `CompanyProfileSeeder` ships
editable demo content.

**Page builder, rebuilt from a stub.** Real HTML5 drag/drop; inspector driven by
`BlockLibrary::catalog()` instead of four generic fields; preview of all 21
components; undo/redo; copy/paste/duplicate; save-as-block; templates.
`BlockLibrary::render()` now emits real markup — it had been a stub.
`reusable_blocks` and `page_templates` gained the columns the builder writes
(`data` / `structure` / `is_active` / `deleted_at`), backfilled from the old
ones.

**Form builder.** Real drag/drop builder, palette from `FormField::TYPES`,
per-property inspector, live preview mirroring `FormRenderer` plus an "as
saved" iframe. Layered anti-spam: honeypot, minimum fill time, field
validation, blocked words, per-IP rate limit. `forms` gained `submit_label` /
`success_message` / `status` / `deleted_at` — `saveForm()` had been filling four
columns that did not exist, so the public endpoint 404'd **every** form.

**Admin surface.** Sidebar rendered from `menu_items` via a view composer rather
than hard-coded. 386 routes; every admin page covered by a render test.
Settings split into real tabs, plus appearance, data builder, white label,
licence, SaaS, notifications, developer and update-centre sections.

**Engines.** Workflow: triggers, conditions, actions plus a run log, with model
writes restricted to `WHITELISTED_MODELS` so a workflow definition cannot name a
class. Licence v3 marketplace kit installed; `/__pair` registered so the wizard
is reachable; `RequirePair` bypass hardened; `provider()` wired into
`status()`; licence key encrypted at rest. Backup/restore with real dumps and a
restore that requires explicit confirmation. Update engine: backup, migrate,
clear caches, record version — never executing remote code, and reporting
"nothing checked" without an endpoint instead of claiming to be up to date.

**Security fixes.**

| | |
|---|---|
| `ModuleController` | called an arbitrary method name from the URL → allowlist |
| `TenantService::resolve()` | applied `status = 'active'` outside the `orWhere`, so a suspended tenant still resolved on an exact domain match |
| Inbound webhooks | verified no signature → HMAC required, fail-closed |
| Media | the allowlist existed in config but was never enforced |
| `license.dev_bypass` | defaulted to true and was not environment-gated, so a production install could silently lose pairing protection |
| `license_key` | was written in plaintext despite its docblock |

**Tooling and tests.** `tools/blade-lint.php`, `tools/route-lint.php`,
`tools/test.php`; `composer check` runs both linters and the suite.
`RepositoryAuditTest`, `TemplateIntegrityTest` and `SecurityHardeningTest` lock
in the invariants above. **293/294 tests, 1209 assertions**, one skip (file
mode `0600` is a no-op on Windows).

**Documentation.** Nine new docs; existing docs corrected against source.
Removed unsupportable claims — **PWA, working payment integration, plugin hook
execution, theme layout swap, enforced media allowlist** — and documented
current behaviour with explicit "known gaps" sections.

`composer.json`: `laravel/laravel` → `linducms/cms`.

---

## v1.7 — 2026-09-26 · `edf0ab5`

*paket deploy VPS: env prod, nginx/apache, supervisor, deploy.sh, CI Actions*

Added the production deployment package: `.env.production.example`, Nginx and
Apache virtual-host configs, a Supervisor config for the queue workers,
`deploy/deploy.sh`, and `.github/workflows/deploy.yml`.

---

## v1.6 — 2026-09-26 · `9b304fe`

*README 3 bahasa lengkap + final polish*

README written in English, Indonesian and Arabic, plus a final polish pass.

---

## v1.5 — 2026-09-26 · `f139e43`

*lindu:doctor + DoctorTest + TROUBLESHOOTING 503 lokal (Apache Laragon)*

Added the `lindu:doctor` health command, its test, and a
[TROUBLESHOOTING.md](TROUBLESHOOTING.md) entry for the local 503 case.

---

## v1.4 — 2026-09-26 · `42e038a`

*Tabler semua 38 views + fix Blade foreach + SeoMeta table + AdminRender 34 pages + APP_NAME*

Tabler applied across 38 views, a fixed Blade `foreach`, the `SeoMeta` table,
34 admin pages covered by render tests, and `APP_NAME` handling.

---

## v1.3 — 2026-09-26 · `7f5326e`

*503 fixed + QR server-side + queue monitor + search index + S3 presign + Tabler + E2E 22 tests*

The 503 fixed, server-side QR generation, a queue monitor, the search index
command, S3 presigned uploads, Tabler, and 22 end-to-end tests.

---

## v1.2 — 2026-09-26 · `af6b4c1`

*Tabler admin + Socialite OAuth + Search drivers + CDN/S3 + Swagger UI + 21 tests*

Tabler for the admin, Socialite OAuth, the search drivers, CDN/S3 delivery,
Swagger UI and 21 tests.

---

## v1.1 — 2026-09-26 · `a421f69`

*Lindu CMS v1.1: media queue webp/avif, 2FA TOTP, API v2 OpenAPI, drag-drop page builder, PWA, payments, README EN/AR/ID*

The first labelled release. Media queue for WebP/AVIF, TOTP two-factor, API v2
with OpenAPI, the drag-and-drop page builder, payments, and the three READMEs.

**Corrections to this entry, from later commits:**

- **PWA.** `public/manifest.webmanifest` and `public/sw.js` exist, and the
  manifest **is** linked from both the public and admin layouts. But the
  service worker is never registered on any rendered page: the only
  `navigator.serviceWorker.register('/sw.js')` in the tree is in
  `resources/views/frontend/home.blade.php`, and the whole
  `resources/views/frontend/` directory is orphaned — `SiteController` renders
  `site.*` views, and nothing references `frontend.*`. `public/sw.js` is itself
  a two-line stub (`install` → `skipWaiting`, an empty `fetch` handler), so it
  would cache nothing even if it were registered. The PWA claim was removed
  from the README in `1304c62` and has not been reinstated. Treat installable /
  offline support as not implemented.
- **Payments.** This is an interface plus five adapters and a resolver, with no
  checkout, no callback route, no reconciliation and no signature
  verification on the Xendit callback. The claim was removed in `1304c62`.
- **Drag-drop page builder.** Was real then, but the builder itself was later
  rebuilt from a stub in `1304c62` and the grid/`wrap` fixes landed in
  `26ddd5c`.

---

## Open items at the tip of this history

Not a to-do list of embarrassing bugs — several are deliberate. These are the
ones that a proposal or an upgrade conversation will run into.

1. **Version numbering is broken.** `config('lindu.version')` is `1.0.0`; the
   history goes to `v1.8` plus **six** unlabelled commits (`1304c62`, `9231bd8`,
   `26ddd5c`, `f6bb6dd`, `c3a3e82`, `e39797b`, `425080c`). No git tags exist.
   `.release-version` was added by `425080c` and also says `1.0.0`.
2. **Two of the three bundled plugins are only partially wired.**
   `webhook-logger` and `whatsapp-bridge` have real classes, and the filter
   chain and direct hook dispatch work as mechanisms — but plugin **event
   hooks** are not proven end to end and do not fire through the framework
   dispatcher under the test harness. Two further gaps sit on top of that, both
   still open: core never calls `applyFilter('seo.meta', …)` or
   `applyFilter('whatsapp.link', …)` (the only filter name core passes is
   `theme.tokens`, which none of the three declares), and
   `whatsapp-bridge`'s `contact.message` hook can never fire because
   `SiteController::notifyNew()` dispatches `cms.contact.message`. Do not
   advertise any of the three as working features.
3. **The admin interface is not translated.** `lang/en.json`, `lang/id.json` and
   `app/Http/Middleware/SetLocale.php` exist; nothing calls `__()` and the
   middleware is not registered. Only the public site structure is set up for
   translation.
4. **There is no visual-regression baseline.** `tools/screenshot.php` renders
   and reports overflow and console errors, and the responsive tests assert
   markup, but nothing compares renders to a golden set.
5. **`dark-commerce` has no tokens of its own beyond the colour-mode setting.**
   Its manifest is equivalent to `default`'s and its `layout.blade.php` sits at
   the theme root, not in `views/`, so it is never loaded.
6. **Several docs are out of date against the code** — chiefly
   [LICENSE.md](LICENSE.md), [DEPLOYMENT.md](DEPLOYMENT.md),
   [INSTALL.md](INSTALL.md) and [TROUBLESHOOTING.md](TROUBLESHOOTING.md) on the
   `/__pair` wizard (now routed), [THEMES.md](THEMES.md) on theme deactivation
   and view overrides, [MODULES.md](MODULES.md) on the removed stub modules and
   the fail-open guard, [PLUGINS.md](PLUGINS.md) on the bundled plugins, and
   [MENU_ENGINE.md](MENU_ENGINE.md) on `MenuController@create`/`@edit`. When the
   docs and the code disagree, the code is right — read the source.
7. **The suite is not a release gate.** Treat `php artisan test` as a
   diagnostic and read the failures rather than assuming a red build is
   pre-existing. See [DEVELOPMENT.md](DEVELOPMENT.md).

---

## See also

[UPGRADE.md](UPGRADE.md) — moving an install between versions
[ARCHITECTURE.md](ARCHITECTURE.md) · [DEVELOPER.md](DEVELOPER.md) ·
[UPDATES.md](UPDATES.md) · [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
