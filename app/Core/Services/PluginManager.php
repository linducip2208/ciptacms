<?php

namespace App\Core\Services;

use App\Core\Plugins\PluginException;
use App\Core\Plugins\PluginInterface;
use App\Core\Plugins\PluginLoader;
use App\Core\Plugins\UnknownPluginException;
use App\Models\Plugin;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * The plugin engine.
 *
 * Discovery and the plugins table are unchanged in shape; what is new is that
 * an activated plugin now actually runs:
 *
 *   - filters: applyFilter() walks the active plugins in priority order and
 *     feeds the value through every plugin that declares the filter name.
 *   - hooks:   hooks() maps events to plugin methods; the listeners are
 *     registered once per request when the manager is resolved, so a core
 *     `event('cms.contact.message', ...)` reaches plugin code untouched.
 *   - actions: a URL may only name an action key that the plugin's own
 *     manifest maps to a method. The URL never names a method itself.
 *
 * Everything a slug touches is resolved through PluginLoader, which matches it
 * against plugin directories on disk. No class name is ever built from request
 * input.
 */
class PluginManager
{
    /** Lifecycle verbs the admin UI may trigger. */
    public const ACTIONS = ['install', 'activate', 'deactivate', 'uninstall', 'update'];

    /**
     * Methods a plugin may never be asked to run, whatever a manifest says.
     * Belt-and-braces: the manifest map is the allowlist, this is the floor.
     */
    public const FORBIDDEN_METHODS = [
        '__construct', '__destruct', '__call', '__callstatic', '__get', '__set',
        '__invoke', '__wakeup', '__sleep', '__serialize', '__unserialize',
        'filter', 'hooks', 'filters', 'manifest', 'slug', 'oninstall', 'onuninstall',
    ];

    protected PluginLoader $loader;

    protected bool $booted = false;

    /** @var array<string, true> "event@slug" already registered */
    protected array $registered = [];

    /** @var array<string, PluginInterface>|null lazily built active set */
    protected ?array $active = null;

    public function __construct(?PluginLoader $loader = null)
    {
        $this->loader = $loader ?: new PluginLoader;
        $this->ensureBooted();
    }

    /* ------------------------------------------------------------------ */
    /* Discovery                                                           */
    /* ------------------------------------------------------------------ */

    /**
     * Every plugin directory holding a readable plugin.json.
     *
     * @return array<int, array<string, mixed>>
     */
    public function discover(): array
    {
        return array_values($this->loader->manifests());
    }

    public function find(string $slug): ?array
    {
        return $this->loader->manifest($slug);
    }

    public function loader(): PluginLoader
    {
        return $this->loader;
    }

    /**
     * A plugin is "installed" when a readable manifest exists on disk.
     * Whether it is also registered in the database is a separate question.
     */
    public function isInstalled(string $slug): bool
    {
        return $this->loader->isInstalled($slug);
    }

    /** A plugin is active when the database says so AND it is on disk. */
    public function isInstalledAndActive(string $slug): bool
    {
        return $this->isInstalled($slug) && $this->isActive($slug);
    }

    public function syncRegistry(): void
    {
        foreach ($this->discover() as $p) {
            $row = Plugin::where('slug', $p['slug'])->first();
            Plugin::updateOrCreate(['slug' => $p['slug']], [
                'name' => $p['name'] ?? $p['slug'],
                'version' => $p['version'] ?? '1.0.0',
                'author' => $p['author'] ?? null,
                'description' => $p['description'] ?? null,
                'meta' => $p,
                'is_installed' => true,
                'is_active' => $row ? (bool) $row->is_active : false,
            ]);
        }
    }

    /* ------------------------------------------------------------------ */
    /* Active set                                                          */
    /* ------------------------------------------------------------------ */

    /**
     * Loaded instances of the plugins that are installed *and* active, ordered
     * by manifest priority (asc) then slug, so a filter chain is deterministic.
     *
     * @return array<string, PluginInterface>
     */
    public function activeInstances(): array
    {
        if ($this->active !== null) {
            return $this->active;
        }

        $out = [];

        try {
            $rows = Plugin::where('is_installed', true)->where('is_active', true)->get();
        } catch (\Throwable $e) {
            // The plugins table may not exist yet (installer window).
            return $this->active = [];
        }

        $ordered = $rows->sortBy(function (Plugin $row) {
            return [(int) (($row->meta['priority'] ?? 10)), (string) $row->slug];
        })->values();

        foreach ($ordered as $row) {
            try {
                $plugin = $this->loader->instance($row->slug);
            } catch (PluginException $e) {
                report($e);

                continue;
            }
            if ($plugin) {
                $out[$row->slug] = $plugin;
            }
        }

        return $this->active = $out;
    }

