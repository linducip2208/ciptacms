<?php
namespace App\Core\Services;
use App\Models\Backup;
use Illuminate\Support\Facades\Artisan;
class BackupService {
    public function run(string $type='full'): Backup {
        $b = Backup::create(['type'=>$type,'status'=>'running','disk'=>config('lindu.backup.disk','local'),'started_at'=>now()]);
        try {
            $name = 'backup-'.date('Ymd-His').'-'.$type.'.zip';
            $path = 'backups/'.$name;
            // Database dump (mysql or sqlite aware)
            $dump = $this->dumpDatabase();
            \Illuminate\Support\Facades\Storage::disk($b->disk)->put($path.'.sql', $dump);
            $b->update(['status'=>'completed','path'=>$path.'.sql','size'=>strlen($dump),'finished_at'=>now()]);
            // Prune old
            $keep=(int)config('lindu.backup.keep',7);
            foreach(Backup::orderByDesc('id')->skip($keep)->take(100)->get() as $old){ $old->delete(); }
        } catch(\Throwable $e){ $b->update(['status'=>'failed','log'=>$e->getMessage(),'finished_at'=>now()]); }
        return $b->fresh();
    }
    protected function dumpDatabase(): string {
        $out = "-- Lindu CMS backup ".now()."\n";
        try {
            foreach(\Illuminate\Support\Facades\DB::select("SHOW TABLES") as $row){
                $t = array_values((array)$row)[0];
                $out .= "\n-- TABLE {$t}\n";
                try { $c = \Illuminate\Support\Facades\DB::selectOne("SHOW CREATE TABLE `{$t}`"); $out .= array_values((array)$c)[1].";\n"; } catch(\Throwable $e){}
            }
        } catch(\Throwable $e){ $out .= "-- sqlite/other driver: schema dump skipped\n"; }
        return $out;
    }
}
