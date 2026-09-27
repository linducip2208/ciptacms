<?php

namespace Tests\Feature;

use App\Core\Services\ModuleManager;
use App\Models\Module;
use App\Models\MenuItem;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The Module Engine has to be trustworthy: activating a module must not be
 * able to create a menu entry that leads nowhere, and a module whose class
 * cannot load must fail loudly rather than pretend to work.
 */
class ModuleEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function admin()
    {
        $this->seed(RolesPermissionsSeeder::class);
        $u = \App\Models\User::create([
            'name' => 'M', 'email' => 'm@m.local',
            'password' => Hash::make('password123'), 'status' => 'active', 'is_active' => true,
        ]);
        $u->roles()->attach(\App\Models\Role::where('slug', 'admin')->first());

        return $u;
    }

    public function test_only_cms_modules_ship(): void
    {
        // A commercial CMS does not ship placeholder folders for industries
        // it does not serve. The engine is still proven by the real modules.
        $slugs = collect((new ModuleManager)->discover())->pluck('slug');

        foreach (['pos', 'erp', 'hotel', 'jodohku', 'crm', 'lms', 'ecommerce', 'marketplace'] as $forbidden) {
            $this->assertNotContains(
                $forbidden,
                $slugs->all(),
                $forbidden . ' still ships as a stub module'
            );
        }
    }

    public function test_the_real_cms_modules_are_present(): void
    {
        $slugs = collect((new ModuleManager)->discover())->pluck('slug');

        foreach (['company-profile', 'pages', 'blog', 'forms', 'media', 'seo', 'api', 'tenants'] as $expected) {
            $this->assertContains($expected, $slugs->all(), $expected . ' is missing');
        }
    }

    public function test_every_manifest_declares_a_version_and_slug(): void
    {
        foreach ((new ModuleManager)->discover() as $manifest) {
            $this->assertArrayHasKey('slug', $manifest, basename($manifest['path']).' has no slug');
            $this->assertArrayHasKey('version', $manifest, $manifest['slug'].' has no version');
            $this->assertArrayHasKey('name', $manifest, $manifest['slug'].' has no name');
            $this->assertMatchesRegularExpression(
                '/^\d+\.\d+\.\d+$/',
                $manifest['version'],
                $manifest['slug'].' has a malformed version'
            );
        }
    }

    public function test_activating_a_module_never_creates_a_dead_menu_link(): void
    {
        $this->seed(\Database\Seeders\SettingSeeder::class);
        $this->seed(\Database\Seeders\MenuSeeder::class);

        $admin = $this->admin();
        $manager = app(ModuleManager::class);

        foreach (collect((new ModuleManager)->discover())->pluck('slug') as $slug) {
            Module::updateOrCreate(['slug' => $slug], [
                'name' => $slug, 'version' => '1.0.0', 'is_installed' => true,
            ]);

            $this->actingAs($admin)->post("/admin/modules/{$slug}/activate");

            foreach (MenuItem::where('module', $slug)->get() as $item) {
                if (empty($item->url) || ! str_starts_with($item->url, '/')) {
                    continue;
                }

                $resolves = true;
                try {
                    Route::getRoutes()->match(
                        \Illuminate\Http\Request::create($item->url, 'GET')
                    );
                } catch (\Throwable $e) {
                    $resolves = false;
                }

                $this->assertTrue(
                    $resolves,
                    "Module [{$slug}] registered the menu entry \"{$item->title}\" -> {$item->url}, which does not resolve"
                );
            }
        }
    }

    public function test_deactivating_removes_the_module_menu(): void
    {
        $this->seed(\Database\Seeders\SettingSeeder::class);
        $this->seed(\Database\Seeders\MenuSeeder::class);

        $admin = $this->admin();
        $slug = collect((new ModuleManager)->discover())->pluck('slug')->first();

        Module::updateOrCreate(['slug' => $slug], [
            'name' => $slug, 'version' => '1.0.0', 'is_installed' => true,
        ]);

        $this->actingAs($admin)->post("/admin/modules/{$slug}/activate");
        $this->assertGreaterThan(0, MenuItem::where('module', $slug)->count());

        $this->actingAs($admin)->post("/admin/modules/{$slug}/deactivate");
        $this->assertSame(0, MenuItem::where('module', $slug)->count(), 'Deactivating left the menu behind');
    }

    public function test_an_unknown_module_action_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/modules/blog/explodeEverything')
            ->assertNotFound();
    }

    public function test_module_routes_only_load_when_active(): void
    {
        $slug = collect((new ModuleManager)->discover())->pluck('slug')->first();

        Module::updateOrCreate(['slug' => $slug], [
            'name' => $slug, 'version' => '1.0.0',
            'is_installed' => true, 'is_active' => false,
        ]);

        $live = collect(Route::getRoutes())->filter(
            fn ($r) => str_contains($r->uri(), 'm/'.$slug)
        );

        $this->assertCount(0, $live, 'An inactive module still registered routes');
    }
}
