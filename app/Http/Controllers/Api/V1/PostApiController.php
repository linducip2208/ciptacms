<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Post;
use Illuminate\Http\Request;

class PostApiController extends ApiController
{
    public function index(Request $r)
    {
        $q = Post::with(['category', 'author', 'tags'])->where('status', 'published');

        if ($search = $r->get('search')) {
            $q->where(function ($w) use ($search) {
                $w->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }
        if ($cat = $r->get('category')) {
            $q->whereHas('category', fn ($c) => $c->where('slug', $cat));
        }
        if ($tag = $r->get('tag')) {
            $q->whereHas('tags', fn ($t) => $t->where('slug', $tag));
        }
        if ($from = $r->get('from')) {
            $q->where('published_at', '>=', $from);
        }
        if ($to = $r->get('to')) {
            $q->where('published_at', '<=', $to);
        }

        $sort = $r->get('sort', 'published_at');
        $allowed = ['published_at', 'title', 'views', 'id'];
        $q->orderBy(in_array($sort, $allowed, true) ? $sort : 'published_at', $r->get('dir') === 'asc' ? 'asc' : 'desc');

        $perPage = min(100, max(1, (int) $r->get('per_page', 15)));

        return $this->paginated($q->paginate($perPage));
    }

    public function show(string $slug)
    {
        $post = Post::with(['category', 'author', 'tags', 'seo'])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return $this->data([
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'body' => $post->body,
            'featured_image' => $post->featured_image,
            'views' => $post->views,
            'category' => $post->category ? ['id' => $post->category->id, 'name' => $post->category->name, 'slug' => $post->category->slug] : null,
            'tags' => $post->tags->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'slug' => $t->slug]),
            'author' => $post->author ? ['id' => $post->author->id, 'name' => $post->author->name] : null,
            'published_at' => optional($post->published_at)->toIso8601String(),
            'url' => url('/blog/'.$post->slug),
            'seo' => $post->seo,
        ]);
    }
}
