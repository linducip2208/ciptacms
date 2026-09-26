<?php

namespace App\Core\Services\Quota;

use RuntimeException;

/**
 * Raised when a plan's hard cap for a metric has been reached.
 *
 * Rendered as HTTP 402 by bootstrap/app.php: a billing condition, not a
 * server fault.
 */
class QuotaExceeded extends RuntimeException
{
    public function __construct(
        public readonly string $metric,
        public readonly int $used,
        public readonly int $limit,
    ) {
        parent::__construct(sprintf(
            'The %s limit for this plan has been reached (%d of %d).',
            $metric,
            $used,
            $limit
        ));
    }
}
