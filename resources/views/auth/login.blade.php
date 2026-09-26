<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login | Lindu CMS</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="min-h-screen grid place-items-center bg-slate-100"><div class="w-full max-w-sm bg-white rounded-2xl shadow p-6">
<div class="flex items-center gap-2 mb-4"><div class="w-9 h-9 rounded-xl bg-indigo-600 text-white grid place-items-center font-bold">L</div><b>{{ setting('general.site_name','Lindu CMS') }}</b></div>
@if($errors->any())<div class="mb-3 text-sm text-rose-600">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
@if(session('ok'))<div class="mb-3 text-sm text-emerald-600">{{ session('ok') }}</div>@endif
<form method="POST" action="{{ route('login.attempt') }}">@csrf
<label class="text-sm">Email</label><input name="email" type="email" required class="w-full border rounded-lg px-3 py-2 mb-3" value="{{ old('email') }}">
<label class="text-sm">Password</label><input name="password" type="password" required class="w-full border rounded-lg px-3 py-2 mb-3">
<label class="text-sm flex items-center gap-2 mb-3"><input type="checkbox" name="remember"> Remember me</label>
<button class="w-full bg-indigo-600 text-white rounded-lg py-2">Login</button></form>
<div class="text-sm mt-3 flex justify-between"><a href="{{ route('register') }}" class="text-indigo-600">Register</a><a href="{{ route('password.request') }}" class="text-slate-500">Forgot?</a></div>
</div></body></html>
