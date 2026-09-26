@extends('admin.layout')
@section('title','Payment Gateways')@section('crumb','Payments')
@section('content')
<div class="card max-w-3xl"><h2 class="font-bold mb-2">Payment adapters</h2>
<p class="text-muted mb-3">Adapter-based: Xendit, iPaymu, Tripay, Stripe, Manual. Secret tersimpan terenkripsi.</p>
<form method="POST" action="{{ route('admin.gateways.save') }}">@csrf
<label class="form-label">Default gateway</label><select name="gateway" class="form-control mb-3">@foreach($adapters as $a)<option value="{{ $a }}" {{ $current===$a?'selected':'' }}>{{ $a }}</option>@endforeach</select>
@foreach(['xendit','ipaymu','tripay','stripe'] as $g)<div class="border rounded p-2 mb-2"><b class="text-sm">{{ $g }}</b><div class="grid md:grid-cols-3 gap-2 mt-1"><input name="{{ $g }}_key" class="form-control" placeholder="key"><input name="{{ $g }}_secret" class="form-control" placeholder="secret"><select name="{{ $g }}_mode" class="form-control"><option value="sandbox">sandbox</option><option value="live">live</option></select></div></div>@endforeach
<button class="btn btn-primary">Save</button></form>
<form method="POST" action="{{ route('admin.gateways.test') }}" class="mt-3 flex gap-2">@csrf<input name="amount" value="10000" class="form-control !w-40"><button class="border rounded-lg px-3">Test charge</button></form>
</div>
@endsection
