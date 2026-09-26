# License

Lindu CMS is a **proprietary, commercial product** (`composer.json`:
`"license": "proprietary"`). This file documents both licensing mechanisms that
ship in the codebase. They are independent: the first is the built-in license
manager, the second is the License v3 pairing gate.

| | Built-in license manager | License v3 pairing |
|---|---|---|
| Code | `App\Core\Services\LicenseService` | `App\Services\LicenseClient` + `RequirePair` |
| Data | `licenses`, `license_activations` tables | `storage/app/.license.lock` |
| Network | none unless a provider is configured | marketplace at `LICENSE_SERVER_URL` |
| Admin UI | `/admin/license` | none — a wizard at `/__pair` |
| Enforced by | `LicenseService::hasFeature()` | `RequirePair` middleware |

---

## 1. Built-in license manager

### Tables

`licenses`: `license_key` (unique), `product`, `customer`, `domain`, `status`,
`features` (JSON), `max_activations` (default 1), `expires_at`,
`support_expires_at`, `version_entitlement`, `last_checked_at`.

`license_activations`: `license_id`, `domain`, `ip`, `activated_at`.

`LicenseService::STATUSES`:

```php
['active', 'inactive', 'expired', 'suspended', 'banned']
```

### Keys

```php
LicenseService::generateKey(): 'LND-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4))
```

### Issuing and activating

`issue(array $d): License` creates a row. `features` is normalised by
`normalizeFeatures()`: a JSON string is decoded, otherwise it is split on
commas and newlines. The literal `'*'` in `features` means "everything".

`activate(string $key, string $domain, ?string $ip = null): array` returns
`['ok' => bool, 'message' => …, 'features' => …, 'license' => License]` and
fails when the key is unknown, the status is not `active`, `expires_at` has
passed (which also flips the row to `expired`), or the activation count has
reached `max_activations` for a domain that is not already activated.

`deactivate(string $key, string $domain): array` releases one activation slot;
it fails if that domain was not activated.

Domains are normalised with `normalizeDomain()`: lower-cased, scheme stripped,
path stripped, leading `www.` removed — the same normalisation
`WhiteLabelController::storeDomain()` uses.

### `status()`

```php
[
    'licensed'            => $state === 'valid',
    'state'               => 'valid'|'unlicensed'|'invalid'|'expired'|'suspended'|'banned'|'outdated'|'inactive',
    'version'             => config('lindu.version'),
    'entitled_version'    => $license->version_entitlement,
    'expires_at'          => …,
    'support_expires_at'  => …,
    'features'            => …,
    'license'             => License,
    'message'             => …,
]
```

`status()` reads the **local** `storage/app/lindu/license.json` lock written by
`install()`. With no lock file it returns `state: 'unlicensed'` — the product
runs in evaluation mode. `outdated` means the installed version is newer than
`version_entitlement`. A license whose `expires_at` has passed is flipped to
`expired` as a side effect of calling `status()`.

`hasFeature(string $feature): bool` returns `false` for any unlicensed install,
otherwise `true` when `features` contains `'*'` or the exact key.

### Remote verification provider (optional)

```php
// config/lindu.php
'license' => ['provider' => env('LINDU_LICENSE_PROVIDER', '')],
```

`LicenseService::provider()` resolves it and returns `null` when the value is
empty or the class does not exist. The contract is
`App\Core\Contracts\LicenseProvider` (`verify`, `activate`, `deactivate`).

**No provider ships with the product.** The default is local-only: the
`licenses` table plus the lock file. If you build a provider, put its class name
in `LINDU_LICENSE_PROVIDER` and make sure it implements the interface.

### Admin UI

`/admin/license` (`admin.licenses.*`): list, create, edit, delete, per-license
activations, revoke one activation, install (`POST admin.licenses.install.run`)
and uninstall (`POST admin.licenses.uninstall`).

`install()` calls `activate()` first and only writes
`Storage::disk('local')->put('lindu/license.json', …)` on success. Note the
inline comment claims *"Encryption keeps the key off disk in clear text"* — it
does not; the JSON is written in plain text on the `local` disk. If you need the
key protected at rest, use License v3 below, or add your own encryption.

---

## 2. License v3 pairing gate

This is the marketplace kit. It is enforced by middleware on **every** `web`
request, and it is separate from section 1.

- Client: `app/Services/LicenseClient.php`
- Middleware: `app/Http/Middleware/RequirePair.php`
- Wizard controller: `app/Http/Controllers/PairController.php`
- Config: `config/license.php`
- Public key: `public/marketplace.public.pem`
- Lock file: `storage/app/.license.lock`

### What it does

