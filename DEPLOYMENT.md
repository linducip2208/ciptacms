# Deploy VPS Produksi

## 1. Siapkan server (Ubuntu 22.04/24.04)
```sh
apt update && apt install -y php8.3 php8.3-{cli,fpm,mysql,mbstring,xml,bcmath,gd,curl,zip} composer mysql-server nginx supervisor certbot python3-certbot-nginx
mysql -e "CREATE DATABASE lindu CHARACTER SET utf8mb4; CREATE USER 'lindu'@'localhost' IDENTIFIED BY 'RAHASIA'; GRANT ALL ON lindu.* TO 'lindu'@'localhost';"
git clone https://github.com/linducip2208/ciptacms /var/www/ciptacms
```

## 2. Env + install
```sh
cd /var/www/ciptacms
cp .env.production.example .env
nano .env   # isi APP_KEY (generate dulu), DB_PASSWORD, APP_URL, MAIL_*
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed --force
php artisan storage:link
chown -R www-data:www-data storage bootstrap/cache && chmod -R 775 storage bootstrap/cache
```

## 3. Web server
- Nginx: salin `deploy/nginx-ciptacms.conf` ke `/etc/nginx/sites-enabled/`, ganti domain, `nginx -t && systemctl reload nginx`.
- Apache: salin `deploy/apache-ciptacms.conf`, `a2ensite ciptacms && systemctl reload apache2`. **Jangan bawa file ProxyPass Node global** (pelajaran 503 lokal).
- HTTPS: `certbot --nginx -d domain.com` (atau `--apache`).

## 4. Queue + scheduler + backup
```sh
cp deploy/supervisor-lindu-queue.conf /etc/supervisor/conf.d/ && supervisorctl reread && supervisorctl update
crontab -e
# * * * * * cd /var/www/ciptacms && php artisan schedule:run >> /dev/null 2>&1
# 0 2 * * * cd /var/www/ciptacms && php artisan lindu:backup --type=full >> /dev/null 2>&1
```

## 5. Deploy rutin / CI
- Manual: `BRANCH=main bash deploy/deploy.sh` (down → pull → composer → migrate → cache → permission → queue restart → up → doctor).
- Otomatis: isi GitHub Secrets `VPS_HOST`, `VPS_USER`, `VPS_SSH_KEY` → push ke main jalanin `.github/workflows/deploy.yml` (test dulu, baru SSH deploy).

## 6. Checklist 503 (jangan ulangi kasus lokal)
- `php artisan up`, pastikan tidak ada `storage/framework/maintenance.php`
- Docroot HARUS `.../public`, PHP 8.3-FPM, `error_log` dicek dulu sebelum panik
