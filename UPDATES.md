# Updates

`UpdateService` reports versions and performs a **safe local apply**. It never
downloads, unpacks or executes code from a remote source. Delivering a new
release is a `composer install` / `git pull` — that is the deployment process's
job, not the CMS's.

- Service: `app/Core/Services/UpdateService.php`
- Admin controller: `app/Http/Controllers/Admin/UpdateCenterController.php`
- Views: `resources/views/admin/updates/{index,registry,history}.blade.php`
- Admin routes: `admin.updates.*` (`/admin/updates…`)
- Tables: `update_logs`, plus `modules` / `plugins` / `themes` registries
- Command: `php artisan lindu:check-updates` (daily in `routes/console.php`)

## Configuration

```php
// config/lindu.php
'version' => '1.0.0',
'updates' => [
    'channel'  => env('LINDU_UPDATE_CHANNEL', 'stable'),
    'endpoint' => env('LINDU_UPDATE_URL', ''),
],
```

**Both env vars are unset in `.env.example`.** Out of the box `endpoint` is an
empty string and `channel` is `stable`.

## `check($type = 'core')`

Returns:

```php
[
    'type'            => 'core',
    'current'         => config('lindu.version'),
    'latest'          => …,
    'update_available'=> version_compare($latest, $current, '>'),
    'channel'         => config('lindu.updates.channel'),
    'checked_at'      => now()->toDateTimeString(),
    'source'          => 'remote' | 'local',
]
```

`remoteManifest()` runs only when `lindu.updates.endpoint` is a non-empty
string. It issues a 5-second `Http::get()` with three query parameters:

```
GET {endpoint}?product=Lindu+CMS&channel=stable&current=1.0.0
```

and requires a JSON body with a non-empty `version` key.

**With no endpoint configured — the default — `check()` does not contact
anything.** `remoteManifest()` returns `null`, `$latest` falls back to
`$current`, and the result is:

```
latest = current, update_available = false, source = "local"
```

That is a truthful "I have nothing to compare against", **not** a claim that the
installation is up to date. Read the `source` field before telling a customer
their copy is current; if it says `local`, nobody checked anything.

If you want a real check, run your own endpoint that answers
`{"version": "1.2.0"}` and set `LINDU_UPDATE_URL` to it. Anything the endpoint
returns is trusted as-is — the response is not signed and the version is not
validated against a release manifest.

## `checkAll($type)`

For `module`, `plugin` and `theme`, `checkAll()` returns one row per registered
package with `current` and `latest` **both set to the row's own `version`**, and
`update_available => false`.

```php
'latest'           => $row->version,
'update_available' => false,
```

This is a listing, not a check. **Extension updates are never actually
checked.** `checkAll()` returns `[]` for any type outside
`['module' => Module::class, 'plugin' => Plugin::class, 'theme' => Theme::class]`.

## `apply($type, $slug, $toVersion)`

Called from `POST admin.updates.apply`, which validates `type` ∈
`core|module|plugin|theme`, a `slug`, a `to_version`, and a `confirm` checkbox.
`authorizeUpdate()` then refuses the request unless:

- `type = core` **and** `slug = 'lindu'` **and** the user has `settings.manage`; or
- the `slug` exists in the `modules` / `plugins` / `themes` registry.

That check is what stops an operator pointing the updater at an arbitrary slug.

`apply()` creates an `update_logs` row (`status: running`) and then, in order:

1. **Backup first.** `BackupService::run('full')`. If the backup did not end
   `completed`, it throws `Pre-update backup failed: …` and nothing else runs —
   the log ends `failed`. There is no "skip the backup" flag.
2. **Run migrations.** `Artisan::call('migrate', ['--force' => true])`.
3. **Clear caches.** `MenuService::forget()` (which is `Cache::flush()` — see
   [MENU_ENGINE.md](MENU_ENGINE.md)) and `SettingService::forgetCache()`.
4. **Record the version**, for `type !== 'core'` only: `bumpVersion()` updates
   the registry row's `version` **and rewrites the on-disk manifest** so
   `module.json` / `plugin.json` / `theme.json` stays in step.

The `log` column holds one line per step; the row ends `completed` or `failed`.
History is at `GET admin.updates.history`.

### `bumpVersion()` and the manifest path

```php
$path = config("lindu.{$type}s_path").'/'.$slug;
$file = $path.'/'.($type === 'theme' ? 'theme' : rtrim($type, 'e')).'.json';
```

- `module` → `rtrim('module', 'e')` = `modul` → **`module.json`**
- `plugin` → `rtrim('plugin', 'e')` = `plugin` → `plugin.json`
- `theme` → `theme` → `theme.json`

Only `slug` is used in the path, and a `slug` comes from the manifest, so this
cannot escape the extensions directory in practice. The file is rewritten with
`json_encode(…, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)`, so reformatting a
manifest through an update is expected.

If the manifest file does not exist, only the database row is updated.

## What `apply()` does not do

- It does not download a release.
- It does not run `composer install` / `composer update`.
- It does not `git pull`.
- It does not replace files, including files in `modules/`, `plugins/` or
  `themes/`.
- It does not clear `bootstrap/cache` (no `optimize:clear`).
- It does not restart the queue worker.

For `type = core` it does not even record a version — `config('lindu.version')`
is a constant in `config/lindu.php`, so a core "update" through the UI changes
the database and the log but **not** the version the application reports.

**This is the intended design.** A CMS that fetches and runs a remote archive is
a remote code execution surface. The update centre is a backup + migrate +
cache-clear + record-version pipeline; shipping a new release stays a deployment
step:

```sh
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
```

## Admin surface

| Route | Purpose |
|---|---|
| `GET admin.updates.index` | core check + module/plugin/theme lists + last 10 log rows |
| `GET admin.updates.modules` / `.plugins` / `.themes` | per-registry listing |
| `GET admin.updates.history` | paginated `update_logs`, `?type=` filter |
| `POST admin.updates.check` | re-runs `check()`/`checkAll()`; message is `"Checked: current X, latest Y."` or `"Checked N module(s)."` |
| `POST admin.updates.apply` | runs `apply()` behind the `confirm` + `authorizeUpdate()` gate |

## Command

```
php artisan lindu:check-updates
```

Registered in `routes/console.php` as `Schedule::command('lindu:check-updates')->daily()`.
With no `LINDU_UPDATE_URL` set it records the same local result as the admin
screen; it does not log anywhere by itself.

## Backup, because apply() depends on it

`apply()` aborts unless the pre-update backup completes, so the backup path has
to work before you can update anything.

- Disk: `setting('storage.backup_disk', config('lindu.backup.disk', 'local'))`
- Retention: `setting('storage.keep_backups', config('lindu.backup.keep', 7))`
- Contents: a `database.sql` dump (MySQL `SHOW CREATE TABLE` / SQLite
  `sqlite_master`), the files under `storage/app/public`,
  `public/storage/media` and `storage/app/careers` (files over 25 MB are skipped,
  capped at 3000 entries), a `manifest.json`, and a **secret-stripped**
  `.env.example` — `APP_KEY`, `DB_PASSWORD`, anything ending in `_SECRET`,
  `_KEY` or `_TOKEN` is blanked.
- A `zip` is written when `ZipArchive` exists, otherwise a single concatenated
  `.sql`-suffixed file with `-- ===== name =====` separators.
- Run it by hand with `php artisan lindu:backup --type=full` (also scheduled
  daily).

See also: [DEPLOYMENT.md](DEPLOYMENT.md), [ARCHITECTURE.md](ARCHITECTURE.md).
