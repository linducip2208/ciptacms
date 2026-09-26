@extends('admin.layout')
@section('title','Payment Gateways')@section('crumb','Payments')
@section('content')
<div class="card p-5 max-w-3xl"><h2 class="font-bold mb-2">Payment adapters</h2>
<p class="text-sm text-slate-500 mb-3">Adapter-based: Xendit, iPaymu, Tripay, Stripe, Manual. Secret tersimpan terenkripsi.</p>
<form method="POST" action="{{ route('admin.gateways.save') }}">@csrf
<label class="text-sm">Default gateway</label><select name="gateway" class="input mb-3">@foreach($adapters as $a)<option value="{{ $a }}" {{ $current===$a?'selected':'' }}>{{ $a }}</option>@endforeach</select>
@foreach(['xendit','ipaymu','tripay','stripe'] as $g)<div class="border rounded p-2 mb-2"><b class="text-sm">{{ $g }}</b><div class="grid md:grid-cols-3 gap-2 mt-1"><input name="{{ $g }}_key" class="input" placeholder="key"><input name="{{ $g }}_secret" class="input" placeholder="secret"><select name="{{ $g }}_mode" class="input"><option value="sandbox">sandbox</option><option value="live">live</option></select></div></div>@endforeach
<button class="btn-primary">Save</button></form>
<form method="POST" action="{{ route('admin.gateways.test') }}" class="mt-3 flex gap-2">@csrf<input name="amount" value="10000" class="input !w-40"><button class="border rounded-lg px-3">Test charge</button></form>
</div>
@endsection
