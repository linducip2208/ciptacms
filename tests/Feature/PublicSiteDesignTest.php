<?php

namespace Tests\Feature;

use Database\Seeders\CompanyProfileSeeder;
use Database\Seeders\FrontendMenuSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The public site is the demo a buyer sees first. These cover the fourteen
 * company-profile pages plus the claims the rework rests on:
 *
 *  - every page renders on one shared design system, not bespoke CSS
 *  - Tabler is actually loaded from the local build, never a CDN
 *  - navigation is driven by the Menu Engine, grouped, never hard-coded
 *  - white-label branding still reaches the markup
 */
class PublicSiteDesignTest extends TestCase
{
    use RefreshDatabase;

    public static function sitePages(): array
    {
        return [
            'home' => ['/'],
            'about' => ['/about'],
            'services' => ['/services'],
            'products' => ['/products'],
            'portfolio' => ['/portfolio'],
            'team' => ['/team'],
            'testimonials' => ['/testimonials'],
            'clients' => ['/clients'],
            'faq' => ['/faq'],
            'gallery' => ['/gallery'],
            'careers' => ['/careers'],
            'blog' => ['/blog'],
            'contact' => ['/contact'],
        ];
    }

    protected function seedSite(): void
    {
        $this->seed(SettingSeeder::class);
        $this->seed(FrontendMenuSeeder::class);
        $this->seed(CompanyProfileSeeder::class);
    }

    /**
     * PHPUnit 12 ignores the @dataProvider annotation, so the attribute is
     * required for the provider to run at all.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('sitePages')]
    public function test_every_public_page_renders(string $url): void
    {
        $this->seedSite();

        $this->get($url)
            ->assertOk()
            ->assertSee('</html>', false);
    }

    public function test_pages_share_one_design_system(): void
    {
        $this->seedSite();

        // The footer, navigation and design-system wrapper are the same
        // partials on every page â€” that is what stops 15 bespoke pages.
        foreach (array_keys(self::sitePages()) as $name) {
            $url = self::sitePages()[$name][0];
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString(
                'class="container-xl',
                $html,
                $name . ' does not use the shared container'
            );
            $this->assertStringContainsString(
                'linduMobileNav',
                $html,
                $name . ' is missing the shared navigation'
            );
            $this->assertStringContainsString(
                'class="footer',
                $html,
                $name . ' is missing the shared footer'
            );
        }
    }

    public function test_tabler_is_loaded_from_the_local_build(): void
    {
        $this->seedSite();

        $html = $this->get('/')->assertOk()->getContent();

        // The compiled bundle, served from our own origin. @vite emits a
        // hashed filename, so match the shape rather than the source name.
        $this->assertMatchesRegularExpression(
            '#href="[^"]*/build/assets/app-[^"]+\.css"#',
            $html,
            'The public site is not loading a compiled stylesheet'
        );

        // Tabler's own JS powers the dropdowns and offcanvas.
        $this->assertMatchesRegularExpression(
            '#/build/assets/tabler-[^"]+\.js#',
            $html,
            'Tabler JS is not being loaded from the local build'
        );

        // No runtime CDN anywhere on a public page.
        $this->assertDoesNotMatchRegularExpression(
            '#(cdn\.jsdelivr\.net|unpkg\.com|cdnjs\.cloudflare\.com)#',
            $html,
            'The public site still depends on a CDN'
        );

