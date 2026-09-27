@extends('site.layout')
@section('title', 'Services — ' . setting('general.site_name', 'Lindu CMS'))
@section('description', setting('general.services_intro'))

@section('content')
    <x-site.hero
        title="What we do"
        eyebrow="Services"
        :subtitle="setting('general.services_intro', 'Everything you need to build, launch and grow.')"
        label="Start a project"
        url="{{ route('site.contact') }}"
    />

    <x-site.section>
        @if ($services->isEmpty())
            <x-site.empty-state
                title="No services published yet"
                message="Services added in the admin appear here."
            />
        @else
            <div class="row g-4">
                @foreach ($services as $service)
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-start gap-3 mb-3">
                                    @if ($service->image)
                                        <img src="{{ $service->image }}" alt="{{ $service->title }}" class="rounded" style="width:4.5rem;height:3.5rem;object-fit:cover" loading="lazy">
                                    @elseif ($service->icon)
                                        <div class="fs-1" style="color:var(--tblr-primary)">{{ $service->icon }}</div>
                                    @endif

                                    <div>
                                        <h2 class="h3 mb-1">
                                            <a href="{{ route('site.service', $service->slug) }}" class="text-decoration-none stretched-link">{{ $service->title }}</a>
                                        </h2>
                                        @if ($service->excerpt)
                                            <p class="text-secondary mb-0">{{ $service->excerpt }}</p>
                                        @endif
                                    </div>
                                </div>

                                @if ($service->description)
                                    <div class="lindu-prose text-secondary">{!! $service->description !!}</div>
                                @endif

                                @if (filled($service->features))
                                    <ul class="list-unstyled d-grid gap-2 mt-3">
                                        @foreach ($service->features as $feature)
                                            <li class="d-flex gap-2">
                                                <i class="ti ti-check text-success mt-1"></i>
                                                <span>{{ is_array($feature) ? ($feature['title'] ?? '') : $feature }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-site.section>

    <x-site.cta
        title="Need something not listed?"
        body="Most projects combine more than one service. Tell us what you are after."
        url="{{ route('site.contact') }}"
    />
@endsection
