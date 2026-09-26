<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TenantUsage extends Model
{
    protected $table = 'tenant_usages';

    protected $fillable = ['tenant_id', 'metric', 'period', 'used', 'limit'];

    protected $casts = [
        'used' => 'integer',
        'limit' => 'integer',
    ];

    /**
     * Add $delta to a counter, creating the row on first use.
     *
     * A plain `used + n` expression cannot be used for the INSERT path: there
     * is no prior row to add to, so the result is NULL and the counter stays
     * at zero. Update first, insert only when nothing matched.
     */
    public static function record(string $tenantId, string $metric, int $delta = 1, ?int $limit = null): void
    {
        $period = now()->format('Y-m');
        $delta = (int) abs($delta);

        $affected = static::query()
            ->where('tenant_id', $tenantId)
            ->where('metric', $metric)
            ->where('period', $period)
            ->update([
                'used' => DB::raw('used + '.$delta),
                'limit' => $limit,
            ]);

        if ($affected === 0) {
            // update() reports 0 both when there is no row and when the
            // values are unchanged, so check before inserting.
            $exists = static::query()
                ->where('tenant_id', $tenantId)
                ->where('metric', $metric)
                ->where('period', $period)
                ->exists();

            if (! $exists) {
                static::query()->create([
                    'tenant_id' => $tenantId,
                    'metric' => $metric,
                    'period' => $period,
                    'used' => $delta,
                    'limit' => $limit,
                ]);
            }
        }
    }
}
