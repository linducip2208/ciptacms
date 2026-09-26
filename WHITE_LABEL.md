# White Label

Rebrand the product for a customer: name, logo, colours, login screen, email
identity, custom code, error pages and custom domains. Everything the operator
can change is a row in `settings`, read through the `setting()` helper. No view
hard-codes the vendor name.

- Admin controller: `app/Http/Controllers/Admin/WhiteLabelController.php` (`SECTIONS`)
- Views: `resources/views/admin/whitelabel/index.blade.php`, `domain-form.blade.php`
- Admin routes: `admin.whitelabel.*` (`/admin/white-label…`)
- Table: `white_label_domains` (+ the `settings` table for everything else)
- The `setting()` helper: `app/Core/Support/helpers.php`

## The `setting()` helper

```php
function setting(string $key, $default = null) {
    try { return app(SettingService::class)->get($key, $default); }
    catch (\Throwable $e) { return config('lindu.'.$key, $default) ?? $default; }
}
```

Every brand string is read through it, so an operator edit is a database write,
not a deployment. `SettingService` caches the whole `settings` table under
`lindu.settings.all` for 300 seconds and drops the cache on every `set()`, so a
saved value is visible almost immediately on the public site.

## The six sections

`WhiteLabelController::SECTIONS` is a `section => [label, description, fields]`
map, where each field is `[setting_key, label, input_type, help]`. Adding a
branding option means adding one array entry; the form, the save handler and the
audit trail all read from the same definition.

Routes: `GET /admin/white-label/{section}` and
`POST /admin/white-label/{section}`. An unknown section 404s.

### `branding` — Site identity

| Key | Input | Consumed by |
|---|---|---|
| `branding.logo` | image | `site/layout.blade.php` header |
| `branding.favicon` | image | `site/layout.blade.php` `<link rel="icon">` |
| `branding.og_image` | image | stored; **not** referenced by the site layout |
| `general.site_name` | text | `<title>`, header, footer, login page, admin navbar, the `<h1>` on `/admin` |
| `general.tagline` | text | stored; **not** referenced by the site layout |
| `general.footer` | text | footer line |
| `branding.primary_color` | color | `--lindu-primary` CSS custom property, and `meta[name=theme-color]` |
| `branding.secondary_color` | color | `--lindu-secondary` |
| `branding.radius` | text | `--lindu-radius` |

### `login` — Sign-in screen

| Key | Input | Consumed by |
|---|---|---|
| `branding.login_logo` | image | **not consumed** — the login view uses a hard-coded `L` badge |
| `branding.login_background` | image | **not consumed** |
| `branding.admin_title` | text | **not consumed** — the admin `<title>` appends `setting('general.site_name')` |

The login screen (`resources/views/auth/login.blade.php`) reads exactly one
branding value, `general.site_name`, and renders a letter badge instead of a
logo. If you need a custom logo on the sign-in page, change the view; the
settings and their plumbing are already there.

### `email` — Sender identity

| Key | Input |
|---|---|
| `branding.email_from_name` | text |
| `branding.email_from_address` | text |
| `email.from_name` | text |
| `email.from_address` | text |

**These four are stored but nothing reads them.** Outgoing mail is configured
through `config/mail.php` and the `MAIL_*` environment variables, not through
the settings table. Rebranding the sender means editing `.env` and the mail
config, not this screen.

### `code` — Custom CSS / JS

| Key | Injected where |
|---|---|
| `branding.custom_css` | `<style>` in `<head>`, via `site/layout.blade.php` |
| `branding.custom_head` | raw, in `<head>` |
| `branding.custom_js` | raw, immediately before `</body>` |
| `branding.custom_footer` | raw, immediately before `</body>` |

All four are output **unfiltered** (`{!! !!}`). This is a deliberate operator
power — it is also arbitrary script execution on the public site for anyone who
can reach `admin.whitelabel.save`. Gate the section accordingly.

### `errors` — Error pages

| Key |
|---|
| `branding.error_404_title`, `branding.error_404_body` |
| `branding.error_500_title`, `branding.error_500_body` |

**Stored but not consumed.** There is no `resources/views/errors/` directory and
nothing in the exception handler (`bootstrap/app.php` only sets
`shouldRenderJsonWhen`) reads these keys. To brand your 404/500 pages, add
`resources/views/errors/404.blade.php` and `500.blade.php` that read
`setting('branding.error_404_title')` etc.

### `advanced` — Vendor credit

