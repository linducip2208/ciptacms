# Lindu CMS

Commercial, white-label company CMS on **Laravel 13 + PHP 8.3 + MySQL/SQLite**.
Core lives in `app/Core`, business modules in `modules/`.

## Commands

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve            # admin@lindu.local / password123
```

```sh
php artisan test             # suite (not green — see DEVELOPMENT.md)
php artisan lindu:doctor     # PHP, extensions, writability, DB, queue
php tools/blade-lint.php     # compiles every .blade.php and parses the result
php tools/route-lint.php     # every route() name exists
composer check               # both linters + the test suite
```

`* * * * * php artisan schedule:run` for the scheduler; `php artisan queue:work`
for webhooks and media processing.

## Layout

| Path | What |
|---|---|
| `app/Core/Services/` | 28 services, all singletons |
| `app/Core/Contracts/` | `PaymentGateway`, `LicenseProvider` — the pluggable seams |
| `app/Models/` | 112 models; `Cp\*` is the company-profile set |
| `config/lindu_admin.php` | 28 generic resources → admin CRUD **and** REST API |
| `modules/`, `plugins/`, `themes/` | folder + manifest extensions |
| `database/migrations/` | 21 files, 126 tables |

## Rules for agents and contributors

1. **Read the source before you change it or document it.** A feature claim that
   cannot be traced to a file, a method and a line is not a feature.
2. **Never build a class name from a request value.** Resolve `{resource}`,
   field types, workflow models and physical table names through a whitelist —
   see `CompanyProfileController::RESOURCES`,
   `WorkflowEngine::WHITELISTED_MODELS`, `DataBuilderService`.
3. **Only modify files you own.** Do not reformat or "tidy" code outside the
   scope of the task.
4. **Wrap external I/O in `try/catch` + `report($e)`.** The public site must
   survive a missing marketplace, search index or `cp_*` table.
5. **Do not use `Cache::flush()`.** `MenuService::forget()` does; new code should
   `Cache::forget()` a specific key.
6. **Run the linters and the tests before you call it done**, and read the
   failures rather than assuming they are pre-existing.

## Docs

Start at [ARCHITECTURE.md](ARCHITECTURE.md). Then
[INSTALL.md](INSTALL.md) · [DEVELOPMENT.md](DEVELOPMENT.md) ·
[DEPLOYMENT.md](DEPLOYMENT.md) ·
[PAGE_BUILDER.md](PAGE_BUILDER.md) · [FORM_BUILDER.md](FORM_BUILDER.md) ·
[DATA_BUILDER.md](DATA_BUILDER.md) · [MENU_ENGINE.md](MENU_ENGINE.md) ·
[WORKFLOW.md](WORKFLOW.md) · [COMPANY_PROFILE.md](COMPANY_PROFILE.md) ·
[WHITE_LABEL.md](WHITE_LABEL.md) · [UPDATES.md](UPDATES.md) ·
[LICENSE.md](LICENSE.md) · [API.md](API.md) · [DATABASE.md](DATABASE.md) ·
[MODULES.md](MODULES.md) · [PLUGINS.md](PLUGINS.md) · [THEMES.md](THEMES.md) ·
[SAAS.md](SAAS.md) · [SECURITY.md](SECURITY.md) ·
[TROUBLESHOOTING.md](TROUBLESHOOTING.md)

Several docs have a **"Known gaps"** section listing what the code does *not* do.
Read those before quoting a feature to a customer.
