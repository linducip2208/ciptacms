@extends('site.layout')
@section('title', $seo['title'] ?? 'Testimonials')

@section('content')
<div class="wrap">
    {!! app(\App\Core\Services\SeoService::class)->breadcrumb([
        ['name' => 'Home', 'url' => route('site.home')],
        ['name' => 'Testimonials'],
    ]) !!}
    <div class="sec-head">
        <h2 class="page-title">Client Testimonials</h2>
        <p>{{ setting('general.testimonials_intro', 'Kind words from the people we work with.') }}</p>
    </div>
</div>

<section style="padding-top:0">
    <div class="wrap">
        <div class="grid g3">
            @forelse($rows as $t)
                <div class="card">
                    <div class="stars" aria-label="{{ $t->rating }} out of 5">
                        @for($i = 0; $i < $t->rating; $i++)★@endfor
                    </div>
                    <p>"{{ $t->testimonial }}"</p>
                    <div style="display:flex;align-items:center;gap:10px;margin-top:12px">
                        @if($t->photo)
                            <img src="{{ $t->photo }}" alt="{{ $t->customer }}" style="width:44px;height:44px;object-fit:cover;border-radius:50%">
                        @endif
                        <div>
                            <b>{{ $t->customer }}</b>
                            @if($t->company)<div style="color:#64748b;font-size:.875rem">{{ $t->company }}</div>@endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty" style="grid-column:1/-1">No testimonials yet.</div>
            @endforelse
        </div>
    </div>
</section>
@endsection
