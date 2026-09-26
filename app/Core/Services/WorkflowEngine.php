<?php

namespace App\Core\Services;

use App\Models\ContentRecord;
use App\Models\ContentType;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/**
 * Trigger → condition → action engine.
 *
 * Models an action may touch are restricted to WHITELISTED_MODELS. A
 * workflow definition is data, not code: it must never be able to name an
 * arbitrary class and have Eloquent instantiate it.
 */
class WorkflowEngine
{
    public const TRIGGERS = [
        'page.created' => 'Page created',
        'page.updated' => 'Page updated',
        'page.deleted' => 'Page deleted',
        'post.created' => 'Post created',
        'post.updated' => 'Post updated',
        'post.deleted' => 'Post deleted',
        'post.published' => 'Post published',
        'comment.created' => 'Comment submitted',
        'form.submitted' => 'Form submitted',
        'contact.message' => 'Contact form message',
        'job.application' => 'Job application received',
        'user.registered' => 'User registered',
        'record.created' => 'Content record created',
        'record.updated' => 'Content record updated',
        'record.deleted' => 'Content record deleted',
        'schedule' => 'Scheduled run',
        'webhook' => 'Incoming webhook',
    ];

    public const CONDITIONS = [
        'equals' => 'is equal to',
        'not_equals' => 'is not equal to',
        'contains' => 'contains',
        'not_contains' => 'does not contain',
        'greater_than' => 'is greater than',
        'greater_or_equal' => 'is greater than or equal to',
        'less_than' => 'is less than',
        'less_or_equal' => 'is less than or equal to',
        'status' => 'has status',
        'role' => 'user has role',
        'date_before' => 'date is before',
        'date_after' => 'date is after',
        'in_list' => 'is one of',
        'boolean' => 'boolean is',
    ];

    public const ACTIONS = [
        'send_email' => 'Send email',
        'send_notification' => 'Send notification',
        'send_webhook' => 'Send webhook',
        'send_http' => 'Send HTTP request',
        'create_record' => 'Create record',
        'update_record' => 'Update record',
        'delete_record' => 'Delete record',
        'create_task' => 'Create task',
        'update_setting' => 'Update setting',
    ];

    /** Models a workflow action is permitted to write to. */
    public const WHITELISTED_MODELS = [
        ContentRecord::class,
        \App\Models\ContentType::class,
    ];

    public function trigger(string $event, array $payload = []): int
    {
        try {
            $flows = Workflow::where('is_active', true)->where('trigger_event', $event)->get();
        } catch (\Throwable $e) {
            return 0;
        }

        $ran = 0;
        foreach ($flows as $f) {
            $this->run($f, $payload);
            $ran++;
        }

        return $ran;
    }

    public function run(Workflow $flow, array $payload = []): WorkflowRun
    {
        $run = WorkflowRun::create([
            'workflow_id' => $flow->id,
            'status' => 'running',
            'payload' => $payload,
            'started_at' => now(),
        ]);

        $log = [];

        try {
            if (! $this->conditionsPass((array) ($flow->conditions ?? []), $payload)) {
                return $run->update(['status' => 'skipped', 'finished_at' => now()])
                    ->fresh();
            }

            foreach ((array) ($flow->actions ?? []) as $i => $action) {
                $outcome = $this->doAction((array) $action, $payload);
                $log[] = '#'.($i + 1).' '.(is_array($action) ? ($action['type'] ?? 'unknown') : 'unknown')
                    .' → '.(is_string($outcome) ? $outcome : 'ok');

                if (is_string($outcome) && str_starts_with($outcome, 'error:')) {
                    return $run->update([
                        'status' => 'failed',
                        'finished_at' => now(),
                        'log' => implode("\n", $log),
                    ])->fresh();
                }
            }

            $run->update(['status' => 'completed', 'finished_at' => now(), 'log' => implode("\n", $log)]);
        } catch (\Throwable $e) {
            $log[] = 'error: '.$e->getMessage();
            $run->update(['status' => 'failed', 'finished_at' => now(), 'log' => implode("\n", $log)]);
        }

        return $run->fresh();
    }

    public function retry(WorkflowRun $run): WorkflowRun
    {
        $flow = Workflow::find($run->workflow_id);
        if (! $flow) {
            return $run->update(['status' => 'abandoned', 'log' => 'Workflow no longer exists.'])->fresh();
        }

        $fresh = $this->run($flow, (array) $run->payload);

        return $run->update([
            'status' => $fresh->status,
            'log' => 'retry → '.$fresh->log,
            'finished_at' => now(),
        ])->fresh();
    }

    protected function conditionsPass(array $conditions, array $payload): bool
    {
        if ($conditions === []) {
            return true;
        }

        foreach ($conditions as $c) {
            $c = (array) $c;
            $field = (string) ($c['field'] ?? '');
            $op = (string) ($c['operator'] ?? 'equals');
            $expected = $c['value'] ?? null;
            $actual = $field === '' ? null : data_get($payload, $field);

            if (! $this->compare($op, $actual, $expected)) {
                return false;
            }
        }

        return true;
    }

