@extends('admin.layout')
@section('title', 'Revisions')
@section('crumb', 'Content / Revisions')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Revisions</h2>
        <div class="text-muted">A snapshot is taken every time a page is saved. Restoring one snapshots the current state first.</div>
    </div>
    <form class="d-flex gap-2" method="GET">
        <select name="page_id" class="form-control" onchange="this.form.submit()">
            <option value="">All pages</option>
            @foreach($pages as $p)
                <option value="{{ $p->id }}" @selected((int) request('page_id') === $p->id)>{{ $p->title }}</option>
            @endforeach
        </select>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Page</th><th>Saved by</th><th>Title at snapshot</th><th>Status</th><th>When</th><th></th></tr></thead>
            <tbody>
                @forelse($rows as $rev)
                    <tr>
                        <td>
                            <b>{{ $rev->data['title'] ?? '—' }}</b>
                            <div class="text-muted text-xs">#{{ $rev->page_id }}</div>
                        </td>
                        <td>{{ $rev->user?->name ?? 'system' }}</td>
                        <td class="text-muted small">{{ \Illuminate\Support\Str::limit((string) ($rev->data['title'] ?? ''), 50) }}</td>
                        <td><span class="badge">{{ $rev->data['status'] ?? '—' }}</span></td>
                        <td class="text-muted small">{{ $rev->created_at->diffForHumans() }}</td>
                        <td>
                            <div class="d-flex gap-2">
                                <form method="POST" action="{{ route('admin.cms.revisions.restore', $rev) }}"
                                      onsubmit="return confirm('Restore this snapshot? The current version is saved first.')">
                                    @csrf
                                    <button class="text-indigo-600">Restore</button>
                                </form>
                                <form method="POST" action="{{ route('admin.cms.revisions.destroy', $rev) }}"
                                      onsubmit="return confirm('Delete this snapshot?')">@csrf @method('DELETE')
                                    <button class="text-rose-600">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No snapshots yet. Save a page twice to create one.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())<div class="card-footer">{{ $rows->links() }}</div>@endif
</div>
@endsection
