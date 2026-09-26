@extends('admin.layout')
@section('title', 'Cache')
@section('crumb', 'System / Cache')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Cache</h2>
        <div class="text-muted">Driver: <code>{{ $driver }}</code></div>
    </div>
    <form method="POST" action="{{ route('admin.system.cache.flush') }}">
        @csrf
        <input type="hidden" name="target" value="all">
        <button class="btn btn-primary">Clear everything</button>
    </form>
</div>

<div class="row g-3 mb-3">
    @foreach([
        ['key' => 'config', 'label' => 'Config cache', 'state' => $configCached],
        ['key' => 'route', 'label' => 'Route cache', 'state' => $routeCached],
        ['key' => 'view', 'label' => 'Compiled views', 'state' => $viewCached],
        ['key' => 'event', 'label' => 'Event cache', 'state' => $eventsCached],
    ] as $row)
        <div class="col-md-3 col-lg-2">
            <div class="card"><div class="card-body text-center p-3">
                <div class="badge bg-{{ $row['state'] ? 'green' : 'secondary' }} mb-2">{{ $row['state'] ? 'cached' : 'not cached' }}</div>
                <form method="POST" action="{{ route('admin.system.cache.flush') }}">
                    @csrf
                    <input type="hidden" name="target" value="{{ $row['key'] }}">
                    <button class="btn btn-sm btn-outline w-100">Clear</button>
                </form>
            </div></div>
        </div>
    @endforeach
    <div class="col-md-3 col-lg-2">
        <div class="card"><div class="card-body text-center p-3">
            <div class="badge bg-blue mb-2">{{ $menuKeys }} keys</div>
            <form method="POST" action="{{ route('admin.system.cache.flush') }}">
                @csrf
                <input type="hidden" name="target" value="app">
                <button class="btn btn-sm btn-outline w-100">Clear app cache</button>
            </form>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Optimize for production</h3></div>
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="text-muted">
            Runs <code>config:cache</code>, <code>route:cache</code>, <code>view:cache</code> and <code>event:cache</code>.
            Run this after every deployment.
        </span>
        <form method="POST" action="{{ route('admin.system.optimize') }}">
            @csrf
            <button class="btn btn-primary">Run optimize</button>
        </form>
    </div>
</div>
@endsection
