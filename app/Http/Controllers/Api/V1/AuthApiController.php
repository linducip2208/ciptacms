<?php
namespace App\Http\Controllers\Api\V1;
use Illuminate\Http\Request; use Illuminate\Support\Facades\Hash; use App\Models\User;
class AuthApiController extends ApiController {
    public function login(Request $r){ $r->validate(['email'=>'required|email','password'=>'required']); $u=User::where('email',$r->email)->first(); if(!$u||!Hash::check($r->password,$u->password)) return $this->error('Invalid credentials',401); if(!$u->isActive()) return $this->error('Account inactive',403); $t=$u->createToken('api',['*'])->plainTextToken; return $this->data(['token'=>$t,'user'=>$u]); }
    public function me(Request $r){ return $this->data($r->user()); }
    public function logout(Request $r){ $r->user()->currentAccessToken()?->delete(); return $this->data(['ok'=>true]); }
    public function register(Request $r){ $d=$r->validate(['name'=>'required','email'=>'required|email|unique:users,email','password'=>'required|min:8']); $u=User::create(['name'=>$d['name'],'email'=>$d['email'],'password'=>Hash::make($d['password']),'status'=>'active','is_active'=>true]); return $this->data($u); }
}
