@extends('site.layout')
@section('title', $seo['title'] ?? 'Portfolio')

@section('content')
<div class="wrap">
    {!! app(\App\Core\Services\SeoService::class)->breadcrumb([
        ['name' => 'Home', 'url' => route('site.home')],
        ['name' => 'Portfolio'],
    ]) !!}
    <div class="sec-head">
        <h2 class="page-title">Our Portfolio</h2>
        <p>{{ setting('general.portfolio_intro', 'A selection of work we are proud of.') }}</p>
    </div>

    @if($categories->isNotEmpty())
        <div class="filter-bar" style="justify-content:center">
            <a href="{{ route('site.portfolio') }}" class="{{ !request('category') ? 'active' : '' }}">All</a>
            @foreach($categories as $c)
                <a href="{{ route('site.portfolio', ['category' => $c]) }}" class="{{ request('category') === $c ? 'active' : '' }}">{{ $c }}</a>
            @endforeach
        </div>
    @endif
</div>

<section style="padding-top:0">
    <div class="wrap">
        <div class="grid g3">
            @forelse($rows as $p)
                <div class="card" style="padding:0;overflow:hidden">
                    @if($p->images && isset($p->images[0]))
                        <img src="{{ $p->images[0] }}" alt="{{ $p->title }}" style="width:100%;height:190px;object-fit:cover;display:block">
                    @elseif($p->excerpt)
                        <div style="height:190px;background:linear-gradient(135deg,#e2e8f0,#cbd5e1);display:flex;align-items:center;justify-content:center;color:#64748b;font-size:2rem">{{ mb_substr($p->title, 0, 1) }}</div>
                    @endif
                    <div style="padding:18px">
                        @if($p->category)<span class="badge">{{ $p->category }}</span>@endif
                        <h3 style="margin:8px 0 4px"><a href="{{ route('site.portfolio.item', $p->slug) }}" style="text-decoration:none">{{ $p->title }}</a></h3>
                        @if($p->client)<div style="color:#64748b;font-size:.9rem">{{ $p->client }}</div>@endif
                    </div>
                </div>
            @empty
                <div class="empty" style="grid-column:1/-1">No portfolio items yet.</div>
            @endforelse
        </div>
        <div style="margin-top:26px">{{ $rows->links() }}</div>
    </div>
</section>
@endsection
