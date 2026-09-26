@extends('admin.layout')
@section('title','Two-Factor Auth')@section('crumb','Security / 2FA')
@section('content')
<div class="card p-5 max-w-2xl">
<h2 class="font-bold text-lg">Two-Factor Authentication (TOTP)</h2>
<p class="text-sm text-slate-500">Status: {!! $user->two_factor_enabled?'<b class="text-emerald-600">AKTIF</b>':'<b class="text-slate-500">NONAKTIF</b>' !!}</p>
@if(!$user->two_factor_enabled)
<div class="mt-3 text-sm"><b>1.</b> Scan URL ini di Google Authenticator / Authy:<div class="font-mono text-xs break-all bg-slate-50 border rounded p-2 mt-1">{{ $otpauth }}</div>
<div class="mt-1">Secret: <code>{{ $user->two_factor_secret }}</code></div>
<b>2.</b> Masukkan kode 6 digit:</div>
<form method="POST" action="{{ route('admin.2fa.enable') }}" class="mt-2 flex gap-2">@csrf<input name="code" class="input !w-40" placeholder="123456" required><button class="btn-primary">Aktifkan</button></form>
@else
<div class="mt-3"><b>Backup codes:</b><div class="grid grid-cols-2 gap-1 font-mono text-sm mt-1">@foreach((array)$user->two_factor_backup_codes as $c)<div class="border rounded px-2 py-1">{{ $c }}</div>@endforeach</div></div>
<form method="POST" action="{{ route('admin.2fa.disable') }}" class="mt-3">@csrf<button class="border rounded-lg px-3 py-1 text-rose-600" onclick="return confirm('Matikan 2FA?')">Disable 2FA</button></form>
@endif
</div>
@endsection
