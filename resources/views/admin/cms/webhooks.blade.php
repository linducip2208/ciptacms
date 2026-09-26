@extends('admin.layout')
@section('title','Webhooks')@section('crumb','Webhooks')
@section('content')
<div class="card p-4"><table class="tbl"><thead><tr><th>Name</th><th>Event</th><th>URL</th><th>Logs</th><th>Active</th></tr></thead><tbody>@foreach($rows as $r)<tr><td>{{ $r->name }}</td><td class="font-mono text-xs">{{ $r->event }}</td><td class="font-mono text-xs">{{ Str::limit($r->url,50) }}</td><td>{{ $r->logs_count }}</td><td>{{ $r->is_active?'yes':'no' }}</td></tr>@endforeach</tbody></table></div>
@endsection
