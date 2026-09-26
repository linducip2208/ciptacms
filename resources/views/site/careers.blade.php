@extends('site.layout')
@section('title', $seo['title'] ?? 'Careers')

@section('content')
<div class="wrap">
    {!! app(\App\Core\Services\SeoService::class)->breadcrumb([
        ['name' => 'Home', 'url' => route('site.home')],
        ['name' => 'Careers'],
    ]) !!}
    <div class="sec-head">
        <h2 class="page-title">Join Our Team</h2>
        <p>{{ setting('general.careers_intro', 'We are always looking for talented people.') }}</p>
    </div>

    <form class="filter-bar" method="GET" action="{{ route('site.careers') }}">
        @if($locations->isNotEmpty())
            <select name="location" onchange="this.form.submit()" style="padding:7px 12px;border:1px solid #cbd5e1;border-radius:99px">
                <option value="">All locations</option>
                @foreach($locations as $l)
                    <option value="{{ $l }}" @selected(request('location') === $l)>{{ $l }}</option>
                @endforeach
            </select>
        @endif
        <input name="search" value="{{ request('search') }}" placeholder="Search position…" style="padding:7px 14px;border:1px solid #cbd5e1;border-radius:99px">
        <button class="btn" style="padding:7px 18px">Search</button>
    </form>
</div>

<section style="padding-top:0">
    <div class="wrap" style="max-width:900px">
        @forelse($rows as $c)
            @php
                $closed = $c->deadline && $c->deadline->isPast();
                $rowStyle = 'margin-bottom:14px;display:flex;justify-content:space-between;gap:18px;align-items:center;flex-wrap:wrap;'
                    .($closed ? 'opacity:.6' : '');
            @endphp
            <div class="card" style="{{ $rowStyle }}">
                <div>
                    <h3 style="margin:0 0 4px">
                        @if($closed)
                            {{ $c->position }}
                        @else
                            <a href="{{ route('site.career', $c->slug) }}" style="text-decoration:none">{{ $c->position }}</a>
                        @endif
                    </h3>
                    <div style="color:#64748b;font-size:.9rem;display:flex;gap:14px;flex-wrap:wrap">
                        @if($c->location)<span>📍 {{ $c->location }}</span>@endif
                        @if($c->employment_type)<span>💼 {{ $c->employment_type }}</span>@endif
                        @if($c->deadline)<span>⏳ Apply before {{ $c->deadline->format('M j, Y') }}</span>@endif
                    </div>
                </div>
                @if($closed)
                    <span class="badge" style="background:#fef2f2;color:#991b1b">Closed</span>
                @else
                    <a class="btn" href="{{ route('site.career', $c->slug) }}">Apply now</a>
                @endif
            </div>
        @empty
            <div class="empty">No open positions right now. Check back soon.</div>
        @endforelse
        <div style="margin-top:20px">{{ $rows->links() }}</div>
    </div>
</section>
@endsection
