<?php

namespace App\Console\Commands;

use App\Core\Services\Quota;
use Illuminate\Console\Command;

/**
 * Recounts plan usage from the source tables.
 *
 * The counters are incremented as content is written, which is fast but can
 * drift after a manual database edit, a restored backup or a failed
 * transaction. This walks the truth back over the counters.
 */
class QuotaRecountCommand extends Command
{
    protected $signature = 'lindu:quota-recount {--tenant= : Restrict to one tenant id}';

    protected $description = 'Recalculate plan usage counters from the source tables';

    public function handle(): int
    {
        if ($id = $this->option('tenant')) {
            $tenant = \App\Models\Tenant::find($id);
            if (! $tenant) {
                $this->error("No tenant with id [{$id}].");

                return self::FAILURE;
            }
            app()->instance('tenant', $tenant);
        }

        $count = Quota::recountAll();

        $this->info("Recounted {$count} metric(s).");

        foreach (Quota::snapshot() as $metric => $row) {
            $this->line(sprintf(
                '  %-12s %8s / %s',
                $metric,
                $row['used'],
                $row['limit'] ?? 'unlimited'
            ));
        }

        return self::SUCCESS;
    }
}
