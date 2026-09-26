@extends('admin.layout')
@section('title','Licenses')@section('crumb','Licenses')
@section('content')
<div class="card mb-4"><form method="POST" action="{{ route('admin.licenses.issue') }}" class="d-flex flex-wrap gap-2">@csrf<input name="product" required placeholder="Product" class="form-control !w-40"><input name="customer" placeholder="Customer" class="form-control !w-40"><input name="domain" placeholder="Domain" class="form-control !w-40"><button class="btn btn-primary">Issue license</button></form></div><div class="card"><div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Key</th><th>Product</th><th>Status</th><th>Expires</th></tr></thead><tbody>@foreach($rows as $r)<tr><td class="font-mono text-xs">{{ $r->license_key }}</td><td>{{ $r->product }}</td><td>{{ $r->status }}</td><td>{{ $r->expires_at }}</td></tr>@endforeach</tbody></table></div></div>
@endsection
