<?php

namespace App\Http\Controllers\Admin;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class SystemAdminController extends AdminController
{
    public function health()
    {
        return view('admin.system.health', [
            'checks' => app(\App\Core\Services\HealthService::class)->checks(),
            'info' => [
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'lindu' => config('lindu.version'),
            ],
        ]);
    }

    public function info()
    {
        return view('admin.system.info', [
            'info' => [
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'db' => config('database.default'),
                'queue' => config('queue.default'),
                'cache' => config('cache.default'),
            ],
        ]);
    }

    public function audits(Request $r)
    {
        $q = AuditLog::with('user')->latest();
        if ($s = $r->get('search')) {
            $q->where(function ($w) use ($s) {
                $w->where('action', 'like', "%{$s}%")
                    ->orWhere('entity_type', 'like', "%{$s}%");
            });
        }
        if ($action = $r->get('action')) {
            $q->where('action', $action);
        }

        return view('admin.system.audits', [
            'rows' => $q->paginate(25)->withQueryString(),
            'actions' => AuditLog::select('action')->distinct()->orderBy('action')->pluck('action'),
        ]);
    }

    public function activity(Request $r)
    {
        $q = ActivityLog::with('user')->latest();
        if ($s = $r->get('search')) {
            $q->where(function ($w) use ($s) {
                $w->where('action', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            });
        }
        if ($action = $r->get('action')) {
            $q->where('action', $action);
        }

        return view('admin.system.activity', [
            'rows' => $q->paginate(30)->withQueryString(),
            'actions' => ActivityLog::select('action')->distinct()->orderBy('action')->pluck('action'),
        ]);
    }

    public function logs(Request $r)
    {
        $channel = $r->get('channel', 'stack');
        $level = $r->get('level');
        $path = storage_path('logs/laravel.log');

        $lines = [];
        if (File::exists($path)) {
            $content = File::get($path);
            // Keep the tail only; the log can be very large in production.
            $content = implode("\n", array_slice(explode("\n", $content), -2000));
            foreach (explode("\n", $content) as $line) {
                $decoded = json_decode($line, true);
                if (! is_array($decoded)) {
                    continue;
                }
                if ($level && ($decoded['level'] ?? '') !== $level) {
                    continue;
                }
                $lines[] = $decoded;
            }
        }
        $lines = array_reverse($lines);
        $lines = array_slice($lines, 0, 300);

        return view('admin.system.logs', [
            'lines' => $lines,
            'levels' => ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'],
            'level' => $level,
            'exists' => File::exists($path),
            'size' => File::exists($path) ? File::size($path) : 0,
        ]);
    }

    public function clearLogs(Request $r)
    {
        $path = storage_path('logs/laravel.log');
        if (File::exists($path)) {
            File::put($path, '');
        }
        foreach (File::glob(storage_path('logs/*.log')) as $f) {
            if ($f !== $path) {
                File::put($f, '');
            }
        }
        $this->audit('clear_logs', null, $r);

        return back()->with('ok', 'Log files truncated');
    }

    public function downloadLog(Request $r)
    {
        $path = storage_path('logs/laravel.log');
        abort_unless(File::exists($path), 404, 'No log file present.');

        return response()->download($path, 'laravel-'.now()->format('Ymd-His').'.log');
    }

    public function schedule()
    {
        return view('admin.system.schedule', [
            'commands' => $this->registeredScheduleCommands(),
        ]);
    }

    protected function registeredScheduleCommands(): array
    {
        $commands = [];
        foreach (\Illuminate\Support\Facades\Artisan::all() as $name => $command) {
            $commands[] = [
                'name' => $name,
                'description' => $command->getDescription() ?: '',
            ];
        }
        usort($commands, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $commands;
    }

    public function cache()
    {
        $driver = config('cache.default');

        // These helpers are not all present on every Laravel release, so ask
        // the filesystem rather than assuming an API exists.
        $cached = fn (string $file) => file_exists(base_path('bootstrap/cache/'.$file.'.php'));

        return view('admin.system.cache', [
            'driver' => $driver,
            'menuKeys' => $this->countKeys('lindu.menu.'),
            'settingsCached' => rescue(fn () => Cache::has('lindu.settings.all'), false),
            'configCached' => rescue(fn () => $this->app->configurationIsCached(), $cached('config')),
            'routeCached' => rescue(fn () => $this->app->routesAreCached(), $cached('routes-v7')),
            'viewCached' => $cached('views'),
            'eventsCached' => $cached('events'),
        ]);
    }

    protected function countKeys(string $prefix): int
    {
        try {
            if (config('cache.default') === 'database') {
                return (int) DB::table(config('cache.stores.database.table', 'cache'))
                    ->where('key', 'like', $prefix.'%')->count();
            }
            if (config('cache.default') === 'file') {
                $path = config('cache.stores.file.path');
                if (! is_dir($path)) {
                    return 0;
                }
                $n = 0;
                $needle = preg_replace('/[^a-z0-9]/i', '', $prefix);
                $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));
                foreach ($it as $f) {
                    if ($f->isFile() && str_contains($f->getFilename(), $needle)) {
                        $n++;
                    }
                }

                return $n;
            }
        } catch (\Throwable $e) {
            return 0;
        }

        return 0;
    }

