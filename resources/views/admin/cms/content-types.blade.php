@extends('admin.layout')
@section('title','Data Builder')@section('crumb','Data Builder')
@section('content')
<div class="card mb-4"><form method="POST" action="{{ route('admin.cms.types.save') }}" class="d-flex gap-2">@csrf<input name="name" required placeholder="Type name" class="form-control !w-48"><input name="slug" required placeholder="slug" class="form-control !w-48"><button class="btn btn-primary">+ Content Type</button></form><p class="text-muted small mt-2">Creates: migration + model + CRUD + validation + permissions + routes + API + search/filter/sort/export/import.</p></div>
<div class="card"><div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Name</th><th>Slug</th><th>Table</th><th>Records</th></tr></thead><tbody>@foreach($rows as $r)<tr><td>{{ $r->name }}</td><td>{{ $r->slug }}</td><td class="font-mono text-xs">{{ $r->table_name }}</td><td>{{ $r->records_count }}</td></tr>@endforeach</tbody></table></div></div>
@endsection
