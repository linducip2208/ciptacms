#!/usr/bin/env bash
# Deploy Lindu CMS ke VPS. Jalankan di server:  bash deploy.sh
# Prasyarat: PHP 8.3 + ext, composer, mysql, repo sudah di-clone ke /var/www/ciptacms
set -euo pipefail
APP_DIR="/var/www/ciptacms"
BRANCH="${BRANCH:-main}"
cd "$APP_DIR"

echo "== [1/7] git pull $BRANCH =="
git fetch origin && git reset --hard "origin/$BRANCH"

echo "== [2/7] maintenance ON =="
php artisan down --render="errors.503" --retry=60 || true

echo "== [3/7] composer =="
composer install --no-dev --optimize-autoloader --no-interaction

echo "== [4/7] migrate =="
php artisan migrate --force

echo "== [5/7] caches =="
php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "== [6/7] permissions =="
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

echo "== [7/7] queue restart + UP =="
supervisorctl restart lindu-queue:* || true
php artisan queue:restart || true
php artisan up
php artisan lindu:doctor
echo "DEPLOY OK"
