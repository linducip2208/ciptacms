@extends('admin.layout')
@section('title','Roles')@section('crumb','Roles')
@section('content')
<div class="grid lg:grid-cols-2 gap-4"><div class="card p-4"><h3 class="font-semibold mb-2">Roles</h3>@foreach($roles as $r)<div class="border-b py-2 text-sm"><b>{{ $r->name }}</b> <span class="badge">{{ $r->slug }}</span> <span class="text-slate-500">{{ $r->users_count }} users · {{ $r->permissions_count }} perms</span></div>@endforeach
<form method="POST" action="{{ route('admin.roles.store') }}" class="mt-3 flex gap-2">@csrf<input name="name" required placeholder="Name" class="input"><input name="slug" required placeholder="slug" class="input"><button class="btn-primary">Add</button></form></div>
<div class="card p-4"><h3 class="font-semibold mb-2">Permissions ({{ $permissions->count() }})</h3><div class="max-h-[480px] overflow-auto text-sm">@foreach($permissions->groupBy(fn($p)=>$p->group?->name??$p->module??'general') as $g=>$ps)<b>{{ $g }}</b>@foreach($ps as $p)<div class="flex justify-between border-b py-1"><span>{{ $p->name }} <span class="text-slate-400">{{ $p->slug }}</span></span></div>@endforeach@endforeach</div></div></div>
@endsection
