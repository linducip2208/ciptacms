@extends('admin.layout')
@section('title', 'Notification Delivery Log')
@section('crumb', 'Notifications / Delivery Log')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <form class="d-flex gap-2" method="GET">
        <select name="status" class="form-control" onchange="this.form.submit()">
            <option value="">All status</option>
            @foreach(['pending' => 'Pending', 'sent' => 'Sent', 'failed' => 'Failed'] as $k => $l)
                <option value="{{ $k }}" @selected($status === $k)>{{ $l }}</option>
            @endforeach
        </select>
    </form>
    <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline">← Notifications</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>#</th><th>Channel</th><th>Recipient</th><th>Subject</th><th>Template</th><th>Status</th><th>Error</th><th>When</th><th></th></tr></thead>
            <tbody>
                @forelse($rows as $d)
                    <tr>
                        <td class="text-muted">{{ $d->id }}</td>
                        <td><span class="badge">{{ $d->channel }}</span></td>
                        <td>{{ $d->recipient }}</td>
                        <td class="text-muted small">{{ \Illuminate\Support\Str::limit($d->subject, 40) }}</td>
                        <td class="text-muted small">{{ $d->template?->slug ?? '—' }}</td>
                        <td><span class="badge bg-{{ $d->status === 'sent' ? 'green' : ($d->status === 'failed' ? 'red' : 'secondary') }}">{{ $d->status }}</span></td>
                        <td class="text-rose-600 text-xs" style="max-width:220px"><div class="text-truncate-cell">{{ $d->error ?? '—' }}</div></td>
                        <td class="text-muted small">{{ $d->created_at->diffForHumans() }}</td>
                        <td>
                            @if($d->status === 'failed')
                                <form method="POST" action="{{ route('admin.notifications.deliveries.retry', $d) }}">@csrf
                                    <button class="text-indigo-600">Retry</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">Nothing sent yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())<div class="card-footer">{{ $rows->links() }}</div>@endif
</div>
@endsection
