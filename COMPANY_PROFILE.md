# Company Profile

A complete company website — about, services, products, portfolio, team,
testimonials, clients, FAQ, gallery, careers and contact — driven entirely by
database rows. This is the module that turns the CMS into a sellable
"company profile" product.

- Manifest: `modules/company-profile/module.json`
- Models: `app/Models/Cp/` (12 models, `cp_*` tables)
- Admin controller: `app/Http/Controllers/Admin/CompanyProfileController.php`
- Public controller: `app/Http/Controllers/Frontend/SiteController.php`
- Settings helper: `app/Core/Services/CompanyProfileService.php`
- Migration: `database/migrations/2026_09_28_100020_create_company_profile_tables.php`
- Seeder: `database/seeders/CompanyProfileSeeder.php`
- Admin routes: `routes/company.php` (`admin.company.*`, mounted inside `/admin`)
- Public routes: `routes/site.php` (`site.*`)

## Data model — 12 `cp_*` tables

| Table | Model | Notes |
|---|---|---|
| `cp_services` | `Cp\Service` | `icon`, `excerpt`, `description`, `features[]`, `cta_label`, `cta_url` |
| `cp_products` | `Cp\Product` | `image`, `gallery[]`, `excerpt`, `description`, `features[]`, CTA |
| `cp_portfolios` | `Cp\Portfolio` | `client`, `category`, `project_date`, `url`, `excerpt`, `description`, `technology[]`, `images[]` |
| `cp_team` | `Cp\TeamMember` | `name`, `position`, `photo`, `social{network:url}`, `bio` |
| `cp_testimonials` | `Cp\Testimonial` | `customer`, `company`, `photo`, `rating` (1–5), `testimonial` |
| `cp_clients` | `Cp\Client` | `name`, `logo`, `website` |
| `cp_faqs` | `Cp\Faq` | `question`, `category`, `answer` |
| `cp_gallery_albums` | `Cp\GalleryAlbum` | `title`, `description`, `cover`, `status`, `sort_order` |
| `cp_gallery_images` | `Cp\GalleryImage` | `album_id`, `path`, `caption`, `sort_order` |
| `cp_careers` | `Cp\Career` | `position`, `location`, `employment_type`, `deadline`, `description`, `requirements[]` |
| `cp_job_applications` | `Cp\JobApplication` | `career_id`, `name`, `email`, `phone`, `cover_letter`, `cv_path`, `status`, `ip` |
| `cp_contact_messages` | `Cp\ContactMessage` | `name`, `email`, `phone`, `subject`, `message`, `status`, `ip` |

Every model uses `SoftDeletes` and the shared traits:

- `HasSlug` — on `creating`, if `slug` is empty, generate
  `Str::slug(<first non-empty of title, name, position, question, customer, label>)-<4 random chars>`.
  Supplying your own `slug` disables generation.
- `BelongsToTenant` — fills `tenant_id` from the resolved tenant.
- `Auditable` — records changes.
- `seo()` — a `morphOne(SeoMeta, 'seoable')`, so per-row meta title /
  description / canonical / robots / OpenGraph / Twitter card.

Content models share two scopes used everywhere on the public site:
`scopePublished()` (`status = 'published'`) and `scopeOrdered()`
(`sort_order`, then the title/name column).

## The whitelisted `RESOURCES` map

`CompanyProfileController::RESOURCES` is the whole admin CRUD surface. The
`{resource}` route parameter is resolved **only** through this map:

```php
protected function resource(string $slug): array
{
    if (! isset(self::RESOURCES[$slug])) {
        abort(404, 'Unknown company resource: '.$slug);
    }
    return self::RESOURCES[$slug];
}
```

`$def['model']` is a hard-coded class constant. **The route parameter is never
used to build a class name** — that is the point, and it is why the generic
`/admin/company/data/{resource}` routes are safe to expose.

| `resource` | Model | Searchable columns |
|---|---|---|
| `services` | `Service` | `title`, `excerpt` |
| `products` | `Product` | `title`, `excerpt` |
| `portfolio` | `Portfolio` | `title`, `client`, `category` |
| `team` | `TeamMember` | `name`, `position` |
| `testimonials` | `Testimonial` | `customer`, `company` |
| `clients` | `Client` | `name` |
| `faqs` | `Faq` | `question`, `category` |
| `careers` | `Career` | `position`, `location` |

