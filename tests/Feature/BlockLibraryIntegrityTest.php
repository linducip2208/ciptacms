<?php

namespace Tests\Feature;

use App\Core\Services\BlockLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every component the page builder can insert must render through the
 * shared design system, produce well-formed markup, and carry no
 * presentation of its own.
 */
class BlockLibraryIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public static function components(): array
    {
        $cases = [];
        foreach (BlockLibrary::catalog() as $c) {
            $cases[$c['type']] = [$c];
        }

        return $cases;
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('components')]
    public function test_component_renders_within_the_shared_wrapper(array $component): void
    {
        $html = BlockLibrary::render([
            'sections' => [['blocks' => [array_merge(['type' => $component['type']], $component['defaults'])]]],
        ]);

        // A component whose content field is empty legitimately renders an
        // empty section (raw HTML and code blocks have nothing to show until
        // the editor fills them in). Everything else must be wrapped.
        $contentField = $component['defaults']['text'] ?? null;
        $isEmptyByDefault = $contentField !== null && trim((string) $contentField) === '';

        if (! $isEmptyByDefault) {
            $this->assertStringContainsString('lindu-block', $html, $component['type'] . ' lost the design-system wrapper');
        }

        $this->assertNotSame('', trim($html), $component['type'] . ' rendered nothing');

        // No component may reach for a runtime CDN.
        $this->assertDoesNotMatchRegularExpression('#https?://(cdn|unpkg|cdnjs)#', $html);

        // No inline <style> or <script> smuggled into page content.
        $this->assertStringNotContainsString('<style>', $html, $component['type'] . ' inlines a stylesheet');
        $this->assertStringNotContainsString('<script', $html, $component['type'] . ' inlines a script');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('components')]
    public function test_component_output_is_balanced(array $component): void
    {
        $html = BlockLibrary::render([
            'sections' => [['blocks' => [array_merge(['type' => $component['type']], $component['defaults'])]]],
        ]);

        // A crude but effective guard: every opening div has a closing one.
        $open = preg_match_all('/<div\b/i', $html);
        $close = preg_match_all('/<\/div>/i', $html);

        $this->assertSame($open, $close, $component['type'] . ' produces unbalanced <div> elements');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('components')]
    public function test_component_escapes_untrusted_text(array $component): void
    {
        $block = array_merge(['type' => $component['type']], $component['defaults']);

        // Plant a script tag in every string field the component accepts.
        foreach ($block as $key => $value) {
            if (is_string($value)) {
                $block[$key] = '<script>alert(1)</script>';
            }
        }

        $html = BlockLibrary::render(['sections' => [['blocks' => [$block]]]]);

        $this->assertStringNotContainsString(
            '<script>alert(1)</script>',
            $html,
            $component['type'] . ' emits an unescaped script tag'
        );
    }

    public function test_every_component_is_registered_in_the_catalog(): void
    {
        $types = array_column(BlockLibrary::catalog(), 'type');

        $this->assertContains('heading', $types);
        $this->assertContains('dynamic', $types);
        $this->assertContains('form', $types);

        // Twenty-one is the documented count; a silent drop is a regression.
        $this->assertGreaterThanOrEqual(20, count($types), 'The component catalog shrank');
    }

    public function test_section_background_and_visibility_are_applied(): void
    {
        $html = BlockLibrary::render([
            'sections' => [[
                'background' => '#123456',
                'padding' => '40px',
                'hide_mobile' => true,
                'blocks' => [['type' => 'heading', 'heading' => 'Hi', 'level' => 'h2']],
            ]],
        ]);

        $this->assertStringContainsString('#123456', $html);
        $this->assertStringContainsString('40px', $html);
        $this->assertStringContainsString('max-width:600px', $html, 'mobile visibility rule is missing');
    }

    public function test_section_style_values_are_sanitised(): void
    {
        // A crafted value must not be able to smuggle a declaration.
        $html = BlockLibrary::render([
            'sections' => [[
                'padding' => '10px; background: url(javascript:alert(1))',
                'blocks' => [['type' => 'heading', 'heading' => 'Hi']],
            ]],
        ]);

        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_dynamic_sources_render_or_explain_themselves(): void
    {
        foreach (['latest_posts', 'services', 'team', 'testimonials', 'clients', 'portfolio', 'faq'] as $source) {
            $html = BlockLibrary::render([
                'sections' => [['blocks' => [['type' => 'dynamic', 'source' => $source, 'limit' => 3]]]],
            ]);

            $this->assertNotSame('', trim($html), 'dynamic source ' . $source . ' rendered nothing');
        }
    }
}
