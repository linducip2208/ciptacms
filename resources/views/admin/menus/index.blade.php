@extends('admin.layout')
@section('title','Menus')@section('crumb','Menus')
@section('content')
<div class="card p-4 mb-4"><form method="POST" action="{{ route('admin.menus.store') }}" class="flex flex-wrap gap-2">@csrf<input name="title" required placeholder="Title" class="input !w-40"><input name="url" placeholder="/admin/..." class="input !w-40"><input name="icon" placeholder="icon" class="input !w-24"><input name="permission" placeholder="permission" class="input !w-40"><input type="hidden" name="location" value="{{ $location }}"><button class="btn-primary">+ Add</button></form></div>
<div class="card p-4"><table class="tbl"><thead><tr><th>#</th><th>Title</th><th>URL/Route</th><th>Permission</th><th>Sort</th><th>Visible</th><th></th></tr></thead><tbody>
@foreach($items as $it)<tr><td>{{ $it->id }}</td><td>{{ str_repeat('— ',0) }}{{ $it->title }}</td><td>{{ $it->url }}{{$it->route}}</td><td><span class="badge">{{ $it->permission }}</span></td><td>{{ $it->sort_order }}</td><td>{{ $it->is_visible?'yes':'no' }}</td>
<td><form method="POST" action="{{ route('admin.menus.destroy',$it) }}">@csrf @method('DELETE')<button class="text-rose-600 text-sm">Delete</button></form></td></tr>@endforeach
</tbody></table></div>
@endsection
