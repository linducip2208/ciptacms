<?php

namespace App\Core\Services;

use Illuminate\Support\Facades\File;

/**
 * Install state.
 *
 * The lock file marks a completed installation. Once it exists the browser
 * installer refuses to run again — reopening it requires a deliberate
 * `php artisan lindu:unlock --force`, or a signed recovery token.
 *
 * The lock lives in storage/app, which is outside the web root.
 */
class InstallLock
{
    public function path(): string
    {
        return (string) config('lindu.installer_lock', storage_path('app/installed'));
    }

    public function installed(): bool
    {
        return File::exists($this->path());
    }

    /** @return array{files:bool,framework:bool,app:bool}|null */
    public function payload(): ?array
    {
        if (! $this->installed()) {
            return null;
        }

        $raw = File::get($this->path());
        $json = json_decode($raw, true);

        return is_array($json) ? $json : ['installed_at' => $raw];
    }

    public function installedAt(): ?string
    {
        return $this->payload()['installed_at'] ?? null;
    }

    public function lock(string $appUrl, string $appName, string $adminEmail): void
    {
        $path = $this->path();
        File::ensureDirectoryExists(dirname($path));

        File::put($path, json_encode([
            'installed_at' => now()->toIso8601String(),
            'app_url' => $appUrl,
            'app_name' => $appName,
            'admin_email' => $adminEmail,
            'version' => config('lindu.version'),
            'recovery_hint' => 'Reopen with: php artisan lindu:unlock --force',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        @chmod($path, 0640);
    }

    public function unlock(): bool
    {
        if (! $this->installed()) {
            return false;
        }

        File::delete($this->path());

        return true;
    }

    /**
     * A recovery token is derived from the application key, so it cannot be
     * guessed and is useless if APP_KEY leaks no further than the lock file.
     * Recovery is intentionally a two-factor style step: a local command OR
     * a token, never a web request alone.
     */
    public function recoveryToken(): string
    {
        $key = (string) config('app.key');

        return substr(hash_hmac('sha256', 'lindu-installer-recovery|'.$this->path(), $key), 0, 32);
    }

    public function verifyToken(?string $token): bool
    {
        return is_string($token)
            && $token !== ''
            && hash_equals($this->recoveryToken(), $token);
    }
}
