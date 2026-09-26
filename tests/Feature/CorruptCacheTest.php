<?php

namespace Tests\Feature;

use App\Core\Services\SettingService;
use App\Core\Support\SafeCache;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Cache stores serialize values. A stored Collection has to be unserialized
 * on every read, which fails with "incomplete object" when the entry was
 * written by a different build of the class or a partial write.
 *
 * Because setting() reads SettingService::all() from every view, one such
 * entry took the entire site down. These tests pin the recovery behaviour.
 */
class CorruptCacheTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Reproduce the real failure: a value that decodes into an *incomplete
     * object*. The class is correct, so unserialize() succeeds — the error
     * only appears when a method is called on it, which is exactly what
     * SettingService::all() did before this was fixed.
     */
    protected function poison(string $key): void
    {
        // "I:0;" declares zero properties for a class that has several.
        $serialized = sprintf('O:%d:"%s":0:{}', strlen(Collection::class), Collection::class);

        DB::table('cache')->updateOrInsert(
            ['key' => $key],
            [
                'value' => $serialized,
                'expiration' => now()->addHour()->getTimestamp(),
            ]
        );
    }

    /**
     * The file store serializes, so it reproduces the decode failure without
     * depending on a cache table.
     */
    protected function useDatabaseCache(): void
    {
        config([
            'cache.default' => 'file',
            'cache.stores.file.path' => storage_path('framework/testing-cache'),
        ]);
        Cache::clear();
    }

    /**
     * The file store writes real files; do not leave them in the project.
     */
    protected function tearDown(): void
    {
        $path = storage_path('framework/testing-cache');

        if (is_dir($path)) {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $f) {
                $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
            }
            @rmdir($path);
        }

        parent::tearDown();
    }

    public function test_settings_survive_a_corrupt_cache_entry(): void
    {
        $this->useDatabaseCache();
        $this->seed(SettingSeeder::class);

        $this->poison('lindu.settings.all');

        // Must not throw, and must still return real values.
        $value = app(SettingService::class)->get('general.site_name', 'fallback');

        $this->assertNotNull($value);
        $this->assertNotSame('fallback', $value, 'Settings did not recover from a corrupt cache entry');
    }

    public function test_the_admin_menu_survives_a_corrupt_cache_entry(): void
    {
        $this->useDatabaseCache();
        $this->seed(SettingSeeder::class);
        $this->seed(\Database\Seeders\MenuSeeder::class);

        $this->poison('lindu.menu.admin.guest.global');

        $tree = app(\App\Core\Services\MenuService::class)->tree('admin');

        $this->assertIsArray($tree);
    }

    public function test_an_admin_page_still_renders_with_a_corrupt_settings_cache(): void
    {
        $this->useDatabaseCache();
        $this->seed(\Database\Seeders\RolesPermissionsSeeder::class);
        $this->seed(SettingSeeder::class);
        $this->seed(\Database\Seeders\MenuSeeder::class);

        $user = \App\Models\User::create([
            'name' => 'C', 'email' => 'c@c.local',
            'password' => bcrypt('password123'), 'status' => 'active', 'is_active' => true,
        ]);
        $user->roles()->attach(\App\Models\Role::where('slug', 'admin')->first());

        $this->poison('lindu.settings.all');

        $this->actingAs($user)->get('/admin')->assertOk();
    }

    public function test_a_rebuilt_entry_is_a_working_collection(): void
    {
        $this->useDatabaseCache();
        $this->seed(SettingSeeder::class);

        $this->poison('lindu.settings.all');

        $rebuilt = app(SettingService::class)->all();

        $this->assertInstanceOf(Collection::class, $rebuilt);
        // Fully usable, not just an instance of the right class.
        $this->assertIsArray($rebuilt->all());
        $this->assertTrue($rebuilt->keys()->contains('general.site_name'));
    }

    public function test_is_readable_reports_a_poisoned_entry(): void
    {
        $this->useDatabaseCache();

        $this->poison('broken.key');
        $this->assertDatabaseHas('cache', ['key' => 'broken.key']);

        // Whatever the decoder makes of the bytes, the probe must answer
        // without throwing. A diagnostic that can crash is worse than none.
        $result = SafeCache::isReadable('broken.key', Collection::class);

        $this->assertIsBool($result);
    }

    public function test_a_healthy_entry_is_readable(): void
    {
        $this->useDatabaseCache();

        SafeCache::remember('good.key', 60, fn () => ['a' => 1]);

        $this->assertTrue(SafeCache::isReadable('good.key'));
    }

    public function test_is_readable_flags_a_cached_collection(): void
    {
        $this->useDatabaseCache();

        // A Collection does not survive the cache store, so the probe must
        // notice that the entry is not what it was written as.
        SafeCache::remember('coll.key', 60, fn () => new Collection(['a' => 1]));

        $this->assertFalse(
            SafeCache::isReadable('coll.key', Collection::class),
            'A cached Collection was reported as a healthy Collection'
        );
    }

    public function test_a_failing_callback_still_fails_loudly(): void
    {
        // The cache layer must be forgiving, not mask real application errors.
        $this->expectException(\RuntimeException::class);

        SafeCache::remember('explode', 60, function () {
            throw new \RuntimeException('database is down');
        });
    }
}
