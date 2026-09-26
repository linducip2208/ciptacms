# Modules

A **module** is a business application folder under `modules/`. Discovery is by
folder plus a manifest: `ModuleManager::discover()` iterates the directories in
`config('lindu.modules_path')` and accepts any folder containing a `module.json`
with a non-empty `slug`. There is no hard-coded module list.

- Manager: `app/Core/Services/ModuleManager.php`
- Registry: `modules`, `modules.php` in `ModuleManager::$…` — the DB table
  `modules` (`slug` unique, `name`, `version`, `author`, `description`, `meta`
  JSON, `is_installed`, `is_active`)
- Admin UI: `/admin/modules` → `admin.modules.index` / `admin.modules.action`
- Provider: `app/Providers/ModuleServiceProvider.php`
- Admin permissions: whatever the manifest's `permissions` array contributes

## What ships in `modules/`

```
api  blog  company-profile  crm  ecommerce  erp  forms  hotel
jodohku  lms  marketplace  media  pages  pos  seo  tenants
```

**Read this before you sell one of them.** Fifteen of these sixteen are
**manifest stubs**:

- `routes.php` is a single placeholder route returning
  `{"module":"…","ok":true}` under `/m/{slug}`;
- there is no controller, no view, no migration and no model inside the folder;
- the models (`Order`, `Room`, `Course`, `Lead`, `Product`, `Sale`, …) live in
  `app/Models/` and are exposed through `config/lindu_admin.php` generic CRUD and
  the corresponding database tables.

So `pos`, `hotel`, `lms`, `crm`, `erp`, `marketplace`, `jodohku` and `ecommerce`
are **schema plus generic admin CRUD**, not working applications. There is no POS
transaction, no room-availability check, no quiz runner, no marketplace checkout
and no ERP ledger. Do not describe them as finished products.

The one module with real behaviour is **`company-profile`**, and even it is a
manifest only — its 12 models are in `app/Models/Cp/`, its controller is
`app/Http/Controllers/Admin/CompanyProfileController.php`, its admin routes come
from `routes/company.php`, and its public routes from `routes/site.php`. See
[COMPANY_PROFILE.md](COMPANY_PROFILE.md).

## The manifest

```json
{
  "name": "Company Profile",
  "slug": "company-profile",
  "version": "1.0.0",
  "author": "Lindu",
  "description": "…",
  "dependencies": [],
  "menus":    [ { "title": "…", "icon": "…", "children": [ … ] } ],
  "permissions": [ "company.view", "company.manage" ],
  "events": [ "contact.message", "job.application" ]
}
```

| Key | Read by | Effect |
|---|---|---|
| `slug` | `discover()` | **Required.** Without it the folder is skipped. |
| `name`, `version`, `author`, `description` | `syncRegistry()` | columns on the `modules` row |
| `dependencies` / `requires` | `checkDeps()` | install and activate refuse unless every listed module is **active** |
| `menus` | `registerMenus()` | `menu_items` rows — see [MENU_ENGINE.md](MENU_ENGINE.md) |
| `permissions` | `registerPermissions()` | `permissions` rows, split on `.` into `action` + `module`, grouped under a `PermissionGroup` named after the module |
| `events` | — | **declarative only.** No code reads this key; it documents what the module emits. |
| `layouts`, `settings` | — | only meaningful in a *theme* manifest |

`syncRegistry()` runs on **every request** from
`LinduCoreProvider::boot()` and does `updateOrCreate(['slug' => …], [...])`. It is
idempotent and cheap (one directory scan), but it means **editing a
`module.json` takes effect on the next request** — you do not need to
re-install.

## What the provider loads

`ModuleServiceProvider::boot()` walks the same directories and, for each one
that is not deactivated in the database, loads what exists:

| File/dir | Registered as |
|---|---|
| `routes.php` | `Route::middleware('web')` |
| `routes-api.php` | `Route::prefix('api/v1')->middleware('api')` |
| `views/` | view namespace `mod-{slug}` |
| `lang/` | translation namespace `mod-{slug}` |
| `database/migrations/` | loaded migrations |

A folder without `module.json` is still processed for routes/views — the
`is_active` check is skipped when there is no registry row. Keep the manifest.

Deactivating a module makes the provider skip it entirely, so its routes
disappear from the route table (not just from the menu).

## Lifecycle

`ModuleController::action($slug, $action)` dispatches to `ModuleManager`:

| Method | Behaviour |
|---|---|
| `install($slug)` | `checkDeps()` → `is_installed = true` → `runMigrations()` → register menus + permissions → `MenuService::forget()` → audit |
| `activate($slug)` | `checkDeps()` → `is_active = true` → `runMigrations()` → register menus + permissions → forget → audit |
| `deactivate($slug)` | `is_active = false` → **delete every `menu_items` row with `module = $slug`** → forget → audit |
| `uninstall($slug)` | `is_active = false, is_installed = false` → delete the module's menu rows → forget |

`runMigrations()` calls `artisan migrate --path=modules/{slug}/database/migrations
--force` when the directory exists.

There is **no `update()` method** and no `m/{slug}/update` action. Upgrading a
module is a `git pull` plus `php artisan migrate` — see [UPDATES.md](UPDATES.md).
`uninstall()` does **not** drop tables; it only flags the registry row and removes
menus. The tables stay.

### A caveat on `action()`

```php
public function action(Request $r, string $slug, string $action, ModuleManager $m){
    try{ $m->$action($slug); … }
```

`$action` comes straight from the URL segment and is **not validated against a
list** before the dynamic call. A POST to
`/admin/modules/{slug}/{anything}` invokes any public method of `ModuleManager`
that takes one string argument. The route is behind `auth` so this is not
anonymous, but it should be an `abort_unless(in_array($action, [...]), 404)` —
`PluginController::action()` does exactly that and does **not** have the problem.

## Making a real module

1. `mkdir modules/your-module`
2. `module.json` with at least `slug`, `name`, `version`
3. `routes.php` and/or `routes-api.php` (they are auto-loaded)
4. `views/` (namespace `mod-your-module`) and/or `lang/`
5. `database/migrations/` — run on install/activate
6. Declare `permissions` and `menus` so RBAC and navigation come for free
7. Drop the controller and models **inside** the module if you want a genuinely
   independent app; core does not need to know about them

`ModuleManager::dependents($slug)` reports which active modules declare a
dependency on the given slug — use it before removing a module.

## See also

[PLUGINS.md](PLUGINS.md), [THEMES.md](THEMES.md), [MENU_ENGINE.md](MENU_ENGINE.md),
[COMPANY_PROFILE.md](COMPANY_PROFILE.md), [DATABASE.md](DATABASE.md).
