<?php

namespace App\Core\Services;

use App\Models\NotificationDelivery;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Notification fan-out. Every send is recorded in notification_deliveries so
 * an operator can see what went out, what failed and retry it.
 *
 * Channels beyond `mail` and `webhook` are adapter slots: they log the
 * attempt and report "no adapter configured" rather than pretending to send.
 */
class NotificationService
{
    public const ADAPTER_CHANNELS = ['sms', 'whatsapp', 'push'];

    public function send(array $data, ?NotificationTemplate $template = null): NotificationDelivery
    {
        $channel = (string) ($data['channel'] ?? 'mail');
        $recipient = (string) ($data['recipient'] ?? '');
        $subject = (string) ($data['subject'] ?? '');
        $body = (string) ($data['body'] ?? '');

        $delivery = NotificationDelivery::create([
            'notification_template_id' => $template?->id,
            'channel' => $channel,
            'recipient' => $recipient,
            'subject' => $subject,
            'body' => $body,
            'status' => 'pending',
            'attempts' => 1,
        ]);

        try {
            $this->dispatch($channel, $recipient, $subject, $body);
            $delivery->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (\Throwable $e) {
            $delivery->update(['status' => 'failed', 'error' => Str::limit($e->getMessage(), 500)]);
        }

        return $delivery->fresh();
    }

    protected function dispatch(string $channel, string $recipient, string $subject, string $body): void
    {
        match ($channel) {
            'mail' => Mail::raw($body, function ($m) use ($recipient, $subject) {
                $from = setting('branding.email_from_address', config('mail.from.address'));
                $name = setting('branding.email_from_name', config('mail.from.name'));
                if ($from) {
                    $m->from($from, $name);
                }
                $m->to($recipient)->subject($subject);
            }),
            'webhook' => Http::timeout((int) setting('webhooks.timeout', 10))
                ->post($recipient, ['subject' => $subject, 'body' => $body]),
            'database' => NotificationDelivery::create([
                'notification_template_id' => null,
                'channel' => 'database',
                'recipient' => $recipient,
                'subject' => $subject,
                'body' => $body,
                'status' => 'sent',
                'attempts' => 1,
                'sent_at' => now(),
            ]),
            default => $this->adapter($channel, $recipient, $subject, $body),
        };
    }

    /**
     * Adapter slots for SMS / WhatsApp / push. A real gateway plugs in by
     * overriding this method or binding its own implementation of the
     * channel in config/lindu.php.
     */
    protected function adapter(string $channel, string $recipient, string $subject, string $body): void
    {
        if (in_array($channel, self::ADAPTER_CHANNELS, true)) {
            throw new \RuntimeException("No adapter configured for the '{$channel}' channel yet.");
        }

        throw new \RuntimeException("Unknown notification channel: {$channel}");
    }

    public function retry(NotificationDelivery $delivery): bool
    {
        if (! in_array($delivery->channel, ['mail', 'webhook', 'database'], true)) {
            $delivery->update(['status' => 'failed', 'error' => "No adapter configured for the '{$delivery->channel}' channel."]);

            return false;
        }

        try {
            $this->dispatch($delivery->channel, $delivery->recipient, (string) $delivery->subject, (string) $delivery->body);
            $delivery->update([
                'status' => 'sent',
                'error' => null,
                'sent_at' => now(),
                'attempts' => $delivery->attempts + 1,
            ]);

            return true;
        } catch (\Throwable $e) {
            $delivery->update([
                'status' => 'failed',
                'error' => Str::limit($e->getMessage(), 500),
                'attempts' => $delivery->attempts + 1,
            ]);

            return false;
        }
    }

    /** Send a stored template with {{variable}} substitution. */
    public function sendTemplate(NotificationTemplate $template, string $recipient, array $variables = []): NotificationDelivery
    {
        $subject = $this->interpolate((string) $template->subject, $variables);
        $body = $this->interpolate((string) $template->body, $variables);

        return $this->send([
            'channel' => $template->channel,
            'recipient' => $recipient,
            'subject' => $subject,
            'body' => $body,
        ], $template);
    }

    protected function interpolate(string $text, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $text = str_replace(['{{'.$key.'}}', '{{ '.$key.' }}'], (string) $value, $text);
        }

        return $text;
    }

    /** Admin notification driven by the boolean settings under Notifications. */
    public function notifyAdmins(string $settingKey, string $subject, string $body): ?NotificationDelivery
    {
        if (! setting($settingKey, true)) {
            return null;
        }

        $to = (string) setting('notifications.admin_email', config('mail.from.address', ''));
        if ($to === '') {
            return null;
        }

        return $this->send(['channel' => 'mail', 'recipient' => $to, 'subject' => $subject, 'body' => $body]);
    }
}
