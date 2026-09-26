<?php

namespace App\Core\Services;

use App\Models\PlanFeature;
use App\Models\Tenant;
use App\Models\TenantUsage;

/**
 * Feature flags and quota accounting. Works with or without a tenant:
 * a single-tenant install treats every flag as enabled unless the plan
 * explicitly disables it.
 */
class FeatureFlag
{
    public static function enabled(string $key, $tenant = null): bool
    {
        $tenant = $tenant ?: tenant();

        try {
            $query = PlanFeature::where('key', $key);

            if ($tenant instanceof Tenant && $tenant->plan_id) {
                $feature = $query->where('plan_id', $tenant->plan_id)->first();
                if ($feature) {
                    return (bool) $feature->is_enabled;
                }
            }

            // Nothing configured for this plan.
            //
            // Default is ON. A feature is only off when some plan explicitly
            // records is_enabled = false, or this plan does. Treating "no
            // configuration" as "disabled" would lock a fresh single-tenant
            // install out of its own page builder.
            return ! PlanFeature::where('key', $key)->where('is_enabled', false)->exists();
        } catch (\Throwable $e) {
            // Table missing during install: allow everything rather than
            // locking the operator out of their own site.
            return true;
        }
    }

    public static function limit(string $key, $tenant = null): ?int
    {
        $tenant = $tenant ?: tenant();

        try {
            if (! $tenant instanceof Tenant || ! $tenant->plan_id) {
                return null;
            }
            $limits = PlanFeature::where('key', $key)
                ->where('plan_id', $tenant->plan_id)
                ->value('limits');

            if (! is_array($limits)) {
                return null;
            }

            $v = $limits['limit'] ?? $limits['max'] ?? null;

            return $v === null ? null : (int) $v;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function track(string $metric, int $delta = 1, $tenant = null): void
    {
        $tenant = $tenant ?: tenant();

        try {
            TenantUsage::record(
                $tenant instanceof Tenant ? $tenant->id : 'default',
                $metric,
                $delta,
                self::limit($metric, $tenant)
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function used(string $metric, $tenant = null): int
    {
        $tenant = $tenant ?: tenant();

        try {
            return (int) TenantUsage::where('tenant_id', $tenant instanceof Tenant ? $tenant->id : 'default')
                ->where('metric', $metric)
                ->where('period', now()->format('Y-m'))
                ->value('used');
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** True when the metric has hit its plan limit. */
    public static function exhausted(string $metric, $tenant = null): bool
    {
        $limit = self::limit($metric, $tenant);

        return $limit !== null && $limit > 0 && self::used($metric, $tenant) >= $limit;
    }
}
