# Form Builder

Build a form (a list of fields) in the admin; the public site renders it and
accepts submissions at a single public endpoint.

- Field model: `app/Models/FormField.php` (`TYPES`, `OPTION_TYPES`, `FILE_TYPES`)
- Render + submit: `app/Core/Services/FormRenderer.php`
- Submission endpoint: `POST /api/v1/forms/{slug}` → `Api\V1\FormSubmissionController@store`
- Admin UI: `resources/views/admin/cms/form-builder.blade.php` (and `forms.blade.php`, `form-fields.blade.php`)
- Admin routes: `admin.cms.forms.*`, `admin.cms.form-fields.*`
- Tables: `forms`, `form_fields`, `form_submissions`, `form_spam_settings`, `word_filters`
- Also embeddable as a [page builder](PAGE_BUILDER.md) block (`type: form`)

## Admin workflow

1. **CMS → Forms → + Add** creates the form. It gets a `slug`
   (`Str::slug(title)` plus a 4-character suffix when the title is used as the
   slug), which is the public identifier.
2. **Add fields** on the form's builder screen. Each field is
   `label` + `name` + `type`, plus `placeholder`, `help`, `options`,
   `is_required`, `is_unique`, `is_active`. `name` defaults to a snake-cased
   version of the label and is the key used in the stored submission payload.
3. **Reorder** with `POST admin.cms.forms.fields.reorder` — it rewrites
   `sort_order` to the submitted array index. Rendering and validation both
   order by `sort_order`.
4. A form with fields but no `slug` is unusable publicly; the endpoint is keyed
   on the slug.

`CMS → Form Fields` lists every field of every form in one table for bulk
review. `CMS → Submissions` lists and exports `form_submissions`, and
`CMS → Spam / Protection` configures the anti-spam settings below.

## Field types

`FormField::TYPES` is the single registry (18 types). `CmsController::saveField()`
validates `type` with `in:` against exactly these keys, so a type that is not in
the constant cannot be saved.

| Type | Rendered as | Extra notes |
|---|---|---|
| `text` | `<input type="text">` | default arm of the `match` |
| `textarea` | `<textarea rows="5">` | |
| `richtext` | `<textarea rows="5">` | same markup as `textarea`; no WYSIWYG |
| `email` | `<input type="email">` | validated `email\|max:190` |
| `url` | `<input type="url">` | validated `url\|max:500` |
| `number` | `<input type="number">` | validated `numeric` |
| `phone` | `<input type="tel">` | no format validation |
| `password` | `<input type="password">` | value is **not** stored: it falls into the `default` submit branch and is written to `form_submissions.data` as a string. Treat password fields as untrusted. |
| `select` | `<select>` | needs options (`OPTION_TYPES`) |
| `multiselect` | `<select multiple name="…[]">` | needs options; validated `array` |
| `radio` | one `<input type="radio">` per option | needs options; validated `string` |
| `checkbox` | one `<input type="checkbox" name="…[]">` per option | validated `array`; **not** in `OPTION_TYPES` |
| `date` | `<input type="date">` | validated `date` |
| `datetime` | `<input type="datetime-local">` | validated `date` |
| `file` | `<input type="file">` | `FILE_TYPES`; validated `file\|max:20480` (20 MB) |
| `image` | `<input type="file" accept="image/*">` | `FILE_TYPES`; validated `image\|max:10240` (10 MB) |
| `hidden` | `<input type="hidden">` | value comes from `placeholder` |
| `repeater` | `<input type="text">` | **not implemented**: it falls into the `default` arm and renders a single text input. Do not use it for real repeated data. |

Constants:

- `FormField::OPTION_TYPES = ['select', 'multiselect', 'radio']` — the types
  that are unusable without an options list (`FormField::needsOptions()`).
- `FormField::FILE_TYPES = ['file', 'image']` — the types that accept an upload.

`options` is cast to `array`; `CmsController::toList()` accepts a JSON array, a
newline-separated list, or `value|label` lines and normalises to an array.

## Rendering

`FormRenderer::render(Form $form, array $old = []): string` returns plain HTML
(no Blade), so the same string is used by the page builder's `form` block and by
any page that embeds a form.

- Only fields with `is_active = true` are rendered, ordered by `sort_order`.
- A form with no active fields returns `<p class="text-muted">This form has no
  fields yet.</p>` rather than an empty `<form>`.
- The form posts to `POST /api/v1/forms/{slug}` and includes `csrf_field()`.
- Labels, help text, placeholders and every rendered value go through `e()`.
  Exception: the `hidden` type puts `placeholder` into a value attribute
  (still `e()`-escaped).
- The optional anti-spam fields (honeypot, timestamp) are injected by the
  renderer itself — see below.
- `FormRenderer::renderField(FormField $field, $value = null): string` renders a
  single field with the exact same code path the public form uses, so the admin
  "as saved" preview cannot drift from the real output.

The rendered form has no client-side JavaScript. It is a normal
`method="POST"` form; the endpoint answers JSON or redirects with a flash
message depending on the `Accept` header.

## Anti-spam

Configured in `form_spam_settings` (singleton, editable at
`admin.cms.form-spam.save`). `FormSpamSetting::current()` returns the row.