        // The bundle that is actually requested must exist on disk.
        preg_match('#/build/assets/(app-[^"]+\.css)#', $html, $m);
        $this->assertFileExists(public_path('build/assets/'.$m[1]));
    }

    public function test_tabler_navigation_markup_is_present(): void
    {
        $this->seedSite();

        $html = $this->get('/')->assertOk()->getContent();

        // Tabler's navbar, dropdown and offcanvas classes, not hand-rolled
        // equivalents.
        $this->assertStringContainsString('class="navbar', $html);
        $this->assertStringContainsString('navbar-toggler', $html);
        $this->assertStringContainsString('offcanvas', $html);
        $this->assertStringContainsString('dropdown-menu', $html, 'No dropdown rendered');
    }

    public function test_navigation_is_grouped_and_comes_from_the_menu_table(): void
    {
        $this->seedSite();

        $html = $this->get('/')->assertOk()->getContent();

        // Seeded groups, rendered as dropdowns.
        foreach (['Company', 'Business', 'Resources'] as $group) {
            $this->assertStringContainsString(
                $group,
                $html,
                'Menu group ' . $group . ' is missing from the navigation'
            );
        }

        // A child item is reachable, proving the tree is being walked.
        $this->assertStringContainsString('About', $html);
        $this->assertStringContainsString('Services', $html);

        // Contact is a CTA, not one more loose link.
        $this->assertStringContainsString('class="btn btn-primary" href="/contact"', $html);
    }

    public function test_navigation_follows_menu_data_not_the_template(): void
    {
        $this->seedSite();

        // Rename a menu item: the page must follow.
        \App\Models\MenuItem::where('title', 'About')->update(['title' => 'Our story']);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Our story', $html);

        // The new title appears as a dropdown link, and the old label is gone
        // from the navigation block.
        $nav = substr($html, strpos($html, '<nav'), strpos($html, '</nav>') - strpos($html, '<nav'));
        $this->assertStringNotContainsString('>About<', $nav);
    }

    public function test_a_nested_menu_item_becomes_a_dropdown(): void
    {
        $this->seedSite();

        $tree = app(\App\Core\Services\MenuService::class)->tree('primary');

        $groups = collect($tree)->filter(fn ($i) => filled($i['children'] ?? []));

        $this->assertGreaterThanOrEqual(3, $groups->count(), 'Menu is not producing dropdown groups');
    }

    public function test_active_state_marks_the_current_page(): void
    {
        $this->seedSite();

        $html = $this->get('/services')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '#nav-link[^"]*active#',
            $html,
            'The current page is not marked active in the navigation'
        );
    }

    public function test_branding_reaches_the_public_markup(): void
    {
        $this->seedSite();

        app(\App\Core\Services\SettingService::class)->set('branding.primary_color', '#ff6600', 'text', 'branding');
        app(\App\Core\Services\SettingService::class)->set('general.site_name', 'Northwind Ltd', 'text', 'general');
        app(\App\Core\Services\SettingService::class)->set('branding.logo', '/storage/brand/logo.svg', 'image', 'branding');

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Northwind Ltd', $html);
        $this->assertStringContainsString('/storage/brand/logo.svg', $html);
        // The token is read by the stylesheet, but the raw setting must be
        // present so the bridge has something to bind.
        $this->assertStringContainsString('#ff6600', $html);
    }

    public function test_design_tokens_are_defined_by_the_stylesheet(): void
    {
        $manifest = File::get(public_path('build/manifest.json'));
        $this->assertStringContainsString('app.css', $manifest);

        // One stylesheet for the whole product.
        $this->assertStringNotContainsString('site.css', $manifest, 'the public site has a second stylesheet again');
        $this->assertStringNotContainsString('css/tabler.css', $manifest, 'tabler is a separate entry again');

        $appCss = File::get(resource_path('css/app.css'));

        // Tabler is imported, not replaced.
        $this->assertStringContainsString("@import './tabler.css';", $appCss);

        // The CMS tokens bridge onto Tabler's own variables.
        foreach (['--tblr-primary', '--tblr-border-radius', '--tblr-font-sans-serif'] as $variable) {
            $this->assertStringContainsString($variable, $appCss, $variable . ' is not bridged');
        }
    }

    public function test_the_compiled_bundle_actually_contains_tabler(): void
    {
        $manifest = json_decode(File::get(public_path('build/manifest.json')), true);
        $file = $manifest['resources/css/app.css']['file'] ?? null;

        $this->assertNotNull($file, 'app.css is not in the build manifest');

        $css = File::get(public_path('build/'.$file));

        // Real Tabler output, not just the token layer.
        foreach (['.navbar', '.dropdown-menu', '.offcanvas', '.form-control', '.accordion'] as $selector) {
            $this->assertStringContainsString($selector, $css, $selector . ' is missing from the bundle');
        }
    }

    public function test_dark_mode_is_supported(): void
    {
        $this->seedSite();

        app(\App\Core\Services\SettingService::class)->set('theme.color_mode', 'dark', 'text', 'theme');

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('data-bs-theme="dark"', $html);
    }

    public function test_block_library_output_inherits_the_design_system(): void
    {
        $this->seedSite();

        $html = \App\Core\Services\BlockLibrary::render([
            'sections' => [[
                'blocks' => [
                    ['type' => 'heading', 'heading' => 'Built on tokens', 'level' => 'h2'],
                    ['type' => 'text', 'text' => '<p>Body copy</p>'],
                ],
            ]],
        ]);

        // The builder emits the same wrapper class the stylesheet targets, so
        // page-builder pages look like the rest of the site.
        $this->assertStringContainsString('lindu-block', $html);
        $this->assertStringContainsString('Built on tokens', $html);
    }

    public function test_page_builder_pages_render_through_the_shared_system(): void
    {
        $this->seedSite();

        \App\Models\Page::create([
            'title' => 'Builder page', 'slug' => 'builder-page',
            'status' => 'published', 'published_at' => now(), 'is_homepage' => true,
            'builder' => ['sections' => [[
                'blocks' => [['type' => 'heading', 'heading' => 'From the builder', 'level' => 'h1']],
            ]]],
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('From the builder', $html);
        $this->assertStringContainsString('lindu-block', $html);
        $this->assertStringContainsString('linduMobileNav', $html);
    }

    public function test_seo_output_survives_the_rework(): void
    {
        $this->seedSite();

        $html = $this->get('/services')->assertOk()->getContent();

        $this->assertStringContainsString('<title>', $html);
        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('rel="canonical"', $html);
    }

    public function test_flash_messages_render_through_the_shared_partial(): void
    {
        $this->seedSite();

        $this->withSession(['ok' => 'Saved successfully']);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('alert-success', $html);
        $this->assertStringContainsString('Saved successfully', $html);
    }

    public function test_validation_errors_render_through_the_shared_partial(): void
    {
        $this->seedSite();

        // Submit the contact form empty, follow the redirect, and confirm the
        // errors surface through the shared flash partial rather than only
        // on the page that produced them.
        $html = $this->followingRedirects()
            ->from(route('site.contact'))
            ->post(route('site.contact.submit'), ['name' => '', 'email' => 'nope', 'message' => ''])
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('alert-danger', $html);
        $this->assertStringContainsString('Please fix the following', $html);
    }

    public function test_forms_still_post_and_render(): void
    {
        $this->seedSite();

        $html = $this->get('/contact')->assertOk()->getContent();

        $this->assertStringContainsString('name="message"', $html);
        $this->assertStringContainsString(route('site.contact.submit'), $html);
        $this->assertStringContainsString('name="_token"', $html);
    }

    public function test_sitemap_and_robots_still_work(): void
    {
        $this->seedSite();

        $this->get('/sitemap.xml')->assertOk()->assertSee('<urlset', false);
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap:', false);
    }
}
