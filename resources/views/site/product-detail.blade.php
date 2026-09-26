@extends('site.layout')
@section('title', $seo['title'] ?? $item->title)

@section('content')
<div class="wrap">
    {!! app(\App\Core\Services\SeoService::class)->breadcrumb([
        ['name' => 'Home', 'url' => route('site.home')],
        ['name' => 'Products', 'url' => route('site.products')],
        ['name' => $item->title],
    ]) !!}
</div>

<section style="padding-top:24px">
    <div class="wrap">
        <div class="grid" style="grid-template-columns:1fr 320px;gap:36px;align-items:start">
            <div>
                <h1 class="page-title">{{ $item->title }}</h1>
                @if($item->image)
                    <img src="{{ $item->image }}" alt="{{ $item->title }}" style="width:100%;border-radius:var(--lindu-radius);margin-bottom:22px">
                @endif
                @if($item->excerpt)<p style="font-size:1.1rem;color:#475569">{{ $item->excerpt }}</p>@endif
                @if($item->description)<div class="prose">{!! $item->description !!}</div>@endif

                @if($item->features)
                    <h3 style="margin-top:30px">Key features</h3>
                    <ul style="padding-left:20px;color:#475569">
                        @foreach($item->features as $f)
                            <li>{{ is_array($f) ? ($f['title'] ?? json_encode($f)) : $f }}</li>
                        @endforeach
                    </ul>
                @endif

                @if($item->gallery)
                    <div class="gallery-grid" style="margin-top:24px">
                        @foreach($item->gallery as $img)
                            <img src="{{ $img }}" alt="{{ $item->title }}" loading="lazy">
                        @endforeach
                    </div>
                @endif

                @if($item->cta_url)
                    <p style="margin-top:28px"><a class="btn" href="{{ $item->cta_url }}">{{ $item->cta_label ?: 'Learn more' }}</a></p>
                @endif
            </div>
            <aside>
                <div class="card">
                    <h3>Related products</h3>
                    <ul style="list-style:none;padding:0;margin:0;display:grid;gap:10px">
                        @foreach($related as $r)
                            <li><a href="{{ route('site.product', $r->slug) }}">{{ $r->title }}</a></li>
                        @endforeach
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</section>
@endsection
