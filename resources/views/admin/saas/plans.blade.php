@extends('admin.layout')
@section('title', 'Plans')
@section('crumb', 'SaaS / Plans')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Plans</h2>
        <div class="text-muted">Plans drive feature flags and quotas. Billing itself is delegated to a payment adapter.</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.saas.features') }}" class="btn btn-outline">Feature flags</a>
        <a href="{{ route('admin.saas.subscriptions') }}" class="btn btn-outline">Subscriptions</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">New plan</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.saas.plans.store') }}">
                    @csrf
                    <div class="form-group"><label>Name *</label><input name="name" value="{{ old('name') }}" class="form-control" required></div>
                    <div class="form-group"><label>Slug *</label><input name="slug" value="{{ old('slug') }}" class="form-control" required></div>
                    <div class="row">
                        <div class="form-group col-6"><label>Price</label><input type="number" step="0.01" name="price" value="{{ old('price', 0) }}" class="form-control"></div>
                        <div class="form-group col-6">
                            <label>Billing period</label>
                            <select name="billing_period" class="form-control">
                                @foreach(['monthly', 'yearly', 'one-time'] as $b)<option value="{{ $b }}">{{ $b }}</option>@endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Limits (JSON)</label>
                        <textarea name="limits" rows="4" class="form-control font-monospace" placeholder='{"storage_mb": 512, "users": 10}'>{{ old('limits') }}</textarea>
                    </div>
                    <button class="btn btn-primary">Create plan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Plan</th><th>Price</th><th>Period</th><th>Features</th><th>Limits</th><th>State</th></tr></thead>
                    <tbody>
                        @forelse($plans as $p)
                            <tr>
                                <td><b>{{ $p->name }}</b><div class="text-muted small"><code>{{ $p->slug }}</code></div></td>
                                <td>{{ number_format((float) $p->price, 0, ',', '.') }}</td>
                                <td>{{ $p->billing_period }}</td>
                                <td>{{ $p->features->count() }}</td>
                                <td class="text-muted text-xs" style="max-width:200px">
                                    <div class="text-truncate-cell">{{ json_encode($p->limits) }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $p->is_active ? 'green' : 'secondary' }}">{{ $p->is_active ? 'active' : 'inactive' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No plans defined.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
