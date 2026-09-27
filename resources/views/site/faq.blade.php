@extends('site.layout')
@section('title', 'FAQ — ' . setting('general.site_name', 'Lindu CMS'))
@section('description', setting('general.faq_intro'))

@section('content')
    <x-site.hero
        title="Frequently asked questions"
        eyebrow="FAQ"
        :subtitle="setting('general.faq_intro', 'Answers to the questions we hear most.')"
        label="Still have questions?"
        url="{{ route('site.contact') }}"
    />

    <x-site.section>
        <div class="row justify-content-center">
            <div class="col-lg-9">
                @if ($grouped->isNotEmpty())
                    @if ($grouped->count() > 1)
                        <div class="lindu-filterbar justify-content-center mb-4">
                            @foreach ($grouped as $category => $items)
                                <a href="#faq-group-{{ \Illuminate\Support\Str::slug($category) }}">
                                    {{ $category }} ({{ $items->count() }})
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @foreach ($grouped as $category => $items)
                        <div id="faq-group-{{ \Illuminate\Support\Str::slug($category) }}" class="mb-5">
                            <h2 class="h3 mb-3">{{ $category }}</h2>
                            <x-site.faq :items="$items" />
                        </div>
                    @endforeach
                @else
                    <x-site.empty-state
                        title="No questions published yet"
                        message="FAQs added in the admin appear here."
                        icon="ti-help-circle"
                    />
                @endif
            </div>
        </div>
    </x-site.section>

    <x-site.cta
        title="Did not find your answer?"
        body="Ask us directly and we will reply personally."
        url="{{ route('site.contact') }}"
    />
@endsection
