@extends('admin.layout')
@section('title','Workflows')@section('crumb','Workflows')
@section('content')
<div class="card p-4 mb-4"><form method="POST" action="{{ route('admin.cms.workflows.save') }}" class="flex flex-wrap gap-2">@csrf<input name="name" required placeholder="Name" class="input !w-48"><select name="trigger_event" class="input !w-56"><option value="record.created">record.created</option><option value="record.updated">record.updated</option><option value="user.registered">user.registered</option><option value="form.submitted">form.submitted</option><option value="order.created">order.created</option><option value="payment.completed">payment.completed</option><option value="webhook.received">webhook.received</option></select><button class="btn-primary">+ Workflow</button></form></div>
<div class="card p-4"><table class="tbl"><thead><tr><th>Name</th><th>Trigger</th><th>Runs</th></tr></thead><tbody>@foreach($rows as $r)<tr><td>{{ $r->name }}</td><td class="font-mono text-xs">{{ $r->trigger_event }}</td><td>{{ $r->runs_count }}</td></tr>@endforeach</tbody></table></div>
@endsection
