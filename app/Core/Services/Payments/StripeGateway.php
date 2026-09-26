<?php

namespace App\Core\Services\Payments;

/**
 * Stripe Checkout.
 * Docs: https://docs.stripe.com/api/checkout
 */
class StripeGateway extends HostedGateway
{
    public function name(): string
    {
        return 'Stripe';
    }

    protected function endpoint(): string
    {
        return 'https://api.stripe.com/v1/checkout/sessions';
    }

    protected function headers(): array
    {
        return [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Bearer '.$this->secret(),
        ];
    }

    protected function buildBody(array $payload): array
    {
        $amount = (int) round(((float) ($payload['amount'] ?? 0)) * 100);
        $currency = strtolower((string) ($payload['currency'] ?? 'idr'));

        $body = [
            'mode' => 'payment',
            'success_url' => $payload['success_url'] ?? ($payload['callback_url'] ?? 'https://example.com/ok'),
            'cancel_url' => $payload['failure_url'] ?? ($payload['callback_url'] ?? 'https://example.com/cancel'),
            'line_items[0][quantity]' => 1,
            'line_items[0][price_data][currency]' => $currency,
            'line_items[0][price_data][unit_amount]' => $amount,
            'line_items[0][price_data][product_data][name]' => $payload['description'] ?? 'Lindu CMS payment',
        ];

        if (! empty($payload['reference'])) {
            $body['client_reference_id'] = $payload['reference'];
        }
        if (! empty($payload['email'])) {
            $body['customer_email'] = $payload['email'];
        }

        return $body;
    }

    protected function interpret(array $json): array
    {
        return [
            'ok' => ! empty($json['id']),
            'reference' => (string) ($json['id'] ?? ''),
            'invoice_url' => $json['url'] ?? null,
            'message' => $json['id'] ? null : trim(($json['error']['message'] ?? 'Stripe rejected the request')),
            'raw' => $json,
        ];
    }

    protected function callbackSignature(string $rawBody, array $payload): string
    {
        // Stripe uses the webhook signing secret, not the API key.
        return hash_hmac(
            'sha256',
            $payload['timestamp'].'.'.$rawBody,
            (string) ($this->config()['webhook_secret'] ?? $this->secret())
        );
    }

    public function parseCallback(array $payload): array
    {
        return [
            'reference' => (string) ($payload['data']['object']['client_reference_id'] ?? $payload['id'] ?? ''),
            'status' => strtolower((string) ($payload['type'] ?? 'unknown')),
            'amount' => $payload['data']['object']['amount_total'] ?? null,
            'currency' => $payload['data']['object']['currency'] ?? null,
        ];
    }
}
