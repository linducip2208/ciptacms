@extends('admin.layout')
@section('title', 'Developer — Hooks')
@section('crumb', 'Developer / Hooks')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Extension hooks</h2>
        <div class="text-muted">Files discovered inside each module, plugin and theme directory. Anything listed here is loaded when the extension is active.</div>
    </div>
</div>

<div class="row g-3">
    @forelse($hooks as $h)
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">{{ $h['name'] }}</h3>
                        <div class="text-muted small"><code>{{ $h['kind'] }}/{{ $h['slug'] }}</code> · v{{ $h['version'] }}</div>
                    </div>
                    <span class="badge bg-{{ $h['active'] ? 'green' : 'secondary' }}">
                        {{ $h['active'] ? 'active' : 'inactive' }}
                    </span>
                </div>
                <div class="table-responsive" style="max-height:280px;overflow:auto">
                    <table class="table table-vcenter card-table">
                        <tbody>
                            @forelse($h['files'] as $file)
                                <tr><td><code class="text-xs">{{ $file }}</code></td></tr>
                            @empty
                                <tr><td class="text-muted text-center py-3">Directory is empty.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card"><div class="card-body text-center text-muted py-5">
                No modules, plugins or themes discovered.
            </div></div>
        </div>
    @endforelse
</div>
@endsection
