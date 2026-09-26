<?php

namespace App\Core\Services\Payments;

use App\Core\Contracts\PaymentGateway;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Base for hosted-checkout gateways. Subclasses only declare their endpoint,
 * headers and the fields a charge request needs; the request/response handling
 * is identical.
 */
abstract class HostedGateway implements PaymentGateway
{
    abstract public function name(): string;

    abstract protected function endpoint(): string;

    /** Configured secrets, read from settings. */
    protected function config(): array
    {
        $all = (array) setting('billing.gateways', []);
        $key = array_search(static::class, (array) config('lindu.payments.adapters', []), true);

        return is_string($key) ? (array) ($all[$key] ?? []) : [];
    }

    protected function secret(): ?string
    {
        return $this->config()['secret_key'] ?? null;
    }

    protected function mode(): string
    {
        return (string) ($this->config()['mode'] ?? 'live');
    }

    protected function isConfigured(): bool
    {
        return ! empty($this->secret());
    }

    /** Gateway specific charge body. */
    abstract protected function buildBody(array $payload): array;

    protected function headers(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$this->secret(),
        ];
    }

    public function charge(array $payload): array
    {
        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'reference' => '',
                'message' => $this->name().' is not configured. Add the secret key under SaaS → Payment gateways.',
            ];
        }

        $body = $this->buildBody($payload);

        try {
            $res = Http::timeout(20)
                ->withHeaders($this->headers())
                ->post($this->endpoint(), $body);
        } catch (\Throwable $e) {
            return ['ok' => false, 'reference' => '', 'message' => $e->getMessage()];
        }

        if (! $res->successful()) {
            return [
                'ok' => false,
                'reference' => '',
                'message' => "HTTP {$res->status()}: ".substr((string) $res->body(), 300),
                'raw' => $res->json(),
            ];
        }

        return $this->interpret($res->json() ?? []);
    }

    /** Map a successful provider response onto the common return shape. */
    abstract protected function interpret(array $json): array;

    public function verifyCallback(array $payload, string $signature, string $rawBody): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        return hash_equals($this->callbackSignature($rawBody, $payload), $signature);
    }

    protected function callbackSignature(string $rawBody, array $payload): string
    {
        return hash_hmac('sha256', $rawBody, (string) $this->secret());
    }

    public function parseCallback(array $payload): array
    {
        return [
            'reference' => (string) ($payload['id'] ?? $payload['reference'] ?? ''),
            'status' => strtolower((string) ($payload['status'] ?? $payload['transaction_status'] ?? 'unknown')),
            'amount' => $payload['amount'] ?? null,
            'currency' => $payload['currency'] ?? config('lindu.payments.currency'),
        ];
    }
}
