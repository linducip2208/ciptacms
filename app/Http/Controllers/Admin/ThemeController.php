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

            return back()->with('ok', "Theme '{$slug}' activated");
        } catch (\Throwable $e) {
            return back()->withErrors(['msg' => $e->getMessage()]);
        }
    }

    public function deactivate(string $slug, ThemeManager $m)
    {
        try {
            $m->deactivate($slug);

            return back()->with('ok', "Theme '{$slug}' deactivated");
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
     * `customizer` group so they reset when a different theme is activated.
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

    public function customize()
    {
        $active = Theme::where('is_active', true)->first();

        return view('admin.themes.customize', [
            'groups' => self::CUSTOMIZER,
            'values' => AppearanceOption::where('group', 'customizer')->pluck('value', 'key')->all(),
            'active' => $active,
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

        $this->audit('update_theme_customizer', null, $r);

        return back()->with('ok', "Saved {$saved} customizer value(s). Reload the public site to see them.");
    }

    /** Emitted as a CSS custom-property block on the public site. */
    public static function cssVariables(): string
    {
        $values = AppearanceOption::where('group', 'customizer')->pluck('value', 'key')->all();
        if (! $values) {
            return '';
        }

        $out = [];
        foreach ($values as $key => $value) {
            [$group, $name] = array_pad(explode('.', (string) $key, 2), 2, $key);
            if ($value === null || $value === '') {
                continue;
            }
            if (in_array($name, ['font_family', 'base_size', 'container', 'radius', 'section_padding'], true)) {
                $out[] = "  --lindu-{$name}: {$value};";
            } else {
                $out[] = "  --lindu-{$name}: {$value};";
            }
        }

        return $out ? ":root {\n".implode("\n", $out)."\n}" : '';
    }
}
