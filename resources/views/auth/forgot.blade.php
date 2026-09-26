<!DOCTYPE html><html><head><meta charset="utf-8"><title>Forgot | Lindu</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="min-h-screen grid place-items-center bg-slate-100"><div class="w-full max-w-sm bg-white rounded-2xl shadow p-6"><h1 class="font-bold mb-3">Reset password</h1>
@if(session('ok'))<div class="text-emerald-600 text-sm mb-2">{{ session('ok') }}</div>@endif
<form method="POST" action="{{ route('password.email') }}">@csrf<input name="email" type="email" required placeholder="Email" class="w-full border rounded-lg px-3 py-2 mb-3"><button class="w-full bg-indigo-600 text-white rounded-lg py-2">Send link</button></form></div></body></html>
