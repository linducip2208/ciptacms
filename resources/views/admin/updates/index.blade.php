@extends('admin.layout')
@section('title', 'Updates')
@section('crumb', 'Updates')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h2 class="mb-1">System updates</h2>
        <div class="text-muted">
            Channel <code>{{ config('lindu.updates.channel', 'stable') }}</code>.
            @if($core['source'] === 'remote')
                Remote manifest is configured.
            @else
                No update endpoint configured — versions below are the installed ones.
                Set <code>LINDU_UPDATE_URL</code> to check against a release server.
            @endif
        </div>
    </div>
    <form method="POST" action="{{ route('admin.updates.check') }}">
        @csrf
        <input type="hidden" name="type" value="core">
        <button class="btn btn-primary">Check for updates</button>
    </form>
</div>

<div class="card mb-3">
    <div class="card-header"><h3 class="card-title">Core</h3></div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Component</th><th>Installed</th><th>Latest</th><th>State</th><th></th></tr></thead>
            <tbody>
                <tr>
                    <td><b>Lindu CMS</b><div class="text-muted text-xs">{{ config('lindu.name') }}</div></td>
                    <td><code>v{{ $core['current'] }}</code></td>
                    <td><code>v{{ $core['latest'] }}</code></td>
                    <td>
                        @if($core['update_available'])
                            <span class="badge bg-orange">update available</span>
                        @else
                            <span class="badge bg-green">up to date</span>
                        @endif
                    </td>
                    <td>
                        @if($core['update_available'])
                            <form method="POST" action="{{ route('admin.updates.apply') }}"
                                  onsubmit="return confirm('This takes a full backup, runs migrations and clears caches. Continue?')">
                                @csrf
                                <input type="hidden" name="type" value="core">
                                <input type="hidden" name="slug" value="lindu">
                                <input type="hidden" name="to_version" value="{{ $core['latest'] }}">
                                <label class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="confirm" value="1" required>
                                    <span class="form-check-label text-xs">I have a backup</span>
                                </label>
                                <button class="btn btn-sm btn-primary">Apply</button>
                            </form>
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="card-footer text-muted small">
        <b>No remote code is ever executed.</b> Applying an update takes a backup, runs pending migrations
        and clears derived caches. Package delivery stays with composer/git — see <code>UPDATES.md</code>.
    </div>
</div>

<div class="row g-3 mb-3">
    @foreach([
        ['Modules', 'admin.updates.modules', $modules],
        ['Plugins', 'admin.updates.plugins', $plugins],
        ['Themes', 'admin.updates.themes', $themes],
    ] as [$label, $route, $rows])
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">{{ $label }} ({{ count($rows) }})</h3>
                    <a href="{{ route($route) }}" class="btn btn-sm btn-outline">Details</a>
                </div>
                <div class="table-responsive" style="max-height:280px;overflow:auto">
                    <table class="table table-vcenter card-table">
                        <tbody>
                            @forelse($rows as $row)
                                <tr>
                                    <td>{{ $row['name'] }}</td>
                                    <td><code class="text-xs">v{{ $row['current'] }}</code></td>
                                    <td>
                                        @if($row['update_available'])
                                            <span class="badge bg-orange">update</span>
                                        @else
                                            <span class="badge bg-{{ $row['is_active'] ? 'green' : 'secondary' }}">
                                                {{ $row['is_active'] ? 'active' : 'inactive' }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">None discovered.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Update history</h3>
        <a href="{{ route('admin.updates.history') }}">View all</a>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Type</th><th>Package</th><th>From → To</th><th>Status</th><th>Log</th><th>When</th></tr></thead>
            <tbody>
                @forelse($logs as $l)
                    <tr>
                        <td><span class="badge">{{ $l->type }}</span></td>
                        <td><code>{{ $l->slug }}</code></td>
                        <td class="text-muted text-xs">{{ $l->from_version }} → {{ $l->to_version }}</td>
                        <td>
                            <span class="badge bg-{{ $l->status === 'completed' ? 'green' : ($l->status === 'failed' ? 'red' : 'secondary') }}">
                                {{ $l->status }}
                            </span>
                        </td>
                        <td class="text-muted text-xs" style="max-width:260px">
                            <div class="text-truncate-cell">{{ $l->log }}</div>
                        </td>
                        <td class="text-muted small">{{ $l->created_at?->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No updates applied yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
