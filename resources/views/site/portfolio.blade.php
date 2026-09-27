@extends('site.layout')
@section('title', 'Portfolio — ' . setting('general.site_name', 'Lindu CMS'))
@section('description', setting('general.portfolio_intro'))

@section('content')
    <x-site.hero
        title="Our portfolio"
        eyebrow="Selected work"
        :subtitle="setting('general.portfolio_intro', 'A selection of work we are proud of.')"
        label="Start a project"
        url="{{ route('site.contact') }}"
    />

    <x-site.section>
        @if (filled($categories))
            <div class="lindu-filterbar justify-content-center">
                <a href="{{ route('site.portfolio') }}" class="{{ request('category') ? '' : 'is-active' }}">All</a>
                @foreach ($categories as $category)
                    <a href="{{ route('site.portfolio', ['category' => $category]) }}"
                       class="{{ request('category') === $category ? 'is-active' : '' }}">{{ $category }}</a>
                @endforeach
            </div>
        @endif

        @if ($rows->isEmpty())
            <x-site.empty-state
                title="No projects published yet"
                message="Portfolio items added in the admin appear here."
                icon="ti-briefcase"
            />
        @else
            <x-site.card-grid
                :columns="3"
                :items="$rows->map(fn ($p) => [
                    'title' => $p->title,
                    'excerpt' => $p->excerpt,
                    'image' => $p->images[0] ?? null,
                    'url' => route('site.portfolio.item', $p->slug),
                    'cta' => 'View project',
                ])->all()"
            />

            <x-site.pagination :paginator="$rows" />
        @endif
    </x-site.section>

    <x-site.cta
        title="Like what you see?"
        body="Tell us about your project and we will show you what is possible."
        url="{{ route('site.contact') }}"
    />
@endsection
