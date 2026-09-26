@extends('admin.layout')
@section('title','Dashboard')@section('crumb','Dashboard')
@section('content')
<div class="row row-cards mb-3">
@foreach(['users'=>'Users|ti-user','pages'=>'Pages|ti-file','posts'=>'Posts|ti-pencil','orders'=>'Orders|ti-receipt','products'=>'Products|ti-package','leads'=>'Leads|ti-target','revenue'=>'Revenue|ti-coins'] as $k=>$v)
@php [$l,$ic]=explode('|',$v); @endphp
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="d-flex align-items-center"><span class="avatar bg-blue-lt me-2"><i class="ti {{ $ic }}"></i></span><div><div class="text-muted small">{{ $l }}</div><div class="h2 m-0">{{ is_numeric($stats[$k]??0)?number_format($stats[$k]):$stats[$k] }}</div></div></div></div></div></div>
@endforeach
</div>
<div class="row row-cards">
<div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">Recent activity</h3></div><div class="list-group list-group-flush">@forelse($activity as $a)<div class="list-group-item"><b>{{ $a->action }}</b> <span class="text-muted">{{ $a->entity_type }} #{{ $a->entity_id }}</span><div class="text-muted small">{{ $a->created_at }} · {{ $a->ip }}</div></div>@empty<div class="list-group-item text-muted">No activity yet.</div>@endforelse</div></div></div>
<div class="col-lg-6"><div class="card"><div class="card-header"><h3 class="card-title">Quick links</h3></div><div class="card-body d-flex flex-wrap gap-2"><a class="btn btn-primary" href="{{ route('admin.cms.pages.create') }}">+ Page</a><a class="btn btn-primary" href="{{ route('admin.resource.create','products') }}">+ Product</a><a class="btn btn-primary" href="{{ route('admin.resource.create','leads') }}">+ Lead</a><a class="btn btn-outline-primary" href="{{ route('admin.cms.workflows') }}">Workflows</a></div><div class="card-footer text-muted">Widgets: {{ $widgets->count() }} configured.</div></div></div>
</div>
@endsection
