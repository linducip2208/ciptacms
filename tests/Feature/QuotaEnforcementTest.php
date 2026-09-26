<?php

namespace Tests\Feature;

use App\Core\Services\FeatureFlag;
use App\Core\Services\Quota;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\Tenant;
use App\Models\TenantUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Plans, feature flags and quotas were all defined but consulted nowhere:
 * the SaaS screens were decorative. These tests prove the numbers now
 * actually stop work.
 */
class QuotaEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function tenant(array $limits = [], array $features = []): Tenant
    {
        $plan = Plan::create([
            'name' => 'Test plan', 'slug' => 'test-plan-'.uniqid(),
            'price' => 0, 'billing_period' => 'monthly', 'is_active' => true,
        ]);

        foreach ($limits as $key => $limit) {
            PlanFeature::create([
                'plan_id' => $plan->id, 'key' => $key, 'is_enabled' => true,
                'limits' => ['limit' => $limit],
            ]);
        }
        foreach ($features as $key => $on) {
            PlanFeature::create([
                'plan_id' => $plan->id, 'key' => $key, 'is_enabled' => $on,
            ]);
        }

        $tenant = Tenant::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Acme', 'slug' => 'acme-'.uniqid(),
            'plan_id' => $plan->id, 'status' => 'active',
        ]);

        app()->instance('tenant', $tenant);

        return $tenant;
    }

    protected function setUsage(Tenant $tenant, string $metric, int $used): void
    {
        TenantUsage::create([
            'tenant_id' => $tenant->id, 'metric' => $metric,
            'period' => now()->format('Y-m'), 'used' => $used,
        ]);
    }

    // ---- limits -------------------------------------------------------

    public function test_no_tenant_means_no_limit(): void
    {
        app()->forgetInstance('tenant');

        $this->assertTrue(Quota::allows('media'));
        $this->assertNull(FeatureFlag::limit('media'));
    }

    public function test_a_plan_without_a_limit_is_unlimited(): void
    {
        $this->tenant();

        $this->assertTrue(Quota::allows('media'));
    }

    public function test_usage_is_measured_against_the_plan_limit(): void
    {
        $tenant = $this->tenant(['media' => 3]);

        $this->assertTrue(Quota::allows('media', 1, $tenant));

        $this->setUsage($tenant, 'media', 3);

        $this->assertFalse(Quota::allows('media', 1, $tenant), 'The cap was not enforced at 3 of 3');
    }

    public function test_guard_throws_when_the_cap_is_reached(): void
    {
        $tenant = $this->tenant(['media' => 2]);
        $this->setUsage($tenant, 'media', 2);

        $this->expectException(Quota\QuotaExceeded::class);

        Quota::guard('media', 1, $tenant);
    }

    public function test_consume_counts_the_usage(): void
    {
        $tenant = $this->tenant(['media' => 10]);

        Quota::consume('media', 1, $tenant);

        $this->assertSame(1, FeatureFlag::used('media', $tenant));
    }

    public function test_snapshot_reports_used_and_limit(): void
    {
        $tenant = $this->tenant(['pages' => 25]);
        $this->setUsage($tenant, 'pages', 4);

        $snapshot = Quota::snapshot($tenant);

        $this->assertSame(4, $snapshot['pages']['used']);
        $this->assertSame(25, $snapshot['pages']['limit']);
        $this->assertArrayHasKey('media', $snapshot);
    }

    // ---- features -----------------------------------------------------

    public function test_a_disabled_feature_is_reported_unavailable(): void
    {
        $tenant = $this->tenant([], ['page_builder' => false]);

        $this->assertFalse(Quota::hasFeature('page_builder', $tenant));

        $this->expectException(Quota\FeatureUnavailable::class);
        Quota::assertFeature('page_builder', $tenant);
    }

    public function test_an_enabled_feature_passes(): void
    {
        $tenant = $this->tenant([], ['page_builder' => true]);

        $this->assertTrue(Quota::hasFeature('page_builder', $tenant));
        Quota::assertFeature('page_builder', $tenant);
    }

    public function test_feature_states_are_reported_for_the_admin(): void
    {
        $states = Quota::featureStates();

        $this->assertArrayHasKey('page_builder', $states);
        $this->assertArrayHasKey('label', $states['page_builder']);
    }

    // ---- recount ------------------------------------------------------

    public function test_recount_rewrites_counters_from_the_source_tables(): void
    {
        $tenant = $this->tenant();

        // A counter that has drifted away from reality.
        $this->setUsage($tenant, 'media', 999);

        Quota::recountAll();

        $this->assertSame(
            0,
            FeatureFlag::used('media', $tenant),
            'Recount did not replace the drifted counter'
        );
    }

    public function test_recount_command_runs(): void
    {
        $this->artisan('lindu:quota-recount')->assertSuccessful();
    }
}
