# Architecture

```
LINDU = Core + CMS + Auth + RBAC + Menu + Module + Plugin + Theme + Builders + API + Webhook + Media + SEO + Tenancy + SaaS + License + Update + Backup + Apps
```

- `app/Core/Services/*`: Setting, Menu, ModuleManager, PluginManager, ThemeManager, SEO, Media, Search, Tenant, License, Backup, Update, Workflow, WebhookDispatcher, ImportExport, DataBuilder, Health.
- `app/Models/*`: ~80 models, tenant-aware where relevant.
- `modules/*`: independent business apps. `plugins/*`: extensions. `themes/*`: presentation.
- Generic `ResourceController` + `ResourceApiController` driven by `config/lindu_admin.php`.
- Policies via `hasPermission()`; middleware `permission:`.
