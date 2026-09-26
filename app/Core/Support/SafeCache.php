<?php

namespace App\Core\Support;

use Closure;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Cache reads that cannot take the site down.
 *
 * Cache stores serialize their values. A stored Collection or model has to be
 * unserialized on every read, which fails — with "incomplete object" — when
 * the entry was written by a different build of the class, a partially
 * written file, or a restored database. SettingService and MenuService back
 * reads that `setting()` depends on from every view, so one corrupt entry
 * turned into a 500 across the whole site.
 *
 * Here a failed read discards the entry and recomputes it. A failed write is
 * reported and ignored: a cache that cannot be written is slow, not broken.
 */
class SafeCache
{
    /**
     * @template TValue
     *
     * @param  string  $key
     * @param  int|\DateTimeInterface|\DateInterval  $seconds
     * @return TValue
     */
    public static function remember(string $key, $seconds, Closure $callback)
    {
        try {
            $hit = Cache::get($key);
        } catch (Throwable $e) {
            // The stored value could not be decoded. Drop it and rebuild.
            report($e);
            self::discard($key);

            return $callback();
        }

        if ($hit !== null) {
            return $hit;
        }

        $value = $callback();

        try {
            Cache::put($key, self::normalise($value), $seconds);
        } catch (Throwable $e) {
            report($e);
        }

        return $value;
    }

    /**
     * Never hand a serializing cache store an object.
     *
     * A Collection written to the database or file store comes back as
     * __PHP_Incomplete_Class, and the first method call on it fatals with
     * "tried to call a method on an incomplete object". Flatten anything
     * array-like to a plain array, which serializes without a class.
     */
    public static function normalise(mixed $value): mixed
    {
        if ($value instanceof \Illuminate\Support\Collection) {
            return $value->all();
        }

        if ($value instanceof \Illuminate\Contracts\Support\Arrayable) {
            return $value->toArray();
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        return $value;
    }

    /** Cache::forget() must never be the thing that throws. */
    public static function discard(string $key): void
    {
        try {
            Cache::forget($key);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Is the stored entry decodable *and* usable?
     *
     * Fetching alone is not enough: a value written by another build can
     * decode into an incomplete object that only fails when a method is
     * called. The probe touches it so that case is caught here too.
     *
     * @param  class-string|null  $expected  Require the value to be this type.
     */
    public static function isReadable(string $key, ?string $expected = null): bool
    {
        try {
            $value = Cache::get($key);

            if ($value === null) {
                return true; // nothing cached is not a problem
            }

            if ($expected !== null && ! $value instanceof $expected) {
                return false;
            }

            // Force the incomplete-object failure, if there is one.
            if ($value instanceof \Illuminate\Support\Collection) {
                $value->all();
            } elseif (is_object($value) && method_exists($value, 'toArray')) {
                $value->toArray();
            }

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