Each entry also declares `label`, `singular`, `columns` (table headers) and
`fields` — a per-field definition of `type`, Laravel `rules`, grid `col` width
and optional `help`. One map therefore drives the list columns, the create/edit
form, the validation rules and the flash messages.

Field `type` values used in the map: `text`, `textarea`, `richtext`, `number`,
`date`, `select`, `image`, `lines`.

## Admin routes

All under `/admin/company/`:

| Route | Behaviour |
|---|---|
| `GET .` | dashboard: counts per table, 5 latest messages, 5 latest applications |
| `GET/POST .about` | about page — `about.description`, `history`, `vision`, `mission`, `values` |
| `GET/POST .contact` | address, phone, whatsapp, email, `map_embed`, `business_hours`, `social` |
| `GET .messages` | paginated 20, `?status=` and `?search=` (name / email / subject) |
| `POST .messages.{message}.status` | `new` \| `read` \| `replied` \| `archived` |
| `DELETE .messages.{message}` | delete |
| `GET .applications` | paginated 20, `?status=` |
| `POST .applications.{application}.status` | `received` \| `reviewing` \| `shortlisted` \| `rejected` \| `hired` |
| `GET .data/{resource}` | list: `?search=`, `?status=`, `?sort=` + `?dir=asc\|desc`, else `sort_order, id desc`; paginate 20 |
| `GET .data/{resource}/trash` | `onlyTrashed()`, paginate 20 |
| `GET .data/{resource}/create` | blank form |
| `POST .data/{resource}` | create |
| `GET .data/{resource}/{id}/edit` | edit (uses `withTrashed()`) |
| `PUT .data/{resource}/{id}` | update (`withTrashed()`) |
| `DELETE .data/{resource}/{id}` | soft delete |
| `POST .data/{resource}/{id}/restore` | `onlyTrashed()->findOrFail()` → restore |
| `POST .data/{resource}/{id}/duplicate` | `replicate()`, clear ids/timestamps, suffix `" (copy)"` on `title`/`name`/`position`/`question`/`customer`, re-slug with a random suffix |
| `POST .data/{resource}/{id}/toggle` | flip `published` ⇄ `draft` |
| `POST .data/{resource}/reorder` | rewrite `sort_order` to the array index; returns `{"ok":true}` |
| `GET/POST .albums` | album list / create-or-update (`{id}` in the body updates) |
| `DELETE .albums.{album}` | delete |
| `GET .albums.{album}.images` | images for one album |
| `POST .albums.{album}.images` | add image (`path` required, `caption`, `sort_order`) |
| `DELETE .albums.images.{image}` | delete |
| `POST .albums.{album}.images.reorder` | reorder, scoped to the album |

## Multi-value and paired fields

Two conventions, both applied in `payload()`:

- `ARRAY_FIELDS = ['features', 'technology', 'images', 'gallery', 'requirements']`
  — a `lines` textarea is stored as a JSON array. `toArray()` accepts a JSON
  array, a newline list, or an array, and drops empty values.
- `social` — a `lines` textarea of `network|url` lines becomes
  `['linkedin' => 'https://…']`. `socialPairs()` discards any line without a
  `|` or with an empty side.

Both are also used by the contact settings (`contact.social` is a JSON map in
`settings`).

## SEO per row

`saveSeo()` runs after every create and update, but **only if the request
contains an `seo` key**:

```php
SeoMeta::updateOrCreate(
    ['seoable_type' => $row::class, 'seoable_id' => $row->getKey()],
    array_intersect_key($request->input('seo'), array_flip([
        'meta_title', 'meta_description', 'canonical', 'robots',
        'og_title', 'og_description', 'og_image', 'twitter_card',
    ]))
);
```

Fields outside that allowlist are ignored.

## About and contact live in `settings`

`CompanyProfileService` reads and writes plain settings, not tables:

- `ABOUT`: `about.description`, `about.history`, `about.vision`,
  `about.mission`, `about.values` (a list)
- `CONTACT`: `contact.address`, `contact.phone`, `contact.whatsapp`,
  `contact.email`, `contact.map_embed`, `contact.business_hours`,
  `contact.social` (a map)

`about()` / `contact()` return an array keyed by the full setting key. The
`group` is `company`, and `contact.social` is stored JSON-encoded.
`socialLinks()` is a one-liner over `contact()`.

These are the same values the `contact` [page builder](PAGE_BUILDER.md) block
renders.

## Public site

`routes/site.php` — fixed section slugs are registered **before** the generic
`/p/{slug}` page route so a CMS page can never shadow a core section.

