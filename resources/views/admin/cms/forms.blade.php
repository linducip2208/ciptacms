@extends('admin.layout')
@section('title','Forms')@section('crumb','Forms')
@section('content')
<div class="card"><div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Name</th><th>Slug</th><th>Fields</th><th>Submissions</th><th></th></tr></thead><tbody>@foreach($rows as $r)<tr><td>{{ $r->name }}</td><td>{{ $r->slug }}</td><td>{{ $r->fields_count }}</td><td>{{ $r->submissions_count }}</td><td><a class="text-indigo-600" href="{{ route('admin.cms.forms.builder',$r) }}">Builder</a></td></tr>@endforeach</tbody></table></div></div>
@endsection
