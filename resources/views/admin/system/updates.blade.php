@extends('admin.layout')
@section('title','Updates')@section('crumb','Updates')
@section('content')
<div class="card mb-4"><pre class="text-xs">{{ json_encode($core, JSON_PRETTY_PRINT) }}</pre></div><div class="card"><div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Type</th><th>Slug</th><th>From → To</th><th>Status</th></tr></thead><tbody>@foreach($logs as $l)<tr><td>{{ $l->type }}</td><td>{{ $l->slug }}</td><td>{{ $l->from_version }} → {{ $l->to_version }}</td><td>{{ $l->status }}</td></tr>@endforeach</tbody></table></div></div>
@endsection
