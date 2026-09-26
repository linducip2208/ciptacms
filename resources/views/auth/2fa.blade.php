<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>2FA | Lindu</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="min-h-screen grid place-items-center bg-slate-100"><div class="w-full max-w-sm bg-white rounded-2xl shadow p-6"><h1 class="font-bold mb-2">Two-factor check</h1>
<p class="text-sm text-slate-500 mb-3">Buka aplikasi authenticator lalu masukkan kode 6 digit, atau gunakan backup code.</p>
@if($errors->any())<div class="text-rose-600 text-sm mb-2">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
<form method="POST" action="{{ route('2fa.verify') }}">@csrf<input name="code" autofocus placeholder="123456" class="w-full border rounded-lg px-3 py-2 mb-3 tracking-widest text-center text-xl"><button class="w-full bg-indigo-600 text-white rounded-lg py-2">Verify</button></form></div></body></html>
