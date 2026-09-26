@extends('admin.layout')
@section('title','Data Builder')@section('crumb','Data Builder')
@section('content')
<div class="card p-4 mb-4"><form method="POST" action="{{ route('admin.cms.types.save') }}" class="flex gap-2">@csrf<input name="name" required placeholder="Type name" class="input !w-48"><input name="slug" required placeholder="slug" class="input !w-48"><button class="btn-primary">+ Content Type</button></form><p class="text-xs text-slate-500 mt-2">Creates: migration + model + CRUD + validation + permissions + routes + API + search/filter/sort/export/import.</p></div>
<div class="card p-4"><table class="tbl"><thead><tr><th>Name</th><th>Slug</th><th>Table</th><th>Records</th></tr></thead><tbody>@foreach($rows as $r)<tr><td>{{ $r->name }}</td><td>{{ $r->slug }}</td><td class="font-mono text-xs">{{ $r->table_name }}</td><td>{{ $r->records_count }}</td></tr>@endforeach</tbody></table></div>
@endsection
