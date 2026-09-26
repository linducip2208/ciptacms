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

        <section id="comments" style="margin-top:48px">
            <h2 class="page-title" style="font-size:1.4rem">Comments ({{ $comments->count() }})</h2>

            @forelse($comments as $c)
                <article class="card" style="margin-top:12px">
                    <div style="display:flex;justify-content:space-between;gap:10px;align-items:baseline;flex-wrap:wrap">
                        <b>{{ $c->author_name }}</b>
                        <time style="color:#94a3b8;font-size:.82rem">{{ $c->created_at?->diffForHumans() }}</time>
                    </div>
                    <p style="margin:8px 0 0;white-space:pre-wrap">{{ $c->body }}</p>
                    <form method="POST" action="{{ route('site.comments.report', $c) }}" class="mt-2">
                        @csrf
                        <input type="hidden" name="reason" value="Reported from the post page">
                        <button class="text-xs" style="background:none;border:0;color:#94a3b8;cursor:pointer;padding:0">
                            Report
                        </button>
                    </form>
                </article>
            @empty
                <p class="text-muted">No comments yet. Be the first.</p>
            @endforelse

            <div class="card" style="margin-top:24px">
                <div class="card-body">
                    <h3 style="margin-top:0">Leave a comment</h3>
                    <form method="POST" action="{{ route('site.comments.store', $post) }}">
                        @csrf
                        {{-- honeypot: real visitors never see this --}}
                        <div style="position:absolute;left:-9999px" aria-hidden="true">
                            <label for="c-website">Website</label>
                            <input type="text" id="c-website" name="website" tabindex="-1" autocomplete="off">
                        </div>
                        @guest
                            <div class="form-row">
                                <label for="c-name">Name *</label>
                                <input id="c-name" name="author_name" value="{{ old('author_name') }}" required>
                            </div>
                            <div class="form-row">
                                <label for="c-email">Email *</label>
                                <input id="c-email" type="email" name="author_email" value="{{ old('author_email') }}" required>
                            </div>
                        @endguest
                        <div class="form-row">
                            <label for="c-body">Comment *</label>
                            <textarea id="c-body" name="body" rows="5" required>{{ old('body') }}</textarea>
                        </div>
                        <button class="btn" type="submit">Post comment</button>
                    </form>
                </div>
            </div>
        </section>
    </div>
</article>
@endsection
