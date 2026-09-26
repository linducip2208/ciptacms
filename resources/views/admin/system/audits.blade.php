@extends('admin.layout')
@section('title','Audit log')@section('crumb','Audit log')
@section('content')
<div class="card"><div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>When</th><th>User</th><th>Action</th><th>Entity</th><th>IP</th></tr></thead><tbody>@foreach($rows as $r)<tr><td class="text-xs">{{ $r->created_at }}</td><td>{{ $r->user?->email }}</td><td><span class="badge">{{ $r->action }}</span></td><td class="text-xs">{{ $r->entity_type }} #{{ $r->entity_id }}</td><td class="text-xs">{{ $r->ip }}</td></tr>@endforeach</tbody></table></div><div class="mt-2">{{ $rows->links() }}</div></div>
@endsection
