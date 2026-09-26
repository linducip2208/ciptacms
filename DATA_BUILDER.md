# Data Builder

Define a content type (a schema of fields) at runtime, then store records
against it. Records live as JSON in `content_records`; the same values are
mirrored into a real `cb_*` table so you can report on them with plain SQL.

- Models: `app/Models/ContentType.php`, `app/Models/ContentRecord.php`
- Schema mirroring: `app/Core/Services/DataBuilderService.php` (`COLUMN_MAP`)
- Admin: `app/Http/Controllers/Admin/DataBuilderController.php` (`FIELD_TYPES`, `RELATION_TYPES`)
- Import / export: `app/Core/Services/ImportExportService.php`
- API: `Api\V1\ContentTypeApiController` (only types with `is_api_enabled`)
- Admin routes: `admin.cms.types.*`, `admin.cms.records.*`, `admin.cms.import`, `admin.cms.export`

## The JSON record is the source of truth

`content_records.data` is a JSON object keyed by **field slug**. Everything —
the admin list, the admin form, the API, the CSV/JSON round trip — reads and
writes that object. `content_records` also carries `uuid`, `status` and
`softDeletes`.

The physical `cb_*` table is a **convenience mirror for reporting**. Every write
path wraps the mirror in `try { … } catch (\Throwable) { report($e); }` and never
fails the request because of it (`DataBuilderService::syncRecord()`,
`DataBuilderController::syncPhysical()`). If the mirror and the JSON record ever
disagree, the JSON record is the truth and the mirror needs a rebuild.

## Content types

A `ContentType` row holds `name`, `slug`, `description`, `icon`, `table_name`,
`is_api_enabled` and `fields` (JSON array of field definitions, cast to `array`).

Each field definition is:

```json
{
  "name": "Headline",
  "slug": "headline",
  "label": "Headline",
  "type": "text",
  "required": true,
  "unique": false,
  "options": [],
  "help": null,
  "default": null
}
```

`slug` is `Str::snake()`d on save and is the JSON key. `options` accepts a JSON
array, a newline list, or `value|label` lines (`parseOptions()`).

`DataBuilderController::storeField()` / `updateField()` append to the array and
then call `DataBuilderService::syncPhysicalTable()`, so declaring a field also
creates its column.

## Field types

`DataBuilderController::FIELD_TYPES` (17 entries) is what the admin UI offers
and what `type` is validated against. `kind` drives the validation rule;
`column` records the intended physical column.

| Type | `kind` | Physical column |
|---|---|---|
| `text`, `longtext`, `richtext` | `string` | `text` |
| `number` | `number` | `integer` |
| `decimal` | `number` | `decimal(15,2)` |
| `boolean` | `boolean` | `boolean` (default `false`) |
| `date` | `string` | `date` |
| `datetime` | `string` | `dateTime` |
| `email`, `url`, `image`, `file`, `select`, `relation` | `string` | `string` |
| `multiselect` | `array` | `json` |
| `json`, `repeater` | `object` | `json` |

`DataBuilderService::COLUMN_MAP` is the authoritative type → column map used when
the physical table is built; it has the same 17 keys. `fieldMap()` returns the
column kind, defaulting to `text` for an unknown type.

## Validation

`DataBuilderController::validateRecord()` walks the type's fields and builds one
rule per field, then collects all errors before throwing a single
`ValidationException` — so the operator sees every bad field at once, not the
first one.

- base: `required` when `field.required`, else `nullable`
- by `kind`: `|numeric` (number), `|boolean` (boolean), `|array` (array/object),
  `|string` (everything else)
- `email` type adds `|email`; `url` type adds `|url`; `date`/`datetime` add `|date`
- `field.unique` adds
  `|unique:content_records,data->{slug}` — and, on update, `,{recordId}` so the
  record does not collide with itself. **This is a real JSON-column uniqueness
  constraint; it works on MySQL and SQLite and is the one place `unique` is
  enforced.**
- `boolean` values are read with `$r->boolean('data[slug]', false)`, so an
  unchecked box is `false`, not absent.

Only validated values are stored. A field that is not declared on the content
type is never written.

## Records

| Operation | Route | Notes |
|---|---|---|
| List | `GET admin/cms.records.list` | `paginate(25)`; `?status=` filter; `?search=` searches **only the first declared field** via `where('data->'.$primary, 'like', …)` |
| Create | `POST admin.cms.records.store` | status defaults to `published` |
| Update | `PUT admin.cms.records.update` | |
| Delete | `DELETE admin.cms.records.destroy` | soft delete → "moved to trash" |
| Bulk | `POST admin.cms.records.bulk` | `publish` / `draft` / `delete` on a list of ids |

The record list shows the first 5 fields as columns.

After any create / update / delete, `afterRecord()` fires (each in its own
`try/catch`):

- `event('record.{created|updated|deleted}', …)`
- `WebhookDispatcher::dispatchEvent('record.{created|updated|deleted}', …)`
  with `content_type` and `record`
- `WorkflowEngine::trigger('record.{created|updated|deleted}', ['content_type',
  'data', 'id'])` — see [WORKFLOW.md](WORKFLOW.md)

## The physical `cb_*` table

`DataBuilderService::safeTableName()` is `content_types.table_name` when set,
otherwise `'cb_'.strtolower(slug)` sanitised to `[a-z0-9_]`, truncated to 40
characters, with a `content` fallback. `cb_` is the namespace.

`syncPhysicalTable()`:

1. creates the table if missing, with `id`, unique `uuid`, nullable indexed
   `tenant_id`, timestamps and `softDeletes`;
