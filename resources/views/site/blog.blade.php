@extends('site.layout')
@section('title', ($seo['title'] ?? 'Blog') . ' — ' . setting('general.site_name', 'Lindu CMS'))
@section('description', $seo['description'] ?? setting('general.blog_intro'))

@section('content')
    <x-site.hero
        title="Blog"
        eyebrow="Insights"
        :subtitle="setting('general.blog_intro', 'Insights, news and updates.')"
    />

    <x-site.section>
        @if ($posts->isEmpty())
            <x-site.empty-state
                title="No articles published yet"
                message="Posts added in the admin appear here."
                icon="ti-news"
            />
        @else
            {{-- Featured first article, then a grid. --}}
            @php $featured = $posts->first(); $rest = $posts->slice(1); @endphp

            <div class="row g-4 mb-5">
                <div class="col-lg-8">
                    <a href="{{ route('site.post', $featured->slug) }}" class="text-decoration-none">
                        <div class="card overflow-hidden h-100">
                            @if ($featured->featured_image)
                                <img src="{{ $featured->featured_image }}" alt="{{ $featured->title }}" class="card-img-top" style="max-height:22rem;object-fit:cover" loading="lazy">
                            @endif
                            <div class="card-body p-4">
                                @if ($featured->category)
                                    <span class="badge bg-blue-lt mb-2">{{ $featured->category->name }}</span>
                                @endif
                                <h2 class="h3">{{ $featured->title }}</h2>
                                <p class="text-secondary">
                                    {{ \Illuminate\Support\Str::limit($featured->excerpt ?: strip_tags((string) $featured->body), 180) }}
                                </p>
                                <div class="text-secondary small">
                                    {{ optional($featured->published_at)->format('j M Y') }}
                                    @if ($featured->author) · {{ $featured->author->name }} @endif
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-lg-4 d-flex flex-column gap-3">
                    @foreach ($rest->take(3) as $post)
                        <a href="{{ route('site.post', $post->slug) }}" class="text-decoration-none">
                            <div class="card">
                                <div class="card-body d-flex gap-3 align-items-center">
                                    @if ($post->featured_image)
                                        <img src="{{ $post->featured_image }}" alt="" loading="lazy"
                                             class="rounded" style="width:4.5rem;height:4.5rem;object-fit:cover;flex-shrink:0">
                                    @endif
                                    <div>
                                        <h3 class="h6 mb-1">{{ $post->title }}</h3>
                                        <div class="text-secondary small">
                                            {{ optional($post->published_at)->format('j M Y') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>

            @if ($rest->count() > 3)
                <x-site.card-grid
                    :columns="3"
                    :items="$rest->slice(3)->map(fn ($p) => [
                        'title' => $p->title,
                        'excerpt' => $p->excerpt ?: strip_tags((string) $p->body),
                        'image' => $p->featured_image,
                        'url' => route('site.post', $p->slug),
                    ])->all()"
                />
            @endif

            <x-site.pagination :paginator="$posts" />
        @endif
    </x-site.section>
@endsection
