@extends('site.layout')
@section('title', $seo['title'] ?? 'FAQ')

@section('content')
<div class="wrap">
    {!! app(\App\Core\Services\SeoService::class)->breadcrumb([
        ['name' => 'Home', 'url' => route('site.home')],
        ['name' => 'FAQ'],
    ]) !!}
    <div class="sec-head">
        <h2 class="page-title">Frequently Asked Questions</h2>
        <p>{{ setting('general.faq_intro', 'Answers to the questions we hear most.') }}</p>
    </div>

    @if($grouped->isNotEmpty() && $categories->count() > 1)
        <div class="filter-bar" style="justify-content:center">
            <a href="#general" class="active">All</a>
            @foreach($categories as $c)
                <a href="#{{ \Illuminate\Support\Str::slug($c) }}">{{ $c }}</a>
            @endforeach
        </div>
    @endif
</div>

<section style="padding-top:0">
    <div class="wrap" style="max-width:820px">
        @forelse($grouped as $category => $items)
            <h3 style="margin-top:30px" id="{{ \Illuminate\Support\Str::slug($category) }}">{{ $category }}</h3>
            @foreach($items as $f)
                <details id="faq-{{ $f->id }}" style="border:1px solid #e5e7eb;border-radius:var(--lindu-radius);padding:14px 18px;margin-bottom:10px;background:#fff">
                    <summary style="cursor:pointer;font-weight:600">{{ $f->question }}</summary>
                    <div class="prose" style="margin-top:10px;color:#475569">{!! nl2br(e($f->answer)) !!}</div>
                </details>
            @endforeach
        @empty
            <div class="empty">No questions published yet.</div>
        @endforelse

        <div class="card" style="margin-top:34px;text-align:center">
            <h3 style="margin-top:0">Still have questions?</h3>
            <p style="color:#64748b">Get in touch and we will be happy to help.</p>
            <a class="btn" href="{{ route('site.contact') }}">Contact Us</a>
        </div>
    </div>
</section>
@endsection
