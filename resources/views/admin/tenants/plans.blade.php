@extends('admin.layout')
@section('title','Plans')@section('crumb','Plans')
@section('content')
<div class="card p-4 mb-4"><form method="POST" action="{{ route('admin.plans.store') }}" class="flex gap-2">@csrf<input name="name" required placeholder="Name" class="input !w-40"><input name="slug" required placeholder="slug" class="input !w-40"><input name="price" type="number" placeholder="Price" class="input !w-32"><button class="btn-primary">+ Plan</button></form></div><div class="grid md:grid-cols-3 gap-3">@foreach($plans as $p)<div class="card p-4"><b>{{ $p->name }}</b><div class="text-2xl font-bold">{{ number_format($p->price) }} {{ $p->currency }}</div><div class="text-xs text-slate-500">{{ $p->interval }} · {{ $p->slug }}</div></div>@endforeach</div>
@endsection
