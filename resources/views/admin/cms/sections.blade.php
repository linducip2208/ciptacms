@extends('admin.layout')
@section('title', 'Sections')
@section('crumb', 'Page Builder / Sections')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Sections</h2>
        <div class="text-muted">A page is a list of sections. Each section wraps blocks and carries its own background, padding and per-breakpoint visibility.</div>
    </div>
    <a href="{{ route('admin.cms.components.index') }}" class="btn btn-outline">Components</a>
</div>

<div class="row g-3">
    @forelse($catalog as $group)
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title mb-0">{{ ucfirst($group['label']) }}</h3>
                    <span class="badge">{{ $group['count'] }}</span>
                </div>
                <div class="card-body">
                    <p class="text-muted small">These components can be dropped into a section.</p>
                    <div class="d-flex gap-1 flex-wrap">
                        @php
                            $types = collect(\App\Core\Services\BlockLibrary::catalog())
                                ->where('group', $group['key'])
                                ->pluck('label');
                        @endphp
                        @foreach($types as $t)
                            <span class="badge">{{ $t }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="card"><div class="card-body text-muted">No components registered.</div></div></div>
    @endforelse
</div>
@endsection
