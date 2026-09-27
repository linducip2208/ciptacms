@extends('site.layout')
@section('title', 'Products — ' . setting('general.site_name', 'Lindu CMS'))
@section('description', setting('general.products_intro'))

@section('content')
    <x-site.hero
        title="Our products"
        eyebrow="Products"
        :subtitle="setting('general.products_intro', 'Solutions built to solve real business problems.')"
        label="Enquire"
        url="{{ route('site.contact') }}"
    />

    <x-site.section>
        @if ($products->isEmpty())
            <x-site.empty-state title="No products published yet" icon="ti-package" />
        @else
            <x-site.card-grid
                :columns="3"
                :items="$products->map(fn ($p) => [
                    'title' => $p->title,
                    'excerpt' => $p->excerpt,
                    'image' => $p->image,
                    'url' => route('site.product', $p->slug),
                    'cta' => 'View details',
                ])->all()"
            />
        @endif
    </x-site.section>

    <x-site.cta
        title="Not seeing what you need?"
        body="We build bespoke solutions too. Start a conversation."
        url="{{ route('site.contact') }}"
        secondary-label="Our services"
        :secondary-url="route('site.services')"
    />
@endsection
