@extends('admin.layout')
@section('title', 'Schema')
@section('crumb', 'SEO / Schema')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Structured data</h2>
        <div class="text-muted">JSON-LD emitted on public pages. Validate with Google's Rich Results Test.</div>
    </div>
    <a href="{{ route('admin.settings.tab', 'seo') }}" class="btn btn-outline">Edit SEO settings</a>
</div>

@if(!$organization)
    <div class="alert alert-warning">
        No Organization schema is being emitted. Fill in <code>seo.schema_organization</code> under
        <strong>Settings → SEO</strong>, or set at least a logo and description.
    </div>
@endif

<div class="row g-3">
    @foreach($samples as $name => $json)
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">{{ $name }}</h3>
                    <button class="btn btn-sm btn-outline" onclick="navigator.clipboard.writeText(document.getElementById('json-{{ $loop->index }}').textContent)">Copy</button>
                </div>
                <div class="card-body">
                    @if($json)
                        <pre id="json-{{ $loop->index }}" style="max-height:340px;overflow:auto;font-size:.75rem;margin:0;white-space:pre-wrap">{{ $json }}</pre>
                    @else
                        <p class="text-muted mb-0">Not configured — this block is not emitted.</p>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card mt-3">
    <div class="card-header"><h3 class="card-title">Per-record schema</h3></div>
    <div class="card-body text-muted">
        Individual pages, posts, services, products, portfolio items and careers can carry their own JSON-LD
        via <a href="{{ route('admin.seo.index') }}">SEO → Edit</a>. A per-record <code>schema</code> value
        replaces the default Organization block on that page.
    </div>
</div>
@endsection
