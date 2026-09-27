@extends('site.layout')
@section('title', 'Testimonials — ' . setting('general.site_name', 'Lindu CMS'))
@section('description', setting('general.testimonials_intro'))

@section('content')
    <x-site.hero
        title="What our clients say"
        eyebrow="Testimonials"
        :subtitle="setting('general.testimonials_intro', 'Kind words from the people we work with.')"
        label="Work with us"
        url="{{ route('site.contact') }}"
    />

    <x-site.section>
        <x-site.testimonials :items="$rows" />
    </x-site.section>

    <x-site.cta
        title="Want to be the next one on this page?"
        url="{{ route('site.contact') }}"
        secondary-label="See our portfolio"
        :secondary-url="route('site.portfolio')"
    />
@endsection
