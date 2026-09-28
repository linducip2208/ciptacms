<?php

namespace Plugins\WhatsAppBridge;

use App\Core\Plugins\PluginInterface;
use App\Core\Services\SettingService;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Log;

/**
 * Sends messages through the WhatsApp Business Cloud API.
 *
 * Credentials live in settings, so an operator configures them in the admin
 * rather than in a .env file:
 *
 *   whatsapp.phone_number_id
 *   whatsapp.access_token
 *   whatsapp.api_version   (default v23.0)
 *
 * The plugin refuses to send when they are absent instead of failing
 * silently, which is the difference between a real integration and a stub.
 */
class Plugin implements PluginInterface
{
    protected const ENDPOINT = 'https://graph.facebook.com/%s/%s/messages';

    /** @var array<string,mixed> */
    protected array $manifest;

    /** @var HttpFactory */
    protected HttpFactory $http;

    public function __construct(?HttpFactory $http = null, array $manifest = [])
    {
        $this->http = $http ?: app(HttpFactory::class);
        $this->manifest = $manifest ?: [
            'name' => 'WhatsApp Bridge',
            'slug' => 'whatsapp-bridge',
            'version' => '1.0.0',
        ];
    }

    public function slug(): string
    {
        return (string) ($this->manifest['slug'] ?? 'whatsapp-bridge');
    }

    public function manifest(): array
    {
        return $this->manifest;
    }

    /**
     * Names this plugin wants to receive. The interface contract is a list,
     * not a name => callable map: the core looks the name up with in_array()
     * and then calls filter() on every plugin that declared it.
     */
    public function filters(): array
    {
        return ['whatsapp.link'];
    }

    public function filter(string $name, mixed $value, array $context = []): mixed
    {
        if ($name !== 'whatsapp.link' || ! is_string($value) || $value === '') {
            return null;
        }

        return $this->link($value, (string) ($context['message'] ?? ''));
    }

    /**
     * The core dispatches contact submissions as `cms.contact.message`
     * (SiteController::notifyNew), so the hook must carry that prefix.
     * An unprefixed name here can never fire.
     */
    public function hooks(): array
    {
        return [
            'cms.contact.message' => 'onContactMessage',
        ];
    }

    /**
     * Normalise a phone number to the international form the API expects,
     * and build a click-to-chat link.
     */
    public function link(?string $number, string $message = ''): ?string
    {
        $digits = $this->normalise($number);
        if ($digits === null) {
            return null;
        }

        $url = 'https://wa.me/'.$digits;
        if ($message !== '') {
            $url .= '?text='.rawurlencode($message);
        }

        return $url;
    }

    /**
     * Send a text message. Returns the decoded API response, or an error
     * array when the plugin is not configured.
     */
    public function send(string $to, string $message): array
    {
        $digits = $this->normalise($to);
        if ($digits === null) {
            return ['ok' => false, 'error' => 'The recipient number is not a valid international number.'];
        }

        $phoneNumberId = trim((string) setting('whatsapp.phone_number_id', ''));
        $token = trim((string) setting('whatsapp.access_token', ''));

        if ($phoneNumberId === '' || $token === '') {
            return [
                'ok' => false,
                'error' => 'WhatsApp Bridge is not configured. Set whatsapp.phone_number_id and whatsapp.access_token.',
            ];
        }

        $version = trim((string) setting('whatsapp.api_version', 'v23.0'));
        $version = ltrim($version, 'v') === $version ? $version : $version;

        $url = sprintf(self::ENDPOINT, $version, $phoneNumberId);

        try {
            $response = $this->http
                ->withToken($token)
                ->acceptJson()
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to' => $digits,
                    'type' => 'text',
                    'text' => ['preview_url' => true, 'body' => $message],
                ]);
        } catch (\Throwable $e) {
            Log::warning('WhatsApp Bridge could not reach the API', ['error' => $e->getMessage()]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }

        $body = $response->json() ?? [];

        if (! $response->successful()) {
            $message = $body['error']['message'] ?? ('HTTP '.$response->status());

            return ['ok' => false, 'error' => $message, 'response' => $body];
        }

        return ['ok' => true, 'response' => $body];
    }

    /**
     * Reply to a new contact message, if the operator enabled it.
     * Failures are logged, never thrown at the visitor's request.
     */
    public function onContactMessage(array $payload): void
    {
        if (! setting('whatsapp.notify_on_contact', false)) {
            return;
        }

        $destination = trim((string) setting('whatsapp.notify_number', ''));
        if ($destination === '') {
            return;
        }

        $name = (string) ($payload['name'] ?? 'Someone');
        $body = "New enquiry from {$name}\n"
            .'Subject: '.($payload['subject'] ?? '—')."\n"
            .($payload['email'] ?? '');

        $result = $this->send($destination, $body);

        if (! ($result['ok'] ?? false)) {
            Log::info('WhatsApp Bridge did not notify', ['error' => $result['error'] ?? 'unknown']);
        }
    }

    public function onInstall(): void
    {
        // Settings are operator-created; nothing to seed.
    }

    public function onUninstall(): void
    {
        // Settings are left in place so reinstalling is non-destructive.
    }

    /**
     * Digits only, with a leading country code. Returns null when the input
     * cannot be a phone number, so a bad value never reaches the API.
     */
    protected function normalise(?string $number): ?string
    {
        $number = trim((string) $number);

        // Keep digits, drop a leading + and any separators.
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if (strlen($digits) < 8 || strlen($digits) > 15) {
            return null;
        }

        return $digits;
    }
}
