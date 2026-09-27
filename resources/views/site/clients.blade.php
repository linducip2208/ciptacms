@extends('site.layout')
@section('title', 'Our clients — ' . setting('general.site_name', 'Lindu CMS'))
@section('description', setting('general.clients_intro'))

@section('content')
    <x-site.hero
        title="Trusted by teams worldwide"
        eyebrow="Clients"
        :subtitle="setting('general.clients_intro', 'Organisations that trust us with their projects.')"
        label="Become a client"
        url="{{ route('site.contact') }}"
    />

    <x-site.client-grid
        title="Trusted by teams worldwide"
        :description="setting('general.clients_intro')"
        :items="$rows->map(fn ($c) => [
            'logo' => $c->logo,
            'url' => $c->website,
            'name' => $c->name,
        ])->all()"
    />

    <x-site.cta
        title="Let's add you to this list"
        body="One conversation is all it takes to start."
        url="{{ route('site.contact') }}"
    />
@endsection
