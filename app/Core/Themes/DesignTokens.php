<?php

namespace App\Core\Themes;

/**
 * Design tokens: the values a theme owns, and how they become CSS.
 *
 * A theme declares tokens in its `theme.json` either flat or grouped:
 *
 *   "tokens": { "colors": { "primary": "#0ea5e9" } }
 *   "tokens": { "colors.primary": "#0ea5e9" }
 *
 * Both normalise to the key `colors.primary`, which maps to the CSS custom
 * property the shipped site layout already reads. The core layout hard-codes
 * `--lindu-primary`, `--lindu-secondary`, `--lindu-radius`, `--lindu-container`
 * and `--lindu-section` in its own `:root` block, so the emitter uses a
 * doubled selector to win the cascade regardless of stylesheet order — see
 * ThemeController::cssVariables().
 */
class DesignTokens
{
    /**
     * Token key => the custom property the core layout consumes. Anything not
     * listed falls back to `--lindu-<last key segment>`.
     */
    public const VARIABLES = [
        'settings.primary' => '--lindu-primary',
        'colors.primary' => '--lindu-primary',
        'colors.secondary' => '--lindu-secondary',
        'colors.accent' => '--lindu-accent',
        'colors.body_bg' => '--lindu-body_bg',
        'colors.text' => '--lindu-text',
        'colors.muted' => '--lindu-muted',
        'colors.border' => '--lindu-border',
        'typography.font_family' => '--lindu-font_family',
        'typography.base_size' => '--lindu-base_size',
        'typography.heading_weight' => '--lindu-heading-weight',
        'layout.container' => '--lindu-container',
        'layout.radius' => '--lindu-radius',
        'layout.section_padding' => '--lindu-section',
    ];

    /** Characters that would let a token break out of its declaration. */
    protected const FORBIDDEN = [';', '{', '}', '<', '>', '\\', "\n", "\r", '@import'];

    protected const MAX_VALUE_LENGTH = 240;

    /**
     * Flatten and sanitise a token tree into `key => value` pairs.
     *
     * @param  array<string, mixed>  $tokens
     * @return array<string, string>
     */
    public static function normalise(array $tokens): array
    {
        $flat = [];
        self::flatten($tokens, '', $flat);

        $out = [];
        foreach ($flat as $key => $value) {
            $key = self::normaliseKey((string) $key);
            $value = self::normaliseValue($value);
            if ($key === null || $value === null) {
                continue;
            }
            $out[$key] = $value;
        }

        return $out;
    }

    protected static function flatten(array $tokens, string $prefix, array &$out): void
    {
        foreach ($tokens as $key => $value) {
            if (is_int($key) && $prefix === '') {
                // A bare list of tokens is meaningless without names; ignore it.
                continue;
            }
            $key = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value)) {
                self::flatten($value, $key, $out);

                continue;
            }

            $out[$key] = $value;
        }
    }

    /** Lower-cased dot path of [a-z0-9_-] segments, or null when unusable. */
    protected static function normaliseKey(string $key): ?string
    {
        $segments = [];
        foreach (explode('.', $key) as $segment) {
            $segment = strtolower(trim($segment));
            if (! preg_match('/^[a-z0-9][a-z0-9_-]*$/', $segment)) {
                return null;
            }
            $segments[] = $segment;
        }

        return $segments === [] ? null : implode('.', $segments);
    }

    /** A token value is a single CSS value: no statements, no nesting. */
    protected static function normaliseValue(mixed $value): ?string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '' || strlen($value) > self::MAX_VALUE_LENGTH) {
            return null;
        }

        foreach (self::FORBIDDEN as $needle) {
            if (str_contains($value, $needle)) {
                return null;
            }
        }

        return $value;
    }

    /** The custom property a token key writes to. */
    public static function variable(string $key): string
    {
        if (isset(self::VARIABLES[$key])) {
            return self::VARIABLES[$key];
        }

        $segments = explode('.', $key);
        $last = end($segments);

        return '--lindu-'.preg_replace('/[^a-z0-9_-]/', '', (string) $last);
    }

    /**
     * Render tokens as a `:root` block.
     *
     * @param  array<string, string>  $tokens
     */
    public static function toCss(array $tokens, string $selector = ':root:root'): string
    {
        $declarations = [];

        foreach ($tokens as $key => $value) {
            $declarations[] = '  '.self::variable((string) $key).': '.$value.';';
        }

        return $declarations === [] ? '' : $selector." {\n".implode("\n", $declarations)."\n}\n";
    }

    /**
     * Merge token layers, later layers winning. Empty values are dropped so a
     * blank customizer field falls back to the theme's own token.
     *
     * @param  array<string, array<string, string>>  $layers
     * @return array<string, string>
     */
    public static function merge(array ...$layers): array
    {
        $out = [];

        foreach ($layers as $layer) {
            foreach ($layer as $key => $value) {
                if ($value === '' || $value === null) {
                    unset($out[$key]);

                    continue;
                }
                $out[$key] = (string) $value;
            }
        }

        return $out;
    }
}
