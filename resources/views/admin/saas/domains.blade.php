@extends('admin.layout')
@section('title', 'Domains')
@section('crumb', 'SaaS / Domains')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-1">Domains</h2>
        <div class="text-muted">Hostnames this installation answers to. The primary domain is used for absolute URLs in emails.</div>
    </div>
    <a href="{{ route('admin.whitelabel.domains.create') }}" class="btn btn-primary">+ Add domain</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Domain</th><th>Tenant</th><th>Primary</th><th>Verified</th><th></th></tr></thead>
            <tbody>
                @forelse($rows as $d)
                    <tr>
                        <td><code>{{ $d->domain }}</code></td>
                        <td class="text-muted small">{{ $d->tenant_id ? \Illuminate\Support\Str::limit($d->tenant_id, 12) : '—' }}</td>
                        <td>@if($d->is_primary)<span class="badge bg-blue">primary</span>@else — @endif</td>
                        <td>
                            <span class="badge bg-{{ $d->is_verified ? 'green' : 'secondary' }}">
                                {{ $d->is_verified ? 'verified' : 'pending' }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                @unless($d->is_primary)
                                    <form method="POST" action="{{ route('admin.whitelabel.domains.primary', $d) }}">@csrf
                                        <button class="text-indigo-600">Make primary</button>
                                    </form>
                                @endunless
                                <form method="POST" action="{{ route('admin.whitelabel.domains.destroy', $d) }}"
                                      onsubmit="return confirm('Remove this domain?')">@csrf @method('DELETE')
                                    <button class="text-rose-600">Remove</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No domains configured.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
