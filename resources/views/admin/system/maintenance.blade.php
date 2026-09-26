@extends('admin.layout')
@section('title', 'Maintenance')
@section('crumb', 'System / Maintenance')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Maintenance mode</h2>
        <div class="text-muted">Takes the whole site offline except for the bypass IPs below.</div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Current state</h3></div>
            <div class="card-body">
                <div class="mb-3">
                    <span class="badge bg-{{ $enabled ? 'red' : 'green' }}" style="font-size:.9rem">
                        {{ $enabled ? 'MAINTENANCE ON' : 'SITE IS LIVE' }}
                    </span>
                </div>
                <form method="POST" action="{{ route('admin.system.maintenance.toggle') }}"
                      onsubmit="return confirm('{{ $enabled ? 'Bring the site back online' : 'Take the site offline' }}?')">
                    @csrf
                    <input type="hidden" name="enabled" value="{{ $enabled ? 0 : 1 }}">
                    <button class="btn {{ $enabled ? 'btn-primary' : 'btn-outline text-rose-600' }}">
                        {{ $enabled ? 'Bring site online' : 'Enable maintenance mode' }}
                    </button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title">Bypass IPs</h3></div>
            <div class="card-body">
                @if($allowedIps)
                    <ul style="list-style:none;padding:0;margin:0;display:grid;gap:6px">
                        @foreach($allowedIps as $ip)
                            <li><code>{{ $ip }}</code></li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted mb-0">No bypass IPs configured — nobody can reach the site during maintenance.</p>
                @endif
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.settings.tab', 'maintenance') }}" class="btn btn-outline btn-sm">Edit maintenance settings</a>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Before you go live</h3></div>
            <div class="card-body">
                <ol style="padding-left:20px;margin:0;color:#475569">
                    <li style="margin-bottom:8px">Run <code>php artisan optimize</code>.</li>
                    <li style="margin-bottom:8px">Set <code>APP_DEBUG=false</code> in <code>.env</code>.</li>
                    <li style="margin-bottom:8px">Confirm <code>APP_URL</code> matches the real domain.</li>
                    <li style="margin-bottom:8px">Take a backup from <a href="{{ route('admin.backups.index') }}">System → Backup</a>.</li>
                    <li style="margin-bottom:8px">Set up the cron entry from <a href="{{ route('admin.system.schedule') }}">Scheduled Tasks</a>.</li>
                    <li>Check <a href="{{ route('admin.health') }}">System Health</a> for any failing check.</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection
