@extends('site.layout')
@section('title', ($seo['title'] ?? $item->title) . ' — ' . setting('general.site_name', 'Lindu CMS'))
@section('description', $seo['description'] ?? \Illuminate\Support\Str::limit(strip_tags((string) $item->excerpt), 160))

@section('content')
    <div class="border-bottom">
        <div class="container-xl py-3">
            <x-site.breadcrumbs :items="[
                ['label' => 'Home', 'url' => route('site.home')],
                ['label' => 'Services', 'url' => route('site.services')],
                ['label' => $item->title],
            ]" />
        </div>
    </div>

    <x-site.hero
        :title="$item->title"
        :subtitle="$item->excerpt"
        :image="$item->image"
        :cta-url="$item->cta_url"
        :cta-label="$item->cta_label"
    />

    <x-site.section>
        <div class="row g-5">
            <div class="col-lg-8">
                @if (! empty($item->description))
                    <div class="lindu-prose">{!! $item->description !!}</div>
                @else
                    <div class="lindu-prose"><p>{{ $item->excerpt }}</p></div>
                @endif

                @if (filled($item->features))
                    <h2 class="h3 mt-4 mb-3">What's included</h2>
                    <div class="row g-2">
                        @foreach ($item->features as $feature)
                            <div class="col-md-6">
                                <div class="d-flex gap-2 align-items-start p-2 rounded" style="background:var(--tblr-bg-surface-secondary)">
                                    <i class="ti ti-check-circle text-success mt-1"></i>
                                    <span>{{ is_array($feature) ? ($feature['title'] ?? '') : $feature }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <aside class="col-lg-4">
                <div class="card sticky-top" style="lindu-sticky-aside">
                    <div class="card-body">
                        <h2 class="h5 mb-3">Talk to us about this</h2>
                        <p class="text-secondary small">
                            Tell us what you need and we will send a plan within one business day.
                        </p>
                        <a href="{{ route('site.contact') }}" class="btn btn-primary w-100 mb-2">
                            {{ $item->cta_label ?: 'Get a quote' }}
                        </a>

                        @if ($related->isNotEmpty())
                            <hr>
                            <h3 class="h6 text-secondary text-uppercase" style="lindu-micro-label">
                                Related services
                            </h3>
                            <ul class="list-unstyled d-grid gap-2 mb-0">
                                @foreach ($related as $r)
                                    <li><a href="{{ route('site.service', $r->slug) }}" class="text-decoration-none">{{ $r->title }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </aside>
        </div>
    </x-site.section>

    <x-site.cta
        title="Ready when you are"
        body="One short message is enough to start."
        url="{{ route('site.contact') }}"
    />
@endsection