`bootstrap/app.php` appends `RequirePair` to the `web` group, ahead of
`ResolveTenant`:

```php
$middleware->appendToGroup('web', [App\Http\Middleware\RequirePair::class]);
$middleware->appendToGroup('web', [App\Http\Middleware\ResolveTenant::class]);
$middleware->appendToGroup('api', [App\Http\Middleware\ResolveTenant::class]);
```

If `LicenseClient::verify($host)` returns `null`, **every** request is
redirected to `/__pair` — admin, storefront and everything else. The decoded
payload is attached to the request as the `license` attribute on success.

Bypassed paths: `/__pair*`, `/up`, `/_debugbar*`, and — only when
`config('license.dev_bypass')` is true **and** `APP_ENV=local` — `localhost`,
`127.0.0.1`, `*.test` and `*.localhost`.

### Lock file format

```
[16-byte nonce][16-byte GCM tag][ciphertext]
```

- Key: `hash_hkdf('sha256', APP_KEY.':'.strtolower($domain), 32, '', 'license-lock-v1')`
- Cipher: `AES-256-GCM`, `OPENSSL_RAW_DATA`
- Plaintext: `json_encode($signedPayload)`
- File mode `0600`

Because the key is derived from the host, **copying the lock file to another
domain does not decrypt** — that is the anti-sharing property.

### Verification

`readLock()` decrypts, then `verifySignature()` checks the payload against the
RSA public key with `openssl_verify(…, OPENSSL_ALGO_SHA256)`. `verify($domain)`
additionally rejects a domain mismatch and an `expires_at` in the past.

### Activation and heartbeat

`activate($key, $domain)` posts to `{server}/api/license/activate` and requires
`activated: true` plus a `signed_payload` that validates against the public key
before the lock is written.

`maybeHeartbeat()` posts to `{server}/api/license/heartbeat` at most once per
`heartbeat_interval` (default 24 h, cached). Outcomes:

| Response | Effect |
|---|---|
| `{ok: true, status: "active"}` | reset the heartbeat timestamp |
| `{action: "delete_license_file"}` | **delete the lock immediately** — the server revoked it |
| unreachable, 5xx, undecodable | `markOffline()`: start a 7-day grace window; when it expires, delete the lock |

So an unreachable marketplace keeps the product working for
`LICENSE_HEARTBEAT_GRACE` seconds (604800 = 7 days) and then blocks. A
marketplace that explicitly says "revoked" blocks at once.

### Configuration

```dotenv
LICENSE_SERVER_URL=https://whitelabel.co.id
LICENSE_DEV_BYPASS=true
LICENSE_HEARTBEAT_INTERVAL=86400
LICENSE_HEARTBEAT_GRACE=604800
```

Plus `config/license.php`: `public_key_path`
(`public/marketplace.public.pem`), `lock_file` (`storage/app/.license.lock`),
`http_timeout` (10).

### Known gap: the wizard is not routed

`RequirePair` redirects to `/__pair`, and `app/Http/Controllers/PairController.php`
plus `routes/pair-routes.php` exist — but **`routes/pair-routes.php` is not
required by `routes/web.php` or by `bootstrap/app.php`.** There is no
`require` of it anywhere in `routes/`.

Consequence: with `LICENSE_DEV_BYPASS` off (i.e. on a real production host, or
any host that is not `local` + `localhost|127.0.0.1|*.test|*.localhost`), an
unpaired install redirects to a route that does not exist, so the operator sees a
404 instead of the activation wizard. **Before deploying, either register the
pair routes or ship the install already paired.** Do not promise a customer a
self-service activation wizard until this is wired up.

Also note `dev_bypass` defaults to `true` in `config/license.php`, and
`.env.example` ships `LICENSE_DEV_BYPASS=true`. It only takes effect in the
`local` environment, so production is not silently bypassed — but do not set
`APP_ENV=local` on a production host.

---

## 3. What a customer actually gets

Nothing is enforced by default:

- With no license row and no lock file, `LicenseService::status()` reports
  `unlicensed` and every feature is available.
- `FeatureFlag` (the plan/entitlement helper) is independent of licensing; it
  reads `plan_features` and, with no tenant, falls back to
  `PlanFeature::where('key',…)->exists()`.

So the licensing system is a **recording and reporting** mechanism plus, if you
wire it, a gate. If you intend to hard-disable features for unlicensed installs,
you have to add that check at the call sites — nothing in core does it for you
today.

## See also

[UPDATES.md](UPDATES.md), [SAAS.md](SAAS.md), [SECURITY.md](SECURITY.md),
[ARCHITECTURE.md](ARCHITECTURE.md).
