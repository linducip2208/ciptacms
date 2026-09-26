@extends('admin.layout')
@section('title', 'robots.txt')
@section('crumb', 'SEO / robots.txt')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">robots.txt</h2>
        <div class="text-muted">Generated from the <code>seo.robots_disallow</code> setting plus the sitemap URL.</div>
    </div>
    <a href="{{ url('/robots.txt') }}" target="_blank" rel="noopener" class="btn btn-outline">Open /robots.txt ↗</a>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Generated file</h3>
                <button class="btn btn-sm btn-outline float-end" onclick="navigator.clipboard.writeText(document.getElementById('txt').textContent)">Copy</button>
            </div>
            <div class="card-body">
                <pre id="txt" style="max-height:420px;overflow:auto;font-size:.8rem;margin:0;white-space:pre-wrap">{{ $robots }}</pre>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Edit the source</h3></div>
            <div class="card-body">
                <p class="text-muted small">Disallow paths, one per line. Saved under <code>seo.robots_disallow</code>.</p>
                <form method="POST" action="{{ route('admin.settings.update') }}">
                    @csrf
                    <input type="hidden" name="tab" value="seo">
                    <textarea name="settings[seo.robots_disallow]" rows="8" class="form-control font-monospace">{{ setting('seo.robots_disallow', "/admin\n/install\n/login") }}</textarea>
                    <button class="btn btn-primary mt-3">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
