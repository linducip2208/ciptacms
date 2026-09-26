# Development

- `php artisan serve`, `php artisan test`, `php artisan lindu:install`, `php artisan lindu:backup`, `php artisan webhooks:retry`
- Add resource: add model + migration + entry in `config/lindu_admin.php` → CRUD + API instant.
- Add module: copy `modules/blog/` skeleton, edit `module.json`.
- Conventions: thin controllers, services in `app/Core/Services`, FormRequests for complex validation, events → WorkflowEngine.
