# Deploy

Apache/Nginx/Laragon/VPS/cPanel/Docker. Set `APP_ENV=production`, `php artisan config:cache route:cache view:cache`, `storage:link`, queue worker + scheduler cron, backups via `lindu:backup`.

Docker: see Dockerfile. Compose with mysql:8 + redis.
