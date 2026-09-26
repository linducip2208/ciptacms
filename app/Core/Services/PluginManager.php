<?php
namespace App\Core\Services;
use App\Models\Plugin;
use Illuminate\Support\Facades\File;
class PluginManager {
    public function discover(): array {
        $base = config('lindu.plugins_path'); $found=[];
        if (!is_dir($base)) return $found;
        foreach (File::directories($base) as $dir) {
            $meta=$dir.'/plugin.json'; if(!File::exists($meta)) continue;
            $j=json_decode(File::get($meta),true); if(!$j||empty($j['slug'])) continue;
            $found[]=array_merge($j,['path'=>$dir]);
        }
        return $found;
    }
    public function syncRegistry(): void {
        foreach ($this->discover() as $p) {
            Plugin::updateOrCreate(['slug'=>$p['slug']],['name'=>$p['name']??$p['slug'],'version'=>$p['version']??'1.0.0','author'=>$p['author']??null,'description'=>$p['description']??null,'meta'=>$p,'is_installed'=>true]);
        }
    }
    public function activate(string $slug): void { Plugin::where('slug',$slug)->update(['is_active'=>true,'is_installed'=>true]); }
    public function deactivate(string $slug): void { Plugin::where('slug',$slug)->update(['is_active'=>false]); }
    public function uninstall(string $slug): void { Plugin::where('slug',$slug)->update(['is_active'=>false,'is_installed'=>false]); }
    public static function hooks(string $hook, array $payload=[]): array {
        $out=$payload;
        foreach (Plugin::where('is_active',true)->get() as $p) {
            $hooks=$p->meta['hooks']??[];
            if (in_array($hook,(array)$hooks)) $out['plugins'][]=$p->slug;
        }
        return $out;
    }
    public static function filters(string $filter, $value, array $ctx=[]) {
        try {
            foreach (Plugin::where('is_active',true)->get() as $p) {
                $f=$p->meta['filters']??[];
                if (in_array($filter,(array)$f)) { /* plugins may observe; core keeps value stable */ }
            }
        } catch (\Throwable $e) {}
        return $value;
    }
}
