<?php
namespace Tests\Feature;
use Tests\TestCase; use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User; use Illuminate\Support\Facades\Hash;
use Database\Seeders\{RolesPermissionsSeeder,SettingSeeder};
class TwoFactorTest extends TestCase {
    use RefreshDatabase;
    public function test_2fa_challenge_flow(): void {
        $this->seed([RolesPermissionsSeeder::class, SettingSeeder::class]);
        $u=User::create(['name'=>'T','email'=>'t@t.local','password'=>Hash::make('password123'),'status'=>'active','is_active'=>true]);
        $tfa=app(\App\Core\Services\TwoFactorService::class);
        $u->update(['two_factor_secret'=>$tfa->generateSecret(),'two_factor_enabled'=>true]);
        $this->post('/login',['email'=>'t@t.local','password'=>'password123'])->assertRedirect(route('2fa.challenge'));
        $this->get('/2fa/challenge')->assertOk();
        $this->post('/2fa/challenge',['code'=>'000000'])->assertSessionHasErrors('code');
    }
    public function test_sessions_page_requires_auth(): void {
        $this->get('/admin/security/sessions')->assertRedirect('/login');
    }
}
