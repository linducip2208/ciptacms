<?php

namespace App\Http\Controllers\Admin;

use App\Core\Services\SeoService;
use App\Models\Page;
use App\Models\Post;
use App\Models\SeoMeta;
use App\Models\SeoRedirect;
use Illuminate\Http\Request;

class SeoController extends AdminController
{
    public function index(Request $r)
    {
        $type = $r->get('type');

        $q = SeoMeta::with('seoable')->latest();
        if ($type === 'page') {
            $q->where('seoable_type', Page::class);
        } elseif ($type === 'post') {
            $q->where('seoable_type', Post::class);
        } elseif ($type && $type !== 'all') {
            $q->where('seoable_type', $type);
        }

        $counts = [
            'all' => SeoMeta::count(),
            'page' => SeoMeta::where('seoable_type', Page::class)->count(),
            'post' => SeoMeta::where('seoable_type', Post::class)->count(),
            'other' => SeoMeta::whereNotIn('seoable_type', [Page::class, Post::class])->count(),
        ];

        return view('admin.seo.index', [
            'rows' => $q->paginate(25)->withQueryString(),
            'type' => $type ?? 'all',
            'counts' => $counts,
        ]);
    }

    public function edit(Request $r, string $type, $id)
    {
        $model = $this->resolveModel($type);
        $row = $model::findOrFail($id);
        $meta = $this->seo()->for($model, (string) $row->getKey());

        return view('admin.seo.edit', [
            'type' => $type,
            'row' => $row,
            'meta' => $meta,
            'publicUrl' => $this->publicUrl($model, $row),
        ]);
    }

    public function update(Request $r, string $type, $id)
    {
        $model = $this->resolveModel($type);
        $row = $model::findOrFail($id);

        $data = $r->validate([
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'canonical' => 'nullable|string|max:255',
            'robots' => 'nullable|in:index,follow,noindex,follow,index,nofollow,noindex,nofollow',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:500',
            'og_image' => 'nullable|string|max:255',
            'twitter_card' => 'nullable|in:summary,summary_large_image',
            'schema' => 'nullable|string',
        ]);

        SeoMeta::updateOrCreate(
            ['seoable_type' => $model, 'seoable_id' => $row->getKey()],
            $data
        );

        $this->audit('update_seo', $row, $r);

        return back()->with('ok', 'SEO metadata saved');
    }

    public function sitemap()
    {
        $xml = $this->seo()->sitemap();
        $count = substr_count($xml, '<url>');

        return view('admin.seo.sitemap', [
            'xml' => $xml,
            'count' => $count,
        ]);
    }

    public function robots()
    {
        return view('admin.seo.robots', [
            'robots' => $this->seo()->robotsTxt(),
        ]);
    }

    public function redirects(Request $r)
    {
        $q = SeoRedirect::latest();
        if ($search = $r->get('search')) {
            $q->where(function ($w) use ($search) {
                $w->where('from_path', 'like', "%{$search}%")
                    ->orWhere('to_path', 'like', "%{$search}%");
            });
        }

        return view('admin.seo.redirects', [
            'rows' => $q->paginate(25)->withQueryString(),
        ]);
    }

    public function saveRedirect(Request $r)
    {
        $data = $r->validate([
            'from_path' => 'required|string|max:255',
            'to_path' => 'required|string|max:255',
            'status_code' => 'required|integer|between:301,302,307,308',
            'is_active' => 'nullable|boolean',
        ]);

        $data['from_path'] = '/'.ltrim($data['from_path'], '/');
        $data['to_path'] = $data['to_path'] === '/' ? '/' : '/'.ltrim($data['to_path'], '/');
        $data['is_active'] = $r->boolean('is_active', true);

        SeoRedirect::updateOrCreate(['from_path' => $data['from_path']], $data);
        $this->audit('save_redirect', null, $r);

        return back()->with('ok', 'Redirect saved');
    }

    public function destroyRedirect(Request $r, SeoRedirect $redirect)
    {
        $redirect->delete();
        $this->audit('delete_redirect', $redirect, $r);

        return back()->with('ok', 'Redirect deleted');
    }

    public function schema()
    {
        $service = $this->seo();

        return view('admin.seo.schema', [
            'organization' => $service->organizationSchema(),
            'samples' => [
                'Organization' => $service->organizationSchema(),
                'BreadcrumbList' => $service->breadcrumbSchema([
                    ['name' => 'Home', 'url' => url('/')],
                    ['name' => 'Blog', 'url' => url('/blog')],
                    ['name' => 'Example post'],
                ]),
            ],
        ]);
    }

    protected function seo(): SeoService
    {
        return app(SeoService::class);
    }

    /** Whitelist: only types that carry an SEO relation are editable. */
    protected function resolveModel(string $type): string
    {
        $map = [
            'page' => Page::class,
            'post' => Post::class,
            'service' => \App\Models\Cp\Service::class,
            'product' => \App\Models\Cp\Product::class,
            'portfolio' => \App\Models\Cp\Portfolio::class,
            'career' => \App\Models\Cp\Career::class,
            'gallery' => \App\Models\Cp\GalleryAlbum::class,
        ];

        if (! isset($map[$type])) {
            abort(404, 'Unknown SEO type: '.$type);
        }

        return $map[$type];
    }

    protected function publicUrl(string $model, $row): string
    {
        try {
            return match ($model) {
                Page::class => url('/p/'.$row->slug),
                Post::class => url('/blog/'.$row->slug),
                \App\Models\Cp\Service::class => url('/services/'.$row->slug),
                \App\Models\Cp\Product::class => url('/products/'.$row->slug),
                \App\Models\Cp\Portfolio::class => url('/portfolio/'.$row->slug),
                \App\Models\Cp\Career::class => url('/careers/'.$row->slug),
                \App\Models\Cp\GalleryAlbum::class => url('/gallery/'.$row->slug),
                default => url('/'),
            };
        } catch (\Throwable $e) {
            return url('/');
        }
    }
}
