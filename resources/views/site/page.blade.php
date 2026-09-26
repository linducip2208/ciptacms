@extends('site.layout')
@section('title', $seo['title'] ?? $page->title)

@section('content')
<div class="wrap">
    {!! app(\App\Core\Services\SeoService::class)->breadcrumb([
        ['name' => 'Home', 'url' => route('site.home')],
        ['name' => $page->title],
    ]) !!}
</div>

<article style="padding-top:16px">
    <div class="wrap" style="max-width:860px">
        @if($page->featured_image)
            <img src="{{ $page->featured_image }}" alt="{{ $page->title }}" style="width:100%;border-radius:var(--lindu-radius);margin-bottom:26px">
        @endif
        <h1 class="page-title">{{ $page->title }}</h1>
        @if($page->excerpt)<p style="font-size:1.1rem;color:#475569">{{ $page->excerpt }}</p>@endif

        @if(!empty($page->builder))
            <div class="prose" style="margin-top:22px">{!! $rendered ?? \App\Core\Services\BlockLibrary::render($page->builder) !!}</div>
        @endif

        @if(!empty($page->body))
            <div class="prose" style="margin-top:22px">{!! $page->body !!}</div>
        @endif
    </div>
</article>
@endsection
