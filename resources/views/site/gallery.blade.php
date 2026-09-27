@extends('site.layout')
@section('title', ($seo['title'] ?? 'Gallery') . ' — ' . setting('general.site_name', 'Lindu CMS'))
@section('description', $seo['description'] ?? null)

@section('content')
    <div class="border-bottom">
        <div class="container-xl py-3">
            <x-site.breadcrumbs :items="array_values(array_filter([
                ['label' => 'Home', 'url' => route('site.home')],
                ['label' => 'Gallery', 'url' => route('site.gallery')],
                $current ? ['label' => $current->title] : null,
            ]))" />
        </div>
    </div>

    <x-site.hero
        :title="$current->title ?? 'Gallery'"
        :eyebrow="$current ? null : 'Photos'"
        :subtitle="$current?->description ?? setting('general.gallery_intro', 'A look at our work and our team.')"
    />

    <x-site.section :tight="true">
        @if ($current)
            <x-site.gallery-grid
                :items="$current->images->map(fn ($i) => [
                    'src' => $i->path,
                    'caption' => $i->caption,
                    'alt' => $i->caption ?: $current->title,
                ])->all()"
            />
        @elseif ($albums->isEmpty())
            <x-site.empty-state
                title="No albums yet"
                message="Albums added in the admin appear here."
                icon="ti-photo"
            />
        @else
            <x-site.card-grid
                :columns="3"
                :items="$albums->map(fn ($a) => [
                    'title' => $a->title,
                    'excerpt' => $a->images->count() . ' photo' . ($a->images->count() === 1 ? '' : 's'),
                    'image' => $a->cover ?: $a->images->first()?->path,
                    'url' => route('site.gallery.album', $a->slug),
                    'cta' => 'Open album',
                ])->all()"
            />
        @endif
    </x-site.section>

    <x-site.cta
        title="Like what you see?"
        body="We would be glad to do something like that for you."
        url="{{ route('site.contact') }}"
    />
@endsection
