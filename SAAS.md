# SaaS

Multi-tenant readiness: tenants, custom domains, plans, per-plan feature flags,
usage quotas and a payment-adapter seam. It is a **hosting control plane**, not a
billing application.

- Tenants: `app/Core/Services/TenantService.php`
- Feature flags and quotas: `app/Core/Services/FeatureFlag.php`
- Payments: `app/Core/Contracts/PaymentGateway.php`, `app/Core/Services/Payments/`, `PaymentManager`
- Admin UI: `app/Http/Controllers/Admin/SaasController.php` at `/admin/saas/*`
  and `/admin/gateways`
- Middleware: `ResolveTenant` (alias `tenant:`)
- Tables: `tenants`, `tenant_domains`, `tenant_user`, `tenant_usages`, `plans`,
  `plan_features`, `subscriptions`, `white_label_domains`
- Config: `LINDU_TENANT_MODE` → `config('lindu.tenant_mode')`

## Enabling multi-tenancy

`.env.example` ships `LINDU_TENANT_MODE=multi`.

**Nothing branches on `lindu.tenant_mode`.** It is a config value with no reader
outside `config/lindu.php` itself. `ResolveTenant` is appended to the `web` and
`api` groups unconditionally and resolves a tenant on **every** request. Single
vs multi is therefore a deployment decision — do not create a second tenant on a
single-tenant install and assume it is inert, and do not set `multi` without
reading the scoping caveats below.

## Tenants

`tenants`: `id` is a **string primary key holding a UUID**, plus a separate
nullable unique `uuid`, `name`, unique `slug`, unique `domain`, unique
`subdomain`, `status` (`active` | `suspended` | `trial`), nullable `plan_id`,
JSON `settings`, JSON `quotas`, `trial_ends_at`, `expires_at`, timestamps.

`SaasController::storeTenant()` generates `Str::uuid()` into both `id` and `uuid`,
slugs the name with a 4-character suffix, sets `status = 'active'`, and — when a
domain is supplied — creates a matching `white_label_domains` row with
`is_primary = true, is_verified = false`.

