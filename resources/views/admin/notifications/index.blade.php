@extends('admin.layout')
@section('title', 'Notifications')
@section('crumb', 'Notifications')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form class="d-flex gap-2" method="GET">
        <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Recipient or subject…" style="min-width:220px">
        <select name="channel" class="form-control" onchange="this.form.submit()">
            <option value="">All channels</option>
            @foreach($channels as $key => $label)
                <option value="{{ $key }}" @selected($channel === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="status" class="form-control" onchange="this.form.submit()">
            <option value="">All status</option>
            @foreach(['pending' => 'Pending', 'sent' => 'Sent', 'failed' => 'Failed'] as $k => $l)
                <option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.notifications.templates') }}" class="btn btn-outline">Templates</a>
        <a href="{{ route('admin.notifications.deliveries') }}" class="btn btn-outline">Delivery log</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body text-center"><div class="h2 mb-0">{{ $byStatus['sent'] ?? 0 }}</div><div class="text-muted text-uppercase" style="font-size:.7rem">Sent</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body text-center"><div class="h2 mb-0">{{ $byStatus['failed'] ?? 0 }}</div><div class="text-muted text-uppercase" style="font-size:.7rem">Failed</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body text-center"><div class="h2 mb-0">{{ $byStatus['pending'] ?? 0 }}</div><div class="text-muted text-uppercase" style="font-size:.7rem">Pending</div></div></div></div>
    <div class="col-md-3">
        <div class="card"><div class="card-body">
            <button class="btn btn-outline w-100" data-bs-toggle="modal" data-bs-target="#test-modal">Send test</button>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Channel</th><th>Recipient</th><th>Subject</th><th>Status</th><th>Attempts</th><th>Sent</th><th></th></tr></thead>
            <tbody>
                @forelse($rows as $d)
                    <tr>
                        <td><span class="badge">{{ $channels[$d->channel] ?? $d->channel }}</span></td>
                        <td>{{ $d->recipient }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($d->subject, 50) }}</td>
                        <td>
                            <span class="badge bg-{{ $d->status === 'sent' ? 'green' : ($d->status === 'failed' ? 'red' : 'secondary') }}">
                                {{ $d->status }}
                            </span>
                            @if($d->error)<div class="text-rose-600 text-xs">{{ \Illuminate\Support\Str::limit($d->error, 60) }}</div>@endif
                        </td>
                        <td>{{ $d->attempts }}</td>
                        <td class="text-muted small">{{ optional($d->sent_at)->diffForHumans() ?? '—' }}</td>
                        <td>
                            @if($d->status === 'failed')
                                <form method="POST" action="{{ route('admin.notifications.deliveries.retry', $d) }}">@csrf
                                    <button class="text-indigo-600">Retry</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No notifications sent yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())<div class="card-footer">{{ $rows->links() }}</div>@endif
</div>

<div class="modal fade" id="test-modal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <form method="POST" action="{{ route('admin.notifications.test') }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Send test notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Channel</label>
                    <select name="channel" class="form-control">
                        @foreach($channels as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">SMS, WhatsApp and Push are adapter slots — they will report "no adapter configured" until a gateway is wired in.</small>
                </div>
                <div class="form-group"><label>Recipient *</label><input type="email" name="recipient" class="form-control" required value="{{ old('recipient', setting('notifications.admin_email', '')) }}"></div>
                <div class="form-group"><label>Subject *</label><input name="subject" class="form-control" required value="Lindu CMS test notification"></div>
                <div class="form-group"><label>Body *</label><textarea name="body" rows="4" class="form-control" required>This is a test notification from {{ setting('general.site_name', 'Lindu CMS') }}.</textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary">Send</button>
            </div>
        </form>
    </div></div>
</div>
@endsection
