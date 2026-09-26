<?php

namespace App\Core\Services;

use App\Core\Contracts\LicenseProvider;
use App\Models\License;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Licensing abstraction.
 *
 * The product data itself is always local (this file, the lock file, the
 * activations table). What is pluggable is the *verification provider*, so a
 * future marketplace/host can add one without touching the callers.
 */
class LicenseService
{
    public const STATUSES = ['active', 'inactive', 'expired', 'suspended', 'banned'];

    /**
     * Optional remote verification provider.
     *
     * When one is configured it becomes the authority for key validity; the
     * local `licenses` table then only supplies the commercial metadata
     * (features, entitlements, activation limits) that the provider does not
     * model. With no provider configured the local table is authoritative on
     * its own.
     */
    public function provider(): ?LicenseProvider
    {
        $class = (string) config('lindu.license.provider', '');

        if ($class === '' || ! class_exists($class)) {
            return null;
        }

        $provider = app($class);

        return $provider instanceof LicenseProvider ? $provider : null;
    }

    public function issue(array $d): License
    {
        $license = License::create([
            'license_key' => $d['license_key'] ?? $this->generateKey(),
            'product' => $d['product'],
            'customer' => $d['customer'] ?? null,
            'domain' => $d['domain'] ?? null,
            'status' => in_array($d['status'] ?? 'active', self::STATUSES, true) ? ($d['status'] ?? 'active') : 'active',
            'features' => $this->normalizeFeatures($d['features'] ?? []),
            'max_activations' => max(1, (int) ($d['max_activations'] ?? 1)),
            'expires_at' => $d['expires_at'] ?? null,
            'support_expires_at' => $d['support_expires_at'] ?? null,
            'version_entitlement' => $d['version_entitlement'] ?? null,
        ]);

        return $license;
    }

    public function generateKey(): string
    {
        return 'LND-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4));
    }

    public function activate(string $key, string $domain, ?string $ip = null): array
    {
        $l = License::where('license_key', trim($key))->first();
        if (! $l) {
            return ['ok' => false, 'message' => 'Invalid license key'];
        }

        if ($l->status !== 'active') {
            return ['ok' => false, 'message' => "License is {$l->status}"];
        }

        if ($l->expires_at && $l->expires_at->isPast()) {
            $l->update(['status' => 'expired']);

            return ['ok' => false, 'message' => 'License expired on '.$l->expires_at->toDateString()];
        }

        $domain = $this->normalizeDomain($domain);
        $count = $l->activations()->count();

        if ($count >= $l->max_activations && ! $l->activations()->where('domain', $domain)->exists()) {
            return ['ok' => false, 'message' => "Activation limit of {$l->max_activations} reached"];
        }

        $l->activations()->updateOrCreate(
            ['domain' => $domain],
            ['activated_at' => now(), 'ip' => $ip]
        );
        $l->update(['last_checked_at' => now(), 'domain' => $l->domain ?: $domain]);

        return [
            'ok' => true,
            'message' => 'Activated',
            'features' => $l->features,
            'license' => $l,
        ];
    }

    public function deactivate(string $key, string $domain): array
    {
        $l = License::where('license_key', trim($key))->first();
        if (! $l) {
            return ['ok' => false, 'message' => 'Invalid license key'];
        }

        $removed = $l->activations()->where('domain', $this->normalizeDomain($domain))->delete();

        return $removed
            ? ['ok' => true, 'message' => 'Deactivated']
            : ['ok' => false, 'message' => 'That domain was not activated'];
    }

    /**
     * Validate the current installation. Checks the local product version and
     * entitlements; a configured provider is consulted if present.
     */
    public function status(): array
    {
        $version = (string) config('lindu.version');
        $installed = $this->installedLock();

        // A configured provider (e.g. the marketplace pairing kit) is the
        // authority on whether this installation is entitled. Without one the
        // local lock file decides.
        if ($provider = $this->provider()) {
            return $this->providerStatus($provider, $version);
        }

        if ($installed === null) {
            return [
                'licensed' => false,
                'state' => 'unlicensed',
                'version' => $version,
                'entitled_version' => null,
                'message' => 'No license is installed. The CMS runs in evaluation mode.',
            ];
        }

        $license = License::where('license_key', $installed['license_key'])->first();

        if (! $license) {
            return [
                'licensed' => false,
                'state' => 'invalid',
                'version' => $version,
                'entitled_version' => null,
                'message' => 'The installed license key no longer exists.',
            ];
        }

        $expired = $license->expires_at && $license->expires_at->isPast();
        if ($expired && $license->status === 'active') {
            $license->update(['status' => 'expired']);
        }

        $entitled = $license->version_entitlement;
        $withinVersion = ! $entitled || version_compare($version, $entitled, '<=');

        $state = match (true) {
            $license->status === 'banned' => 'banned',
            $license->status === 'suspended' => 'suspended',
            $expired => 'expired',
            ! $withinVersion => 'outdated',
            $license->status === 'inactive' => 'inactive',
            default => 'valid',
        };

        return [
            'licensed' => $state === 'valid',
            'state' => $state,
            'version' => $version,
            'entitled_version' => $entitled,
            'expires_at' => optional($license->expires_at)->toDateString(),
            'support_expires_at' => optional($license->support_expires_at)->toDateString(),
            'features' => $license->features,
            'license' => $license,
            'message' => match ($state) {
                'valid' => 'Licensed to '.$license->customer,
                'expired' => 'License expired on '.$license->expires_at->toDateString(),
                'outdated' => "This release needs a license entitled to v{$entitled} or later.",
                default => ucfirst($state),
            },
        ];
    }

