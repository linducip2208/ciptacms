# Troubleshooting

## `503 Service Unavailable`

> The literal text *"temporarily unable to service your request … maintenance
> downtime or capacity problems"* is **Apache's** default error page, not output
> from Lindu CMS. Read the server's `error_log` before debugging the
> application.

### Case on record: `http://ciptacms.test/` → 503 (local, Laragon)

**Root cause, from the log.** The vhost file `auto.ciptacms.test.conf` was
created at 14:26 but Apache had started at 01:35. Apache did not know that
hostname, so the request fell through to the default vhost
(`alias/wanode.conf`), which proxies to a Node backend on `127.0.0.1:8891` that
was not running:

```
AH00957: attempt to connect to 127.0.0.1:8891 failed   → 503
```

**Fix.** Reload Apache from the Laragon tray (right click → Reload, or Stop All
→ Start All). `httpd -k restart` from the CLI does **not** work — Laragon's
Apache on Windows is not a Windows service.

**Resolved 26 Sep 2026.** After the reload (new child, 64 workers),
`ciptacms.test/` returned 200 and `/login` returned the Tabler login page with no
proxy errors.

### Two-minute check

1. Kill every stale dev server and start exactly one:

   ```sh
   cd "D:\project laravel\ciptacms"
   php artisan optimize:clear
   php artisan serve --host=127.0.0.1 --port=8000
   ```

2. `http://127.0.0.1:8000/up` must return **200**. Then
   `http://127.0.0.1:8000/login`.
3. `php artisan lindu:doctor` — everything OK.

`php artisan up` clears maintenance mode; a leftover
`storage/framework/maintenance.php` will 503 every route.

### Still on the Laragon vhost (`http://ciptacms.test`)

1. Laragon Menu → PHP → Version → **8.3**.
2. The vhost's `DocumentRoot` must be `D:\project laravel\ciptacms\public`, not
   the project root.
3. Laragon → Stop All → Start All.
4. Read the last 20 lines of `storage\logs\laravel.log`.

### Need to report it

Send: the exact URL, the last 20 lines of `storage\logs\laravel.log`, and the
output of `php artisan lindu:doctor`.

---

## Everything redirects to a 404

`RequirePair` middleware sends every unpaired request to `/__pair`, and
`routes/pair-routes.php` is **not registered** anywhere. This happens on any
host that is not `local` + `localhost` / `127.0.0.1` / `*.test` / `*.localhost`.

- For local work: `APP_ENV=local` and `LICENSE_DEV_BYPASS=true` in `.env`.
- For a real host: provision `storage/app/.license.lock` before pointing DNS at
  the box, or add `require __DIR__.'/pair-routes.php';` to `routes/web.php`.

Full explanation in [LICENSE.md](LICENSE.md#known-gap-the-wizard-is-not-routed).

---

## `Route [...] not defined`

A `route('name')` in a Blade template or controller references a route that does
not exist. This is a runtime-only failure on one page, so it ships easily.

```sh
php tools/route-lint.php
```

It catches literal `route('name')` calls in Blade and in `.php` files under
`app/`, `routes/`, `database/` and `modules/`.

**It will not catch a dynamic name with arguments after it** — the pattern needs
`)` immediately after the quoted name, so
`route('api.v1.forms.submit', $slug)` is skipped. Check those by hand:

```sh
php artisan route:list | findstr forms
```

---

## A page 500s but the template looks fine

A Blade directive placed immediately after a word character is not matched by
Blade, so the block it should have opened is never opened while its `@endif`
still is. The template compiles, `php -l` passes, and the failure appears only at
runtime:

```blade
<p>Total: @if($n){{ $n }}@endif</p>      {{-- @if is literal text --}}
<p>Total: @if ($n) {{ $n }} @endif</p>  {{-- correct --}}
```

```sh
php tools/blade-lint.php                 # all templates
php tools/blade-lint.php resources/views/site   # one subtree
```

---

## Images are 404

```sh
php artisan storage:link
```

`InstallerController` and `deploy.sh` both call it, but nothing enforces it. The
link maps `public/storage` → `storage/app/public`, which is where media, form
uploads and CVs land. On a server, also check:

```sh
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

## Webhooks never arrive

Three things must all be true:

1. a worker is running — `php artisan queue:work`, or
   `supervisorctl status lindu-queue` on a server;
2. `webhooks:retry` is scheduled — `* * * * * php artisan schedule:run`;
3. the hook row has `is_active = true` and the right `event` name.

Check `/admin/cms/webhooks/logs`; a failed delivery records the HTTP status, the
response body (truncated) and `next_retry_at`, and is abandoned after
`webhooks.max_attempts` (default 3).

`WebhookDispatcher::dispatchEvent()` falls back to delivering inline if the queue
driver refuses the job, so an unconfigured queue degrades rather than drops.

## The admin sidebar is empty

The sidebar is rendered from the `menu_items` table, not from code:

```sh
php artisan db:seed          # MenuSeeder + FrontendMenuSeeder
```

Cache is 120 s per `(location, user, tenant)`, so a seed is not visible
immediately — or force it:

```php
app(\App\Core\Services\MenuService::class)->forget();   // Cache::flush()
```

A module's menus are deleted by `deactivate()` / `uninstall()`; reactivate the
module to get them back.

## Search returns nothing

`SEARCH_DRIVER=database` (the default) needs no external service. For Meilisearch:

```sh
MEILISEARCH_HOST=http://127.0.0.1:7700 php artisan lindu:search-index --limit=200
```

## `php artisan test` fails

It is **not green** — see [DEVELOPMENT.md](DEVELOPMENT.md#current-state-of-the-suite).
For a compact view of *why*:

```sh
php tools/test.php              # counts + one line per distinct failure reason
php tools/test.php Workflow     # filtered
```

Note that three of the four tests in `tests/Feature/FormBuilderTest.php` are
`probe` tests that catch an exception and `assertTrue(true)` — they report success
whether or not the code works.

## "No application encryption key"

```sh
php artisan key:generate
```

**Do not regenerate `APP_KEY` on a live site.** It invalidates every encrypted
setting, every session and the License v3 `.license.lock`, which is derived from
it. That is why the browser installer never generates it.

## `lindu:unlock` after installing by mistake

`/install` locks itself on first use. To reopen it:

```sh
php artisan lindu:unlock --force     # removes the lock
php artisan lindu:unlock --token     # prints the token without unlocking
```

The recovery token is a stable 32-char hex value derived from the lock path and
`APP_KEY`; `/install?recovery_token=…` reopens the installer for one request.

## Deployed but nothing changed

Cached config or routes. A new route, config key or Blade component is invisible
until:

```sh
php artisan optimize:clear
```

`deploy.sh` runs `config:cache`, `route:cache` and `view:cache`, so a manual
`git pull` that skips them will silently serve the old application.

## Quick reference

| Command | Answers |
|---|---|
| `php artisan lindu:doctor` | Is this install healthy at all? |
| `php artisan route:list` | Does the route exist? |
| `php tools/route-lint.php` | Does every `route()` name in the code exist? |
| `php tools/blade-lint.php` | Does every template compile to valid PHP? |
| `php tools/test.php` | Which failures are real, and why? |
| `tail -f storage/logs/laravel.log` | What did the app actually say? |
| `php artisan about` | Framework, environment, drivers, cache |

## See also

[DEPLOYMENT.md](DEPLOYMENT.md), [INSTALL.md](INSTALL.md),
[DEVELOPMENT.md](DEVELOPMENT.md), [LICENSE.md](LICENSE.md).