2. adds any declared column that does not exist yet, per `COLUMN_MAP`;
3. writes the resolved name back to `content_types.table_name`;
4. returns the table name **only if it created the table**, otherwise `null`.

Column names are validated against `^[a-z][a-z0-9_]*$` after `Str::snake()`;
anything that fails is skipped rather than injected into a `Blueprint`.

`removeColumn()` drops a column when a field is deleted — but only after the same
name check and an `hasColumn()` guard.

`dropPhysicalTable()` refuses to drop anything that does not start with `cb_`:

```php
if (! str_starts_with($table, 'cb_') || ! Schema::hasTable($table)) {
    return;
}
```

`DataBuilderController::destroy()` refuses to delete a content type that still
has records ("Delete or archive the records first") and drops the `cb_*` table
only on that path.

`syncRecord()` writes `uuid` and `tenant_id` plus one column per field, JSON-
encoding `multiselect` / `json` / `repeater` values and casting `boolean` to
`1`/`0`. It upserts on `id`, so re-syncing a record is idempotent.
`physicalRows($ct, 100)` returns raw rows for reporting.

### What the mirror does not do

- It is a flat projection. There are **no indexes** beyond the primary key,
  `uuid` and `tenant_id`.
- It is **not** created by a migration. If a content type exists but no `cb_*`
  table was ever synced, the admin still works — the mirror calls just no-op.
- There is **no rebuild/repair command**. The only way to regenerate it is to
  re-save each record (or add one yourself).

## Import / export

`ImportExportService` is the CSV/JSON path for content-type records.

### Export

`GET admin.cms.export.run?content_type_id=…&format=csv|json&status=…`

- **CSV** — header is `_id, _status, _created_at` followed by one column per
  declared field slug. Array values are joined with `|`. The `_`-prefixed
  columns are the record's own metadata; field slugs that start with `_` would
  collide, which is why slugs are `Str::snake()`d and validated.
- **JSON** — `{"content_type": "<slug>", "count": N, "data": [ {…fields,
  _id, _status, _created_at}, … ]}`.
- Filename: `{slug}-{Ymd-His}.{ext}`.

### Import

`POST admin.cms.import` with `content_type_id`, `file` (max 20 MB),
`format` (`csv` | `json`), `mode` (`insert` | `update`), optional `key_field`.

Imports are **row-at-a-time and never abort halfway**. Every rejected row is
returned as `['line' => int, 'reason' => string, 'row' => array]` and surfaced
in the flash data as `import_errors`; the summary is
`"Imported N row(s), M failed."`.

- CSV parsing strips a UTF-8 BOM, snake_cases the header row, and **skips any
  row whose column count does not match the header** — a ragged file loses rows
  silently, with no error entry.
- JSON input may be a bare array of objects or `{"data":[…]}`.
- Values are matched to fields by slug, then by `Str::snake(str_replace(' ',
  '_', slug))` — so a header of `First Name` still reaches a `first_name` field.
- `castValue()` coerces by type: numeric for `number`/`decimal`; boolean for
  `true|1|yes|y|on`; pipe-split array for `multiselect`; JSON (or the raw
  string) for `json` / `repeater`; string otherwise.
- Unknown columns are dropped, so a wide export round-trips back in.
- A row missing a `required` field is rejected with
  `Missing required: <names>`.
- `mode = update` + `key_field`: an existing record with the same
  `data->{key_field}` value is merged (`array_merge`) and counted as `updated`;
  otherwise the row is inserted as a `draft`. `mode = insert` inserts as
  `published`.

**Note:** the import path does **not** run `DataBuilderController::validateRecord()`
and does **not** mirror to the `cb_*` table. Imported rows bypass the type rules
and the physical mirror.

`ImportExportService` also has the generic model helpers used by the resource
controller: `export($model, $filters)` (up to 5000 rows), `toCsv()` and
`downloadModel()` (`GET admin.resource.export`).

## API

`Api\V1\ContentTypeApiController` exposes a content type over the API, but
**only when `is_api_enabled` is true** — otherwise `type()` returns 404.

```
GET    /api/v1/content-types
GET    /api/v1/content-types/{slug}
GET    /api/v1/content-types/{slug}/records
POST   /api/v1/content-types/{slug}/records
GET    /api/v1/content-types/{slug}/records/{id}
PUT    /api/v1/content-types/{slug}/records/{id}
DELETE /api/v1/content-types/{slug}/records/{id}
```

Record writes run through the **same** validation as the admin UI, so the API
cannot bypass it, and `mirror()` re-syncs the `cb_*` table after a write inside a
`try/catch`. All of it is behind `auth:sanctum` — see [API.md](API.md).

## Relations

`DataBuilderController::RELATION_TYPES` lists six relation kinds:

```php
['hasOne', 'hasMany', 'belongsTo', 'belongsToMany', 'morphOne', 'morphMany']
```

They are offered by the `admin.cms.types.relations` screen and a `relation` field
type exists, whose physical column is `string(190)`.

**Not implemented yet:** there is no controller method, route, model trait or
Eloquent relationship behind those choices. Nothing reads or writes a relation;
a `relation` field stores an opaque key in the JSON record and a `string(190)`
column. Treat the relations screen as a UI seam only.

## Deleting safely

```php
// Content types: only when the type has no records, and only inside cb_*
$ct->records()->exists()  // guard in DataBuilderController::destroy()

// Columns: name-checked, existence-checked, wrapped in try/catch
app(DataBuilderService::class)->removeColumn($ct, 'old_field');
```

The `cb_*` prefix check in `dropPhysicalTable()` is the only hard boundary in
this subsystem. Everything else is guarded by convention.

See also: [WORKFLOW.md](WORKFLOW.md), [API.md](API.md), [DATABASE.md](DATABASE.md).
