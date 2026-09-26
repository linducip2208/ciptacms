@extends('admin.layout')
@section('title', 'Billing')
@section('crumb', 'SaaS / Billing')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Billing</h2>
        <div class="text-muted">
            This build ships the adapter interface, not a billing application. Configure a gateway below and
            a module can charge through <code>\App\Core\Services\PaymentManager</code>.
        </div>
    </div>
    <a href="{{ route('admin.gateways') }}" class="btn btn-outline">Payment gateways</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Adapter</th><th>Class</th><th>Configured</th><th>Mode</th></tr></thead>
            <tbody>
                @php $any = false; @endphp
                @foreach($adapters as $key => $class)
                    @php $any = true; @endphp
                    <tr>
                        <td><b>{{ ucfirst($key) }}</b></td>
                        <td><code class="text-xs">{{ $class }}</code></td>
                        <td>
                            <span class="badge bg-{{ !empty($gateways[$key]['secret_key']) ? 'green' : 'secondary' }}">
                                {{ !empty($gateways[$key]['secret_key']) ? 'yes' : 'no' }}
                            </span>
                        </td>
                        <td>{{ $gateways[$key]['mode'] ?? 'live' }}</td>
                    </tr>
                @endforeach
                @unless($any)
                    <tr><td colspan="4" class="text-center text-muted py-4">
                        No payment adapters registered. Add them under
                        <code>config('lindu.payments.adapters')</code>.
                    </td></tr>
                @endunless
            </tbody>
        </table>
    </div>
    <div class="card-footer text-muted small">
        Intentionally supported out of the box as seams: Xendit, iPaymu, Tripay, Stripe and a generic
        adapter. Wiring a real gateway is a module concern, not core.
    </div>
</div>
@endsection