    /**
     * Register every active plugin's hooks as Laravel listeners. Runs once per
     * request from the constructor, so any `event()` dispatched later in the
     * request reaches plugin code.
     */
    public function ensureBooted(): void
    {
        if ($this->booted) {
            return;
        }
        $this->booted = true;

        try {
            foreach ($this->activeInstances() as $slug => $plugin) {
                $this->registerHooks($slug, $plugin);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function registerHooks(string $slug, PluginInterface $plugin): void
    {
        foreach ($this->normaliseHooks($plugin) as $event => $method) {
            if (isset($this->registered[$event.'@'.$slug])) {
                continue;
            }
            if (! $this->isCallablePluginMethod($plugin, $method)) {
                report(new PluginException("Plugin [{$slug}] maps hook [{$event}] to missing or non-public method [{$method}]."));

                continue;
            }

            $this->registered[$event.'@'.$slug] = true;

            Event::listen($event, function (...$args) use ($plugin, $method) {
                try {
                    // The dispatcher passes (event name, payload). Taking the
                    // last argument covers that and the payload-only shape.
                    // Spreading both made every hook throw a TypeError that
                    // the catch below then swallowed, so no plugin hook ran.
                    $payload = $args ? end($args) : [];

                    return $plugin->{$method}(is_array($payload) ? $payload : []);
                } catch (\Throwable $e) {
                    // A broken plugin must not take the public site down.
                    report($e);

                    return null;
                }
            });
        }
    }

    /**
     * Accept both hook shapes: `['event.name' => 'method']` and the bare list
     * `['event.name']`, in which case the method is derived as
     * `on` . studly(event name) — `page.rendered` becomes `onPageRendered`.
     *
     * @return array<string, string>
     */
    public function normaliseHooks(PluginInterface $plugin): array
    {
        $out = [];

        foreach ((array) $plugin->hooks() as $key => $value) {
            if (is_int($key)) {
                if (! is_string($value) || $value === '') {
                    continue;
                }
                $event = $value;
                $method = 'on'.Str::studly(str_replace(['.', '-', ':'], '_', $event));
            } else {
                $event = (string) $key;
                $method = is_string($value) && $value !== ''
                    ? $value
                    : 'on'.Str::studly(str_replace(['.', '-', ':'], '_', $event));
            }

            if ($event === '' || $method === '') {
                continue;
            }
            $out[$event] = $method;
        }

        return $out;
    }

    /** Is $method a public, non-forbidden method the plugin actually has? */
    public function isCallablePluginMethod(PluginInterface $plugin, string $method): bool
    {
        if ($method === '' || in_array(strtolower($method), self::FORBIDDEN_METHODS, true)) {
            return false;
        }
        if (! method_exists($plugin, $method)) {
            return false;
        }

        try {
            return (new \ReflectionMethod($plugin, $method))->isPublic()
                && ! (new \ReflectionMethod($plugin, $method))->isStatic();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /* ------------------------------------------------------------------ */
    /* Filters                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * Run a filter chain. Every active plugin that declares $name in its
     * filters() gets a chance to transform the value; the last return value
     * wins. A plugin that throws is reported and skipped.
     */
    public function applyFilter(string $name, mixed $value, array $context = []): mixed
    {
        $this->ensureBooted();

        foreach ($this->activeInstances() as $slug => $plugin) {
            $names = (array) $plugin->filters();
            if (! in_array($name, $names, true)) {
                continue;
            }
            try {
                $next = $plugin->filter($name, $value, $context);
            } catch (\Throwable $e) {
                report($e);

                continue;
            }
            if ($next !== null) {
                $value = $next;
            }
        }

        return $value;
    }

    /**
     * Backwards-compatible entry point. Previously this returned its input
     * unchanged; it now runs the chain.
     */
    public static function filters(string $filter, mixed $value, array $ctx = []): mixed
    {
        return app(self::class)->applyFilter($filter, $value, $ctx);
    }

    /* ------------------------------------------------------------------ */
    /* Hooks                                                               */
    /* ------------------------------------------------------------------ */

    /**
     * Backwards-compatible entry point. Now genuinely invokes the handlers
     * that declare $hook and returns the payload they produced, with the
     * slugs that ran listed under `plugins`.
     *
     * Listeners registered through Laravel's event system are the normal path;
     * this is for callers that already hold a payload and want it transformed
     * without going through the event dispatcher.
     */
    public static function hooks(string $hook, array $payload = []): array
    {
        return app(self::class)->dispatchHook($hook, $payload);
    }

    public function dispatchHook(string $hook, array $payload = []): array
    {
        $this->ensureBooted();

        $out = $payload;
        $handled = (array) ($payload['plugins'] ?? []);

        foreach ($this->activeInstances() as $slug => $plugin) {
            $method = $this->methodForHook($plugin, $hook);
            if ($method === null) {
                continue;
            }
            try {
                $result = $plugin->{$method}($payload);
            } catch (\Throwable $e) {
                report($e);

                continue;
            }
            $handled[] = $slug;
            if (is_array($result)) {
                $out = $result;
            }
        }

        $out['plugins'] = array_values(array_unique(array_filter($handled, 'is_string')));

        return $out;
    }

    /** The method a plugin declares for $event, or null. */
    public function methodForHook(PluginInterface $plugin, string $event): ?string
    {
        $method = $this->normaliseHooks($plugin)[$event] ?? null;

        return is_string($method) && $this->isCallablePluginMethod($plugin, $method) ? $method : null;
    }

    /* ------------------------------------------------------------------ */
    /* Lifecycle                                                           */
    /* ------------------------------------------------------------------ */

    public function install(string $slug): void
    {
        $manifest = $this->requireManifest($slug);
        $row = Plugin::where('slug', $slug)->first();
        $wasActive = (bool) $row?->is_active;

        $plugin = $this->loader->instance($slug);

        Plugin::updateOrCreate(['slug' => $slug], [
            'name' => $manifest['name'] ?? $slug,
            'version' => $manifest['version'] ?? '1.0.0',
            'author' => $manifest['author'] ?? null,
            'description' => $manifest['description'] ?? null,
            'meta' => $manifest,
            'is_installed' => true,
            'is_active' => $wasActive,
        ]);

        $this->runMigrations($manifest);
        $this->runLifecycle($plugin, 'onInstall');
        $this->flush();

        $this->audit('install_plugin', $slug);
    }

    public function activate(string $slug): void
    {
        $this->requireManifest($slug);
        $row = Plugin::where('slug', $slug)->first();

        if (! $row || ! $row->is_installed) {
            $this->install($slug);
        }

        $this->syncRow($slug);
        $this->runMigrations($this->requireManifest($slug));
        Plugin::where('slug', $slug)->update(['is_active' => true]);
        $this->flush();

        $this->audit('activate_plugin', $slug);
    }

    public function deactivate(string $slug): void
    {
        Plugin::where('slug', $slug)->update(['is_active' => false]);
        $this->flush();

        $this->audit('deactivate_plugin', $slug);
    }

    /** Re-read the manifest (new version, new class) and catch migrations up. */
    public function update(string $slug): void
    {
        $manifest = $this->requireManifest($slug);
        $this->syncRow($slug);
        $this->loader->flush();
        $this->runMigrations($manifest);
        $this->flush();

        $this->audit('update_plugin', $slug);
    }

    public function uninstall(string $slug): void
    {
        $plugin = null;
        try {
            $plugin = $this->loader->instance($slug);
        } catch (PluginException $e) {
            report($e);
        }

        Plugin::where('slug', $slug)->update(['is_active' => false, 'is_installed' => false]);
        $this->runLifecycle($plugin, 'onUninstall');
        $this->flush();

        $this->audit('uninstall_plugin', $slug);
    }

    public function isActive(string $slug): bool
    {
        try {
            return Plugin::where('slug', $slug)->where('is_active', true)->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /* ------------------------------------------------------------------ */
    /* URL-invoked plugin actions                                          */
    /* ------------------------------------------------------------------ */

    /**
     * Run a plugin method named by an HTTP request.
     *
     * The request supplies an *action key*, never a method name. The key must
     * appear in the plugin's own `actions` map, and the method it maps to must
     * be a public, non-forbidden method of a loaded plugin. Anything else
     * raises PluginException, which controllers turn into a 404.
     *
     * @throws UnknownPluginException when the slug is not a plugin directory
     * @throws PluginException        when the action is not on the allowlist
     */
    public function callAction(string $slug, string $action, array $args = []): mixed
    {
        $manifest = $this->requireManifest($slug);

        if (! $this->isInstalled($slug)) {
            throw new PluginException("Plugin [{$slug}] is not installed.");
        }

        $allowed = (array) ($manifest['actions'] ?? []);
        $method = $allowed[$action] ?? null;

        if (! is_string($method) || $method === '') {
            throw new PluginException("Plugin [{$slug}] does not expose the action [{$action}].");
        }

        $plugin = $this->loader->instance($slug);
        if (! $plugin) {
            throw new PluginException("Plugin [{$slug}] has no loadable class.");
        }

        if (! $this->isCallablePluginMethod($plugin, $method)) {
            throw new PluginException("Plugin [{$slug}] action [{$action}] does not resolve to a callable method.");
        }

        try {
            return $plugin->{$method}(...$args);
        } catch (PluginException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            throw new PluginException("Plugin [{$slug}] failed while running [{$action}]: ".$e->getMessage());
        }
    }

    /* ------------------------------------------------------------------ */
    /* Internals                                                           */
    /* ------------------------------------------------------------------ */

    /** Drop per-request caches so a lifecycle change is visible immediately. */
    /**
     * Drop every piece of cached plugin state.
     *
     * This must also clear `booted` and the registered-hook map. Clearing
     * only the instances left `booted` true, so ensureBooted() returned
     * early for the rest of the process and a plugin activated at runtime
     * never got its hooks registered.
     */
    public function flush(): void
    {
        $this->loader->flush();
        $this->active = null;
        $this->booted = false;
        $this->registered = [];
    }

    protected function requireManifest(string $slug): array
    {
        $manifest = $this->loader->manifest($slug);
        if ($manifest === null) {
            throw new UnknownPluginException("Unknown plugin [{$slug}].");
        }

        return $manifest;
    }

    protected function syncRow(string $slug): Plugin
    {
        $manifest = $this->requireManifest($slug);
        $row = Plugin::where('slug', $slug)->first();
        if (! $row) {
            $row = Plugin::updateOrCreate(['slug' => $slug], [
                'name' => $manifest['name'] ?? $slug,
                'version' => $manifest['version'] ?? '1.0.0',
                'author' => $manifest['author'] ?? null,
                'description' => $manifest['description'] ?? null,
                'meta' => $manifest,
                'is_installed' => true,
            ]);
        }

        return $row;
    }

    protected function runMigrations(array $manifest): void
    {
        $path = ($manifest['path'] ?? '').'/database/migrations';
        if (! is_dir($path)) {
            return;
        }

        try {
            Artisan::call('migrate', [
                '--path' => ltrim(str_replace(base_path(), '', $path), '/'),
                '--force' => true,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function runLifecycle(?PluginInterface $plugin, string $method): void
    {
        if (! $plugin) {
            return;
        }
        try {
            $plugin->{$method}();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function audit(string $action, string $slug): void
    {
        try {
            app(AuditService::class)->log($action, ['slug' => $slug]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Manifest list without the resolved paths — safe to hand to a view. */
    public function catalogue(): array
    {
        return array_map(function (array $m) {
            unset($m['path'], $m['dir']);

            return $m;
        }, $this->discover());
    }

    /** Files shipped inside a plugin directory, for the developer screen. */
    public function files(string $slug): array
    {
        $manifest = $this->loader->manifest($slug);
        $dir = $manifest['path'] ?? null;
        if (! $dir || ! is_dir($dir)) {
            return [];
        }

        $out = [];
        foreach (File::allFiles($dir) as $file) {
            $out[] = Str::after($file->getPathname(), $dir.DIRECTORY_SEPARATOR);
        }
        sort($out);

        return $out;
    }
}
