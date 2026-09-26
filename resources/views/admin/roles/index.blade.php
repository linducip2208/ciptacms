@extends('admin.layout')
@section('title','Roles')@section('crumb','Roles')
@section('content')
<div class="row row-cards">
<div class="col-lg-5"><div class="card"><div class="card-header"><h3 class="card-title">Roles</h3></div>
<div class="list-group list-group-flush">@foreach($roles as $r)<div class="list-group-item"><b>{{ $r->name }}</b> <span class="badge bg-blue-lt">{{ $r->slug }}</span> <span class="text-muted small">{{ $r->users_count }} users · {{ $r->permissions_count }} perms</span></div>@endforeach</div>
<div class="card-footer"><form method="POST" action="{{ route('admin.roles.store') }}" class="d-flex gap-2">@csrf<input name="name" required placeholder="Name" class="form-control"><input name="slug" required placeholder="slug" class="form-control"><button class="btn btn-primary">Add</button></form></div>
</div></div>
<div class="col-lg-7"><div class="card"><div class="card-header"><h3 class="card-title">Permissions ({{ $permissions->count() }})</h3></div>
<div class="card-body p-0" style="max-height:480px;overflow:auto">@foreach($grouped as $g=>$ps)<div class="px-3 py-1 bg-light fw-bold">{{ $g }}</div>@foreach($ps as $p)<div class="d-flex justify-content-between px-3 py-1 border-bottom small"><span>{{ $p->name }} <span class="text-muted">{{ $p->slug }}</span></span></div>@endforeach @endforeach</div>
</div></div>
</div>
@endsection
