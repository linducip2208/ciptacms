<?php

namespace App\Core\Services\Payments;

/**
 * iPaymu v2 API.
 * Docs: https://ipaymu.com/en/api-doc/
 */
class IpaymuGateway extends HostedGateway
{
    public function name(): string
    {
        return 'iPaymu';
    }

    protected function endpoint(): string
    {
        return $this->mode() === 'sandbox'
            ? 'https://sandbox.ipaymu.com/v2/checkout'
            : 'https://payment.ipaymu.com/v2/checkout';
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
            'reference_id' => $payload['reference'] ?? null,
            'currency' => $payload['currency'] ?? 'IDR',
            'amount' => (int) $payload['amount'],
            'name' => trim((string) ($payload['customer_name'] ?? 'Customer')),
            'email' => $payload['email'] ?? null,
            'description' => $payload['description'] ?? 'Lindu CMS payment',
            'callback_url' => $payload['callback_url'] ?? null,
            'expiry_date' => 1,
            'expiry_unit' => 'hours',
        ], fn ($v) => $v !== null);
    }

    protected function interpret(array $json): array
    {
        return [
            'ok' => ! empty($json['Data']['RedirectURL'] ?? null),
            'reference' => (string) ($json['Data']['ReferenceID'] ?? ''),
            'invoice_url' => $json['Data']['RedirectURL'] ?? null,
            'message' => isset($json['Data']) ? null : trim(($json['StatusMessage'] ?? 'iPaymu rejected the request').' '.json_encode($json)),
            'raw' => $json,
        ];
    }

    protected function callbackSignature(string $rawBody, array $payload): string
    {
        // iPaymu signs with a distinct key; fall back to the HMAC form when
        // no dedicated key is configured.
        $key = (string) ($this->config()['callback_key'] ?? $this->secret());

        return hash_hmac('sha256', $rawBody, $key);
    }
}
