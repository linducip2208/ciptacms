@extends('site.layout')
@section('title', $seo['title'] ?? $post->title)

@section('content')
<div class="wrap">
    {!! app(\App\Core\Services\SeoService::class)->breadcrumb([
        ['name' => 'Home', 'url' => route('site.home')],
        ['name' => 'Blog', 'url' => route('site.blog')],
        ['name' => $post->title],
    ]) !!}
</div>

<article style="padding-top:24px">
    <div class="wrap" style="max-width:760px">
        @if($post->category)
            <span class="badge">{{ $post->category->name }}</span>
        @endif
        <h1 class="page-title" style="margin-top:10px">{{ $post->title }}</h1>
        <div style="color:#94a3b8;font-size:.9rem;margin-bottom:22px">
            {{ optional($post->published_at)->format('F j, Y') }}
            @if($post->author) · by {{ $post->author->name }}@endif
            · {{ $post->views }} views
        </div>

        @if($post->featured_image)
            <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" style="width:100%;border-radius:var(--lindu-radius);margin-bottom:26px">
        @endif

        <div class="prose">{!! $post->body !!}</div>

        @if($related->isNotEmpty())
            <h3 style="margin-top:44px">Related reading</h3>
            <div class="grid g3">
                @foreach($related as $r)
                    <div class="card">
                        <b><a href="{{ route('site.post', $r->slug) }}" style="text-decoration:none">{{ $r->title }}</a></b>
                        <div style="color:#94a3b8;font-size:.85rem;margin-top:4px">{{ optional($r->published_at)->format('M j, Y') }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</article>
@endsection
