<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
class DoctorCommand extends Command {
    protected $signature='lindu:doctor';
    protected $description='Cek kesehatan Lindu CMS (PHP, ext, writable, DB, maintenance, APP_KEY, disk)';
    public function handle(){
        $fail=0;
        $this->info('== Lindu Doctor ==');
        $this->check(version_compare(PHP_VERSION,'8.3.0','>='),"PHP >= 8.3 (kini ".PHP_VERSION.')',$fail);
        foreach(['mbstring','openssl','pdo','tokenizer','xml','ctype','json','bcmath','fileinfo','gd'] as $e)
            $this->check(extension_loaded($e),"ext {$e}",$fail, $e==='gd');
        foreach([storage_path(),storage_path('app'),storage_path('logs'),base_path('bootstrap/cache')] as $p)
            $this->check(is_writable($p),"writable {$p}",$fail);
        try{ DB::select('select 1'); $this->check(true,'DB '.config('database.default').' OK',$fail); }
        catch(\Throwable $e){ $this->check(false,'DB: '.$e->getMessage(),$fail); }
        $this->check(!file_exists(storage_path('framework/maintenance.php')),'maintenance OFF',$fail);
        $this->check((bool)config('app.key'),'APP_KEY set',$fail);
        try{ $free=disk_free_space(base_path()); $this->check($free>100*1024*1024,'disk free '.round($free/1024/1024).'MB',$fail); }
        catch(\Throwable $e){ $this->line('disk: unknown'); }
        $this->check(file_exists(public_path('storage'))||true,'storage link '.(file_exists(public_path('storage'))?'OK':'BELUM (jalankan storage:link)'),$fail,true);
        $this->info($fail ? "DITEMUKAN {$fail} MASALAH" : 'SEMUA OK');
        return $fail?1:0;
    }
    protected function check(bool $ok, string $msg, int &$fail, bool $warn=false): void {
        if($ok) $this->info("  [OK] {$msg}");
        elseif($warn){ $this->warn("  [WARN] {$msg}"); }
        else{ $this->error("  [FAIL] {$msg}"); $fail++; }
    }
}
