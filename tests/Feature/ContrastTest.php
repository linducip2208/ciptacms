<?php

namespace Tests\Feature;

use App\Core\Support\Contrast;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Colour contrast measured, not assumed.
 *
 * The accessibility suite covered alt text, labels and aria. It did not
 * cover the failure a user actually notices: text they cannot read. These
 * assert the real WCAG ratio for the shipped palette and for whatever an
 * operator configures.
 */
class ContrastTest extends TestCase
{
    use RefreshDatabase;

    // ---- the maths itself --------------------------------------------

    public function test_ratio_matches_known_values(): void
    {
        // Reference values from the WCAG definition.
        $this->assertEqualsWithDelta(21.0, Contrast::ratio('#ffffff', '#000000'), 0.01);
        $this->assertEqualsWithDelta(1.0, Contrast::ratio('#ffffff', '#ffffff'), 0.01);
        $this->assertEqualsWithDelta(4.54, Contrast::ratio('#767676', '#ffffff'), 0.02);
        $this->assertEqualsWithDelta(3.03, Contrast::ratio('#949494', '#ffffff'), 0.05);
    }

    public function test_short_hex_and_alpha_are_handled(): void
    {
        $this->assertEqualsWithDelta(
            Contrast::ratio('#000', '#fff'),
            Contrast::ratio('#000000', '#ffffff'),
            0.001
        );
        // An 8-digit hex is the same colour as its 6-digit prefix.
        $this->assertEqualsWithDelta(
            Contrast::ratio('#ff0000', '#ffffff'),
            Contrast::ratio('#ff0000ff', '#ffffff'),
            0.001
        );
    }

    public function test_ratio_is_direction_independent(): void
    {
        $this->assertEqualsWithDelta(
            Contrast::ratio('#1d4ed8', '#ffffff'),
            Contrast::ratio('#ffffff', '#1d4ed8'),
            0.001
        );
    }

    // ---- the shipped palette -----------------------------------------

    /** @return array<string,array{0:string,1:string}> */
    public static function shippedPairs(): array
    {
        return [
            'body text on page background' => ['#0f172a', '#ffffff'],
            'muted text on page background' => ['#64748b', '#ffffff'],
            'primary action on white' => ['#1d4ed8', '#ffffff'],
            'white on primary' => ['#ffffff', '#1d4ed8'],
            'footer text on footer background' => ['rgba(255,255,255,.72)', '#0f172a'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('shippedPairs')]
    public function test_shipped_palette_meets_aa(string $foreground, string $background): void
    {
        // rgba() is not a hex colour; resolve it to its solid equivalent first.
        $foreground = str_contains($foreground, 'rgba')
            ? self::blendOver($foreground, $background)
            : $foreground;

        $ratio = Contrast::ratio($foreground, $background);

        $this->assertGreaterThanOrEqual(
            4.5,
            $ratio,
            sprintf('%s on %s is %.2f:1, below the 4.5:1 AA minimum', $foreground, $background, $ratio)
        );
    }

    public function test_secondary_colour_survives_white_text_on_it(): void
    {
        // The footer and CTA use white on the secondary colour.
        $ratio = Contrast::ratio('#ffffff', '#0f172a');

        $this->assertGreaterThanOrEqual(4.5, $ratio, 'White on the secondary colour is unreadable');
    }

    // ---- what an operator configures ----------------------------------

    public function test_a_bad_operator_colour_is_detectable(): void
    {
        // Pale text on white is the classic unreadable combination, and the
        // point of the checker is to catch exactly this.
        $this->assertFalse(Contrast::aaNormal('#bbbbbb', '#ffffff'));
        $this->assertTrue(Contrast::aaNormal('#767676', '#ffffff'));
    }

    public function test_setting_a_low_contrast_brand_is_flagged(): void
    {
        $this->seed(SettingSeeder::class);

        // An operator picks a brand colour that cannot carry body text.
        app(\App\Core\Services\SettingService::class)
            ->set('branding.text_color', '#ffff00', 'text', 'branding');

        $background = (string) setting('branding.background_color', '#ffffff');
        $text = (string) setting('branding.text_color', '#0f172a');

        $this->assertFalse(
            Contrast::aaNormal($text, $background),
            'The configured brand text colour is unreadable; this is the case the check exists for'
        );
    }

    public function test_the_theme_customizer_does_not_break_contrast(): void
    {
        $this->seed(SettingSeeder::class);

        $css = \App\Http\Controllers\Admin\ThemeController::tokenCss();
        $body = (string) setting('branding.text_color', '#0f172a');
        $page = (string) setting('branding.background_color', '#ffffff');

        $this->assertTrue(Contrast::aaNormal($body, $page), "Tokens emitted:\n".$css);
    }

    public function test_best_picks_the_readable_option(): void
    {
        $this->assertSame('#000000', Contrast::best('#ffffff', '#000000', '#ffff00'));
        $this->assertSame('#ffff00', Contrast::best('#000000', '#000000', '#ffff00'));
    }

    /** Resolve an rgba() foreground against a solid background. */
    protected static function blendOver(string $rgba, string $background): string
    {
        if (! preg_match('/rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)\s*(?:,\s*([\d.]+)\s*)?\)/i', $rgba, $m)) {
            return $rgba;
        }

        $alpha = isset($m[4]) && $m[4] !== '' ? (float) $m[4] : 1.0;
        $bg = [
            hexdec(substr(ltrim($background, '#'), 0, 2)),
            hexdec(substr(ltrim($background, '#'), 2, 2)),
            hexdec(substr(ltrim($background, '#'), 4, 2)),
        ];

        $channels = [];
        foreach ([0, 1, 2] as $i) {
            $channels[] = (int) round(((float) $m[$i + 1]) * $alpha + $bg[$i] * (1 - $alpha));
        }

        return sprintf('#%02x%02x%02x', ...$channels);
    }
}
