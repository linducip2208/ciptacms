@extends('admin.layout')
@section('title','Tenants')@section('crumb','Tenants')
@section('content')
<div class="card mb-4"><form method="POST" action="{{ route('admin.tenants.store') }}" class="d-flex gap-2">@csrf<input name="name" required placeholder="Tenant name" class="form-control !w-64"><button class="btn btn-primary">+ Tenant</button></form></div><div class="card"><div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Name</th><th>Slug</th><th>Domain</th><th>Plan</th><th>Status</th></tr></thead><tbody>@foreach($tenants as $t)<tr><td>{{ $t->name }}</td><td>{{ $t->slug }}</td><td>{{ $t->domain }}</td><td>{{ $t->plan?->name }}</td><td>{{ $t->status }}</td></tr>@endforeach</tbody></table></div></div>
@endsection
