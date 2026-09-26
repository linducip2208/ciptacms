@extends('admin.layout')
@section('title', 'Payment Gateways')
@section('crumb', 'SaaS / Payment Gateways')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Payment gateways</h2>
        <div class="text-muted">Secrets are stored encrypted and never displayed again.</div>
    </div>
    <a href="{{ route('admin.saas.billing') }}" class="btn btn-outline">Billing overview</a>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Configure a gateway</h3></div>
            <div class="card-body">
                @php $adapters = array_keys($adapters); @endphp
                @if($adapters === [])
                    <p class="text-muted mb-0">
                        No adapters registered. Add them under <code>config/lindu.php</code> →
                        <code>payments.adapters</code>.
                    </p>
                @else
                    <form method="POST" action="{{ route('admin.gateways.save') }}">
                        @csrf
                        <div class="form-group">
                            <label>Gateway</label>
                            <select name="gateway" class="form-control">
                                <option value="">— none —</option>
                                @foreach($adapters as $key)
                                    <option value="{{ $key }}">{{ ucfirst($key) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Secret key</label>
                            <input type="password" name="secret_key" class="form-control" autocomplete="new-password"
                                   placeholder="{{ !empty($gateways) ? 'Leave blank to keep the stored key' : 'Paste the secret' }}">
                        </div>
                        <div class="form-group">
                            <label>Mode</label>
                            <select name="mode" class="form-control">
                                <option value="live">Live</option>
                                <option value="sandbox">Sandbox / test</option>
                            </select>
                        </div>
                        <button class="btn btn-primary">Save gateway</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Active configuration</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead><tr><th>Gateway</th><th>Secret</th><th>Mode</th></tr></thead>
                    <tbody>
                        @forelse($gateways as $key => $config)
                            <tr>
                                <td><b>{{ ucfirst($key) }}</b></td>
                                <td class="text-muted">••••••••{{ !empty($config['secret_key']) ? ' (set)' : ' (missing)' }}</td>
                                <td><span class="badge">{{ $config['mode'] ?? 'live' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-4">No gateway configured.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
