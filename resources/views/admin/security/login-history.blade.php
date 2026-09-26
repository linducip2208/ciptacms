@extends('admin.layout')
@section('title', 'Login History')
@section('crumb', 'Users / Login History')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form class="d-flex gap-2" method="GET">
        <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Email or IP…" style="min-width:220px">
        <select name="successful" class="form-control" onchange="this.form.submit()">
            <option value="">All attempts</option>
            <option value="1" @selected(request('successful') === '1')>Successful</option>
            <option value="0" @selected(request('successful') === '0')>Failed</option>
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>
    <a href="{{ route('admin.sessions.index') }}" class="btn btn-outline">Sessions</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card"><div class="card-body text-center">
        <div class="h1 mb-0">{{ $stats['total'] }}</div>
        <div class="text-muted text-uppercase" style="font-size:.7rem">Total attempts</div>
    </div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body text-center">
        <div class="h1 mb-0 text-rose-600">{{ $stats['failed'] }}</div>
        <div class="text-muted text-uppercase" style="font-size:.7rem">Failed</div>
    </div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body text-center">
        <div class="h1 mb-0">{{ $stats['today'] }}</div>
        <div class="text-muted text-uppercase" style="font-size:.7rem">Today</div>
    </div></div></div>
</div>

@if($recentFailures->isNotEmpty())
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title mb-0">Recent failures</h3>
            <div class="card-subtitle">A burst from one address is the usual sign of a credential-stuffing attempt.</div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead><tr><th>Email</th><th>IP</th><th>Agent</th><th>When</th></tr></thead>
                <tbody>
                    @foreach($recentFailures as $f)
                        <tr>
                            <td>{{ $f->email }}</td>
                            <td><code>{{ $f->ip_address }}</code></td>
                            <td class="text-muted text-xs" style="max-width:320px">
                                <div class="text-truncate-cell">{{ $f->user_agent }}</div>
                            </td>
                            <td class="text-muted small">{{ $f->created_at?->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>User</th><th>Email</th><th>IP</th><th>Result</th><th>Agent</th><th>When</th></tr></thead>
            <tbody>
                @forelse($rows as $h)
                    <tr>
                        <td>{{ $h->user?->name ?? '—' }}</td>
                        <td>{{ $h->email }}</td>
                        <td><code>{{ $h->ip_address }}</code></td>
                        <td>
                            <span class="badge bg-{{ $h->successful ? 'green' : 'red' }}">
                                {{ $h->successful ? 'success' : 'failed' }}
                            </span>
                        </td>
                        <td class="text-muted text-xs" style="max-width:280px">
                            <div class="text-truncate-cell">{{ $h->user_agent }}</div>
                        </td>
                        <td class="text-muted small">{{ $h->created_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No login attempts recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())<div class="card-footer">{{ $rows->links() }}</div>@endif
</div>
@endsection
