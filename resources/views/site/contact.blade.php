@extends('site.layout')
@section('title', $seo['title'] ?? 'Contact Us')

@section('content')
<div class="wrap">
    {!! app(\App\Core\Services\SeoService::class)->breadcrumb([
        ['name' => 'Home', 'url' => route('site.home')],
        ['name' => 'Contact'],
    ]) !!}
    <div class="sec-head">
        <h2 class="page-title">Get In Touch</h2>
        <p>{{ setting('general.contact_intro', 'We would love to hear from you.') }}</p>
    </div>
</div>

<section style="padding-top:0">
    <div class="wrap">
        <div class="grid" style="grid-template-columns:1fr 380px;gap:36px;align-items:start">
            <div class="card">
                <h3 style="margin-top:0">Send us a message</h3>
                <form method="POST" action="{{ route('site.contact.submit') }}">
                    @csrf
                    <div style="position:absolute;left:-9999px" aria-hidden="true">
                        <label for="website">Website</label>
                        <input id="website" name="website" tabindex="-1" autocomplete="off">
                    </div>
                    <div class="grid g2">
                        <div class="form-row">
                            <label for="c-name">Name *</label>
                            <input id="c-name" name="name" value="{{ old('name') }}" required>
                        </div>
                        <div class="form-row">
                            <label for="c-email">Email *</label>
                            <input id="c-email" type="email" name="email" value="{{ old('email') }}" required>
                        </div>
                    </div>
                    <div class="grid g2">
                        <div class="form-row">
                            <label for="c-phone">Phone / WhatsApp</label>
                            <input id="c-phone" name="phone" value="{{ old('phone') }}">
                        </div>
                        <div class="form-row">
                            <label for="c-subject">Subject</label>
                            <select id="c-subject" name="subject">
                                <option value="">General enquiry</option>
                                @foreach($services as $title)
                                    <option value="{{ $title }}" @selected(old('subject') === $title)>{{ $title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <label for="c-message">Message *</label>
                        <textarea id="c-message" name="message" required placeholder="How can we help?">{{ old('message') }}</textarea>
                    </div>
                    <button class="btn" type="submit">Send Message</button>
                </form>
            </div>

            <aside style="display:grid;gap:16px">
                <div class="card">
                    <h3 style="margin-top:0">Contact details</h3>
                    @if($contactInfo['contact.address'])
                        <p style="margin:0 0 10px"><b>Address</b><br>{{ $contactInfo['contact.address'] }}</p>
                    @endif
                    @if($contactInfo['contact.phone'])
                        <p style="margin:0 0 10px"><b>Phone</b><br><a href="tel:{{ $contactInfo['contact.phone'] }}">{{ $contactInfo['contact.phone'] }}</a></p>
                    @endif
                    @if($contactInfo['contact.whatsapp'])
                        <p style="margin:0 0 10px"><b>WhatsApp</b><br>
                            <a href="https://wa.me/{{ ltrim($contactInfo['contact.whatsapp'], '+') }}" target="_blank" rel="noopener">{{ $contactInfo['contact.whatsapp'] }}</a>
                        </p>
                    @endif
                    @if($contactInfo['contact.email'])
                        <p style="margin:0 0 10px"><b>Email</b><br><a href="mailto:{{ $contactInfo['contact.email'] }}">{{ $contactInfo['contact.email'] }}</a></p>
                    @endif
                    @if($contactInfo['contact.business_hours'])
                        <p style="margin:0"><b>Business hours</b><br>{!! nl2br(e($contactInfo['contact.business_hours'])) !!}</p>
                    @endif
                    @if($contactInfo['contact.social'])
                        <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap">
                            @foreach($contactInfo['contact.social'] as $net => $url)
                                <a href="{{ $url }}" target="_blank" rel="noopener" class="badge">{{ ucfirst($net) }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if($contactInfo['contact.map_embed'])
                    <div class="card" style="padding:0;overflow:hidden">
                        {!! $contactInfo['contact.map_embed'] !!}
                    </div>
                @endif
            </aside>
        </div>
    </div>
</section>
@endsection
