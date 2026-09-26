<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request; use Illuminate\Support\Facades\{Auth,Hash,Password};
use App\Models\{User,LoginHistory};
use App\Core\Services\WorkflowEngine;
class AuthController extends Controller {
    public function showLogin(){ return view('auth.login'); }
    public function login(Request $r){
        $r->validate(['email'=>'required|email','password'=>'required']);
        $key = strtolower($r->email).'|'.$r->ip();
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 5)) return back()->withErrors(['email'=>'Too many attempts. Try later.']);
        $user = User::where('email',$r->email)->first();
        if (!$user || !Hash::check($r->password,$user->password)) {
            \Illuminate\Support\Facades\RateLimiter::hit($key, 900);
            if($user) LoginHistory::create(['user_id'=>$user->id,'ip'=>$r->ip(),'user_agent'=>$r->userAgent(),'status'=>'failed','failed_reason'=>'bad_credentials','logged_in_at'=>now()]);
            return back()->withErrors(['email'=>'Invalid credentials']);
        }
        if (!$user->isActive()) return back()->withErrors(['email'=>'Account '.$user->status]);
        // 2FA challenge
        if ($user->two_factor_enabled) {
            $r->session()->put('2fa:user_id',$user->id);
            $r->session()->put('2fa:remember',$r->boolean('remember'));
            return redirect()->route('2fa.challenge');
        }
        return $this->finishLogin($r,$user);
    }
    protected function finishLogin(Request $r, User $user){
        \Illuminate\Support\Facades\RateLimiter::clear(strtolower($user->email).'|'.$r->ip());
        Auth::login($user, $r->session()->pull('2fa:remember', $r->boolean('remember')));
        $r->session()->regenerate();
        try{
            \Illuminate\Support\Facades\DB::table('sessions')->where('id',$r->session()->getId())->update(['device'=>substr((string)$r->userAgent(),0,190)]);
        }catch(\Throwable $e){}
        $user->update(['last_login_at'=>now(),'last_login_ip'=>$r->ip()]);
        LoginHistory::create(['user_id'=>$user->id,'ip'=>$r->ip(),'user_agent'=>$r->userAgent(),'status'=>'success','logged_in_at'=>now()]);
        try{ app(\App\Core\Services\AuditService::class)->log('login',$user);}catch(\Throwable $e){}
        return redirect()->intended('/admin');
    }
    public function showChallenge(){ return session('2fa:user_id') ? view('auth.2fa') : redirect()->route('login'); }
    public function verifyChallenge(Request $r){
        $r->validate(['code'=>'required']);
        $uid=$r->session()->get('2fa:user_id'); if(!$uid) return redirect()->route('login');
        $user=User::findOrFail($uid);
        $tfa=app(\App\Core\Services\TwoFactorService::class);
        $ok=$tfa->verify($user->two_factor_secret??'',$r->code);
        if(!$ok && in_array(strtoupper(trim($r->code)), (array)$user->two_factor_backup_codes)){
            $codes=array_values(array_diff((array)$user->two_factor_backup_codes,[strtoupper(trim($r->code))])); $user->update(['two_factor_backup_codes'=>$codes]); $ok=true;
        }
        if(!$ok){ LoginHistory::create(['user_id'=>$user->id,'ip'=>$r->ip(),'user_agent'=>$r->userAgent(),'status'=>'failed','failed_reason'=>'bad_2fa','logged_in_at'=>now()]); return back()->withErrors(['code'=>'Invalid 2FA code']); }
        $r->session()->forget('2fa:user_id');
        return $this->finishLogin($r,$user);
    }
    public function showRegister(){ return view('auth.register'); }
    public function register(Request $r){
        $d=$r->validate(['name'=>'required|max:100','email'=>'required|email|unique:users,email','password'=>'required|min:8|confirmed']);
        $u=User::create(['name'=>$d['name'],'email'=>$d['email'],'password'=>Hash::make($d['password']),'status'=>'active','is_active'=>true]);
        try{ $role=\App\Models\Role::where('slug','member')->first(); if($role) $u->roles()->attach($role);}catch(\Throwable $e){}
        try{ app(WorkflowEngine::class)->trigger('user.registered',['user_id'=>$u->id,'email'=>$u->email]); }catch(\Throwable $e){}
        Auth::login($u); return redirect('/admin');
    }
    public function logout(Request $r){ try{ app(\App\Core\Services\AuditService::class)->log('logout', $r->user() ?? 'user'); }catch(\Throwable $e){} Auth::logout(); $r->session()->invalidate(); $r->session()->regenerateToken(); return redirect('/login'); }
    public function showForgot(){ return view('auth.forgot'); }
    public function forgot(Request $r){ $r->validate(['email'=>'required|email']); Password::sendResetLink($r->only('email')); return back()->with('ok','Reset link sent if email exists'); }
    // Social login-ready: generic OAuth entry point (plug Socialite driver per provider)
    public function socialRedirect(string $provider){
        $allowed=['google','github'];
        abort_unless(in_array($provider,$allowed),404);
        try { return \Laravel\Socialite\Facades\Socialite::driver($provider)->redirect(); }
        catch(\Throwable $e){ return redirect('/login')->withErrors(['email'=>"OAuth [$provider] belum dikonfigurasi. Isi ".strtoupper($provider)."_CLIENT_ID/SECRET di .env"]); }
    }
    public function socialCallback(string $provider){
        try {
            $su=\Laravel\Socialite\Facades\Socialite::driver($provider)->user();
            $acc=\App\Models\SocialAccount::where('provider',$provider)->where('provider_id',$su->getId())->first();
            if($acc){ Auth::login($acc->user, true); return redirect('/admin'); }
            $user=User::where('email',$su->getEmail())->first();
            if(!$user){ $user=User::create(['name'=>$su->getName()?:$su->getNickname()?:'User','email'=>$su->getEmail(),'password'=>bcrypt(\Illuminate\Support\Str::random(24)),'status'=>'active','is_active'=>true,'email_verified_at'=>now()]); }
            $user->socialAccounts()->create(['provider'=>$provider,'provider_id'=>$su->getId(),'email'=>$su->getEmail(),'meta'=>['avatar'=>$su->getAvatar()]]);
            Auth::login($user, true);
            return redirect('/admin');
        } catch(\Throwable $e){ return redirect('/login')->withErrors(['email'=>'OAuth gagal: '.$e->getMessage()]); }
    }
}
