<?php

namespace App\Core\Services\Payments;

/**
 * Tripay payment gateway.
 * Docs: https://tripay.co.id/en/documentation
 */
class TripayGateway extends HostedGateway
{
    public function name(): string
    {
        return 'Tripay';
    }

    protected function endpoint(): string
    {
        $base = $this->mode() === 'sandbox'
            ? 'https://tripay.co.id/api/sandbox/transaction/init'
            : 'https://tripay.co.id/api/transaction/init';

        return $base;
    }

    protected function headers(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->secret(),
        ];
    }

    protected function buildBody(array $payload): array
    {
        return array_filter([
            'merchant_ref' => $payload['reference'] ?? null,
            'amount' => (int) $payload['amount'],
            'currency' => $payload['currency'] ?? 'IDR',
            'description' => $payload['description'] ?? 'Lindu CMS payment',
            'callback_url' => $payload['callback_url'] ?? null,
        ], fn ($v) => $v !== null);
    }

    protected function interpret(array $json): array
    {
        $tripayRef = $json['data']['reference'] ?? null;

        return [
            'ok' => ! empty($tripayRef),
            'reference' => (string) ($tripayRef ?? ''),
            'invoice_url' => $json['data']['checkout_url'] ?? null,
            'message' => $tripayRef ? null : trim(($json['message'] ?? 'Tripay rejected the request').' '.json_encode($json)),
            'raw' => $json,
        ];
    }

    /**
     * Tripay signs callbacks with its own scheme rather than a plain HMAC of
     * the body, so the shared verification path does not apply here.
     * Callers should use parseCallback() and confirm the merchant_ref against
     * the stored order instead.
     */
    public function verifyCallback(array $payload, string $signature, string $rawBody): bool
    {
        return false;
    }
}
