<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request; use App\Models\{Tenant,Plan,Subscription}; use Illuminate\Support\Str;
class TenantController extends AdminController {
    public function index(){ return view('admin.tenants.index',['tenants'=>Tenant::with('plan')->paginate(20),'plans'=>Plan::all()]); }
    public function store(Request $r){ $d=$r->validate(['name'=>'required']); $id=(string)Str::uuid(); Tenant::create(['id'=>$id,'uuid'=>$id,'name'=>$d['name'],'slug'=>Str::slug($d['name']).'-'.substr($id,0,4),'status'=>'active']); return back()->with('ok','Tenant created'); }
    public function plans(){ return view('admin.tenants.plans',['plans'=>Plan::orderBy('sort_order')->get()]); }
    public function storePlan(Request $r){ Plan::create($r->validate(['name'=>'required','slug'=>'required|unique:plans,slug','price'=>'nullable|numeric'])+['is_active'=>true]); return back()->with('ok','Plan created'); }
    public function licenses(){ return view('admin.tenants.licenses',['rows'=>\App\Models\License::latest()->paginate(20)]); }
    public function issueLicense(Request $r){ app(\App\Core\Services\LicenseService::class)->issue($r->validate(['product'=>'required'])+$r->only(['customer','domain','features','max_activations','expires_at'])); return back()->with('ok','License issued'); }
}
