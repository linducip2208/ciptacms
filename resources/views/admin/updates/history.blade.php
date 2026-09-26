@extends('admin.layout')
@section('title', 'Update History')
@section('crumb', 'Updates / History')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <form class="d-flex gap-2" method="GET">
        <select name="type" class="form-control" onchange="this.form.submit()">
            <option value="">All types</option>
            @foreach(['core', 'module', 'plugin', 'theme'] as $t)
                <option value="{{ $t }}" @selected($type === $t)>{{ $t }}</option>
            @endforeach
        </select>
    </form>
    <a href="{{ route('admin.updates.index') }}" class="btn btn-outline">← Updates</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Type</th><th>Package</th><th>From → To</th><th>Status</th><th>Log</th><th>Started</th><th>Finished</th></tr></thead>
            <tbody>
                @forelse($rows as $l)
                    <tr>
                        <td><span class="badge">{{ $l->type }}</span></td>
                        <td><code>{{ $l->slug }}</code></td>
                        <td class="text-muted text-xs">{{ $l->from_version }} → {{ $l->to_version }}</td>
                        <td>
                            <span class="badge bg-{{ $l->status === 'completed' ? 'green' : ($l->status === 'failed' ? 'red' : 'secondary') }}">
                                {{ $l->status }}
                            </span>
                        </td>
                        <td class="text-muted text-xs" style="max-width:340px">
                            <div class="text-truncate-cell">{{ $l->log ?: '—' }}</div>
                        </td>
                        <td class="text-muted small">{{ optional($l->started_at)->diffForHumans() ?? '—' }}</td>
                        <td class="text-muted small">{{ optional($l->finished_at)->diffForHumans() ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No updates applied yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())<div class="card-footer">{{ $rows->links() }}</div>@endif
</div>
@endsection
