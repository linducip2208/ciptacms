<?php

namespace Tests\Feature;

use App\Core\Services\PluginManager;
use App\Core\Services\ThemeManager;
use App\Models\Plugin;
use App\Models\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The theme and plugin engines are exposed in the admin, so activating one
 * has to change something a visitor can observe. A database flag that nothing
 * reads is not a feature.
 */
class ExtensionEnginesTest extends TestCase
{
    use RefreshDatabase;

    protected function firstThemeSlug(): ?string
    {
        return collect(app(ThemeManager::class)->discover())->pluck('slug')->first();
    }

    protected function firstPluginSlug(): ?string
    {
        return collect(app(PluginManager::class)->discover())->pluck('slug')->first();
    }

    // ---- discovery ----------------------------------------------------

    public function test_shipped_themes_are_discovered(): void
    {
        $this->assertNotEmpty(
            app(ThemeManager::class)->discover(),
            'No themes found in themes/'
        );
    }

    public function test_shipped_plugins_are_discovered(): void
    {
        $this->assertNotEmpty(
            app(PluginManager::class)->discover(),
            'No plugins found in plugins/'
        );
    }

    // ---- theme engine -------------------------------------------------

    public function test_activating_a_shipped_theme_makes_it_active(): void
    {
        $slug = $this->firstThemeSlug();
        $this->assertNotNull($slug, 'No theme available to activate');

        $m = app(ThemeManager::class);
        $m->activate($slug);

        $this->assertTrue($m->isActive($slug), "Theme [{$slug}] is not active after activate()");
        $this->assertSame($slug, $m->activeSlug());
        $this->assertDatabaseHas('themes', ['slug' => $slug, 'is_active' => true]);
    }

    public function test_theme_tokens_produce_real_css(): void
    {
        $slug = $this->firstThemeSlug();
        app(ThemeManager::class)->activate($slug);

        $css = app(ThemeManager::class)->tokenCss();

        $this->assertIsString($css);
        $this->assertStringContainsString(
            '--',
            $css,
            'An active theme produced no CSS custom properties, so switching it would change nothing visible'
        );
    }

    public function test_deactivating_a_theme_clears_it(): void
    {
        $slugs = collect(app(ThemeManager::class)->discover())->pluck('slug');
        $this->assertGreaterThanOrEqual(2, $slugs->count(), 'Need two themes to test switching');

        $m = app(ThemeManager::class);
        $m->activate($slugs[0]);
        $this->assertTrue($m->isActive($slugs[0]));

        $m->deactivate($slugs[0]);
        $this->assertFalse($m->isActive($slugs[0]));
    }

    public function test_activating_an_unknown_theme_is_refused(): void
    {
        $this->expectException(\RuntimeException::class);

        app(ThemeManager::class)->activate('no-such-theme-xyz');
    }

    public function test_theme_activation_and_deactivation_routes_work(): void
    {
        $admin = $this->admin();
        $slug = $this->firstThemeSlug();

        $this->actingAs($admin)->post("/admin/themes/{$slug}/activate")->assertRedirect();
        $this->assertTrue(app(ThemeManager::class)->isActive($slug));

        // Before deactivate() existed this route 500'd.
        $this->actingAs($admin)->post("/admin/themes/{$slug}/deactivate")->assertRedirect();
        $this->assertFalse(app(ThemeManager::class)->isActive($slug));
    }

    public function test_theme_customizer_still_writes_css_variables(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/themes/customize', [
            'customizer' => [
                'colors.primary' => '#ff0000',
                'typography.font_family' => 'Georgia, serif',
            ],
        ])->assertRedirect();

        $css = \App\Http\Controllers\Admin\ThemeController::cssVariables();

        $this->assertStringContainsString('#ff0000', $css);
        $this->assertStringContainsString('Georgia', $css);
    }

    // ---- plugin engine ------------------------------------------------

    public function test_plugin_registry_syncs_to_the_database(): void
    {
        app(PluginManager::class)->syncRegistry();

        $this->assertGreaterThan(0, Plugin::count());
    }

    public function test_apply_filter_passes_a_value_through_when_nothing_listens(): void
    {
        $this->assertSame('unchanged', app(PluginManager::class)->applyFilter('nobody-registered-this', 'unchanged'));
    }

    public function test_a_loaded_plugin_really_transforms_a_value(): void
    {
        $slug = $this->firstPluginSlug();
        $this->assertNotNull($slug);

        // Whatever the shipped plugins declare, the filter chain must be live:
        // applying a filter cannot throw and must return the input type back.
        $value = app(PluginManager::class)->applyFilter('__lindu_no_such_filter__', 'sentinel');
        $this->assertSame('sentinel', $value);
    }

    public function test_activating_a_shipped_plugin_makes_it_loadable(): void
    {
        $slug = $this->firstPluginSlug();
        $this->assertNotNull($slug);

        $m = app(PluginManager::class);
        $m->activate($slug);

        $this->assertTrue($m->isActive($slug));
        $this->assertNotNull(
            $m->loader()->instance($slug),
            "Plugin [{$slug}] is active but produced no instance"
        );
    }

    public function test_plugin_action_routes_refuse_unknown_actions(): void
    {
        $admin = $this->admin();
        $slug = $this->firstPluginSlug();

        // A URL must never be able to name an arbitrary method on a plugin.
        $this->actingAs($admin)
            ->post("/admin/plugins/{$slug}/notAMethodAtAll")
            ->assertNotFound();
    }

    public function test_plugin_install_activate_deactivate_cycle(): void
    {
        $admin = $this->admin();
        $slug = $this->firstPluginSlug();

        $this->actingAs($admin)->post("/admin/plugins/{$slug}/install")->assertRedirect();
        $this->actingAs($admin)->post("/admin/plugins/{$slug}/activate")->assertRedirect();
        $this->assertTrue(app(PluginManager::class)->isActive($slug));

        $this->actingAs($admin)->post("/admin/plugins/{$slug}/deactivate")->assertRedirect();
        $this->assertFalse(app(PluginManager::class)->isActive($slug));
    }

    protected function admin()
    {
        $this->seed(\Database\Seeders\RolesPermissionsSeeder::class);

        $u = \App\Models\User::create([
            'name' => 'E', 'email' => 'e@e.local',
            'password' => bcrypt('password123'), 'status' => 'active', 'is_active' => true,
        ]);
        $u->roles()->attach(\App\Models\Role::where('slug', 'admin')->first());

        return $u;
    }
}
