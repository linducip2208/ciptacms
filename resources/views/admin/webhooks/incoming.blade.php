@extends('admin.layout')
@section('title', 'Incoming Webhooks')
@section('crumb', 'Notifications / Webhooks / Incoming')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Incoming endpoints</h2>
        <div class="text-muted">Point an external service at <code>/api/v1/webhooks/in/{key}</code> to push data into the CMS.</div>
    </div>
    <a href="{{ route('admin.cms.webhooks.index') }}" class="btn btn-outline">← Webhooks</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Name</th><th>Endpoint</th><th>Event forwarded</th><th>State</th><th></th></tr></thead>
            <tbody>
                @forelse($rows as $k)
                    <tr>
                        <td><b>{{ $k->name }}</b></td>
                        <td><code>{{ url('/api/v1/webhooks/in/'.$k->key) }}</code></td>
                        <td>{{ $k->forward_event ?: '—' }}</td>
                        <td><span class="badge bg-{{ $k->is_active ? 'green' : 'secondary' }}">{{ $k->is_active ? 'active' : 'paused' }}</span></td>
                        <td><a class="text-indigo-600" href="{{ route('admin.cms.webhooks.incoming.detail', $k->key) }}">View payload log</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">
                        No incoming endpoints yet. Create one with
                        <code>php artisan lindu:webhook-key "name"</code>.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
