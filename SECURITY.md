# Security

This page lists what the code **actually enforces**, and — just as importantly —
what it does not. The second list is not a to-do of embarrassing bugs; several of
them are deliberate design decisions. Both matter when you are signing a data
processing agreement with a customer.

## Enforced

### Output escaping

Views use `{{ }}` (escaped) by default. `{!! !!}` is used deliberately in a
bounded set of places:

- `BlockLibrary` for the raw component fields (`text`/richtext, `card.text`,
  `html`) and for the `map` / `video` / `contact` embeds, which accept
  `<iframe>` markup;
- `resources/views/site/layout.blade.php` for `branding.custom_css`,
  `branding.custom_head`, `branding.custom_js` and `branding.custom_footer`;
- the SEO schema / breadcrumb helpers.

Everywhere else, values go through `e()`: menu titles, form labels, help text,
placeholders, form values, page titles, slugs and alt text.

### CSRF

The `web` middleware group is on by default and every admin form posts through
it, so `@csrf` / `csrf_field()` protect them. `FormRenderer` emits a
`csrf_field()` in every rendered public form.

The `api` group is stateless and has no CSRF token — correct for token
authentication, but it means **`POST /api/v1/webhooks/in/{key}` and
`POST /api/v1/forms/{slug}` are reachable cross-origin from a browser page.**
`shouldRenderJsonWhen()` is set for `api/*` in `bootstrap/app.php`.

### Authentication

Laravel's session auth for `/admin`, plus two-factor via `TwoFactorService`
(`/admin/security/2fa`, and the `/2fa/challenge` step in the web flow). Sanctum
personal access tokens for the API. Passwords are bcrypt-hashed by `Hash::make`.
Social login via Socialite (`/oauth/{provider}`) — enabled purely by the presence
of `GITHUB_*` / `GOOGLE_*` env vars.

Login lockout: `config('lindu.security.max_login_attempts')` (5) and
`lockout_minutes` (15) exist, and a `login` rate limiter is defined — **but no
route uses it.** See "Not enforced" below.

### Authorisation

- Middleware alias `permission:` → `App\Http\Middleware\CheckPermission`.
- `User::hasPermission($slug)`, `User::hasRole([…])`, and the `user_can($slug)`
  helper. Menus are filtered server-side by `MenuService::visible()` on
  `permission` and `roles` — hiding a menu item is not the same as authorising
  it, and the permission check is the one that matters.
- 127 seeded permissions, 4 roles.
- `Gate::before()` grants **every** ability to `super-admin` and `admin`. If you
  need `admin` to be less than all-powerful, that global bypass has to change.

### Whitelists instead of dynamic class names

The recurring security pattern in this codebase — a request value is never
concatenated into a class name or a table name:

| Boundary | Where |
|---|---|
| `{resource}` → model | `ResourceController`, `ResourceApiController` (v1 and v2) via `config('lindu_admin.php')` |
| `{resource}` → model | `CompanyProfileController::RESOURCES` |
| `{resource}` → relation | `DataBuilderController::FIELD_TYPES` / `RELATION_TYPES` |
| Workflow action → model | `WorkflowEngine::WHITELISTED_MODELS` (only `ContentRecord` and `ContentType`) |
| Form field type | `FormField::TYPES` |
| Physical table | `DataBuilderService::dropPhysicalTable()` refuses any name not starting with `cb_` |
| Physical column | `columnName()` enforces `^[a-z][a-z0-9_]*$` after `Str::snake()` |

`UpdateCenterController::authorizeUpdate()` additionally requires the slug to
exist in the registry, and for `core` requires the literal slug `lindu` plus
`settings.manage`.

### Input validation and mass assignment

Validation is in the controllers (`$r->validate([...])`), with the rules
co-located with the field definitions for the data builder and the company
profile. Writes are filtered through each model's `$fillable`; where a model
declares none, `ResourceApiController::safe()` intersects the request with the
real column list from the schema.

### File uploads

