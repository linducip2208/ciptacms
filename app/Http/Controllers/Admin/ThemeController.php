<?php
namespace App\Http\Controllers\Admin;
use App\Core\Services\ThemeManager; use App\Models\Theme; use Illuminate\Http\Request;
class ThemeController extends AdminController {
    public function index(ThemeManager $m){ $m->syncRegistry(); return view('admin.themes.index',['themes'=>Theme::orderBy('name')->get()]); }
    public function activate(string $slug, ThemeManager $m){ $m->activate($slug); return back()->with('ok','Theme activated'); }
    public function settings(Request $r){ foreach((array)$r->get('theme',[]) as $k=>$v) app(\App\Core\Services\SettingService::class)->set('theme.'.$k,$v,'text','branding'); return back()->with('ok','Theme settings saved'); }
}
