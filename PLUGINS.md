# Plugins

A **plugin** is an extension/integration point. A **module** is a business
application. Both are folder + manifest, but a plugin is the lighter of the two:
it contributes settings and declares participation in hooks and filters.

- Manager: `app/Core/Services/PluginManager.php`
- Registry: the `plugins` table (`slug` unique, `name`, `version`, `author`,
  `description`, `meta` JSON, `is_installed`, `is_active`)
- Admin UI: `/admin/plugins` → `admin.plugins.index`, `admin.plugins.action`,
  `admin.plugins.settings`
- Path: `config('lindu.plugins_path')` = `base_path('plugins')`

## What ships in `plugins/`

```
seo-booster  webhook-logger  whatsapp-bridge
```

**All three are manifest-only.** Each folder contains `plugin.json` and a
`README.md` — no PHP, no views, no routes. All three declare the same
`hooks: ["page.rendered"]` and `filters: ["seo.meta"]`.

There is no plugin autoloader, no plugin class, no sandbox and no plugin
bootstrap file. A plugin folder contributes **settings rows and a registry
entry** — nothing else. `webhook-logger` does not log webhooks;
`whatsapp-bridge` is not connected to anything.

## The manifest

```json
{
  "name": "SEO Booster",
  "slug": "seo-booster",
  "version": "1.0.0",
  "author": "Lindu",
  "description": "Adds extra meta helpers",
  "hooks":   [ "page.rendered" ],
  "filters": [ "seo.meta" ]
}
```

`slug` is required for discovery. `name`, `version`, `author` and `description`
are copied onto the registry row by `syncRegistry()`, which runs on every request
from `LinduCoreProvider::boot()`. **Edit the JSON and the next request sees it** —
no reinstall needed.

Unlike a module, a plugin manifest has **no** `menus`, `permissions` or
`dependencies` handling. `PluginManager` reads none of those keys.

## Lifecycle

`PluginController::action()` whitelists the action explicitly — this is the
correct pattern (compare `ModuleController`, which does not):

```php
abort_unless(in_array($action, ['install', 'activate', 'deactivate', 'uninstall', 'update'], true), 404);
```

then calls `$m->$action($slug)`:

| Method | Behaviour |
|---|---|
| `install($slug)` | **not implemented** — `PluginManager` has no `install()`, so this throws and the controller shows the error |
| `activate($slug)` | `is_active = true, is_installed = true` |
| `deactivate($slug)` | `is_active = false` |
| `uninstall($slug)` | `is_active = false, is_installed = false` |
| `update($slug)` | **not implemented** — no such method |

There are no menus to register, no migrations to run and no audit entry, unlike
modules. The only plugin action that works today is activate / deactivate /
uninstall.

## Hooks and filters

Two static helpers. Read what they actually do.

### `PluginManager::hooks($hook, array $payload = []): array`

Returns the payload with `$payload['plugins'][] = $slug` appended for every
**active** plugin whose manifest `hooks` array contains `$hook`.

It is an **observer list**, not an execution point. Nothing calls a plugin
method; it just reports which plugins expressed interest. A caller has to
iterate the result itself.

### `PluginManager::filters($filter, $value, array $ctx = [])`

```php
public static function filters(string $filter, $value, array $ctx=[]) {
    try {
        foreach (Plugin::where('is_active',true)->get() as $p) {
            $f=$p->meta['filters']??[];
            if (in_array($filter,(array)$f)) { /* plugins may observe; core keeps value stable */ }
        }
    } catch (\Throwable $e) {}
    return $value;
}
```

**It returns `$value` unchanged.** The loop body is a comment. This is a declared
seam: the filter protocol exists, and the core deliberately keeps the value
stable until a plugin system can actually transform it.

Neither helper is called from the request pipeline today, so declaring
`page.rendered` in a manifest has no runtime effect. `/admin/developer/hooks`
lists manifests and their files, which is where you can currently see them.

## Settings

`/admin/plugins/settings` shows every plugin and every key in the `settings`
table. `saveSettings()` writes whatever is posted, deriving the group from the
part of the key before the first dot (falling back to `plugins`), and choosing
`json` for an array, `boolean` when `settings.{key}.__bool` is truthy, `text`
otherwise. There is no allowlist — a plugin settings screen is a generic
settings editor.

## Making a real plugin

There is currently no code-loading mechanism, so a real plugin needs one of:

1. **Settings-only** (what the shipped ones are): a `plugin.json` that documents
   the intent, plus settings rows the core reads. Works today.
2. **Code in core**: add the class under `app/` and call it from the relevant
   service or from a `PluginManager::hooks()` consumer. The folder + manifest is
   then the registry and the version marker.
3. **A loader**: add a `PluginManager::boot()` that requires a
   `plugins/{slug}/Plugin.php` when active. Nothing does this today — if you add
   it, be aware you are adding arbitrary code execution driven by a database
   flag.

## See also

[MODULES.md](MODULES.md), [THEMES.md](THEMES.md), [ARCHITECTURE.md](ARCHITECTURE.md).
