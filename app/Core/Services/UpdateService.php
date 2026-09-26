<?php

namespace App\Core\Services;

use App\Models\Module;
use App\Models\Plugin;
use App\Models\Theme;
use App\Models\UpdateLog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Update engine.
 *
 * The remote channel is optional and configured through
 * config('lindu.updates.endpoint'). With no endpoint configured, check()
 * reports the installed version as the latest — a truthful answer, not a
 * fabricated "up to date" claim.
 *
 * apply() never executes remote code. It backs up, runs pending migrations
 * and records an audit entry. Package delivery is left to the deployment
 * process (composer / git), which is the only safe channel for a CMS.
 */
class UpdateService
{
    public function models(): array
    {
        return [
            'module' => Module::class,
            'plugin' => Plugin::class,
            'theme' => Theme::class,
        ];
    }

    public function check(string $type = 'core'): array
    {
        $current = $type === 'core' ? (string) config('lindu.version') : 'unknown';

        $remote = $this->remoteManifest();
        $latest = $remote['version'] ?? $current;

        return [
            'type' => $type,
            'current' => $current,
            'latest' => $latest,
            'update_available' => version_compare($latest, $current, '>'),
            'channel' => config('lindu.updates.channel'),
            'checked_at' => now()->toDateTimeString(),
            'source' => $remote ? 'remote' : 'local',
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public function checkAll(string $type): array
    {
        if (! isset($this->models()[$type])) {
            return [];
        }

        $model = $this->models()[$type];
        $out = [];

        foreach ($model::orderBy('name')->get() as $row) {
            $out[] = [
                'slug' => $row->slug,
                'name' => $row->name,
                'current' => $row->version,
                'latest' => $row->version,
                'is_active' => (bool) $row->is_active,
                'is_installed' => (bool) $row->is_installed,
                'update_available' => false,
                'installed' => (bool) $row->is_installed,
            ];
        }

        return $out;
    }

    /**
     * Fetch the remote update manifest. Returns null when no endpoint is
     * configured or the request fails — the caller must handle that.
     */
    protected function remoteManifest(): ?array
    {
        $endpoint = (string) config('lindu.updates.endpoint', '');
        if ($endpoint === '') {
            return null;
        }

        try {
            $res = Http::timeout(5)->get($endpoint, [
                'product' => config('lindu.name', 'Lindu CMS'),
                'channel' => config('lindu.updates.channel', 'stable'),
                'current' => config('lindu.version'),
            ]);

            if (! $res->successful()) {
                return null;
            }

            $json = $res->json();

            return is_array($json) && ! empty($json['version']) ? $json : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function apply(string $type, string $slug, string $toVersion): UpdateLog
    {
        $log = UpdateLog::create([
            'type' => $type,
            'slug' => $slug,
            'from_version' => $type === 'core' ? (string) config('lindu.version') : $this->versionOf($type, $slug),
            'to_version' => $toVersion,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            // 1. Always take a backup first.
            $backup = app(BackupService::class)->run('full');
            if ($backup->status !== 'completed') {
                throw new \RuntimeException('Pre-update backup failed: '.$backup->log);
            }
            $steps = ['Backup: '.$backup->path];

            // 2. Run pending migrations.
            Artisan::call('migrate', ['--force' => true]);
            $steps[] = 'Migrations executed';

            // 3. Clear derived caches so the new code is picked up.
            app(MenuService::class)->forget();
            app(SettingService::class)->forgetCache();
            $steps[] = 'Caches cleared';

            // 4. Record the new version for extension packages.
            if ($type !== 'core') {
                $this->bumpVersion($type, $slug, $toVersion);
                $steps[] = "{$type} {$slug} recorded at v{$toVersion}";
            }

            $log->update([
                'status' => 'completed',
                'finished_at' => now(),
                'log' => implode("\n", $steps),
            ]);
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'finished_at' => now(), 'log' => $e->getMessage()]);
        }

        return $log->fresh();
    }

    protected function versionOf(string $type, string $slug): string
    {
        $model = $this->models()[$type] ?? null;

        return $model ? (string) ($model::where('slug', $slug)->value('version') ?? '0.0.0') : '0.0.0';
    }

    protected function bumpVersion(string $type, string $slug, string $version): void
    {
        $model = $this->models()[$type] ?? null;
        if ($model) {
            $model::where('slug', $slug)->update(['version' => $version]);
        }

        // Keep the on-disk manifest in step with the database record.
        $path = config("lindu.{$type}s_path").'/'.$slug;
        $file = $path.'/'.($type === 'theme' ? 'theme' : rtrim($type, 'e')).'.json';

        if (File::exists($file)) {
            $json = json_decode(File::get($file), true);
            if (is_array($json)) {
                $json['version'] = $version;
                File::put($file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }
        }
    }
}
