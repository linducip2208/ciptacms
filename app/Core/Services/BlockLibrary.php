<?php

namespace App\Core\Services;

use App\Models\Cp\Faq;
use App\Models\Cp\GalleryAlbum;
use App\Models\Cp\Service;
use App\Models\Cp\TeamMember;
use App\Models\Cp\Testimonial;
use App\Models\Form;
use App\Models\Post;

/**
 * Page builder component library.
 *
 * `catalog()` is the single source of truth for what the builder can insert;
 * `render()` turns a saved builder payload into HTML. Adding a component
 * means adding it here and in the same render match — there is no second
 * registry to fall out of sync.
 */
class BlockLibrary
{
    /**
     * Component definitions.
     *
     * fields: which builder properties to show in the inspector
     * defaults: initial values for a freshly dropped component
     */
    public static function catalog(): array
    {
        return [
            [
                'type' => 'heading', 'label' => 'Heading', 'icon' => 'ti ti-heading',
                'group' => 'text',
                'fields' => ['heading' => ['label' => 'Text', 'type' => 'text'], 'level' => ['label' => 'Level', 'type' => 'select', 'options' => ['h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6']]],
                'defaults' => ['heading' => 'Your heading here', 'level' => 'h2'],
            ],
            [
                'type' => 'text', 'label' => 'Text', 'icon' => 'ti ti-align-left', 'group' => 'text',
                'fields' => ['text' => ['label' => 'Body', 'type' => 'richtext']],
                'defaults' => ['text' => 'Write something here.'],
            ],
            [
                'type' => 'image', 'label' => 'Image', 'icon' => 'ti ti-photo', 'group' => 'media',
                'fields' => [
                    'image' => ['label' => 'Image URL', 'type' => 'image'],
                    'alt' => ['label' => 'Alt text', 'type' => 'text'],
                    'caption' => ['label' => 'Caption', 'type' => 'text'],
                ],
                'defaults' => ['image' => '', 'alt' => '', 'caption' => ''],
            ],
            [
                'type' => 'video', 'label' => 'Video', 'icon' => 'ti ti-video', 'group' => 'media',
                'fields' => [
                    'text' => ['label' => 'Video URL or embed', 'type' => 'textarea'],
                    'autoplay' => ['label' => 'Autoplay', 'type' => 'boolean'],
                    'loop' => ['label' => 'Loop', 'type' => 'boolean'],
                ],
                'defaults' => ['text' => '', 'autoplay' => false, 'loop' => false],
            ],
            [
                'type' => 'button', 'label' => 'Button', 'icon' => 'ti ti-cursor', 'group' => 'action',
                'fields' => [
                    'heading' => ['label' => 'Label', 'type' => 'text'],
                    'link' => ['label' => 'URL', 'type' => 'text'],
                    'style' => ['label' => 'Style', 'type' => 'select', 'options' => ['primary' => 'Primary', 'outline' => 'Outline', 'link' => 'Plain link']],
                ],
                'defaults' => ['heading' => 'Click me', 'link' => '#', 'style' => 'primary'],
            ],
            [
                'type' => 'icon', 'label' => 'Icon', 'icon' => 'ti ti-star', 'group' => 'text',
                'fields' => ['heading' => ['label' => 'Character', 'type' => 'text'], 'text' => ['label' => 'Label', 'type' => 'text']],
                'defaults' => ['heading' => '★', 'text' => ''],
            ],
            [
                'type' => 'card', 'label' => 'Card', 'icon' => 'ti ti-box', 'group' => 'layout',
                'fields' => [
                    'heading' => ['label' => 'Title', 'type' => 'text'],
                    'text' => ['label' => 'Body', 'type' => 'richtext'],
                    'image' => ['label' => 'Image URL', 'type' => 'image'],
                    'link' => ['label' => 'Link', 'type' => 'text'],
                ],
                'defaults' => ['heading' => 'Card title', 'text' => 'Card body'],
            ],
            [
                'type' => 'grid', 'label' => 'Grid', 'icon' => 'ti ti-layout-grid', 'group' => 'layout',
                'fields' => [
                    'columns' => ['label' => 'Columns', 'type' => 'select', 'options' => [2 => 2, 3 => 3, 4 => 4]],
                    'items' => ['label' => 'Items ("Title|Text" per line)', 'type' => 'lines'],
                ],
                'defaults' => ['columns' => 3, 'items' => []],
            ],
            [
                'type' => 'gallery', 'label' => 'Gallery', 'icon' => 'ti ti-photo-plus', 'group' => 'media',
                'fields' => ['album' => ['label' => 'Album (slug)', 'type' => 'text'], 'columns' => ['label' => 'Columns', 'type' => 'number']],
                'defaults' => ['album' => '', 'columns' => 3],
            ],
            [
                'type' => 'slider', 'label' => 'Slider', 'icon' => 'ti ti-carousel', 'group' => 'media',
                'fields' => [
                    'heading' => ['label' => 'Autoplay (ms, 0 = off)', 'type' => 'number'],
                    'items' => ['label' => 'Images (one URL per line)', 'type' => 'lines'],
                ],
                'defaults' => ['autoplay' => 0, 'items' => []],
            ],
            [
                'type' => 'tabs', 'label' => 'Tabs', 'icon' => 'ti ti-tabs', 'group' => 'interactive',
                'fields' => ['items' => ['label' => 'Tabs (one "label|text" per line)', 'type' => 'lines']],
                'defaults' => ['items' => []],
            ],
            [
                'type' => 'accordion', 'label' => 'Accordion', 'icon' => 'ti ti-chevron-down', 'group' => 'interactive',
                'fields' => ['items' => ['label' => 'Rows (one "question|answer" per line)', 'type' => 'lines']],
                'defaults' => ['items' => []],
            ],
            [
                'type' => 'testimonials', 'label' => 'Testimonials', 'icon' => 'ti ti-star', 'group' => 'dynamic',
                'fields' => ['limit' => ['label' => 'How many', 'type' => 'number']],
                'defaults' => ['limit' => 6],
            ],
            [
                'type' => 'pricing', 'label' => 'Pricing', 'icon' => 'ti ti-coin', 'group' => 'static',
                'fields' => [
                    'items' => ['label' => 'Plans ("name|price|features…" per line)', 'type' => 'lines'],
                    'currency' => ['label' => 'Currency symbol', 'type' => 'text'],
                ],
                'defaults' => ['items' => [], 'currency' => 'Rp'],
            ],
            [
                'type' => 'team', 'label' => 'Team', 'icon' => 'ti ti-users', 'group' => 'dynamic',
                'fields' => ['limit' => ['label' => 'How many', 'type' => 'number']],
                'defaults' => ['limit' => 8],
            ],
            [
                'type' => 'contact', 'label' => 'Contact', 'icon' => 'ti ti-address-book', 'group' => 'dynamic',
                'fields' => ['show_form' => ['label' => 'Show contact form', 'type' => 'boolean']],
                'defaults' => ['show_form' => true],
            ],
            [
                'type' => 'map', 'label' => 'Map', 'icon' => 'ti ti-map-2', 'group' => 'media',
                'fields' => ['text' => ['label' => 'Embed code or Google Maps URL', 'type' => 'textarea']],
                'defaults' => ['text' => ''],
            ],
            [
                'type' => 'form', 'label' => 'Form', 'icon' => 'ti ti-form', 'group' => 'dynamic',
                'fields' => ['form' => ['label' => 'Form (slug)', 'type' => 'text']],
                'defaults' => ['form' => ''],
            ],
            [
                'type' => 'html', 'label' => 'HTML', 'icon' => 'ti ti-code', 'group' => 'raw',
                'fields' => ['text' => ['label' => 'HTML', 'type' => 'code']],
                'defaults' => ['text' => ''],
            ],
            [
                'type' => 'code', 'label' => 'Code', 'icon' => 'ti ti-code', 'group' => 'text',
                'fields' => ['text' => ['label' => 'Code', 'type' => 'code'], 'language' => ['label' => 'Language', 'type' => 'text']],
                'defaults' => ['text' => '', 'language' => 'php'],
            ],
            [
                'type' => 'dynamic', 'label' => 'Dynamic content', 'icon' => 'ti ti-database', 'group' => 'dynamic',
                'fields' => [
                    'source' => [
                        'label' => 'Source', 'type' => 'select',
                        'options' => [
                            'latest_posts' => 'Latest posts', 'services' => 'Services',
                            'team' => 'Team', 'testimonials' => 'Testimonials',
                            'clients' => 'Clients', 'faq' => 'FAQ', 'portfolio' => 'Portfolio',
                        ],
                    ],
                    'limit' => ['label' => 'How many', 'type' => 'number'],
                ],
                'defaults' => ['source' => 'latest_posts', 'limit' => 3],
            ],
        ];
    }

