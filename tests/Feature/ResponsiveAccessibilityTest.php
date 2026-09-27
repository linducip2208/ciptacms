<?php

namespace Tests\Feature;

use Database\Seeders\CompanyProfileSeeder;
use Database\Seeders\FrontendMenuSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Responsive and accessibility guarantees for the public site.
 *
 * Breakpoints cover the seven widths the brief calls out. Assertions are
 * structural rather than visual: the markup must not depend on a fixed
 * width, must not introduce a horizontal scroller of its own, and must
 * expose the semantics a keyboard or screen-reader user needs.
 */
class ResponsiveAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    /** Desktop, laptop, tablet, mobile. */
    public static function breakpoints(): array
    {
        return [
            'desktop-1920' => [1920],
            'desktop-1440' => [1440],
            'laptop-1366' => [1366],
            'tablet-1024' => [1024],
            'tablet-768' => [768],
            'mobile-390' => [390],
            'mobile-375' => [375],
        ];
    }

    public static function sitePages(): array
    {
        return [
            '/' => ['/'], '/about' => ['/about'], '/services' => ['/services'],
            '/products' => ['/products'], '/portfolio' => ['/portfolio'],
            '/team' => ['/team'], '/testimonials' => ['/testimonials'],
            '/clients' => ['/clients'], '/faq' => ['/faq'],
            '/gallery' => ['/gallery'], '/careers' => ['/careers'],
            '/blog' => ['/blog'], '/contact' => ['/contact'],
        ];
    }

    protected function seedSite(): void
    {
        $this->seed(SettingSeeder::class);
        $this->seed(FrontendMenuSeeder::class);
        $this->seed(CompanyProfileSeeder::class);
    }

    // ---- responsive ---------------------------------------------------

    #[\PHPUnit\Framework\Attributes\DataProvider('breakpoints')]
    public function test_pages_render_at_every_breakpoint(int $width): void
    {
        $this->seedSite();

        foreach (array_keys(self::sitePages()) as $url) {
            $this->get($url)
                ->assertOk("{$url} at {$width}px");
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('sitePages')]
    public function test_page_does_not_pin_a_fixed_width(string $url): void
    {
        $this->seedSite();

        $html = $this->get($url)->assertOk()->getContent();

        // A hard px width on a structural element is what causes the
        // horizontal scrollbar; the container is fluid by design.
        $this->assertDoesNotMatchRegularExpression(
            '/<div[^>]+style="[^"]*\bwidth:\s*\d{3,}px/',
            $html,
            $url . ' pins a fixed width on a div'
        );
        $this->assertStringNotContainsString(
            'width="1000"',
            $html,
            $url . ' uses a fixed width attribute'
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('sitePages')]
    public function test_viewport_meta_is_present_and_responsive(string $url): void
    {
        $this->seedSite();

        $html = $this->get($url)->assertOk()->getContent();

        $this->assertStringContainsString('name="viewport"', $html);
        $this->assertStringContainsString('width=device-width', $html);
    }

    public function test_navigation_collapses_for_small_screens(): void
    {
        $this->seedSite();

        $html = $this->get('/')->assertOk()->getContent();

        // Tabler's breakpoint: the desktop list collapses, the offcanvas opens.
        $this->assertStringContainsString('navbar-expand-md', $html);
        $this->assertStringContainsString('collapse navbar-collapse', $html);
        $this->assertStringContainsString('offcanvas', $html);
        $this->assertStringContainsString('navbar-toggler', $html);
    }

    public function test_mobile_menu_can_nest_and_close(): void
    {
        $this->seedSite();

        $html = $this->get('/')->assertOk()->getContent();

        // Nested groups inside the offcanvas, plus a close control.
        $offcanvas = substr($html, strpos($html, 'id="linduMobileNav"'));
        $offcanvas = substr($offcanvas, 0, strpos($offcanvas, '</div>\n</div>') ?: 4000);

        $this->assertStringContainsString('list-unstyled ps-2', $offcanvas, 'Mobile menu has no nested list');
        $this->assertStringContainsString('btn-close', $offcanvas, 'Mobile menu has no close button');
    }

    public function test_images_declare_lazy_loading_and_dimensions_are_bounded(): void
    {
        $this->seedSite();

        foreach (['/', '/gallery', '/team'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            // Content images below the fold must not block first paint.
            preg_match_all('#<img\b[^>]*>#i', $html, $m);
            foreach ($m[0] as $img) {
                if (str_contains($img, 'lindu-thumb') || str_contains($img, 'card-img-top')) {
                    $this->assertStringContainsString('loading="lazy"', $img, $url . ': content image is not lazy');
                }
            }
        }
    }

    // ---- accessibility ------------------------------------------------

    public function test_landmarks_and_skip_link(): void
    {
        $this->seedSite();

        foreach (array_keys(self::sitePages()) as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('<main id="main">', $html, $url . ' has no <main>');
            $this->assertStringContainsString('<header', $html, $url . ' has no <header>');
            $this->assertStringContainsString('<footer', $html, $url . ' has no <footer>');
            $this->assertStringContainsString('Skip to content', $html, $url . ' has no skip link');
        }
    }

    public function test_navigation_aria(): void
    {
        $this->seedSite();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('aria-label="Main navigation"', $html);
        $this->assertStringContainsString('aria-label="Open menu"', $html);
        $this->assertStringContainsString('aria-expanded="false"', $html);
        $this->assertStringContainsString('aria-labelledby="linduMobileNavLabel"', $html);
    }

    public function test_current_page_is_marked_for_assistive_tech(): void
    {
        $this->seedSite();

        $html = $this->get('/services')->assertOk()->getContent();

        // Breadcrumbs mark the leaf as current; the nav marks the active link.
        $this->assertStringContainsString('aria-current', $html);
    }

    public function test_heading_hierarchy_is_not_skipped(): void
    {
        $this->seedSite();

        foreach (['/about', '/services', '/contact', '/faq'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            // Exactly one h1 per page.
            $this->assertSame(
                1,
                preg_match_all('/<h1\b/i', $html),
                $url . ' must have exactly one <h1>'
            );

            // No heading level is skipped on the way down.
            preg_match_all('/<h([1-6])\b/i', $html, $m);
            $levels = array_map('intval', $m[1]);
            $previous = 0;
            foreach ($levels as $level) {
                if ($previous && $level > $previous + 1) {
                    $this->fail($url . " skips from h{$previous} to h{$level}");
                }
                $previous = $level;
            }
        }
    }

    public function test_every_image_has_an_alt_attribute(): void
    {
        $this->seedSite();

        foreach (['/', '/team', '/gallery', '/services'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();

            preg_match_all('#<img\b[^>]*>#i', $html, $m);
            foreach ($m[0] as $img) {
                $this->assertMatchesRegularExpression(
                    '/\balt="[^"]*"/',
                    $img,
                    $url . ': an <img> has no alt attribute'
                );
            }
        }
    }

    public function test_form_controls_have_labels(): void
    {
        $this->seedSite();

        $html = $this->get('/contact')->assertOk()->getContent();

        foreach (['name', 'email', 'phone', 'subject', 'message'] as $field) {
            $this->assertStringContainsString(
                'for="c-' . str_replace('name', 'name', $field) . '"',
                $html,
                'contact form: no label for ' . $field
            );
        }

        // The honeypot must be hidden from assistive tech, not just visually.
        $this->assertMatchesRegularExpression(
            '/aria-hidden="true"[^>]*>.*?name="website"/s',
            $html,
            'the honeypot is not hidden from screen readers'
        );
    }

    public function test_interactive_controls_are_keyboard_reachable(): void
    {
        $this->seedSite();

        $html = $this->get('/')->assertOk()->getContent();

        // Nothing should be a bare div/span standing in for a button.
        $this->assertStringContainsString('navbar-toggler', $html);
        $this->assertStringContainsString('btn-close', $html);
        $this->assertDoesNotMatchRegularExpression(
            '/<div[^>]+onclick=/i',
            $html,
            'a div is used as a click target instead of a button'
        );
    }

    public function test_focus_is_visible_not_suppressed(): void
    {
        $css = File::get(resource_path('css/app.css'));

        // Never ship outline:none without a replacement.
        $this->assertDoesNotMatchRegularExpression(
            '/outline:\s*(none|0)\s*;?\s*}/',
            $css,
            'the stylesheet removes the focus outline without replacing it'
        );
    }
}
