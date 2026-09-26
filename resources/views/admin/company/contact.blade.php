@extends('admin.layout')
@section('title', 'Contact Settings')
@section('crumb', 'Company Profile / Contact')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Contact Information</h2>
        <div class="text-muted">Used on <a href="{{ route('site.contact') }}" target="_blank" rel="noopener">/contact</a> and in the footer.</div>
    </div>
    <a href="{{ route('site.contact') }}" target="_blank" rel="noopener" class="btn btn-outline">Preview ↗</a>
</div>

<form method="POST" action="{{ route('admin.company.contact.save') }}">
    @csrf
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="contact.address" rows="3" class="form-control">{{ old('contact.address', $contact['contact.address']) }}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Phone</label>
                                <input name="contact.phone" value="{{ old('contact.phone', $contact['contact.phone']) }}" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>WhatsApp number</label>
                                <input name="contact.whatsapp" value="{{ old('contact.whatsapp', $contact['contact.whatsapp']) }}" class="form-control" placeholder="+6281234567890">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="contact.email" value="{{ old('contact.email', $contact['contact.email']) }}" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Business hours</label>
                        <textarea name="contact.business_hours" rows="3" class="form-control" placeholder="Monday - Friday&#10;09:00 - 17:00">{{ old('contact.business_hours', $contact['contact.business_hours']) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Google Maps embed</label>
                        <textarea name="contact.map_embed" rows="5" class="form-control font-monospace" placeholder="&lt;iframe src=&quot;https://www.google.com/maps/embed?...&quot;&gt;&lt;/iframe&gt;">{{ old('contact.map_embed', $contact['contact.map_embed']) }}</textarea>
                        <small class="text-muted">
                            Paste the embed <code>&lt;iframe&gt;</code> from Google Maps → Share → Embed a map.
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Social links</h3></div>
                <div class="card-body">
                    <label class="form-label">One per line as <code>network|url</code></label>
                    <textarea name="contact.social" rows="9" class="form-control font-monospace">@php
                        $old = old('contact.social', $contact['contact.social']);
                        echo is_array($old) ? implode("\n", array_map(fn($k, $v) => $k.'|'.$v, array_keys($old), $old)) : $old;
                    @endphp</textarea>
                    <small class="text-muted">Example: <code>linkedin|https://linkedin.com/company/acme</code></small>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary">Save contact settings</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
