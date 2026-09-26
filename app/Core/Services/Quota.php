<?php

namespace App\Core\Services;

use App\Core\Services\Quota\FeatureUnavailable;
use App\Core\Services\Quota\QuotaExceeded;
use App\Models\Tenant;

/**
 * Plan enforcement.
 *
 * FeatureFlag and TenantUsage were defined but consulted nowhere, so the
 * Plans / Features / Usage screens were decorative: an operator could set a
 * limit of 100 uploads and the CMS would never stop anyone.
 *
 * This class is the single place that turns those numbers into behaviour.
 * Two shapes are offered:
 *
 *   QuotaExceeded — thrown, for hard caps (an upload must not be written).
 *   allows()      — boolean, for soft caps (offer an upgrade instead of
 *                   refusing outright).
 *
 * The two exception types live in their own files so they are autoloadable
 * and can be caught by type without loading this class first.
 */
class Quota
{
    /** @return array<string,string> Metrics the CMS can count. */
    public const METRICS = [
        'media' => 'Media uploads',
        'pages' => 'Pages',
        'posts' => 'Posts',
        'users' => 'User accounts',
        'records' => 'Content records',
        'api_calls' => 'API calls',
        'storage_mb' => 'Storage (MB)',
    ];

    /** @return array<string,string> Features a plan can gate. */
    public const FEATURES = [
        'page_builder' => 'Page builder',
        'form_builder' => 'Form builder',
        'data_builder' => 'Data builder',
        'workflow' => 'Workflows',
        'webhooks' => 'Webhooks',
        'multi_site' => 'Multi-site',
        'api' => 'REST API',
        'white_label' => 'White label',
    ];

    /** Unlimited when no tenant, or when the plan sets no limit. */
    public static function allows(string $metric, int $additional = 1, $tenant = null): bool
    {
        $limit = FeatureFlag::limit($metric, $tenant);

        if ($limit === null || $limit <= 0) {
            return true;
        }

        return (FeatureFlag::used($metric, $tenant) + $additional) <= $limit;
    }

    /**
     * Count usage and refuse when the cap is already reached.
     *
     * The counter is incremented first so two concurrent requests cannot both
     * slip past the check.
     */
    public static function consume(string $metric, int $delta = 1, $tenant = null): int
    {
        FeatureFlag::track($metric, $delta, $tenant);

        if (! self::allows($metric, 0, $tenant)) {
            throw new QuotaExceeded(
                $metric,
                FeatureFlag::used($metric, $tenant),
                FeatureFlag::limit($metric, $tenant) ?? PHP_INT_MAX
            );
        }

        return FeatureFlag::used($metric, $tenant);
    }

    /** Refuse when the cap is reached, without counting a failure. */
    public static function guard(string $metric, int $additional = 1, $tenant = null): void
    {
        if (! self::allows($metric, $additional, $tenant)) {
            throw new QuotaExceeded(
                $metric,
                FeatureFlag::used($metric, $tenant),
                FeatureFlag::limit($metric, $tenant) ?? PHP_INT_MAX
            );
        }
    }

    public static function assertFeature(string $feature, $tenant = null): void
    {
        if (! FeatureFlag::enabled($feature, $tenant)) {
            throw new FeatureUnavailable($feature);
        }
    }

    public static function hasFeature(string $feature, $tenant = null): bool
    {
        return FeatureFlag::enabled($feature, $tenant);
    }

    /** Current usage against the limit, for the admin screens. */
    public static function snapshot($tenant = null): array
    {
        $out = [];

        foreach (array_keys(self::METRICS) as $metric) {
            $out[$metric] = [
                'label' => self::METRICS[$metric],
                'used' => FeatureFlag::used($metric, $tenant),
                'limit' => FeatureFlag::limit($metric, $tenant),
            ];
        }

        return $out;
    }

    public static function featureStates($tenant = null): array
    {
        $out = [];

        foreach (array_keys(self::FEATURES) as $feature) {
            $out[$feature] = [
                'label' => self::FEATURES[$feature],
                'enabled' => FeatureFlag::enabled($feature, $tenant),
            ];
        }

        return $out;
    }

    /**
     * Recount every metric from its source table.
     *
     * Call from the scheduler so counters cannot drift away from reality after
     * a manual database edit or a restored backup.
     */
    public static function recountAll(): int
    {
        $counts = [
            'media' => fn () => \App\Models\MediaFile::count(),
            'pages' => fn () => \App\Models\Page::count(),
            'posts' => fn () => \App\Models\Post::count(),
            'users' => fn () => \App\Models\User::count(),
            'records' => fn () => \App\Models\ContentRecord::count(),
        ];

        $done = 0;

        foreach ($counts as $metric => $counter) {
            try {
                $actual = $counter();
                $period = now()->format('Y-m');
                $tenantId = app()->bound('tenant') && app('tenant') instanceof Tenant
                    ? app('tenant')->id
                    : 'default';

                \App\Models\TenantUsage::updateOrCreate(
                    ['tenant_id' => $tenantId, 'metric' => $metric, 'period' => $period],
                    ['used' => $actual, 'limit' => FeatureFlag::limit($metric)]
                );
                $done++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $done;
    }
}
