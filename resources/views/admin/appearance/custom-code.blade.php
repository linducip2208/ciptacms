@extends('admin.layout')
@section('title', 'Custom CSS / JS')
@section('crumb', 'Appearance / Custom CSS & JS')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Custom code</h2>
        <div class="text-muted">Injected into every public page. Also mirrored to Branding so the White Label panel and the theme share one source of truth.</div>
    </div>
    <a href="{{ url('/') }}" target="_blank" rel="noopener" class="btn btn-outline">View site ↗</a>
</div>

<div class="alert alert-warning">
    Custom code runs with full page privileges. Only paste snippets you wrote or trust — this is
    deliberately not sandboxed.
</div>

<form method="POST" action="{{ route('admin.appearance.custom-code.save') }}">
    @csrf
    <div class="row g-3">
        @foreach([
            ['code.custom_css', 'Custom CSS', 'Injected inside a <code>&lt;style&gt;</code> in <code>&lt;head&gt;</code>.', 12],
            ['code.custom_js', 'Custom JavaScript', 'Injected before <code>&lt;/body&gt;</code>. Runs on every page.', 12],
            ['code.custom_head', 'Extra <head> markup', 'Verification tags, pixel snippets, preconnect hints.', 6],
            ['code.custom_footer', 'Extra footer markup', 'Chat widgets, analytics, anything after the footer.', 6],
        ] as [$key, $label, $help, $col])
            <div class="col-lg-{{ $col }}">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">{{ $label }}</h3></div>
                    <div class="card-body">
                        <textarea name="options[{{ $key }}]" rows="8" class="form-control font-monospace"
                                  placeholder="{{ $help }}">{{ old('options['.$key.']', $options[$key] ?? setting(str_replace('code.', 'branding.', $key), '')) }}</textarea>
                        <small class="text-muted d-block mt-2">{!! $help !!}</small>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="card mt-3">
        <div class="card-body d-flex justify-content-between align-items-center">
            <span class="text-muted">Saved values are read by <code>resources/views/site/layout.blade.php</code> on every request.</span>
            <button class="btn btn-primary">Save custom code</button>
        </div>
    </div>
</form>
@endsection