`tenant_domains` exists alongside `tenants.domain` but is **not** what the
middleware reads. See [WHITE_LABEL.md](WHITE_LABEL.md#custom-domains).

### Resolution

```php
Tenant::where('domain', $host)
      ->orWhere('subdomain', explode('.', $host)[0])
      ->where('status', 'active')
      ->first();
```

`ResolveTenant` binds the result as the `tenant` container instance;
`tenant()` and `tenant_id()` read it. `BelongsToTenant` fills `tenant_id` on the
models that use it. `MenuService` scopes its tree to
`tenant_id IS NULL OR tenant_id = {current}`.

### Scoping caveats — read before a multi-tenant sale

Two real problems in that one query:

1. **The `status` filter is not grouped with the `orWhere`.** SQL precedence
   means a host matching `domain` or `subdomain` is accepted **regardless of
   status**, so a `suspended` tenant still resolves.
2. **The first host label is compared to `subdomain` for any TLD.** On
   `anything.test` the label is `anything`, which matches a tenant whose
   subdomain is `anything`. There is no allowlist of suffixes.

And beyond the resolver: only `MenuService` and the `cb_*` mirror actually filter
by `tenant_id`. Most core queries — `pages`, `posts`, `media_files`, `settings`,
`content_records`, the `cp_*` tables — have no tenant predicate at all. **A
multi-tenant install today shares its content across tenants.** Fixing that is
work, not configuration.

## Plans, features and quotas

`plans`: `name`, `slug`, `price`, `billing_period` (`monthly` | `yearly` |
`one-time`), JSON `limits`, `sort_order`, `is_active`.

`plan_features`: `plan_id`, `key`, `is_enabled`, JSON `limits`,
`unique(['plan_id','key'])`.

`tenant_usages`: `tenant_id`, `metric`, `period` (`Y-m`), `used`, `limit`,
`unique(['tenant_id','metric','period'])`.

`FeatureFlag` is a static helper:

```php
FeatureFlag::enabled(string $key, $tenant = null): bool
FeatureFlag::limit(string $key,  $tenant = null): ?int
FeatureFlag::used(string $metric, $tenant = null): int
FeatureFlag::exhausted(string $metric, $tenant = null): bool
FeatureFlag::track(string $metric, int $delta = 1, $tenant = null): void
```

- `enabled()` — with a tenant that has a `plan_id`, it reads that plan's
  `plan_features` row and returns its `is_enabled`. With no such row it falls
  back to `PlanFeature::where('key', …)->exists()` — i.e. **a feature listed on
  *any* plan counts as available.** If the table is missing it returns `true`
  rather than locking the operator out of their own site.
- `limit()` — reads `limits['limit'] ?? limits['max']` from the tenant's plan
  row, `null` when there is no tenant, no plan, or no value.
- `track()` — upserts `tenant_usages` for the current `Y-m` period, using the
  string `'default'` as the tenant key when there is no tenant.
- `exhausted()` — `limit !== null && limit > 0 && used >= limit`.

**Nothing in core calls `FeatureFlag`.** It is a helper you wire into your own
module code. It is also independent of licensing — see
[LICENSE.md](LICENSE.md#3-what-a-customer-actually-gets).

`TenantService::checkQuota($tenant, $key, $increment)` is a second, separate
implementation that reads the tenant's JSON `quotas` column
(`{key}_limit`, `{key}_used`) rather than `tenant_usages`. Two quota systems
exist; pick one.

## Subscriptions

`subscriptions`: `tenant_id`, `plan_id`, `status` (default `trial`),
`trial_ends_at`, `current_period_start`, `current_period_end`, `cancelled_at`.

`/admin/saas/subscriptions` lists them. **There is no code that creates, renews,
prorates, cancels or dunning-chases a subscription.** `SaasController` has no
method that writes to the table. It is a read-only list over a table you would
have to populate yourself.

## Payments — a seam, not an integration

`App\Core\Contracts\PaymentGateway` declares four methods: `name()`, `charge()`,
`verifyCallback()`, `parseCallback()`.

`HostedGateway` (abstract) implements the shared behaviour: read the secret from
`setting('billing.gateways')`, `POST` the built body with a bearer
`Authorization` header and a 20-second timeout, and map the response through
`interpret()`. A default `callbackSignature()` computes
`hash_hmac('sha256', $rawBody, $secret)`; a subclass can override it.

| Adapter | Endpoint |
|---|---|
| `XenditGateway` | `POST https://api.xendit.co/v2/invoices`. `callbackSignature()` returns the secret verbatim, i.e. **no signature check**. `mode()` has no effect — both branches return the same host |
| `IpaymuGateway` | `POST {sandbox.}ipaymu.com/v2/checkout` — sandbox and live hosts are selected by `mode()` |
| `TripayGateway` | `POST tripay.co.id/api/{sandbox/}transaction/init` |
| `StripeGateway` | `POST https://api.stripe.com/v1/checkout/sessions`; `success_url` / `cancel_url` fall back to `callback_url` and then to `https://example.com/…` |
| `GenericGateway` | a configurable `endpoint` from `billing.gateways`, pass-through body; refuses to run without both `endpoint` and `secret_key` |

`PaymentManager` resolves one by key from `config('lindu.payments.adapters')`
and returns `null` for an unknown key, a missing class, or a class that does not
implement the interface.

**What is missing, explicitly:**

- no checkout page, no hosted redirect, no return/callback routes;
- no invoice reconciliation, no payment → subscription link;
- no proration, no trial conversion, no dunning, no failed-payment handling;
- `XenditGateway` verifies no callback signature, so a Xendit webhook is
  unauthenticated;
- `/admin/gateways` only writes `billing.gateways` into `settings`.

**Do not describe this as billing in a customer proposal.** It is an interface
plus five adapters and a resolver. Turning it into billing is module work.

## Admin surface

| Route | Purpose |
|---|---|
| `GET/POST/PUT admin.saas.tenants` | list / create / update (`status`: `active`, `suspended`, `trial`) |
| `GET admin.saas.tenants` | tenants with their plan, plus active plans for the form |
| `GET/POST admin.saas.plans` | list / create |
| `GET admin.saas.features` | plans × `plan_features`, grouped by plan |
| `GET admin.saas.subscriptions` | read-only list |
| `GET admin.saas.usage` | `tenant_usages` grouped by tenant, for the current `Y-m` |
| `GET admin.saas.domains` | `white_label_domains` |
| `GET/POST admin.gateways` | `billing.gateways` in `settings` (group `billing`) |

`/admin/tenants` and `/admin/plans` are legacy redirects to the `/admin/saas/…`
paths.

`saveGateways()` validates `gateway` against
`array_keys(config('lindu.payments.adapters'))`, stores
`{enabled, secret_key, mode}` per gateway as JSON, and clears the whole map when
no gateway is chosen. The secret is stored **unencrypted** in the `settings`
table — `saveGateways()` writes plain `json`, not `secret`, so
`SettingService`'s `Crypt` path is bypassed. Move it to a `secret`-type setting
before you handle a real key.

## See also

[LICENSE.md](LICENSE.md), [WHITE_LABEL.md](WHITE_LABEL.md), [DATABASE.md](DATABASE.md),
[API.md](API.md).
