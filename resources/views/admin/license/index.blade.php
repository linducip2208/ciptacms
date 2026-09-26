@extends('admin.layout')
@section('title', 'License')
@section('crumb', 'License')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h2 class="mb-1">License</h2>
        <div class="text-muted">Installed version <strong>v{{ $status['version'] }}</strong></div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">This installation</h3></div>
            <div class="card-body">
                @php
                    $badge = match ($status['state']) {
                        'valid' => 'green', 'expired' => 'yellow', 'banned' => 'red',
                        'suspended' => 'red', 'outdated' => 'orange', default => 'secondary',
                    };
                @endphp
                <div class="mb-3">
                    <span class="badge bg-{{ $badge }}" style="font-size:.9rem">{{ strtoupper($status['state']) }}</span>
                    <p class="mt-2 mb-0">{{ $status['message'] }}</p>
                </div>
                <table class="table table-sm">
                    <tbody>
                        <tr><td>Installed version</td><td><code>v{{ $status['version'] }}</code></td></tr>
                        <tr><td>Entitled version</td><td>{{ $status['entitled_version'] ?? '—' }}</td></tr>
                        <tr><td>Expires</td><td>{{ $status['expires_at'] ?? 'Never' }}</td></tr>
                        <tr><td>Support until</td><td>{{ $status['support_expires_at'] ?? '—' }}</td></tr>
                    </tbody>
                </table>
                @if(!empty($status['features']))
                    <div class="mb-3">
                        <b>Entitled features</b>
                        <div class="d-flex gap-1 flex-wrap mt-1">
                            @foreach($status['features'] as $f)
                                <span class="badge">{{ $f === '*' ? 'all features' : $f }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.licenses.install.run') }}" class="border-top pt-3">
                    @csrf
                    <div class="row g-2">
                        <div class="col-6">
                            <input name="license_key" value="{{ old('license_key') }}" class="form-control" placeholder="LND-XXXX-XXXX-XXXX" required>
                        </div>
                        <div class="col-6">
                            <input name="domain" value="{{ old('domain', parse_url(config('app.url'), PHP_URL_HOST)) }}" class="form-control" placeholder="example.com" required>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-sm mt-2">Activate on this domain</button>
                </form>

                @if($status['state'] !== 'unlicensed')
                    <form method="POST" action="{{ route('admin.licenses.uninstall') }}" class="mt-2"
                          onsubmit="return confirm('Remove the license from this installation?')">
                        @csrf
                        <button class="btn btn-outline btn-sm text-rose-600">Remove license</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Issue a license</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.licenses.store') }}">
                    @csrf
                    <div class="row g-2">
                        <div class="form-group col-6">
                            <label>Product *</label>
                            <input name="product" value="{{ old('product', config('lindu.name')) }}" class="form-control" required>
                        </div>
                        <div class="form-group col-6">
                            <label>Customer</label>
                            <input name="customer" value="{{ old('customer') }}" class="form-control">
                        </div>
                        <div class="form-group col-6">
                            <label>Domain</label>
                            <input name="domain" value="{{ old('domain') }}" class="form-control" placeholder="example.com">
                        </div>
                        <div class="form-group col-6">
                            <label>Max activations</label>
                            <input type="number" name="max_activations" value="{{ old('max_activations', 1) }}" class="form-control" min="1" max="500">
                        </div>
                        <div class="form-group col-6">
                            <label>Expires</label>
                            <input type="date" name="expires_at" value="{{ old('expires_at') }}" class="form-control">
                        </div>
                        <div class="form-group col-6">
                            <label>Support until</label>
                            <input type="date" name="support_expires_at" value="{{ old('support_expires_at') }}" class="form-control">
                        </div>
                        <div class="form-group col-6">
                            <label>Version entitlement</label>
                            <input name="version_entitlement" value="{{ old('version_entitlement') }}" class="form-control" placeholder="1.0.0">
                        </div>
                        <div class="form-group col-6">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                @foreach($statuses as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                            </select>
                        </div>
                        <div class="form-group col-12">
                            <label>Features</label>
                            <textarea name="features" rows="3" class="form-control font-monospace" placeholder="One per line, or * for everything">{{ old('features') }}</textarea>
                        </div>
                    </div>
                    <button class="btn btn-primary">Issue license</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <form class="card-header d-flex gap-2" method="GET">
        <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search key, customer, product, domain…">
        <select name="status" class="form-control" onchange="this.form.submit()">
            <option value="">All status</option>
            @foreach($statuses as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Key</th><th>Product</th><th>Customer</th><th>Domain</th><th>Activations</th><th>Status</th><th>Expires</th><th></th></tr></thead>
            <tbody>
                @forelse($rows as $l)
                    <tr>
                        <td><code>{{ $l->license_key }}</code></td>
                        <td>{{ $l->product }}</td>
                        <td>{{ $l->customer ?: '—' }}</td>
                        <td><code>{{ $l->domain ?: '—' }}</code></td>
                        <td>
                            <a href="{{ route('admin.licenses.activations', $l) }}">
                                {{ $l->activations->count() }} / {{ $l->max_activations }}
                            </a>
                        </td>
                        <td><span class="badge bg-{{ $l->status === 'active' ? 'green' : ($l->status === 'expired' ? 'yellow' : 'secondary') }}">{{ $l->status }}</span></td>
                        <td class="text-muted small">{{ optional($l->expires_at)->toDateString() ?? 'Never' }}</td>
                        <td>
                            <div class="d-flex gap-2">
                                <button class="text-indigo-600" data-bs-toggle="modal" data-bs-target="#l-{{ $l->id }}">Edit</button>
                                <form method="POST" action="{{ route('admin.licenses.destroy', $l) }}"
                                      onsubmit="return confirm('Delete this license?')">@csrf @method('DELETE')
                                    <button class="text-rose-600">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <div class="modal fade" id="l-{{ $l->id }}" tabindex="-1">
                        <div class="modal-dialog"><div class="modal-content">
                            <form method="POST" action="{{ route('admin.licenses.update', $l) }}">
                                @csrf @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title">{{ $l->license_key }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="form-group col-6"><label>Customer</label><input name="customer" value="{{ $l->customer }}" class="form-control"></div>
                                        <div class="form-group col-6"><label>Domain</label><input name="domain" value="{{ $l->domain }}" class="form-control"></div>
                                        <div class="form-group col-6">
                                            <label>Status</label>
                                            <select name="status" class="form-control">
                                                @foreach($statuses as $s)
                                                    <option value="{{ $s }}" @selected($l->status === $s)>{{ $s }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-6">
                                            <label>Max activations</label>
                                            <input type="number" name="max_activations" value="{{ $l->max_activations }}" class="form-control" min="1">
                                        </div>
                                        <div class="form-group col-6">
                                            <label>Expires</label>
                                            <input type="date" name="expires_at" value="{{ optional($l->expires_at)->toDateString() }}" class="form-control">
                                        </div>
                                        <div class="form-group col-6">
                                            <label>Support until</label>
                                            <input type="date" name="support_expires_at" value="{{ optional($l->support_expires_at)->toDateString() }}" class="form-control">
                                        </div>
                                        <div class="form-group col-12">
                                            <label>Version entitlement</label>
                                            <input name="version_entitlement" value="{{ $l->version_entitlement }}" class="form-control">
                                        </div>
                                        <div class="form-group col-12">
                                            <label>Features</label>
                                            <textarea name="features" rows="3" class="form-control font-monospace">{{ is_array($l->features) ? implode("\n", $l->features) : '' }}</textarea>
                                        </div>
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
                    <tr><td colspan="8" class="text-center text-muted py-4">No licenses issued yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())<div class="card-footer">{{ $rows->links() }}</div>@endif
</div>
@endsection
