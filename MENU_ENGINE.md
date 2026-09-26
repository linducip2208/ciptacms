# Menu Engine

Menus are database rows, not code. One table (`menu_items`), one service
(`MenuService`), two renderers (admin sidebar, public nav), and a manifest
convention so a [module](MODULES.md) can ship its own admin navigation.

- Model: `app/Models/MenuItem.php`
- Service: `app/Core/Services/MenuService.php` (`tree()`, `forget()`)
- Admin rendering: `resources/views/admin/partials/sidebar-menu.blade.php` → `menu-tree.blade.php`
- Public rendering: `resources/views/site/partials/nav.blade.php`
- Composer: `app/View/Composers/AdminMenuComposer.php` (shares `$adminMenu`)
- Admin UI: `admin.menus.*` → `Admin\MenuController`
- Module registration: `ModuleManager::registerMenus()` reads each `module.json`

## The row

`menu_items` columns (`MenuItem::$fillable`): `tenant_id`, `location`,
`parent_id`, `title`, `icon`, `route`, `url`, `permission`, `roles`, `module`,
`badge`, `badge_color`, `sort_order`, `target`, `is_visible`, `meta`, plus a
unique `uuid` added by migration `2026_09_28_100022`.

`roles` and `meta` are `array` casts; `is_visible` is a boolean.
`parent_id` is a self-referencing foreign key with `nullOnDelete`.

| Column | Meaning |
|---|---|
| `location` | which menu this belongs to. `admin` for the sidebar, `primary` for the public nav. Arbitrary strings are allowed. |
| `parent_id` | `null` = top level. Nesting depth is **not** limited by the schema or the service — `nest()` recurses. |
| `route` / `url` | named route **or** literal URL. `url` wins when both are set. |
| `permission` | a single permission slug; the item is hidden unless the user has it |
| `roles` | array of role slugs; the item is hidden unless the user holds one of them |
| `module` | which module owns the item. `ModuleManager::deactivate()` / `uninstall()` delete every row with this value. |
| `target` | `_self` (default) or `_blank` |
| `badge` / `badge_color` | small label rendered next to the title in the admin sidebar |
| `sort_order` | ascending within the parent |
| `is_visible` | `false` removes the item from the tree entirely |

## `MenuService::tree()`

```php
MenuService::tree(string $location = 'admin', $user = null): array
```

1. **Cache key** — `lindu.menu.{location}.{user_id|guest}.{tenant_id|global}`,
   for **120 seconds**.
2. **Query** — `where('location', …)->where('is_visible', true)->orderBy('sort_order')`.
3. **Tenant scoping** — when a tenant is bound, keep rows where `tenant_id` is
   `NULL` **or** equals the current tenant. Global rows are therefore shared by
   every tenant.
4. **Visibility filter** — `visible()` drops the row if:
   - `permission` is set and there is no user, no `hasPermission()` method, or
     the user lacks it; or
   - `roles` is a non-empty list and the user holds none of those role slugs.
5. **Nesting** — `nest()` walks `where('parent_id', $parent)`, recursing into
   `children`. Each node gets an `active` flag:
   `request()->is(ltrim(url,'/').'*')` **or** `request()->routeIs($route)`.
   Note this means an item is "active" when the current path is the item's path
   **or anything beneath it** — a section stays highlighted while you are on a
   child page.

Because the permission filter runs on the flat collection *before* nesting, a
child whose parent was filtered out is simply not rendered: there is no "show
the parent because it has a visible child" logic. A section with no visible
children still renders as a collapsed group.

### `MenuService::forget()`

```php
public static function forget(): void { Cache::flush(); }
```

This is the **entire application cache**, not just menu keys. Every menu write
(`MenuController`), every module activate/deactivate/uninstall and
`UpdateService::apply()` call it. On a file or database cache store that is cheap
but throws away every other cached value; on Redis or Memcached it is a
`FLUSHDB`/`flush_all`, not a targeted delete. If you put something expensive in
the cache, expect it to disappear on every menu edit. (This is a known wart, not
a design choice — there is no targeted `Cache::forget("lindu.menu.*")` yet.)

## Rendering

### Admin sidebar

`AdminMenuComposer` injects `$adminMenu = MenuService::tree('admin',
auth()->user())` into the admin views. `sidebar-menu.blade.php` always renders
the Dashboard link, then includes `menu-tree.blade.php` for the rest.

`menu-tree.blade.php` recurses into itself for `children`:

- an item with children renders a Bootstrap collapse (`#menu-{id}`), the `icon`
  or a chevron fallback, the `title`, and an optional `badge` with
  `bg-{badge_color|blue}`
