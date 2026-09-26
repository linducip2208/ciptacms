@extends('admin.layout')
@section('title', 'Submissions')
@section('crumb', 'Forms / Submissions')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form class="d-flex gap-2" method="GET">
        <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search submissions…" style="min-width:220px">
        <select name="form_id" class="form-control" onchange="this.form.submit()">
            <option value="">All forms</option>
            @foreach($forms as $f)
                <option value="{{ $f->id }}" @selected((int) request('form_id') === $f->id)>{{ $f->title }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.cms.submissions.export', ['format' => 'csv'] + request()->only('form_id')) }}" class="btn btn-outline">Export CSV</a>
        <a href="{{ route('admin.cms.submissions.export', ['format' => 'json'] + request()->only('form_id')) }}" class="btn btn-outline">Export JSON</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Form</th><th>Data</th><th>IP</th><th>Received</th><th></th></tr></thead>
            <tbody>
                @forelse($rows as $s)
                    <tr>
                        <td>{{ $s->form?->title ?? '—' }}</td>
                        <td style="max-width:400px">
                            <div class="text-truncate-cell">{{ json_encode($s->data, JSON_UNESCAPED_UNICODE) }}</div>
                        </td>
                        <td class="text-muted text-xs">{{ $s->ip ?: '—' }}</td>
                        <td class="text-muted small">{{ $s->created_at?->diffForHumans() }}</td>
                        <td>
                            <div class="d-flex gap-2">
                                <a class="text-indigo-600" href="{{ route('admin.cms.submissions.show', $s) }}">View</a>
                                <form method="POST" action="{{ route('admin.cms.submissions.destroy', $s) }}"
                                      onsubmit="return confirm('Delete this submission?')">@csrf @method('DELETE')
                                    <button class="text-rose-600">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No submissions yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())<div class="card-footer">{{ $rows->links() }}</div>@endif
</div>
@endsection
