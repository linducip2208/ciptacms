@extends('admin.layout')
@section('title','Tenants')@section('crumb','Tenants')
@section('content')
<div class="card p-4 mb-4"><form method="POST" action="{{ route('admin.tenants.store') }}" class="flex gap-2">@csrf<input name="name" required placeholder="Tenant name" class="input !w-64"><button class="btn-primary">+ Tenant</button></form></div><div class="card p-4"><table class="tbl"><thead><tr><th>Name</th><th>Slug</th><th>Domain</th><th>Plan</th><th>Status</th></tr></thead><tbody>@foreach($tenants as $t)<tr><td>{{ $t->name }}</td><td>{{ $t->slug }}</td><td>{{ $t->domain }}</td><td>{{ $t->plan?->name }}</td><td>{{ $t->status }}</td></tr>@endforeach</tbody></table></div>
@endsection
