<?php

namespace App\Core\Services\Payments;

use App\Core\Contracts\PaymentGateway;
use Illuminate\Support\Facades\Http;

/**
 * Escape hatch for gateways the CMS does not ship an adapter for. Point
 * `endpoint` at the provider and pass-through whatever fields you need.
 */
class GenericGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'Generic';
    }

    protected function config(): array
    {
        $all = (array) setting('billing.gateways', []);
        $key = array_search(static::class, (array) config('lindu.payments.adapters', []), true);

        return is_string($key) ? (array) ($all[$key] ?? []) : [];
    }

    public function charge(array $payload): array
    {
        $endpoint = (string) ($this->config()['endpoint'] ?? '');
        $secret = (string) ($this->config()['secret_key'] ?? '');

        if ($endpoint === '' || $secret === '') {
            return [
                'ok' => false,
                'reference' => '',
                'message' => 'The generic adapter needs both `endpoint` and `secret_key` in billing.gateways.',
            ];
        }

        $body = array_merge($payload, [
            'reference' => $payload['reference'] ?? uniqid('ord_', true),
            'currency' => $payload['currency'] ?? config('lindu.payments.currency'),
        ]);

        try {
            $res = Http::timeout(20)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Api-Key' => $secret,
                ])
                ->post($endpoint, $body);
        } catch (\Throwable $e) {
            return ['ok' => false, 'reference' => '', 'message' => $e->getMessage()];
        }

        $json = $res->json() ?? [];

        return [
            'ok' => $res->successful(),
            'reference' => (string) ($json['reference'] ?? $body['reference']),
            'invoice_url' => $json['invoice_url'] ?? $json['url'] ?? null,
            'message' => $res->successful() ? null : "HTTP {$res->status()}: ".substr((string) $res->body(), 300),
            'raw' => $json,
        ];
    }

    public function verifyCallback(array $payload, string $signature, string $rawBody): bool
    {
        $secret = (string) ($this->config()['callback_secret'] ?? $this->config()['secret_key'] ?? '');
        if ($secret === '' || $signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $rawBody, $secret), $signature);
    }

    public function parseCallback(array $payload): array
    {
        return [
            'reference' => (string) ($payload['reference'] ?? ''),
            'status' => strtolower((string) ($payload['status'] ?? 'unknown')),
            'amount' => $payload['amount'] ?? null,
            'currency' => $payload['currency'] ?? null,
        ];
    }
}
