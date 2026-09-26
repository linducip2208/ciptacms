@extends('admin.layout')
@section('title', 'Sitemap')
@section('crumb', 'SEO / Sitemap')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Sitemap.xml</h2>
        <div class="text-muted">Generated live from published content — <strong>{{ $count }}</strong> URL(s). No cache to bust.</div>
    </div>
    <a href="{{ url('/sitemap.xml') }}" target="_blank" rel="noopener" class="btn btn-outline">Open /sitemap.xml ↗</a>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Generated XML</h3>
        <button class="btn btn-sm btn-outline float-end" onclick="navigator.clipboard.writeText(document.getElementById('xml').textContent)">Copy</button>
    </div>
    <div class="card-body">
        <pre id="xml" style="max-height:560px;overflow:auto;font-size:.75rem;margin:0;white-space:pre-wrap">{{ $xml }}</pre>
    </div>
    <div class="card-footer text-muted small">
        Submit this URL to Google Search Console and Bing Webmaster Tools after each significant content change.
    </div>
</div>
@endsection
