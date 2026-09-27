@extends('site.layout')
@section('title', 'Contact us — ' . setting('general.site_name', 'Lindu CMS'))
@section('description', setting('general.contact_intro'))

@section('content')
    <x-site.hero
        title="Get in touch"
        eyebrow="Contact"
        :subtitle="setting('general.contact_intro', 'We would love to hear from you.')"
    />

    <x-site.section>
        <div class="row g-4 g-lg-5">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body p-4">
                        <h2 class="h4 mb-4">Send us a message</h2>

                        <form method="POST" action="{{ route('site.contact.submit') }}">
                            @csrf

                            {{-- Honeypot: real visitors never see this. --}}
                            <div class="visually-hidden" aria-hidden="true">
                                <label for="c-website">Website</label>
                                <input type="text" id="c-website" name="website" tabindex="-1" autocomplete="off">
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="c-name">Name *</label>
                                    <input id="c-name" name="name" value="{{ old('name') }}" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="c-email">Email *</label>
                                    <input id="c-email" type="email" name="email" value="{{ old('email') }}" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="c-phone">Phone / WhatsApp</label>
                                    <input id="c-phone" name="phone" value="{{ old('phone') }}" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="c-subject">What is this about?</label>
                                    <select id="c-subject" name="subject" class="form-select">
                                        <option value="">General enquiry</option>
                                        @foreach ($services as $title)
                                            <option value="{{ $title }}" @selected(old('subject') === $title)>{{ $title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label" for="c-message">Message *</label>
                                    <textarea id="c-message" name="message" rows="6" class="form-control" required
                                              placeholder="How can we help?">{{ old('message') }}</textarea>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-primary btn-lg px-4">Send message</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="d-grid gap-3">
                    <div class="card">
                        <div class="card-body">
                            <h2 class="h5 mb-3">Contact details</h2>

                            @if (! empty($contactInfo['contact.address']))
                                <p class="mb-2">
                                    <i class="ti ti-map-pin me-2 text-secondary"></i>{{ $contactInfo['contact.address'] }}
                                </p>
                            @endif
                            @if (! empty($contactInfo['contact.phone']))
                                <p class="mb-2">
                                    <i class="ti ti-phone me-2 text-secondary"></i>
                                    <a href="tel:{{ $contactInfo['contact.phone'] }}" class="text-decoration-none">
                                        {{ $contactInfo['contact.phone'] }}
                                    </a>
                                </p>
                            @endif
                            @if (! empty($contactInfo['contact.whatsapp']))
                                <p class="mb-2">
                                    <i class="ti ti-brand-whatsapp me-2 text-secondary"></i>
                                    <a href="https://wa.me/{{ ltrim($contactInfo['contact.whatsapp'], '+') }}"
                                       target="_blank" rel="noopener" class="text-decoration-none">
                                        {{ $contactInfo['contact.whatsapp'] }}
                                    </a>
                                </p>
                            @endif
                            @if (! empty($contactInfo['contact.email']))
                                <p class="mb-0">
                                    <i class="ti ti-mail me-2 text-secondary"></i>
                                    <a href="mailto:{{ $contactInfo['contact.email'] }}" class="text-decoration-none">
                                        {{ $contactInfo['contact.email'] }}
                                    </a>
                                </p>
                            @endif

                            @if (! empty($contactInfo['contact.business_hours']))
                                <hr>
                                <p class="mb-0 small text-secondary">
                                    <i class="ti ti-clock me-2"></i>{!! nl2br(e($contactInfo['contact.business_hours'])) !!}
                                </p>
                            @endif
                        </div>
                    </div>

                    @if (filled($contactInfo['contact.social'] ?? []))
                        <div class="card">
                            <div class="card-body">
                                <h2 class="h6 text-secondary text-uppercase mb-3" style="lindu-micro-label">
                                    Follow us
                                </h2>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($contactInfo['contact.social'] as $network => $url)
                                        <a href="{{ $url }}" target="_blank" rel="noopener nofollow"
                                           class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                                            <i class="ti ti-brand-{{ $network }}"></i> {{ ucfirst($network) }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    @if (! empty($contactInfo['contact.map_embed']))
                        <div class="card overflow-hidden">
                            {!! $contactInfo['contact.map_embed'] !!}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </x-site.section>
@endsection
