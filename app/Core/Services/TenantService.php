<?php

namespace App\Core\Services;

use App\Models\Tenant;

class TenantService
{
    public function resolve(?string $host = null): ?Tenant
    {
        $host = strtolower($host ?: request()->getHost());

        try {
            $tenant = $this->queryForHost($host)->first();
        } catch (\Throwable $e) {
            // The tenants table may not exist yet during install.
            return null;
        }

        if ($tenant) {
            app()->instance('tenant', $tenant);
        }

        return $tenant;
    }

    /**
     * Host → tenant lookup.
     *
     * The status filter wraps the whole disjunction. Without the grouping,
     * `where(domain)->orWhere(subdomain)->where(status)` compiles to
     * `domain = ? OR (subdomain = ? AND status = 'active')`, which lets a
     * suspended tenant resolve as long as it matched on domain.
     *
     * Subdomain matching is only attempted for hosts that are genuinely
     * subdomains of a configured base domain — otherwise the first label of
     * any host (`acme` from `acme.co.uk`) would match an unrelated tenant.
     */
    protected function queryForHost(string $host)
    {
        $exact = Tenant::where('domain', $host);

        $base = config('tenancy.base_domain');
        if (! $base) {
            return $exact->where('status', 'active');
        }

        $base = strtolower($base);
        $suffix = '.'.$base;

        if (! str_ends_with($host, $suffix)) {
            return $exact->where('status', 'active');
        }

        $label = substr($host, 0, -strlen($suffix));

        return Tenant::where(function ($q) use ($host, $label) {
            $q->where('domain', $host)->orWhere('subdomain', $label);
        })->where('status', 'active');
    }

    public function checkQuota(Tenant $t, string $key, int $increment = 0): bool
    {
        $quotas = $t->quotas ?? [];
        $limit = $quotas[$key.'_limit'] ?? PHP_INT_MAX;
        $used = $quotas[$key.'_used'] ?? 0;

        return ($used + $increment) <= $limit;
    }
}
