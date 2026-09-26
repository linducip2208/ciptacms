@extends('site.layout')
@section('title', $seo['title'] ?? 'About Us')

@section('content')
<div class="wrap">
    {!! app(\App\Core\Services\SeoService::class)->breadcrumb([
        ['name' => 'Home', 'url' => route('site.home')],
        ['name' => 'About'],
    ]) !!}
</div>

<section style="padding-top:24px">
    <div class="wrap">
        <h1 class="page-title">{{ setting('general.site_name', 'Lindu CMS') }}</h1>
        <div class="prose">
            @if($about['about.description'])
                <p style="font-size:1.1rem">{{ $about['about.description'] }}</p>
            @endif
        </div>

        <div class="grid g3" style="margin-top:28px">
            <div class="stat card"><b>{{ $stats['years'] }}+</b><span>Years of experience</span></div>
            <div class="stat card"><b>{{ $stats['projects'] }}</b><span>Projects completed</span></div>
            <div class="stat card"><b>{{ $stats['clients'] }}</b><span>Clients served</span></div>
        </div>
    </div>
</section>

@if($about['about.history'] || $about['about.vision'] || $about['about.mission'])
<section class="sec-alt">
    <div class="wrap">
        <div class="grid g2">
            @if($about['about.history'])
                <div class="card">
                    <h3>Our History</h3>
                    <div class="prose">{!! nl2br(e($about['about.history'])) !!}</div>
                </div>
            @endif
            @if($about['about.vision'])
                <div class="card">
                    <h3>Our Vision</h3>
                    <div class="prose">{!! nl2br(e($about['about.vision'])) !!}</div>
                </div>
            @endif
            @if($about['about.mission'])
                <div class="card">
                    <h3>Our Mission</h3>
                    <div class="prose">{!! nl2br(e($about['about.mission'])) !!}</div>
                </div>
            @endif
        </div>
    </div>
</section>
@endif

@if(!empty($about['about.values']))
<section>
    <div class="wrap">
        <div class="sec-head"><h2>Our Values</h2></div>
        <div class="grid g4">
            @foreach($about['about.values'] as $value)
                @php $text = is_array($value) ? ($value['title'] ?? $value['value'] ?? json_encode($value)) : $value; @endphp
                <div class="card"><p style="margin:0">{{ $text }}</p></div>
            @endforeach
        </div>
    </div>
</section>
@endif

@if($team->isNotEmpty())
<section class="sec-alt">
    <div class="wrap">
        <div class="sec-head"><h2>Meet The Team</h2><p>The people behind the work.</p></div>
        <div class="grid g4">
            @foreach($team as $m)
                <div class="card" style="text-align:center">
                    @if($m->photo)
                        <img src="{{ $m->photo }}" alt="{{ $m->name }}" style="width:96px;height:96px;object-fit:cover;border-radius:50%;margin-bottom:10px">
                    @endif
                    <h3 style="margin:0 0 2px">{{ $m->name }}</h3>
                    <div style="color:#64748b;font-size:.9rem">{{ $m->position }}</div>
                </div>
            @endforeach
        </div>
        <p style="text-align:center;margin-top:26px"><a class="btn btn-outline" href="{{ route('site.team') }}">Meet everyone</a></p>
    </div>
</section>
@endif
@endsection
