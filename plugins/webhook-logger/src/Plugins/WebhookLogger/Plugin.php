<?php

namespace Plugins\WebhookLogger;

use App\Core\Plugins\PluginInterface;
use App\Models\ActivityLog;

/**
 * Records every outgoing webhook attempt in the activity log.
 *
 * The core already writes a row per delivery in `webhook_logs`. This plugin
 * adds the summary an operator actually reads: one activity entry per
 * delivery, tagged so it can be filtered, and failures called out so a
 * misbehaving endpoint is visible in the admin rather than buried.
 *
 * It is also the worked example of the plugin contract. Copy this folder
 * to start a new plugin.
 */
class Plugin implements PluginInterface
{
    /** @var array<string,mixed> */
    protected array $manifest;

    public function __construct(array $manifest = [])
    {
        $this->manifest = $manifest ?: [
            'name' => 'Webhook Logger',
            'slug' => 'webhook-logger',
            'version' => '1.0.0',
        ];
    }

    public function slug(): string
    {
        return (string) ($this->manifest['slug'] ?? 'webhook-logger');
    }

    public function manifest(): array
    {
        return $this->manifest;
    }

    public function filters(): array
    {
        return [];
    }

    /**
     * A plugin may transform a filtered value. This one is observation-only,
     * so it declines by returning null, which the core treats as "unchanged".
     */
    public function filter(string $name, mixed $value, array $context = []): mixed
    {
        return null;
    }

    public function hooks(): array
    {
        return [
            'webhook.delivered' => 'onDelivered',
            'webhook.failed' => 'onFailed',
        ];
    }

    public function onDelivered(array $payload): void
    {
        $this->record($payload, 'success');
    }

    public function onFailed(array $payload): void
    {
        $this->record($payload, 'failed');
    }

    public function onInstall(): void
    {
        // Nothing to migrate.
    }

    public function onUninstall(): void
    {
        // Activity log entries are history; they are intentionally left alone.
    }

    /**
     * Failures are the point of this plugin, so they must never be lost
     * silently either — if the activity log itself is unavailable the error
     * is reported rather than thrown at the delivery path.
     */
    protected function record(array $payload, string $action): void
    {
        try {
            ActivityLog::create([
                'tenant_id' => tenant_id(),
                'user_id' => auth()->id(),
                'channel' => 'webhook',
                'action' => $action,
                'description' => $this->describe($payload),
                'subject_type' => \App\Models\Webhook::class,
                'subject_id' => $payload['webhook_id'] ?? null,
                'properties' => [
                    'event' => $payload['event'] ?? null,
                    'http_status' => $payload['http_status'] ?? null,
                    'attempts' => $payload['attempts'] ?? null,
                    'error' => $payload['error'] ?? null,
                    'url' => $payload['url'] ?? null,
                ],
            ]);
        } catch (\Throwable $e) {
            // The delivery path must not lose the record silently, but a
            // failure here has to be visible somewhere other than the log the
            // caller is already writing.
            report($e);
        }
    }

    protected function describe(array $payload): string
    {
        $name = (string) ($payload['name'] ?? 'webhook');
        $event = (string) ($payload['event'] ?? 'event');

        $base = "Webhook \"{$name}\" delivered \"{$event}\"";

        if (($payload['status'] ?? '') === 'delivered') {
            return $base.' — HTTP '.($payload['http_status'] ?? '?').'.';
        }

        return $base.' FAILED — '
            .($payload['http_status'] ? 'HTTP '.$payload['http_status'] : 'no response')
            .' after '.($payload['attempts'] ?? 1).' attempt(s).';
    }
}
