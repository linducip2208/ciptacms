<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
class ModuleServiceProvider extends ServiceProvider {
    public function boot(): void {
        $base = config('lindu.modules_path', base_path('modules'));
        if (!is_dir($base)) return;
        foreach (File::directories($base) as $dir) {
            $slug = basename($dir);
            try {
                $rec = \App\Models\Module::where('slug',$slug)->first();
                if ($rec && !$rec->is_active) continue;
            } catch(\Throwable $e){}
            $routes = $dir.'/routes.php';
            if (file_exists($routes)) { Route::middleware('web')->group($routes); }
            $api = $dir.'/routes-api.php';
            if (file_exists($api)) { Route::prefix('api/v1')->middleware('api')->group($api); }
            $views = $dir.'/views';
            if (is_dir($views)) $this->loadViewsFrom($views, 'mod-'.$slug);
            $lang = $dir.'/lang';
            if (is_dir($lang)) $this->loadTranslationsFrom($lang, 'mod-'.$slug);
            $mig = $dir.'/database/migrations';
            if (is_dir($mig)) $this->loadMigrationsFrom($mig);
        }
    }
}
