<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Page;
use Illuminate\Http\Request;

class PageApiController extends ApiController
{
    public function index(Request $r)
    {
        $q = Page::published()->with('seo')->orderBy('title');

        if ($search = $r->get('search')) {
            $q->where(function ($w) use ($search) {
                $w->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }
        if ($homepage = $r->boolean('homepage')) {
            $q->homepage();
        }

        $perPage = min(100, max(1, (int) $r->get('per_page', 15)));

        return $this->paginated($q->paginate($perPage));
    }

    public function show(string $slug)
    {
        $page = Page::published()->with('seo')->where('slug', $slug)->firstOrFail();

        return $this->data([
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'excerpt' => $page->excerpt,
            'body' => $page->body,
            'builder' => $page->builder,
            'featured_image' => $page->featured_image,
            'template' => $page->template,
            'is_homepage' => $page->is_homepage,
            'published_at' => optional($page->published_at)->toIso8601String(),
            'url' => $page->url,
            'seo' => $page->seo,
        ]);
    }
}
