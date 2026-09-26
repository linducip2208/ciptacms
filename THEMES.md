# Themes

A theme is a presentation folder under `themes/` with a `theme.json` manifest.
Activation is a flag on the `themes` table; exactly one theme is active at a
time.

- Manager: `app/Core\Services/ThemeManager.php`
- Controller: `app/Http/Controllers/Admin/ThemeController.php`
- Admin UI: `/admin/themes`, `/admin/themes/customize`
- Registry: the `themes` table (`slug` unique, `name`, `version`, `author`,
  `description`, `meta` JSON, `is_active`)
- Path: `config('lindu.themes_path')` = `base_path('themes')`

## What ships in `themes/`

```
default          theme.json + layout.blade.php
dark-commerce    theme.json + layout.blade.php
```

Both manifests are near-identical:

```json
{
  "name": "Lindu Default",
  "slug": "default",
  "version": "1.0.0",
  "author": "Lindu",
  "description": "Responsive default theme",
  "layouts": ["default", "fullwidth"],
  "settings": { "primary": "#4f46e5" }
}
```

Only `slug` is required for discovery. `name`, `version`, `author` and
`description` are copied onto the registry row by `syncRegistry()`, which runs on
every request from `LinduCoreProvider::boot()`.

**`layouts` and `settings` are not read by any code.** No controller, view or
service loads `theme.json`'s `layouts` or `settings` keys. They are a convention
for a theme system that does not exist yet.

## The theme layouts are not wired up

Each theme has a `layout.blade.php`. `default/layout.blade.php` is, in full:

```blade
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Lindu Default</title></head><body>@yield('content')</body></html>
```

**The public site never renders it.** Every public view extends
`site.layout` (`resources/views/site/layout.blade.php`), which is a Tabler +
custom-CSS layout reading the `branding.*` settings. `ThemeManager::active()`
exists and is not used by the view layer, and nothing maps
`themes/{slug}/layout.blade.php` to a view namespace the way
`ModuleServiceProvider` does for modules.

So activating `dark-commerce` does **not** change the site's appearance. It sets
`themes.is_active` and the `theme.active` setting, and that is all.

## Lifecycle

| Route | Behaviour |
|---|---|
| `GET admin.themes.index` | `syncRegistry()` then list by name |
| `POST admin.themes.{slug}.activate` | `ThemeManager::activate()` |
| `POST admin.themes.{slug}.deactivate` | `ThemeController::deactivate()` |
| `POST admin.themes.settings` | writes `theme.{key}` into the `settings` table, group `branding` |
| `GET/POST admin.themes.customize` | the customizer, below |

`activate($slug)` is a two-step flag flip plus one setting:

```php
Theme::query()->update(['is_active' => false]);
Theme::where('slug', $slug)->update(['is_active' => true]);
app(SettingService::class)->set('theme.active', $slug, 'text', 'branding');
```

**Deactivation is broken.** `ThemeController::deactivate()` calls
`$m->deactivate($slug)`, but `ThemeManager` has no `deactivate()` method. The
`try/catch` around it turns the error into a flash message rather than a 500, so
the button reports an error and the theme stays active. There is no other way to
deactivate a theme from the admin — activate a different one instead.

## The customizer

`ThemeController::CUSTOMIZER` defines three groups; values are stored in
`appearance_options` under the `customizer` group (`unique(['group','key'])`), not
in `settings`.

| Group | Fields (with defaults) |
|---|---|
| `colors` | `primary` `#1d4ed8`, `secondary` `#0f172a`, `accent` `#f59e0b`, `body_bg` `#ffffff`, `text` `#0f172a`, `muted` `#64748b`, `border` `#e5e7eb` |
| `typography` | `font_family`, `base_size` `16px`, `heading_weight` (select 400–800, default 700) |
| `layout` | `container` `1180px`, `radius` `12px`, `section_padding` `64px`, `sticky_header` (bool, default true) |

`ThemeController::cssVariables()` turns them into a `:root { … }` block that
`site/layout.blade.php` inlines in `<head>`:

```
colors.primary            ->  --lindu-primary
layout.radius             ->  --lindu-radius
typography.font_family    ->  --lindu-font_family
```

The **group prefix is dropped** — the key is split on the first `.` and only the
leaf name is used. Two details worth knowing:

- the function has an `if`/`else` whose branches are identical, so every key is
  emitted the same way; a group-specific prefix would be a code change;
- because the prefix is dropped, two groups cannot both define the same leaf name
  without the later one winning. No leaf name is currently duplicated.

The layout also hard-codes three variables from the *branding* settings
(`--lindu-primary`, `--lindu-secondary`, `--lindu-radius`) around the customizer
block, so the two systems can disagree. The [page builder](PAGE_BUILDER.md)
components reference `--lindu-primary`, `--lindu-secondary` and `--lindu-radius`,
which is why those three are declared in both places.

**The comment on `customize()` claims these values reset when a different theme
is activated. That reset is not implemented** — nothing deletes the `customizer`
group on activation.

## Appearance, which is what actually restyles the site

`Admin\AppearanceController` at `/admin/appearance/…` is the real theming
surface, and it is independent of the `themes` registry:

| Screen | Backing store |
|---|---|
| Header, Footer | `appearance_options` (header / footer groups) |
| Homepage | the page marked `is_homepage` |
| Custom code | `branding.custom_css` / `custom_js` / `custom_head` / `custom_footer` |
| Widgets | the `widgets` table (`sidebar`, `type`, `title`, `config`, `sort_order`, `is_visible`) |

`widgets` are stored and reordered from the admin; check whether the public layout
actually renders a given `sidebar` before promising a customer a widget layout.

## Related settings

`ThemeController::settings()` writes `theme.{key}` into `settings` with group
`branding`, so theme settings and brand settings share the `branding` group.
`theme.active` is written by `activate()` and seeded to `default`.

See [WHITE_LABEL.md](WHITE_LABEL.md) for the full branding surface, including
which of those settings are actually consumed by a view.

## See also

[WHITE_LABEL.md](WHITE_LABEL.md), [PLUGINS.md](PLUGINS.md), [MODULES.md](MODULES.md).
