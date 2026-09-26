<?php

namespace App\Jobs;

use App\Core\Services\WebhookDispatcher;
use App\Models\Webhook;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Delivers a single webhook attempt. The signing, timing and log bookkeeping
 * all live in WebhookDispatcher so a queued job and an inline delivery
 * behave identically.
 */
class DispatchWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public string $webhookId, public array $payload, public ?int $logId = null) {}

    public function handle(WebhookDispatcher $dispatcher): void
    {
        $hook = Webhook::find($this->webhookId);
        if (! $hook || ! $hook->is_active) {
            return;
        }

        $log = $this->logId ? \App\Models\WebhookLog::find($this->logId) : null;
        $log = $dispatcher->send($hook, $this->payload, $log);

        if ($log->status === 'failed') {
            // Re-throw so the queue records the failure; the dispatcher has
            // already written the reason to webhook_logs.
            throw new \RuntimeException((string) $log->error);
        }
    }
}
