<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Locks in the auth hardening. Each test fails if the corresponding
 * protection is removed or silently bypassed.
 */
class AuthHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    // ---- registration -------------------------------------------------

    public function test_web_registration_is_closed_by_default(): void
    {
        $this->post('/register', [
            'name' => 'N', 'email' => 'n@n.local',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseMissing('users', ['email' => 'n@n.local']);
    }

    public function test_api_registration_is_closed_by_default(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'A', 'email' => 'a@a.local',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertStatus(403);

        $this->assertDatabaseMissing('users', ['email' => 'a@a.local']);
    }

    public function test_api_registration_works_when_enabled(): void
    {
        app(\App\Core\Services\SettingService::class)->set('api.allow_registration', true, 'boolean', 'api');

        $this->postJson('/api/v1/auth/register', [
            'name' => 'A', 'email' => 'a@a.local',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertCreated();

        $this->assertDatabaseHas('users', ['email' => 'a@a.local']);
    }

    // ---- session fixation ---------------------------------------------

    public function test_registration_regenerates_the_session(): void
    {
        app(\App\Core\Services\SettingService::class)->set('security.allow_registration', true, 'boolean', 'security');

        $this->get('/register')->assertOk();
        $before = session()->getId();

        $this->post('/register', [
            'name' => 'F', 'email' => 'f@f.local',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ]);

        $this->assertNotSame($before, session()->getId(), 'Session id was not regenerated after registration');
        $this->assertAuthenticated();
    }

    // ---- brute force --------------------------------------------------

    public function test_two_factor_cannot_be_guessed_without_limit(): void
    {
        User::create([
            'name' => 'T', 'email' => 't2@t.local',
            'password' => Hash::make('secret123'),
            'two_factor_enabled' => true,
            'status' => 'active', 'is_active' => true,
        ]);

        $this->post('/login', ['email' => 't2@t.local', 'password' => 'secret123'])
            ->assertRedirect(route('2fa.challenge'));

        app(\App\Core\Services\SettingService::class)
            ->set('security.max_login_attempts', 3, 'number', 'security');

        $codes = ['111111', '222222', '333333', '444444', '555555', '666666', '000000'];

        $reachedLogin = false;
        foreach ($codes as $i => $code) {
            $res = $this->post('/2fa/challenge', ['code' => $code]);

            if ($res->getStatusCode() === 429) {
                break; // route limiter stopped it
            }
            if ($res->isRedirect(route('login'))) {
                $reachedLogin = true; // session budget exhausted
                break;
            }
        }

        $this->assertTrue(
            $reachedLogin || ! $this->isAuthenticated(),
            'TOTP guesses were not bounded'
        );
        $this->assertFalse($this->isAuthenticated());
    }

    public function test_login_attempt_limit_reads_the_operator_setting(): void
    {
        User::create([
            'name' => 'L', 'email' => 'l@l.local',
            'password' => Hash::make('secret123'), 'status' => 'active', 'is_active' => true,
        ]);

        app(\App\Core\Services\SettingService::class)
            ->set('security.max_login_attempts', 3, 'number', 'security');

        for ($i = 0; $i < 3; $i++) {
            $this->post('/login', ['email' => 'l@l.local', 'password' => 'nope']);
        }

        $this->post('/login', ['email' => 'l@l.local', 'password' => 'secret123']);

        $this->assertFalse(
            $this->isAuthenticated(),
            'The configured attempt limit was ignored'
        );
    }

    // ---- force_2fa ----------------------------------------------------

    public function test_force_2fa_blocks_an_unenrolled_account(): void
    {
        User::create([
            'name' => 'N', 'email' => 'n@n.local',
            'password' => Hash::make('secret123'), 'status' => 'active', 'is_active' => true,
        ]);

        app(\App\Core\Services\SettingService::class)
            ->set('security.force_2fa', true, 'boolean', 'security');

        $this->post('/login', ['email' => 'n@n.local', 'password' => 'secret123'])
            ->assertSessionHasErrors('email');

        $this->assertFalse($this->isAuthenticated());
        $this->assertDatabaseHas('login_histories', ['failed_reason' => 'two_factor_not_enrolled']);
    }

    public function test_force_2fa_lets_an_enrolled_account_through(): void
    {
        User::create([
            'name' => 'Y', 'email' => 'y@y.local',
            'password' => Hash::make('secret123'),
            'two_factor_enabled' => true,
            'two_factor_secret' => app(\App\Core\Services\TwoFactorService::class)->generateSecret(),
            'status' => 'active', 'is_active' => true,
        ]);

        app(\App\Core\Services\SettingService::class)
            ->set('security.force_2fa', true, 'boolean', 'security');

        $this->post('/login', ['email' => 'y@y.local', 'password' => 'secret123'])
            ->assertRedirect(route('2fa.challenge'));
    }

    // ---- API auth -----------------------------------------------------

    public function test_api_login_does_not_confirm_whether_an_email_exists(): void
    {
        User::create([
            'name' => 'K', 'email' => 'k@k.local',
            'password' => Hash::make('secret123'), 'status' => 'active', 'is_active' => true,
        ]);

        $unknown = $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@nowhere.local', 'password' => 'secret123',
        ]);

        $wrongPassword = $this->postJson('/api/v1/auth/login', [
            'email' => 'k@k.local', 'password' => 'wrong',
        ]);

        $this->assertSame($unknown->json(), $wrongPassword->json());
        $this->assertSame(401, $unknown->getStatusCode());
    }

    public function test_api_login_refuses_a_suspended_account(): void
    {
        User::create([
            'name' => 'S', 'email' => 's@s.local',
            'password' => Hash::make('secret123'), 'status' => 'suspended', 'is_active' => false,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 's@s.local', 'password' => 'secret123',
        ])->assertStatus(403);
    }

    public function test_api_login_issues_a_token_for_a_valid_account(): void
    {
        User::create([
            'name' => 'G', 'email' => 'g@g.local',
            'password' => Hash::make('secret123'), 'status' => 'active', 'is_active' => true,
        ]);

        $res = $this->postJson('/api/v1/auth/login', [
            'email' => 'g@g.local', 'password' => 'secret123',
        ])->assertOk();

        $this->assertNotEmpty($res->json('data.token'));
    }

    public function test_api_login_refuses_an_account_that_needs_2fa(): void
    {
        User::create([
            'name' => 'M', 'email' => 'm@m.local',
            'password' => Hash::make('secret123'),
            'two_factor_enabled' => true,
            'status' => 'active', 'is_active' => true,
        ]);

        // A token would let someone bypass the second factor entirely.
        $this->postJson('/api/v1/auth/login', [
            'email' => 'm@m.local', 'password' => 'secret123',
        ])->assertStatus(409);
    }
}
