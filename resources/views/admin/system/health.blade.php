@extends('admin.layout')
@section('title','Health')@section('crumb','Health')
@section('content')
<div class="grid md:grid-cols-2 gap-4"><div class="card"><h3 class="font-semibold mb-2">Checks</h3>@foreach($checks as $k=>$c)<div class="d-flex justify-content-between text-sm border-b py-1"><span>{{ $k }}</span><b class="{{ $c['ok']?'text-emerald-600':'text-rose-600' }}">{{ $c['ok']?'OK':'FAIL' }}</b></div>@endforeach</div><div class="card"><h3 class="font-semibold mb-2">System</h3><pre class="text-xs">{{ json_encode($info, JSON_PRETTY_PRINT) }}</pre></div></div>
@endsection
