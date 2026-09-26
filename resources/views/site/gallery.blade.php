@extends('site.layout')
@section('title', $seo['title'] ?? 'Gallery')

@section('content')
<div class="wrap">
    {!! app(\App\Core\Services\SeoService::class)->breadcrumb(array_filter([
        ['name' => 'Home', 'url' => route('site.home')],
        ['name' => 'Gallery', 'url' => route('site.gallery')],
        $current ? ['name' => $current->title] : null,
    ])) !!}
    <div class="sec-head">
        <h2 class="page-title">{{ $current ? $current->title : 'Gallery' }}</h2>
        <p>{{ $current?->description ?: setting('general.gallery_intro', 'A look at our work and our team.') }}</p>
    </div>
</div>

<section style="padding-top:0">
    <div class="wrap">
        @if($current)
            <div class="gallery-grid">
                @forelse($current->images as $img)
                    <img src="{{ $img->path }}" alt="{{ $img->caption ?: $current->title }}" loading="lazy">
                @empty
                    <div class="empty" style="grid-column:1/-1">This album has no images yet.</div>
                @endforelse
            </div>
            <p style="margin-top:22px"><a href="{{ route('site.gallery') }}">← All albums</a></p>
        @else
            <div class="grid g3">
                @forelse($albums as $album)
                    <a href="{{ route('site.gallery.album', $album->slug) }}" class="card" style="text-decoration:none;display:block;padding:0;overflow:hidden">
                        @if($album->cover)
                            <img src="{{ $album->cover }}" alt="{{ $album->title }}" style="width:100%;height:200px;object-fit:cover;display:block">
                        @elseif($album->images->isNotEmpty())
                            <img src="{{ $album->images->first()->path }}" alt="{{ $album->title }}" style="width:100%;height:200px;object-fit:cover;display:block">
                        @else
                            <div style="height:200px;background:#e2e8f0"></div>
                        @endif
                        <div style="padding:16px">
                            <b>{{ $album->title }}</b>
                            <div style="color:#64748b;font-size:.875rem">{{ $album->images_count ?? $album->images->count() }} photos</div>
                        </div>
                    </a>
                @empty
                    <div class="empty" style="grid-column:1/-1">No gallery albums yet.</div>
                @endforelse
            </div>
        @endif
    </div>
</section>
@endsection
