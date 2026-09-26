<?php
namespace App\Core\Services;
use App\Models\Module;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
class ModuleManager {
    public function discover(): array {
        $base = config('lindu.modules_path'); $found=[];
        if (!is_dir($base)) return $found;
        foreach (File::directories($base) as $dir) {
            $meta = $dir.'/module.json';
            if (!File::exists($meta)) continue;
            $j = json_decode(File::get($meta), true); if (!$j || empty($j['slug'])) continue;
            $found[] = array_merge($j, ['path'=>$dir]);
        }
        return $found;
    }
    public function syncRegistry(): void {
        foreach ($this->discover() as $m) {
            Module::updateOrCreate(['slug'=>$m['slug']], [
                'name'=>$m['name']??$m['slug'],'version'=>$m['version']??'1.0.0',
                'author'=>$m['author']??null,'description'=>$m['description']??null,
                'meta'=>$m,'is_installed'=>true,
            ]);
        }
    }
    public function install(string $slug): void {
        $mod = Module::where('slug',$slug)->firstOrFail();
        $this->checkDeps($mod);
        $mod->update(['is_installed'=>true]);
        $this->runMigrations($slug);
        app(AuditService::class)->log('install_module', $mod);
    }
    public function activate(string $slug): void {
        Module::where('slug',$slug)->update(['is_active'=>true]);
        MenuService::forget();
    }
    public function deactivate(string $slug): void {
        Module::where('slug',$slug)->update(['is_active'=>false]);
        MenuService::forget();
    }
    public function uninstall(string $slug): void {
        Module::where('slug',$slug)->update(['is_active'=>false,'is_installed'=>false]);
    }
    public function active(): \Illuminate\Support\Collection { return Module::where('is_active',true)->get(); }
    public function isActive(string $slug): bool { try { return (bool)Module::where('slug',$slug)->where('is_active',true)->exists(); } catch (\Throwable $e){ return true; } }
    protected function checkDeps($mod): void {
        $deps = $mod->meta['dependencies'] ?? $mod->meta['requires'] ?? [];
        foreach ((array)$deps as $d) { if (!Module::where('slug',$d)->where('is_active',true)->exists() && !Module::where('slug',$d)->exists()) throw new \RuntimeException("Missing dependency: {$d}"); }
    }
    protected function runMigrations(string $slug): void {
        $p = config('lindu.modules_path').'/'.$slug.'/database/migrations';
        if (is_dir($p)) Artisan::call('migrate', ['--path'=>ltrim(str_replace(base_path(),'', $p),'/'), '--force'=>true]);
    }
}
