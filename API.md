# API

Two REST surfaces, `v1` and `v2`, plus a public form endpoint and an inbound
webhook receiver. Authentication is Laravel Sanctum personal access tokens.

- Routes: `routes/api.php` (registered by `bootstrap/app.php` as the `api` group)
- Controllers: `app/Http/Controllers/Api/V1/`, `app/Http/Controllers/Api/V2/`
- OpenAPI document: `public/docs/openapi.json`, served at `GET /api/docs/openapi.json`
  (with the `web` middleware, so it is reachable without a token) and browsable at
  `GET /docs` (`view('docs.index')`)

## Response envelope

`Api\V1\ApiController`:

```php
data($data,  $meta = [])        // {"data": …, "meta": {}}
paginated($p)                   // {"data": [...items], "meta": {current_page, total, per_page}}
error($msg, $code = 422, $e=[]) // {"message": …, "errors": {…}}
```

`Api\V2\ApiController` is the same, plus `meta.version = "v2"` on every response —
including errors — and an extra `sparse()` helper.

## Authentication

### v1

```
POST /api/v1/auth/login      {email, password} -> {"data": {"token": …, "user": …}}
POST /api/v1/auth/register   {name, email, password}   (no token issued)
GET  /api/v1/auth/me
POST /api/v1/auth/logout
```

`login` issues a Sanctum token named `api` with the `['*']` ability, returns
`401 Invalid credentials` for a bad email/password and `403 Account inactive`
when `User::isActive()` is false. `register` creates an **active** user with no
role and returns the model — it does not log the caller in and it is not
disabled by any setting. `logout` deletes only the current access token.

Send the token as a bearer credential:

```sh
curl https://example.test/api/v1/posts \
     -H 'Accept: application/json' \
     -H 'Authorization: Bearer YOUR_TOKEN'
```

### v2

```
POST /api/v1… /api/v2/auth/login   -> also returns "token_type": "Bearer"
GET  /api/v2/auth/me               ?fields=name,email
POST /api/v2/auth/logout
```

Differences from v1: the token is named `api-v2`, the response carries
`token_type`, `?fields=` sparsely selects attributes via `sparse()`, and **2FA is
checked** — a user with `two_factor_enabled` gets
`423 2FA required — use web login or send X-2FA-Code`. That `X-2FA-Code` header
is mentioned in the message but **is not read anywhere in the code**; v2 login
cannot currently be completed for a 2FA user.

`POST /api/v2/auth/register` does not exist.

## v1 routes

### Public (no token)

| Method | URI | Notes |
|---|---|---|
| POST | `/api/v1/auth/login` | |
| POST | `/api/v1/auth/register` | |
| POST | `/api/v1/forms/{slug}` | form submission, `throttle:form-submit` — see [FORM_BUILDER.md](FORM_BUILDER.md) |
| POST | `/api/v1/webhooks/in/{key}` | inbound webhook — see below |

### Authenticated (`auth:sanctum`)

| Method | URI | Controller |
|---|---|---|
| GET/POST | `/api/v1/auth/…` | `AuthApiController` |
| GET | `/pages`, `/pages/{slug}` | `PageApiController` (includes the raw `builder` JSON) |
| GET | `/posts`, `/posts/{slug}` | `PostApiController` |
| GET | `/categories`, `/tags` | `TaxonomyApiController` |
| GET | `/search` | `SearchApiController` |
| GET/POST | `/media`, `GET/DELETE /media/{id}` | `MediaApiController` |
| GET | `/content-types` | `ContentTypeApiController` |
| GET | `/content-types/{slug}` | 404 unless `is_api_enabled` |
| GET/POST | `/content-types/{slug}/records` | |
| GET/PUT/DELETE | `/content-types/{slug}/records/{id}` | |
| GET/POST | `/{resource}` | `ResourceApiController` — `where('resource','[a-z-]+')` |
| GET/PUT/DELETE | `/{resource}/{id}` | |

The named routes are registered **before** the `/{resource}` catch-all so they
are not shadowed by it.

### Generic resources

`ResourceApiController` resolves `{resource}` **only** through
`config('lindu_admin.php')` → `resources`, which maps 28 slugs to hard-coded model
classes: `pages`, `posts`, `categories`, `tags`, `users`, `products`, `orders`,
`customers`, `leads`, `deals`, `courses`, `properties`, `reservations`, `tasks`,
`vendors`, `members`, `coupons`, `payments`, `forms`, `workflows`, `webhooks`,
`tenants`, `licenses`, `contacts`, `companies`, `rooms`, `lessons`, `sales`.

An unknown slug is a 404. **The slug is never used to build a class name.**

Query parameters on `GET /{resource}`:

| Parameter | Behaviour |
|---|---|
| `search` | `LIKE %…%` OR-ed across the resource's configured `search` columns |
| `filter[k]=v` | equality, one clause per key. An unknown column returns `422 Invalid filter: k`. |
| `sort` + `dir` | `orderBy(sort, dir === 'asc' ? 'asc' : 'desc')`. A bad column is swallowed and the model's default order is used. |
| `per_page` | `min(100, max(1, per_page))`, default 15 |

