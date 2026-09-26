<?php
namespace App\Http\Controllers\Admin;
class QueueController extends AdminController {
    public function index(){
        try { $pending=\Illuminate\Support\Facades\DB::table('jobs')->count(); $failed=\Illuminate\Support\Facades\DB::table('failed_jobs')->count(); }
        catch(\Throwable $e){ $pending=0; $failed=0; }
        try { $recent=\Illuminate\Support\Facades\DB::table('jobs')->orderByDesc('id')->limit(20)->get(); } catch(\Throwable $e){ $recent=collect(); }
        try { $fails=\Illuminate\Support\Facades\DB::table('failed_jobs')->orderByDesc('id')->limit(20)->get(); } catch(\Throwable $e){ $fails=collect(); }
        $horizon=class_exists(\Laravel\Horizon\Horizon::class);
        return view('admin.system.queue',compact('pending','failed','recent','fails','horizon'));
    }
    public function retry(string $id){
        try{ \Illuminate\Support\Facades\Artisan::call('queue:retry',['id'=>$id]); }catch(\Throwable $e){}
        return back()->with('ok','Retry dispatched');
    }
    public function flush(){
        try{ \Illuminate\Support\Facades\Artisan::call('queue:flush'); }catch(\Throwable $e){}
        return back()->with('ok','Failed jobs cleared');
    }
}
