@extends('admin.layout')
@section('title','Two-Factor Auth')@section('crumb','Security / 2FA')
@section('content')
<div class="card max-w-2xl">
<h2 class="font-bold text-lg">Two-Factor Authentication (TOTP)</h2>
<p class="text-muted">Status: {!! $user->two_factor_enabled?'<b class="text-emerald-600">AKTIF</b>':'<b class="text-slate-500">NONAKTIF</b>' !!}</p>
@if(!$user->two_factor_enabled)
<div class="mt-3 text-sm grid md:grid-cols-2 gap-4">
<div>
<b>1. Scan QR di Authenticator:</b>
@if(!empty($qr))<div class="bg-white border rounded p-3 mt-1 d-inline-block">{!! $qr !!}</div>@else<div id="qr" class="bg-white border rounded p-3 mt-1 grid place-items-center min-h-[180px]"></div>@endif
<div class="font-mono text-[11px] break-all bg-slate-50 border rounded p-2 mt-1">{{ $otpauth }}</div>
<div class="mt-1">Secret: <code id="tfaSecret">{{ $user->two_factor_secret }}</code> <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('tfaSecret').innerText)" class="text-indigo-600 text-xs">Copy</button></div>
</div>
<div>
<b>2. Masukkan kode 6 digit:</b>
<form method="POST" action="{{ route('admin.2fa.enable') }}" class="mt-2 flex gap-2">@csrf<input name="code" inputmode="numeric" autocomplete="one-time-code" class="form-control !w-40 tracking-widest text-center" placeholder="123456" required><button class="btn btn-primary">Aktifkan</button></form>
<p class="text-muted small mt-2">Gunakan Google Authenticator / Authy / 1Password. Waktu server & HP harus sinkron.</p>
</div>
</div>
@if(empty($qr))
{{-- PHP could not produce the QR; fall back to the bundled renderer. --}}
@vite(['resources/js/qr.js'])
<script>renderLinduQr(document.getElementById('qr'), @json($otpauth), 180);</script>
@endif
@else
<div class="mt-3"><div class="d-flex justify-content-between items-center"><b>Backup codes:</b><div class="d-flex gap-2"><form method="POST" action="{{ route('admin.2fa.regen') }}">@csrf<button class="text-xs text-indigo-600">Regenerate</button></form><button class="text-xs" onclick="window.print()">Print</button></div></div>
<div class="row g-1 font-mono text-sm mt-1">@foreach((array)$user->two_factor_backup_codes as $c)<div class="border rounded px-2 py-1">{{ $c }}</div>@endforeach</div>
<p class="text-muted small mt-1">Simpan di tempat aman. Satu kode hanya sekali pakai (saat HP hilang).</p></div>
<form method="POST" action="{{ route('admin.2fa.disable') }}" class="mt-3">@csrf<button class="border rounded-lg px-3 py-1 text-rose-600" onclick="return confirm('Matikan 2FA?')">Disable 2FA</button></form>
@endif
</div>
@endsection