    public function flushCache(Request $r)
    {
        $target = $r->input('target', 'all');
        $done = [];

        if ($target === 'all' || $target === 'app') {
            $done[] = 'application cache';
        }
        if ($target === 'all' || $target === 'config') {
            Artisan::call('config:clear');
            $done[] = 'config cache';
        }
        if ($target === 'all' || $target === 'route') {
            Artisan::call('route:clear');
            $done[] = 'route cache';
        }
        if ($target === 'all' || $target === 'view') {
            Artisan::call('view:clear');
            $done[] = 'compiled views';
        }
        if ($target === 'all' || $target === 'events') {
            Artisan::call('event:clear');
            $done[] = 'event cache';
        }

        $this->audit('flush_cache', null, $r);

        return back()->with('ok', 'Cleared: '.implode(', ', $done));
    }

    public function storage()
    {
        $disks = [];
        foreach (['local', 'public', 's3'] as $name) {
            try {
                $disk = \Illuminate\Support\Facades\Storage::disk($name);
                $disks[] = [
                    'name' => $name,
                    'root' => method_exists($disk, 'path') ? $disk->path('') : '(remote)',
                    'exists' => rescue(fn () => $disk->exists(''), true),
                    'files' => rescue(fn () => count($disk->allFiles()), 0),
                ];
            } catch (\Throwable $e) {
                $disks[] = ['name' => $name, 'root' => '—', 'exists' => false, 'files' => 0];
            }
        }

        $usage = [];
        foreach (['storage/app', 'storage/framework/cache', 'storage/framework/views', 'storage/logs', 'public/storage'] as $rel) {
            $path = base_path($rel);
            $usage[] = [
                'path' => $rel,
                'exists' => is_dir($path),
                'size' => is_dir($path) ? $this->dirSize($path) : 0,
                'writable' => is_dir($path) ? is_writable($path) : false,
            ];
        }

        return view('admin.system.storage', [
            'disks' => $disks,
            'usage' => $usage,
            'mediaCount' => rescue(fn () => \App\Models\MediaFile::count(), 0),
        ]);
    }

