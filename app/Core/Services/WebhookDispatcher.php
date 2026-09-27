<?php

namespace App\Core\Services;

use App\Jobs\DispatchWebhookJob;
use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Outgoing webhooks. Every dispatch is queued, signed with an HMAC and
 * recorded in webhook_logs so a failed delivery can be retried from the UI.
 */
class WebhookDispatcher
{
    public function dispatchEvent(string $event, array $payload = []): void
    {
        try {
            $hooks = Webhook::where('is_active', true)->where('event', $event)->get();
        } catch (\Throwable $e) {
            return;
        }

        foreach ($hooks as $w) {
            $log = $this->log($w, $event, $payload);

            try {
                DispatchWebhookJob::dispatch($w->id, $payload, $log->id);
            } catch (\Throwable $e) {
                // Queue driver unavailable (sync/database misconfigured):
                // deliver inline so the event is not silently lost.
                $this->send($w, $payload, $log);
            }
        }
    }

    public function log(Webhook $hook, string $event, array $payload): WebhookLog
    {
        return WebhookLog::create([
            'webhook_id' => $hook->id,
            'event' => $event,
            'payload' => $payload,
            'status' => 'pending',
        ]);
    }

    public function send(Webhook $hook, array $payload, ?WebhookLog $log = null): WebhookLog
    {
        $log = $log ?: $this->log($hook, (string) ($payload['event'] ?? 'manual'), $payload);

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $timestamp = (string) time();
        $signature = $this->sign($body, (string) $hook->secret, $timestamp);

        try {
            $response = Http::withHeaders(array_merge(
                (array) ($hook->headers ?? []),
                [
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'LinduCMS-Webhook/'.config('lindu.version'),
                    (string) setting('webhooks.signature_header', 'X-Lindu-Signature') => $signature,
                    'X-Lindu-Timestamp' => $timestamp,
                    'X-Lindu-Event' => (string) $log->event,
                    'X-Lindu-Delivery' => (string) $log->id,
                ]
            ))
                ->timeout(max(1, (int) $hook->timeout))
                ->withBody($body, 'application/json')
                ->post($hook->url);

            $status = $response->successful() ? 'delivered' : 'failed';

            $log->update([
                'status' => $status,
                'attempts' => $log->attempts + 1,
                'response' => Str::limit($response->body(), 4000),
                'response_status' => $response->status(),
                'delivered_at' => $response->successful() ? now() : null,
                'error' => $response->successful() ? null : "HTTP {$response->status()}: ".Str::limit($response->body(), 300),
                'next_retry_at' => $response->successful()
                    ? null
                    : now()->addSeconds((int) setting('webhooks.retry_backoff', 60)),
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'attempts' => $log->attempts + 1,
                'error' => Str::limit($e->getMessage(), 400),
                'next_retry_at' => now()->addSeconds((int) setting('webhooks.retry_backoff', 60)),
            ]);
            Log::warning('Webhook delivery failed', ['webhook_id' => $hook->id, 'error' => $e->getMessage()]);
        }

        // Delivery outcome as a first-class event, so extensions (the bundled
        // webhook-logger plugin, or an operator's own) can observe every
        // attempt without touching this service.
        try {
            event('webhook.'.($log->status === 'delivered' ? 'delivered' : 'failed'), [
                'webhook_id' => $hook->id,
                'name' => $hook->name,
                'event' => $log->event,
                'status' => $log->status,
                'http_status' => $log->response_status,
                'attempts' => $log->attempts,
                'error' => $log->error,
                'url' => $hook->url,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        return $log->fresh();
    }

    /**
     * HMAC-SHA256 over "timestamp.body" so a replay with a stale timestamp
     * can be rejected by the receiver.
     */
    public function sign(string $body, string $secret, ?string $timestamp = null): string
    {
        $timestamp = $timestamp ?: (string) time();

        return 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$body, $secret);
    }

    /** Verify an inbound request signed with the same scheme. */
    public function verify(string $body, string $secret, ?string $header, int $tolerance = 300): bool
    {
        if (! $header) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $chunk) {
            [$k, $v] = array_pad(explode('=', $chunk, 2), 2, '');
            $parts[$k] = $v;
        }

        if (! isset($parts['t'], $parts['v1'])) {
            return false;
        }

        if (abs(time() - (int) $parts['t']) > $tolerance) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $parts['t'].'.'.$body, $secret), $parts['v1']);
    }

    /** Process deliveries whose next_retry_at has passed. Run from the scheduler. */
    public function processRetries(int $limit = 50): int
    {
        $logs = WebhookLog::where('status', 'failed')
            ->whereNotNull('next_retry_at')
            ->where('next_retry_at', '<=', now())
            ->orderBy('next_retry_at')
            ->limit($limit)
            ->get();

        $max = (int) setting('webhooks.max_attempts', 3);
        $done = 0;

        foreach ($logs as $log) {
            if ($log->attempts >= $max) {
                $log->update(['status' => 'abandoned', 'next_retry_at' => null]);

                continue;
            }
            $hook = $log->webhook;
            if (! $hook || ! $hook->is_active) {
                $log->update(['status' => 'abandoned', 'next_retry_at' => null]);

                continue;
            }
            $this->send($hook, (array) $log->payload, $log);
            $done++;
        }

        return $done;
    }
}
