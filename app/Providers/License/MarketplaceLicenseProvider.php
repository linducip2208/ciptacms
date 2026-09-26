<?php

namespace App\Providers\License;

use App\Core\Contracts\LicenseProvider;
use App\Services\LicenseClient;

/**
 * Bridges the v3 marketplace pairing kit onto the project's own licensing
 * contract so module + update systems can reach it through
 * App\Core\Services\LicenseService without importing App\Services directly.
 *
 * Enable with LINDU_LICENSE_PROVIDER=App\Providers\License\MarketplaceLicenseProvider
 * (or the same FQCN in config/lindu.php). With the variable empty the project
 * keeps using its local `licenses` rows + activations table and this class is
 * never resolved.
 *
 * All cryptography stays inside the kit's LicenseClient. This adapter only
 * translates the contract's shape and never re-implements verification.
 */
class MarketplaceLicenseProvider implements LicenseProvider
{
    public function __construct(private LicenseClient $client) {}

    /**
     * The activation key currently bound to this install. The kit never
     * persists the raw key on disk, so this is whatever the marketplace
     * embedded in the signed payload (null if the server omits it).
     */
    public function currentKey(): ?string
    {
        $domain = $this->currentDomain();

        if ($domain === null) {
            return null;
        }

        $data = $this->client->verify($domain);
        if ($data === null) {
            return null;
        }

        $key = $data['license']['key'] ?? $data['license']['activation_key'] ?? $data['license_key'] ?? null;

        return is_string($key) && trim($key) !== '' ? trim($key) : null;
    }

    /**
     * Validate a key against the marketplace.
     *
     * A domain that is already paired short-circuits: the signed lock is the
     * authority and re-hitting the server on every check would add a network
     * dependency to every request. A fresh key always contacts the server.
     *
     * @return array{ok:bool,message:string,features?:array,expires_at?:?string,entitled_version?:?string}
     */
    public function verify(string $key, string $domain): array
    {
        $domain = $this->normalizeDomain($domain);

        if ($domain === '') {
            return ['ok' => false, 'message' => 'A domain is required to pair this license.'];
        }

        $existing = $this->client->verify($domain);

        if ($existing !== null) {
            return $this->toResult(true, 'Paired', $existing);
        }

        if (trim($key) === '') {
            return ['ok' => false, 'message' => 'An activation key is required.'];
        }

        $result = $this->client->activate(strtoupper(trim($key)), $domain);

        if (! ($result['ok'] ?? false)) {
            return ['ok' => false, 'message' => (string) ($result['error'] ?? 'Aktivasi gagal.')];
        }

        // Re-read from disk so callers only ever see a payload that survived
        // an authenticated decrypt + RSA signature check.
        $data = $this->client->verify($domain);

        if ($data === null) {
            return ['ok' => false, 'message' => 'Lock file failed verification after activation.'];
        }

        return $this->toResult(true, 'Activated', $data);
    }

    /** Release this domain's pairing by discarding the lock file. */
    public function deactivate(string $key, string $domain): bool
    {
        $domain = $this->normalizeDomain($domain);

        if ($domain === '') {
            return false;
        }

        if ($this->client->verify($domain) === null) {
            return false;
        }

        $this->client->clearLock();

        return ! file_exists((string) config('license.lock_file'));
    }

    /**
     * Read-only view of the signed payload for the current host, for the
     * update center and module gating.
     *
     * @return array<string,mixed>|null
     */
    public function payload(?string $domain = null): ?array
    {
        $domain = $this->normalizeDomain($domain ?? (string) $this->currentDomain());

        return $domain === '' ? null : $this->client->verify($domain);
    }

    public function isPaired(?string $domain = null): bool
    {
        return $this->payload($domain) !== null;
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array{ok:bool,message:string,features:array,expires_at:?string,entitled_version:?string}
     */
    private function toResult(bool $ok, string $message, array $data): array
    {
        $features = $data['license']['features'] ?? $data['features'] ?? [];
        $features = is_array($features) ? array_values(array_filter(array_map('strval', $features))) : [];

        $version = $data['product']['version'] ?? $data['entitled_version'] ?? null;

        return [
            'ok' => $ok,
            'message' => $message,
            'features' => $features,
            'expires_at' => $this->asDateString($data['expires_at'] ?? null),
            'entitled_version' => is_string($version) && $version !== '' ? $version : null,
        ];
    }

    private function asDateString(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * The kit derives its encryption key from the exact host string that was
     * signed at pairing time, so this must stay a plain lowercase host. Do not
     * add www/scheme stripping here — it would make the lock undecryptable.
     */
    private function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain) ?? $domain;
        $domain = explode('/', $domain)[0];
        $domain = explode(':', $domain)[0];

        return trim($domain, '.');
    }

    private function currentDomain(): ?string
    {
        $request = app('request');

        if (! $request instanceof \Illuminate\Http\Request) {
            return null;
        }

        $host = trim((string) $request->getHost());

        return $host === '' ? null : $host;
    }
}
