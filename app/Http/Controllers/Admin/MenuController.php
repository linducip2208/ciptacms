<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request;
use App\Models\MenuItem;
class MenuController extends AdminController {
    public function index(Request $r){ $location=$r->get('location','admin'); $items=MenuItem::where('location',$location)->orderBy('sort_order')->get(); return view('admin.menus.index',compact('items','location')); }
    public function store(Request $r){ $d=$r->validate(['title'=>'required','location'=>'required','parent_id'=>'nullable|exists:menu_items,id','url'=>'nullable','route'=>'nullable','icon'=>'nullable','permission'=>'nullable','sort_order'=>'nullable|integer']); $d['is_visible']=$r->boolean('is_visible',true); $item=MenuItem::create($d); \App\Core\Services\MenuService::forget(); return back()->with('ok','Menu created'); }
    public function update(Request $r, MenuItem $menu){ $menu->update($r->only(['title','parent_id','url','route','icon','permission','sort_order','target','badge','badge_color','module'])+['is_visible'=>$r->boolean('is_visible')]); \App\Core\Services\MenuService::forget(); return back()->with('ok','Menu updated'); }
    public function destroy(MenuItem $menu){ $menu->delete(); \App\Core\Services\MenuService::forget(); return back()->with('ok','Menu deleted'); }
    public function reorder(Request $r){ foreach((array)$r->get('order',[]) as $i=>$id){ MenuItem::where('id',$id)->update(['sort_order'=>$i,'parent_id'=>$r->get('parent_id')]); } \App\Core\Services\MenuService::forget(); return response()->json(['ok'=>true]); }
}
