<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request; use App\Models\{Role,Permission,PermissionGroup};
class RoleController extends AdminController {
    public function index(){ return view('admin.roles.index',['roles'=>Role::withCount(['users','permissions'])->get(),'permissions'=>Permission::with('group')->get(),'groups'=>PermissionGroup::all()]); }
    public function store(Request $r){ $d=$r->validate(['name'=>'required','slug'=>'required|unique:roles,slug']); $role=Role::create($d); $role->permissions()->sync($r->get('permissions',[])); return back()->with('ok','Role created'); }
    public function update(Request $r, Role $role){ $role->update($r->only(['name','description'])); $role->permissions()->sync($r->get('permissions',[])); try{ app(\App\Core\Services\AuditService::class)->log('permission.update',$role);}catch(\Throwable $e){} return back()->with('ok','Role updated'); }
    public function destroy(Role $role){ if($role->is_system) return back()->withErrors(['msg'=>'System role']); $role->delete(); return back()->with('ok','Deleted'); }
}
