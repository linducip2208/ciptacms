# Install

## Requirements

| | |
|---|---|
| PHP | **8.3+** (`HealthService` compares against `8.3.0`) |
| Extensions | `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo` |
| Database | MySQL 8+ or SQLite (SQLite is the zero-config dev path) |
| Composer | 2.x |
| Web server | Nginx or Apache, docroot **must** be `public/` |
| Optional | GD or Imagick (thumbnails, WebP/AVIF), `ext-zip` (backup archives), Node/npm (asset build only) |

`php artisan lindu:doctor` runs exactly this list and reports the PHP version,
each extension, writability of `storage/`, `storage/app` and `public/storage`, a
`select 1` against the database, and the queue driver. `GET /install` shows the
same checks and refuses to continue while any of them fails.

## Steps

```sh
git clone https://github.com/linducip2208/ciptacms
cd ciptacms
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Then open `http://127.0.0.1:8000` and sign in:

```
admin@lindu.local / password123
```

`database/seeders/UserSeeder.php` creates that account (role `super-admin`) plus
`manager@lindu.local` and `member1..5@lindu.local`, all with the same password.
**Change or delete them before the site is reachable by anyone else.**

`.env.example` ships `DB_CONNECTION=sqlite`, so the five commands above work with
no database server. To use MySQL, uncomment and fill the `DB_*` block first.

`php artisan lindu:install` is an alternative to `migrate --seed` that also takes
`--admin-email`, `--admin-password` and `--fresh` if you want a different first
admin.

## Browser installer

`/install` is a three-step wizard (`welcome` → `database` → `run`):

1. **Requirements** — the `HealthService` checks above. It blocks the next step
   while anything fails.
2. **Database** — pick `mysql`, `sqlite`, `pgsql` or `mariadb` and fill in the
   credentials. It writes `APP_URL`, `APP_NAME` and the `DB_*` keys into `.env`.
   It **never generates `APP_KEY`**, deliberately: a new key would invalidate
   every encrypted setting, session and license lock already written.
3. **Run** — `migrate --force`, `db:seed --force`, then create or reset the admin
   user, attach `super-admin` (falling back to `admin`), write `general.site_name`
   and `general.tagline`, and write the install lock. It redirects to `/login`.

### The install lock

Once installed, `/install` is closed. The lock lives at
`config('lindu.installer_lock')` = `storage/app/installed`, outside the web root.

To reopen it:

```sh
php artisan lindu:unlock --force        # remove the lock
php artisan lindu:unlock --token        # print the recovery token, change nothing
```

The recovery token is a deterministic 32-character hex value derived from the lock
path and `APP_KEY`; the same install always produces the same token, and
`/install?recovery_token=…` reopens the installer for that single request before
it re-locks. Both are covered by `tests/Feature/InstallerTest.php`.

Never delete `storage/app/installed` by hand on a live site — that would expose a
full re-install to anyone who can reach `/install`.

## After installing

```sh
php artisan storage:link     # public/storage -> storage/app/public
```

Without it every uploaded image, form upload and CV 404s. `InstallerController`
does not create it, so run it yourself.

### Queue and scheduler

Webhooks and the media pipeline use the queue. `.env.example` sets
`QUEUE_CONNECTION=database`, so the `jobs` table is used and no extra process is
strictly required to get started — but for anything real run a worker:

```sh
php artisan queue:work
```

For the scheduled jobs (`lindu:backup`, `lindu:check-updates`, `webhooks:retry`,
`queue:prune-failed`):

```sh
* * * * * cd /path/to/ciptacms && php artisan schedule:run >> /dev/null 2>&1
```

`QUEUE_CONNECTION=sync` runs jobs inline — fine for a demo, not for production.

### Assets

The admin and the public site ship working CSS and JS with **no build step**:
Tabler and Alpine are loaded from a CDN, and the page builder is plain Alpine in
one Blade file. `npm install && npm run build` (Vite) is only needed if you
change `resources/css`, `resources/js` or `vite.config.js`.

### Optional integrations

Everything below is inert until you fill in the env var:

```dotenv
SEARCH_DRIVER=database          # or meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=
GITHUB_CLIENT_ID=  GITHUB_CLIENT_SECRET=  GITHUB_REDIRECT_URI=
GOOGLE_CLIENT_ID= GOOGLE_CLIENT_SECRET= GOOGLE_REDIRECT_URI=
MEDIA_DISK=public               # or s3
LICENSE_SERVER_URL=https://whitelabel.co.id
```

`SEARCH_DRIVER=database` (the default) needs nothing. OAuth login needs both
`client_id`/`client_secret` **and** a `redirect_uri` that exactly matches the
callback registered with the provider.

## Upgrading an existing install

See [UPDATES.md](UPDATES.md) for why the in-app update centre does not fetch
code, and [DEPLOYMENT.md](DEPLOYMENT.md) for the deploy script.

```sh
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan queue:restart
```

## Troubleshooting

`php artisan lindu:doctor` first, then
[TROUBLESHOOTING.md](TROUBLESHOOTING.md).

| Symptom | Cause |
|---|---|
| Every page redirects to a 404 | `RequirePair` sent you to `/__pair`, which is not routed. See [LICENSE.md](LICENSE.md#known-gap-the-wizard-is-not-routed) |
| Images 404 | `php artisan storage:link` was not run |
| `APP_KEY` empty / "No application encryption key" | `php artisan key:generate` |
| Webhooks never deliver | no queue worker, or `webhooks:retry` is not scheduled |
| Admin sidebar is empty | the `menu_items` seed did not run — `php artisan db:seed` |

## See also

[DEVELOPMENT.md](DEVELOPMENT.md), [DEPLOYMENT.md](DEPLOYMENT.md),
[ARCHITECTURE.md](ARCHITECTURE.md), [LICENSE.md](LICENSE.md).