| Route | Notes |
|---|---|
| `GET /` | a published page with `is_homepage` wins and renders through the page builder; otherwise the default home view with services / posts / testimonials / clients / counts |
| `GET /about` | `about` settings + up to 8 team members + stats (years from `general.founded_year`) |
| `GET /services`, `/services/{slug}` | detail page also shows 3 other services |
| `GET /products`, `/products/{slug}` | same shape |
| `GET /portfolio`, `/portfolio/{slug}` | `paginate(12)`, `?category=`, `?search=`; category list from distinct non-null `category` |
| `GET /team` | |
| `GET /testimonials` | |
| `GET /clients` | |
| `GET /faq` | grouped by `category`, falling back to `General` |
| `GET /gallery`, `/gallery/{slug}` | the album route highlights one album |
| `GET /careers`, `/careers/{slug}` | `paginate(15)`, `?search=` and `?location=`; location list from distinct values |
| `POST /careers/{career}/apply` | see below |
| `GET /contact`, `POST /contact` | see below |
| `GET /blog`, `/blog/{slug}` | blog is core, not this module |
| `GET /p/{slug}` | `routes/web.php` — a CMS page, rendered with the page builder |

Detail routes use `firstOrFail()` on `published()`, so an unpublished row is a
404, not a leak. The public controller wraps most reads in `safe()` /
`safeCollection()`, which swallows a `QueryException` and returns an empty
result — deliberate, so the public site survives a half-migrated database.

### Job application

`POST /careers/{career}/apply` validates `name`, `email`, `phone`,
`cover_letter`, and `cv` as `nullable|file|mimes:pdf,doc,docx|max:4096`
(4 MB). A CV is stored on the `public` disk under `careers/`. If the career has
a `deadline` in the past the request is rejected with
`"This position is already closed."`. The new application is `status: received`
and the `job.application` event fires.

### Contact message

`POST /contact` validates `name`, `email`, `phone`, `subject`, `message` and a
honeypot `website` field with `'nullable|max:0'` — any submitted value fails
validation. The row is stored with `status: new` and `contact.message` fires.

### The event fan-out

Both public POSTs end in `notifyNew()`, which — each in its own `try/catch` so a
failure cannot lose the message — fires:

```php
event('cms.'.$event, $payload);
WebhookDispatcher::dispatchEvent($event, $payload);   // contact.message / job.application
WorkflowEngine::trigger($event, $payload);           // contact.message / job.application
```

Both event names are declared in `WorkflowEngine::TRIGGERS` and in the module
manifest's `events` array.

## Menu contributions

`modules/company-profile/module.json` declares one admin menu group with 14
children (Home, About, Services, Products, Portfolio, Team, Testimonials,
Clients, FAQ, Gallery, Careers, Messages, Applications, Settings) and two
permissions: `company.view`, `company.manage`.

`ModuleManager::registerMenus()` turns that into `menu_items` rows on install and
activate, and `deactivate()` / `uninstall()` delete every row with
`module = company-profile` — see [MENU_ENGINE.md](MENU_ENGINE.md).

## Using the content elsewhere

Six [page builder](PAGE_BUILDER.md) blocks read these tables directly, so a page
built in the admin automatically reflects the company data:
`testimonials`, `team`, `contact`, `gallery`, and the `dynamic` block's
`services`, `team`, `testimonials`, `clients`, `faq` and `portfolio` sources.
They are all wrapped in `try/catch`, so a page renders a note instead of a 500
when the data is missing.

## This module is a manifest plus core code

`modules/company-profile/` contains **only `module.json`** — no
`routes.php`, no `views/`, no migrations, no controller. The controller is
`app/Http/Controllers/Admin/CompanyProfileController.php` and the models are
`app/Models/Cp/`, i.e. inside core. `ModuleServiceProvider` therefore loads
nothing for this module, and the admin routes come from `routes/company.php`,
which `routes/admin.php` requires unconditionally.

The practical effect: the module can be deactivated in the admin (which deletes
its menu rows), but its tables, models, controller and admin routes are still
present. **Deactivation hides the navigation, it does not remove the feature.**
Moving the code into the module folder is real work.

See also: [PAGE_BUILDER.md](PAGE_BUILDER.md), [MENU_ENGINE.md](MENU_ENGINE.md),
[WORKFLOW.md](WORKFLOW.md), [MODULES.md](MODULES.md).
