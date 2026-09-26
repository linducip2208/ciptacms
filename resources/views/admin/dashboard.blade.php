@extends('admin.layout')
@section('title','Dashboard')@section('crumb','Dashboard')
@section('content')
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
@foreach(['users'=>'Users','pages'=>'Pages','posts'=>'Posts','orders'=>'Orders','products'=>'Products','leads'=>'Leads','revenue'=>'Revenue'] as $k=>$l)
<div class="card p-4"><div class="text-xs text-slate-500">{{ $l }}</div><div class="text-2xl font-bold">{{ is_numeric($stats[$k]??0)?number_format($stats[$k]):$stats[$k] }}</div></div>
@endforeach
</div>
<div class="grid lg:grid-cols-2 gap-4">
<div class="card p-4"><h3 class="font-semibold mb-3">Recent activity</h3>@forelse($activity as $a)<div class="text-sm py-2 border-b border-slate-100 dark:border-slate-800"><b>{{ $a->action }}</b> <span class="text-slate-500">{{ $a->entity_type }} #{{ $a->entity_id }}</span><div class="text-xs text-slate-400">{{ $a->created_at }} · {{ $a->ip }}</div></div>@empty<div class="text-sm text-slate-500">No activity yet.</div>@endforelse</div>
<div class="card p-4"><h3 class="font-semibold mb-3">Quick links</h3><div class="flex flex-wrap gap-2"><a class="btn-primary" href="{{ route('admin.cms.pages.create') }}">+ Page</a><a class="btn-primary" href="{{ route('admin.resource.create','products') }}">+ Product</a><a class="btn-primary" href="{{ route('admin.resource.create','leads') }}">+ Lead</a><a class="btn-primary" href="{{ route('admin.cms.workflows') }}">Workflows</a></div>
<div class="mt-4 text-sm text-slate-500">Widgets: {{ $widgets->count() }} configured. Permission-aware dashboard builder ready at Data Types & Widgets.</div></div>
</div>
@endsection
