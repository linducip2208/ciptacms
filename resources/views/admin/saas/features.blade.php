@extends('admin.layout')
@section('title', 'Feature Flags')
@section('crumb', 'SaaS / Features')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Feature flags</h2>
        <div class="text-muted">Per-plan feature entitlement with optional numeric limits for quotas.</div>
    </div>
    <a href="{{ route('admin.saas.plans') }}" class="btn btn-outline">← Plans</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Plan</th><th>Enabled features</th><th>Limits</th></tr></thead>
            <tbody>
                @php $any = false; @endphp
                @foreach($plans as $plan)
                    @php
                        $rows = $features[$plan->id] ?? collect();
                        $any = $any || $rows->isNotEmpty();
                    @endphp
                    <tr>
                        <td><b>{{ $plan->name }}</b><div class="text-muted small"><code>{{ $plan->slug }}</code></div></td>
                        <td>
                            @forelse($rows as $f)
                                <span class="badge bg-{{ $f->is_enabled ? 'green' : 'secondary' }}">{{ $f->key }}</span>
                            @empty
                                <span class="text-muted">—</span>
                            @endforelse
                        </td>
                        <td class="text-muted text-xs" style="max-width:240px">
                            @php $limits = $rows->pluck('limits')->filter()->map(fn ($l) => json_encode($l))->implode(', '); @endphp
                            {{ $limits ?: '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer text-muted small">
        Feature flags are stored in <code>plan_features</code> and read through
        <code>\App\Core\Services\FeatureFlag::enabled($key, $tenant)</code>.
    </div>
</div>
@endsection
