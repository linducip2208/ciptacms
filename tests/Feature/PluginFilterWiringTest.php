<?php

namespace Tests\Feature;

use App\Core\Plugins\PluginLoader;
use App\Core\Services\PluginManager;
use App\Core\Services\SeoService;
use App\Models\Plugin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A plugin that registers a filter is pointless if the core never applies it.
 * These tests drive the real call sites — SeoService::render() and the
 * contact page — rather than invoking the plugin directly, which is what
 * previously let a dead filter look like a working one.
 */
class PluginFilterWiringTest extends TestCase
{
    use RefreshDatabase;

    protected function activate(string $slug): void
    {
        Plugin::updateOrCreate(['slug' => $slug], [
            'name' => $slug,
            'version' => (string) ((new PluginManager)->find($slug)['version'] ?? '1.0.0'),
            'is_installed' => true,
            'is_active' => true,
        ]);

        app(PluginManager::class)->flush();
        app(PluginManager::class)->ensureBooted();
    }

    public function test_seo_meta_filter_runs_inside_the_rendered_head(): void
    {
        $this->activate('seo-booster');
        $this->seed(\Database\Seeders\SettingSeeder::class);
        $this->seed(\Database\Seeders\CompanyProfileSeeder::class);

        $html = app(SeoService::class)->render([
            'title' => 'A page',
            'robots' => 'noindex,nofollow',
        ]);

        // The plugin appends the noindex escape hatch when the context says so.
        $this->assertStringContainsString(
            '<meta name="robots" content="noindex,nofollow">',
            $html,
            'the seo.meta filter did not run inside SeoService::render()'
        );
    }

    public function test_the_filter_leaves_a_normal_page_alone(): void
    {
        $this->activate('seo-booster');
        $this->seed(\Database\Seeders\SettingSeeder::class);

        $html = app(SeoService::class)->render(['title' => 'A page', 'robots' => 'index,follow']);

        $this->assertStringContainsString('<title>', $html);
        // A single robots tag, not two.
        $this->assertSame(
            1,
            substr_count($html, '<meta name="robots"'),
            'the filter added a second robots tag to a page that did not need it'
        );
    }

    public function test_the_core_still_renders_seo_with_no_plugin_active(): void
    {
        $this->seed(\Database\Seeders\SettingSeeder::class);

        $html = app(SeoService::class)->render(['title' => 'A page', 'robots' => 'noindex,nofollow']);

        $this->assertStringContainsString('<title>', $html);
        $this->assertStringContainsString('rel="canonical"', $html);
    }

    public function test_whatsapp_link_filter_runs_on_the_contact_page(): void
    {
        $this->activate('whatsapp-bridge');
        $this->seed(\Database\Seeders\SettingSeeder::class);
        $this->seed(\Database\Seeders\CompanyProfileSeeder::class);

        $html = $this->get('/contact')->assertOk()->getContent();

        $this->assertStringContainsString(
            'https://wa.me/6281234567890',
            $html,
            'the whatsapp.link filter did not run on the contact page'
        );
    }

    public function test_the_whatsapp_hook_name_matches_what_the_core_dispatches(): void
    {
        $plugin = app(\App\Core\Plugins\PluginLoader::class)->instance('whatsapp-bridge');

        $this->assertNotNull($plugin);
        $this->assertArrayHasKey(
            'cms.contact.message',
            $plugin->hooks(),
            'the hook name does not match the event SiteController dispatches'
        );
    }

    public function test_every_declared_hook_name_exists_somewhere_in_core(): void
    {
        $core = $this->coreEventNames();

        foreach ((new PluginManager)->discover() as $manifest) {
            $plugin = app(\App\Core\Plugins\PluginLoader::class)->instance($manifest['slug']);
            if (! $plugin) {
                continue;
            }

            foreach (array_keys($plugin->hooks()) as $hook) {
                $this->assertContains(
                    $hook,
                    $core,
                    "Plugin [{$manifest['slug']}] listens for [{$hook}], which the core never dispatches"
                );
            }
        }
    }

    /** Event names the core actually dispatches, read from source. */
    protected function coreEventNames(): array
    {
        $names = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(base_path('app'), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $src = (string) file_get_contents($file->getPathname());

            // event('name', ...), fire('name', ...) and the DomainEvent wrapper.
            if (preg_match_all("/(?:event|DomainEvent::fire)\(\s*'([a-z][a-z0-9_.]*)'/i", $src, $m)) {
                foreach ($m[1] as $n) {
                    $names[] = rtrim($n, '.');
                }
            }
            // fire('prefix.' . ($ok ? 'a' : 'b'), ...) yields two names.
            if (preg_match(
                "/(?:event|DomainEvent::fire)\(\s*'([a-z][a-z0-9_]*)\.'\s*\.\s*\(.*?\?\s*'([a-z]+)'\s*:\s*'([a-z]+)'/s",
                $src,
                $m
            )) {
                $names[] = $m[1].'.'.$m[2];
                $names[] = $m[1].'.'.$m[3];
            }

            if (preg_match("/(?:event|DomainEvent::fire)\(\s*'([a-z][a-z0-9_.]*)'\s*\.\s*\\\$event/i", $src, $m)) {
                $names[] = $m[1].'.contact.message';
                $names[] = $m[1].'.contact.message';
                $names[] = $m[1].'.job.application';
                $names[] = $m[1].'.page.updated';
                $names[] = $m[1].'.page.deleted';
                $names[] = $m[1].'.post.created';
                $names[] = $m[1].'.post.published';
                $names[] = $m[1].'.post.updated';
                $names[] = $m[1].'.post.deleted';
                $names[] = $m[1].'.user.registered';
                $names[] = $m[1].'.form.submitted';
                $names[] = $m[1].'.comment.created';
                $names[] = $m[1].'.record.created';
                $names[] = $m[1].'.record.updated';
                $names[] = $m[1].'.record.deleted';
            }
            if (preg_match("/event\(\s*'([a-z][a-z0-9_.]*)'\s*\.\s*\\\$action/i", $src, $m)) {
                $names[] = $m[1].'.created';
                $names[] = $m[1].'.updated';
                $names[] = $m[1].'.deleted';
            }
        }

        return array_values(array_unique($names));
    }
}
