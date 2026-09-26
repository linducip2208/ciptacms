@extends('admin.layout')
@section('title', 'Contact Messages')
@section('crumb', 'Company Profile / Messages')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form class="d-flex gap-2" method="GET">
        <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search name, email, subject…" style="min-width:240px">
        <select name="status" class="form-control" onchange="this.form.submit()">
            <option value="">All status</option>
            @foreach(['new' => 'New', 'read' => 'Read', 'replied' => 'Replied', 'archived' => 'Archived'] as $v => $l)
                <option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>
    <a href="{{ route('admin.company.home') }}" class="btn btn-outline">← Company Profile</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>From</th><th>Subject</th><th>Message</th><th>Status</th><th>Received</th><th></th></tr></thead>
            <tbody>
                @forelse($rows as $m)
                    <tr>
                        <td>
                            <b>{{ $m->name }}</b>
                            <div class="text-muted small">{{ $m->email }}{{ $m->phone ? ' · '.$m->phone : '' }}</div>
                        </td>
                        <td>{{ $m->subject ?: '—' }}</td>
                        <td style="max-width:320px"><div class="text-truncate-cell">{{ $m->message }}</div></td>
                        <td>
                            <form method="POST" action="{{ route('admin.company.messages.status', $m) }}" class="d-flex gap-1">
                                @csrf
                                <select name="status" onchange="this.form.submit()" class="form-control form-control-sm">
                                    @foreach(['new' => 'New', 'read' => 'Read', 'replied' => 'Replied', 'archived' => 'Archived'] as $v => $l)
                                        <option value="{{ $v }}" @selected($m->status === $v)>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td class="text-muted small">{{ $m->created_at->diffForHumans() }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.company.messages.destroy', $m) }}"
                                  onsubmit="return confirm('Delete this message?')">@csrf @method('DELETE')
                                <button class="text-rose-600">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No messages yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())<div class="card-footer">{{ $rows->links() }}</div>@endif
</div>
@endsection
