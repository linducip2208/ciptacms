@extends('admin.layout')
@section('title', 'Webhooks')
@section('crumb', 'Notifications / Webhooks')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Outgoing webhooks</h2>
        <div class="text-muted">Each request is signed with an HMAC-SHA256 signature over <code>timestamp.body</code> and retried automatically.</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.cms.webhooks.logs') }}" class="btn btn-outline">Delivery log</a>
        <a href="{{ route('admin.cms.webhooks.incoming') }}" class="btn btn-outline">Incoming</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">New webhook</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.cms.webhooks.store') }}">
                    @csrf
                    <div class="form-group"><label>Name *</label><input name="name" value="{{ old('name') }}" class="form-control" required></div>
                    <div class="form-group">
                        <label>Event *</label>
                        <select name="event" class="form-control">
                            @foreach($events as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group"><label>Target URL *</label><input type="url" name="url" value="{{ old('url') }}" class="form-control" required placeholder="https://example.com/hooks/lindu"></div>
                    <div class="form-group">
                        <label>Custom headers</label>
                        <textarea name="headers" rows="3" class="form-control font-monospace" placeholder="X-Api-Key: abc123"></textarea>
                        <small class="text-muted">One <code>Name: value</code> per line.</small>
                    </div>
                    <div class="form-group"><label>Timeout (seconds)</label><input type="number" name="timeout" value="{{ old('timeout', 10) }}" class="form-control" min="1" max="120"></div>
                    <label class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                        <span class="form-check-label">Active</span>
                    </label>
                    <button class="btn btn-primary">Create webhook</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Name</th><th>Event</th><th>URL</th><th>Deliveries</th><th>State</th><th></th></tr></thead>
                    <tbody>
                        @forelse($rows as $w)
                            <tr>
                                <td><b>{{ $w->name }}</b></td>
                                <td><code class="text-xs">{{ $w->event }}</code></td>
                                <td class="text-muted small" style="max-width:180px"><div class="text-truncate-cell">{{ $w->url }}</div></td>
                                <td>{{ $w->logs_count }}</td>
                                <td><span class="badge bg-{{ $w->is_active ? 'green' : 'secondary' }}">{{ $w->is_active ? 'active' : 'paused' }}</span></td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <form method="POST" action="{{ route('admin.cms.webhooks.test', $w) }}">@csrf
                                            <button class="text-indigo-600">Test</button>
                                        </form>
                                        <button class="text-slate-600" data-bs-toggle="modal" data-bs-target="#w-{{ $w->id }}">Edit</button>
                                        <form method="POST" action="{{ route('admin.cms.webhooks.rotate-secret', $w) }}"
                                              onsubmit="return confirm('Rotate the signing secret? Existing receivers will stop verifying.')">@csrf
                                            <button class="text-slate-600">Rotate secret</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.cms.webhooks.destroy', $w) }}"
                                              onsubmit="return confirm('Delete this webhook?')">@csrf @method('DELETE')
                                            <button class="text-rose-600">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <div class="modal fade" id="w-{{ $w->id }}" tabindex="-1">
                                <div class="modal-dialog"><div class="modal-content">
                                    <form method="POST" action="{{ route('admin.cms.webhooks.update', $w) }}">
                                        @csrf @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ $w->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group"><label>Name</label><input name="name" value="{{ $w->name }}" class="form-control" required></div>
                                            <div class="form-group">
                                                <label>Event</label>
                                                <select name="event" class="form-control">
                                                    @foreach($events as $k => $v)
                                                        <option value="{{ $k }}" @selected($w->event === $k)>{{ $v }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group"><label>Target URL</label><input type="url" name="url" value="{{ $w->url }}" class="form-control" required></div>
                                            <div class="form-group">
                                                <label>Custom headers</label>
                                                <textarea name="headers" rows="3" class="form-control font-monospace">{{ collect($w->headers ?? [])->map(fn($v, $k) => $k.': '.$v)->implode("\n") }}</textarea>
                                            </div>
                                            <div class="row">
                                                <div class="form-group col-6">
                                                    <label>Timeout</label>
                                                    <input type="number" name="timeout" value="{{ $w->timeout }}" class="form-control" min="1" max="120">
                                                </div>
                                                <div class="form-group col-6 d-flex align-items-end">
                                                    <label class="form-check form-switch mb-2">
                                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($w->is_active)>
                                                        <span class="form-check-label">Active</span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>Signing secret</label>
                                                <input class="form-control font-monospace" value="{{ $w->secret }}" readonly onclick="this.select()">
                                                <small class="text-muted">Verify with <code>hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret)</code>.</small>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
                                            <button class="btn btn-primary">Save</button>
                                        </div>
                                    </form>
                                </div></div>
                            </div>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No webhooks yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
