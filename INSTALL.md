# Install

## Requirements
PHP 8.3+, MySQL 8+ (or SQLite for dev), Composer, Node optional.

## Steps
1. `composer install`
2. `cp .env.example .env` + configure DB
3. `php artisan key:generate`
4. `php artisan migrate --seed` or open `/install` wizard
5. `php artisan serve`

## Queue/Scheduler
`php artisan queue:work` · cron: `* * * * * php artisan schedule:run`

## Storage
`php artisan storage:link`
