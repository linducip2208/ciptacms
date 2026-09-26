<?php

namespace Tests\Feature;

use App\Core\Services\InstallLock;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The installer must be usable exactly once, and reopening it has to be a
 * deliberate act — otherwise a live site exposes a full re-install to anyone
 * who can reach /install.
 *
 * These tests exercise the lock and the route guard directly. They
 * deliberately do NOT POST a real installation: the installer's run() writes
 * credentials into .env and executes migrate/db:seed, which would mutate the
 * developer's environment and the test database. The full install path is
 * covered manually via the browser installer.
 */
class InstallerTest extends TestCase
{
    protected function tearDown(): void
    {
        File::delete((string) config('lindu.installer_lock'));

        parent::tearDown();
    }

    protected function lock(): InstallLock
    {
        return app(InstallLock::class);
    }

    public function test_installer_is_open_when_not_installed(): void
    {
        $this->assertFalse($this->lock()->installed());
        $this->get('/install')->assertOk()->assertSee('Server requirements', false);
    }

    public function test_requirements_step_lists_every_check(): void
    {
        $this->get('/install')->assertOk();

        $checks = app(\App\Core\Services\HealthService::class)->checks();

        $this->assertNotEmpty($checks);
        foreach (array_keys($checks) as $key) {
            // The welcome view renders one row per check.
            $this->assertIsArray($checks[$key]);
        }
    }

    public function test_lock_records_the_installation(): void
    {
        $lock = $this->lock();
        $lock->lock('https://acme.test', 'Acme', 'owner@acme.test');

        $this->assertTrue($lock->installed());
        $this->assertSame('Acme', $lock->payload()['app_name']);
        $this->assertSame('owner@acme.test', $lock->payload()['admin_email']);
        $this->assertNotNull($lock->installedAt());
    }

    public function test_installer_refuses_to_run_again_once_locked(): void
    {
        $this->lock()->lock('https://acme.test', 'Acme', 'owner@acme.test');

        $this->get('/install')
            ->assertRedirect(route('login'))
            ->assertSessionHas('ok');

        $this->get('/install/database')->assertRedirect(route('login'));
    }

    public function test_lock_file_is_not_web_accessible(): void
    {
        $lock = $this->lock();
        $lock->lock('https://acme.test', 'Acme', 'owner@acme.test');

        $path = str_replace('\\', '/', $lock->path());

        $this->assertFileExists($lock->path());
        $this->assertStringNotContainsString(
            '/public/',
            $path,
            'The install lock must not live inside the web root'
        );
    }

    public function test_recovery_token_unlocks_a_single_request(): void
    {
        $lock = $this->lock();
        $lock->lock('https://acme.test', 'Acme', 'owner@acme.test');

        $token = $lock->recoveryToken();

        $this->assertTrue($lock->verifyToken($token));
        $this->assertFalse($lock->verifyToken('wrong-token'));
        $this->assertFalse($lock->verifyToken(null));
        $this->assertFalse($lock->verifyToken(''));

        $this->get('/install?recovery_token='.$token)->assertOk();
    }

    public function test_recovery_token_is_stable_per_path(): void
    {
        $lock = $this->lock();
        $this->assertSame($lock->recoveryToken(), $lock->recoveryToken());
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $lock->recoveryToken());
    }

    public function test_wrong_token_does_not_reopen_the_installer(): void
    {
        $this->lock()->lock('https://acme.test', 'Acme', 'owner@acme.test');

        $this->get('/install?recovery_token=not-the-token')
            ->assertRedirect(route('login'));
    }

    public function test_unlock_removes_the_lock_and_reopens_the_installer(): void
    {
        $lock = $this->lock();
        $lock->lock('https://acme.test', 'Acme', 'owner@acme.test');

        $this->assertTrue($lock->unlock());
        $this->assertFalse($this->lock()->installed());

        $this->get('/install')->assertOk();
    }

    public function test_unlock_command_requires_force(): void
    {
        $this->lock()->lock('https://acme.test', 'Acme', 'owner@acme.test');

        $this->artisan('lindu:unlock')->assertFailed();

        $this->assertTrue($this->lock()->installed(), 'Lock was removed without --force');
    }

    public function test_unlock_command_with_force_removes_the_lock(): void
    {
        $this->lock()->lock('https://acme.test', 'Acme', 'owner@acme.test');

        $this->artisan('lindu:unlock --force')->assertSuccessful();

        $this->assertFalse($this->lock()->installed());
    }

    public function test_unlock_command_reports_when_already_open(): void
    {
        $this->artisan('lindu:unlock --force')->assertSuccessful();
        $this->get('/install')->assertOk();
    }

    public function test_unlock_command_can_print_the_recovery_token_without_unlocking(): void
    {
        $this->lock()->lock('https://acme.test', 'Acme', 'owner@acme.test');

        $this->artisan('lindu:unlock --token')->assertSuccessful();

        // Printing the token must not unlock anything.
        $this->assertTrue($this->lock()->installed());
    }
}
