# Administrator guide

For whoever runs the site day to day: accounts and access, navigation, branding,
media, forms, SEO, automation, extensions, backups and system health.

You do not need to be a developer for most of this. Where a task genuinely
needs the server console, the shell command is given explicitly.

If you own the site but do not administer it, read
[USER_GUIDE.md](USER_GUIDE.md) instead. If you are going to change code, read
[DEVELOPER.md](DEVELOPER.md).

---

## Contents

1. [The admin area](#1-the-admin-area)
2. [Users, roles and permissions](#2-users-roles-and-permissions)
3. [Menus](#3-menus)
4. [Appearance and branding](#4-appearance-and-branding)
5. [Media](#5-media)
6. [Forms and submissions](#6-forms-and-submissions)
7. [SEO](#7-seo)
8. [Workflows](#8-workflows)
9. [Modules, plugins and themes](#9-modules-plugins-and-themes)
10. [Content types and records (data builder)](#10-content-types-and-records-data-builder)
11. [Backups and restore](#11-backups-and-restore)
12. [System health and logs](#12-system-health-and-logs)
13. [The install lock](#13-the-install-lock)
14. [The update centre](#14-the-update-centre)
15. [Plans, quotas and tenants](#15-plans-quotas-and-tenants)
16. [Security settings worth checking](#16-security-settings-worth-checking)
17. [Known limitations](#17-known-limitations)

---

## 1. The admin area

Sign in at `/login`. Everything under `/admin` requires a session and a
permission. The sidebar is **not hard-coded** — it is rendered from rows in the
`menu_items` table by a view composer, which is why an empty sidebar is a
seeding problem and not a bug. See [MENU_ENGINE.md](MENU_ENGINE.md).

Top-level groups the seeder creates:

| Group | What is in it |
|---|---|
| **Content** | Pages, Posts, Categories, Tags, Media Library, Comments, Revisions, Trash |
| **Appearance** | Themes, Theme Customizer, Menus, Widgets, Header, Footer, Homepage, Templates, Global Blocks, Custom CSS/JS |
| **Page Builder** | Pages Builder, Sections, Components, Blocks, Templates, Saved Blocks |
| **Forms** | Forms, Form Fields, Submissions, Email Notifications, Spam / Protection |
| **Media** | Library, Folders, Images, Documents, Videos, Storage |
| **SEO** | Global/Page/Post SEO, Sitemap, Robots.txt, Redirects, Schema, Open Graph |
| **Users** | Users, Roles, Permissions, Groups, Login History, Sessions, Security |
| **Modules / Plugins** | Installed, Active, Inactive, Updates, Settings |
| **Data** | Content Types, Fields, Relations, Records, Import, Export |
| **Workflow** | Workflows, Triggers, Conditions, Actions, Tasks, Execution Logs |
| **Notifications** | Notifications, Email, Webhook, Push, Templates |
| **Comments** | All Comments, Pending, Approved, Spam, Trash, Word Filter, Reports |
| **Settings** | 17 tabs: General, Branding, Site Identity, Localization, Email, Storage, Media, SEO, Security, API, Webhooks, Cache, Queue, Notifications, Social, Maintenance, Advanced |
| **System** | System Health, Logs, Audit Logs, Activity Logs, Jobs, Scheduled Tasks, Cache, Storage, Database, Backup, Restore, Maintenance |
| **Developer** | API, API Keys, Webhooks, Events, Hooks, Cache, Documentation |

> **The admin interface is English only.** `lang/en.json` and `lang/id.json`
> exist and a locale middleware exists, but no admin view calls `__()` and the
> middleware is not registered. Only the structure of the public site is set up
> for translation. See [Known limitations](#17-known-limitations).

---

## 2. Users, roles and permissions

**Users** (`/admin/users`) — list, search, create, edit, enable/disable, delete.
Roles are assigned on the user's own form as a set of checkboxes. The user
cannot delete themselves; the form refuses it. Passwords are minimum 8
characters and are bcrypt-hashed.

**Roles** (`/admin/roles`) — create (name + slug), rename, and assign
permissions as a set of checkboxes. Deleting a role flagged `is_system` is
refused. Four are seeded:

| Role | Level | `is_system` | Permissions after seeding |
|---|---|---|---|
| `super-admin` | 100 | yes | **all 127** |
| `admin` | 90 | yes | **all 127** |
| `manager` | 50 | no | **none** — assign them by hand |
| `member` | 10 | no | **none** — assign them by hand |

The seeder syncs every permission to `super-admin` and `admin` and none to
`manager` or `member`, so a new `manager` account can sign in and see an empty
sidebar until someone gives it permissions.

**Permissions** (`/admin/permissions`) — the flat list, searchable and
filterable by module, with an `action` (one of view, create, read, update,
delete, publish, approve, export, import, manage, configure — inferred from the
last segment of the slug when you leave it blank), a module and a group. A
permission assigned to any role cannot be deleted until you detach it. **Groups**
(`/admin/permissions/groups`) — the `permission_groups` records the list is
displayed under; a group with permissions in it cannot be deleted.

127 permissions are seeded across five groups: `cms`, `commerce`, `apps`,
`system`, `saas`. Note that six of the `apps` entries (`pos`, `hotel`, `lms`,
`crm`, `marketplace`, `jodohku`) are permission rows for module folders that no
longer exist on disk — harmless leftovers, but they will show up in the module
filter with nothing behind them. Modules contribute their own through
`ModuleManager::registerPermissions()`, which splits a manifest entry like
`company.manage` into action `manage` and module `company`.

### The gap that bites when you create a `manager` or `member`

`User::hasPermission($slug)` asks whether any of the user's roles has a
`permissions` row **with that exact slug**. A slug with no row in the
`permissions` table can never be granted, so a `manager` or `member` will fail
that check no matter what you tick.

The seeder creates permissions for 21 modules, but `MenuSeeder` writes menu
entries guarded by permissions for several that are **not** in that list:
`categories.*`, `tags.*`, `comments.*`, `themes.*`, `widgets.*`, `plugins.*`,
`content-types.*`, `content-records.*`, `workflows.*`, `tasks.*`,
`notifications.*`, `webhooks.*` and `system.*`. Because the sidebar is
permission-filtered, those groups **will not appear at all** for a
`manager` or `member` until you create the missing permission rows on
`/admin/permissions` (Create, then set the slug to exactly what the menu item
uses) and assign them to a role.

`super-admin` and `admin` are unaffected — `hasPermission()` short-circuits on
the role, as does the `Gate::before()` below.

### The one thing to understand about permissions

`AppServiceProvider` installs a `Gate::before()` that grants **every** ability
to any user holding the `super-admin` or `admin` role:

```php
Gate::before(function ($user, $ability) {
    if ($user && $user->hasRole(['super-admin', 'admin'])) { return true; }
});
```

So a permission check is **not** on its own a separation between those two
roles. If you sell an "administrator" account that should be less than
all-powerful, that global bypass is what has to change — it is a code change,
not a setting. Everything else (`manager`, `member`, custom roles) is enforced
normally, both by the `permission:` route middleware and by the
server-side menu filter.

Two supporting points:

- Hiding a menu item is **not** the same as authorising it. `MenuService`
  filters the sidebar by `permission` and `roles`, but the check that matters is
  the one on the route.
- A module's `permissions` entries only take effect once the module is
  installed and active; `ModuleManager::registerPermissions()` runs on
  install/activate.

---

## 3. Menus

**Appearance → Menus** (`/admin/menus?location=admin` or `?location=primary`).
One screen, one table, one form.

`MenuController::LOCATIONS` accepts exactly three: `admin`, `primary` and
`footer`. **Only `admin` and `primary` are rendered.** The sidebar reads
`admin`; the public navbar *and the footer* both read `primary`. Anything you
put in the `footer` location is stored and shown in the admin list, and appears
nowhere on the site.

The seeded `primary` menu groups the site as **Company** (About, Team, Clients,
Testimonials), **Business** (Services, Products, Portfolio, Careers) and
**Resources** (Blog, FAQ, Gallery), with Contact as a highlighted button —
grouping lives in the data (`parent_id`), not in the template. The footer
renders the first three groups that have children, plus a Contact column built
from the company-profile contact settings.

The index screen has a small inline **Add** form (title, url, icon, permission)
and a Delete button per row. The full form — `/admin/menus/create` and
`/admin/menus/{menu}/edit` — adds location, parent, route, badge, badge colour,
target, sort order and visible. **The table has no Edit link**, so reach the
full form by URL, or create through the inline form and refine afterwards.

### Things that will trip you up

- **There is no drag-and-drop reordering in the screen.** `POST
  admin.menus.reorder` exists and takes an `order` array plus an optional
  `parent_id`, but nothing in the current view calls it. Use the sort-order and
  parent fields.
- **Delete is a hard delete**, not a move to a bin. It is refused outright if
  the item has children — move or delete them first.
- **A menu item cannot become its own ancestor.** `validated()` walks up to ten
  levels of descendants and rejects a `parent_id` that would create a cycle.
- `roles` and `meta` are in the model's `$fillable` but are **not** written by
  the form (the form decodes `meta` and then unsets it). They are reachable
  only through the API, a module manifest or the database.
- **Every menu write flushes the entire application cache.**
  `MenuService::forget()` is `Cache::flush()`. Expect anything else cached to
  disappear. The tree is otherwise cached for 120 seconds per
  `(location, user, tenant)`, through `SafeCache::remember()`.
- A menu item needs a `url` like `/services`, or a named `route`. `url` wins
  when both are set.
- Deactivating or uninstalling a module **deletes** every `menu_items` row with
  that module slug. Reactivating restores them.
- The public navbar renders parent items as dropdowns and the mobile offcanvas
  with nested groups, and an item flagged `meta.cta` becomes the button. **If
  there are no visible `primary` rows at all there is no fallback list** — the
  header collapses to the brand and, if there is one, the CTA. An empty navbar
  means the `primary` menu has no visible rows.

---

## 4. Appearance and branding

There are three separate systems. They do not know about each other, and it is
worth knowing which one you are in.

### a. Theme customizer — `Appearance → Theme Customizer`

`/admin/themes/customize`. Values are stored in `appearance_options` under the
`customizer` group and emitted as CSS custom properties inlined into
`<head>`.

| Group | Keys |
|---|---|
| `colors` | `primary`, `secondary`, `accent`, `body_bg`, `text`, `muted`, `border` |
| `typography` | `font_family`, `base_size`, `heading_weight` |
| `layout` | `container`, `radius`, `section_padding`, `sticky_header` |

This is the fastest way to recolour the public site. `sticky_header` is stored
but not read by the layout.

### b. White label — `/admin/white-label/{section}`

Six sections: `branding`, `login`, `email`, `code`, `errors`, `advanced`.

**Read this before promising a customer a full rebrand.** These keys are stored
and then never read by anything:

| Key | Why it matters |
|---|---|
| `branding.login_logo`, `branding.login_background` | the login page renders a hard-coded letter badge |
| `branding.admin_title` | the admin `<title>` appends `general.site_name` instead |
| `branding.og_image`, `general.tagline` | stored; the site layout does not reference them |
| `branding.email_from_*`, `email.from_*` | outgoing mail is configured through `config/mail.php` and `MAIL_*`, not here |
| `branding.error_404_*`, `branding.error_500_*` | there is no `resources/views/errors/` directory |
| `branding.og_image`, `general.tagline` | stored; the site layout does not reference them |
`branding.footer_branding` and `branding.hide_powered_by` **are** read now —
the footer renders the former and drops it when the latter is set — but the
text has to be typed into `footer_branding` before it appears, and there is
nothing else in the UI that does so.

What **is** consumed: `branding.logo`, `branding.favicon`,
`general.site_name`, `general.footer`, `branding.primary_color`,
`branding.secondary_color`, `branding.radius`.

**`code` is raw.** `branding.custom_css`, `custom_head`, `custom_js` and
`custom_footer` are output unfiltered with `{!! !!}`. That is the point of the
feature, and it also means *edit white label* is a root-equivalent privilege:
anyone who can reach `admin.whitelabel.save` can run JavaScript on every public
page. Gate it separately or do not delegate it.

**Domains** — `/admin/white-label/domains` records customer domains and marks
one primary. There is **no DNS ownership check**: adding a row records an
assertion, it does not prove control. And `ResolveTenant` resolves tenants
from `tenants.domain` / `tenants.subdomain`, **not** from
`white_label_domains`, so a white-label row alone does not route a request.

### c. Header / footer / homepage / custom code

`/admin/appearance/header`, `/footer`, `/homepage`, `/custom-code`, and
`/admin/widgets` for sidebar widgets. The homepage screen picks which page is
the homepage (`is_homepage`); when one is set, `/` renders it through the page
builder instead of the default home view.

Check whether the public layout actually renders a given widget `sidebar`
before promising a customer a widget layout — widgets are stored and reordered
from the admin regardless.

---

## 5. Media

**Media → Library** (`/admin/media`) — upload (a file picker that accepts
several files at once), search, sort, filter by type, 24 per page. **Folders**
(`/admin/media/folders`) — nested folders with counts. **Storage**
(`/admin/media/storage`) — a read-only report of each disk, its root, file count
and bytes, plus the upload limit, thumbnail sizes and the auto-optimise switch.

Per file: title, description, caption, **alt text**, and which folder it is in.
There is also reprocess, trash and restore.

Thumbnails are generated at 150×150, 300×300 and 800×600, and WebP/AVIF
variants are produced on the queue. Check **System → Jobs** if conversions
seem stuck.

### Two things to know

- **Uploads are size-limited, not type-limited.** The default is
  `setting('media.max_upload_mb', config('lindu.media.max_upload_mb', 10))`,
  enforced twice (controller validation and `MediaService::store()`). But
  `config('lindu.media.allowed_mimes')` is a 15-extension list that **nothing
  enforces** — the only reader is the Storage screen, which displays it.
  `MediaService` records the MIME type and stores whatever it is given. Treat
  that config key as documentation of intent, not a control. If you serve
  `public/storage/media` from the same origin, add a real `mimes:` rule to the
  upload validation. This is documented in [SECURITY.md](SECURITY.md).
- **If nothing you upload appears on the site, `php artisan storage:link` has
  not been run.** The link maps `public/storage` → `storage/app/public`.
  `InstallerController` does not create it. On a server, also check ownership:
  `chown -R www-data:www-data storage bootstrap/cache && chmod -R 775 …`.

Form file uploads (`form-uploads/{form-slug}/…`) and career CVs
(`careers/…`) go to the same public disk, so the same link covers them.

---

## 6. Forms and submissions

**Forms → Forms** (`/admin/cms/forms`) — create and delete forms; each gets a
`slug` (`Str::slug(title)` plus a 4-character suffix) which is the public
identifier. A form with fields but no slug is unusable.

**The builder** (`/admin/cms/forms/{form}`) — real HTML5 drag-and-drop from
the palette onto the canvas and between existing fields, a per-property
inspector (label, name, placeholder, help text, options, required and unique
flags). `POST admin.cms.forms.fields.reorder` rewrites `sort_order` from the
submitted order. A live preview mirrors the renderer, and an "as saved" iframe
renders through the same code path the public endpoint uses, so the preview
cannot drift from reality.

**Form Fields** (`/admin/cms/form-fields`) — every field of every form in one
table, for bulk review.

**Submissions** (`/admin/cms/submissions`) — list, read one, delete, and export
to CSV (`/admin/cms/submissions-export`). `data` is a JSON object keyed by field
`name`; `multiselect`/`checkbox` are arrays, `file`/`image` are stored paths.

**Spam / Protection** (`/admin/cms/form-spam`) — the `form_spam_settings`
singleton: honeypot on/off, minimum fill seconds, per-IP rate limit,
blocked words.

Enforcement, in the order `FormRenderer::submit()` applies it: honeypot →
minimum fill time → per-IP rate limit (checked *and* incremented, so a failed
validation also counts) → per-field validation → blocked words. Plus a routing
layer `throttle:form-submit` at 20 requests/minute per IP.

**What is stored but not enforced:** `block_disposable_email` and `captcha`
have columns and no reader. `is_unique` on a field is stored and never checked.
`validation` and `conditional` are inert. A `password` field stores its value
in cleartext in the submission. A `repeater` field renders as a single text
input. Do not advertise any of these as working.

**Email Notifications** (`/admin/cms/form-notifications`) — per-form templates.

---

## 7. SEO

**Global SEO** (`/admin/cms/seo`) — site-wide defaults: title template and
separator, OpenGraph and Twitter card defaults, and the share image.

**Per row** — every page, post and company-profile record has a
`SeoMeta` row (a `morphOne` from the content model): meta title, meta
description, canonical, robots, OpenGraph title/description/image, Twitter card.
Edit from `GET admin/cms/seo/{type}/{id}/edit`. On pages and posts the SEO
block is on the edit form itself and is only written if you actually fill
something in.

**Sitemap** (`/admin/cms/seo/sitemap`) — the generated `sitemap.xml`, which is
also served live at `/sitemap.xml`.

**Robots.txt** (`/admin/cms/seo/robots`) — also served at `/robots.txt`.

**Redirects** (`/admin/cms/seo/redirects`) — managed from/to/status rows.

**Schema** (`/admin/cms/seo/schema`) — the JSON-LD the site emits (organisation,
breadcrumb).

---

## 8. Workflows

**Workflow → Workflows** (`/admin/cms/workflows`). A workflow is a row, not
code: a trigger, a list of conditions, a list of actions, and an on/off switch.

- **Triggers** (`/admin/cms/workflows/triggers`) — 17 events: page/post
  created, updated, deleted, published; comment created; form submitted;
  contact message; job application; user registered; record created/updated/
  deleted; plus `schedule` and `webhook`.
- **Conditions** (`/admin/cms/workflows/conditions`) — 13 operators. All must
  pass; the first failure stops the run and records it as `skipped`.
- **Actions** (`/admin/cms/workflows/actions`) — 9 types: `send_email`,
  `send_notification` (which just sends mail), `send_webhook` / `send_http`
  (aliases), `create_record` / `update_record` / `delete_record`,
  `create_task`, `update_setting`.
- **Execution Logs** (`/admin/cms/workflows/runs`) — every run with its
  per-action log, and a **retry** button that replays the stored payload.

### Before you build one

- **Actions run synchronously, in the request that fired them.** A
  `send_http` with a 10-second timeout holds the visitor's request for ten
  seconds. There is no queue and no delay.
- **The `schedule` trigger has no runner.** Nothing dispatches it. You have to
  fire the event yourself from Developer → Events.
- **An incoming webhook fires the wrong event name.**
  `POST /api/v1/webhooks/in/{key}` triggers `webhook.received`, but
  `TRIGGERS` contains `webhook` — so a workflow on `webhook` never runs from
  an incoming webhook.
- **An HTTP action's status code is not recorded.** The run log shows `→ ok`
  whether the endpoint answered 200 or 500. Check the target service.
- **A mistyped operator silently blocks the workflow** (recorded as `skipped`,
  not `failed`) and an unrecognised action type does the same. Read the run log.
- **Write actions can only touch `ContentRecord` and `ContentType`**
  (`WorkflowEngine::WHITELISTED_MODELS`). Anything else returns
  `error: model not permitted`. Do not widen that constant casually: anyone who
  can edit a workflow can then write to whatever you add.
- **`update_setting` is an unfiltered settings write**, restricted only to a
  `group.key` pattern. Treat *edit workflows* and *edit settings* as the same
  privilege.

There is a manual trigger at **Developer → Events** for testing any event
with a JSON payload you type in.

---

## 9. Modules, plugins and themes

### Modules (`/admin/modules`)

Eight ship: `api`, `blog`, `company-profile`, `forms`, `media`, `pages`,
`seo`, `tenants`. Actions are `install`, `activate`, `deactivate`, `uninstall`
and nothing else — the controller allowlists them, so a URL cannot name an
arbitrary method.

`install` and `activate` check `dependencies`, run the module's migrations,
register its `menus` as `menu_items` rows and its `permissions` as permission
rows. `deactivate` and `uninstall` **delete every menu row the module
contributed**. `uninstall` does not drop tables.

**A module is only reachable if it is installed *and* active.** A folder on
disk with no registry row registers nothing.

**There is no `update()` action for a module.** Upgrading one is a `git pull`
plus `php artisan migrate`.

`company-profile` is the module that matters for a company site, and it is a
manifest plus core code: its 12 models are in `app/Models/Cp/`, its controller
is in `app/Http/Controllers/Admin/`, and its routes come from
`routes/company.php`. Deactivating it hides the navigation; it does not remove
the tables, the models, the controller or the routes.

**Managing company content** (`/admin/company/…`, the *Company Profile* menu
group): a dashboard, two settings forms (About, Contact), a Messages list with
a status workflow (new → read → replied → archived), an Applications list
(received → reviewing → shortlisted → rejected → hired), and generic CRUD over
eight whitelisted resources — services, products, portfolio, team,
testimonials, clients, faqs, careers — plus a separate gallery-album screen.

Each resource list offers search, a Published/Draft filter, a **Trash** link,
**+ Add**, and per row: Edit, a publish/draft toggle, Copy and Delete. Deleted
rows are soft-deleted and can be restored from `…/trash`.

Two things to know:

- **There is no way to set a manual sort order from the admin.** The
  `POST …/data/{resource}/reorder` route exists and rewrites `sort_order`, but
  nothing in the current list view calls it. The public site orders by
  `sort_order` and then by the title/name column, so in practice items appear
  **alphabetically** unless an operator sets `sort_order` by hand in the
  database. The list does accept `?sort=` and `?dir=asc|desc` in the URL, but
  the column headings are not links.
- **Arrays and paired fields use textarea conventions.** `features`,
  `technology`, `images`, `gallery` and `requirements` are one value per line;
  the team member's `social` field is one `network|url` pair per line
  (`linkedin|https://…`). A line without a `|` is dropped.

### Plugins (`/admin/plugins`)

Three ship: `seo-booster`, `webhook-logger`, `whatsapp-bridge`. Same
discovery. The lifecycle verbs `install`, `activate`, `deactivate`,
`uninstall` and `update` all work; anything else in the URL is treated as a
plugin-declared action key and resolved through that plugin's **own manifest
`actions` map** — and none of the three bundled manifests declares one, so a
custom action on these three is a 404.

**Be careful what you tell a customer about these.** All three have real
`Plugin` classes implementing `PluginInterface` and all three are loaded when
active. What they do, and how far each is actually wired:

| Plugin | What it does | Status |
|---|---|---|
| `seo-booster` | subscribes to the `seo.meta` filter and appends a `<meta name="novel">` tag and a `noindex,nofollow` escape hatch to the rendered head | **Filter chain is live but nothing in core calls `applyFilter('seo.meta', …)`**, so it does not run on a real page. Settings go in `seo_booster.novel_meta`. |
| `webhook-logger` | writes one activity-log row per outbound webhook delivery, with the HTTP status and attempt count on failure | The event names match what `WebhookDispatcher` fires (`webhook.delivered` / `webhook.failed`), so this is the one closest to working — but it depends on the `event()` → plugin hook path below. |
| `whatsapp-bridge` | sends text through the WhatsApp Business Cloud API, configured from settings (`whatsapp.phone_number_id`, `whatsapp.access_token`, `whatsapp.api_version`); also offers a `whatsapp.link` filter | **Its `contact.message` hook can never fire** — the contact form dispatches `cms.contact.message`, not `contact.message`. And nothing in core calls `applyFilter('whatsapp.link', …)`, so no `wa.me` link is produced. The `send()` method itself is real and refuses to run unconfigured. |

**And plugin event hooks are not proven end to end in any case.** Everything
around the dispatch is verified — the listener is registered, the delivery
succeeds, a second listener on the same event fires, and calling the plugin's
handler directly records the row — but the registered listener does not run
when the real dispatcher fires. So:

- **The filter chain (`applyFilter`) works as a mechanism** — but core only ever
  calls it for the `theme.tokens` filter, which none of the three declares.
- **Direct handler invocation (`dispatchHook`) works.**
- **Framework `event()` → plugin hook does not, and is not proven.** The fix is
  tracked in the commit log (`e39797b`); the residual difference between `event()`
  and a manual call is still open.

The honest summary: **two of the three bundled plugins are only partially
wired.** Do not advertise `seo-booster`'s meta tags, `whatsapp-bridge`'s contact
notifications, or either plugin's event hooks as features of a customer's site.

`/admin/plugins/settings` is a generic settings editor over the whole `settings`
table — there is no per-plugin allowlist, so treat write access there as
settings-level access.

### Themes (`/admin/themes`)

Two ship: `default` and `dark-commerce`. Activation is real now: the active
theme's `tokens` and legacy `settings` are emitted as CSS custom properties,
and a theme that ships a `views/` directory **and** sets `"views": true` gets
that directory prepended to the view finder, so its `site/layout.blade.php`
replaces the shipped one. Deactivating removes both.

**`dark-commerce` has no tokens of its own beyond the colour-mode setting.**
Its `theme.json` is byte-for-byte equivalent to `default`'s (`settings.primary
= #4f46e5`), it has no `tokens` block, and its `layout.blade.php` sits at the
theme root rather than in `views/`, so it is never loaded. Activating it
therefore changes almost nothing. Do not sell it as a second look.

The customizer values are not reset when you activate a different theme —
nothing deletes the `customizer` group on activation. Clear those rows yourself
if you want per-theme values.

---

## 10. Content types and records (data builder)

For structured content that is not pages, posts or company-profile rows:
**Data → Content Types** defines a schema at runtime (17 field types), and
**Data → Records** stores rows against it. Each content type also gets a
physical `cb_*` table as a flat mirror for reporting in plain SQL.

**Import** (`/admin/cms/import`) and **Export** (`/admin/cms/export`) handle
CSV and JSON. Import is row-at-a-time and never aborts halfway; every rejected
row is reported with a line number and a reason.

Three honest caveats:

- **Relations are a UI seam only.** The six relation kinds are offered on the
  Relations screen, but there is no model trait or Eloquent relationship behind
  them. A `relation` field stores an opaque key.
- **Import bypasses validation and the physical mirror.** Imported rows skip
  the type rules; a CSV row whose column count does not match the header is
  skipped with no error entry.
- **The `cb_*` mirror is not created by a migration** and there is no rebuild
  command. If it and the JSON record ever disagree, the JSON record is the
  truth.

---

## 11. Backups and restore

**System → Backup** (`/admin/backups`) — run a backup now (`full`, `database`
or `files`), list archives with their exact path and size, delete one.

An archive contains a `database.sql` dump, the uploaded files under
`storage/app/public`, `public/storage/media` and `storage/app/careers` (over
25 MB skipped, capped at 3000 entries), a `manifest.json`, and a
**secret-stripped** `.env.example` — `APP_KEY`, `DB_PASSWORD` and anything
ending `_SECRET`/`_KEY`/`_TOKEN` is blanked. On a host with `ext-zip` it is a
zip; without it, a single concatenated `.sql` file.

Retention is `setting('storage.keep_backups', config('lindu.backup.keep', 7))`
archives. `lindu:backup --type=full` also runs daily from the scheduler.

**Copy the archives off the server.** A backup that only exists on the box that
produced it is not a backup. See [DEPLOYMENT.md](DEPLOYMENT.md).

**System → Restore** (`/admin/backups/restore`) — pick a `completed` archive
and confirm explicitly. The confirmation flag is required in code, so it cannot
be triggered by a stray GET. Restoring overwrites the current database and
files; take a fresh backup first.

`/admin/updates` also takes a backup before it applies anything, and refuses to
continue if that backup did not complete.

---

## 12. System health and logs

| Screen | What it gives you |
|---|---|
| **System → System Health** (`/admin/health`) | `HealthService::checks()` — PHP ≥ 8.3, nine extensions, writability of `storage/`, `storage/app` and `public/storage`, `select 1` against the database, and the queue driver — plus the PHP, Laravel and CiptaCMS versions. |
| **System → Info** (`/admin/info`) | PHP, Laravel, database driver, queue driver, cache driver. |
| **System → Logs** (`/admin/logs`) | `storage/logs/laravel.log` filtered by channel and level, with **Clear** and **Download**. |
| **System → Audit Logs** (`/admin/audits`) | `audit_logs` — who changed what, filterable by action. |
| **System → Activity Logs** (`/admin/system/activity`) | `activity_logs`. |
| **System → Jobs** (`/admin/queue`) | the queue, with retry and flush. |
| **System → Scheduled Tasks** (`/admin/system/schedule`) | what the scheduler is configured to run. |
| **System → Cache** (`/admin/system/cache`) | inspect and flush; the flush target is selectable. |
| **System → Storage** (`/admin/system/storage`) | disk usage. |
| **System → Database** (`/admin/system/database`) | tables and sizes. |
| **System → Maintenance** (`/admin/system/maintenance`) | maintenance mode, with an optional IP allowlist. |

The same checks from a shell, with more of them:

```sh
php artisan lindu:doctor
```

It exits non-zero on failure. It also checks `gd`, `bootstrap/cache`
writability, free disk (> 100 MB) and the storage link — `gd` and a missing
link are warnings rather than failures.

**System → Maintenance → Artisan** runs one artisan command and shows the
first 1500 characters of output. It is a **denylist**, not an allowlist:
`migrate:fresh`, `db:wipe`, `env:clear`, `key:clear`, `config:clear`,
`cache:clear`, `route:clear`, `view:clear` and `down` are refused; everything
else runs. If you delegate admin access, treat this screen as shell access.

---

## 13. The install lock

`/install` runs once. When it finishes it writes
`storage/app/installed`, which closes the installer. The lock lives outside the
web root and records the install time, the app URL, the app name, the admin
email, and the version **at the time of installation** (upgrades do not
rewrite it).

To reopen it — for a re-install, or to recover from a half-finished
installation:

```sh
php artisan lindu:unlock            # reports the install time and version, then refuses
php artisan lindu:unlock --force    # actually removes the lock
php artisan lindu:unlock --token    # prints the recovery token and changes nothing
```

The recovery token is a deterministic 32-character hex value derived from the
lock path and `APP_KEY`. The same install always produces the same token, and
`/install?recovery_token=…` reopens the installer for that one request before
it re-locks. Covered by `tests/Feature/InstallerTest.php`.

**Never delete `storage/app/installed` by hand on a live site**, and never give
`--force` to an operator who does not need it: with the lock gone, anyone who
can reach `/install` gets a full re-install wizard.

The browser installer deliberately **never generates `APP_KEY`**. A new key
would invalidate every encrypted setting, every session and the licence lock
file.

---

## 14. The update centre

`/admin/updates` — check versions, list module/plugin/theme registries, take a
backup, run migrations, clear caches, record a version, and read the history of
`update_logs`.

**It never downloads or runs code.** There is no remote fetch, no
`composer install`, no `git pull`, no file replacement, and no
`optimize:clear`. Shipping a release stays a deployment step —
see [UPGRADE.md](UPGRADE.md) and [UPDATES.md](UPDATES.md).

With no `LINDU_UPDATE_URL` set (the default), a check contacts nothing and
reports `source: local`. Read that field before telling a customer their copy
is current — `local` means *nobody checked anything*, not *you are up to date*.

Extension update checks (`checkAll`) return each registry row's own version as
both `current` and `latest` with `update_available: false`. That is a listing,
not a check.

---

## 15. Plans, quotas and tenants

**SaaS → Plans / Features / Usage / Subscriptions / Tenants / Domains**, and
**SaaS → Gateways**.

- **Plans and Features** are not decorative. `Quota::consume()` counts an
  action and raises `QuotaExceeded` (rendered as **HTTP 402**) when a metric is
  over its limit, and `Quota::assertFeature()` raises `FeatureUnavailable`
  (**HTTP 403**) for a gated feature. Counters are recounted nightly.
- Recount by hand with `php artisan lindu:quota-recount` (add `--tenant=<id>`
  to scope it). The screen prints the `used / limit` table it produced.
- **Subscriptions are a read-only list.** No code creates, renews, prorates or
  cancels a subscription; you would have to populate the table yourself.
- **Gateways are not billing.** `/admin/gateways` only writes
  `billing.gateways` into `settings`. There is no checkout page, no return or
  callback route, no invoice reconciliation and no dunning, and the
  `XenditGateway` verifies no callback signature. Do not describe this as
  payment processing in a proposal — see [SAAS.md](SAAS.md) and
  [ARCHITECTURE.md](ARCHITECTURE.md#money).
- The gateway secret is written as plain `json`, **not** `secret`, so it sits
  unencrypted in the `settings` table.

---

## 16. Security settings worth checking

**Users → Security** (`/admin/security/2fa`) — enable, disable and regenerate
two-factor for the signed-in user, with backup codes shown once.
**Sessions** — list and revoke sessions, and "log out everywhere else".
**Login History** — every sign-in attempt, successful or not, with the IP and
user agent.

**Settings → Security** — the login attempt limit and lockout window feed both
the controller lockout and the `throttle:login` limiter, which is now applied
to `POST /login`. `POST /2fa/challenge`, `POST /register` and
`POST /forgot-password` are throttled too.

**Settings → Advanced → Allow raw SQL in the data builder** is a settings row
that **nothing reads**. There is no raw SQL path in core. Leave it off and do
not enable it expecting a feature.

Two more that a security review will ask about:

- **Self-registration is off by default** on both the web form
  (`security.allow_registration`) and the JSON endpoint
  (`api.allow_registration`). Turn them on deliberately.
- **The REST API is not throttled by the framework.** A `throttle:api` limiter
  is defined (60/minute by default, per token or IP) but no route uses it.
  Public form posts and inbound webhooks are throttled. See
  [SECURITY.md](SECURITY.md) before exposing an install publicly.

---

## 17. Known limitations

These are real and current. State them rather than discovering them with a
customer.

1. **Two of the three bundled plugins are only partially wired.**
   `webhook-logger` and `whatsapp-bridge` have real classes and a working
   filter chain, but the plugin *event hook* path — framework `event()` reaching
   plugin code — is **not proven end to end** and does not fire under the test
   harness. In addition: nothing in core calls `applyFilter('seo.meta', …)` or
   `applyFilter('whatsapp.link', …)`, so `seo-booster`'s meta tags and the
   WhatsApp link are never produced; and `whatsapp-bridge`'s `contact.message`
   hook can never fire because the contact form dispatches
   `cms.contact.message`. Do not advertise any of them as working features.

2. **The admin interface is not translated.** `lang/en.json` and `lang/id.json`
   exist, a `SetLocale` middleware exists and reads `general.locale` — but no
   admin view calls `__()` and the middleware is not registered on any route or
   group, so it never runs. Only the public site structure is set up for
   translation. The residue is visible: **two admin screens still have
   hard-coded Indonesian strings** — the media library's help line
   (*"(jika GD support) diproses via queue"*) and the queue screen's
   Redis/Horizon hint (*"Jalankan … Untuk Redis+Horizon …"*). The two
   marketplace pairing views under `resources/views/license/` are also in
   Indonesian.

3. **There is no visual-regression baseline.** Responsive coverage is
   structural (tests over seven widths) plus `tools/screenshot.php` renders.
   A change could alter appearance without failing a test. Accessibility is
   asserted structurally too; no person has run a screen-reader or
   keyboard-only pass.

4. **`dark-commerce` has no tokens of its own beyond the colour-mode setting.**
   It is not a second look. Only `default` is a real theme.

5. **Page-builder sections always render in one column**, and per-breakpoint
   section visibility is honoured in the editor but not on the published page.
   The `grid` component is now self-contained, but the `html` component is an
   unsanitised passthrough by design.

6. **Four form-builder features are stored but not enforced:** `is_unique`,
   `validation`, `conditional`, and the `captcha` / `block_disposable_email`
   switches. `repeater` renders as a single text input. A `password` field
   stores its value.

7. **Menu deactivation is not instant**, and there is no drag-and-drop
   reordering; `admin.menus.create` and `admin.menus.edit` are dead routes.

8. **The customizer reset on theme activation is not implemented**, and
   `dark-commerce` never applies its `layout.blade.php`.

9. **Several white-label keys are stored and never read** — login logo,
   background, error-page text, email sender identity and the
   hide-vendor-branding switches. The rebrand checklist in
   [WHITE_LABEL.md](WHITE_LABEL.md#re-branding-checklist) lists them.

10. **Media type is not enforced.** `allowed_mimes` is documentation, not a
    control.

11. **The test suite is not a release gate.** Run it, read the failures, do not
    assume a red build is pre-existing. See
    [DEVELOPMENT.md](DEVELOPMENT.md#current-state-of-the-suite).

---

## See also

[USER_GUIDE.md](USER_GUIDE.md) · [DEVELOPER.md](DEVELOPER.md) ·
[INSTALL.md](INSTALL.md) · [UPGRADE.md](UPGRADE.md) ·
[DEPLOYMENT.md](DEPLOYMENT.md) · [SECURITY.md](SECURITY.md) ·
[MENU_ENGINE.md](MENU_ENGINE.md) · [THEMES.md](THEMES.md) ·
[PLUGINS.md](PLUGINS.md) · [MODULES.md](MODULES.md) ·
[WORKFLOW.md](WORKFLOW.md) · [FORM_BUILDER.md](FORM_BUILDER.md) ·
[DATA_BUILDER.md](DATA_BUILDER.md) · [UPDATES.md](UPDATES.md) ·
[SAAS.md](SAAS.md) · [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
