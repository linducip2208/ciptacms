@extends('admin.layout')
@section('title', 'Usage & Quotas')
@section('crumb', 'SaaS / Usage')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Usage &amp; quotas</h2>
        <div class="text-muted">Period <code>{{ $period }}</code>. Counters come from <code>tenant_usages</code>.</div>
    </div>
    <a href="{{ route('admin.saas.tenants') }}" class="btn btn-outline">← Tenants</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Tenant</th><th>Metric</th><th>Used</th><th>Limit</th><th>Usage</th></tr></thead>
            <tbody>
                @php $any = false; @endphp
                @foreach($rows as $tenantId => $usages)
                    @foreach($usages as $u)
                        @php
                            $any = true;
                            $pct = $u->limit ? min(100, round($u->used / max(1, $u->limit) * 100)) : null;
                        @endphp
                        <tr>
                            <td><b>{{ \App\Models\Tenant::find($tenantId)?->name ?? $tenantId }}</b></td>
                            <td><code>{{ $u->metric }}</code></td>
                            <td>{{ number_format($u->used) }}</td>
                            <td>{{ $u->limit === null ? '∞' : number_format($u->limit) }}</td>
                            <td style="min-width:140px">
                                @if($pct !== null)
                                    <div class="progress" style="height:6px">
                                        <div class="progress-bar bg-{{ $pct > 90 ? 'red' : ($pct > 70 ? 'orange' : 'blue') }}"
                                             style="width:{{ $pct }}%"></div>
                                    </div>
                                    <span class="text-muted text-xs">{{ $pct }}%</span>
                                @else
                                    <span class="text-muted">unlimited</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endforeach
                @unless($any)
                    <tr><td colspan="5" class="text-center text-muted py-4">
                        No usage recorded yet. Counters are written by
                        <code>FeatureFlag::track($metric, $delta)</code> from the consuming code.
                    </td></tr>
                @endunless
            </tbody>
        </table>
    </div>
</div>
@endsection
