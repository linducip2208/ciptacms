@extends('admin.layout')
@section('title','Workflows')@section('crumb','Workflows')
@section('content')
<div class="card mb-4"><form method="POST" action="{{ route('admin.cms.workflows.save') }}" class="d-flex flex-wrap gap-2">@csrf<input name="name" required placeholder="Name" class="form-control !w-48"><select name="trigger_event" class="form-control !w-56"><option value="record.created">record.created</option><option value="record.updated">record.updated</option><option value="user.registered">user.registered</option><option value="form.submitted">form.submitted</option><option value="order.created">order.created</option><option value="payment.completed">payment.completed</option><option value="webhook.received">webhook.received</option></select><button class="btn btn-primary">+ Workflow</button></form></div>
<div class="card"><div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Name</th><th>Trigger</th><th>Runs</th></tr></thead><tbody>@foreach($rows as $r)<tr><td>{{ $r->name }}</td><td class="font-mono text-xs">{{ $r->trigger_event }}</td><td>{{ $r->runs_count }}</td></tr>@endforeach</tbody></table></div></div>
@endsection
