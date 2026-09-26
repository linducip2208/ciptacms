@extends('admin.layout')
@section('title', 'Webhook Delivery Log')
@section('crumb', 'Notifications / Webhooks / Log')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <form class="d-flex gap-2" method="GET">
        <select name="webhook_id" class="form-control" onchange="this.form.submit()">
            <option value="">All webhooks</option>
            @foreach($webhooks as $w)
                <option value="{{ $w->id }}" @selected((int) request('webhook_id') === $w->id)>{{ $w->name }}</option>
            @endforeach
        </select>
        <select name="status" class="form-control" onchange="this.form.submit()">
            <option value="">All status</option>
            @foreach(['pending' => 'Pending', 'delivered' => 'Delivered', 'failed' => 'Failed', 'abandoned' => 'Abandoned'] as $k => $l)
                <option value="{{ $k }}" @selected($status === $k)>{{ $l }}</option>
            @endforeach
        </select>
    </form>
    <a href="{{ route('admin.cms.webhooks.index') }}" class="btn btn-outline">← Webhooks</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Webhook</th><th>Event</th><th>Status</th><th>HTTP</th><th>Attempts</th><th>Next retry</th><th>When</th><th></th></tr></thead>
            <tbody>
                @forelse($rows as $l)
                    <tr>
                        <td>{{ $l->webhook?->name ?? '—' }}</td>
                        <td><code class="text-xs">{{ $l->event }}</code></td>
                        <td>
                            <span class="badge bg-{{ $l->status === 'delivered' ? 'green' : ($l->status === 'failed' ? 'red' : 'secondary') }}">
                                {{ $l->status }}
                            </span>
                            @if($l->error)<div class="text-rose-600 text-xs">{{ \Illuminate\Support\Str::limit($l->error, 70) }}</div>@endif
                        </td>
                        <td>{{ $l->response_status ?? '—' }}</td>
                        <td>{{ $l->attempts }}</td>
                        <td class="text-muted small">{{ $l->next_retry_at?->diffForHumans() ?? '—' }}</td>
                        <td class="text-muted small">{{ $l->created_at->diffForHumans() }}</td>
                        <td>
                            @if(in_array($l->status, ['failed', 'abandoned'], true))
                                <form method="POST" action="{{ route('admin.cms.webhooks.logs.retry', $l) }}">@csrf
                                    <button class="text-indigo-600">Retry</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No deliveries recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())<div class="card-footer">{{ $rows->links() }}</div>@endif
</div>
@endsection
