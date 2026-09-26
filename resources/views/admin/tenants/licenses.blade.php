@extends('admin.layout')
@section('title','Licenses')@section('crumb','Licenses')
@section('content')
<div class="card p-4 mb-4"><form method="POST" action="{{ route('admin.licenses.issue') }}" class="flex flex-wrap gap-2">@csrf<input name="product" required placeholder="Product" class="input !w-40"><input name="customer" placeholder="Customer" class="input !w-40"><input name="domain" placeholder="Domain" class="input !w-40"><button class="btn-primary">Issue license</button></form></div><div class="card p-4"><table class="tbl"><thead><tr><th>Key</th><th>Product</th><th>Status</th><th>Expires</th></tr></thead><tbody>@foreach($rows as $r)<tr><td class="font-mono text-xs">{{ $r->license_key }}</td><td>{{ $r->product }}</td><td>{{ $r->status }}</td><td>{{ $r->expires_at }}</td></tr>@endforeach</tbody></table></div>
@endsection
