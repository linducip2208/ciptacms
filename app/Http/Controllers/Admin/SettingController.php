<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request;
use App\Core\Services\SettingService;
class SettingController extends AdminController {
    public function index(){ $groups = \App\Models\Setting::select('group')->distinct()->pluck('group'); $settings = \App\Models\Setting::orderBy('group')->orderBy('key')->get(); return view('admin.settings.index',compact('settings','groups')); }
    public function update(Request $r, SettingService $svc){
        foreach((array)$r->get('settings',[]) as $key=>$val){ $row=\App\Models\Setting::where('key',$key)->first(); $svc->set($key,$val,$row->type??'text',$row->group??'general'); }
        if($extra=$r->get('new_key')) $svc->set($extra,$r->get('new_value'),$r->get('new_type','text'),$r->get('new_group','general'));
        try{ app(\App\Core\Services\AuditService::class)->log('settings.update','settings'); }catch(\Throwable $e){}
        return back()->with('ok','Settings saved');
    }
}
