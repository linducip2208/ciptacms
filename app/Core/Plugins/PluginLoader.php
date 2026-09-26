<?php

namespace App\Core\Plugins;

use Illuminate\Support\Facades\File;

/**
 * Turns a plugin slug into a live PluginInterface instance — and refuses
 * everything else.
 *
 * This class is the only place a plugin class name is produced. A slug coming
 * off an HTTP request is never concatenated into a class name: it is matched
 * against the set of directories that actually exist under `plugins/`, and
 * only the class named by that directory's on-disk `plugin.json` is loaded.
 * The class file must resolve inside that same directory, and the loaded
 * object must implement PluginInterface and report the same slug.
 *
 * Plugin code is operator-installed and therefore trusted; the HTTP request
 * that names the slug is not. That asymmetry is why every check below is a
 * whitelist check.
 */
class PluginLoader
{
    /** Class names are only ever taken from a manifest, then sanity checked. */
    protected const CLASS_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*(\\\\[A-Za-z_][A-Za-z0-9_]*)*$/';

    /** @var array<string, array<string, mixed>>|null slug => manifest */
    protected ?array $manifestCache = null;

    /** @var array<string, PluginInterface> */
    protected array $instances = [];

    public function basePath(): string
    {
        return (string) config('lindu.plugins_path', base_path('plugins'));
    }

    /**
     * Every readable manifest, keyed by slug. Each entry also carries `path`
     * (the plugin directory) and `dir` (its basename).
     *
     * @return array<string, array<string, mixed>>
     */
    public function manifests(): array
    {
        if ($this->manifestCache !== null) {
            return $this->manifestCache;
        }

        $out = [];
        $base = $this->basePath();

        if (is_dir($base)) {
            foreach (File::directories($base) as $dir) {
                $meta = $dir.'/plugin.json';
                if (! File::exists($meta)) {
                    continue;
                }
                $json = json_decode(File::get($meta), true);
                if (! is_array($json) || empty($json['slug']) || ! is_string($json['slug'])) {
                    continue;
                }
                $slug = $json['slug'];
                if (! $this->isSafeSegment($slug)) {
                    continue;
                }
                $json['path'] = $dir;
                $json['dir'] = basename($dir);
                $out[$slug] = $json;
            }
        }

        return $this->manifestCache = $out;
    }

    /** The manifest for $slug, or null when no such plugin directory exists. */
    public function manifest(string $slug): ?array
    {
        if (! $this->isSafeSegment($slug)) {
            return null;
        }

        return $this->manifests()[$slug] ?? null;
    }

    public function isInstalled(string $slug): bool
    {
        return $this->manifest($slug) !== null;
    }

    /**
     * The live plugin for $slug, or null when the plugin has no loadable
     * class (a manifest-only plugin) or the class fails validation.
     *
     * @throws PluginException when the manifest exists but its class is
     *                         unusable — that is an operator error worth seeing.
     */
    public function instance(string $slug): ?PluginInterface
    {
        if (isset($this->instances[$slug])) {
            return $this->instances[$slug];
        }

        $manifest = $this->manifest($slug);
        if ($manifest === null) {
            return null;
        }

        $class = $manifest['class'] ?? null;
        if (! is_string($class) || $class === '') {
            return null; // manifest-only plugin: no behaviour, no error.
        }

        if (! preg_match(self::CLASS_PATTERN, $class)) {
            throw new PluginException("Plugin [{$slug}] declares an unusable class name.");
        }

        if (! class_exists($class, false)) {
            $this->requireClassFile($slug, $manifest, $class);
        }

        if (! class_exists($class, false)) {
            throw new PluginException("Plugin [{$slug}] class [{$class}] was not defined by {$manifest['path']}/src.");
        }

        $object = new $class;
        if (! $object instanceof PluginInterface) {
            throw new PluginException("Plugin [{$slug}] class [{$class}] does not implement ".PluginInterface::class.'.');
        }

        if ($object->slug() !== $slug) {
            throw new PluginException("Plugin [{$slug}] class [{$class}] reports slug [{$object->slug()}].");
        }

        return $this->instances[$slug] = $object;
    }

    /** Forget cached manifests/instances — used after install/uninstall. */
    public function flush(): void
    {
        $this->manifestCache = null;
        $this->instances = [];
    }

    /**
     * Load the class file from inside the plugin's own directory. The path is
     * built from the directory we already discovered, not from the slug, and
     * the result is confirmed to live under that directory.
     */
    protected function requireClassFile(string $slug, array $manifest, string $class): void
    {
        $file = $manifest['path'].'/src/'.str_replace('\\', '/', $class).'.php';
        $real = realpath($file);
        $root = realpath($manifest['path']);

        if ($real === false || $root === false || ! is_file($real)) {
            throw new PluginException("Plugin [{$slug}] is missing its class file [{$file}].");
        }

        if (! str_starts_with($real, $root.DIRECTORY_SEPARATOR)) {
            throw new PluginException("Plugin [{$slug}] class file escapes the plugin directory.");
        }

        require_once $real;
    }

    /** Slugs are a single path segment: no dots, slashes or traversal. */
    protected function isSafeSegment(string $slug): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,63}$/', $slug)
            && ! str_contains($slug, '..');
    }
}