Writes are filtered through the model's `$fillable`:

```php
protected function safe(Request $r, $model): array
{
    $allowed = $model->getFillable();
    $input = $r->except(['_token', 'api_token']);

    if ($allowed === ['*'] || $allowed === []) {
        return array_intersect_key($input, array_flip(Schema::getColumnListing($model->getTable())));
    }
    return array_intersect_key($input, array_flip($allowed));
}
```

When a model declares no fillable list, the request body is intersected with the
**real column list** from the schema rather than trusted. Every v1 write also
dispatches a webhook: `{resource}.created`, `.updated`, `.deleted`.

## v2 routes

`/api/v2` is a thin variant over the same `config('lindu_admin.php')` map:

```
POST /api/v2/auth/login
GET  /api/v2/auth/me
POST /api/v2/auth/logout
GET/POST         /api/v2/{resource}
GET/PUT/DELETE   /api/v2/{resource}/{id}
```

Every response carries `meta.version = "v2"`; a paginated list also carries
`meta.resource`.

v2 adds three query parameters:

| Parameter | Behaviour |
|---|---|
| `fields` | comma-separated attribute allowlist, applied through `sparse()`. Unknown names are ignored. |
| `include` | comma-separated relations for eager loading, each in a `try/catch` |
| `filter` | same shape as v1, but a bad column is **silently ignored** rather than returning 422 |

**v2 has one material difference from v1: `store()` and `update()` pass
`$r->all()` straight to `Model::create()` / `$row->update()`** instead of
intersecting with `$fillable` first. Models in this project declare an explicit
`$fillable`, so Eloquent still blocks the dangerous keys — but if you add a
resource whose model uses `$guarded = []`, v2 will accept any column. Prefer
v1, or tighten the model.

`/api/v2` has **no** pages / posts / media / search / content-types routes and
**no** public form or inbound-webhook endpoint. A v2 client that needs content
must use the v1 named routes with a v2 token (Sanctum tokens are not
version-scoped).

## Inbound webhooks

```
POST /api/v1/webhooks/in/{key}
```

`WebhookApiController::incoming()` looks the webhook up by
`where('secret', $key)->where('is_active', true)`. An unknown key is
`404 Unknown webhook`.

**It does not verify a signature.** There is no HMAC check on the inbound path
even though `WebhookDispatcher::verify()` exists and implements the same
`t=<ts>,v1=<hmac>` scheme used for outbound deliveries. The secret *is* the
URL. Treat this endpoint as unauthenticated: put it behind a proxy, an IP
allowlist, or add the `verify()` call.

The payload is passed to `WorkflowEngine::trigger('webhook.received', …)` and
`{"ok": true}` is returned.

The admin side (incoming webhook records, secret rotation, delivery logs) is at
`/admin/cms/webhooks` — see [ARCHITECTURE.md](ARCHITECTURE.md) for the outbound
dispatcher.

## Rate limiting

`AppServiceProvider::rateLimiters()` defines four limiters:

| Name | Limit |
|---|---|
| `api` | `setting('api.rate_limit', 60)` per minute, keyed by user id or IP |
| `form-submit` | 20 per minute per IP |
| `webhook-in` | 120 per minute per IP |
| `login` | `max(3, security.max_login_attempts)` per minute, keyed by `email|ip` |

**Only `form-submit` is actually applied** — it is the only `throttle:` middleware
in `routes/`. The `api`, `webhook-in` and `login` limiters are registered but no
route references them, so the API and the login endpoint are currently
unthrottled at the framework level. Add `->middleware('throttle:api')` /
`'throttle:login'` to the relevant routes before exposing the install publicly.
This is called out again in [SECURITY.md](SECURITY.md).

## `GET /api/docs/openapi.json`

Served with the `web` middleware, so it is public. It is a static file at
`public/docs/openapi.json` — it is not generated from the controllers, so it
can drift. The three v2 behaviours (`fields`, `include`, the unfiltered
`store`/`update`) and the missing `X-2FA-Code` handling are worth checking
against the document before you publish it.

## Adding a resource

Add one entry to `config/lindu_admin.php`:

```php
'resources' => [
    'invoices' => [
        'model'  => App\Models\Invoice::class,   // must exist
        'label'  => 'Invoices',
        'search' => ['number', 'customer'],
    ],
],
```

That single entry gives you, with no new code:

- `GET/POST /api/v1/invoices`, `GET/PUT/DELETE /api/v1/invoices/{id}`
- the same five routes under `/api/v2`
- admin CRUD at `/admin/r/invoices` (list, create, edit, bulk, CSV export) via
  `Admin\ResourceController`

The slug must match `[a-z-]+` to match the route constraint, and the model's
`$fillable` becomes the API write surface.

## See also

[DATA_BUILDER.md](DATA_BUILDER.md) (the content-type API),
[FORM_BUILDER.md](FORM_BUILDER.md), [SECURITY.md](SECURITY.md),
[ARCHITECTURE.md](ARCHITECTURE.md).
