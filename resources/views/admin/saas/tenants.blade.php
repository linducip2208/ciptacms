@extends('admin.layout')
@section('title', 'Tenants')
@section('crumb', 'SaaS / Tenants')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Tenants</h2>
        <div class="text-muted">Tenant isolation is resolved by the <code>ResolveTenant</code> middleware on every request.</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.saas.usage') }}" class="btn btn-outline">Usage &amp; quotas</a>
        <a href="{{ route('admin.saas.domains') }}" class="btn btn-outline">Domains</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">New tenant</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.saas.tenants.store') }}">
                    @csrf
                    <div class="form-group">
                        <label>Name *</label>
                        <input name="name" value="{{ old('name') }}" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Plan</label>
                        <select name="plan_id" class="form-control">
                            <option value="">—</option>
                            @foreach($plans as $p)
                                <option value="{{ $p->id }}" @selected((int) old('plan_id') === $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Primary domain</label>
                        <input name="domain" value="{{ old('domain') }}" class="form-control" placeholder="client.example.com">
                    </div>
                    <button class="btn btn-primary">Create tenant</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Name</th><th>Slug</th><th>Plan</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse($tenants as $t)
                            <tr>
                                <td><b>{{ $t->name }}</b><div class="text-muted text-xs">{{ $t->id }}</div></td>
                                <td><code>{{ $t->slug }}</code></td>
                                <td>{{ $t->plan?->name ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $t->status === 'active' ? 'green' : 'secondary' }}">{{ $t->status }}</span>
                                </td>
                                <td>
                                    <button class="text-indigo-600" data-bs-toggle="modal" data-bs-target="#t-{{ $t->id }}">Edit</button>
                                </td>
                            </tr>
                            <div class="modal fade" id="t-{{ $t->id }}" tabindex="-1">
                                <div class="modal-dialog"><div class="modal-content">
                                    <form method="POST" action="{{ route('admin.saas.tenants.update', $t) }}">
                                        @csrf @method('PUT')
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ $t->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group"><label>Name</label><input name="name" value="{{ $t->name }}" class="form-control" required></div>
                                            <div class="form-group">
                                                <label>Plan</label>
                                                <select name="plan_id" class="form-control">
                                                    <option value="">—</option>
                                                    @foreach($plans as $p)
                                                        <option value="{{ $p->id }}" @selected($t->plan_id === $p->id)>{{ $p->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select name="status" class="form-control">
                                                    @foreach(['active', 'trial', 'suspended'] as $s)
                                                        <option value="{{ $s }}" @selected($t->status === $s)>{{ $s }}</option>
                                                    @endforeach
                                                </select>
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
                            <tr><td colspan="5" class="text-center text-muted py-4">No tenants yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($tenants->hasPages())<div class="card-footer">{{ $tenants->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