    /** Component groups shown in the builder palette. */
    public static function sections(): array
    {
        $groups = [];
        foreach (self::catalog() as $component) {
            $groups[$component['group']][] = $component['label'];
        }

        return array_map(
            fn ($items, $key) => ['key' => $key, 'label' => ucfirst($key), 'count' => count($items)],
            $groups,
            array_keys($groups)
        );
    }

    /** Flat list kept for backwards compatibility. */
    public static function all(): array
    {
        return array_map(fn ($c) => [
            'type' => $c['type'],
            'label' => $c['label'],
            'defaults' => $c['defaults'],
        ], self::catalog());
    }

    public static function component(string $type): ?array
    {
        foreach (self::catalog() as $c) {
            if ($c['type'] === $type) {
                return $c;
            }
        }

        return null;
    }

    // ==================================================================
    // Rendering
    // ==================================================================

    /**
     * @param  array  $builder  ['sections' => [['blocks' => [['type'=>…], …]], …]]
     * @return string
     */
    public static function render(array $builder): string
    {
        $out = '';

        foreach ((array) ($builder['sections'] ?? []) as $section) {
            $section = (array) $section;
            $out .= self::renderSection($section);
        }

        return $out;
    }

    protected static function renderSection(array $section): string
    {
        $classes = trim('lindu-section '.(string) ($section['class'] ?? ''));
        $style = self::style([
            'padding' => $section['padding'] ?? null,
            'background' => $section['background'] ?? null,
            'text_align' => $section['align'] ?? null,
        ]);

        // Visibility per breakpoint.
        $hide = [];
        foreach (['desktop', 'tablet', 'mobile'] as $bp) {
            if (($section['hide_'.$bp] ?? false) === true || ($section['hide_'.$bp] ?? null) === '1') {
                $hide[] = $bp;
            }
        }
        $media = '';
        if (in_array('mobile', $hide, true)) {
            $media .= '@media(max-width:600px){.sec-'.self::id().'{display:none}}';
        }

        $inner = '';
        foreach ((array) ($section['blocks'] ?? []) as $block) {
            $inner .= self::renderBlock((array) $block);
        }

        return '<section class="'.$classes.'" style="'.$style.$media.'">'
            .'<div class="container-xl">'.$inner.'</div>'
            .'</section>';
    }

