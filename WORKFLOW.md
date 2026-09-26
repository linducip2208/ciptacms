# Workflow

Trigger → condition → action automation. A workflow is **data** (a row in
`workflows`), never code, and the engine keeps it from touching anything outside
a whitelist.

- Engine: `app/Core/Services/WorkflowEngine.php` (`TRIGGERS`, `CONDITIONS`, `ACTIONS`, `WHITELISTED_MODELS`)
- Models: `app/Models/Workflow.php`, `app/Models/WorkflowRun.php`
- Tables: `workflows`, `workflow_runs`
- Admin routes: `admin.cms.workflows.*` (`routes/admin.php`)
- Admin views: `resources/views/admin/cms/workflows.blade.php`, `workflow-run*.blade.php`
- Reference screens: `GET admin.cms.workflows.triggers` / `.conditions` / `.actions`

## Anatomy of a workflow

A `workflows` row holds `name`, `trigger_event` (one key of `TRIGGERS`),
`conditions` (JSON array), `actions` (JSON array) and `is_active`.

```json
{
  "name": "Notify sales on a qualified enquiry",
  "trigger_event": "form.submitted",
  "conditions": [
    { "field": "data.budget", "operator": "greater_than", "value": 1000000 }
  ],
  "actions": [
    { "type": "send_notification", "to": "sales@example.test", "subject": "New lead", "body": "Budget above 1jt" },
    { "type": "create_task", "title": "Call the new lead", "due_at": "2026-10-01" }
  ]
}
```

## Triggers

`WorkflowEngine::TRIGGERS` — 17 events:

| Group | Events |
|---|---|
| Pages | `page.created`, `page.updated`, `page.deleted` |
| Posts | `post.created`, `post.updated`, `post.deleted`, `post.published` |
| Comments | `comment.created` |
| Forms | `form.submitted` |
| Company | `contact.message`, `job.application` |
| Users | `user.registered` |
| Data builder | `record.created`, `record.updated`, `record.deleted` |
| Manual / system | `schedule`, `webhook` |

`trigger(string $event, array $payload = []): int` loads every `is_active`
workflow whose `trigger_event` matches and runs each one, returning the count. A
failure to read the table returns `0` rather than throwing.

Where the engine is called from:

- `FormRenderer::submit()` → `form.submitted`
- `DataBuilderController::afterRecord()` → `record.{created,updated,deleted}`
- `DeveloperController::dispatchEvent()` (`POST admin.developer.events.dispatch`)
  → any trigger, for manual testing. It fires both the webhooks and the
  workflows, with a JSON payload you type in.
- `schedule` — the `TRIGGERS` constant lists it, but nothing in
  `routes/console.php` or the commands calls `trigger('schedule')`. To run
  scheduled workflows today you have to dispatch the event yourself.

The payload is what conditions read via `data_get()`, and what actions receive.
`form.submitted` sends `['form' => slug, 'data' => [...]]`; the data-builder
events send `['content_type' => slug, 'data' => [...], 'id' => …]`.

## Conditions

`WorkflowEngine::CONDITIONS` — 13 operators, all evaluated by
`compare($op, $actual, $expected)`:

| Operator | Test |
|---|---|
| `equals`, `not_equals` | loose `==` / `!=` |
| `contains`, `not_contains` | `str_contains` on the scalar-ised values (arrays are joined with `, `) |
| `greater_than`, `greater_or_equal`, `less_than`, `less_or_equal` | cast both sides to `float` |
| `status` | case-insensitive string compare |
| `role` | `in_array($expected, (array) $actual, true)` |
| `boolean` | `(bool) $actual` vs `filter_var($expected, FILTER_VALIDATE_BOOLEAN)` |
| `in_list` | `in_array($actual, (array) $expected)` — **non-strict** |
| `date_before`, `date_after` | both sides through `strtotime()` (or a numeric timestamp); unparseable → `0` |

`conditionsPass()` returns `true` immediately when the array is empty (an
unconditional workflow runs), reads the actual value with
`data_get($payload, $field)`, and returns `false` on the **first** failing
condition — conditions are ANDed, and short-circuit.

### The security boundary

Two rules, both deliberate:

1. **An unknown operator evaluates to `false`.**

   ```php
   // An unknown operator must not silently pass everything.
   default => false,
   ```

   A typo in an operator name therefore *blocks* the workflow rather than
   letting it fire unconditionally. The run is recorded as `skipped`, not
   `failed`, so a mistyped operator is easy to miss in the run list — check the
   run log.

