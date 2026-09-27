# Upgrade

How to move an existing CiptaCMS / Lindu CMS installation from one version to
the next. Everything here is a command that exists in
`app/Console/Commands/` or ships with Laravel 13; nothing in this file is
aspirational.

If you are installing for the first time, you want
[INSTALL.md](INSTALL.md) instead. If you are deploying to a server for the
first time, see [DEPLOYMENT.md](DEPLOYMENT.md).

---

## Where the version lives

| | |
|---|---|
| `config/lindu.php` → `version` | what the application reports (currently `1.0.0`) |
| `config/lindu.php` → `core_version` | same value, separate key |
| `.release-version` | the version `tools/release.php` builds by default |
| `storage/app/installed` | the **install-time** version — a JSON file written once by the installer, and **not** rewritten by an upgrade |

Two things follow from that last row:

- `php artisan lindu:unlock` prints *"Version: 1.0.0"* for an install that was
  set up months ago on an old build and has since been upgraded several times.
  It is telling you when the site was **installed**, not what is running now.
- An upgrade does **not** touch the install lock. `/install` stays closed
  across every upgrade, which is what you want.

Note also that the version constant in `config/lindu.php` and the version
labels in commit messages have drifted apart — commit subjects go up to `v1.8`
while the config still says `1.0.0`. Read the config, not the changelog, for
what the code reports. See [CHANGELOG.md](CHANGELOG.md).

---

## Before you start

| | |
|---|---|
| You need | shell access to the server, and a backup you have actually restored from at least once |
| You should have | a maintenance window, and a second person who knows the site |
| You must not | change `APP_KEY`, delete `storage/app/installed`, or run `migrate:fresh` |

`APP_KEY` is the one thing that cannot be regenerated on a live site. The
License v3 lock file (`storage/app/.license.lock`) is encrypted with a key
derived from `APP_KEY` **and** the host, and every `secret`-type setting is
encrypted with it. A new key means an unpaired site and unreadable settings.
This is why the browser installer never generates one.

---

## The upgrade

### 1. Back up, and copy the backup off the server

```sh
php artisan lindu:backup --type=full
```

That writes `backups/backup-YYYYmmdd-HHMMSS-full.zip` to the disk named by
`setting('storage.backup_disk')`, which defaults to `local` — so
`storage/app/backups/` — and prunes back to
`setting('storage.keep_backups', 7)` archives. On a host without `ext-zip` the
file is a single `.sql` file instead of a zip.

**A backup that only exists on the same box is not a backup.** Copy the newest
archive somewhere else before you touch anything:

```sh
ls -lt storage/app/backups/
```

The admin screen **System → Backup** lists every archive with its exact path,
which is the authoritative answer if you have changed the backup disk.

The archive contains a `database.sql` dump, the uploaded files, a
`manifest.json` and a **secret-stripped** `.env.example`. It does not contain
your real `.env`, so keep your own copy of that safe separately.

A backup is also taken automatically for you by `/admin/updates` when you use
the in-app update centre, and by the daily `lindu:backup` scheduled job. Do
not rely on either for the backup you are about to depend on.

### 2. Put the site into maintenance mode

```sh
php artisan down
```

Visitors get a 503 page instead of a half-upgraded site. Leave it on until
step 9.

### 3. Fetch the new code

If the install is a git checkout (the normal case):

```sh
git fetch origin
git reset --hard origin/main        # or your branch
```

If the install came from a release archive, unpack the new archive over the
project root, then re-apply your own `.env`:

```sh
unzip CiptaCMS-v1.0.0.zip -d /tmp/ciptacms-new
# copy .env, storage/, and anything you customised, across
```

