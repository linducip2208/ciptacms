<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Tabler and Tailwind ship colliding class names.
 *
 * Tailwind v4's rules live in `@layer utilities`, which beats Tabler's
 * unlayered components whenever they share a name. The `collapse` collision
 * hid the admin sidebar and the public navbar: elements were laid out with a
 * real bounding box and still painted nothing, which no markup assertion
 * could catch. This test exists so that class of bug cannot return.
 */
class TailwindTablerCollisionTest extends TestCase
{
    protected function builtCss(): string
    {
        $manifest = json_decode(File::get(public_path('build/manifest.json')), true);
        $file = $manifest['resources/css/app.css']['file'] ?? null;

        $this->assertNotNull($file, 'app.css is missing from the build; run npm run build');

        return File::get(public_path('build/'.$file));
    }

    /**
     * Tabler components that must remain visible. Each of these was, at some
     * point, shadowed by a Tailwind utility of the same name.
     *
     * A data provider must yield an array of arguments per case, so each
     * value is ['purpose'], not a bare string — otherwise PHPUnit reports
     * "expected array but got string" and poisons the exit code of every
     * filtered run.
     */
    public static function criticalComponents(): array
    {
        $components = [
            'navbar' => 'top navigation',
            'collapse' => 'the JS collapse component (navbar, sidebar, accordions)',
            'dropdown-menu' => 'navigation dropdowns',
            'offcanvas' => 'the mobile menu',
            'card' => 'content cards',
            'accordion' => 'FAQ and careers accordions',
            'modal' => 'admin modals',
        ];

        // Each value must be the full argument list for the test method, and
        // the class is the first argument. A bare string here makes PHPUnit
        // report "expected array but got string" and poisons the exit code of
        // every filtered run.
        $out = [];
        foreach ($components as $class => $purpose) {
            $out[$class] = [$class, $purpose];
        }

        return $out;
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('criticalComponents')]
    public function test_component_is_not_visibility_hidden(string $class, string $purpose): void
    {
        $css = $this->builtCss();

        $this->assertStringContainsString(
            '.'.$class,
            $css,
            $class.' ('.$purpose.') is missing from the bundle entirely'
        );

        // Shadow detection is deliberately NOT done with a whole-bundle
        // regex here: Tabler ships its own `visibility:hidden` rules on
        // purpose (an offcanvas is hidden until .show). Scanning everything
        // cannot tell a legitimate component rule from a utility shadowing
        // it. test_no_other_component_is_hidden_by_a_utility isolates the
        // Tailwind utilities layer and does that job correctly.
    }

    public function test_collapse_is_explicitly_restored(): void
    {
        $css = $this->builtCss();

        // The documented, deliberate override must still be present.
        $this->assertMatchesRegularExpression(
            '/\.collapse\{[^}]*visibility:\s*visible/',
            $css,
            'the Tabler .collapse override was removed; the navigation will be invisible again'
        );
    }

    public function test_utility_layer_is_declared_before_our_overrides(): void
    {
        $css = $this->builtCss();

        $utilities = strpos($css, '@layer utilities');
        $override = strpos($css, '.collapse{visibility:visible}');

        $this->assertIsInt($utilities, 'Tailwind utilities layer not found in the bundle');
        $this->assertIsInt($override, 'the .collapse override is missing');
        $this->assertGreaterThan($utilities, $override, 'the override must come after the utilities layer');
    }

    /**
     * Sweep for the same hazard elsewhere: a class that both frameworks
     * define, where the Tailwind one changes something invisible.
     */
    public function test_no_other_component_is_hidden_by_a_utility(): void
    {
        $css = $this->builtCss();

        $utilitiesAt = strpos($css, '@layer utilities');
        $this->assertIsInt($utilitiesAt);

        // Isolate the utilities layer and look for visibility rules on class
        // names that Tabler also defines as components.
        $open = strpos($css, '{', $utilitiesAt);
        $depth = 0;
        $end = null;
        for ($i = $open; $i < strlen($css); $i++) {
            if ($css[$i] === '{') {
                $depth++;
            } elseif ($css[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    $end = $i;
                    break;
                }
            }
        }

        $this->assertIsInt($end, 'could not isolate the Tailwind utilities layer');

        $utilities = substr($css, $open, $end - $open);

        preg_match_all('/\.([a-z0-9-]+)\{[^}]*visibility:\s*(?:hidden|collapse)/i', $utilities, $m);

        $tablerComponents = array_keys(self::criticalComponents());
        /**
         * Collisions that exist and are handled by an explicit unlayered
         * override in app.css, with the reason. Adding a name here without
         * adding the matching override in app.css is the bug this guards
         * against, so the pair must stay in step.
         */
        $handled = ['collapse'];

        $colliding = array_values(array_diff(
            array_intersect(array_unique($m[1]), $tablerComponents),
            $handled
        ));

        $this->assertSame(
            [],
            $colliding,
            'Tailwind utilities hide Tabler components and no override is declared: '.implode(', ', $colliding)
        );

        // Each acknowledged collision must really have its override.
        foreach ($handled as $class) {
            $this->assertMatchesRegularExpression(
                '/\.'.preg_quote($class, '/').'\{[^}]*visibility:\s*visible/',
                $css,
                $class.' is listed as handled but the override is missing from app.css'
            );
        }
    }
}