    protected static function renderBlock(array $b): string
    {
        $type = (string) ($b['type'] ?? 'text');

        $html = match ($type) {
            'heading' => self::heading($b),
            'text' => self::richtext($b['text'] ?? ''),
            'image' => self::image($b),
            'video' => self::video($b),
            'button' => self::button($b),
            'icon' => self::icon($b),
            'card' => self::card($b),
            'grid' => self::grid($b),
            'gallery' => self::gallery($b),
            'slider' => self::slider($b),
            'tabs' => self::tabs($b),
            'accordion' => self::accordion($b),
            'testimonials' => self::testimonials($b),
            'pricing' => self::pricing($b),
            'team' => self::team($b),
            'contact' => self::contact($b),
            'map' => self::map($b),
            'form' => self::form($b),
            'html' => (string) ($b['text'] ?? ''),
            'code' => self::code($b),
            'dynamic' => self::dynamic($b),
            default => '',
        };

        if ($html === '') {
            return '';
        }

        $style = self::style([
            'padding' => $b['padding'] ?? null,
            'margin' => $b['margin'] ?? null,
            'background' => $b['background'] ?? null,
            'text_align' => $b['align'] ?? null,
        ]);
        $extra = (string) ($b['class'] ?? '');
        $class = 'lindu-block lindu-'.$type.($extra !== '' ? ' '.preg_replace('/[^a-z0-9\-_ ]/i', '', $extra) : '');

        return '<div class="'.$class.'" style="'.$style.'">'.$html.'</div>';
    }

