<?php

namespace App\Core\Services;

use App\Core\Contracts\PaymentGateway;

/**
 * Resolves a configured gateway adapter. Core never assumes a specific
 * provider; modules ask for one by key.
 */
class PaymentManager
{
    /** @return array<string,string> */
    public function adapters(): array
    {
        return (array) config('lindu.payments.adapters', []);
    }

    public function has(string $key): bool
    {
        return isset($this->adapters()[$key]);
    }

    public function resolve(string $key): ?PaymentGateway
    {
        $class = $this->adapters()[$key] ?? null;
        if (! $class || ! class_exists($class)) {
            return null;
        }

        $gateway = app($class);

        return $gateway instanceof PaymentGateway ? $gateway : null;
    }

    public function charge(string $key, array $payload): array
    {
        $gateway = $this->resolve($key);
        if (! $gateway) {
            return ['ok' => false, 'reference' => '', 'message' => "Unknown payment gateway '{$key}'."];
        }

        return $gateway->charge($payload + ['currency' => config('lindu.payments.currency')]);
    }

    public function verify(string $key, array $payload, string $signature, string $rawBody): bool
    {
        return (bool) $this->resolve($key)?->verifyCallback($payload, $signature, $rawBody);
    }

    public function parse(string $key, array $payload): array
    {
        return $this->resolve($key)?->parseCallback($payload) ?? ['reference' => '', 'status' => 'unknown'];
    }
}
