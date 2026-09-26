<?php

namespace App\Http\Controllers\Admin;

use App\Models\AppearanceOption;
use App\Models\Page;
use App\Models\Widget;
use Illuminate\Http\Request;

/**
 * Appearance options are stored in the appearance_options table rather than
 * settings, because they belong to the active theme and are reset when a
 * different theme is activated.
 */
class AppearanceController extends AdminController
{
    public function header()
    {
        return view('admin.appearance.header', [
            'options' => $this->group('header'),
            'menus' => \App\Models\MenuItem::where('location', 'primary')->orderBy('sort_order')->get(),
        ]);
    }

    public function saveHeader(Request $r)
    {
        $this->saveGroup($r, 'header', [
            'header.sticky' => 'boolean',
            'header.logo' => 'text',
            'header.show_search' => 'boolean',
            'header.cta_label' => 'text',
            'header.cta_url' => 'text',
            'header.height' => 'text',
        ]);

        $this->audit('update_header', null, $r);

        return back()->with('ok', 'Header settings saved');
    }

    public function footer()
    {
        return view('admin.appearance.footer', [
            'options' => $this->group('footer'),
        ]);
    }

    public function saveFooter(Request $r)
    {
        $this->saveGroup($r, 'footer', [
            'footer.copyright' => 'text',
            'footer.show_social' => 'boolean',
            'footer.social_links' => 'text',
            'footer.about' => 'text',
            'footer.branding' => 'text',
        ]);

        $this->audit('update_footer', null, $r);

        return back()->with('ok', 'Footer settings saved');
    }

    public function homepage()
    {
        return view('admin.appearance.homepage', [
            'options' => $this->group('homepage'),
            'pages' => Page::orderBy('title')->get(['id', 'title', 'slug', 'status', 'is_homepage']),
        ]);
    }

    public function saveHomepage(Request $r)
    {
        $pageId = $r->input('options[homepage.page_id]');

        Page::where('is_homepage', true)->when($pageId, fn ($q) => $q->where('id', '!=', $pageId))->update(['is_homepage' => false]);

        if ($pageId) {
            Page::whereKey($pageId)->update(['is_homepage' => true]);
        }

        $this->saveGroup($r, 'homepage', [
            'homepage.hero_title' => 'text',
            'homepage.hero_subtitle' => 'text',
            'homepage.hero_image' => 'text',
            'homepage.show_stats' => 'boolean',
            'homepage.show_testimonials' => 'boolean',
            'homepage.show_clients' => 'boolean',
            'homepage.show_blog' => 'boolean',
        ]);

        $this->audit('update_homepage', null, $r);

        return back()->with('ok', 'Homepage settings saved');
    }

    public function customCode()
    {
        return view('admin.appearance.custom-code', [
            'options' => $this->group('code'),
        ]);
    }

    public function saveCustomCode(Request $r)
    {
        $this->saveGroup($r, 'code', [
            'code.custom_css' => 'text',
            'code.custom_js' => 'text',
            'code.custom_head' => 'text',
            'code.custom_footer' => 'text',
        ]);

        $this->audit('update_custom_code', null, $r);

        return back()->with('ok', 'Custom code saved');
    }

    // ---- Widgets ------------------------------------------------------

    public const WIDGET_TYPES = [
        'recent-posts' => 'Recent posts',
        'search' => 'Search',
        'menu' => 'Menu',
        'text' => 'Rich text',
        'html' => 'Raw HTML',
        'cta' => 'Call to action',
        'newsletter' => 'Newsletter form',
        'social' => 'Social links',
        'contact' => 'Contact details',
    ];

    public function widgets()
    {
        return view('admin.appearance.widgets', [
            'sidebars' => Widget::query()->distinct()->pluck('sidebar')->push('sidebar-1')->unique()->values(),
            'rows' => Widget::orderBy('sidebar')->orderBy('sort_order')->get()->groupBy('sidebar'),
            'types' => self::WIDGET_TYPES,
        ]);
    }

    public function saveWidget(Request $r, $widget = null)
    {
        $data = $r->validate([
            'sidebar' => 'required|string|max:100',
            'type' => 'required|string|max:100',
            'title' => 'nullable|string|max:190',
            'config' => 'nullable',
            'is_visible' => 'nullable|boolean',
        ]);

        if (! array_key_exists($data['type'], self::WIDGET_TYPES)) {
            return back()->withErrors(['msg' => 'Unknown widget type.']);
        }

        $config = $data['config'] ?? [];
        if (is_string($config)) {
            $decoded = json_decode($config, true);
            $config = is_array($decoded) ? $decoded : ['body' => $config];
        }

        $payload = [
            'sidebar' => $data['sidebar'],
            'type' => $data['type'],
            'title' => $data['title'] ?? null,
            'config' => $config,
            'is_visible' => $r->boolean('is_visible', true),
        ];

        if ($widget) {
            $widget->update($payload);
            $msg = 'Widget updated';
        } else {
            $payload['sort_order'] = (int) Widget::where('sidebar', $payload['sidebar'])->max('sort_order') + 1;
            $widget = Widget::create($payload);
            $msg = 'Widget added';
        }

        $this->audit($widget->wasRecentlyCreated ? 'create' : 'update', $widget, $r);

        return back()->with('ok', $msg);
    }

    public function destroyWidget(Request $r, Widget $widget)
    {
        $widget->delete();
        $this->audit('delete', $widget, $r);

        return back()->with('ok', 'Widget removed');
    }

    public function reorderWidgets(Request $r)
    {
        foreach ((array) $r->input('order', []) as $i => $id) {
            Widget::where('id', $id)->update(['sort_order' => $i]);
        }

        return response()->json(['ok' => true]);
    }

    // ---- helpers ------------------------------------------------------

    protected function group(string $name): array
    {
        return AppearanceOption::where('group', $name)->pluck('value', 'key')->all();
    }

    protected function saveGroup(Request $r, string $group, array $types): void
    {
        $posted = (array) $r->input('options', []);

        foreach ($types as $key => $type) {
            if ($type === 'boolean') {
                AppearanceOption::put($group, $key, $r->boolean("options.$key") ? '1' : '0', 'boolean');

                continue;
            }
            if (! array_key_exists($key, $posted)) {
                continue;
            }
            AppearanceOption::put($group, $key, (string) $posted[$key], $type);
        }
    }
}