Size is enforced in two places: `$r->validate(['files.*' => 'required|file|max:…'])
in `MediaController` / `MediaApiController` (using `media.max_upload_mb`, default
10) and a second size check in `MediaService::store()`. Career CVs are validated
`mimes:pdf,doc,docx|max:4096`. Form `file` fields are capped at 20 MB and
`image` fields at 10 MB with an `image` rule.

**MIME types are recorded, not enforced.** `config('lindu.media.allowed_mimes')`
lists 15 extensions, but the only reader of that key is
`MediaController::storage()` (line 232), which passes the list to a view for
display. `MediaService::store()` stores whatever it is given, keeps
`$file->getMimeType()` in the `media_files.mime` column, and never compares it
to an allowlist. `File::store()` hashes the name, which prevents path traversal
and overwriting, but **not** the upload of an arbitrary file type.

If you serve `public/storage/media` from the same origin, add a real
`mimes:`/`mimetypes:` rule to the upload validation. Treat the `allowed_mimes`
config key as documentation of intent, not as a control.

### Secrets in settings

`SettingService::set()` encrypts a value whose type is `secret` with
`Crypt::encryptString()`; `get()` decrypts it and returns the caller's default if
decryption fails. The *admin* never writes `secret` type — it writes `text` — so
a secret only stays encrypted if you write it yourself with `$type = 'secret'`.

`BackupService::envExample()` strips `APP_KEY`, `DB_PASSWORD` and anything
matching `*_SECRET`, `*_KEY` or `*_TOKEN` from the `.env.example` embedded in a
backup archive, so a backup does not carry a live `.env`.

### Outbound webhooks

`WebhookDispatcher::sign()` produces `t=<unix>,v1=<hmac_sha256(timestamp.body,
secret)>`, so a receiver can reject a replay with a stale timestamp.
`WebhookDispatcher::verify()` implements the matching check with a 300-second
tolerance. Every delivery is recorded in `webhook_logs` with attempts, response
body (truncated to 4000 chars), status code and `next_retry_at`, and
`processRetries()` abandons a delivery after
`setting('webhooks.max_attempts', 3)` attempts.

`WorkflowEngine::sendWebhook()` refuses any URL whose scheme is not `http` or
`https` — that is what stops a workflow from reading `file://` or reaching an
internal service.

### Anti-spam

See [FORM_BUILDER.md](FORM_BUILDER.md) for the full layer list. The one that is
actually enforced end to end is the route-level throttle:
`POST /api/v1/forms/{slug}` carries `throttle:form-submit`, 20 requests/minute
per IP, plus a second per-form, per-IP limiter inside `FormRenderer::submit()`.
The contact form has its own `website` honeypot validated as `nullable|max:0`.

### Tenant and pairing gates

`RequirePair` is appended **first** in the `web` group, ahead of
`ResolveTenant`, so an unpaired domain never reaches tenant resolution. It
redirects to `/__pair` unless the `.license.lock` verifies for the current host.
The lock is AES-256-GCM with a key derived from `APP_KEY` **and the host**, so
copying it to another domain does not decrypt; the payload signature is verified
against the marketplace RSA public key. See [LICENSE.md](LICENSE.md).

## Not enforced — read this before you sell it

These are accurate statements about the current code, not aspirations.

1. **Rate limiting is defined but almost entirely unused.**
   `AppServiceProvider::rateLimiters()` registers `api` (60/min),
   `webhook-in` (120/min) and `login` (per `email|ip`). The **only** `throttle:`
   middleware in `routes/` is `throttle:form-submit`. The REST API and the login
   endpoint are not throttled by the framework. Add
   `->middleware('throttle:api')` and `'throttle:login'` before exposing the
   install publicly — brute-forcing `/api/v1/auth/login` and `/login` is
   currently unthrottled.

2. **The inbound webhook endpoint verifies no signature.**
   `POST /api/v1/webhooks/in/{key}` looks the hook up by `where('secret', $key)`.
   The secret *is* the URL, and `WebhookDispatcher::verify()` is never called on
   the inbound path, even though it exists. Anything that knows the key can post.
   Put it behind a proxy or an IP allowlist.

3. **`Api\V2\ResourceApiController` does not intersect writes with `$fillable`.**
   v1's `safe()` does; v2 passes `$r->all()` straight to `Model::create()` /
   `update()`. Eloquent's `$fillable` still applies, so every current model is
   protected — but a resource whose model uses `$guarded = []` would be fully
   writable. Prefer v1, or keep `$fillable` on every model.

