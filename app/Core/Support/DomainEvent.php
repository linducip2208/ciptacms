<?php

namespace App\Core\Support;

/**
 * Domain events with an explicit payload contract.
 *
 * The framework's `event($name, $payload)` hands the listener the payload's
 * *values* as separate positional arguments — `event('x', ['a'=>1,'b'=>2])`
 * calls `$listener(1, 2)`. That makes the payload unrecoverable in a plugin
 * hook unless every call site is audited, and a hook that silently receives
 * nothing is worse than no hook at all.
 *
 * Firing through here wraps the payload as a single argument, so a listener
 * written as `function ($payload)` receives the array it expects, and the
 * contract is visible at the call site.
 */
class DomainEvent
{
    public static function fire(string $name, array $payload = []): void
    {
        event($name, [$payload]);
    }

    /**
     * Recover a payload from whatever shape a listener was given.
     *
     * Handles the wrapped form this class produces, the bare-array form an
     * older call site may still use, and a plain `event($name)` with no
     * payload.
     */
    public static function payload(array $args): array
    {
        if ($args === []) {
            return [];
        }

        if (count($args) === 1 && is_array($args[0])) {
            return $args[0];
        }

        foreach ($args as $arg) {
            if (is_array($arg)) {
                return $arg;
            }
        }

        return [];
    }
}
