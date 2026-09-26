<?php
namespace App\Core\Services;
use App\Core\Support\SafeCache;
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
    /**
     * All settings, keyed by setting key.
     *
     * The cache holds a plain ARRAY, never a Collection. Round-tripping a
     * Collection through a cache store comes back as
     * __PHP_Incomplete_Class, and because setting() reads this from every
     * view the first call fataled with "tried to call a method on an
     * incomplete object". Arrays serialize without touching a class, so the
     * cache is safe on every read.
     */
    public function all() {
        $key = 'lindu.settings.all';

        try {
            $cached = Cache::get($key);
            if (is_array($cached)) {
                return collect($cached);
            }
        } catch (\Throwable $e) {
            // Genuinely corrupt bytes: report and rebuild.
            report($e);
        }

        SafeCache::discard($key);

        $fresh = $this->buildAll()->all();

        try {
            Cache::put($key, $fresh, 300);
        } catch (\Throwable $e) {
            report($e);
        }

        return collect($fresh);
    }

    protected function buildAll() {
        try {
            return Setting::all()->keyBy('key')->map(fn($s)=>['value'=>$s->value,'type'=>$s->type,'group'=>$s->group]);
        } catch (\Throwable $e) {
            report($e);

            return collect();
        }
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