    // ---- individual components -----------------------------------------

    protected static function heading(array $b): string
    {
        $level = preg_match('/^h[1-6]$/i', (string) ($b['level'] ?? '')) ? strtolower($b['level']) : 'h2';
        $text = e((string) ($b['heading'] ?? ''));

        return "<{$level} style=\"color:var(--lindu-secondary,#0f172a);margin:.4em 0\">{$text}</{$level}>";
    }

    protected static function richtext($html): string
    {
        return '<div class="prose">'.(string) $html.'</div>';
    }

    protected static function image(array $b): string
    {
        $src = (string) ($b['image'] ?? '');
        if ($src === '') {
            return '<div class="image-placeholder" style="border:1px dashed #cbd5e1;padding:32px;text-align:center;color:#94a3b8;border-radius:var(--lindu-radius)">No image selected</div>';
        }

        $out = '<img src="'.e($src).'" alt="'.e((string) ($b['alt'] ?? '')).'" loading="lazy" '
            .'style="width:100%;height:auto;border-radius:var(--lindu-radius)">';

        if (! empty($b['caption'])) {
            $out .= '<p style="text-align:center;color:#64748b;font-size:.875rem;margin-top:6px">'.e((string) $b['caption']).'</p>';
        }

        return $out;
    }

    protected static function video(array $b): string
    {
        $url = trim((string) ($b['text'] ?? ''));
        if ($url === '') {
            return '<div class="image-placeholder" style="border:1px dashed #cbd5e1;padding:32px;text-align:center;color:#94a3b8">No video set</div>';
        }

        // Already an embed?
        if (stripos($url, '<iframe') !== false) {
            return $url;
        }

        $autoplay = ! empty($b['autoplay']) ? ' autoplay muted playsinline' : '';
        $loop = ! empty($b['loop']) ? ' loop' : '';

        return '<video controls'.$autoplay.$loop.' style="width:100%;border-radius:var(--lindu-radius)">'
            .'<source src="'.e($url).'">Your browser cannot play this video.</video>';
    }

    protected static function button(array $b): string
    {
        $label = e((string) ($b['heading'] ?? 'Click'));
        $url = e((string) ($b['link'] ?? '#'));
        $style = (string) ($b['style'] ?? 'primary');

        if ($style === 'link') {
            return '<a href="'.$url.'" style="text-decoration:none">'.$label.'</a>';
        }
        if ($style === 'outline') {
            return '<a href="'.$url.'" class="btn btn-outline" style="background:transparent;color:var(--lindu-primary);border:1px solid var(--lindu-primary)">'.$label.'</a>';
        }

        return '<a href="'.$url.'" class="btn" style="background:var(--lindu-primary);color:#fff;padding:11px 20px;border-radius:var(--lindu-radius);text-decoration:none;display:inline-block">'.$label.'</a>';
    }

    protected static function icon(array $b): string
    {
        $glyph = e((string) ($b['heading'] ?? '★'));
        $label = e((string) ($b['text'] ?? ''));

        return '<div style="text-align:center">'
            .'<div style="font-size:2.4rem;color:var(--lindu-primary);line-height:1.2">'.$glyph.'</div>'
            .($label ? '<div style="margin-top:4px">'.$label.'</div>' : '')
            .'</div>';
    }

