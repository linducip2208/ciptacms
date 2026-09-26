@extends('site.layout')
@section('title', $seo['title'] ?? $item->title)

@section('content')
<div class="wrap">
    {!! app(\App\Core\Services\SeoService::class)->breadcrumb([
        ['name' => 'Home', 'url' => route('site.home')],
        ['name' => 'Portfolio', 'url' => route('site.portfolio')],
        ['name' => $item->title],
    ]) !!}
</div>

<section style="padding-top:24px">
    <div class="wrap">
        <h1 class="page-title">{{ $item->title }}</h1>
        <div class="grid g4" style="margin:14px 0 22px">
            @if($item->client)<div><b>Client</b><br>{{ $item->client }}</div>@endif
            @if($item->category)<div><b>Category</b><br>{{ $item->category }}</div>@endif
            @if($item->project_date)<div><b>Date</b><br>{{ $item->project_date->format('M Y') }}</div>@endif
            @if($item->url)<div><b>Visit</b><br><a href="{{ $item->url }}" rel="noopener" target="_blank">Open project ↗</a></div>@endif
        </div>

        @if($item->images)
            <div class="gallery-grid">
                @foreach($item->images as $img)
                    <img src="{{ $img }}" alt="{{ $item->title }}" loading="lazy">
                @endforeach
            </div>
        @endif

        @if($item->excerpt)<p style="font-size:1.1rem;color:#475569;margin-top:24px">{{ $item->excerpt }}</p>@endif
        @if($item->description)<div class="prose">{!! $item->description !!}</div>@endif

        @if($item->technology)
            <h3 style="margin-top:28px">Technology</h3>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                @foreach($item->technology as $tech)
                    <span class="badge">{{ $tech }}</span>
                @endforeach
            </div>
        @endif

        @if($related->isNotEmpty())
            <h3 style="margin-top:40px">More work</h3>
            <div class="grid g3">
                @foreach($related as $r)
                    <div class="card"><a href="{{ route('site.portfolio.item', $r->slug) }}" style="text-decoration:none"><b>{{ $r->title }}</b></a></div>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
