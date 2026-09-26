@extends('admin.layout')
@section('title', 'Subscriptions')
@section('crumb', 'SaaS / Subscriptions')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Subscriptions</h2>
        <div class="text-muted">Tenant-to-plan assignments. Renewals are handled by the payment adapter, not by this page.</div>
    </div>
    <a href="{{ route('admin.saas.tenants') }}" class="btn btn-outline">← Tenants</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Tenant</th><th>Plan</th><th>Status</th><th>Started</th><th>Renews</th><th>Ends</th></tr></thead>
            <tbody>
                @forelse($rows as $s)
                    <tr>
                        <td><b>{{ $s->tenant?->name ?? '—' }}</b></td>
                        <td>{{ $s->plan?->name ?? '—' }}</td>
                        <td><span class="badge bg-{{ $s->status === 'active' ? 'green' : 'secondary' }}">{{ $s->status }}</span></td>
                        <td class="text-muted small">{{ optional($s->started_at)->toDateString() ?? '—' }}</td>
                        <td class="text-muted small">{{ optional($s->renews_at)->toDateString() ?? '—' }}</td>
                        <td class="text-muted small">{{ optional($s->ends_at)->toDateString() ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">
                        No subscriptions yet. A subscription is created when a tenant is assigned a paid plan.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())<div class="card-footer">{{ $rows->links() }}</div>@endif
</div>
@endsection
