@extends('admin.layout')
@section('title', 'Header')
@section('crumb', 'Appearance / Header')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Header</h2>
        <div class="text-muted">Logo, sticky behaviour and the header call-to-action.</div>
    </div>
    <a href="{{ url('/') }}" target="_blank" rel="noopener" class="btn btn-outline">View site ↗</a>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <form method="POST" action="{{ route('admin.appearance.header.save') }}">
            @csrf
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Header logo URL</label>
                            <input name="options[header.logo]" value="{{ old('options[header.logo]', $options['header.logo'] ?? setting('branding.logo', '')) }}" class="form-control">
                            <small class="text-muted">Falls back to Branding → Logo.</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Height</label>
                            <input name="options[header.height]" value="{{ old('options[header.height]', $options['header.height'] ?? '68px') }}" class="form-control">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <label class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="options[header.sticky]" value="1"
                                       @checked(old('options[header.sticky]', $options['header.sticky'] ?? '1') === '1')>
                                <span class="form-check-label">Sticky on scroll</span>
                            </label>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">CTA label</label>
                            <input name="options[header.cta_label]" value="{{ old('options[header.cta_label]', $options['header.cta_label'] ?? '') }}" class="form-control" placeholder="Contact us">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">CTA URL</label>
                            <input name="options[header.cta_url]" value="{{ old('options[header.cta_url]', $options['header.cta_url'] ?? '/contact') }}" class="form-control">
                        </div>
                        <div class="col-md-5 d-flex align-items-end">
                            <label class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="options[header.show_search]" value="1"
                                       @checked(old('options[header.show_search]', $options['header.show_search'] ?? '0') === '1')>
                                <span class="form-check-label">Show search box</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-right"><button class="btn btn-primary">Save header</button></div>
            </div>
        </form>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Navigation menu</h3></div>
            <div class="card-body">
                <p class="text-muted small">
                    The header renders items from the <code>primary</code> menu location.
                    Manage entries under <a href="{{ route('admin.menus.index', ['location' => 'primary']) }}">Appearance → Menus</a>.
                </p>
                @if($menus->isNotEmpty())
                    <ul style="list-style:none;padding:0;margin:0;display:grid;gap:8px">
                        @foreach($menus as $m)
                            <li class="d-flex justify-content-between align-items-center border rounded px-2 py-1">
                                <span>{{ $m->icon ? '' : '' }}{{ $m->title }}</span>
                                <code class="text-xs">{{ $m->url }}</code>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted mb-0">No primary menu items yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
