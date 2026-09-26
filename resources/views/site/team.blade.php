@extends('site.layout')
@section('title', $seo['title'] ?? 'Our Team')

@section('content')
<div class="wrap">
    {!! app(\App\Core\Services\SeoService::class)->breadcrumb([
        ['name' => 'Home', 'url' => route('site.home')],
        ['name' => 'Team'],
    ]) !!}
    <div class="sec-head">
        <h2 class="page-title">Meet The Team</h2>
        <p>{{ setting('general.team_intro', 'The people who make it happen.') }}</p>
    </div>
</div>

<section style="padding-top:0">
    <div class="wrap">
        <div class="grid g4">
            @forelse($members as $m)
                <div class="card" style="text-align:center">
                    @if($m->photo)
                        <img src="{{ $m->photo }}" alt="{{ $m->name }}" style="width:120px;height:120px;object-fit:cover;border-radius:50%;margin:0 auto 12px" loading="lazy">
                    @else
                        <div style="width:120px;height:120px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;font-size:2.2rem;color:#64748b;margin:0 auto 12px">{{ mb_substr($m->name, 0, 1) }}</div>
                    @endif
                    <h3 style="margin:0 0 2px">{{ $m->name }}</h3>
                    <div style="color:var(--lindu-primary);font-size:.9rem;font-weight:600">{{ $m->position }}</div>
                    @if($m->bio)<p style="color:#64748b;font-size:.9rem;margin-top:8px">{{ \Illuminate\Support\Str::limit($m->bio, 110) }}</p>@endif
                    @if($m->social)
                        <div style="margin-top:8px;display:flex;gap:8px;justify-content:center">
                            @foreach($m->social as $net => $url)
                                <a href="{{ $url }}" target="_blank" rel="noopener" style="font-size:.85rem">{{ ucfirst($net) }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="empty" style="grid-column:1/-1">No team members yet.</div>
            @endforelse
        </div>
    </div>
</section>
@endsection
