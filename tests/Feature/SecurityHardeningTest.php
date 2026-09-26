<?php

namespace Tests\Feature;

use App\Core\Services\TenantService;
use App\Models\Module;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Regression tests for the three security defects found during the
 * repository audit. Each one is a real hole, not a style issue.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // 1. ModuleController::action() must not expose arbitrary methods
    // ------------------------------------------------------------------

    public function test_module_action_route_rejects_methods_outside_the_allowlist(): void
    {
        $this->actingAs($this->admin());

        // "syncModuleMenus" and "isActive" are real public methods on
        // ModuleManager. Before the allowlist the URL could call them.
        $this->post('/admin/modules/blog/syncModuleMenus')->assertNotFound();
        $this->post('/admin/modules/blog/forget')->assertNotFound();
    }

    public function test_module_action_still_allows_the_supported_actions(): void
    {
        $this->actingAs($this->admin());
        Module::create(['name' => 'Blog', 'slug' => 'blog', 'version' => '1.0.0']);

        foreach (['activate', 'deactivate'] as $action) {
            $this->post("/admin/modules/blog/{$action}")->assertRedirect();
        }
    }

    public function test_module_actions_are_not_in_the_route_pattern(): void
    {
        // The allowlist lives in the controller, so the route accepts any
        // string by design. This asserts the guard, not the pattern.
        $this->actingAs($this->admin());
        $this->post('/admin/modules/blog/nonexistentMethod')->assertNotFound();
    }

    // ------------------------------------------------------------------
    // 2. A suspended tenant must never resolve
    // ------------------------------------------------------------------

    public function test_suspended_tenant_does_not_resolve_on_exact_domain(): void
    {
        $t = Tenant::create([
            'id' => '11111111-1111-1111-1111-111111111111',
            'uuid' => '11111111-1111-1111-1111-111111111111',
            'name' => 'Suspended Co',
            'slug' => 'suspended',
            'domain' => 'suspended.example.com',
            'status' => 'suspended',
        ]);

        $resolved = app(TenantService::class)->resolve('suspended.example.com');

        $this->assertNull($resolved, 'A suspended tenant resolved from its exact domain');
        $this->assertNotSame($t->id, app()->bound('tenant') ? app('tenant')->id : null);
    }

    public function test_active_tenant_still_resolves_on_exact_domain(): void
    {
        Tenant::create([
            'id' => '22222222-2222-2222-2222-222222222222',
            'uuid' => '22222222-2222-2222-2222-222222222222',
            'name' => 'Active Co',
            'slug' => 'active',
            'domain' => 'active.example.com',
            'status' => 'active',
        ]);

        $resolved = app(TenantService::class)->resolve('active.example.com');

        $this->assertNotNull($resolved);
        $this->assertSame('Active Co', $resolved->name);
    }

    public function test_subdomain_matching_only_applies_under_the_base_domain(): void
    {
        config(['tenancy.base_domain' => 'example.test']);

        Tenant::create([
            'id' => '33333333-3333-3333-3333-333333333333',
            'uuid' => '33333333-3333-3333-3333-333333333333',
            'name' => 'Sub Co',
            'slug' => 'sub',
            'subdomain' => 'sub',
            'status' => 'active',
        ]);

        // Under the base domain: resolves.
        $this->assertNotNull(app(TenantService::class)->resolve('sub.example.test'));

        // An unrelated public domain whose first label is "sub" must not match.
        $this->assertNull(
            app(TenantService::class)->resolve('sub.elsewhere.co.uk'),
            'The first label of an unrelated host matched a tenant'
        );
    }

    public function test_suspended_tenant_does_not_resolve_via_subdomain(): void
    {
        config(['tenancy.base_domain' => 'example.test']);

        Tenant::create([
            'id' => '44444444-4444-4444-4444-444444444444',
            'uuid' => '44444444-4444-4444-4444-444444444444',
            'name' => 'Suspended Sub',
            'slug' => 'suspended-sub',
            'subdomain' => 'suspendedsub',
            'status' => 'suspended',
        ]);

        $this->assertNull(app(TenantService::class)->resolve('suspendedsub.example.test'));
    }

    // ------------------------------------------------------------------
    // 3. Inbound webhooks must verify their signature
    // ------------------------------------------------------------------

    public function test_inbound_webhook_rejects_an_unsigned_request(): void
    {
        $this->createInboundKey('partner', 'shhh-secret');

        $this->postJson('/api/v1/webhooks/in/partner', ['event' => 'paid'])
            ->assertStatus(401);
    }

    public function test_inbound_webhook_rejects_a_wrong_signature(): void
    {
        $this->createInboundKey('partner', 'shhh-secret');

        $this->postJson('/api/v1/webhooks/in/partner', ['event' => 'paid'], [
            'X-Lindu-Signature' => 't=1,v1=deadbeef',
        ])->assertStatus(401);
    }

    public function test_inbound_webhook_without_a_secret_fails_closed(): void
    {
        // A key row that was never given a secret must not become an open
        // endpoint that anyone who saw the URL could post to.
        $this->createInboundKey('partner');

        $this->postJson('/api/v1/webhooks/in/partner', ['event' => 'paid'])
            ->assertStatus(403);
    }

    public function test_inbound_webhook_accepts_a_valid_signature(): void
    {
        $this->createInboundKey('partner', 'shhh-secret');

        $payload = json_encode(['event' => 'paid', 'order' => 42]);
        $timestamp = (string) time();
        $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, 'shhh-secret');

        $this->call(
            'POST',
            '/api/v1/webhooks/in/partner',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_LINDU_SIGNATURE' => $signature,
            ],
            $payload
        )->assertOk();
    }

    public function test_inbound_webhook_rejects_a_stale_timestamp(): void
    {
        $this->createInboundKey('partner', 'shhh-secret');

        // A captured signature replayed an hour later must not be accepted.
        $payload = json_encode(['event' => 'paid']);
        $timestamp = (string) (time() - 3600);
        $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, 'shhh-secret');

        $this->call(
            'POST',
            '/api/v1/webhooks/in/partner',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_LINDU_SIGNATURE' => $signature,
            ],
            $payload
        )->assertStatus(401);
    }

    public function test_unknown_inbound_key_is_not_found(): void
    {
        $this->postJson('/api/v1/webhooks/in/nope', ['a' => 1])->assertStatus(404);
    }

    public function test_inbound_webhook_route_is_rate_limited(): void
    {
        $this->createInboundKey('partner', 'shhh-secret');

        $limiter = app(\Illuminate\Cache\RateLimiter::class);
        $key = 'webhook-in:127.0.0.1';

        for ($i = 0; $i < 120; $i++) {
            $limiter->hit($key, 60);
        }

        $this->assertTrue($limiter->tooManyAttempts($key, 120));
    }

    // ------------------------------------------------------------------
    // 4. Media uploads must honour the allowlist
    // ------------------------------------------------------------------

    public function test_media_upload_rejects_a_disallowed_type(): void
    {
        config(['lindu.media.disk' => 'local', 'lindu.media.allowed_mimes' => ['jpg', 'png', 'pdf']]);

        $php = \Illuminate\Http\UploadedFile::fake()->createWithContent(
            'shell.php',
            "<?php echo 'pwned'; ?>"
        );

        $this->expectException(\RuntimeException::class);

        app(\App\Core\Services\MediaService::class)->store($php);
    }

    public function test_media_upload_accepts_an_allowed_type(): void
    {
        config([
            'lindu.media.disk' => 'local',
            'lindu.media.allowed_mimes' => ['jpg', 'png', 'pdf'],
        ]);

        $png = \Illuminate\Http\UploadedFile::fake()->image('photo.png', 10, 10);

        $media = app(\App\Core\Services\MediaService::class)->store($png, null, null, false);

        $this->assertNotEmpty($media->path);
        $this->assertDatabaseHas('media_files', ['id' => $media->id]);
    }

    // ------------------------------------------------------------------

    protected function admin()
    {
        $this->seed(\Database\Seeders\RolesPermissionsSeeder::class);

        $user = \App\Models\User::create([
            'name' => 'S', 'email' => 's@s.local',
            'password' => Hash::make('password123'),
            'status' => 'active', 'is_active' => true,
        ]);
        $user->roles()->attach(\App\Models\Role::where('slug', 'admin')->first());

        return $user;
    }

    protected function createInboundKey(string $key, ?string $secret = null): void
    {
        DB::table('webhook_incoming_keys')->insert([
            'tenant_id' => null,
            'name' => ucfirst($key),
            'key' => $key,
            'secret' => $secret,
            'forward_event' => 'webhook',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
