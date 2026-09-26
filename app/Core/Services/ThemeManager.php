<?php
namespace App\Core\Services;
use App\Models\Theme;
use Illuminate\Support\Facades\File;
class ThemeManager {
    public function discover(): array {
        $base=config('lindu.themes_path'); $found=[];
        if(!is_dir($base)) return $found;
        foreach(File::directories($base) as $dir){
            $meta=$dir.'/theme.json'; if(!File::exists($meta)) continue;
            $j=json_decode(File::get($meta),true); if(!$j||empty($j['slug'])) continue;
            $found[]=array_merge($j,['path'=>$dir]);
        }
        return $found;
    }
    public function syncRegistry(): void {
        foreach($this->discover() as $t){
            Theme::updateOrCreate(['slug'=>$t['slug']],['name'=>$t['name']??$t['slug'],'version'=>$t['version']??'1.0.0','author'=>$t['author']??null,'description'=>$t['description']??null,'meta'=>$t]);
        }
    }
    public function activate(string $slug): void {
        Theme::query()->update(['is_active'=>false]);
        Theme::where('slug',$slug)->update(['is_active'=>true]);
        app(SettingService::class)->set('theme.active',$slug,'text','branding');
    }
    public function active(): ?Theme { try { return Theme::where('is_active',true)->first(); } catch(\Throwable $e){ return null; } }
}
