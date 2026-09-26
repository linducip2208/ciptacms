<?php
namespace App\Core\Services;
use App\Models\Tenant;
class TenantService {
    public function resolve(?string $host=null): ?Tenant {
        $host = $host ?: request()->getHost();
        try {
            $t = Tenant::where('domain',$host)->orWhere('subdomain',explode('.',$host)[0])->where('status','active')->first();
            if ($t) { app()->instance('tenant',$t); }
            return $t;
        } catch(\Throwable $e){ return null; }
    }
    public function checkQuota(Tenant $t, string $key, int $increment=0): bool {
        $quotas = $t->quotas ?? [];
        $limit = $quotas[$key.'_limit'] ?? PHP_INT_MAX;
        $used = $quotas[$key.'_used'] ?? 0;
        return ($used + $increment) <= $limit;
    }
}
