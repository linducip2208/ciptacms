<?php

namespace App\Core\Services\Quota;

use RuntimeException;

/**
 * Raised when a tenant's plan does not include a gated feature.
 *
 * Rendered as HTTP 403 by bootstrap/app.php.
 */
class FeatureUnavailable extends RuntimeException
{
    public function __construct(public readonly string $feature)
    {
        parent::__construct("The [{$feature}] feature is not available on this plan.");
    }
}