    protected static function card(array $b): string
    {
        $html = '<div class="card" style="border:1px solid var(--lindu-border,#e5e7eb);border-radius:var(--lindu-radius);overflow:hidden;height:100%">';
        if (! empty($b['image'])) {
            $html .= '<img src="'.e((string) $b['image']).'" alt="" style="width:100%;height:160px;object-fit:cover;display:block">';
        }
        $html .= '<div style="padding:20px">';
        if (! empty($b['heading'])) {
            $html .= '<h3 style="margin:0 0 6px;color:var(--lindu-secondary,#0f172a)">'.e((string) $b['heading']).'</h3>';
        }
        if (! empty($b['text'])) {
            $html .= '<div class="prose" style="color:#475569">'.(string) $b['text'].'</div>';
        }
        if (! empty($b['link'])) {
            $html .= '<p style="margin:12px 0 0"><a href="'.e((string) $b['link']).'">Read more →</a></p>';
        }
        $html .= '</div></div>';

        return $html;
    }

    /**
     * A column grid.
     *
     * Previously this opened a wrapper and implicitly "owned" the blocks that
     * followed it, so it never closed its own <div> and every page using it
     * shipped broken markup. It is now a self-contained component with its own
     * items, like every other card-shaped component.
     */
    protected static function grid(array $b): string
    {
        $cols = max(1, min(6, (int) ($b['columns'] ?? 3)));
        $items = self::lines($b['items'] ?? []);

        if ($items === []) {
            return '<div class="lindu-auto-grid" style="grid-template-columns:repeat('.$cols.',minmax(0,1fr))"></div>';
        }

        $html = '<div class="lindu-auto-grid" style="grid-template-columns:repeat('
            .$cols.',minmax(0,1fr))">';

        foreach ($items as $item) {
            [$title, $body] = array_pad(explode('|', $item, 2), 2, '');
            $html .= '<div class="card h-100"><div class="card-body">'
                .'<h3 class="h5 mb-1">'.e(trim($title)).'</h3>'
                .($body !== '' ? '<p class="text-secondary mb-0">'.e(trim($body)).'</p>' : '')
                .'</div></div>';
        }

        return $html.'</div>';
    }

    protected static function gallery(array $b): string
    {
        $album = (string) ($b['album'] ?? '');
        $cols = max(1, (int) ($b['columns'] ?? 3));

        try {
            $query = GalleryAlbum::published()->where('status', 'published')->with('images');
            $album ? $query->where('slug', $album) : null;
            $images = $query->first()?->images ?? collect();
        } catch (\Throwable $e) {
            $images = collect();
        }

        if ($images->isEmpty()) {
            return '<p class="text-muted">Gallery: no images found'.($album ? " for album '{$album}'" : '').'.</p>';
        }

        $html = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px">';
        foreach ($images as $img) {
            $html .= '<img src="'.e((string) $img->path).'" alt="'.e((string) $img->caption).'" loading="lazy" '
                .'style="width:100%;height:150px;object-fit:cover;border-radius:var(--lindu-radius)">';
        }

        return $html.'</div>';
    }

    protected static function slider(array $b): string
    {
        $items = self::lines($b['items'] ?? []);
        if ($items === []) {
            return '<p class="text-muted">Slider: add image URLs in the inspector.</p>';
        }

        $html = '<div class="lindu-slider" style="display:flex;gap:12px;overflow-x:auto;scroll-snap-type:x mandatory">';
        foreach ($items as $url) {
            $html .= '<img src="'.e($url).'" alt="" loading="lazy" '
                .'style="min-width:280px;height:190px;object-fit:cover;border-radius:var(--lindu-radius);scroll-snap-align:start">';
        }

        return $html.'</div>';
    }

