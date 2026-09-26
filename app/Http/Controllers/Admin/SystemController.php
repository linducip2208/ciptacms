<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request;
class SystemController extends AdminController {
    public function health(){ return view('admin.system.health',['checks'=>app(\App\Core\Services\HealthService::class)->checks(),'info'=>['php'=>PHP_VERSION,'laravel'=>app()->version(),'lindu'=>config('lindu.version')]]); }
    public function info(){ return view('admin.system.info',['info'=>['php'=>PHP_VERSION,'laravel'=>app()->version(),'db'=>config('database.default'),'queue'=>config('queue.default'),'cache'=>config('cache.default')]]); }
    public function audits(Request $r){ $q=\App\Models\AuditLog::with('user')->latest(); if($s=$r->get('search')) $q->where('action','like',"%{$s}%"); return view('admin.system.audits',['rows'=>$q->paginate(25)]); }
    public function backups(){ return view('admin.system.backups',['rows'=>\App\Models\Backup::latest()->paginate(20)]); }
    public function runBackup(Request $r){ $b=app(\App\Core\Services\BackupService::class)->run($r->get('type','full')); return back()->with('ok','Backup '.$b->status); }
    public function updates(){ return view('admin.system.updates',['core'=>app(\App\Core\Services\UpdateService::class)->check('core'),'logs'=>\App\Models\UpdateLog::latest()->limit(20)->get()]); }
    public function search(Request $r){ $out=[]; if($q=$r->get('q')) $out=app(\App\Core\Services\SearchService::class)->search($q); if($r->expectsJson()) return $this->ok($out); return view('admin.system.search',['q'=>$r->get('q'),'results'=>$out]); }
}
