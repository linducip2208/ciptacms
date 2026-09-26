@extends('admin.layout')
@section('title','Plans')@section('crumb','Plans')
@section('content')
<div class="card mb-4"><form method="POST" action="{{ route('admin.plans.store') }}" class="d-flex gap-2">@csrf<input name="name" required placeholder="Name" class="form-control !w-40"><input name="slug" required placeholder="slug" class="form-control !w-40"><input name="price" type="number" placeholder="Price" class="form-control !w-32"><button class="btn btn-primary">+ Plan</button></form></div><div class="grid md:grid-cols-3 gap-3">@foreach($plans as $p)<div class="card"><b>{{ $p->name }}</b><div class="text-2xl font-bold">{{ number_format($p->price) }} {{ $p->currency }}</div><div class="text-muted small">{{ $p->interval }} · {{ $p->slug }}</div></div>@endforeach</div>
@endsection