    protected static function tabs(array $b): string
    {
        $items = self::pairs($b['items'] ?? []);
        if ($items === []) {
            return '<p class="text-muted">Tabs: add "label|content" lines in the inspector.</p>';
        }

        $id = 'tabs-'.substr(md5(implode('|', array_keys($items))), 0, 8);
        $html = '<div class="lindu-tabs" id="'.$id.'">';
        $html .= '<div style="display:flex;gap:4px;border-bottom:1px solid var(--lindu-border,#e5e7eb);margin-bottom:12px">';
        $first = true;
        foreach (array_keys($items) as $label) {
            $html .= '<button data-tab="'.e($label).'" onclick="linduTab(\''.$id.'\',this)" '
                .'style="border:0;background:'.($first ? 'var(--lindu-primary,#1d4ed8);color:#fff' : 'transparent;color:#475569')
                .';padding:9px 16px;border-radius:6px 6px 0 0;cursor:pointer;font:inherit">'.e($label).'</button>';
            $first = false;
        }
        $html .= '</div>';

        $first = true;
        foreach ($items as $label => $content) {
            $html .= '<div class="lindu-tab-panel" data-panel="'.e($label).'" style="'.($first ? '' : 'display:none;').'">'
                .self::richtext($content).'</div>';
            $first = false;
        }

        return $html.'</div>';
    }

    protected static function accordion(array $b): string
    {
        $items = self::pairs($b['items'] ?? []);
        if ($items === []) {
            return '<p class="text-muted">Accordion: add "question|answer" lines in the inspector.</p>';
        }

        $html = '';
        foreach ($items as $q => $a) {
            $html .= '<details style="border:1px solid var(--lindu-border,#e5e7eb);border-radius:var(--lindu-radius);padding:12px 16px;margin-bottom:8px">'
                .'<summary style="cursor:pointer;font-weight:600">'.e($q).'</summary>'
                .'<div class="prose" style="margin-top:10px;color:#475569">'.nl2br(e($a)).'</div></details>';
        }

        return $html;
    }

    protected static function testimonials(array $b): string
    {
        try {
            $rows = Testimonial::published()->ordered()->limit((int) ($b['limit'] ?? 6))->get();
        } catch (\Throwable $e) {
            $rows = collect();
        }

        if ($rows->isEmpty()) {
            return '<p class="text-muted">Testimonials: none published yet.</p>';
        }

        $html = '<div class="lindu-auto-grid" style="--min:260px">';
        foreach ($rows as $t) {
            $html .= '<div class="card" style="border:1px solid var(--lindu-border,#e5e7eb);border-radius:var(--lindu-radius);padding:20px">'
                .'<div style="color:#f59e0b">'.str_repeat('★', max(0, min(5, (int) $t->rating))).'</div>'
                .'<p>"'.e($t->testimonial).'"</p>'
                .'<b>'.e($t->customer).'</b>'
                .($t->company ? '<div style="color:#64748b;font-size:.875rem">'.e($t->company).'</div>' : '')
                .'</div>';
        }

        return $html.'</div>';
    }

    protected static function pricing(array $b): string
    {
        $items = self::lines($b['items'] ?? []);
        if ($items === []) {
            return '<p class="text-muted">Pricing: add "Name|price|feature;feature" lines in the inspector.</p>';
        }

        $currency = e((string) ($b['currency'] ?? 'Rp'));
        $html = '<div class="lindu-auto-grid" style="--min:240px">';

        foreach ($items as $line) {
            $parts = array_pad(explode('|', $line, 3), 3, '');
            [$name, $price, $features] = $parts;
            $html .= '<div class="card" style="border:1px solid var(--lindu-border,#e5e7eb);border-radius:var(--lindu-radius);padding:24px;text-align:center">'
                .'<h3 style="margin:0 0 6px">'.e($name).'</h3>'
                .'<div style="font-size:1.8rem;font-weight:700;color:var(--lindu-primary)">'.$currency.e($price).'</div>'
                .'<ul style="list-style:none;padding:0;margin:16px 0 0;display:grid;gap:6px;color:#475569;text-align:left">';
            foreach (array_filter(array_map('trim', explode(';', $features))) as $f) {
                $html .= '<li>✓ '.e($f).'</li>';
            }
            $html .= '</ul></div>';
        }

        return $html.'</div>';
    }

