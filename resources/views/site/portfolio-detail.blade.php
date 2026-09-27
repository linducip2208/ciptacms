@extends('site.layout')
@section('title', ($seo['title'] ?? $item->title) . ' — ' . setting('general.site_name', 'Lindu CMS'))
@section('description', $seo['description'] ?? \Illuminate\Support\Str::limit(strip_tags((string) $item->excerpt), 160))

@section('content')
    <div class="border-bottom">
        <div class="container-xl py-3">
            <x-site.breadcrumbs :items="[
                ['label' => 'Home', 'url' => route('site.home')],
                ['label' => 'Portfolio', 'url' => route('site.portfolio')],
                ['label' => $item->title],
            ]" />
        </div>
    </div>

    <x-site.section :tight="true">
        <div class="row g-5">
            <div class="col-lg-8">
                <span class="lindu-eyebrow">{{ $item->category ?: 'Project' }}</span>
                <h1 class="mb-3">{{ $item->title }}</h1>

                <div class="d-flex flex-wrap gap-4 text-secondary small mb-4">
                    @if ($item->client)<span><i class="ti ti-building me-1"></i>{{ $item->client }}</span>@endif
                    @if ($item->project_date)<span><i class="ti ti-calendar me-1"></i>{{ $item->project_date->format('M Y') }}</span>@endif
                    @if ($item->url)
                        <a href="{{ $item->url }}" target="_blank" rel="noopener" class="text-decoration-none">
                            <i class="ti ti-external-link me-1"></i>Visit project
                        </a>
                    @endif
                </div>

                @if (filled($item->images))
                    <div class="row g-3 mb-4">
                        @foreach ($item->images as $image)
                            <div class="col-12">
                                <img src="{{ $image }}" alt="{{ $item->title }}" loading="lazy" class="rounded w-100">
                            </div>
                        @endforeach
                    </div>
                @endif

                @if (! empty($item->description))
                    <div class="lindu-prose">{!! $item->description !!}</div>
                @endif

                @if (filled($item->technology))
                    <h2 class="h4 mt-4 mb-2">Technology</h2>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($item->technology as $tech)
                            <span class="badge bg-blue-lt">{{ is_array($tech) ? ($tech['title'] ?? '') : $tech }}</span>
                        @endforeach
                    </div>
                @endif
            </div>

            <aside class="col-lg-4">
                @if ($related->isNotEmpty())
                    <div class="card">
                        <div class="card-body">
                            <h2 class="h6 text-secondary text-uppercase mb-3" style="lindu-micro-label">
                                More work
                            </h2>
                            <ul class="list-unstyled d-grid gap-2 mb-0">
                                @foreach ($related as $r)
                                    <li>
                                        <a href="{{ route('site.portfolio.item', $r->slug) }}" class="text-decoration-none">
                                            {{ $r->title }}
                                            @if ($r->client)<div class="text-secondary small">{{ $r->client }}</div>@endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <a href="{{ route('site.contact') }}" class="btn btn-primary w-100 mt-3">Start a project like this</a>
            </aside>
        </div>
    </x-site.section>
@endsection
