<?php
namespace App\Core\Traits;
use Illuminate\Database\Eloquent\Builder;
trait BelongsToTenant {
    public static function bootBelongsToTenant(): void {
        static::creating(function ($m) {
            if (app()->bound('tenant') && app('tenant') && empty($m->tenant_id)) {
                $m->tenant_id = app('tenant')->id;
            }
        });
        static::addGlobalScope('tenant', function (Builder $b) {
            if (app()->bound('tenant') && app('tenant')) {
                // Only apply when column exists to keep single-tenant installs simple
                try { $b->where($b->getModel()->getTable().'.tenant_id', app('tenant')->id); }
                catch (\Throwable $e) {}
            }
        });
    }
    public function tenant() { return $this->belongsTo(\App\Models\Tenant::class); }
}