    protected function compare(string $op, $actual, $expected): bool
    {
        return match ($op) {
            'equals' => $actual == $expected,
            'not_equals' => $actual != $expected,
            'contains' => str_contains((string) $this->scalar($actual), (string) $this->scalar($expected)),
            'not_contains' => ! str_contains((string) $this->scalar($actual), (string) $this->scalar($expected)),
            'greater_than' => (float) $this->scalar($actual) > (float) $this->scalar($expected),
            'greater_or_equal' => (float) $this->scalar($actual) >= (float) $this->scalar($expected),
            'less_than' => (float) $this->scalar($actual) < (float) $this->scalar($expected),
            'less_or_equal' => (float) $this->scalar($actual) <= (float) $this->scalar($expected),
            'status' => strtolower((string) $this->scalar($actual)) === strtolower((string) $this->scalar($expected)),
            'role' => in_array((string) $this->scalar($expected), (array) $actual, true),
            'boolean' => (bool) $this->scalar($actual) === filter_var($expected, FILTER_VALIDATE_BOOLEAN),
            'in_list' => in_array($actual, (array) $expected),
            'date_before' => $this->date($actual) < $this->date($expected),
            'date_after' => $this->date($actual) > $this->date($expected),
            // An unknown operator must not silently pass everything.
            default => false,
        };
    }

    protected function scalar($value)
    {
        if (is_array($value)) {
            return implode(', ', array_map(fn ($v) => is_scalar($v) ? $v : json_encode($v), $value));
        }

        return $value;
    }

    protected function date($value): int
    {
        if (empty($value)) {
            return 0;
        }
        $ts = is_numeric($value) ? (int) $value : strtotime((string) $value);

        return $ts === false ? 0 : $ts;
    }

    protected function doAction(array $action, array $payload)
    {
        $type = (string) ($action['type'] ?? '');

        $result = match ($type) {
            'send_email' => $this->sendEmail($action),
            'send_notification' => $this->sendNotification($action, $payload),
            'send_webhook', 'send_http' => $this->sendWebhook($action, $payload),
            'create_record' => $this->createRecord($action, $payload),
            'update_record' => $this->updateRecord($action, $payload),
            'delete_record' => $this->deleteRecord($action),
            'create_task' => $this->createTask($action),
            'update_setting' => $this->updateSetting($action),
            default => 'skipped (unknown action type)',
        };

        return $result;
    }

    protected function sendEmail(array $a)
    {
        $to = trim((string) ($a['to'] ?? ''));
        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return 'error: send_email needs a valid "to" address';
        }

        try {
            $service = app(NotificationService::class);
            $service->send([
                'channel' => 'mail',
                'recipient' => $to,
                'subject' => (string) ($a['subject'] ?? 'Workflow notification'),
                'body' => (string) ($a['body'] ?? 'A workflow completed.'),
            ]);

            return 'sent to '.$to;
        } catch (\Throwable $e) {
            return 'error: '.$e->getMessage();
        }
    }

    protected function sendNotification(array $a, array $p)
    {
        return $this->sendEmail($a);
    }

    protected function sendWebhook(array $a, array $payload)
    {
        $url = trim((string) ($a['url'] ?? ''));
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return 'error: send_webhook needs a valid "url"';
        }

        // Only http(s) — blocks file://, gopher:// and similar local access.
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return "error: refusing to call a {$scheme}:// URL";
        }

        try {
            $res = Http::timeout((int) ($a['timeout'] ?? 10))->post($url, (array) ($a['body'] ?? $payload));
            $res->successful() ? 'HTTP '.$res->status() : 'error: HTTP '.$res->status();
        } catch (\Throwable $e) {
            return 'error: '.$e->getMessage();
        }
    }

    protected function resolveModel(array $a): ?string
    {
        $model = (string) ($a['model'] ?? '');
        if ($model === '') {
            return null;
        }

        return in_array($model, self::WHITELISTED_MODELS, true) ? $model : null;
    }

    protected function createRecord(array $a, array $p)
    {
        $model = $this->resolveModel($a);
        if (! $model) {
            return 'error: model not permitted (allowed: '.implode(', ', array_map(fn ($m) => class_basename($m), self::WHITELISTED_MODELS)).')';
        }

        $data = (array) ($a['data'] ?? []);
        $row = new $model;
        $row->fill($data);
        $row->save();

        return 'created #'.$row->getKey();
    }

    protected function updateRecord(array $a, array $p)
    {
        $model = $this->resolveModel($a);
        if (! $model) {
            return 'error: model not permitted';
        }
        if (empty($a['id'])) {
            return 'error: update_record needs an "id"';
        }

        $n = $model::where('id', $a['id'])->update((array) ($a['data'] ?? []));

        return "updated {$n} row(s)";
    }

    protected function deleteRecord(array $a)
    {
        $model = $this->resolveModel($a);
        if (! $model) {
            return 'error: model not permitted';
        }
        if (empty($a['id'])) {
            return 'error: delete_record needs an "id"';
        }

        $n = $model::where('id', $a['id'])->delete();

        return "deleted {$n} row(s)";
    }

    protected function createTask(array $a)
    {
        $title = trim((string) ($a['title'] ?? ''));
        if ($title === '') {
            return 'error: create_task needs a "title"';
        }

        $row = \App\Models\Task::firstOrCreate(
            ['title' => $title],
            ['description' => $a['description'] ?? null, 'status' => 'todo', 'due_at' => $a['due_at'] ?? null]
        );

        return 'task #'.$row->getKey();
    }

    protected function updateSetting(array $a)
    {
        $key = trim((string) ($a['key'] ?? ''));
        if ($key === '' || ! preg_match('/^[a-z0-9_]+\.[a-z0-9_]+$/', $key)) {
            return 'error: update_setting needs a "group.key" style key';
        }

        app(SettingService::class)->set($key, $a['value'] ?? null, 'text', explode('.', $key)[0]);

        return 'setting '.$key.' updated';
    }
}
