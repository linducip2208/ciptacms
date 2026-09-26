@extends('admin.layout')
@section('title','Plugins')@section('crumb','Plugins')
@section('content')
<div class="row row-cards">@foreach($plugins as $p)<div class="card"><div class="d-flex justify-content-between"><b>{{ $p->name }}</b><span class="badge">{{ $p->is_active?'active':'inactive' }}</span></div><div class="text-muted small">{{ $p->slug }} · v{{ $p->version }}</div><p class="text-sm my-2">{{ $p->description }}</p><div class="d-flex gap-2">@if(!$p->is_active)<form method="POST" action="{{ route('admin.plugins.action',[$p->slug,'activate']) }}">@csrf<button class="btn btn-primary">Activate</button></form>@else<form method="POST" action="{{ route('admin.plugins.action',[$p->slug,'deactivate']) }}">@csrf<button class="border rounded px-3 py-1">Deactivate</button></form>@endif</div></div>@endforeach</div>
@endsection