| Key | Consumed by |
|---|---|
| `branding.footer_branding` | **not consumed** |
| `branding.hide_powered_by` | **not consumed** — nothing reads it |
| `branding.support_url` | **not consumed** |
| `branding.docs_url` | **not consumed** |

The footer in `site/layout.blade.php` is driven entirely by `general.site_name`
and `general.footer`. To hide a vendor credit you would have to add the markup
yourself.

The `hide_powered_by` help text says *"Leave empty to hide all vendor branding"*
for the footer credit — that behaviour is not implemented. **Before you promise
a customer a fully unbranded product, check this yourself.**

## Custom domains

`white_label_domains` (`Admin\WhiteLabelController`): `tenant_id`, `domain`
(unique), `is_primary`, `is_verified`.

| Route | Behaviour |
|---|---|
| `GET admin.whitelabel.domains.create` | add form |
| `POST admin.whitelabel.domains.store` | validated `required\|string\|max:190\|unique:white_label_domains,domain`, then normalised: lower-cased, `http(s)://` stripped, path stripped, leading `www.` removed |
| `DELETE admin.whitelabel.domains.destroy` | delete |
| `POST admin.whitelabel.domains.{domain}.primary` | clears `is_primary` on every row, then sets this one |

The first domain added is automatically primary. **`is_verified` is never set by
the white-label screens** — it is only written to `false` when
`SaasController::storeTenant()` creates a tenant's domain. There is no DNS
ownership check anywhere in the codebase: adding a domain here records an
assertion, it does not prove control. `ResolveTenant` resolves tenants from the
`tenants.domain` / `tenants.subdomain` columns via
`TenantService::resolve()`, **not** from `white_label_domains` — so a
white-label domain row does not by itself route a request to a tenant.

Domains are listed on both the white-label screen and `admin.saas.domains`.

## Theme customizer CSS variables

Separate from white label, but it is the other half of "rebrand without
deploying": `ThemeController::CUSTOMIZER` stores values in
`appearance_options` under the `customizer` group, and
`ThemeController::cssVariables()` emits them as a `:root { … }` block that
`site/layout.blade.php` inlines in `<head>`.

| Group | Keys |
|---|---|
| `colors` | `primary`, `secondary`, `accent`, `body_bg`, `text`, `muted`, `border` |
| `typography` | `font_family`, `base_size`, `heading_weight` |
| `layout` | `container`, `radius`, `section_padding`, `sticky_header` |

Each stored key is `group.name`; `cssVariables()` splits on the first `.` and
emits `--lindu-{name}: {value};`, **dropping the group prefix**. So
`colors.primary` → `--lindu-primary`, `layout.radius` → `--lindu-radius`,
`typography.font_family` → `--lindu-font_family`.

Two consequences worth knowing:

- The current implementation has an `if`/`else` whose two branches are
  identical, so every key is emitted the same way. Adding a group-specific
  prefix is a code change.
- Because the group is dropped, two groups cannot both define a key with the
  same leaf name without the later one winning. Today no leaf name is
  duplicated across the three groups, so nothing collides.

The customizer values live in `appearance_options` (unique on
`(group, key)`) rather than `settings`, so they are independent of the branding
settings above. The comment in `ThemeController::customize()` says they reset
when a different theme is activated — **that reset is not implemented**; nothing
deletes the `customizer` group on activation. Clear those rows yourself if you
need per-theme values.

## Secrets and caching

`SettingService::set()` encrypts values stored with `type = 'secret'` via
`Crypt::encryptString()`, and `get()` decrypts them, returning the supplied
default if decryption fails. The white-label screen writes every field as
`text` (or `boolean` for the switches) — it never writes `secret`. `json` and
`file` / `image` types are decoded on read.

## Re-branding checklist

1. **Appearance → Themes → Customize** — set the `colors` and `typography`
   groups. These drive the CSS custom properties.
2. **Settings → Branding** — set `general.site_name`, `general.footer`,
   `branding.logo`, `branding.favicon`, `branding.primary_color`,
   `branding.secondary_color`, `branding.radius`.
3. **White label → Code** — any extra CSS/JS. Remember it is raw.
4. **White label → Domains** — record the customer's domains and pick the
   primary. Set `tenants.domain` yourself if the domain must resolve a tenant.
5. **Verify manually** — the login screen, the 404 page, the 500 page and the
   email `From:` header are *not* driven by these settings yet.

See also: [THEMES.md](THEMES.md), [SAAS.md](SAAS.md), [COMPANY_PROFILE.md](COMPANY_PROFILE.md).
