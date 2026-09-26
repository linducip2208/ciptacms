@extends('admin.layout')
@section('title','Backups')@section('crumb','Backups')
@section('content')
<div class="card mb-4"><form method="POST" action="{{ route('admin.backups.run') }}">@csrf<button class="btn btn-primary">Run backup now</button></form></div><div class="card"><div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Type</th><th>Status</th><th>Path</th><th>When</th></tr></thead><tbody>@foreach($rows as $r)<tr><td>{{ $r->type }}</td><td>{{ $r->status }}</td><td class="text-xs font-mono">{{ $r->path }}</td><td class="text-xs">{{ $r->created_at }}</td></tr>@endforeach</tbody></table></div></div>
@endsection
