<?php
namespace App\Http\Controllers\Admin;
use App\Core\Services\PluginManager; use App\Models\Plugin;
class PluginController extends AdminController {
    public function index(PluginManager $m){ $m->syncRegistry(); return view('admin.plugins.index',['plugins'=>Plugin::orderBy('name')->get()]); }
    public function action(string $slug, string $action, PluginManager $m){ try{ $m->$action($slug); return back()->with('ok',ucfirst($action).'d'); }catch(\Throwable $e){ return back()->withErrors(['msg'=>$e->getMessage()]); } }
}
