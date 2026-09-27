<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Load a module's routes, views, translations and migrations.
     *
     * Only modules that are both installed and active get anything wired up.
     * The previous guard skipped a module only when a database row existed
     * and was inactive, so on a fresh install — where the modules table is
     * empty — every folder on disk registered its routes. A module an
     * operator never activated was therefore reachable.
     */
    public function boot(): void
    {
        $base = config('lindu.modules_path', base_path('modules'));
        if (! is_dir($base)) {
            return;
        }

        foreach (File::directories($base) as $dir) {
            $slug = basename($dir);

            if (! $this->isActive($slug)) {
                continue;
            }

            $routes = $dir.'/routes.php';
            if (file_exists($routes)) {
                Route::middleware('web')->group($routes);
            }

            $api = $dir.'/routes-api.php';
            if (file_exists($api)) {
                Route::prefix('api/v1')->middleware('api')->group($api);
            }

            $views = $dir.'/views';
            if (is_dir($views)) {
                $this->loadViewsFrom($views, 'mod-'.$slug);
            }

            $lang = $dir.'/lang';
            if (is_dir($lang)) {
                $this->loadTranslationsFrom($lang, 'mod-'.$slug);
            }

            $migrations = $dir.'/database/migrations';
            if (is_dir($migrations)) {
                $this->loadMigrationsFrom($migrations);
            }
        }
    }

    /**
     * Fail closed: a module with no database row has not been installed, so
     * nothing it ships may be reachable. If the table is missing entirely we
     * let nothing load rather than guessing.
     */
    protected function isActive(string $slug): bool
    {
        try {
            return (bool) \App\Models\Module::query()
                ->where('slug', $slug)
                ->where('is_installed', true)
                ->where('is_active', true)
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
