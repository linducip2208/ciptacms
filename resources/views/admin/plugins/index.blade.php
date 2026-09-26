@extends('admin.layout')
@section('title','Plugins')@section('crumb','Plugins')
@section('content')
<div class="grid md:grid-cols-3 gap-4">@foreach($plugins as $p)<div class="card p-4"><div class="flex justify-between"><b>{{ $p->name }}</b><span class="badge">{{ $p->is_active?'active':'inactive' }}</span></div><div class="text-xs text-slate-500">{{ $p->slug }} · v{{ $p->version }}</div><p class="text-sm my-2">{{ $p->description }}</p><div class="flex gap-2">@if(!$p->is_active)<form method="POST" action="{{ route('admin.plugins.action',[$p->slug,'activate']) }}">@csrf<button class="btn-primary">Activate</button></form>@else<form method="POST" action="{{ route('admin.plugins.action',[$p->slug,'deactivate']) }}">@csrf<button class="border rounded px-3 py-1">Deactivate</button></form>@endif</div></div>@endforeach</div>
@endsection
