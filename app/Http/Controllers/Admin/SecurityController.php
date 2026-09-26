<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request;
use App\Core\Services\TwoFactorService;
use Illuminate\Support\Facades\Hash;
class SecurityController extends AdminController {
    public function twoFactor(Request $r, TwoFactorService $tfa){
        $u=$r->user();
        if(!$u->two_factor_secret){ $secret=$tfa->generateSecret(); $u->update(['two_factor_secret'=>$secret]); $u=$u->fresh(); }
        return view('admin.security.2fa',['user'=>$u,'otpauth'=>$tfa->otpauthUrl($u->email,$u->two_factor_secret)]);
    }
    public function twoFactorEnable(Request $r, TwoFactorService $tfa){
        $r->validate(['code'=>'required']); $u=$r->user();
        if(!$tfa->verify($u->two_factor_secret,$r->code)) return back()->withErrors(['code'=>'Kode salah']);
        $u->update(['two_factor_enabled'=>true,'two_factor_backup_codes'=>$tfa->backupCodes()]);
        try{ app(\App\Core\Services\AuditService::class)->log('2fa.enable',$u);}catch(\Throwable $e){}
        return back()->with('ok','2FA aktif. Simpan backup codes.');
    }
    public function twoFactorDisable(Request $r){
        $r->user()->update(['two_factor_enabled'=>false,'two_factor_secret'=>null,'two_factor_backup_codes'=>null]);
        return back()->with('ok','2FA dimatikan');
    }
    public function sessions(Request $r){
        $rows=\Illuminate\Support\Facades\DB::table('sessions')->where('user_id',$r->user()->id)->orderByDesc('last_activity')->get();
        $hist=\App\Models\LoginHistory::where('user_id',$r->user()->id)->latest()->limit(20)->get();
        return view('admin.security.sessions',compact('rows','hist'));
    }
    public function revokeSession(Request $r, string $id){
        \Illuminate\Support\Facades\DB::table('sessions')->where('id',$id)->where('user_id',$r->user()->id)->delete();
        return back()->with('ok','Session revoked');
    }
    public function revokeOthers(Request $r){
        \Illuminate\Support\Facades\DB::table('sessions')->where('user_id',$r->user()->id)->where('id','!=',$r->session()->getId())->delete();
        return back()->with('ok','Other devices logged out');
    }
}
