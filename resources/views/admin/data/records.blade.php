@extends('admin.layout')
@section('title', 'Records')
@section('crumb', 'Data / Records')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Content records</h2>
        <div class="text-muted">Pick a content type to manage its records.</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.cms.import') }}" class="btn btn-outline">Import</a>
        <a href="{{ route('admin.cms.export') }}" class="btn btn-outline">Export</a>
    </div>
</div>

<div class="row g-3">
    @forelse($types as $ct)
        <div class="col-md-6 col-lg-4">
            <a href="{{ route('admin.cms.records.list', $ct) }}" class="card text-decoration-none d-block h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h3 class="card-title mb-1">{{ $ct->icon }} {{ $ct->name }}</h3>
                            <code class="text-muted">{{ $ct->slug }}</code>
                        </div>
                        <span class="badge bg-{{ $ct->records_count ? 'blue' : 'secondary' }}">{{ $ct->records_count }}</span>
                    </div>
                    <p class="text-muted small mb-0 mt-2">
                        {{ count($ct->fields ?? []) }} field(s){{ $ct->is_api_enabled ? ' · API enabled' : '' }}
                    </p>
                </div>
            </a>
        </div>
    @empty
        <div class="col-12">
            <div class="card"><div class="card-body text-center text-muted py-5">
                No content types defined yet. <a href="{{ route('admin.cms.types.create') }}">Create one</a> first.
            </div></div>
        </div>
    @endforelse
</div>
@endsection
