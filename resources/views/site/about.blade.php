@extends('site.layout')
@section('title', 'About us — ' . setting('general.site_name', 'Lindu CMS'))
@section('description', \Illuminate\Support\Str::limit(strip_tags((string) ($about['about.description'] ?? '')), 160))

@php
    $aboutSummary = \Illuminate\Support\Str::limit(strip_tags((string) ($about['about.description'] ?? '')), 220);
@endphp

@section('content')
    <x-site.hero
        title="{{ setting('general.site_name', 'Lindu CMS') }}"
        :subtitle="$aboutSummary"
        label="Contact us"
        url="{{ route('site.contact') }}"
    />

    @if (($stats['projects'] ?? 0) > 0 || ($stats['clients'] ?? 0) > 0)
        <div class="border-bottom">
            <div class="container-xl py-4">
                <x-site.stats :items="[
                    ['value' => $stats['years'] . '+', 'label' => 'Years of experience'],
                    ['value' => number_format($stats['projects']), 'label' => 'Projects completed'],
                    ['value' => number_format($stats['clients']), 'label' => 'Clients served'],
                ]" />
            </div>
        </div>
    @endif

    @if (! empty($about['about.history']) || ! empty($about['about.vision']) || ! empty($about['about.mission']))
        <x-site.section title="Who we are" eyebrow="Our story">
            <div class="row g-4">
                @foreach (['about.history' => 'Our history', 'about.vision' => 'Our vision', 'about.mission' => 'Our mission'] as $key => $label)
                    @if (! empty($about[$key]))
                        <div class="col-md-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h3 class="h4">{{ $label }}</h3>
                                    <div class="lindu-prose text-secondary">{!! nl2br(e($about[$key])) !!}</div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </x-site.section>
    @endif

    @if (! empty($about['about.values']))
        <x-site.section
            title="Our values"
            eyebrow="What drives us"
            :alt="true"
            href="{{ route('site.team') }}"
            badge="Meet the team"
        >
            <div class="row g-3">
                @foreach ($about['about.values'] as $value)
                    @php $text = is_array($value) ? ($value['title'] ?? $value['value'] ?? '') : $value; @endphp
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <i class="ti ti-check-circle fs-3 mb-2" style="color:var(--tblr-primary)"></i>
                                <p class="mb-0">{{ $text }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-site.section>
    @endif

    @if ($team->isNotEmpty())
        <x-site.section title="Meet the team" eyebrow="People" href="{{ route('site.team') }}" badge="Everyone">
            <x-site.card-grid
                :columns="4"
                :items="$team->map(fn ($m) => [
                    'title' => $m->name,
                    'excerpt' => $m->position,
                    'image' => $m->photo,
                    'imageAlt' => $m->name,
                    'url' => route('site.team'),
                ])->all()"
            />
        </x-site.section>
    @endif

    <x-site.cta
        title="Want to work together?"
        body="Tell us about the project and we will reply with next steps."
        url="{{ route('site.contact') }}"
        secondary-label="View our services"
        :secondary-url="route('site.services')"
    />
@endsection
