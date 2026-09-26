@extends('admin.layout')
@section('title','Sessions & Devices')@section('crumb','Security / Sessions')
@section('content')
<div class="row row-cards">
<div class="card"><div class="d-flex justify-content-between items-center mb-2"><h3 class="font-semibold">Active sessions</h3><form method="POST" action="{{ route('admin.sessions.others') }}">@csrf<button class="text-sm text-rose-600">Logout other devices</button></form></div>
@foreach($rows as $s)<div class="text-sm border-b py-2 flex justify-between"><div><div class="font-medium">{{ $s->device ?: Str::limit($s->user_agent,60) }}</div><div class="text-muted small">{{ $s->ip_address }} · {{ date('d M Y H:i',$s->last_activity) }} {{ $s->id===session()->getId()?'(this device)':'' }}</div></div><form method="POST" action="{{ route('admin.sessions.revoke',$s->id) }}">@csrf @method('DELETE')<button class="text-xs text-rose-600">Revoke</button></form></div>@endforeach
</div>
<div class="card"><h3 class="font-semibold mb-2">Login history</h3>@foreach($hist as $h)<div class="text-sm border-b py-1"><span class="badge">{{ $h->status }}</span> {{ $h->ip }} · {{ Str::limit($h->user_agent,50) }}<div class="text-xs text-slate-400">{{ $h->logged_in_at }} {{ $h->failed_reason }}</div></div>@endforeach</div>
</div>
@endsection
