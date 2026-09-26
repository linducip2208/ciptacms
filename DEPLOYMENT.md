# Deploy VPS Produksi

> **Read this first.** Lindu CMS ships a **License v3 pairing gate** that runs
> on every `web` request. On a production host (`APP_ENV=production`) the
> `LICENSE_DEV_BYPASS` bypass does **not** apply, so an unpaired install
> redirects to `/__pair` — and that route is **not registered**. Provision the
> license lock before you point a domain at the box, or you will deploy a 404.
> See [LICENSE.md](LICENSE.md#known-gap-the-wizard-is-not-routed).

## 1. Server (Ubuntu 22.04 / 24.04)

```sh
apt update && apt install -y \
  php8.3 php8.3-{cli,fpm,mysql,mbstring,xml,bcmath,gd,curl,zip,fileinfo} \
  composer mysql-server nginx supervisor certbot python3-certbot-nginx
```

`HealthService` requires PHP **8.3+** and these extensions: `mbstring`,
`openssl`, `pdo`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`.
`gd` and `zip` are not in that list but are needed for thumbnails/WebP/AVIF and
for backup archives.

```sh
mysql -e "CREATE DATABASE lindu CHARACTER SET utf8mb4;
          CREATE USER 'lindu'@'localhost' IDENTIFIED BY 'RAHASIA';
          GRANT ALL ON lindu.* TO 'lindu'@'localhost';"
git clone https://github.com/linducip2208/ciptacms /var/www/ciptacms
```

## 2. Environment and install

```sh
cd /var/www/ciptacms
cp .env.production.example .env
nano .env     # APP_URL, DB_*, MAIL_*, and APP_KEY
php artisan key:generate
php artisan migrate --seed --force
php artisan storage:link
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

`.env.production.example` already sets `APP_ENV=production`, `APP_DEBUG=false`,
`SESSION_SECURE_COOKIE=true`, `QUEUE_CONNECTION=database`,
`SEARCH_DRIVER=database` and `TRUSTED_PROXIES=*`. `AppServiceProvider` forces
HTTPS automatically in the `production` environment.

**It does not contain the `LICENSE_*` variables.** They therefore default to
`config/license.php`: `LICENSE_SERVER_URL=https://whitelabel.co.id`,
`LICENSE_DEV_BYPASS=true` (inert outside `local`), and a lock file at
`storage/app/.license.lock`. Add them explicitly if you need a different
marketplace or a heartbeat cadence.

### Default accounts — delete them

`--seed` creates `admin@lindu.local`, `manager@lindu.local` and
`member1..5@lindu.local`, all with `password123`. Rotate or delete them before
the domain resolves.

```sh
php artisan tinker
>>> App\Models\User::where('email','like','%@lindu.local')->delete();
```

## 3. Web server

Docroot **must** be `/var/www/ciptacms/public`.

```sh
cp deploy/nginx-ciptacms.conf /etc/nginx/sites-enabled/
# or: cp deploy/apache-ciptacms.conf /etc/apache2/sites-available/
nginx -t && systemctl reload nginx
```

Nginx: edit the `server_name` and `root`, then `nginx -t` before reloading — a
bad config takes the whole site down and `systemctl reload nginx` will not tell
you until it has already done it.

Apache: `a2ensite ciptacms && systemctl reload apache2`. **Do not carry over a
global `ProxyPass` to a Node backend** — that is what caused the local 503 in
[TROUBLESHOOTING.md](TROUBLESHOOTING.md).

```sh
certbot --nginx -d domain.com     # or --apache
```

## 4. Queue, scheduler, backups

```sh
cp deploy/supervisor-lindu-queue.conf /etc/supervisor/conf.d/
supervisorctl reread && supervisorctl update
```

That config runs **2** `queue:work` processes as `www-data`, logging to
`storage/logs/queue.log`. Webhooks, the media pipeline and the notification
deliveries all depend on it.

```sh
crontab -e
# * * * * * cd /var/www/ciptacms && php artisan schedule:run >> /dev/null 2>&1
# 0 2 * * * cd /var/www/ciptacms && php artisan lindu:backup --type=full >> /dev/null 2>&1
```

The scheduler already runs `lindu:backup --type=full` daily, so the second cron
line is only needed if you want a different time. `schedule:run` also covers
`lindu:check-updates` (daily), `webhooks:retry` (every 5 min) and
`queue:prune-failed --hours=72` (daily).

**A backup is not a backup until it is off the box.** `BackupService` writes to
the `local` disk under `backups/`, keeping
`setting('storage.keep_backups', 7)` archives. Copy them somewhere else.

## 5. Deploy

### Manual

```sh
BRANCH=main bash deploy/deploy.sh
```

The script does exactly seven steps: `git fetch` + `reset --hard`,
`artisan down --render=errors.503 --retry=60`, `composer install --no-dev`,
`migrate --force`, `storage:link` + `config:cache` + `route:cache` +
`view:cache`, `chown`/`chmod` on `storage` and `bootstrap/cache`, then
`supervisorctl restart lindu-queue:*`, `queue:restart`, `artisan up` and
`lindu:doctor`.

Every cache and queue step is `|| true`, so a failure is reported by the later
`lindu:doctor` rather than aborting the run.

### CI

`.github/workflows/deploy.yml` runs on push to `main`: tests first, then an SSH
deploy to the VPS. Fill in the secrets `VPS_HOST`, `VPS_USER` and
`VPS_SSH_KEY`.

### By hand

```sh
php artisan down
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
php artisan up
php artisan lindu:doctor
```

Route and config caching means **new routes, new config keys and new Blade
components are not picked up until `cache:clear` / `optimize:clear`**. The most
common "I deployed it and nothing changed" is a stale route cache.

## 6. The in-app update centre

`/admin/updates` can take a backup, run migrations, clear caches and record a
version. **It never downloads or runs code** — see
[UPDATES.md](UPDATES.md). Shipping a release stays a `git pull` +
`composer install` + `migrate`, which is what `deploy.sh` does.

## 7. Post-deploy checklist

- [ ] `php artisan lindu:doctor` — every check OK
- [ ] `curl -I https://domain.tld/up` returns 200 (`/up` bypasses the pairing gate)
- [ ] `https://domain.tld/` returns 200 and is **not** a redirect to `/__pair`
- [ ] `supervisorctl status` shows `lindu-queue` running
- [ ] `crontab -l` contains the `schedule:run` line
- [ ] `storage/` and `bootstrap/cache/` are owned by `www-data`
- [ ] `storage/framework/maintenance.php` does **not** exist
- [ ] TLS certificate issued and auto-renewing (`certbot renew --dry-run`)
- [ ] Seeded `@lindu.local` accounts removed
- [ ] An off-box copy of the latest backup exists

## 8. 503 checklist

- `php artisan up` — confirm maintenance mode is off
- confirm the docroot is `public/`, not the project root
- confirm PHP 8.3-FPM is the handler, and read `error_log` **before** assuming
  the application is at fault: Apache's own
  "temporarily unable … maintenance downtime or capacity problems" page is a
  *server* error, not Lindu code
- if you are on Laragon locally, see
  [TROUBLESHOOTING.md](TROUBLESHOOTING.md)

## See also

[INSTALL.md](INSTALL.md), [UPDATES.md](UPDATES.md), [SECURITY.md](SECURITY.md),
[LICENSE.md](LICENSE.md), [TROUBLESHOOTING.md](TROUBLESHOOTING.md).