4. **`/api/v1/auth/register` is open and creates an active user.**
   No setting disables it, no role is assigned, no token is issued, and the
   account is created with `is_active = true` and `status = 'active'`. Gate it
   (`bootstrap/app.php` route change, or a middleware) on any public install.

5. **`ModuleController::action()` calls an unvalidated method name.**
   `POST /admin/modules/{slug}/{action}` does `$m->$action($slug)` with `$action`
   straight from the URL, with no allowlist. Behind `auth`, so not anonymous, but
   any method of `ModuleManager` that takes one string is callable.
   `PluginController::action()` does the check correctly — copy that.

6. **Tenant scoping is largely absent.** Only `MenuService` and the `cb_*` mirror
   filter by `tenant_id`. Pages, posts, media, settings, content records and the
   `cp_*` tables do not. `TenantService::resolve()` also applies its `status`
   filter outside the `orWhere` and compares the first host label to `subdomain`
   for any TLD. See [SAAS.md](SAAS.md#scoping-caveats--read-before-a-multi-tenant-sale).

7. **The custom code white-label fields are raw script injection.** Anyone with
   `admin.whitelabel.save` can put arbitrary JavaScript on every public page.
   That is the point of the feature, but it means "edit white label" is a
   root-equivalent privilege. Split it if you sell roles separately.

8. **The page builder's `html` block is unsanitised** by design. Anyone who can
   edit a page can inject script into the public site. See
   [PAGE_BUILDER.md](PAGE_BUILDER.md#known-gaps).

9. **Stored XSS surface in a few admin fields.** `map_embed`,
   `branding.custom_*` and the page builder's embed fields accept `<iframe>`
   markup and are rendered with `{!! !!}`. They are operator-only fields, but
   they are operator-only *by route*, not by sanitiser.

10. **`Api\V2\AuthApiController` mentions `X-2FA-Code` and does not read it.**
    A 2FA user gets `423` with a message telling them to send a header that is
    never inspected, so v2 login cannot be completed for them.

11. **Form password fields store their value.** A `type = password` field is
    written into `form_submissions.data` as a string. Do not put a password in a
    builder form expecting it to be discarded.

12. **The payment secret is stored unencrypted.**
    `SaasController::saveGateways()` writes `billing.gateways` as plain `json`,
    not `secret`, so the gateway secret sits in clear text in `settings`.

13. **The license key is stored in clear text.**
    `LicenseService::install()` writes `storage/app/lindu/license.json` with the
    key in plain text, despite a comment claiming encryption. Use License v3 if
    the key must be protected at rest.

14. **`Settings → Advanced → Allow raw SQL in the data builder`** exists as
    `advanced.raw_sql` (default off, help text: *"Only enable if you fully trust
    every user with admin access"*). It is a settings row; no code reads it, so
    there is no raw SQL path in core today. Do not enable it expecting a feature.

## Operational checklist

- [ ] `APP_DEBUG=false` and `APP_ENV=production` on the live host
- [ ] `php artisan down` before `storage:link`, `migrate` and permission changes;
      `php artisan up` after ([TROUBLESHOOTING.md](TROUBLESHOOTING.md))
- [ ] `chown -R www-data:www-data storage bootstrap/cache && chmod -R 775 …`
- [ ] `chmod 600 storage/app/.license.lock` (the client sets this on write)
- [ ] Web server docroot is `public/`, never the project root
- [ ] HTTPS, and `URL::forceScheme('https')` is already applied automatically in
      the `production` environment by `AppServiceProvider`
- [ ] Delete or rotate `admin@lindu.local` and the other seeded accounts
- [ ] Add a `mimes:` / `mimetypes:` rule to the media upload validation (see
      "File uploads" above) — `allowed_mimes` is not enforced today
- [ ] Add `->middleware('throttle:api')` and `'throttle:login'` to the API and
      login routes
- [ ] A queue worker is running (`php artisan queue:work`) and the scheduler is
      cron'd — otherwise webhooks never retry
- [ ] `Gate::before()` super-admin/admin bypass is acceptable for your role model

## Reporting a vulnerability

This is a commercial product. There is no public security contact in
`composer.json`; use the private channel agreed with the customer rather than a
public issue.