    protected static function team(array $b): string
    {
        try {
            $rows = TeamMember::published()->ordered()->limit((int) ($b['limit'] ?? 8))->get();
        } catch (\Throwable $e) {
            $rows = collect();
        }

        if ($rows->isEmpty()) {
            return '<p class="text-muted">Team: no members published yet.</p>';
        }

        $html = '<div class="lindu-auto-grid" style="--min:200px;text-align:center">';
        foreach ($rows as $m) {
            $photo = $m->photo
                ? '<img src="'.e($m->photo).'" alt="'.e($m->name).'" style="width:110px;height:110px;object-fit:cover;border-radius:50%;margin:0 auto 10px" loading="lazy">'
                : '<div style="width:110px;height:110px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-size:2rem;color:#64748b;margin:0 auto 10px">'
                    .e(mb_substr((string) $m->name, 0, 1)).'</div>';

            $html .= '<div>'.$photo
                .'<b>'.e($m->name).'</b>'
                .'<div style="color:var(--lindu-primary);font-size:.9rem">'.e((string) $m->position).'</div></div>';
        }

        return $html.'</div>';
    }

    protected static function contact(array $b): string
    {
        $info = app(CompanyProfileService::class)->contact();

        $html = '<div class="card" style="border:1px solid var(--lindu-border,#e5e7eb);border-radius:var(--lindu-radius);padding:22px">';
        foreach ([
            'contact.address' => 'Address',
            'contact.phone' => 'Phone',
            'contact.email' => 'Email',
            'contact.business_hours' => 'Hours',
        ] as $key => $label) {
            if (! empty($info[$key])) {
                $html .= '<p style="margin:0 0 8px"><b>'.e($label).':</b> '.nl2br(e((string) $info[$key])).'</p>';
            }
        }

        if (! empty($info['contact.map_embed'])) {
            $html .= '<div style="margin-top:12px">'.$info['contact.map_embed'].'</div>';
        }

        $html .= '</div>';

        if (! empty($b['show_form'])) {
            $html .= '<p style="margin-top:14px"><a class="btn" style="background:var(--lindu-primary);color:#fff;padding:11px 20px;border-radius:var(--lindu-radius);text-decoration:none" href="'.e(route('site.contact')).'">Send a message</a></p>';
        }

        return $html;
    }

    protected static function map(array $b): string
    {
        $embed = trim((string) ($b['text'] ?? ''));
        if ($embed === '') {
            return '<p class="text-muted">Map: paste a Google Maps embed in the inspector.</p>';
        }

        if (stripos($embed, '<iframe') !== false) {
            return '<div style="border-radius:var(--lindu-radius);overflow:hidden">'.$embed.'</div>';
        }

        return '<iframe src="'.e($embed).'" style="width:100%;height:340px;border:0;border-radius:var(--lindu-radius)" loading="lazy"></iframe>';
    }

