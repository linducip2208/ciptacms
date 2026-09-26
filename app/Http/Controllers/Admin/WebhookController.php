<?php

namespace App\Http\Controllers\Admin;

use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WebhookController extends AdminController
{
    public function index()
    {
        return view('admin.webhooks.index', [
            'rows' => Webhook::withCount('logs')->orderBy('event')->get(),
            'events' => \App\Http\Controllers\Admin\DeveloperController::EVENTS,
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'name' => 'required|string|max:190',
            'event' => 'required|string|max:190',
            'url' => 'required|url|max:500',
            'headers' => 'nullable',
            'timeout' => 'nullable|integer|between:1,120',
            'is_active' => 'nullable|boolean',
        ]);

        $headers = $this->parseHeaders($data['headers'] ?? '');
        unset($data['headers']);

        $data['headers'] = $headers;
        $data['secret'] = (string) Str::random(48);
        $data['is_active'] = $r->boolean('is_active', true);
        $data['timeout'] = $data['timeout'] ?? (int) setting('webhooks.timeout', 10);

        $w = Webhook::create($data);
        $this->audit('create', $w, $r);

        return back()->with('ok', 'Webhook created. Copy the signing secret from the edit dialog.');
    }

    public function update(Request $r, Webhook $webhook)
    {
        $data = $r->validate([
            'name' => 'required|string|max:190',
            'event' => 'required|string|max:190',
            'url' => 'required|url|max:500',
            'headers' => 'nullable',
            'timeout' => 'nullable|integer|between:1,120',
            'is_active' => 'nullable|boolean',
        ]);

        unset($data['headers']);
        $webhook->update($data + [
            'headers' => $this->parseHeaders($r->input('headers', '')),
            'is_active' => $r->boolean('is_active'),
            'timeout' => $data['timeout'] ?? $webhook->timeout,
        ]);

        $this->audit('update', $webhook, $r);

        return back()->with('ok', 'Webhook updated');
    }

    public function destroy(Request $r, Webhook $webhook)
    {
        $webhook->delete();
        $this->audit('delete', $webhook, $r);

        return back()->with('ok', 'Webhook deleted');
    }

    public function rotateSecret(Request $r, Webhook $webhook)
    {
        $webhook->update(['secret' => (string) Str::random(48)]);
        $this->audit('rotate_webhook_secret', $webhook, $r);

        return back()->with('ok', 'Signing secret rotated');
    }

    public function test(Request $r, Webhook $webhook)
    {
        $log = app(\App\Core\Services\WebhookDispatcher::class)->send($webhook, [
            'test' => true,
            'message' => 'Test payload from '.config('lindu.name'),
            'timestamp' => now()->toIso8601String(),
        ]);

        return back()->with($log->status === 'delivered' ? 'ok' : 'error',
            $log->status === 'delivered'
                ? "Delivered (HTTP {$log->response_status})"
                : 'Delivery failed: '.Str::limit((string) $log->error, 200));
    }

    public function logs(Request $r)
    {
        $q = WebhookLog::with('webhook')->latest();
        if ($status = $r->get('status')) {
            $q->where('status', $status);
        }
        if ($id = $r->get('webhook_id')) {
            $q->where('webhook_id', $id);
        }

        return view('admin.webhooks.logs', [
            'rows' => $q->paginate(30)->withQueryString(),
            'status' => $status,
            'webhooks' => Webhook::orderBy('name')->get(),
        ]);
    }

    public function retryLog(Request $r, WebhookLog $log)
    {
        $webhook = $log->webhook;
        if (! $webhook) {
            return back()->withErrors(['msg' => 'The parent webhook no longer exists.']);
        }

        $result = app(\App\Core\Services\WebhookDispatcher::class)->send($webhook, (array) $log->payload);
        $this->audit('retry_webhook', $log, $r);

        return back()->with($result->status === 'delivered' ? 'ok' : 'error',
            $result->status === 'delivered' ? 'Webhook re-delivered' : 'Retry failed: '.$result->error);
    }

    /** Incoming webhook endpoints, keyed by a random path segment. */
    public function incoming()
    {
        return view('admin.webhooks.incoming', [
            'rows' => DB::table('webhook_incoming_keys')
                ->orderByDesc('id')->limit(100)->get(),
        ]);
    }

    public function incomingDetail(Request $r, string $key)
    {
        $row = DB::table('webhook_incoming_keys')->where('key', $key)->first();
        abort_if(! $row, 404, 'Unknown incoming webhook key.');

        return view('admin.webhooks.incoming-detail', [
            'row' => $row,
            'logs' => DB::table('webhook_incoming_logs')
                ->where('key_id', $row->id)
                ->orderByDesc('id')->limit(50)->get(),
        ]);
    }

    protected function parseHeaders($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        $out = [];
        foreach (array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $value))) as $line) {
            if (str_contains($line, ':')) {
                [$k, $v] = explode(':', $line, 2);
                $out[trim($k)] = trim($v);
            }
        }

        return $out;
    }
}
