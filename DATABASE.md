# Database

**126 tables** created by the 21 migrations in `database/migrations/`, plus any
`cb_*` tables the [data builder](DATA_BUILDER.md) generates at runtime.

## Conventions

- `id` big-increment primary key, timestamps on every table.
- **`tenants` is the exception**: `tenants.id` is a `string` primary key holding a
  UUID (`SaasController::storeTenant()` writes `Str::uuid()` into both `id` and
  `uuid`). Every other table that references a tenant uses a nullable, indexed
  `tenant_id` string.
- `uuid` — a second `uuid` column, nullable and unique, on `content_records`,
  `menu_items`, `tenants` and each `cb_*` table. `content_records` and
  `page_revisions` are the public-facing `uuid` consumers.
- Soft deletes on content and commerce tables. The 12 `cp_*` tables all use
  `SoftDeletes` through their models, as do `pages`, `posts`, `comments`,
  `form_fields`, `reusable_blocks`, `page_templates` and `content_records`.
- `settings` has a `unique` `key` and no `uuid` — it is a key/value table, not
  an entity.
- Pivot tables: `role_user`, `permission_role`, plus `tenant_user` (a modelled
  pivot with a `role_id`).
- `location`, `status`, `event`, `metric` columns are `string` + `index`, not
  native enums. Status vocabularies live in PHP constants, not the schema.

## Migration files

| File | Tables |
|---|---|
| `0001_01_01_000000_create_users_table.php` | 3 |
| `0001_01_01_000001_create_cache_table.php` | 2 |
| `0001_01_01_000002_create_jobs_table.php` | 3 |
| `2026_09_26_045641_create_personal_access_tokens_table.php` | 1 |
| `2026_09_26_100000_create_rbac_tables.php` | 5 |
| `2026_09_26_100001_create_platform_tables.php` | 7 |
| `2026_09_26_100002_create_cms_tables.php` | 12 |
| `2026_09_26_100003_create_builder_tables.php` | 11 |
| `2026_09_26_100004_create_tenancy_tables.php` | 13 |
| `2026_09_26_100005_create_commerce_tables.php` | 17 |
| `2026_09_26_100006_create_apps_tables.php` | 17 |
| `2026_09_26_100007_create_social_tables.php` | 9 |
| `2026_09_27_100009_media_2fa_hardening.php` | alters existing |
| `2026_09_27_100010_social_accounts.php` | 1 || `2026_09_28_100020_create_company_profile_tables.php` | 12 (`cp_*`) |
| `2026_09_28_100021_create_platform_gap_tables.php` | 11 |
| `2026_09_28_100022_extend_cms_columns.php` | alters existing |
| `2026_09_28_100023_create_incoming_webhook_tables.php` | 2 |
| `2026_09_28_100024_extend_form_field_columns.php` | alters existing |
| `2026_09_28_100025_align_page_builder_columns.php` | alters existing |

The `extend_*` and `align_*` migrations are late `Schema::hasColumn()`-guarded
patches. `2026_09_28_100025` is worth reading if you touch the builder: the
original schema gave `reusable_blocks` a `blocks` column and no activation flag,
and `page_templates` a `blocks` column with no active flag, while the builder
writes `data` and `structure`. It adds the real columns and backfills from the
old ones — without it every insert silently discarded the layout.

## Notable tables

### CMS
`pages`, `page_revisions`, `page_templates`, `reusable_blocks`, `posts`,
`categories`, `tags`, `post_tag`, `comments`, `media_folders`, `media_files`,
`seo_meta`.

`seo_redirects`, `comment_reports` and `word_filters` are added later by
`2026_09_28_100021`. There is **no `post_category` pivot** — a post's category is
a nullable `category_id` on `posts`. There is also **no `notifications` table**;
`NotificationController` works from `notification_templates` and
`notification_deliveries`.

### Builders
`forms`, `form_fields`, `form_submissions`, `content_types`, `content_records`,
`workflows`, `workflow_runs`, `form_spam_settings`, `word_filters`.

`form_fields` carries `validation` and `conditional` JSON columns and an
`is_unique` boolean — all three are stored but never read. See
[FORM_BUILDER.md](FORM_BUILDER.md).

