<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantUsage extends Model
{
    protected $table = 'tenant_usages';

    protected $fillable = ['tenant_id', 'metric', 'period', 'used', 'limit'];

    protected $casts = [
        'used' => 'integer',
        'limit' => 'integer',
    ];

    public static function record(string $tenantId, string $metric, int $delta = 1, ?int $limit = null): void
    {
        $period = now()->format('Y-m');
        static::query()->updateOrCreate(
            ['tenant_id' => $tenantId, 'metric' => $metric, 'period' => $period],
            ['used' => \Illuminate\Support\Facades\DB::raw('used + '.(int) abs($delta)), 'limit' => $limit]
        );
    }
}