- a leaf renders `url ?? route(route) ?? '#'` with `target`
- the active state is recomputed in the view: `routeIs(route)` if `route` is
  set, otherwise `is(ltrim(url,'/') . '*')`

### Public nav

`resources/views/site/partials/nav.blade.php` calls
`MenuService::tree('primary', auth()->user())` inside a `try/catch`, so a menu
failure cannot take the public site down.

- It renders **one level only** — `children` are used to decide whether to show
  a `▾`, but they are not rendered. There is no public submenu markup.
- `href` is `url ?? (route ? route(route) : '#')`; `target="_blank"` adds
  `rel="noopener"`.
- Active state is first-segment based: an item is active when its path equals or
  is prefixed by the first path segment of the current URL.
- **When there are no rows for `primary`, it falls back to hard-coded links** to
  `site.about`, `site.services`, `site.products`, `site.portfolio`, `site.blog`
  and `site.contact`, so a fresh install is still navigable.

## Admin CRUD

`Admin\MenuController` at `/admin/menus`:

| Route | Behaviour |
|---|---|
| `GET admin.menus.index` | flat list for `?location=` (default `admin`) |
| `POST admin.menus.store` | validates `title`, `location`, `parent_id`, `url`, `route`, `icon`, `permission`, `sort_order`; `is_visible` defaults to `true` |
| `PUT admin.menus.update` | writes only `title`, `parent_id`, `url`, `route`, `icon`, `permission`, `sort_order`, `target`, `badge`, `badge_color`, `module` plus `is_visible` |
| `DELETE admin.menus.destroy` | soft delete (the model uses `SoftDeletes`) |
| `POST admin.menus.reorder` | rewrites `sort_order` to the array index and applies one `parent_id` to **every** id in the payload |

The index view is a flat table with an inline create form and a delete button.
There is no drag-and-drop reordering UI and no per-item edit form: `reorder()`
is a JSON endpoint and `create` / `edit` are not implemented
(`admin.menus.create` and `admin.menus.edit` are registered routes with no
controller method — do not link to them). Editing goes through `update`, and
`target`, `badge`, `badge_color`, `roles` and `meta` are only reachable through
the API, a module manifest, or the database.

## Declaring menus from a module

`module.json` may carry a `menus` array. `ModuleManager::registerMenus()`
`updateOrCreate`s each entry keyed on `(location, module, title, parent_id)`, so
re-running install/activate is idempotent.

```json
{
  "menus": [
    {
      "title": "Company Profile",
      "icon": "ti ti-building",
      "location": "admin",
      "sort_order": 40,
      "children": [
        { "title": "Home",  "url": "/admin/company",        "icon": "ti ti-home",  "sort_order": 10 },
        { "title": "About", "url": "/admin/company/about",  "icon": "ti ti-info-circle" }
      ]
    }
  ]
}
```

Per entry: `title` (required — entries without one are skipped), `icon`, `url`,
`route`, `permission`, `sort_order` (defaults to `index * 10`), `is_visible`
(defaults to `true`), `meta`, plus a `children` array. Only **one level of
`children`** is registered — a nested `children` key inside a child is ignored,
so a module menu is two levels deep at most. Use `module.json` for module
navigation and the admin UI for anything deeper.

`syncModuleMenus()` runs this for every active module and is called from the
seeder and after activation.

## Seeder

`database/seeders/MenuSeeder.php` and `FrontendMenuSeeder.php` build the default
admin and public menus, including the entries the bundled
[company-profile](COMPANY_PROFILE.md) module contributes. The seeded URLs are
checked by `Tests\Feature\RepositoryAuditTest`, so a renamed or removed route
breaks the suite rather than the site.

## Manual recipes

A "Services" section with a "Detail" child under the public nav:

```sql
INSERT INTO menu_items (location, title, url, parent_id, sort_order, is_visible, created_at, updated_at)
VALUES ('primary', 'Services', '/services', NULL, 10, 1, NOW(), NOW());

INSERT INTO menu_items (location, title, url, parent_id, sort_order, is_visible, created_at, updated_at)
VALUES ('primary', 'Case studies', '/portfolio', LAST_INSERT_ID(), 20, 1, NOW(), NOW());
```

Remember to call `MenuService::forget()` (or wait 120s) afterwards, otherwise the
cached tree still shows the old menu.

See also: [MODULES.md](MODULES.md), [COMPANY_PROFILE.md](COMPANY_PROFILE.md),
[SECURITY.md](SECURITY.md).