`deploy/deploy.sh` does steps 3–9 automatically; see
[DEPLOYMENT.md §5](DEPLOYMENT.md#5-deploy).

### 4. Install the dependencies

```sh
composer install --no-dev --optimize-autoloader
```

`--no-dev` is what a live site wants: it drops PHPUnit, Faker, Pint and the
other `require-dev` packages. `--optimize-autoloader` rebuilds the classmap,
which matters because a classmap built on a dev machine will not match a
production tree.

If `composer.lock` is out of step with `composer.json` — a real failure, not a
warning — stop and resolve that before continuing. Do not run
`composer update` to "fix" it on a production box: that resolves a different
set of packages than the one you tested.

### 5. Run the migrations

```sh
php artisan migrate --force
```

`--force` is required in production, otherwise Laravel refuses. Migrations are
additive: there are 23 of them and they create tables and add columns. They
do not drop data.

If you want to see what is pending first:

```sh
php artisan migrate:status
```

### 6. Clear the caches

```sh
php artisan optimize:clear
```

This is the step people forget, and the symptom is always the same: *"I
deployed it and nothing changed."* `optimize:clear` drops the config cache,
the route cache, the view cache and the event cache in one go. A cached route
table means a new admin screen 404s; a cached config means a new setting key
is invisible.

On a production site, re-warm the caches afterwards:

```sh
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Do this **after** `optimize:clear`, never instead of it.

### 7. Re-check the storage link

```sh
php artisan storage:link
```

Harmless if the link already exists. Without it every uploaded image, form
upload and CV is a 404.

On a server, re-assert ownership after any code change:

```sh
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### 8. Recount plan usage — only if you use plans

```sh
php artisan lindu:quota-recount
```

Usage counters are incremented as content is written, which is fast but drifts
after a restore, a manual database edit or a failed transaction. This command
walks the truth back over the counters and prints a table of
`used / limit` for every metric. It also runs daily from
`routes/console.php`, so it is a correction rather than a required step.

Add `--tenant=<id>` to restrict it to one tenant.

Skip it entirely if the install is single-tenant with no plan limits — it is
harmless either way, but there is no reason to run it by habit.

### 9. Restart the queue and leave maintenance mode

```sh
php artisan queue:restart
php artisan up
```

`queue:restart` signals running workers to exit after their current job, so
new code is picked up. Webhook delivery, the media pipeline and notification
mail all depend on a worker being alive — if `supervisorctl` is managing them
on a server, `queue:restart` is enough, but `supervisorctl restart lindu-queue:*`
is the belt-and-braces version.

### 10. Verify

```sh
php artisan lindu:doctor
```

It exits non-zero if anything failed. It checks the PHP version, ten
extensions, writability of `storage/`, `storage/app`, `storage/logs` and
`bootstrap/cache`, a `select 1` against the database, that maintenance mode is
off, that `APP_KEY` is set, that there is at least 100 MB of free disk, and
that `public/storage` exists (`gd` and a missing storage link are warnings,
not failures).

Then, in a browser:

- [ ] the public home page loads
- [ ] `/login` works and you can sign in
- [ ] `/admin` loads and the sidebar has its items
- [ ] one uploaded image renders (proves the storage link)
- [ ] `/up` returns 200 — it bypasses the licence pairing gate, so it is the
      cleanest single check that PHP and the router are alive

Full checklist in [DEPLOYMENT.md §7](DEPLOYMENT.md#7-post-deploy-checklist).

---

## If a step fails

### `composer install` fails

**Stop.** Nothing has changed on disk except `vendor/`, and the old code is
still there. Fix the dependency problem on a development machine, or restore
`vendor/` from your own copy, and re-run. Do not continue to step 5 with a
half-installed vendor tree — every subsequent error will be a red herring.

### `migrate --force` fails part-way

Migrations that already ran stay applied; the one that failed rolled back
(Laravel wraps each migration). The site is **still on maintenance mode**,
which is the right place to be.

1. Read the actual error. `php artisan migrate:status` tells you which
   migration is pending.
2. Most first-failure causes are an environment problem, not a code problem:
   the database user lacks `ALTER`, the MySQL version is too old for a
   generated column, the disk is full, `innodb_file_per_table` is off.
3. Fix the cause and re-run `php artisan migrate --force`. It resumes — it
   does not start over.
4. If it still cannot be made to run, roll the code back to the previous
   commit and restore the database from your backup. Restoring a backup means
   restoring the SQL dump too; migrated tables do not un-migrate themselves.
5. Only then `php artisan up`.

**Never** reach for `migrate:fresh` or `migrate:reset` on a live site. They
drop every table, and `migrate:fresh` is exactly the command
`lindu:install --fresh` runs.

### `optimize:clear` fails

Unlikely on its own. If it does, the most common cause is `bootstrap/cache`
not being writable by the web user:

```sh
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
php artisan optimize:clear
```

Note the consequence of *skipping* this step rather than failing it: the old
cached config, routes and views keep being served, and the site appears to
have ignored the upgrade. That is a worse outcome than a hard error, so
verify the step ran.

### The site comes back up but the admin looks wrong

That is a stale cache. Re-run `php artisan optimize:clear`, then hard-reload
the browser. If it persists, a migration added a column a controller expects
and did not create it — check `php artisan migrate:status` against
`database/migrations/`.

### Everything redirects to `/__pair`

The Licence v3 pairing gate runs on every `web` request. The wizard at
`/__pair` **is** routed (`routes/pair-routes.php`, required from
`routes/web.php`), so this means the install is genuinely unpaired for this
host: `storage/app/.license.lock` is missing, is for a different domain, or
has been deleted by the marketplace heartbeat. Read
[LICENSE.md](LICENSE.md) before touching it.

### `lindu:doctor` reports a problem you cannot explain

```sh
tail -n 200 storage/logs/laravel.log
```

and then [TROUBLESHOOTING.md](TROUBLESHOOTING.md). The doctor is a summary;
the log is the evidence.

---

## What an upgrade does not do

- It does **not** re-run the seeders. `db:seed` on a live site calls
  `MenuItem::query()->delete()` first (`MenuSeeder`) and would wipe the
  navigation an operator has arranged. Never run `php artisan db:seed` on a
  production site.
- It does **not** update modules, plugins or themes from anywhere. Extension
  upgrades are a `git pull` plus `php artisan migrate`, like everything else.
  There is no `update()` action on a module.
- It does **not** re-register the licence. If the marketplace heartbeat deletes
  the lock, re-activate through `/__pair`.
- It does **not** touch `storage/app/installed`, so `/install` stays closed.
- It does **not** download anything. The in-app update centre
  (`/admin/updates`) reports versions and runs backup + migrate + clear-cache
  + record-version; it never fetches or executes code. See
  [UPDATES.md](UPDATES.md).

---

## Rolling back

An upgrade is a `git reset` away, provided you also undo the database:

```sh
php artisan down
git reset --hard <previous-commit>
composer install --no-dev --optimize-autoloader
php artisan migrate --force      # additive migrations have no down path; see below
php artisan optimize:clear
php artisan up
```

The caveat is the database. `database/migrations/` does not ship `down()`
methods for every migration, so a schema change is not automatically
reversible. **Restoring the SQL dump from the step-1 backup is the reliable
rollback**, and it is the reason step 1 is not optional.

---

## See also

[INSTALL.md](INSTALL.md) · [DEPLOYMENT.md](DEPLOYMENT.md) ·
[UPDATES.md](UPDATES.md) · [CHANGELOG.md](CHANGELOG.md) ·
[TROUBLESHOOTING.md](TROUBLESHOOTING.md) · [LICENSE.md](LICENSE.md) ·
[SECURITY.md](SECURITY.md)
