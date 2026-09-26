<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request; use App\Models\{User,Role}; use Illuminate\Support\Facades\Hash;
class UserController extends AdminController {
    public function index(Request $r){ $q=User::with('roles')->latest(); if($s=$r->get('search')) $q->where(fn($w)=>$w->where('name','like',"%{$s}%")->orWhere('email','like',"%{$s}%")); return view('admin.users.index',['users'=>$q->paginate(20),'roles'=>Role::all()]); }
    public function store(Request $r){ $d=$r->validate(['name'=>'required','email'=>'required|email|unique:users,email','password'=>'required|min:8']); \App\Core\Services\Quota::guard('users',1); $u=User::create(['name'=>$d['name'],'email'=>$d['email'],'password'=>Hash::make($d['password']),'status'=>'active','is_active'=>true]); \App\Core\Services\Quota::consume('users',1); $u->roles()->sync($r->get('roles',[])); return back()->with('ok','User created'); }
    public function update(Request $r, User $user){ $user->update($r->only(['name','phone','status','locale','timezone'])+['is_active'=>$r->boolean('is_active')]); if($r->filled('password')) $user->update(['password'=>Hash::make($r->password)]); $user->roles()->sync($r->get('roles',[])); return back()->with('ok','User updated'); }
    public function destroy(User $user){ if($user->id===auth()->id()) return back()->withErrors(['msg'=>'Cannot delete self']); $user->delete(); return back()->with('ok','Deleted'); }
    public function toggle(User $user){ $user->update(['is_active'=>!$user->is_active]); return back()->with('ok','Toggled'); }
}
