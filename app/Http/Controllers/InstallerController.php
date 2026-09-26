<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Support\Facades\{Artisan,Hash,DB};
use App\Models\User;
class InstallerController extends Controller {
    public function index(){ if(file_exists(config('lindu.installer_lock'))) return redirect('/login'); return view('installer.welcome',['checks'=>app(\App\Core\Services\HealthService::class)->checks()]); }
    public function run(Request $r){
        $d=$r->validate(['app_name'=>'required','db_host'=>'nullable','db_name'=>'nullable','db_user'=>'nullable','db_pass'=>'nullable','admin_name'=>'required','admin_email'=>'required|email','admin_password'=>'required|min:8']);
        try{
            Artisan::call('migrate',['--force'=>true]);
            Artisan::call('db:seed',['--force'=>true]);
            if(!User::where('email',$d['admin_email'])->exists()){ $u=User::create(['name'=>$d['admin_name'],'email'=>$d['admin_email'],'password'=>Hash::make($d['admin_password']),'status'=>'active','is_active'=>true]); try{ $role=\App\Models\Role::where('slug','super-admin')->first(); if($role) $u->roles()->attach($role);}catch(\Throwable $e){} }
            app(\App\Core\Services\SettingService::class)->set('general.site_name',$d['app_name'],'text','general');
            file_put_contents(config('lindu.installer_lock'), now()->toDateTimeString());
            return redirect('/login')->with('ok','Installed. Please login.');
        }catch(\Throwable $e){ return back()->withErrors(['msg'=>$e->getMessage()])->withInput(); }
    }
}
