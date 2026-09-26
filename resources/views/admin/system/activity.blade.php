@extends('admin.layout')
@section('title', 'Activity Log')
@section('crumb', 'System / Activity Log')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <form class="d-flex gap-2" method="GET">
        <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search action or description…" style="min-width:240px">
        <select name="action" class="form-control" onchange="this.form.submit()">
            <option value="">All actions</option>
            @foreach($actions as $a)
                <option value="{{ $a }}" @selected(request('action') === $a)>{{ $a }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>
    <a href="{{ route('admin.system.audits') }}" class="btn btn-outline">Audit log</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Action</th><th>Description</th><th>User</th><th>Channel</th><th>IP</th><th>When</th></tr></thead>
            <tbody>
                @forelse($rows as $a)
                    <tr>
                        <td><code class="text-xs">{{ $a->action }}</code></td>
                        <td>
                            {{ $a->description }}
                            @if($a->subject_type)
                                <div class="text-muted text-xs">{{ class_basename($a->subject_type) }} #{{ $a->subject_id }}</div>
                            @endif
                        </td>
                        <td>{{ $a->user?->name ?? 'system' }}</td>
                        <td><span class="badge">{{ $a->channel }}</span></td>
                        <td class="text-muted text-xs">{{ $a->ip }}</td>
                        <td class="text-muted small">{{ $a->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">
                        No activity recorded yet. Write to it with
                        <code>activity($action, $description)</code>.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())<div class="card-footer">{{ $rows->links() }}</div>@endif
</div>
@endsection
