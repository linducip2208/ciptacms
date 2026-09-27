<?php

namespace App\Http\Controllers\Admin;

use App\Core\Services\ThemeManager;
use App\Models\AppearanceOption;
use App\Models\Theme;
use Illuminate\Http\Request;

class ThemeController extends AdminController
{
    public function index(ThemeManager $m)
    {
        $m->syncRegistry();

        return view('admin.themes.index', [
            'themes' => Theme::orderBy('name')->get(),
        ]);
    }

    public function activate(string $slug, ThemeManager $m)
    {
        try {
            $m->activate($slug);

            $manifest = $m->find($slug);
            $notes = [];
            if (! empty($manifest['tokens']) || ! empty($manifest['settings'])) {
                $notes[] = 'its colours and typography are now live';
            }
            if ($m->hasViewOverrides()) {
                $notes[] = 'its layout now replaces the shipped site layout';
            }

            $message = "Theme '{$slug}' activated";
            if ($notes) {
                $message .= ' — '.implode(' and ', $notes);
            }

            return back()->with('ok', $message);
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
    }

    public function deactivate(string $slug, ThemeManager $m)
    {
        try {
            $m->deactivate($slug);

            return back()->with('ok', "Theme '{$slug}' deactivated — the site is back to the default layout and colours");
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
    }

    public function settings(Request $r)
    {
        $posted = (array) $r->input('theme', []);

        foreach ($posted as $key => $value) {
            $type = $r->boolean("theme.{$key}.__bool") ? 'boolean' : 'text';
            app(\App\Core\Services\SettingService::class)->set('theme.'.$key, $value, $type, 'branding');
        }

        $this->audit('update_theme_settings', null, $r);

        return back()->with('ok', 'Theme settings saved');
    }

    /**
     * Theme customizer. Values live in appearance_options under the
     * `customizer` group and are applied *over* the active theme's own design
     * tokens, so they survive a theme switch: switching themes changes the
     * base layer, and these overrides keep winning until they are cleared.
     */
    public const CUSTOMIZER = [
        'colors' => [
            'label' => 'Colours',
            'fields' => [
                ['primary', 'Primary colour', 'color', '#1d4ed8'],
                ['secondary', 'Secondary colour', 'color', '#0f172a'],
                ['accent', 'Accent colour', 'color', '#f59e0b'],
                ['body_bg', 'Page background', 'color', '#ffffff'],
                ['text', 'Body text', 'color', '#0f172a'],
                ['muted', 'Muted text', 'color', '#64748b'],
                ['border', 'Border colour', 'color', '#e5e7eb'],
            ],
        ],
        'typography' => [
            'label' => 'Typography',
            'fields' => [
                ['font_family', 'Font family', 'text', 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif'],
                ['base_size', 'Base font size', 'text', '16px'],
                ['heading_weight', 'Heading weight', 'select', '700', ['400' => '400', '500' => '500', '600' => '600', '700' => '700', '800' => '800']],
            ],
        ],
        'layout' => [
            'label' => 'Layout',
            'fields' => [
                ['container', 'Container width', 'text', '1180px'],
                ['radius', 'Corner radius', 'text', '12px'],
                ['section_padding', 'Section padding', 'text', '64px'],
                ['sticky_header', 'Sticky header', 'bool', true],
            ],
        ],
    ];

    /**
     * Every CMS-driven design token, as a CSS custom-property block.
     *
     * Emitted after the stylesheet, so these win over the compiled-in
     * defaults. This is the only place a live install's colours, fonts and
     * spacing come from: nothing in the templates hard-codes them.
     */
    public static function tokenCss(): string
    {
        $color = static function (string $key, string $fallback): string {
            $value = trim((string) setting($key, ''));
            if ($value === '') {
                return $fallback;
            }
            // Only accept a literal colour, never an arbitrary expression.
            return preg_match('/^(#[0-9a-f]{3,8}|[a-z]+|rgb(a)?\([\d\s.,%]+\)|hsl(a)?\([\d\s.,%]+\))$/i', $value)
                ? $value
                : $fallback;
        };

        $length = static function (string $key, string $fallback): string {
            $value = trim((string) setting($key, ''));

            return preg_match('/^-?\d+(\.\d+)?(px|rem|em|%|vh|vw)$/', $value) ? $value : $fallback;
        };

        $customizer = AppearanceOption::where('group', 'customizer')->pluck('value', 'key')->all();

        $tokens = [
            '--lindu-primary' => $color('branding.primary_color', '#1d4ed8'),
            '--lindu-secondary' => $color('branding.secondary_color', '#0f172a'),
            '--lindu-accent' => $color('branding.accent_color', '#f59e0b'),
            '--lindu-background' => $color('branding.background_color', '#ffffff'),
            '--lindu-surface' => $color('branding.surface_color', '#f8fafc'),
            '--lindu-text' => $color('branding.text_color', '#0f172a'),
            '--lindu-muted' => $color('branding.muted_color', '#64748b'),
            '--lindu-border' => $color('branding.border_color', '#e5e7eb'),
            '--lindu-radius' => $length('branding.radius', '12px'),
            '--lindu-container' => $length('branding.container_width', '1180px'),
            '--lindu-section' => $length('branding.section_spacing', '64px'),
            '--lindu-header-height' => $length('branding.header_height', '68px'),
            '--lindu-footer-background' => $color('branding.footer_background', 'var(--lindu-secondary)'),
            '--lindu-footer-text' => $color('branding.footer_text', 'rgba(255,255,255,.72)'),
            '--lindu-font-family' => (string) (setting('branding.font_family') ?: 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif'),
            '--lindu-heading-font-family' => (string) (setting('branding.heading_font_family') ?: 'var(--lindu-font-family)'),
            '--lindu-heading-weight' => (string) (setting('branding.heading_weight') ?: '700'),
            '--lindu-button-weight' => (string) (setting('branding.button_weight') ?: '600'),
        ];

        $out = [];
        foreach ($tokens as $name => $value) {
            $out[] = "  {$name}: {$value};";
        }

        // Tabler font-size scale, when the operator tuned it.
        foreach (['base' => '16px', 'h1' => '', 'h2' => '', 'h3' => ''] as $key => $default) {
            $value = $length('branding.font_size_'.$key, $default);
            if ($value !== '') {
                $out[] = "  --lindu-font-size-{$key}: {$value};";
            }
        }

        $body = $out ? ":root {\n".implode("\n", $out)."\n}" : '';

        if ($customizer) {
            $extra = [];
            foreach ($customizer as $key => $value) {
                $name = 'lindu-customizer-'.preg_replace('/[^a-z0-9-]/i', '-', (string) $key);
                $extra[] = "  --{$name}: {$value};";
            }
            $body .= ($body ? "\n" : '').":root {\n".implode("\n", $extra)."\n}";
        }

        return $body;
    }

    /**
     * The colour mode the public site should render in.
     *
     * Returns 'dark', 'light' or an empty string. Empty means "no opinion",
     * which leaves the stylesheet free to follow prefers-color-scheme.
     */
    public static function siteColorMode(): string
    {
        $mode = strtolower((string) setting('theme.color_mode', 'auto'));

        return in_array($mode, ['dark', 'light'], true) ? $mode : '';
    }

    public function customize()
    {
        $active = Theme::where('is_active', true)->first();
        $tokens = [];

        try {
            $tokens = app(ThemeManager::class)->tokens();
        } catch (\Throwable $e) {
            report($e);
        }

        return view('admin.themes.customize', [
            'groups' => self::CUSTOMIZER,
            'values' => AppearanceOption::where('group', 'customizer')->pluck('value', 'key')->all(),
            'active' => $active,
            // What the active theme contributes before these overrides apply.
            'tokens' => $tokens,
        ]);
    }

    public function saveCustomize(Request $r)
    {
        $posted = (array) $r->input('customizer', []);
        $saved = 0;

        foreach (self::CUSTOMIZER as $group => $def) {
            foreach ($def['fields'] as [$key, $label, $type, $default]) {
                $full = $group.'.'.$key;
                if ($type === 'bool') {
                    AppearanceOption::put('customizer', $full, $r->boolean("customizer.$full") ? '1' : '0', 'boolean');
                    $saved++;

                    continue;
                }
                if (! array_key_exists($full, $posted)) {
                    continue;
                }
                AppearanceOption::put('customizer', $full, (string) $posted[$full], $type);
                $saved++;
            }
        }

        // ThemeManager caches the active slug for the request; drop it so a
        // value saved now is visible to the very next render.
        try {
            app(ThemeManager::class)->reset();
        } catch (\Throwable $e) {
            report($e);
        }

        $this->audit('update_theme_customizer', null, $r);

        return back()->with('ok', "Saved {$saved} customizer value(s). Reload the public site to see them.");
    }

    /**
     * Emitted as a CSS custom-property block on the public site.
     *
     * The active theme's design tokens come first and the customizer's saved
     * values are merged over them, so activation and customisation are the
     * same code path. The selector is doubled (`:root:root`) because the
     * shipped site layout hard-codes --lindu-primary, --lindu-secondary,
     * --lindu-radius, --lindu-container and --lindu-section in a later
     * `:root` block; a doubled selector wins that tie without the layout
     * needing to know this block exists.
     */
    public static function cssVariables(): string
    {
        try {
            return app(ThemeManager::class)->tokenCss();
        } catch (\Throwable $e) {
            report($e);

            return '';
        }
    }
}
