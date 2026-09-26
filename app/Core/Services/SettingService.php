<?php
namespace App\Core\Services;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
class SettingService {
    public function get(string $key, $default = null) {
        $row = $this->all()->get($key);
        if (!$row) return $default;
        $v = $row['value'];
        if (($row['type'] ?? '') === 'secret' && $v) { try { $v = Crypt::decryptString($v); } catch (\Throwable $e) { return $default; } }
        if (in_array($row['type'] ?? '', ['json','file','image'])) { $d = json_decode((string)$v, true); return $d ?? $v ?? $default; }
        if (($row['type'] ?? '') === 'boolean') return filter_var($v, FILTER_VALIDATE_BOOLEAN);
        if (($row['type'] ?? '') === 'number') return is_numeric($v) ? $v + 0 : $default;
        return $v ?? $default;
    }
    public function all() {
        return Cache::remember('lindu.settings.all', 300, function () {
            try { return Setting::all()->keyBy('key')->map(fn($s)=>['value'=>$s->value,'type'=>$s->type,'group'=>$s->group]); }
            catch (\Throwable $e) { return collect(); }
        });
    }
    public function set(string $key, $value, string $type='text', string $group='general'): void {
        if ($type==='secret' && $value) $value = Crypt::encryptString((string)$value);
        elseif (is_array($value)) { $value = json_encode($value); if($type==='text') $type='json'; }
        elseif (is_bool($value)) { $value = $value?'1':'0'; if($type==='text') $type='boolean'; }
        Setting::updateOrCreate(['key'=>$key], ['value'=> (string)$value,'type'=>$type,'group'=>$group]);
        Cache::forget('lindu.settings.all');
    }
    public function forgetCache(): void { Cache::forget('lindu.settings.all'); }
}
