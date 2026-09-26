@extends('admin.layout')
@section('title', $config['label'] ?? $resource)@section('crumb', $config['label'] ?? $resource)
@section('content')
<div class="card">
<div class="card-header"><div class="row w-100 g-2 align-items-center">
<div class="col-md-6"><form class="d-flex gap-2"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search {{ $config['label']??'' }}…"><button class="btn btn-primary">Search</button></form></div>
<div class="col-md-6 text-md-end"><a href="{{ route('admin.resource.create',$resource) }}" class="btn btn-primary">+ Create</a> <a href="{{ route('admin.resource.export',$resource, request()->query()) }}" class="btn btn-outline-primary">Export CSV</a></div>
</div></div>
<div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>ID</th>@foreach(array_slice(array_keys($rows->first()?->toArray()??['name'=>''],0,5) as $c)<th>{{ $c }}</th>@endforeach<th></th></tr></thead>
<tbody>@forelse($rows as $row)<tr><td>{{ $row->id }}</td>@foreach(array_slice($row->toArray(),0,5) as $v)<td class="text-truncate" style="max-width:220px">{{ is_array($v)?json_encode($v):Str::limit((string)$v,60) }}</td>@endforeach
<td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.resource.edit',[$resource,$row->id]) }}">Edit</a><form class="d-inline" method="POST" action="{{ route('admin.resource.destroy',[$resource,$row->id]) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger ms-1" onclick="return confirm('Delete?')">Del</button></form></td></tr>
@empty<tr><td colspan="8" class="text-center py-5 text-muted">Empty — create your first record.</td></tr>@endforelse</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div>
</div>
@endsection