### Platform
`settings`, `menu_items`, `modules`, `plugins`, `themes`, `languages`,
`translations`, `dashboard_widgets`, `activity_logs`, `audit_logs`,
`login_histories`, `widgets`, `appearance_options`, `white_label_domains`,
`notification_templates`, `notification_deliveries`, `webhooks`, `webhook_logs`,
`webhook_incoming_keys`, `webhook_incoming_logs`.

`appearance_options` is `unique(['group','key'])` and holds the theme-customizer
values (group `customizer`) — see [WHITE_LABEL.md](WHITE_LABEL.md).

### Tenancy, licensing, updates
`tenants`, `tenant_domains`, `tenant_user`, `tenant_usages`, `plans`,
`plan_features`, `subscriptions`, `licenses`, `license_activations`,
`update_logs`, `backups`.

### Commerce, apps and social
`products`, `product_categories`, `product_variants`, `customers`, `suppliers`,
`orders`, `order_items`, `payments`, `coupons`, `inventories`, `stock_movements`,
`expenses`, `sales`, `sale_items`, `purchases`, `reviews`, `wishlists`.

Hotel: `properties`, `room_types`, `rooms`, `guests`, `reservations`,
`housekeeping_tasks`.
LMS: `courses`, `lessons`, `enrollments`, `quizzes`, `certificates`.
CRM: `leads`, `companies`, `contacts`, `pipelines`, `deals`, `crm_activities`.
Social/marketplace: `member_profiles`, `member_preferences`, `member_likes`,
`member_favorites`, `member_blocks`, `member_reports`, `memberships`, `vendors`,
`vendor_payouts`.

**These are schema and generic-CRUD only.** The matching models get admin CRUD
through `config/lindu_admin.php`, but there is no POS transaction, no room
availability check, no quiz runner and no marketplace checkout. The
`pos`, `hotel`, `lms`, `crm`, `erp`, `marketplace` and `jodohku` folders under
`modules/` are manifest stubs — see [MODULES.md](MODULES.md).

Note two naming details that catch people out: the inventory table is
**`inventories`**, and the member table is **`member_profiles`**, not `members`.

## The `cb_*` mirror

`DataBuilderService` can create a real table per content type, named
`cb_{slug}` (sanitised to `[a-z0-9_]`, truncated to 40 chars, `content` as the
fallback). It always has `id`, unique `uuid`, nullable indexed `tenant_id`,
timestamps and `softDeletes`, plus one column per declared field.

**The JSON record in `content_records.data` is the source of truth.** The
physical table is a reporting convenience, written inside `try/catch`, and a
mirror failure never fails a request. `dropPhysicalTable()` refuses any name that
does not start with `cb_`. No migration creates these tables and no command
rebuilds them — see [DATA_BUILDER.md](DATA_BUILDER.md).

## Drivers

`BackupService::dumpDatabase()` handles two drivers:

- **MySQL / MariaDB** — `SHOW TABLES` + `SHOW CREATE TABLE`, then
  `INSERT INTO` per row with PDO-quoted values, wrapped in
  `SET FOREIGN_KEY_CHECKS=0/1`.
- **SQLite** — `sqlite_master` for structure, `SELECT *` per table, string
  literals, wrapped in `PRAGMA foreign_keys=OFF` / `BEGIN` / `COMMIT`.

Restore splits the dump on `";\n"` and runs each statement with
`DB::unprepared()` (MySQL) or hands the file to a `PDO` subprocess (SQLite). A
statement that fails is recorded and the restore continues, returning
`ok: false` with the log.

`.env.example` ships `DB_CONNECTION=sqlite`, which is why
`database/database.sqlite` is the zero-config dev path. See
[INSTALL.md](INSTALL.md).

## Conventions for a new migration

1. Name it `YYYY_MM_DD_HHMMSS_description.php`, return an anonymous class.
2. Guard with `Schema::hasTable()` / `Schema::hasColumn()` when you are patching
   an existing table — the late migrations all do.
3. Add a `down()`.
4. `php artisan migrate --seed`, then `php artisan test`.

Do not add a table for something a [data builder](DATA_BUILDER.md) content type
can express — a `cb_*` table plus a JSON record is cheaper to change than a
migration, and the operator can add a field from the admin.

## See also

[DATA_BUILDER.md](DATA_BUILDER.md), [COMPANY_PROFILE.md](COMPANY_PROFILE.md),
[ARCHITECTURE.md](ARCHITECTURE.md), [UPDATES.md](UPDATES.md) (backup).
