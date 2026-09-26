<?php

namespace App\Core\Services\Payments;

/**
 * Xendit hosted checkout / invoice API.
 * Docs: https://developers.xendit.co/api-reference/
 */
class XenditGateway extends HostedGateway
{
    public function name(): string
    {
        return 'Xendit';
    }

    protected function endpoint(): string
    {
        $base = $this->mode() === 'sandbox'
            ? 'https://api.xendit.co'
            : 'https://api.xendit.co';

        return $base.'/v2/invoices';
    }

    protected function buildBody(array $payload): array
    {
        return array_filter([
            'external_id' => $payload['reference'] ?? null,
            'amount' => (int) $payload['amount'],
            'payer_email' => $payload['email'] ?? null,
            'description' => $payload['description'] ?? 'Lindu CMS payment',
            'callback_url' => $payload['callback_url'] ?? null,
            'success_redirect_url' => $payload['success_url'] ?? null,
            'failure_redirect_url' => $payload['failure_url'] ?? null,
            'currency' => $payload['currency'] ?? 'IDR',
        ], fn ($v) => $v !== null);
    }

    protected function interpret(array $json): array
    {
        return [
            'ok' => ! empty($json['id']),
            'reference' => (string) ($json['id'] ?? ''),
            'invoice_url' => $json['invoice_url'] ?? null,
            'message' => $json['id'] ? null : 'Xendit returned no invoice id',
            'raw' => $json,
        ];
    }

    protected function callbackSignature(string $rawBody, array $payload): string
    {
        // Xendit sends the callback token in a header; the invoice callback
        // itself is server-to-server and trusted over TLS.
        return (string) $this->secret();
    }
}
