@extends('site.layout')
@section('title', $seo['title'] ?? $item->title)

@section('content')
<div class="wrap">
    {!! app(\App\Core\Services\SeoService::class)->breadcrumb([
        ['name' => 'Home', 'url' => route('site.home')],
        ['name' => 'Services', 'url' => route('site.services')],
        ['name' => $item->title],
    ]) !!}
</div>

<section style="padding-top:24px">
    <div class="wrap">
        <div class="grid" style="grid-template-columns:1fr 320px;gap:36px;align-items:start">
            <div>
                <h1 class="page-title">{{ $item->icon ? $item->icon.' ' : '' }}{{ $item->title }}</h1>
                @if($item->image)
                    <img src="{{ $item->image }}" alt="{{ $item->title }}" style="width:100%;border-radius:var(--lindu-radius);margin-bottom:22px">
                @endif
                @if($item->excerpt)
                    <p style="font-size:1.1rem;color:#475569">{{ $item->excerpt }}</p>
                @endif
                @if($item->description)
                    <div class="prose">{!! $item->description !!}</div>
                @endif

                @if($item->features)
                    <h3 style="margin-top:30px">What's included</h3>
                    <div class="grid g2">
                        @foreach($item->features as $f)
                            <div class="card" style="padding:14px 18px">✓ {{ is_array($f) ? ($f['title'] ?? json_encode($f)) : $f }}</div>
                        @endforeach
                    </div>
                @endif

                @if($item->cta_url)
                    <p style="margin-top:28px"><a class="btn" href="{{ $item->cta_url }}">{{ $item->cta_label ?: 'Get Started' }}</a></p>
                @endif
            </div>

            <aside>
                <div class="card">
                    <h3>Other services</h3>
                    <ul style="list-style:none;padding:0;margin:0;display:grid;gap:10px">
                        @foreach($related as $r)
                            <li><a href="{{ route('site.service', $r->slug) }}">{{ $r->title }}</a></li>
                        @endforeach
                        <li><a href="{{ route('site.contact') }}">Ask about this service →</a></li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</section>
@endsection
