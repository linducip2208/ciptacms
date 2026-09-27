<?php

namespace App\Core\Support;

/**
 * WCAG 2.1 contrast maths.
 *
 * Accessibility checks that only look for alt text and aria attributes miss
 * the failure users actually notice: text that cannot be read. This computes
 * the real contrast ratio so the design tokens can be tested rather than
 * assumed.
 *
 * @see https://www.w3.org/WAI/WCAG21/Understanding/contrast-minimum.html
 */
class Contrast
{
    /**
     * Relative luminance, 0 (black) to 1 (white).
     */
    public static function luminance(string $hex): float
    {
        [$r, $g, $b] = self::channels($hex);

        return (0.2126 * self::linearise($r)) + (0.7152 * self::linearise($g)) + (0.0722 * self::linearise($b));
    }

    /**
     * Contrast ratio between two colours, 1 to 21.
     */
    public static function ratio(string $foreground, string $background): float
    {
        $a = self::luminance($foreground);
        $b = self::luminance($background);

        $lighter = max($a, $b);
        $darker = min($a, $b);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * AA for normal text is 4.5:1; AA for large text (18.66px bold or 24px)
     * and UI components is 3:1.
     */
    public static function passes(string $foreground, string $background, bool $large = false): bool
    {
        return self::ratio($foreground, $background) >= ($large ? 3.0 : 4.5);
    }

    public static function aaNormal(string $foreground, string $background): bool
    {
        return self::ratio($foreground, $background) >= 4.5;
    }

    public static function aaLarge(string $foreground, string $background): bool
    {
        return self::ratio($foreground, $background) >= 3.0;
    }

    /** Pick whichever of two options contrasts better against a background. */
    public static function best(string $background, string $a, string $b): string
    {
        return self::ratio($a, $background) >= self::ratio($b, $background) ? $a : $b;
    }

    /** @return array{0:float,1:float,2:float} */
    protected static function channels(string $hex): array
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) === 8) {
            $hex = substr($hex, 0, 6); // ignore alpha
        }

        if (! preg_match('/^[0-9a-f]{6}$/i', $hex)) {
            // Not a hex colour: treat as opaque black rather than guessing.
            return [0.0, 0.0, 0.0];
        }

        return [
            hexdec(substr($hex, 0, 2)) / 255,
            hexdec(substr($hex, 2, 2)) / 255,
            hexdec(substr($hex, 4, 2)) / 255,
        ];
    }

    protected static function linearise(float $channel): float
    {
        return $channel <= 0.03928
            ? $channel / 12.92
            : (($channel + 0.055) / 1.055) ** 2.4;
    }
}
