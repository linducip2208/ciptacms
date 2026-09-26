<?php
namespace App\Core\Services;
class HealthService {
    public function checks(): array {
        $out=[];
        $out['php']=['ok'=>version_compare(PHP_VERSION,'8.3.0','>='),'value'=>PHP_VERSION];
        foreach(['mbstring','openssl','pdo','tokenizer','xml','ctype','json','bcmath','fileinfo'] as $e) $out['ext_'.$e]=['ok'=>extension_loaded($e),'value'=>$e];
        foreach([storage_path(), storage_path('app'), public_path('storage')] as $p) $out['writable_'.md5($p)]=['ok'=>is_writable(dirname($p))||is_writable($p),'value'=>$p];
        try { \Illuminate\Support\Facades\DB::select('select 1'); $out['db']=['ok'=>true,'value'=>config('database.default')]; }
        catch(\Throwable $e){ $out['db']=['ok'=>false,'value'=>$e->getMessage()]; }
        try { $out['queue']=['ok'=>true,'value'=>config('queue.default')]; } catch(\Throwable $e){ $out['queue']=['ok'=>false,'value'=>$e->getMessage()]; }
        return $out;
    }
}
