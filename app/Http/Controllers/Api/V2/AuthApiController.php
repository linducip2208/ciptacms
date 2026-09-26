<?php
namespace App\Http\Controllers\Api\V2;
use Illuminate\Http\Request; use Illuminate\Support\Facades\Hash; use App\Models\User;
class AuthApiController extends ApiController {
    public function login(Request $r){ $r->validate(['email'=>'required|email','password'=>'required']); $u=User::where('email',$r->email)->first(); if(!$u||!Hash::check($r->password,$u->password)) return $this->error('Invalid credentials',401); if(!$u->isActive()) return $this->error('Account inactive',403); if($u->two_factor_enabled) return $this->error('2FA required — use web login or send X-2FA-Code',423); $t=$u->createToken('api-v2',['*'])->plainTextToken; return $this->data(['token'=>$t,'token_type'=>'Bearer','user'=>$this->sparse($u,$r->get('fields'))]); }
    public function me(Request $r){ return $this->data($this->sparse($r->user(),$r->get('fields'))); }
    public function logout(Request $r){ $r->user()->currentAccessToken()?->delete(); return $this->data(['ok'=>true]); }
}
