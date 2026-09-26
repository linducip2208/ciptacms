@extends('site.layout')
@section('title', $seo['title'] ?? 'Our Clients')

@section('content')
<div class="wrap">
    {!! app(\App\Core\Services\SeoService::class)->breadcrumb([
        ['name' => 'Home', 'url' => route('site.home')],
        ['name' => 'Clients'],
    ]) !!}
    <div class="sec-head">
        <h2 class="page-title">Our Clients</h2>
        <p>{{ setting('general.clients_intro', 'Organisations that trust us with their projects.') }}</p>
    </div>
</div>

<section style="padding-top:0">
    <div class="wrap">
        <div class="grid g4">
            @forelse($rows as $c)
                @if($c->website)
                    <a href="{{ $c->website }}" target="_blank" rel="noopener" class="card" style="text-align:center;text-decoration:none;display:block">
                @else
                    <div class="card" style="text-align:center">
                @endif
                    @if($c->logo)
                        <img src="{{ $c->logo }}" alt="{{ $c->name }}" style="max-height:56px;width:auto;object-fit:contain" loading="lazy">
                    @else
                        <b>{{ $c->name }}</b>
                    @endif
                @if($c->website)</a>@else</div>@endif
            @empty
                <div class="empty" style="grid-column:1/-1">No clients listed yet.</div>
            @endforelse
        </div>
    </div>
</section>
@endsection
