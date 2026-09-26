@extends('site.layout')
@section('title', $seo['title'] ?? setting('general.site_name', 'Lindu CMS'))

@section('content')
    @isset($page)
        {{-- Homepage rendered from Page Builder data --}}
        @if(!empty($page->featured_image))
            <div class="hero" style="background-image:linear-gradient(135deg,rgba(0,0,0,.55),rgba(0,0,0,.55)),url('{{ $page->featured_image }}');background-size:cover;background-position:center">
                <div class="wrap"><h1>{{ $page->title }}</h1></div>
            </div>
        @endif
        <div class="wrap prose" style="padding-top:32px;padding-bottom:32px">
            @if(!empty($page->builder))
                {!! $rendered ?? \App\Core\Services\BlockLibrary::render($page->builder) !!}
            @endif
            @if(!empty($page->body))
                {!! $page->body !!}
            @endif
        </div>
    @else
        <div class="hero">
            <div class="wrap">
                <h1>{{ setting('general.tagline', 'Building digital products that grow with you.') }}</h1>
                <p>{{ \Illuminate\Support\Str::limit(strip_tags((string) ($about['about.description'] ?? '')), 220) }}</p>
                <p style="margin-top:24px">
                    <a class="btn" href="{{ route('site.contact') }}" style="background:#fff;color:{{ setting('branding.primary_color', '#1d4ed8') }}">Get in touch</a>
                    <a class="btn btn-outline" href="{{ route('site.portfolio') }}" style="color:#fff;border-color:rgba(255,255,255,.7);margin-left:8px">See our work</a>
                </p>
            </div>
        </div>

        <div class="wrap" style="padding-top:32px">
            <div class="grid g4">
                <div class="stat"><b>{{ $stats['projects'] ?? 0 }}</b><span>Projects delivered</span></div>
                <div class="stat"><b>{{ $stats['clients'] ?? 0 }}</b><span>Happy clients</span></div>
                <div class="stat"><b>{{ $stats['team'] ?? 0 }}</b><span>Team members</span></div>
                <div class="stat"><b>{{ $stats['services'] ?? 0 }}</b><span>Services offered</span></div>
            </div>
        </div>

        @if($services->isNotEmpty())
        <section class="sec-alt">
            <div class="wrap">
                <div class="sec-head">
                    <h2>What We Do</h2>
                    <p>{{ setting('general.services_intro', 'A complete set of services to move your business forward.') }}</p>
                </div>
                <div class="grid g3">
                    @foreach($services as $s)
                        <div class="card">
                            @if($s->icon)<div style="font-size:1.8rem;margin-bottom:8px">{{ $s->icon }}</div>@endif
                            <h3><a href="{{ route('site.service', $s->slug) }}" style="text-decoration:none">{{ $s->title }}</a></h3>
                            <p style="color:#64748b;margin:0">{{ \Illuminate\Support\Str::limit($s->excerpt, 110) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        @if($testimonials->isNotEmpty())
        <section>
            <div class="wrap">
                <div class="sec-head"><h2>What Our Clients Say</h2></div>
                <div class="grid g3">
                    @foreach($testimonials as $t)
                        <div class="card">
                            <div class="stars">@for($i=0;$i<$t->rating;$i++)★@endfor</div>
                            <p>"{{ $t->testimonial }}"</p>
                            <b>{{ $t->customer }}</b>
                            @if($t->company)<div style="color:#64748b;font-size:.9rem">{{ $t->company }}</div>@endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        @if($clients->isNotEmpty())
        <section class="sec-alt">
            <div class="wrap">
                <div class="sec-head"><h2>Trusted By</h2></div>
                <div class="grid g4">
                    @foreach($clients as $c)
                        <div class="card" style="text-align:center;padding:18px">
                            @if($c->logo)
                                <img src="{{ $c->logo }}" alt="{{ $c->name }}" style="max-height:52px;width:auto;object-fit:contain">
                            @else
                                <b>{{ $c->name }}</b>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        @if($posts->isNotEmpty())
        <section>
            <div class="wrap">
                <div class="sec-head"><h2>From The Blog</h2></div>
                <div class="grid g3">
                    @foreach($posts as $p)
                        <div class="card">
                            <h3><a href="{{ route('site.post', $p->slug) }}" style="text-decoration:none">{{ $p->title }}</a></h3>
                            <p style="color:#64748b;margin:0">{{ \Illuminate\Support\Str::limit(strip_tags((string) $p->body), 100) }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        @endif
    @endisset
@endsection
