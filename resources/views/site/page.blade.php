@extends('site.layout')
@section('title', ($seo['title'] ?? $page->title) . ' — ' . setting('general.site_name', 'Lindu CMS'))
@section('description', $seo['description'] ?? $page->meta_description ?? $page->excerpt)

@section('content')
    <div class="border-bottom">
        <div class="container-xl py-3">
            <x-site.breadcrumbs :items="[
                ['label' => 'Home', 'url' => route('site.home')],
                ['label' => $page->title],
            ]" />
        </div>
    </div>

    <x-site.section :tight="true">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <h1 class="mb-3">{{ $page->title }}</h1>

                @if ($page->excerpt)
                    <p class="lead text-secondary">{{ $page->excerpt }}</p>
                @endif

                @if ($page->featured_image)
                    <img src="{{ $page->featured_image }}" alt="{{ $page->title }}" class="rounded w-100 my-4" style="max-height:26rem;object-fit:cover">
                @endif

                @if (! empty($page->builder))
                    {{-- Page Builder output, rendered through the same tokens. --}}
                    {!! $rendered ?? \App\Core\Services\BlockLibrary::render($page->builder) !!}
                @endif

                @if (! empty($page->body))
                    <div class="lindu-prose mt-4">{!! $page->body !!}</div>
                @endif
            </div>
        </div>
    </x-site.section>

    <x-site.cta
        title="Any questions about this?"
        body="We are happy to talk it through."
        url="{{ route('site.contact') }}"
    />
@endsection