    public function hasFeature(string $feature): bool
    {
        $status = $this->status();
        if (! $status['licensed']) {
            return false;
        }
        $features = $status['features'] ?? [];

        return in_array('*', (array) $features, true) || in_array($feature, (array) $features, true);
    }

    /**
     * Status derived from a remote provider rather than the local table.
     *
     * The provider answers "is this key valid for this domain"; the local
     * licence row, when present, still supplies features, activation limits
     * and version entitlement that the provider does not model.
     */
    protected function providerStatus(LicenseProvider $provider, string $version): array
    {
        $key = (string) ($provider->currentKey() ?? '');
        $domain = $this->normalizeDomain((string) config('app.url'));

        $base = [
            'version' => $version,
            'entitled_version' => null,
            'expires_at' => null,
            'support_expires_at' => null,
            'features' => [],
            'license' => null,
        ];

        if ($key === '') {
            return $base + [
                'licensed' => false,
                'state' => 'unlicensed',
                'message' => 'No license key is installed. The CMS runs in evaluation mode.',
            ];
        }

        $result = $provider->verify($key, $domain);
        $local = License::where('license_key', $key)->first();

        $entitled = $local->version_entitlement ?? ($result['entitled_version'] ?? null);
        $withinVersion = ! $entitled || version_compare($version, $entitled, '<=');

        $state = match (true) {
            (bool) ($local?->status === 'banned') => 'banned',
            (bool) ($local?->status === 'suspended') => 'suspended',
            ! ($result['ok'] ?? false) => 'invalid',
            ! $withinVersion => 'outdated',
            default => 'valid',
        };

        return $base + [
            'licensed' => $state === 'valid',
            'state' => $state,
            'entitled_version' => $entitled,
            'expires_at' => $result['expires_at'] ?? optional($local?->expires_at)->toDateString(),
            'support_expires_at' => optional($local?->support_expires_at)->toDateString(),
            'features' => $result['features'] ?? ($local->features ?? []),
            'license' => $local,
            'message' => $result['message'] ?? ($state === 'valid' ? 'Licensed.' : ucfirst($state)),
        ];
    }

    /**
     * Persist the activation payload.
     *
     * The key IS encrypted at rest using the application key, so the file is
     * not readable as a plaintext secret by anyone who can read the file but
     * not the environment. The v3 marketplace kit uses a separate, stronger
     * scheme (RSA-signed payload + AES-256-GCM lock); this file is the local
     * fallback used when no provider is configured.
     */
    public function install(array $payload): array
    {
        $key = (string) $payload['license_key'];
        $result = $this->activate($key, (string) $payload['domain'], $payload['ip'] ?? null);

        if (! $result['ok']) {
            return $result;
        }

        $body = json_encode([
            'license_key' => $key,
            'domain' => $this->normalizeDomain((string) $payload['domain']),
            'installed_at' => now()->toIso8601String(),
            'version' => config('lindu.version'),
        ], JSON_UNESCAPED_SLASHES);

        $path = 'lindu/license.json';
        Storage::disk('local')->put($path, Crypt::encryptString($body));
        @chmod(Storage::disk('local')->path($path), 0600);

        return ['ok' => true, 'message' => 'License installed'];
    }

    public function uninstall(): void
    {
        Storage::disk('local')->delete('lindu/license.json');
    }

    public function installedLock(): ?array
    {
        if (! Storage::disk('local')->exists('lindu/license.json')) {
            return null;
        }

        $raw = (string) Storage::disk('local')->get('lindu/license.json');

        // Written encrypted by install(); fall back to plaintext so a lock
        // file written by an earlier build still resolves.
        try {
            $raw = Crypt::decryptString($raw);
        } catch (\Throwable $e) {
            // Not encrypted — carry on and read it as-is.
        }

        $json = json_decode($raw, true);

        return is_array($json) ? $json : null;
    }

    public function normalizeDomain(string $domain): string
    {
        $domain = trim(strtolower($domain));
        $domain = preg_replace('#^https?://#', '', $domain) ?? $domain;
        $domain = explode('/', $domain)[0];
        $domain = str_replace('www.', '', $domain);

        return $domain;
    }

    protected function normalizeFeatures($features): array
    {
        if (is_string($features)) {
            $decoded = json_decode($features, true);
            $features = is_array($decoded) ? $decoded : array_filter(array_map('trim', preg_split('/[,\n]/', $features)));
        }

        return array_values(array_filter(array_map('strval', (array) $features)));
    }
}
