<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request;
use App\Models\{User,Page,Post,Order,Product,Lead,Reservation,Course};
class DashboardController extends AdminController {
    public function index(Request $r){
        $stats=[];
        foreach(['users'=>User::class,'pages'=>Page::class,'posts'=>Post::class,'orders'=>Order::class,'products'=>Product::class,'leads'=>Lead::class] as $k=>$m){
            try{ $stats[$k]=$m::count(); }catch(\Throwable $e){ $stats[$k]=0; }
        }
        try{ $revenue = Order::where('payment_status','paid')->sum('total'); }catch(\Throwable $e){ $revenue=0; }
        $stats['revenue']=$revenue;
        $widgets = \App\Models\DashboardWidget::where(function($w)use($r){ $w->whereNull('user_id')->orWhere('user_id',$r->user()?->id); })->orderBy('sort_order')->get();
        $activity = \App\Models\AuditLog::latest()->limit(10)->get();
        return view('admin.dashboard',['stats'=>$stats,'widgets'=>$widgets,'activity'=>$activity]);
    }
}
