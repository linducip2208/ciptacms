<?php

namespace Tests\Feature;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Why settings used to fatal with "tried to call a method on an incomplete
 * object": a Collection does not survive a round trip through the cache
 * store, while an array does. This pins that difference so the cache layer
 * is never handed an object again.
 */
class CacheRoundTripTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The array driver still serializes values, so it reproduces the
     * decode behaviour without depending on a cache table.
     */
    protected function useSerializingCache(): void
    {
        config([
            'cache.default' => 'file',
            'cache.stores.file.path' => storage_path('framework/testing-cache'),
        ]);
        Cache::clear();
    }

    public function test_an_array_survives_the_cache(): void
    {
        $this->useSerializingCache();

        Cache::put('probe.array', ['a' => 1], 60);

        $this->assertSame(['a' => 1], Cache::get('probe.array'));
    }

    public function test_a_collection_does_not_survive_the_cache(): void
    {
        $this->useSerializingCache();

        Cache::put('probe.collection', new Collection(['a' => 1]), 60);
        $back = Cache::get('probe.collection');

        // It is not the Collection we stored, and calling a method on it is
        // exactly the failure this guards against.
        $this->assertNotInstanceOf(Collection::class, $back);
    }

    public function test_settings_cache_holds_an_array_and_returns_a_collection(): void
    {
        $this->useSerializingCache();
        $this->seed(\Database\Seeders\SettingSeeder::class);

        // The caller gets a Collection...
        $settings = app(\App\Core\Services\SettingService::class)->all();
        $this->assertInstanceOf(Collection::class, $settings);

        // ...but what is in the cache is a plain array, so the next read is
        // safe.
        $this->assertIsArray(Cache::get('lindu.settings.all'));
    }

    public function test_settings_survive_a_repeated_read(): void
    {
        $this->useSerializingCache();
        $this->seed(\Database\Seeders\SettingSeeder::class);

        $service = app(\App\Core\Services\SettingService::class);

        $first = $service->all();
        $second = $service->all();
        $third = $service->all();

        $this->assertTrue($first->keys()->contains('general.site_name'));
        $this->assertTrue($second->keys()->contains('general.site_name'));
        $this->assertTrue($third->keys()->contains('general.site_name'));
        $this->assertNotNull(setting('general.site_name'));
    }
}