    protected static function form(array $b): string
    {
        $slug = (string) ($b['form'] ?? '');
        if ($slug === '') {
            return '<p class="text-muted">Form: enter a form slug in the inspector.</p>';
        }

        try {
            $form = Form::with(['fields' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
                ->where('slug', $slug)->where('status', 'published')->first();
        } catch (\Throwable $e) {
            $form = null;
        }

        if (! $form) {
            return '<p class="text-muted">No published form with slug "'.e($slug).'".</p>';
        }

        return app(FormRenderer::class)->render($form);
    }

    protected static function code(array $b): string
    {
        $code = (string) ($b['text'] ?? '');
        if (trim($code) === '') {
            return '';
        }

        return '<pre style="background:#0f172a;color:#e2e8f0;padding:18px;border-radius:var(--lindu-radius);overflow:auto"><code>'
            .e($code).'</code></pre>';
    }

    protected static function dynamic(array $b): string
    {
        $source = (string) ($b['source'] ?? 'latest_posts');
        $limit = (int) ($b['limit'] ?? 3);

        try {
            return match ($source) {
                'latest_posts' => self::listOf(
                    Post::where('status', 'published')->latest('published_at')->limit($limit)->get()
                        ->map(fn ($p) => ['title' => $p->title, 'url' => url('/blog/'.$p->slug), 'excerpt' => (string) $p->excerpt]),
                    'No posts published yet.'
                ),
                'services' => self::listOf(
                    Service::published()->ordered()->limit($limit)->get()
                        ->map(fn ($s) => ['title' => $s->title, 'url' => url('/services/'.$s->slug), 'excerpt' => (string) $s->excerpt]),
                    'No services published yet.'
                ),
                'team' => self::team($b),
                'testimonials' => self::testimonials($b),
                'clients' => self::listOf(
                    \App\Models\Cp\Client::published()->ordered()->limit($limit)->get()
                        ->map(fn ($c) => ['title' => $c->name, 'url' => (string) $c->website, 'excerpt' => '']),
                    'No clients listed yet.'
                ),
                'portfolio' => self::listOf(
                    \App\Models\Cp\Portfolio::published()->ordered()->limit($limit)->get()
                        ->map(fn ($p) => ['title' => $p->title, 'url' => url('/portfolio/'.$p->slug), 'excerpt' => (string) $p->excerpt]),
                    'No portfolio items yet.'
                ),
                'faq' => self::accordion([
                    'items' => Faq::published()->ordered()->get()
                        ->map(fn ($f) => $f->question.'|'.$f->answer)->all(),
                ]),
                default => '<p class="text-muted">Unknown dynamic source.</p>',
            };
        } catch (\Throwable $e) {
            return '<p class="text-muted">Dynamic content unavailable.</p>';
        }
    }

    protected static function listOf($rows, string $empty): string
    {
        if ($rows->isEmpty()) {
            return '<p class="text-muted">'.e($empty).'</p>';
        }

        $html = '<div class="lindu-auto-grid" style="--min:260px">';
        foreach ($rows as $r) {
            $html .= '<div class="card" style="border:1px solid var(--lindu-border,#e5e7eb);border-radius:var(--lindu-radius);padding:20px">'
                .'<h3 style="margin:0 0 6px"><a href="'.e((string) $r['url']).'" style="text-decoration:none">'.e((string) $r['title']).'</a></h3>'
                .($r['excerpt'] ? '<p style="color:#64748b;margin:0">'.e(\Illuminate\Support\Str::limit((string) $r['excerpt'], 110)).'</p>' : '')
                .'</div>';
        }

        return $html.'</div>';
    }

    // ---- helpers ------------------------------------------------------

    protected static function style(array $props): string
    {
        $css = [];

        if (! empty($props['padding'])) {
            $css[] = 'padding:'.self::length((string) $props['padding']);
        }
        if (! empty($props['margin'])) {
            $css[] = 'margin:'.self::length((string) $props['margin']);
        }
        if (! empty($props['background'])) {
            $css[] = 'background:'.self::colour((string) $props['background']);
        }
        if (! empty($props['text_align'])) {
            $align = in_array($props['text_align'], ['left', 'center', 'right', 'justify'], true) ? $props['text_align'] : 'left';
            $css[] = 'text-align:'.$align;
        }

        return implode(';', $css);
    }

    /** Only allow a length value; never a raw url() or expression. */
    protected static function length(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^-?\d+(\.\d+)?(px|rem|em|%|vh|vw|pt)$/', $value)) {
            return $value;
        }

        return preg_match('/^\d+$/', $value) ? $value.'px' : '0';
    }

    protected static function colour(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^#[0-9a-f]{3,8}$/i', $value) || preg_match('/^[a-z]+$/i', $value)) {
            return $value;
        }
        if (str_starts_with($value, 'var(--')) {
            return $value;
        }

        return 'transparent';
    }

    /** Inspector textarea "one per line" → array. */
    public static function lines($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value)));
        }
        if (! $value) {
            return [];
        }
        $decoded = json_decode((string) $value, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('trim', $decoded)));
        }

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $value))));
    }

    /** Inspector "label|value" lines → ['label' => 'value', …]. */
    public static function pairs($value): array
    {
        $out = [];
        foreach (self::lines($value) as $line) {
            if (! str_contains($line, '|')) {
                continue;
            }
            [$k, $v] = array_pad(explode('|', $line, 2), 2, '');
            $k = trim($k);
            if ($k !== '') {
                $out[$k] = trim($v);
            }
        }

        return $out;
    }

    protected static function id(): string
    {
        static $n = 0;

        return 'lindu-sec-'.(++$n);
    }
}
