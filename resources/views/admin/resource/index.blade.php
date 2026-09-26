@extends('admin.layout')
@section('title', $config['label'] ?? $resource)@section('crumb', $config['label'] ?? $resource)
@section('content')
<div class="card p-4">
<div class="flex flex-wrap gap-2 items-center mb-4">
<form class="flex gap-2 flex-1 min-w-[220px]"><input name="search" value="{{ request('search') }}" class="input" placeholder="Search {{ $config['label']??'' }}…"><button class="btn-primary">Search</button></form>
<a href="{{ route('admin.resource.create',$resource) }}" class="btn-primary">+ Create</a>
<a href="{{ route('admin.resource.export',$resource, request()->query()) }}" class="btn-primary">Export CSV</a>
</div>
<div class="overflow-x-auto"><table class="tbl"><thead><tr><th>ID</th>@foreach(array_slice(array_keys($rows->first()?->toArray()??['name'=>''],0,5) as $c)<th>{{ $c }}</th>@endforeach<th></th></tr></thead>
<tbody>@forelse($rows as $row)<tr><td>{{ $row->id }}</td>@foreach(array_slice($row->toArray(),0,5) as $v)<td class="max-w-[220px] truncate">{{ is_array($v)?json_encode($v):Str::limit((string)$v,60) }}</td>@endforeach
<td class="whitespace-nowrap"><a class="text-indigo-600" href="{{ route('admin.resource.edit',[$resource,$row->id]) }}">Edit</a><form class="inline" method="POST" action="{{ route('admin.resource.destroy',[$resource,$row->id]) }}">@csrf @method('DELETE')<button class="text-rose-600 ml-2" onclick="return confirm('Delete?')">Del</button></form></td></tr>
@empty<tr><td colspan="8" class="text-center py-10 text-slate-500">Empty — create your first record.</td></tr>@endforelse</tbody></table></div>
<div class="mt-3">{{ $rows->links() }}</div>
</div>
@endsection
