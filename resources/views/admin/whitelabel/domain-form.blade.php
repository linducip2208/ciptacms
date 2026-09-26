@extends('admin.layout')
@section('title', 'Add Domain')
@section('crumb', 'White Label / Domains / New')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('admin.whitelabel.section', 'branding') }}" class="text-muted">← Branding</a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Add a domain</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.whitelabel.domains.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>Domain *</label>
                        <input name="domain" value="{{ old('domain') }}" class="form-control" required placeholder="example.com">
                        <small class="text-muted">
                            Enter the host only. Protocol, <code>www.</code> and any path are stripped automatically.
                        </small>
                    </div>
                    <button class="btn btn-primary">Add domain</button>
                </form>
            </div>
            <div class="card-footer text-muted small">
                Verification is deliberately manual in this build: point a DNS <code>A</code> record at this
                installation, then mark the domain verified once it resolves.
            </div>
        </div>
    </div>
</div>
@endsection
