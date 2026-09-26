<?php

namespace App\Core\Services;

use App\Models\Module;
use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class ModuleManager
{
    public function discover(): array
    {
        $base = config('lindu.modules_path');
        $found = [];
        if (! is_dir($base)) {
            return $found;
        }
        foreach (File::directories($base) as $dir) {
            $meta = $dir.'/module.json';
            if (! File::exists($meta)) {
                continue;
            }
            $j = json_decode(File::get($meta), true);
            if (! $j || empty($j['slug'])) {
                continue;
            }
            $found[] = array_merge($j, ['path' => $dir]);
        }

        return $found;
    }

    public function find(string $slug): ?array
    {
        foreach ($this->discover() as $m) {
            if ($m['slug'] === $slug) {
                return $m;
            }
        }

        return null;
    }

    public function syncRegistry(): void
    {
        foreach ($this->discover() as $m) {
            Module::updateOrCreate(['slug' => $m['slug']], [
                'name' => $m['name'] ?? $m['slug'],
                'version' => $m['version'] ?? '1.0.0',
                'author' => $m['author'] ?? null,
                'description' => $m['description'] ?? null,
                'meta' => $m,
                'is_installed' => true,
            ]);
        }
    }

    /**
     * Register the menus and permissions declared in each active module's
     * manifest. Called from the seeder and after activation.
     */
    public function syncModuleMenus(): void
    {
        foreach ($this->discover() as $m) {
            $module = Module::where('slug', $m['slug'])->first();
            if (! $module || ! $module->is_active) {
                continue;
            }
            $this->registerMenus($module, $m);
            $this->registerPermissions($module, $m);
        }
    }

    protected function registerMenus(Module $module, array $manifest): void
    {
        foreach ((array) ($manifest['menus'] ?? []) as $i => $def) {
            $title = $def['title'] ?? null;
            if (! $title) {
                continue;
            }
            $parent = \App\Models\MenuItem::updateOrCreate(
                [
                    'location' => $def['location'] ?? 'admin',
                    'module' => $module->slug,
                    'title' => $title,
                    'parent_id' => $def['parent_id'] ?? null,
                ],
                [
                    'icon' => $def['icon'] ?? null,
                    'url' => $def['url'] ?? null,
                    'route' => $def['route'] ?? null,
                    'permission' => $def['permission'] ?? null,
                    'sort_order' => $def['sort_order'] ?? ($i * 10),
                    'is_visible' => $def['is_visible'] ?? true,
                    'meta' => $def['meta'] ?? null,
                ]
            );

            foreach ((array) ($def['children'] ?? []) as $j => $child) {
                if (! ($child['title'] ?? null)) {
                    continue;
                }
                \App\Models\MenuItem::updateOrCreate(
                    [
                        'location' => $def['location'] ?? 'admin',
                        'module' => $module->slug,
                        'title' => $child['title'],
                        'parent_id' => $parent->id,
                    ],
                    [
                        'icon' => $child['icon'] ?? null,
                        'url' => $child['url'] ?? null,
                        'route' => $child['route'] ?? null,
                        'permission' => $child['permission'] ?? null,
                        'sort_order' => $child['sort_order'] ?? ($j * 10),
                        'is_visible' => $child['is_visible'] ?? true,
                    ]
                );
            }
        }
    }

    protected function registerPermissions(Module $module, array $manifest): void
    {
        $perms = (array) ($manifest['permissions'] ?? []);
        if (! $perms) {
            return;
        }

        $group = PermissionGroup::firstOrCreate(
            ['slug' => $module->slug],
            ['name' => $module->name, 'description' => $module->description]
        );

        foreach ($perms as $p) {
            [$action, $name] = str_contains($p, '.')
                ? [Str::after($p, '.'), Str::before($p, '.')]
                : ['view', $p];

            Permission::updateOrCreate(
                ['slug' => $p],
                ['name' => Str::headline($name).' '.Str::headline($action), 'action' => $action, 'module' => $module->slug, 'group_id' => $group->id]
            );
        }
    }

    public function install(string $slug): void
    {
        $mod = Module::where('slug', $slug)->firstOrFail();
        $this->checkDeps($mod);
        $mod->update(['is_installed' => true]);
        $this->runMigrations($slug);
        $manifest = $this->find($slug);
        if ($manifest) {
            $this->registerMenus($mod->fresh(), $manifest);
            $this->registerPermissions($mod->fresh(), $manifest);
        }
        MenuService::forget();
        app(AuditService::class)->log('install_module', $mod);
    }

    public function activate(string $slug): void
    {
        $mod = Module::where('slug', $slug)->firstOrFail();
        $this->checkDeps($mod);
        $mod->update(['is_active' => true]);
        $this->runMigrations($slug);
        $manifest = $this->find($slug);
        if ($manifest) {
            $this->registerMenus($mod->fresh(), $manifest);
            $this->registerPermissions($mod->fresh(), $manifest);
        }
        MenuService::forget();
        app(AuditService::class)->log('activate_module', $mod);
    }

    public function deactivate(string $slug): void
    {
        Module::where('slug', $slug)->update(['is_active' => false]);
        // Remove menus so a disabled module cannot be navigated to.
        \App\Models\MenuItem::where('module', $slug)->delete();
        MenuService::forget();
        app(AuditService::class)->log('deactivate_module', Module::where('slug', $slug)->first());
    }

    public function uninstall(string $slug): void
    {
        Module::where('slug', $slug)->update(['is_active' => false, 'is_installed' => false]);
        \App\Models\MenuItem::where('module', $slug)->delete();
        MenuService::forget();
    }

    public function active()
    {
        return Module::where('is_active', true)->get();
    }

    public function isActive(string $slug): bool
    {
        try {
            return (bool) Module::where('slug', $slug)->where('is_active', true)->exists();
        } catch (\Throwable $e) {
            return true;
        }
    }

    /** Modules that declare a dependency on $slug. */
    public function dependents(string $slug): array
    {
        return Module::where('is_active', true)->get()
            ->filter(function ($m) use ($slug) {
                $deps = (array) (($m->meta['dependencies'] ?? []));
                $requires = (array) (($m->meta['requires'] ?? []));

                return in_array($slug, array_merge($deps, $requires), true);
            })
            ->pluck('slug')
            ->all();
    }

    protected function checkDeps($mod): void
    {
        $deps = (array) (($mod->meta['dependencies'] ?? []) ?: ($mod->meta['requires'] ?? []));
        foreach ($deps as $d) {
            $ok = Module::where('slug', $d)->where('is_active', true)->exists();
            if (! $ok) {
                throw new RuntimeException("Missing or inactive dependency: {$d}");
            }
        }
    }

    protected function runMigrations(string $slug): void
    {
        $p = config('lindu.modules_path').'/'.$slug.'/database/migrations';
        if (is_dir($p)) {
            Artisan::call('migrate', ['--path' => ltrim(str_replace(base_path(), '', $p), '/'), '--force' => true]);
        }
    }
}
