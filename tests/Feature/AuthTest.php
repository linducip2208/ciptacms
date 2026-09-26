<?php
namespace Tests\Feature;
use Tests\TestCase; use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User; use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
class AuthTest extends TestCase {
    use RefreshDatabase;
    public function test_login_page(): void { $this->get('/login')->assertOk(); }

    public function test_registration_is_closed_by_default(): void {
        // A company site should not accept account creation from the open
        // internet unless an operator explicitly enables it.
        $this->post('/register',['name'=>'T','email'=>'t@t.local','password'=>'password123','password_confirmation'=>'password123'])
            ->assertRedirect(route('login'));
        $this->assertDatabaseMissing('users',['email'=>'t@t.local']);
    }

    public function test_register_and_login_when_enabled(): void {
        app(\App\Core\Services\SettingService::class)->set('security.allow_registration', true, 'boolean', 'security');

        $this->post('/register',['name'=>'T','email'=>'t@t.local','password'=>'password123','password_confirmation'=>'password123'])->assertRedirect('/admin');
        $u = User::where('email','t@t.local')->first();
        $this->assertNotNull($u);
        $this->assertAuthenticatedAs($u);
    }

    public function test_registration_regenerates_the_session_id(): void {
        // A pre-authentication session id surviving into the authenticated
        // session is a session-fixation hole.
        app(\App\Core\Services\SettingService::class)->set('security.allow_registration', true, 'boolean', 'security');

        $before = session()->getId();
        $this->post('/register',['name'=>'F','email'=>'f@f.local','password'=>'password123','password_confirmation'=>'password123']);

        $this->assertNotSame($before, session()->getId());
        $this->assertAuthenticated();
    }

    public function test_failed_login_tracked(): void {
        User::create(['name'=>'A','email'=>'a@a.local','password'=>Hash::make('secret123'),'status'=>'active','is_active'=>true]);
        $this->post('/login',['email'=>'a@a.local','password'=>'wrongpass']);
        $this->assertDatabaseHas('login_histories',['status'=>'failed']);
    }

    public function test_login_honours_the_configured_attempt_limit(): void {
        User::create(['name'=>'A','email'=>'a@a.local','password'=>Hash::make('secret123'),'status'=>'active','is_active'=>true]);

        // The Settings > Security tab exposes this number. Both the route
        // limiter and the controller limiter read it, so lowering it here has
        // to take effect.
        app(\App\Core\Services\SettingService::class)->set('security.max_login_attempts', 3, 'number', 'security');

        // Spend the budget with wrong passwords.
        for ($i = 0; $i < 3; $i++) {
            $this->post('/login',['email'=>'a@a.local','password'=>'wrong-password']);
        }

        // The correct password must no longer get in.
        $this->post('/login',['email'=>'a@a.local','password'=>'secret123']);

        $this->assertFalse(
            $this->isAuthenticated(),
            'A correct password still worked after the attempt budget was spent'
        );
    }

    public function test_two_factor_codes_cannot_be_brute_forced(): void {
        User::create(['name'=>'T','two_factor_enabled'=>true,'email'=>'t2@t.local','password'=>Hash::make('secret123'),'status'=>'active','is_active'=>true]);

        // Reach the challenge with the correct password.
        $this->post('/login',['email'=>'t2@t.local','password'=>'secret123'])->assertRedirect(route('2fa.challenge'));

        app(\App\Core\Services\SettingService::class)->set('security.max_login_attempts', 3, 'number', 'security');

        $blocked = false;
        for ($i = 0; $i < 6; $i++) {
            $res = $this->post('/2fa/challenge',['code'=>'000000']);
            if ($res->getStatusCode() === 429) {
                $blocked = true;
                break;
            }
            if ($res->isRedirect(route('login'))) {
                $blocked = true;
                break;
            }
        }

        $this->assertTrue($blocked, 'TOTP codes could be cycled without limit');
        $this->assertGuest();
        $this->assertDatabaseHas('login_histories',['failed_reason'=>'bad_2fa']);
    }
}