    protected function dirSize(string $path): int
    {
        $total = 0;
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile()) {
                $total += $f->getSize();
            }
        }

        return $total;
    }

    public function database()
    {
        $tables = [];
        $totalRows = 0;

        try {
            $driver = DB::connection()->getDriverName();

            if ($driver === 'sqlite') {
                $names = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
            } else {
                $names = DB::select('SHOW TABLES');
            }

            foreach ($names as $n) {
                $table = is_object($n) ? (array) $n : (array) $n;
                $table = (string) reset($table);

                $count = rescue(fn () => DB::table($table)->count(), null);
                $size = rescue(fn () => $this->tableSize($driver, $table), 0);
                $tables[] = ['name' => $table, 'rows' => $count, 'size' => $size];
                $totalRows += (int) $count;
            }
        } catch (\Throwable $e) {
            $tables = [];
        }

        return view('admin.system.database', [
            'tables' => $tables,
            'totalRows' => $totalRows,
            'driver' => config('database.default'),
            'pending' => rescue(fn () => \Illuminate\Support\Facades\Artisan::call('migrate:status'), null),
        ]);
    }

    protected function tableSize(string $driver, string $table): int
    {
        if ($driver === 'mysql') {
            $row = DB::selectOne('SELECT (data_length + index_length) AS size FROM information_schema.TABLES WHERE table_schema = DATABASE() AND table_name = ?', [$table]);

            return (int) (is_object($row) ? $row->size ?? 0 : 0);
        }

        return 0;
    }

    public function maintenance(Request $r)
    {
        return view('admin.system.maintenance', [
            'enabled' => (bool) setting('maintenance.enabled', false),
            'allowedIps' => array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) setting('maintenance.allowed_ips', ''))))),
            'artisan' => true,
        ]);
    }

    public function toggleMaintenance(Request $r)
    {
        $enabled = $r->boolean('enabled');
        app(\App\Core\Services\SettingService::class)->set('maintenance.enabled', $enabled, 'boolean', 'maintenance');

        if ($enabled) {
            Artisan::call('down', ['--render' => 'errors::503']);
        } else {
            Artisan::call('up');
        }

        $this->audit('toggle_maintenance', null, $r);

        return back()->with('ok', $enabled ? 'Maintenance mode enabled' : 'Maintenance mode disabled');
    }

    public function optimize(Request $r)
    {
        $steps = [
            'config' => 'config:cache',
            'routes' => 'route:cache',
            'views' => 'view:cache',
            'events' => 'event:cache',
        ];

        $output = [];
        foreach ($steps as $label => $command) {
            try {
                Artisan::call($command);
                $output[$label] = 'ok';
            } catch (\Throwable $e) {
                $output[$label] = 'failed: '.$e->getMessage();
            }
        }

        $this->audit('optimize', null, $r);

        return back()->with('ok', 'Optimization: '.json_encode($output));
    }

    public function runArtisan(Request $r)
    {
        $command = (string) $r->validate(['command' => 'required|string|max:190'])['command'];

        // Whitelist: never let a web form run arbitrary artisan arguments.
        $blocked = ['migrate:fresh', 'db:wipe', 'env:clear', 'key:clear', 'config:clear', 'cache:clear', 'route:clear', 'view:clear', 'down'];
        foreach ($blocked as $b) {
            if (str_starts_with($command, $b)) {
                return back()->withErrors(['msg' => "Command '{$b}' is not allowed from the admin UI."]);
            }
        }

        try {
            $exit = Artisan::call($command);
            $out = Artisan::output();

            return back()->with('ok', "exit={$exit}\n".\Illuminate\Support\Str::limit($out, 1500));
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
    }

    public function backups()
    {
        return view('admin.system.backups', [
            'rows' => \App\Models\Backup::latest()->paginate(20),
        ]);
    }

    public function runBackup(Request $r)
    {
        $type = $r->validate(['type' => 'nullable|in:full,database,files'])['type'] ?? 'full';
        $b = app(\App\Core\Services\BackupService::class)->run($type);
        $this->audit('run_backup', $b, $r);

        return back()->with('ok', 'Backup '.($b->status === 'completed' ? 'completed' : 'failed: '.$b->log));
    }

    public function destroyBackup(Request $r, \App\Models\Backup $backup)
    {
        app(\App\Core\Services\BackupService::class)->delete($backup);
        $this->audit('delete_backup', $backup, $r);

        return back()->with('ok', 'Backup deleted');
    }

    public function restore()
    {
        return view('admin.system.restore', [
            'backups' => \App\Models\Backup::where('status', 'completed')->latest()->get(),
        ]);
    }

    public function runRestore(Request $r)
    {
        $backup = \App\Models\Backup::where('status', 'completed')->findOrFail($r->validate(['backup_id' => 'required|integer'])['backup_id']);

        $result = app(\App\Core\Services\BackupService::class)->restore($backup, (bool) $r->boolean('confirm'));
        $this->audit('restore_backup', $backup, $r);

        if (! $result['ok']) {
            return back()->withErrors(['msg' => $result['message']]);
        }

        return back()->with('ok', $result['message']);
    }

    public function updates()
    {
        return view('admin.system.updates', [
            'core' => app(\App\Core\Services\UpdateService::class)->check('core'),
            'logs' => \App\Models\UpdateLog::latest()->limit(20)->get(),
        ]);
    }

    /**
     * Global admin search. Each source is wrapped so a missing table simply
     * contributes no results rather than breaking the page.
     */
    public function search(Request $r)
    {
        $q = trim((string) $r->get('q', ''));
        $groups = [];

        if (mb_strlen($q) >= 2) {
            $like = "%{$q}%";

            $groups['Pages'] = $this->safe(fn () => \App\Models\Page::where('title', 'like', $like)
                ->limit(8)->get()->map(fn ($p) => [
                    'title' => $p->title,
                    'sub' => '/p/'.$p->slug,
                    'url' => route('admin.cms.pages.edit', $p),
                ]), collect());

            $groups['Posts'] = $this->safe(fn () => \App\Models\Post::where('title', 'like', $like)
                ->limit(8)->get()->map(fn ($p) => [
                    'title' => $p->title,
                    'sub' => '/blog/'.$p->slug,
                    'url' => route('admin.cms.posts.edit', $p),
                ]), collect());

            $groups['Media'] = $this->safe(fn () => \App\Models\MediaFile::where('original_name', 'like', $like)
                ->limit(8)->get()->map(fn ($m) => [
                    'title' => $m->original_name,
                    'sub' => $m->mime,
                    'url' => route('admin.media.index', ['search' => $m->original_name]),
                ]), collect());

            $groups['Users'] = $this->safe(fn () => \App\Models\User::where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->limit(8)->get()->map(fn ($u) => [
                    'title' => $u->name,
                    'sub' => $u->email,
                    'url' => route('admin.users.index'),
                ]), collect());

            $groups['Services'] = $this->safe(fn () => \App\Models\Cp\Service::where('title', 'like', $like)
                ->limit(8)->get()->map(fn ($s) => [
                    'title' => $s->title,
                    'sub' => '/services/'.$s->slug,
                    'url' => route('admin.company.edit', ['services', $s->id]),
                ]), collect());

            $groups['Content records'] = $this->safe(fn () => \App\Models\ContentRecord::where('data', 'like', $like)
                ->limit(8)->get()->map(fn ($c) => [
                    'title' => 'Record #'.$c->id,
                    'sub' => json_encode($c->data),
                    'url' => route('admin.cms.records.list', $c->content_type_id),
                ]), collect());

            $groups = array_filter($groups, fn ($rows) => $rows->isNotEmpty());
        }

        $total = array_sum(array_map(fn ($rows) => $rows->count(), $groups));

        if ($r->expectsJson()) {
            return response()->json(['query' => $q, 'total' => $total, 'groups' => $groups]);
        }

        return view('admin.system.search', [
            'q' => $q,
            'groups' => $groups,
            'total' => $total,
        ]);
    }
}