`FormRenderer::submit()` applies these layers **in this order** — the order
matters, because the rate limiter is consulted before validation but only
*incremented* after it:

1. **Honeypot** — when `honeypot` is on, the renderer emits a `website` text
   input positioned off-screen and `tabindex="-1"`. Any non-empty `website`
   value rejects the submission with a generic message and logs
   `Form submission rejected (honeypot)`.
2. **Minimum fill time** — when `min_fill_seconds > 0`, the renderer emits a
   hidden `_t` timestamp. A submission whose elapsed time is `>= 0` and
   `< min_fill_seconds` is rejected (`too fast`). A missing `_t` skips the
   check, so it is a speed bump, not a hard gate.
3. **Per-IP rate limit** — `RateLimiter` key `form-rate:{form_id}:{ip}`,
   allowance `rate_limit_per_minute` (minimum 1). Over the limit, the response
   is a 422 with the seconds to wait. The limit is *checked* here and
   *incremented* (`RateLimiter::hit($key, 60)`, i.e. a 60-second decay) both on
   a validation failure and after a successful store — so a user who fails
   validation is throttled too.
4. **Field validation** — rules are built per field from `is_required` plus the
   type table above; an unknown type gets `|string|max:5000`. Failures return
   `['ok' => false, 'errors' => …]`, which the controller turns into HTTP 422.
5. **Blocked words** — the union of `form_spam_settings.blocked_words` and every
   active row in `word_filters`. The haystack is the lower-cased concatenation
   of all submitted values except keys starting with `_` and the `website`
   honeypot. A substring match on any word rejects the submission
   (`blocked word`).

There is a second, coarser limiter at the routing layer: the endpoint carries
`throttle:form-submit`, defined in `AppServiceProvider::rateLimiters()` as
**20 requests/minute per IP** (`Limit::perMinute(20)->by($r->ip())`).

`form_spam_settings` also has `block_disposable_email` and `captcha` columns.
Neither is read by `FormRenderer` — they are stored, not enforced. Do not
advertise them as active protections.

## Submission endpoint

```
POST /api/v1/forms/{slug}
```

Public and unauthenticated, throttled as above. Registered in `routes/api.php`
inside the `v1` group.

| Situation | Response |
|---|---|
| No form with that slug (or not published) | `404` `{"message":"Form not found.","errors":{"form":[…]}}` |
| Spam or validation rejection | `422` `{"message":…, "errors":{…}}` |
| Accepted, JSON/HTML client | `201` `{"message":…, "submission_id":…}` |
| Accepted, plain form post | `302` back with a flash `ok` |

```sh
curl -X POST https://example.test/api/v1/forms/contact-us \
     -H 'Accept: application/json' \
     -F 'name=Ada' -F 'email=ada@example.test'
```

`FormRenderer::submitUrl()` resolves the action by route name when it exists and
falls back to building `/api/v1/forms/{slug}` from the URI. The route in
`routes/api.php` currently has no per-route `->name()`, so in practice the
fallback is what runs — if you add a `->name('forms.submit')` to that route the
name lookup starts working too.

## What a stored submission looks like

`form_submissions.data` is a JSON object keyed by field `name`:

- scalar fields → string (or `null`)
- `multiselect` / `checkbox` → array of the submitted values
- `file` / `image` → the stored path on the `public` disk, under
  `form-uploads/{form_slug}/`

`ip` and `user_agent` (truncated to 500 chars) are stored alongside, and
`tenant_id` is filled from the resolved tenant.

After a successful store the engine fires, each in its own `try/catch` so one
failure cannot lose the submission:

- `NotificationService::notifyAdmins('notifications.new_submission', …)`
- `event('form.submitted', …)`
- `WebhookDispatcher::dispatchEvent('form.submitted', …)`
- `WorkflowEngine::trigger('form.submitted', ['form' => …, 'data' => …])` — see
  [WORKFLOW.md](WORKFLOW.md)

## Known gaps

Verified against the source; these are not features.

- **`is_unique` is stored but never enforced.** The column exists on
  `form_fields` and the admin form has the toggle, but `FormRenderer::submit()`
  never reads it. Uniqueness has to be handled in a workflow or a listener.
- **`validation` and `conditional` columns are inert.** Both are on the model
  with `array` casts and in the migration, but neither is read by the renderer,
  the submit path, or `CmsController::saveField()`. There is no per-field
  custom rule and no conditional visibility.
- **`repeater` is not implemented** (see the type table).
- **`block_disposable_email` and `captcha` are not enforced.**
- **The `forms` table has no `title` column.** The schema (and `Form::$fillable`)
  use `name`; parts of the admin controller and the renderer read `title`,
  `status`, `submit_label` and `success_message`, which are not columns. In
  practice the renderer falls back (`submitLabel()` prefers `submit_label`, then
  `submit_text`, then `'Submit'`) and the list/search paths that read `title` or
  filter on `status` are the ones that break. Treat `forms.name`,
  `forms.submit_text` and `forms.is_active` as the real columns until this is
  reconciled.
- **Password fields store their value.** Nothing filters `type = password` out
  of `form_submissions.data`.

See also: [PAGE_BUILDER.md](PAGE_BUILDER.md), [WORKFLOW.md](WORKFLOW.md),
[SECURITY.md](SECURITY.md).
