@extends('admin.layout')
@section('title', 'Activations — '.$license->license_key)
@section('crumb', 'License / '.$license->license_key.' / Activations')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('admin.licenses.index') }}" class="text-muted">← All licenses</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3"><b>Key</b><br><code>{{ $license->license_key }}</code></div>
            <div class="col-md-3"><b>Product</b><br>{{ $license->product }}</div>
            <div class="col-md-3"><b>Customer</b><br>{{ $license->customer ?: '—' }}</div>
            <div class="col-md-3"><b>Status</b><br><span class="badge bg-{{ $license->status === 'active' ? 'green' : 'secondary' }}">{{ $license->status }}</span></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">Activations ({{ $rows->count() }} / {{ $license->max_activations }})</h3></div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Domain</th><th>IP</th><th>Activated</th><th></th></tr></thead>
            <tbody>
                @forelse($rows as $a)
                    <tr>
                        <td><code>{{ $a->domain }}</code></td>
                        <td class="text-muted small">{{ $a->ip ?: '—' }}</td>
                        <td class="text-muted small">{{ optional($a->activated_at)->diffForHumans() }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.licenses.activations.revoke', [$license, $a->domain]) }}"
                                  onsubmit="return confirm('Revoke this activation?')">@csrf
                                <button class="text-rose-600">Revoke</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">No activations yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
