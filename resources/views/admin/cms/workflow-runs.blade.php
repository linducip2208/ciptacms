@extends('admin.layout')
@section('title', 'Workflow Runs')
@section('crumb', 'Workflow / Execution Logs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form class="d-flex gap-2" method="GET">
        <select name="workflow_id" class="form-control" onchange="this.form.submit()">
            <option value="">All workflows</option>
            @foreach($workflows as $w)
                <option value="{{ $w->id }}" @selected((int) request('workflow_id') === $w->id)>{{ $w->name }}</option>
            @endforeach
        </select>
        <select name="status" class="form-control" onchange="this.form.submit()">
            <option value="">All status</option>
            @foreach(['completed' => 'Completed', 'skipped' => 'Skipped', 'failed' => 'Failed', 'running' => 'Running'] as $k => $l)
                <option value="{{ $k }}" @selected($status === $k)>{{ $l }}</option>
            @endforeach
        </select>
    </form>
    <a href="{{ route('admin.cms.workflows.index') }}" class="btn btn-outline">← Workflows</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>#</th><th>Workflow</th><th>Status</th><th>Log</th><th>Duration</th><th>When</th><th></th></tr></thead>
            <tbody>
                @forelse($rows as $run)
                    <tr>
                        <td class="text-muted">{{ $run->id }}</td>
                        <td>{{ $run->workflow?->name ?? '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $run->status === 'completed' ? 'green' : ($run->status === 'failed' ? 'red' : ($run->status === 'skipped' ? 'secondary' : 'orange')) }}">
                                {{ $run->status }}
                            </span>
                        </td>
                        <td class="text-muted text-xs" style="max-width:280px">
                            <div class="text-truncate-cell">{{ $run->log ?: '—' }}</div>
                        </td>
                        <td class="text-muted text-xs">
                            @if($run->started_at && $run->finished_at)
                                {{ round($run->started_at->diffInMilliseconds($run->finished_at)) }} ms
                            @else — @endif
                        </td>
                        <td class="text-muted small">{{ $run->created_at->diffForHumans() }}</td>
                        <td>
                            <div class="d-flex gap-2">
                                <a class="text-indigo-600" href="{{ route('admin.cms.workflows.runs.show', $run) }}">Detail</a>
                                @if($run->status === 'failed')
                                    <form method="POST" action="{{ route('admin.cms.workflows.runs.retry', $run) }}">@csrf
                                        <button class="text-slate-600">Retry</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">
                        No runs recorded. A run is logged every time a workflow's trigger fires.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())<div class="card-footer">{{ $rows->links() }}</div>@endif
</div>
@endsection
