@extends('site.layout')
@section('title', ($seo['title'] ?? $post->title) . ' — ' . setting('general.site_name', 'Lindu CMS'))
@section('description', $seo['description'] ?? \Illuminate\Support\Str::limit(strip_tags((string) $post->excerpt), 160))

@section('content')
    <div class="border-bottom">
        <div class="container-xl py-3">
            <x-site.breadcrumbs :items="[
                ['label' => 'Home', 'url' => route('site.home')],
                ['label' => 'Blog', 'url' => route('site.blog')],
                ['label' => $post->title],
            ]" />
        </div>
    </div>

    <article>
        <header class="lindu-section lindu-section--tight">
            <div class="container-xl">
                <div class="row justify-content-center">
                    <div class="col-lg-9 text-center">
                        @if ($post->category)
                            <a href="{{ route('site.blog') }}" class="badge bg-blue-lt mb-2">{{ $post->category->name }}</a>
                        @endif
                        <h1 class="mb-3">{{ $post->title }}</h1>
                        <div class="text-secondary small">
                            {{ optional($post->published_at)->format('j F Y') }}
                            @if ($post->author) · {{ $post->author->name }} @endif
                            · {{ number_format($post->views) }} view{{ $post->views === 1 ? '' : 's' }}
                        </div>
                    </div>
                </div>
            </div>
        </header>

        @if ($post->featured_image)
            <div class="container-xl">
                <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="rounded w-100 mb-4" style="max-height:28rem;object-fit:cover">
            </div>
        @endif

        <div class="container-xl mb-5">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="lindu-prose">{!! $post->body !!}</div>

                    @if ($related->isNotEmpty())
                        <hr class="my-5">
                        <h2 class="h4 mb-3">Keep reading</h2>
                        <div class="row g-3">
                            @foreach ($related as $r)
                                <div class="col-md-4">
                                    <a href="{{ route('site.post', $r->slug) }}" class="text-decoration-none">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h3 class="h6 mb-1">{{ $r->title }}</h3>
                                                <span class="text-secondary small">
                                                    {{ optional($r->published_at)->format('j M Y') }}
                                                </span>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
        {{-- Comments: only approved ones are passed in by CommentService. --}}
        <section id="comments" class="lindu-section lindu-section--alt">
            <div class="container-xl">
                <div class="row justify-content-center">
                    <div class="col-lg-9">
                        <h2 class="h3 mb-4">Comments ({{ $comments->count() }})</h2>

                        @forelse ($comments as $c)
                            <article class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-baseline gap-2 flex-wrap">
                                        <b>{{ $c->author_name }}</b>
                                        <time class="text-secondary small">{{ $c->created_at?->diffForHumans() }}</time>
                                    </div>
                                    <p class="mt-2 mb-2" style="white-space:pre-wrap">{{ $c->body }}</p>
                                    <form method="POST" action="{{ route('site.comments.report', $c) }}">
                                        @csrf
                                        <input type="hidden" name="reason" value="Reported from the post page">
                                        <button class="btn btn-link btn-sm p-0 text-secondary">Report</button>
                                    </form>
                                </div>
                            </article>
                        @empty
                            <p class="text-secondary">No comments yet. Be the first.</p>
                        @endforelse

                        <div class="card mt-4">
                            <div class="card-body p-4">
                                <h3 class="h5 mb-3">Leave a comment</h3>
                                <form method="POST" action="{{ route('site.comments.store', $post) }}">
                                    @csrf
                                    {{-- Honeypot: real visitors never see this. --}}
                                    <div class="visually-hidden" aria-hidden="true">
                                        <label for="c-website">Website</label>
                                        <input type="text" id="c-website" name="website" tabindex="-1" autocomplete="off">
                                    </div>
                                    @guest
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label" for="c-name">Name *</label>
                                                <input id="c-name" name="author_name" value="{{ old('author_name') }}" class="form-control" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="c-email">Email *</label>
                                                <input id="c-email" type="email" name="author_email" value="{{ old('author_email') }}" class="form-control" required>
                                            </div>
                                        </div>
                                    @endguest
                                    <div class="mt-3">
                                        <label class="form-label" for="c-body">Comment *</label>
                                        <textarea id="c-body" name="body" rows="5" class="form-control" required>{{ old('body') }}</textarea>
                                    </div>
                                    <button class="btn btn-primary mt-3">Post comment</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </article>

    <x-site.cta
        title="Enjoyed this?"
        body="We write about building things that last."
        url="{{ route('site.contact') }}"
        secondary-label="All articles"
        :secondary-url="route('site.blog')"
    />
@endsection
