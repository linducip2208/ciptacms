<?php
namespace App\Core\Services;
use App\Models\UpdateLog;
class UpdateService {
    public function check(string $type='core'): array {
        return ['type'=>$type,'current'=>config('lindu.version'),'latest'=>config('lindu.version'),'update_available'=>false,'checked_at'=>now()->toDateTimeString()];
    }
    public function apply(string $type, string $slug, string $toVersion): UpdateLog {
        $log = UpdateLog::create(['type'=>$type,'slug'=>$slug,'from_version'=>config('lindu.version'),'to_version'=>$toVersion,'status'=>'running','started_at'=>now()]);
        try {
            app(BackupService::class)->run('full');
            \Illuminate\Support\Facades\Artisan::call('migrate',['--force'=>true]);
            $log->update(['status'=>'completed','finished_at'=>now(),'log'=>'Migrations executed']);
        } catch(\Throwable $e){ $log->update(['status'=>'failed','finished_at'=>now(),'log'=>$e->getMessage()]); }
        return $log->fresh();
    }
}
