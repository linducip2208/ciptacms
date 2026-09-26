@extends('site.layout')
@section('title', $seo['title'] ?? 'Services')

@section('content')
<div class="wrap">
    {!! app(\App\Core\Services\SeoService::class)->breadcrumb([
        ['name' => 'Home', 'url' => route('site.home')],
        ['name' => 'Services'],
    ]) !!}
    <div class="sec-head">
        <h2 class="page-title">Our Services</h2>
        <p>{{ setting('general.services_intro', 'Everything you need to build, launch and grow.') }}</p>
    </div>
</div>

<section style="padding-top:0">
    <div class="wrap">
        @forelse($services as $s)
            <div class="card" style="margin-bottom:20px;display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap">
                @if($s->image)
                    <img src="{{ $s->image }}" alt="{{ $s->title }}" style="width:150px;height:110px;object-fit:cover;border-radius:10px">
                @elseif($s->icon)
                    <div style="font-size:2.4rem;width:80px;text-align:center">{{ $s->icon }}</div>
                @endif
                <div style="flex:1;min-width:240px">
                    <h3 style="margin-top:0">{{ $s->title }}</h3>
                    <p style="color:#64748b;margin:0 0 12px">{{ $s->excerpt }}</p>
                    @if($s->features)
                        <ul style="margin:0;padding-left:20px;color:#475569">
                            @foreach($s->features as $f)
                                <li>{{ is_array($f) ? ($f['title'] ?? json_encode($f)) : $f }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <p style="margin:14px 0 0"><a class="btn" href="{{ route('site.service', $s->slug) }}">Learn more</a></p>
                </div>
            </div>
        @empty
            <div class="empty">No services published yet.</div>
        @endforelse
    </div>
</section>
@endsection
