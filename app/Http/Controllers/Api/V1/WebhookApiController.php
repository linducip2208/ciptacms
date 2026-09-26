<?php

namespace App\Http\Controllers\Api\V1;

use App\Core\Services\WebhookDispatcher;
use App\Core\Services\WorkflowEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookApiController extends ApiController
{
    /**
     * Inbound webhook endpoint: POST /api/v1/webhooks/in/{key}
     *
     * The key identifies the endpoint. When the key row has a secret, the
     * request must also carry a valid HMAC signature — otherwise anyone who
     * ever saw the URL could drive the workflow engine.
     */
    public function incoming(Request $r, string $key)
    {
        $record = $this->keyFor($key);

        if (! $record || ! $record->is_active) {
            return $this->error('Unknown webhook', 404);
        }

        $raw = $r->getContent();

        // Fail closed. A key row without a secret is an open door, not a
        // trusted endpoint, so it is refused rather than silently accepted.
        $secret = (string) ($record->secret ?? '');

        if ($secret === '') {
            $this->log($record->id, $r, [], 'rejected: endpoint has no secret', 401);

            return $this->error('This endpoint is not configured for delivery', 403);
        }

        $signature = (string) $r->header(
            (string) setting('webhooks.signature_header', 'X-Lindu-Signature')
        );

        if (! app(WebhookDispatcher::class)->verify($raw, $secret, $signature)) {
            $this->log($record->id, $r, [], 'rejected: bad signature', 401);

            return $this->error('Invalid signature', 401);
        }

        $payload = json_decode($raw, true);
        $payload = is_array($payload) ? $payload : $r->all();

        $this->log($record->id, $r, $payload, 'accepted');

        try {
            app(WorkflowEngine::class)->trigger(
                $record->forward_event ?: 'webhook',
                array_merge($payload, ['_webhook' => $record->id, '_key' => $record->name])
            );
        } catch (\Throwable $e) {
            // A broken workflow must not turn a successful delivery into a
            // 500, which would make the sender retry forever.
            Log::warning('Inbound webhook handler failed', [
                'key_id' => $record->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->data([
            'ok' => true,
            'name' => $record->name,
            'event' => $record->forward_event ?: 'webhook',
        ]);
    }

    protected function keyFor(string $key)
    {
        try {
            return DB::table('webhook_incoming_keys')
                ->where('key', $key)
                ->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function log(int $keyId, Request $r, array $payload, string $status, ?int $code = 200): void
    {
        try {
            DB::table('webhook_incoming_logs')->insert([
                'key_id' => $keyId,
                'payload' => json_encode($payload),
                'headers' => json_encode([
                    'content-type' => $r->header('Content-Type'),
                    'user-agent' => $r->userAgent(),
                ]),
                'method' => $r->method(),
                'ip' => $r->ip(),
                'status' => $status,
                'error' => $code && $code >= 400 ? 'signature verification failed' : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
