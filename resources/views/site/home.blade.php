@extends('site.layout')
@section('title', $seo['title'] ?? setting('general.site_name', 'Lindu CMS'))
@section('description', $seo['description'] ?? null)

@section('content')
    @isset($page)
        {{-- A Page Builder page owns the homepage entirely. --}}
        @if (! empty($page->builder))
            {!! $rendered ?? \App\Core\Services\BlockLibrary::render($page->builder) !!}
        @else
            <x-site.hero
                :title="$page->title"
                :subtitle="$page->excerpt"
                :image="$page->featured_image"
                label="Contact us"
                url="{{ route('site.contact') }}"
            />
            @if (! empty($page->body))
                <x-site.section :tight="true">
                    <div class="row justify-content-center">
                        <div class="col-lg-8 lindu-prose">{!! $page->body !!}</div>
                    </div>
                </x-site.section>
            @endif
        @endif
    @else
        <x-site.hero
            :title="setting('general.tagline', 'Building digital products that grow with you.')"
            :subtitle="setting('general.hero_subtitle', \Illuminate\Support\Str::limit(strip_tags((string) ($about['about.description'] ?? '')), 180))"
            :image="setting('general.hero_image')"
            label="Contact us"
            url="{{ route('site.contact') }}"
        />

        @if (($stats ?? []) && ($stats['projects'] ?? 0) > 0)
            <div class="border-bottom">
                <div class="container-xl py-4">
                    <x-site.stats :items="[
                        ['value' => number_format($stats['projects'] ?? 0), 'label' => 'Projects delivered'],
                        ['value' => number_format($stats['clients'] ?? 0), 'label' => 'Happy clients'],
                        ['value' => number_format($stats['team'] ?? 0), 'label' => 'Team members'],
                        ['value' => number_format($stats['services'] ?? 0), 'label' => 'Services offered'],
                    ]" />
                </div>
            </div>
        @endif

        @if ($services->isNotEmpty())
            <x-site.section
                title="What we do"
                eyebrow="Services"
                :description="setting('general.services_intro')"
            >
                <x-site.card-grid
                    :columns="3"
                    :items="$services->map(fn ($s) => [
                        'title' => $s->title,
                        'excerpt' => $s->excerpt,
                        'icon' => $s->icon,
                        'image' => $s->image,
                        'url' => route('site.service', $s->slug),
                        'cta' => 'Learn more',
                    ])->all()"
                />
            </x-site.section>
        @endif

        @if ($portfolio->isNotEmpty())
            <x-site.section
                title="Selected work"
                eyebrow="Portfolio"
                :description="setting('general.portfolio_intro')"
                :alt="true"
                href="{{ route('site.portfolio') }}"
                badge="All projects"
            >
                <x-site.card-grid
                    :columns="3"
                    :items="$portfolio->map(fn ($p) => [
                        'title' => $p->title,
                        'excerpt' => $p->excerpt,
                        'image' => $p->images[0] ?? null,
                        'url' => route('site.portfolio.item', $p->slug),
                    ])->all()"
                />
            </x-site.section>
        @endif

        @if ($testimonials->isNotEmpty())
            <x-site.section
                title="What our clients say"
                eyebrow="Testimonials"
                :description="setting('general.testimonials_intro')"
            >
                <x-site.testimonials :items="$testimonials" />
            </x-site.section>
        @endif

        @if ($clients->isNotEmpty())
            <x-site.client-grid
                title="Trusted by"
                :description="setting('general.clients_intro')"
                :items="$clients->map(fn ($c) => [
                    'logo' => $c->logo,
                    'url' => $c->website,
                    'name' => $c->name,
                ])->all()"
            />
        @endif

        @if ($posts->isNotEmpty())
            <x-site.section
                title="From the blog"
                eyebrow="Insights"
                href="{{ route('site.blog') }}"
                badge="All articles"
            >
                <x-site.card-grid
                    :columns="3"
                    :items="$posts->map(fn ($p) => [
                        'title' => $p->title,
                        'excerpt' => $p->excerpt ?: strip_tags((string) $p->body),
                        'image' => $p->featured_image,
                        'url' => route('site.post', $p->slug),
                    ])->all()"
                />
            </x-site.section>
        @endif

        <x-site.cta
            title="Let's build something together"
            body="Tell us what you need and we will come back with a plan."
            url="{{ route('site.contact') }}"
            secondary-label="See our work"
            :secondary-url="route('site.portfolio')"
        />
    @endisset
@endsection
