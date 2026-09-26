@extends('admin.layout')
@section('title', 'Incoming Webhook: '.$row->name)
@section('crumb', 'Notifications / Webhooks / Incoming / '.$row->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">{{ $row->name }}</h2>
        <div class="text-muted"><code>{{ url('/api/v1/webhooks/in/'.$row->key) }}</code></div>
    </div>
    <a href="{{ route('admin.cms.webhooks.incoming') }}" class="btn btn-outline">← All endpoints</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">How to call it</h3></div>
            <div class="card-body">
<pre class="font-monospace text-xs mb-0" style="white-space:pre-wrap">curl -X POST "{{ url('/api/v1/webhooks/in/'.$row->key) }}" \
  -H "Content-Type: application/json" \
  -H "X-Lindu-Signature: t=&lt;unix-ts&gt;,v1=&lt;hmac&gt;" \
  -d '{"order_id": 123, "status": "paid"}'</pre>
                @if($row->secret)
                    <hr>
                    <label class="form-label">Signing secret</label>
                    <input class="form-control font-monospace" value="{{ $row->secret }}" readonly onclick="this.select()">
                    <small class="text-muted">Signature = <code>hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret)</code>. Requests without a valid signature are rejected.</small>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Settings</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <tbody>
                        <tr><td>Key</td><td><code>{{ $row->key }}</code></td></tr>
                        <tr><td>Forwards to event</td><td>{{ $row->forward_event ?: '—' }}</td></tr>
                        <tr><td>Active</td><td><span class="badge bg-{{ $row->is_active ? 'green' : 'secondary' }}">{{ $row->is_active ? 'yes' : 'no' }}</span></td></tr>
                        <tr><td>Created</td><td>{{ $row->created_at ?? '—' }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Recent payloads</h3></div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>#</th><th>Status</th><th>Method</th><th>Payload</th><th>IP</th><th>When</th></tr></thead>
            <tbody>
                @forelse($logs as $l)
                    <tr>
                        <td class="text-muted">{{ $l->id }}</td>
                        <td><span class="badge bg-{{ $l->status === 'accepted' ? 'green' : 'red' }}">{{ $l->status }}</span></td>
                        <td>{{ $l->method }}</td>
                        <td style="max-width:420px"><pre class="text-xs mb-0" style="white-space:pre-wrap">{{ json_encode($l->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></td>
                        <td class="text-muted small">{{ $l->ip }}</td>
                        <td class="text-muted small">{{ $l->created_at }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No payloads received yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
