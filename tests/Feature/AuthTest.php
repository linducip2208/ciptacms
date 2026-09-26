<?php
namespace Tests\Feature;
use Tests\TestCase; use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User; use Illuminate\Support\Facades\Hash;
class AuthTest extends TestCase {
    use RefreshDatabase;
    public function test_login_page(): void { $this->get('/login')->assertOk(); }
    public function test_register_and_login(): void {
        $this->post('/register',['name'=>'T','email'=>'t@t.local','password'=>'password123','password_confirmation'=>'password123'])->assertRedirect('/admin');
        $u = User::where('email','t@t.local')->first(); $this->assertNotNull($u);
    }
    public function test_failed_login_tracked(): void {
        User::create(['name'=>'A','email'=>'a@a.local','password'=>Hash::make('secret123'),'status'=>'active','is_active'=>true]);
        $this->post('/login',['email'=>'a@a.local','password'=>'wrongpass']);
        $this->assertDatabaseHas('login_histories',['status'=>'failed']);
    }
}