2. **A write action may only touch a whitelisted model.**

   ```php
   public const WHITELISTED_MODELS = [
       ContentRecord::class,
       \App\Models\ContentType::class,
   ];
   ```

   `resolveModel()` returns `null` for anything else, and `create_record`,
   `update_record` and `delete_record` all bail out with
   `error: model not permitted …`. A workflow definition is data an operator
   edits in a form; it must never be able to name an arbitrary class and have
   Eloquent instantiate it. **Adding a model to this constant is a deliberate
   act** — anyone who can edit a workflow can then write to that model. Do not
   add `User`, `Role`, `Setting` or a billing model.

Also validated per action, not by the whitelist:

- `update_record` / `delete_record` require an `id`, otherwise
  `error: update_record needs an "id"`.
- `send_email` requires a `FILTER_VALIDATE_EMAIL` recipient.
- `send_webhook` / `send_http` require a `FILTER_VALIDATE_URL` and an
  `http`/`https` scheme. The scheme check is what stops a workflow from reading
  local files or reaching internal services:

  ```php
  $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
  if (! in_array($scheme, ['http', 'https'], true)) {
      return "error: refusing to call a {$scheme}:// URL";
  }
  ```

- `create_task` requires a `title`.
- `update_setting` requires a key matching `^[a-z0-9_]+\.[a-z0-9_]+$` —
  `group.key`, nothing else. It writes through `SettingService::set()` with the
  group taken from the key prefix. **This is a settings write without a
  per-key allowlist**; treat "edit workflows" and "edit settings" as the same
  privilege.

## Actions

`WorkflowEngine::ACTIONS` — 9 types:

| Type | Behaviour |
|---|---|
| `send_email` | `NotificationService::send()` on the `mail` channel. Needs `to`, `subject`, `body`. |
| `send_notification` | currently **delegates to `send_email`** — same requirements, same channel. There is no separate in-app delivery path. |
| `send_webhook` | `Http::timeout(timeout ?? 10)->post(url, body ?? $payload)`. Alias of `send_http`. |
| `send_http` | identical to `send_webhook`. |
| `create_record` | `new $model; fill($data); save()` on a whitelisted model. |
| `update_record` | `$model::where('id', $id)->update($data)`. |
| `delete_record` | `$model::where('id', $id)->delete()`. |
| `create_task` | `Task::firstOrCreate(['title' => …], [status: 'todo', description, due_at])`. First-or-create on the title, so re-running is idempotent. |
| `update_setting` | `SettingService::set("group.key", value, 'text', group)`. |

An unrecognised `type` returns `skipped (unknown action type)` — a non-error
string, so the run still ends up `completed`. That is a second place where a
typo is silent.

## Runs

Every `run()` creates a `workflow_runs` row (`status: running`, `payload`,
`started_at`) and finishes it as one of:

| Status | When |
|---|---|
| `skipped` | conditions did not pass (including every unknown-operator case) |
| `completed` | all actions returned a non-`error:` result |
| `failed` | an action returned `error: …`, or `run()` threw |

The `log` is one line per action: `#<n> <type> → <outcome>`. Run the workflow
again with `POST admin.cms.workflows.runs.retry`; `retry()` re-resolves the
workflow by id and replays the stored `payload`. If the workflow no longer
exists, the run is marked `abandoned`.

View runs at `GET admin.cms.workflows.runs` and a single run at
`GET admin.cms.workflows.runs.show`.

Actions run **synchronously, in order, in the request that triggered them**.
There is no queue and no delay. A `send_http` action with a 10s timeout will hold
the request that fired the workflow.

## Known gaps

- **`schedule` has no runner.** See Triggers above.
- **The incoming webhook fires the wrong event name.**
  `POST /api/v1/webhooks/in/{key}` calls
  `WorkflowEngine::trigger('webhook.received', …)`, but `TRIGGERS` contains
  `webhook`, not `webhook.received`. A workflow registered on `webhook` is
  therefore never triggered by an incoming webhook.
- **HTTP action status is not recorded.** `sendWebhook()` ends with

  ```php
  $res->successful() ? 'HTTP '.$res->status() : 'error: HTTP '.$res->status();
  ```

  — an expression whose value is discarded, so the method returns `null` and the
  run log shows `→ ok` whether the endpoint answered 200 or 500. A failing HTTP
  action will not fail the run. Check the target service, not the run log.
- **No action result is persisted** beyond the one-line log — no per-action
  request/response body, no retry, no per-action idempotency key.
- **`send_notification` is not an in-app notification.** It sends mail.
- **`roles` and `meta` are not read by `compare()` for `role`** — the operator
  works on whatever the payload contains, and most triggers do not put roles in
  it.

See also: [FORM_BUILDER.md](FORM_BUILDER.md), [DATA_BUILDER.md](DATA_BUILDER.md),
[SECURITY.md](SECURITY.md).
