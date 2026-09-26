<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request;
use App\Core\Services\ModuleManager;
use App\Models\Module;
class ModuleController extends AdminController {
    public function index(ModuleManager $m){ $m->syncRegistry(); return view('admin.modules.index',['modules'=>Module::orderBy('name')->get()]); }
    public function action(Request $r, string $slug, string $action, ModuleManager $m){
        try{ $m->$action($slug); return back()->with('ok',ucfirst($action).'d: '.$slug);}catch(\Throwable $e){ return back()->withErrors(['msg'=>$e->getMessage()]); }
    }
}
